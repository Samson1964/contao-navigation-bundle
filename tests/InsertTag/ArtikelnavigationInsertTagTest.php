<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt die Inserttags {{artikelnavigation}} und
 * {{seitennavigation}} bereit.
 *
 * @author    Frank Binding
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoNavigationBundle\Tests\InsertTag;

use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoNavigationBundle\InsertTag\ArtikelnavigationInsertTag;
use Schachbulle\ContaoNavigationBundle\Tests\Fixtures\ErsetzungsSchleifen;
use Schachbulle\ContaoNavigationBundle\Tests\Fixtures\NavigationsAttrappe;

class ArtikelnavigationInsertTagTest extends TestCase
{
    use ErsetzungsSchleifen;

    public function testFremdeInserttagsWerdenNichtBeansprucht(): void
    {
        $listener = new NavigationsAttrappe();

        $this->assertFalse($listener->doReplace('env::request'));
        $this->assertFalse($listener->doReplace('artikelnavigationen'));
        $this->assertFalse($listener->doReplace('seitennavigation'));
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
     * Der eigentliche Fehlerfall aus 0.2.0: Steht das Inserttag in jedem Artikel
     * und zeigt die Seite alle Artikel vollständig an (kein Anrisstext), stand
     * die Navigation mehrfach auf der Seite.
     */
    public function testSchleifeAusContao413GibtNurEineNavigationAus(): void
    {
        $listener = new NavigationsAttrappe();
        $seite = '<div>{{artikelnavigation}} Artikel 1</div><div>{{artikelnavigation}} Artikel 2</div><div>{{artikelnavigation}} Artikel 3</div>';

        $ergebnis = $this->ersetzeWieContao413([$listener], $seite);

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

        $ergebnis = $this->ersetzeWieContao5([$listener], $seite);

        $this->assertSame(
            '<div><nav>aktuell</nav> Artikel 1</div><div> Artikel 2</div>',
            $ergebnis,
        );
        $this->assertSame(1, $listener->aufrufe);
    }

    public function testOhneAusgewaehltenArtikelIstDieSeiteDerAktuelleEintrag(): void
    {
        $eintraege = ArtikelnavigationInsertTag::baueEintraege(
            'Meine Seite',
            '/meine-seite.html',
            [
                ['alias' => 'erster', 'title' => 'Erster', 'url' => '/meine-seite/articles/erster.html'],
                ['alias' => 'zweiter', 'title' => 'Zweiter', 'url' => '/meine-seite/articles/zweiter.html'],
            ],
            null,
        );

        $this->assertSame(
            [
                ['titel' => 'Meine Seite', 'url' => '/meine-seite.html', 'aktiv' => true, 'imPfad' => true],
                ['titel' => 'Zweiter', 'url' => '/meine-seite/articles/zweiter.html', 'aktiv' => false, 'imPfad' => false],
            ],
            $eintraege,
        );
    }

    public function testDerAufgerufeneArtikelIstDerAktuelleEintrag(): void
    {
        $eintraege = ArtikelnavigationInsertTag::baueEintraege(
            'Meine Seite',
            '/meine-seite.html',
            [
                ['alias' => 'erster', 'title' => 'Erster', 'url' => '/meine-seite/articles/erster.html'],
                ['alias' => 'zweiter', 'title' => 'Zweiter', 'url' => '/meine-seite/articles/zweiter.html'],
                ['alias' => 'dritter', 'title' => 'Dritter', 'url' => '/meine-seite/articles/dritter.html'],
            ],
            'zweiter',
        );

        $this->assertSame([false, true, false], array_column($eintraege, 'aktiv'));
        $this->assertSame(['Meine Seite', 'Zweiter', 'Dritter'], array_column($eintraege, 'titel'));
    }

    public function testOhneArtikelGibtEsKeineEintraege(): void
    {
        $this->assertSame([], ArtikelnavigationInsertTag::baueEintraege('Meine Seite', '/meine-seite.html', [], null));
    }

    public function testEinzelnerArtikelErgibtNurDenSeiteneintrag(): void
    {
        $eintraege = ArtikelnavigationInsertTag::baueEintraege(
            'Meine Seite',
            '/meine-seite.html',
            [['alias' => 'erster', 'title' => 'Erster', 'url' => '/meine-seite/articles/erster.html']],
            null,
        );

        $this->assertCount(1, $eintraege);
        $this->assertSame('Meine Seite', $eintraege[0]['titel']);
    }
}
