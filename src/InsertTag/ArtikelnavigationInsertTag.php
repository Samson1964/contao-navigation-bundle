<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Inserttag {{artikelnavigation}} bereit.
 *
 * @author    Frank Binding
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoNavigationBundle\InsertTag;

use Contao\ArticleModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\Input;
use Contao\PageModel;
use Contao\System;

/**
 * Erzeugt eine waagerechte Navigation über die Artikel einer Seite.
 *
 * Die Klasse bedient zwei Schnittstellen:
 *
 *  - ab Contao 5.2 das Attribut AsInsertTag (siehe __invoke()),
 *  - bis Contao 5.1 (und damit auch Contao 4.13) den Hook "replaceInsertTags"
 *    (siehe doReplace()), der in der config.php nur dann registriert wird,
 *    wenn das Attribut nicht zur Verfügung steht.
 */
class ArtikelnavigationInsertTag
{
    /**
     * Name des Inserttags.
     */
    public const TAG = 'artikelnavigation';

    /**
     * Spalte, aus der die Artikel gelesen werden.
     */
    public const SPALTE = 'main';

    /**
     * Parameter, mit dem ein bereits ausgegebenes Vorkommen stillgelegt wird.
     *
     * Contao 4.13 merkt sich das Ergebnis eines Inserttags für die Dauer des
     * Seitenaufbaus (Contao\InsertTags::$arrItCache) und ruft den Hook für
     * jedes weitere gleichlautende Vorkommen gar nicht mehr auf. Ein Zähler
     * im Hook kann die Wiederholungen deshalb nicht unterdrücken. Statt
     * dessen werden die noch offenen Vorkommen im Puffer auf diesen Parameter
     * umgeschrieben; er liefert eine leere Zeichenkette.
     *
     * Der Tagname bleibt dabei unverändert, damit die Ersetzung auch dann
     * greift, wenn die Seite die erlaubten Inserttags einschränkt
     * (Parameter contao.insert_tags.allowed_tags).
     */
    public const PARAMETER_STUMM = 'unterdrueckt';

    /**
     * Bereits ausgegebene Navigationen, Schlüssel ist das Tag samt Parameter.
     *
     * Der Dienst wird vom Container nur einmal je Anfrage erzeugt, die
     * Merkliste gilt also genau für einen Seitenaufbau.
     *
     * @var array<string, true>
     */
    private array $ausgegeben = [];

    /**
     * Inserttag {{artikelnavigation}} bzw. {{artikelnavigation::43}} ab Contao 5.2.
     *
     * @param ResolvedInsertTag $insertTag Das aufgelöste Inserttag; Parameter 0 ist
     *                                     die gewünschte Seiten-ID (optional)
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
     * Hook "replaceInsertTags" für Contao 4.13 bis 5.1.
     *
     * Die Signatur ist durch den Hook vorgegeben. Der Puffer $tags wird
     * ausdrücklich als Referenz entgegengenommen (dafür reicht Contao ihn
     * durch, siehe Contao-Issue #6672): Nach der ersten Ausgabe werden alle
     * weiteren Vorkommen desselben Tags darin stillgelegt, weil Contao 4.13
     * den Hook für sie nicht noch einmal aufruft.
     *
     * Die Parameter 3 ($strCache) und 6 ($arrCache) werden bewusst *nicht* als
     * Referenz deklariert: Contao 5 übergibt dort Literale ('' bzw. array()),
     * eine Referenz würde den Aufruf dort zerreißen.
     *
     * @param string             $strTag   Das Inserttag samt Parametern, aber ohne Flags
     * @param bool               $blnCache Ob die Ausgabe zwischengespeichert wird (ungenutzt)
     * @param string             $strCache Bisheriger Ersatztext (ungenutzt)
     * @param array<int, string> $flags    Die Flags des Inserttags (ungenutzt)
     * @param array<int, string> $tags     Der zerlegte Puffer; gerade Indizes sind Text,
     *                                     ungerade sind Inserttags
     * @param array<string, mixed> $arrCache Contaos Inserttag-Zwischenspeicher (ungenutzt)
     * @param int                $_rit     Position des aktuellen Textstücks im Puffer
     * @param int                $_cnt     Anzahl der Einträge im Puffer
     *
     * @return string|false Der Ersatztext, oder false, wenn das Tag nicht zu diesem
     *                      Bundle gehört
     */
    public function doReplace($strTag, $blnCache = false, $strCache = '', $flags = [], &$tags = [], $arrCache = [], $_rit = 0, $_cnt = 0)
    {
        $teile = explode('::', (string) $strTag);

        if (self::TAG !== strtolower($teile[0])) {
            return false;
        }

        $parameter = $teile[1] ?? null;

        // Ein von uns stillgelegtes Vorkommen: nichts ausgeben, aber das Tag beanspruchen
        if (self::PARAMETER_STUMM === $parameter) {
            return '';
        }

        $inhalt = $this->ersetze($parameter);

        if ('' !== $inhalt) {
            self::legeWeitereVorkommenStill($tags, (int) $_rit, (int) $_cnt, (string) $strTag);
        }

        return $inhalt;
    }

    /**
     * Schreibt alle noch offenen Vorkommen desselben Inserttags im Puffer auf
     * den stillen Parameter um.
     *
     * Der Puffer stammt aus Contao\InsertTags::replace(): gerade Indizes sind
     * Text, ungerade sind Inserttags. Das gerade bearbeitete Tag steht auf
     * $rit + 1, das nächste also auf $rit + 3. Flags werden beim Vergleich
     * abgeschnitten, aber nicht übernommen — das Ergebnis ist ohnehin leer.
     *
     * @param array<int, string> $tags Der Puffer, wird verändert
     * @param int                $rit  Position des aktuellen Textstücks
     * @param int                $cnt  Anzahl der Einträge im Puffer
     * @param string             $tag  Das Inserttag samt Parametern, ohne Flags
     */
    public static function legeWeitereVorkommenStill(array &$tags, int $rit, int $cnt, string $tag): void
    {
        for ($i = $rit + 3; $i < $cnt; $i += 2) {
            if (!isset($tags[$i])) {
                continue;
            }

            if (explode('|', (string) $tags[$i])[0] === $tag) {
                $tags[$i] = self::TAG.'::'.self::PARAMETER_STUMM;
            }
        }
    }

    /**
     * Setzt die Navigation aus den übergebenen Angaben zusammen.
     *
     * Der erste Artikel einer Seite steht nicht für sich selbst, sondern für
     * die Seite: Wer die Seite ohne Artikelparameter aufruft, landet bei ihm.
     * Deshalb trägt der erste Eintrag den Seitentitel. Er ist unverlinkt,
     * solange kein Artikel ausgewählt ist, denn dann ist er der aktuelle.
     *
     * Die Titel werden nicht maskiert. Contao legt sie bereits eingabekodiert
     * in der Datenbank ab; ein zweites Maskieren würde Umlaute und kaufmännische
     * Und-Zeichen sichtbar verstümmeln.
     *
     * @param string $seitenTitel  Titel der Seite
     * @param string $seitenUrl    Adresse der Seite ohne Artikelparameter
     * @param array<int, array{alias: string, title: string, url: string}> $artikel
     *                             Die Artikel der Seite in ihrer Sortierreihenfolge
     * @param string|null $aktiverAlias Alias (oder ID) des gerade aufgerufenen Artikels,
     *                                  null wenn kein Artikel ausgewählt ist
     *
     * @return string Die Navigation als HTML, oder eine leere Zeichenkette,
     *                wenn die Seite keine Artikel hat
     */
    public static function baueNavigation(string $seitenTitel, string $seitenUrl, array $artikel, ?string $aktiverAlias): string
    {
        $eintraege = [];

        foreach (array_values($artikel) as $nummer => $daten) {
            // Der erste Artikel wird durch die Seite selbst vertreten
            if (0 === $nummer) {
                $eintraege[] = null === $aktiverAlias
                    ? $seitenTitel
                    : sprintf('<a href="%s">%s</a>', $seitenUrl, $seitenTitel);

                continue;
            }

            $eintraege[] = (string) $daten['alias'] === $aktiverAlias
                ? $daten['title']
                : sprintf('<a href="%s">%s</a>', $daten['url'], $daten['title']);
        }

        return implode(' | ', $eintraege);
    }

    /**
     * Ermittelt den Ersatztext für ein Vorkommen des Inserttags.
     *
     * Jede Navigation wird je Seitenaufbau nur einmal ausgegeben. Steht das
     * Inserttag in jedem Artikel einer Seite, erscheint es beim Blick auf die
     * ganze Seite (also wenn die Artikel keinen Anrisstext benutzen) sonst so
     * oft, wie die Seite Artikel hat.
     *
     * @param string|null $parameter Die gewünschte Seiten-ID, oder null für die aktuelle Seite
     *
     * @return string Die Navigation als HTML, oder eine leere Zeichenkette, wenn
     *                die Navigation schon steht, die Seite unbekannt ist oder
     *                keine Artikel hat
     */
    private function ersetze(?string $parameter): string
    {
        $parameter = ('' === $parameter) ? null : $parameter;

        if (self::PARAMETER_STUMM === $parameter) {
            return '';
        }

        $schluessel = self::TAG.'::'.($parameter ?? '');

        if (isset($this->ausgegeben[$schluessel])) {
            return '';
        }

        $inhalt = $this->baue($parameter);

        if ('' !== $inhalt) {
            $this->ausgegeben[$schluessel] = true;
        }

        return $inhalt;
    }

    /**
     * Liest die Artikel der gewünschten Seite und übergibt sie an baueNavigation().
     *
     * @param string|null $parameter Die gewünschte Seiten-ID, oder null für die aktuelle Seite
     *
     * @return string Die Navigation als HTML, oder eine leere Zeichenkette
     */
    protected function baue(?string $parameter): string
    {
        $seite = $this->ermittleSeite($parameter);

        if (null === $seite) {
            return '';
        }

        $artikel = ArticleModel::findPublishedByPidAndColumn((int) $seite->id, self::SPALTE);

        if (null === $artikel) {
            return '';
        }

        $aktuelle = $this->aktuelleSeite();
        $aktiverAlias = null;

        // Ein Artikel kann nur auf der gerade aufgerufenen Seite ausgewählt sein
        if (null !== $aktuelle && (int) $aktuelle->id === (int) $seite->id) {
            $angefordert = Input::get('articles');
            $aktiverAlias = (\is_string($angefordert) && '' !== $angefordert) ? $angefordert : null;
        }

        $daten = [];

        foreach ($artikel as $eintrag) {
            $alias = (string) ($eintrag->alias ?: $eintrag->id);

            $daten[] = [
                'alias' => $alias,
                'title' => (string) $eintrag->title,
                'url' => $this->artikelUrl($seite, $eintrag, $alias),
            ];
        }

        return self::baueNavigation((string) $seite->title, $this->seitenUrl($seite), $daten, $aktiverAlias);
    }

    /**
     * Ermittelt die Seite, deren Artikel aufgelistet werden sollen.
     *
     * Ohne Parameter ist das die gerade aufgerufene Seite. Mit Parameter wird
     * eine Seiten-ID erwartet; alles andere gilt als unbekannt, damit ein
     * Tippfehler nicht stillschweigend die aktuelle Seite ausgibt.
     *
     * @param string|null $parameter Die gewünschte Seiten-ID, oder null
     *
     * @return PageModel|null Die Seite, oder null wenn sie nicht ermittelt werden konnte
     */
    private function ermittleSeite(?string $parameter): ?PageModel
    {
        if (null === $parameter) {
            return $this->aktuelleSeite();
        }

        if ((string) (int) $parameter !== $parameter) {
            return null;
        }

        // findByPk() bitte stehen lassen: Der Contao-Codingstandard schlägt
        // findById() vor, das es aber erst ab Contao 5 gibt (Model.php).
        return PageModel::findByPk((int) $parameter);
    }

    /**
     * Liefert die gerade aufgerufene Seite.
     *
     * @return PageModel|null Die Seite, oder null außerhalb des Frontends
     */
    private function aktuelleSeite(): ?PageModel
    {
        $seite = $GLOBALS['objPage'] ?? null;

        return $seite instanceof PageModel ? $seite : null;
    }

    /**
     * Erzeugt die Adresse eines Artikels.
     *
     * Ab Contao 5.3 ist PageModel::getFrontendUrl() als veraltet gekennzeichnet;
     * dort erzeugt der Dienst contao.routing.content_url_generator die Adresse
     * unmittelbar aus dem Artikel. In Contao 4.13 gibt es diesen Dienst noch
     * nicht, deshalb der Rückfall auf den Seitenaufruf mit Artikelparameter.
     *
     * @param PageModel    $seite   Die Seite, zu der der Artikel gehört
     * @param ArticleModel $artikel Der Artikel
     * @param string       $alias   Alias des Artikels, ersatzweise seine ID
     *
     * @return string Die Adresse des Artikels
     */
    private function artikelUrl(PageModel $seite, ArticleModel $artikel, string $alias): string
    {
        $erzeuger = $this->urlErzeuger();

        if (null !== $erzeuger) {
            return $erzeuger->generate($artikel);
        }

        return $seite->getFrontendUrl('/articles/'.$alias);
    }

    /**
     * Erzeugt die Adresse einer Seite.
     *
     * @param PageModel $seite Die Seite
     *
     * @return string Die Adresse der Seite ohne Artikelparameter
     */
    private function seitenUrl(PageModel $seite): string
    {
        $erzeuger = $this->urlErzeuger();

        if (null !== $erzeuger) {
            return $erzeuger->generate($seite);
        }

        return $seite->getFrontendUrl();
    }

    /**
     * Liefert den Dienst zur Adresserzeugung, sofern die Contao-Fassung ihn kennt.
     *
     * Der Dienst wird über seine ID angesprochen, weil es in keiner der beiden
     * Fassungen einen Alias auf den Klassennamen gibt.
     *
     * @return object|null Der Dienst contao.routing.content_url_generator,
     *                     oder null unter Contao 4.13
     */
    private function urlErzeuger(): ?object
    {
        $container = System::getContainer();

        if (null === $container || !$container->has('contao.routing.content_url_generator')) {
            return null;
        }

        return $container->get('contao.routing.content_url_generator');
    }
}
