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

use Contao\FrontendTemplate;
use Contao\PageModel;
use Contao\System;

/**
 * Gemeinsamer Unterbau der beiden Navigations-Inserttags.
 *
 * Hier steht alles, was sich Seiten- und Artikelnavigation teilen: die
 * Anbindung an beide Inserttag-Schnittstellen von Contao, das Unterdrücken
 * wiederholter Vorkommen, das Ermitteln der Seite und das Erzeugen von
 * Adressen und Ausgabe.
 *
 * Die abgeleiteten Klassen liefern den Tagnamen, den Templatenamen und die
 * Einträge; ein eigenes `__invoke()` mit dem Attribut AsInsertTag brauchen sie
 * ebenfalls, weil das Attribut je Klasse einen anderen Tagnamen trägt und ein
 * geerbtes Attribut für beide denselben Namen anmelden würde.
 */
abstract class AbstractNavigationInsertTag
{
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
     * Trennzeichen zwischen zwei Einträgen, an das Template durchgereicht.
     */
    public const TRENNER = ' | ';

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
     * Liefert den Namen des Inserttags, also etwa "seitennavigation".
     */
    abstract public static function tag(): string;

    /**
     * Liefert den Namen des Templates ohne Endung.
     */
    abstract public static function template(): string;

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
     * @param string               $strTag   Das Inserttag samt Parametern, aber ohne Flags
     * @param bool                 $blnCache Ob die Ausgabe zwischengespeichert wird (ungenutzt)
     * @param string               $strCache Bisheriger Ersatztext (ungenutzt)
     * @param array<int, string>   $flags    Die Flags des Inserttags (ungenutzt)
     * @param array<int, string>   $tags     Der zerlegte Puffer; gerade Indizes sind Text,
     *                                       ungerade sind Inserttags
     * @param array<string, mixed> $arrCache Contaos Inserttag-Zwischenspeicher (ungenutzt)
     * @param int                  $_rit     Position des aktuellen Textstücks im Puffer
     * @param int                  $_cnt     Anzahl der Einträge im Puffer
     *
     * @return string|false Der Ersatztext, oder false, wenn das Tag nicht zu dieser
     *                      Klasse gehört
     */
    public function doReplace($strTag, $blnCache = false, $strCache = '', $flags = [], &$tags = [], $arrCache = [], $_rit = 0, $_cnt = 0)
    {
        $teile = explode('::', (string) $strTag);

        if (static::tag() !== strtolower($teile[0])) {
            return false;
        }

        $parameter = $teile[1] ?? null;

        // Ein von uns stillgelegtes Vorkommen: nichts ausgeben, aber das Tag beanspruchen
        if (self::PARAMETER_STUMM === $parameter) {
            return '';
        }

        $inhalt = $this->ersetze($parameter);

        if ('' !== $inhalt) {
            static::legeWeitereVorkommenStill($tags, (int) $_rit, (int) $_cnt, (string) $strTag);
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
                $tags[$i] = static::tag().'::'.self::PARAMETER_STUMM;
            }
        }
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
     *                es nichts aufzulisten gibt
     */
    protected function ersetze(?string $parameter): string
    {
        $parameter = ('' === $parameter) ? null : $parameter;

        if (self::PARAMETER_STUMM === $parameter) {
            return '';
        }

        $schluessel = static::tag().'::'.($parameter ?? '');

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
     * Baut die Navigation für die gewünschte Seite.
     *
     * @param string|null $parameter Die gewünschte Seiten-ID, oder null für die aktuelle Seite
     *
     * @return string Die Navigation als HTML, oder eine leere Zeichenkette
     */
    abstract protected function baue(?string $parameter): string;

    /**
     * Gibt die Einträge über das Template des Inserttags aus.
     *
     * Ohne Einträge wird gar nicht erst gerendert, damit ein Template mit
     * Rahmen (etwa ein <nav>-Element) nicht leer auf der Seite landet.
     *
     * @param array<int, array{titel: string, url: string, aktiv: bool, imPfad: bool}> $eintraege
     *                             Die Einträge in ihrer Reihenfolge
     * @param PageModel $seite     Die Seite, auf die sich die Navigation bezieht
     *
     * @return string Die gerenderte Navigation, oder eine leere Zeichenkette
     */
    protected function rendere(array $eintraege, PageModel $seite): string
    {
        if (!$eintraege) {
            return '';
        }

        $template = new FrontendTemplate(static::template());
        $template->eintraege = $eintraege;
        $template->trenner = self::TRENNER;
        $template->seite = $seite;

        return $template->parse();
    }

    /**
     * Ermittelt die Seite, auf die sich die Navigation bezieht.
     *
     * Ohne Parameter ist das die gerade aufgerufene Seite. Mit Parameter wird
     * eine Seiten-ID erwartet; alles andere gilt als unbekannt, damit ein
     * Tippfehler nicht stillschweigend die aktuelle Seite ausgibt.
     *
     * @param string|null $parameter Die gewünschte Seiten-ID, oder null
     *
     * @return PageModel|null Die Seite, oder null wenn sie nicht ermittelt werden konnte
     */
    protected function ermittleSeite(?string $parameter): ?PageModel
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
    protected function aktuelleSeite(): ?PageModel
    {
        $seite = $GLOBALS['objPage'] ?? null;

        return $seite instanceof PageModel ? $seite : null;
    }

    /**
     * Erzeugt die Adresse einer Seite.
     *
     * Weiterleitungs- und Verweisseiten bekommen ebenfalls ihre eigene
     * Adresse; Contao leitet den Besucher beim Aufruf selbst weiter.
     *
     * @param PageModel $seite Die Seite
     *
     * @return string Die Adresse der Seite
     */
    protected function seitenUrl(PageModel $seite): string
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
     * Ab Contao 5.3 ist PageModel::getFrontendUrl() als veraltet gekennzeichnet;
     * dort erzeugt dieser Dienst die Adresse unmittelbar aus dem Modell. In
     * Contao 4.13 gibt es ihn noch nicht. Angesprochen wird er über seine ID,
     * weil es in keiner der beiden Fassungen einen Alias auf den Klassennamen gibt.
     *
     * @return object|null Der Dienst contao.routing.content_url_generator,
     *                     oder null unter Contao 4.13
     */
    protected function urlErzeuger(): ?object
    {
        $container = System::getContainer();

        if (null === $container || !$container->has('contao.routing.content_url_generator')) {
            return null;
        }

        return $container->get('contao.routing.content_url_generator');
    }
}
