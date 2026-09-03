<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Inserttag {{artikelnavigation}} bereit.
 *
 * @author    Frank Binding
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoNavigationBundle\Tests\InsertTag;

use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoNavigationBundle\InsertTag\ArtikelnavigationInsertTag;
use Schachbulle\ContaoNavigationBundle\Tests\Fixtures\NavigationsAttrappe;

class ArtikelnavigationInsertTagTest extends TestCase
{
    public function testFremdeInserttagsWerdenNichtBeansprucht(): void
    {
        $listener = new NavigationsAttrappe();

        $this->assertFalse($listener->doReplace('env::request'));
        $this->assertFalse($listener->doReplace('artikelnavigationen'));
        $this->assertFalse($listener->doReplace(''));
        $this->assertSame(0, $listener->aufrufe);
    }

    public function testWiederholteAufrufeGebenNurEinmalAus(): void
    {
        $listener = new NavigationsAttrappe();

        $this->assertSame('<nav>aktuell</nav>', $listener->doReplace('artikelnavigation'));
        $this->assertSame('', $listener->doReplace('artikelnavigation'));
        $this->assertSame('', $listener->doReplace('artikelnavigation'));
        $this->assertSame(1, $listener->aufrufe);
    }

    public function testNavigationenVerschiedenerSeitenBleibenEigenstaendig(): void
    {
        $listener = new NavigationsAttrappe();

        $this->assertSame('<nav>aktuell</nav>', $listener->doReplace('artikelnavigation'));
        $this->assertSame('<nav>43</nav>', $listener->doReplace('artikelnavigation::43'));
        $this->assertSame('', $listener->doReplace('artikelnavigation::43'));
        $this->assertSame(2, $listener->aufrufe);
    }

    public function testStillgelegtesVorkommenLiefertLeer(): void
    {
        $listener = new NavigationsAttrappe();

        $this->assertSame('', $listener->doReplace('artikelnavigation::'.ArtikelnavigationInsertTag::PARAMETER_STUMM));
        $this->assertSame(0, $listener->aufrufe);
    }

    public function testWeitereVorkommenWerdenImPufferStillgelegt(): void
    {
        $tags = ['Text ', 'artikelnavigation', ' Mitte ', 'artikelnavigation|strtolower', ' und ', 'env::request', ' Ende'];

        ArtikelnavigationInsertTag::legeWeitereVorkommenStill($tags, 0, \count($tags), 'artikelnavigation');

        // Das gerade bearbeitete Vorkommen bleibt unangetastet
        $this->assertSame('artikelnavigation', $tags[1]);
        $this->assertSame('artikelnavigation::unterdrueckt', $tags[3]);
        $this->assertSame('env::request', $tags[5]);
    }

    public function testVorkommenAndererSeitenBleibenImPufferStehen(): void
    {
        $tags = ['', 'artikelnavigation', '', 'artikelnavigation::43', ''];

        ArtikelnavigationInsertTag::legeWeitereVorkommenStill($tags, 0, \count($tags), 'artikelnavigation');

        $this->assertSame('artikelnavigation::43', $tags[3]);
    }

    /**
     * Der eigentliche Fehlerfall: Steht das Inserttag in jedem Artikel und
     * zeigt die Seite alle Artikel vollständig an (kein Anrisstext), stand die
     * Navigation bisher mehrfach auf der Seite.
     */
    public function testSchleifeAusContao413GibtNurEineNavigationAus(): void
    {
        $listener = new NavigationsAttrappe();
        $seite = '<div>{{artikelnavigation}} Artikel 1</div><div>{{artikelnavigation}} Artikel 2</div><div>{{artikelnavigation}} Artikel 3</div>';

        $ergebnis = $this->ersetzeWieContao413($listener, $seite);

        $this->assertSame(
            '<div><nav>aktuell</nav> Artikel 1</div><div> Artikel 2</div><div> Artikel 3</div>',
            $ergebnis,
        );
        $this->assertSame(1, $listener->aufrufe);
    }

    public function testSchleifeAusContao5GibtNurEineNavigationAus(): void
    {
        $listener = new NavigationsAttrappe();
        $seite = '<div>{{artikelnavigation}} Artikel 1</div><div>{{artikelnavigation}} Artikel 2</div>';

        $ergebnis = $this->ersetzeWieContao5($listener, $seite);

        $this->assertSame(
            '<div><nav>aktuell</nav> Artikel 1</div><div> Artikel 2</div>',
            $ergebnis,
        );
        $this->assertSame(1, $listener->aufrufe);
    }

    public function testOhneAusgewaehltenArtikelStehtDieSeiteUnverlinktVorn(): void
    {
        $ergebnis = ArtikelnavigationInsertTag::baueNavigation(
            'Meine Seite',
            '/meine-seite.html',
            [
                ['alias' => 'erster', 'title' => 'Erster', 'url' => '/meine-seite/articles/erster.html'],
                ['alias' => 'zweiter', 'title' => 'Zweiter', 'url' => '/meine-seite/articles/zweiter.html'],
            ],
            null,
        );

        $this->assertSame(
            'Meine Seite | <a href="/meine-seite/articles/zweiter.html">Zweiter</a>',
            $ergebnis,
        );
    }

    public function testDerAufgerufeneArtikelIstNichtVerlinkt(): void
    {
        $ergebnis = ArtikelnavigationInsertTag::baueNavigation(
            'Meine Seite',
            '/meine-seite.html',
            [
                ['alias' => 'erster', 'title' => 'Erster', 'url' => '/meine-seite/articles/erster.html'],
                ['alias' => 'zweiter', 'title' => 'Zweiter', 'url' => '/meine-seite/articles/zweiter.html'],
                ['alias' => 'dritter', 'title' => 'Dritter', 'url' => '/meine-seite/articles/dritter.html'],
            ],
            'zweiter',
        );

        $this->assertSame(
            '<a href="/meine-seite.html">Meine Seite</a> | Zweiter | <a href="/meine-seite/articles/dritter.html">Dritter</a>',
            $ergebnis,
        );
    }

    public function testOhneArtikelBleibtDieNavigationLeer(): void
    {
        $this->assertSame('', ArtikelnavigationInsertTag::baueNavigation('Meine Seite', '/meine-seite.html', [], null));
    }

    public function testEinzelnerArtikelErgibtNurDenSeitentitel(): void
    {
        $ergebnis = ArtikelnavigationInsertTag::baueNavigation(
            'Meine Seite',
            '/meine-seite.html',
            [['alias' => 'erster', 'title' => 'Erster', 'url' => '/meine-seite/articles/erster.html']],
            null,
        );

        $this->assertSame('Meine Seite', $ergebnis);
    }

    /**
     * Bildet die Ersetzungsschleife aus Contao\InsertTags::replace() von
     * Contao 4.13 nach.
     *
     * Entscheidend ist der Zwischenspeicher: Ein bereits ersetztes Tag wird für
     * jedes weitere gleichlautende Vorkommen wiederverwendet, der Hook also gar
     * nicht mehr aufgerufen. Genau daran scheiterte der frühere Zähler.
     *
     * @param NavigationsAttrappe $listener Der zu prüfende Hook
     * @param string              $puffer   Der Seitenpuffer mit den Inserttags
     *
     * @return string Der ersetzte Puffer
     */
    private function ersetzeWieContao413(NavigationsAttrappe $listener, string $puffer): string
    {
        $tags = preg_split('/{{([^{}]*)}}/', $puffer, -1, PREG_SPLIT_DELIM_CAPTURE);
        $ausgabe = [];
        $zwischenspeicher = [];

        for ($rit = 0, $cnt = \count($tags); $rit < $cnt; $rit += 2) {
            $ausgabe[$rit] = $tags[$rit];

            if (!isset($tags[$rit + 1])) {
                break;
            }

            $strTag = $tags[$rit + 1];
            $flags = explode('|', $strTag);
            $tag = array_shift($flags);

            if (isset($zwischenspeicher[$strTag])) {
                $ausgabe[$rit + 1] = $zwischenspeicher[$strTag];

                continue;
            }

            $zwischenspeicher[$strTag] = '';
            $wert = $listener->doReplace($tag, false, $zwischenspeicher[$strTag], $flags, $tags, $zwischenspeicher, $rit, $cnt);

            if (false !== $wert) {
                $zwischenspeicher[$strTag] = $wert;
            }

            $ausgabe[$rit + 1] = $zwischenspeicher[$strTag];
        }

        return implode('', $ausgabe);
    }

    /**
     * Bildet die Ersetzungsschleife von Contao 5 nach.
     *
     * Dort entfällt das Lesen aus dem Zwischenspeicher, der Hook wird also für
     * jedes Vorkommen aufgerufen. Die Parameter 3 und 6 sind Literale — deshalb
     * dürfen sie im Hook nicht als Referenz deklariert sein.
     *
     * @param NavigationsAttrappe $listener Der zu prüfende Hook
     * @param string              $puffer   Der Seitenpuffer mit den Inserttags
     *
     * @return string Der ersetzte Puffer
     */
    private function ersetzeWieContao5(NavigationsAttrappe $listener, string $puffer): string
    {
        $tags = preg_split('/{{([^{}]*)}}/', $puffer, -1, PREG_SPLIT_DELIM_CAPTURE);
        $ausgabe = [];

        for ($rit = 0, $cnt = \count($tags); $rit < $cnt; $rit += 2) {
            $ausgabe[$rit] = $tags[$rit];

            if (!isset($tags[$rit + 1])) {
                break;
            }

            $flags = explode('|', $tags[$rit + 1]);
            $tag = array_shift($flags);

            $wert = $listener->doReplace($tag, false, '', $flags, $tags, [], $rit, $cnt);
            $ausgabe[$rit + 1] = false === $wert ? '{{'.$tags[$rit + 1].'}}' : $wert;
        }

        return implode('', $ausgabe);
    }
}
