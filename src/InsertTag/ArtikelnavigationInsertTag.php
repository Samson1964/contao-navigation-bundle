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

use Contao\ArticleModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\Input;
use Contao\PageModel;

/**
 * Erzeugt eine waagerechte Navigation über die Artikel einer Seite.
 *
 * Die Klasse bedient zwei Schnittstellen:
 *
 *  - ab Contao 5.2 das Attribut AsInsertTag (siehe __invoke()),
 *  - bis Contao 5.1 (und damit auch Contao 4.13) den Hook "replaceInsertTags"
 *    (siehe AbstractNavigationInsertTag::doReplace()), der in der config.php
 *    nur dann registriert wird, wenn das Attribut nicht zur Verfügung steht.
 */
class ArtikelnavigationInsertTag extends AbstractNavigationInsertTag
{
    /**
     * Name des Inserttags.
     */
    public const TAG = 'artikelnavigation';

    /**
     * Name des Templates ohne Endung.
     */
    public const TEMPLATE = 'navigation_artikel';

    /**
     * Spalte, aus der die Artikel gelesen werden.
     */
    public const SPALTE = 'main';

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
     * Setzt die Einträge der Navigation aus den übergebenen Angaben zusammen.
     *
     * Der erste Artikel einer Seite steht nicht für sich selbst, sondern für
     * die Seite: Wer die Seite ohne Artikelparameter aufruft, landet bei ihm.
     * Deshalb trägt der erste Eintrag den Seitentitel. Er gilt als der
     * aktuelle, solange kein Artikel ausgewählt ist.
     *
     * Die Titel werden nicht maskiert. Contao legt sie bereits eingabekodiert
     * in der Datenbank ab; ein zweites Maskieren würde Umlaute und kaufmännische
     * Und-Zeichen sichtbar verstümmeln.
     *
     * @param string      $seitenTitel  Titel der Seite
     * @param string      $seitenUrl    Adresse der Seite ohne Artikelparameter
     * @param array<int, array{alias: string, title: string, url: string}> $artikel
     *                                  Die Artikel der Seite in ihrer Sortierreihenfolge
     * @param string|null $aktiverAlias Alias (oder ID) des gerade aufgerufenen Artikels,
     *                                  null wenn kein Artikel ausgewählt ist
     *
     * @return array<int, array{titel: string, url: string, aktiv: bool, imPfad: bool}>
     *                Die Einträge in ihrer Reihenfolge, leer wenn die Seite keine
     *                Artikel hat
     */
    public static function baueEintraege(string $seitenTitel, string $seitenUrl, array $artikel, ?string $aktiverAlias): array
    {
        $eintraege = [];

        foreach (array_values($artikel) as $nummer => $daten) {
            // Der erste Artikel wird durch die Seite selbst vertreten
            if (0 === $nummer) {
                $eintraege[] = [
                    'titel' => $seitenTitel,
                    'url' => $seitenUrl,
                    'aktiv' => null === $aktiverAlias,
                    'imPfad' => true,
                ];

                continue;
            }

            $aktiv = (string) $daten['alias'] === $aktiverAlias;

            $eintraege[] = [
                'titel' => $daten['title'],
                'url' => $daten['url'],
                'aktiv' => $aktiv,
                'imPfad' => $aktiv,
            ];
        }

        return $eintraege;
    }

    /**
     * Liest die Artikel der gewünschten Seite und gibt sie über das Template aus.
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

        $daten = [];

        foreach ($artikel as $eintrag) {
            $alias = (string) ($eintrag->alias ?: $eintrag->id);

            $daten[] = [
                'alias' => $alias,
                'title' => (string) $eintrag->title,
                'url' => $this->artikelUrl($seite, $eintrag, $alias),
            ];
        }

        $eintraege = self::baueEintraege(
            (string) $seite->title,
            $this->seitenUrl($seite),
            $daten,
            $this->aktiverAlias($seite),
        );

        return $this->rendere($eintraege, $seite);
    }

    /**
     * Ermittelt den Alias des gerade aufgerufenen Artikels.
     *
     * Ein Artikel kann nur auf der gerade aufgerufenen Seite ausgewählt sein;
     * bei einer Navigation für eine fremde Seite gibt es deshalb keinen
     * aktuellen Artikel.
     *
     * @param PageModel $seite Die Seite, um deren Artikel es geht
     *
     * @return string|null Der Alias oder die ID aus der Adresse, sonst null
     */
    private function aktiverAlias(PageModel $seite): ?string
    {
        $aktuelle = $this->aktuelleSeite();

        if (null === $aktuelle || (int) $aktuelle->id !== (int) $seite->id) {
            return null;
        }

        $angefordert = Input::get('articles');

        return (\is_string($angefordert) && '' !== $angefordert) ? $angefordert : null;
    }

    /**
     * Erzeugt die Adresse eines Artikels.
     *
     * Ab Contao 5.3 erzeugt der Dienst contao.routing.content_url_generator die
     * Adresse unmittelbar aus dem Artikel. In Contao 4.13 gibt es diesen Dienst
     * noch nicht, deshalb der Rückfall auf den Seitenaufruf mit Artikelparameter.
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
}
