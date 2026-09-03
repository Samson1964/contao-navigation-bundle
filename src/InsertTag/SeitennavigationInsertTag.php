<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt die Inserttags {{artikelnavigation}} und
 * {{seitennavigation}} bereit.
 *
 * @author    Frank Binding
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoNavigationBundle\InsertTag;

use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\PageModel;
use Contao\System;

/**
 * Erzeugt eine waagerechte Navigation über die Unterseiten einer Seite.
 *
 * Aufgelistet werden die direkten Unterseiten der aktuellen Seite; mit
 * Parameter die Unterseiten der angegebenen Seite. Damit lässt sich einer
 * Rubrikseite ein Untermenü mitgeben, ohne dafür ein Navigationsmodul in das
 * Seitenlayout zu hängen.
 *
 * Die Klasse bedient dieselben zwei Schnittstellen wie die Artikelnavigation,
 * siehe AbstractNavigationInsertTag.
 */
class SeitennavigationInsertTag extends AbstractNavigationInsertTag
{
    /**
     * Name des Inserttags.
     */
    public const TAG = 'seitennavigation';

    /**
     * Name des Templates ohne Endung.
     */
    public const TEMPLATE = 'navigation_seiten';

    /**
     * Liefert den Namen des Inserttags.
     */
    public static function tag(): string
    {
        return self::TAG;
    }

    /**
     * Liefert den Namen des Templates ohne Endung.
     */
    public static function template(): string
    {
        return self::TEMPLATE;
    }

    /**
     * Inserttag {{seitennavigation}} bzw. {{seitennavigation::43}} ab Contao 5.2.
     *
     * @param ResolvedInsertTag $insertTag Das aufgelöste Inserttag; Parameter 0 ist
     *                                     die ID der Elternseite (optional)
     *
     * @return InsertTagResult Die Navigation als HTML, oder ein leeres Ergebnis,
     *                         wenn die Navigation auf dieser Seite schon steht
     */
    #[AsInsertTag(self::TAG)]
    public function __invoke(ResolvedInsertTag $insertTag): InsertTagResult
    {
        return new InsertTagResult(
            $this->ersetze($insertTag->getParameters()->get(0)),
            OutputType::html,
        );
    }

    /**
     * Setzt die Einträge der Navigation aus den übergebenen Angaben zusammen.
     *
     * Als „aktiv" gilt genau die gerade aufgerufene Seite; sie wird im Template
     * unverlinkt ausgegeben. Liegt eine Unterseite lediglich im Pfad zur
     * aktuellen Seite, ist sie mit „imPfad" gekennzeichnet, bleibt aber ein
     * Verweis — sonst käme man nicht mehr dorthin zurück.
     *
     * Die Titel werden nicht maskiert; Contao legt sie bereits eingabekodiert
     * in der Datenbank ab.
     *
     * @param array<int, array{id: int, title: string, url: string}> $seiten
     *                                  Die Unterseiten in ihrer Sortierreihenfolge
     * @param int                       $aktuelleId ID der gerade aufgerufenen Seite, 0 wenn keine
     * @param array<int, int|string>    $pfad       Die Seiten-IDs vom Wurzelknoten bis zur
     *                                              aktuellen Seite (PageModel::$trail)
     *
     * @return array<int, array{titel: string, url: string, aktiv: bool, imPfad: bool}>
     *                Die Einträge in ihrer Reihenfolge, leer wenn es keine
     *                sichtbaren Unterseiten gibt
     */
    public static function baueEintraege(array $seiten, int $aktuelleId, array $pfad = []): array
    {
        $pfad = array_map('intval', $pfad);
        $eintraege = [];

        foreach ($seiten as $seite) {
            $id = (int) $seite['id'];

            $eintraege[] = [
                'titel' => $seite['title'],
                'url' => $seite['url'],
                'aktiv' => 0 !== $aktuelleId && $id === $aktuelleId,
                'imPfad' => \in_array($id, $pfad, true),
            ];
        }

        return $eintraege;
    }

    /**
     * Liest die sichtbaren Unterseiten und gibt sie über das Template aus.
     *
     * @param string|null $parameter Die ID der Elternseite, oder null für die aktuelle Seite
     *
     * @return string Die Navigation als HTML, oder eine leere Zeichenkette
     */
    protected function baue(?string $parameter): string
    {
        $seite = $this->ermittleSeite($parameter);

        if (null === $seite) {
            return '';
        }

        // Der Kern kümmert sich hier schon um Veröffentlichung, Start- und
        // Ablaufdatum, die Vorschau sowie um Wurzel- und nicht aufrufbare Seiten
        $unterseiten = PageModel::findPublishedRegularByPid((int) $seite->id);

        if (null === $unterseiten) {
            return '';
        }

        $aktuelle = $this->aktuelleSeite();
        $daten = [];

        foreach ($unterseiten as $unterseite) {
            if (!$this->istSichtbar($unterseite)) {
                continue;
            }

            $daten[] = [
                'id' => (int) $unterseite->id,
                'title' => (string) $unterseite->title,
                'url' => $this->seitenUrl($unterseite),
            ];
        }

        $eintraege = self::baueEintraege(
            $daten,
            null !== $aktuelle ? (int) $aktuelle->id : 0,
            (null !== $aktuelle && \is_array($aktuelle->trail)) ? $aktuelle->trail : [],
        );

        return $this->rendere($eintraege, $seite);
    }

    /**
     * Prüft, ob eine Unterseite für den Besucher in der Navigation auftauchen darf.
     *
     * Geprüft wird dasselbe wie im Navigationsmodul des Kerns
     * (Contao\Module::renderNavigation()):
     *
     *  - im Menü versteckte Seiten bleiben draußen. Das Feld "hide" ist in
     *    Contao 4.13 ein char(1) und ab Contao 5 ein tinyint, deshalb wird nur
     *    auf „wahr" geprüft und nicht auf einen bestimmten Wert;
     *  - nur für Gäste sichtbare Seiten verschwinden, sobald jemand angemeldet
     *    ist (in Contao 4.13 als veraltet gekennzeichnet, in 5 weiterhin so);
     *  - geschützte Seiten erscheinen nur für Mitglieder der berechtigten
     *    Gruppen. loadDetails() muss davor laufen: Es macht aus "groups" ein
     *    Feld und vererbt den Schutz von den übergeordneten Seiten.
     *
     * @param PageModel $seite Die zu prüfende Unterseite
     *
     * @return bool true, wenn die Seite aufgelistet werden darf
     */
    private function istSichtbar(PageModel $seite): bool
    {
        if ($seite->hide) {
            return false;
        }

        $seite->loadDetails();

        $security = System::getContainer()->get('security.helper');

        if ($seite->guests && $security->isGranted('ROLE_MEMBER')) {
            return false;
        }

        if (!$seite->protected) {
            return true;
        }

        return $security->isGranted(ContaoCorePermissions::MEMBER_IN_GROUPS, $seite->groups);
    }
}
