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
use Schachbulle\ContaoNavigationBundle\InsertTag\SeitennavigationInsertTag;
use Schachbulle\ContaoNavigationBundle\Tests\Fixtures\ErsetzungsSchleifen;
use Schachbulle\ContaoNavigationBundle\Tests\Fixtures\NavigationsAttrappe;
use Schachbulle\ContaoNavigationBundle\Tests\Fixtures\SeitenAttrappe;

class SeitennavigationInsertTagTest extends TestCase
{
    use ErsetzungsSchleifen;

    public function testFremdeInserttagsWerdenNichtBeansprucht(): void
    {
        $listener = new SeitenAttrappe();

        $this->assertFalse($listener->doReplace('env::request'));
        $this->assertFalse($listener->doReplace('artikelnavigation'));
        $this->assertFalse($listener->doReplace('seitennavigationen'));
        $this->assertSame(0, $listener->aufrufe);
    }

    public function testWiederholteAufrufeGebenNurEinmalAus(): void
    {
        $listener = new SeitenAttrappe();

        $this->assertSame('<nav>aktuell</nav>', $listener->doReplace('seitennavigation'));
        $this->assertSame('', $listener->doReplace('seitennavigation'));
        $this->assertSame('<nav>43</nav>', $listener->doReplace('seitennavigation::43'));
        $this->assertSame(2, $listener->aufrufe);
    }

    public function testWeitereVorkommenWerdenAufDenEigenenTagnamenUmgeschrieben(): void
    {
        $tags = ['', 'seitennavigation', '', 'seitennavigation', ''];

        SeitennavigationInsertTag::legeWeitereVorkommenStill($tags, 0, \count($tags), 'seitennavigation');

        $this->assertSame('seitennavigation::unterdrueckt', $tags[3]);
    }

    /**
     * Beide Inserttags hängen am selben Hook. Jedes muss nur sein eigenes Tag
     * beanspruchen, und die Zählung darf sich nicht vermischen.
     */
    public function testBeideInserttagsNebeneinanderInDerSchleifeAusContao413(): void
    {
        $seiten = new SeitenAttrappe();
        $artikel = new NavigationsAttrappe();
        $puffer = '<p>{{seitennavigation}}</p><div>{{artikelnavigation}} A1</div><div>{{artikelnavigation}} A2</div><p>{{seitennavigation}}</p>';

        $ergebnis = $this->ersetzeWieContao413([$seiten, $artikel], $puffer);

        $this->assertSame(
            '<p><nav>aktuell</nav></p><div><nav>aktuell</nav> A1</div><div> A2</div><p></p>',
            $ergebnis,
        );
        $this->assertSame(1, $seiten->aufrufe);
        $this->assertSame(1, $artikel->aufrufe);
    }

    public function testBeideInserttagsNebeneinanderInDerSchleifeAusContao5(): void
    {
        $seiten = new SeitenAttrappe();
        $artikel = new NavigationsAttrappe();
        $puffer = '{{seitennavigation}}|{{artikelnavigation}}|{{seitennavigation}}|{{artikelnavigation}}';

        $ergebnis = $this->ersetzeWieContao5([$seiten, $artikel], $puffer);

        $this->assertSame('<nav>aktuell</nav>|<nav>aktuell</nav>||', $ergebnis);
        $this->assertSame(1, $seiten->aufrufe);
        $this->assertSame(1, $artikel->aufrufe);
    }

    public function testDieAufgerufeneSeiteIstDerAktuelleEintrag(): void
    {
        $eintraege = SeitennavigationInsertTag::baueEintraege(
            [
                ['id' => 11, 'title' => 'Ausschreibung', 'url' => '/turniere/ausschreibung.html'],
                ['id' => 12, 'title' => 'Ergebnisse', 'url' => '/turniere/ergebnisse.html'],
            ],
            12,
            [1, 5, 12],
        );

        $this->assertSame([false, true], array_column($eintraege, 'aktiv'));
        $this->assertSame([false, true], array_column($eintraege, 'imPfad'));
    }

    public function testEineSeiteImPfadBleibtEinVerweis(): void
    {
        $eintraege = SeitennavigationInsertTag::baueEintraege(
            [['id' => 11, 'title' => 'Ausschreibung', 'url' => '/turniere/ausschreibung.html']],
            99,
            [1, 5, 11, 99],
        );

        $this->assertFalse($eintraege[0]['aktiv']);
        $this->assertTrue($eintraege[0]['imPfad']);
    }

    public function testOhneAktuelleSeiteIstKeinEintragAktiv(): void
    {
        $eintraege = SeitennavigationInsertTag::baueEintraege(
            [['id' => 11, 'title' => 'Ausschreibung', 'url' => '/turniere/ausschreibung.html']],
            0,
            [],
        );

        $this->assertFalse($eintraege[0]['aktiv']);
        $this->assertFalse($eintraege[0]['imPfad']);
    }

    /**
     * Contao liefert den Pfad je nach Fassung mit Zeichenketten oder Zahlen.
     */
    public function testDerPfadDarfZeichenkettenEnthalten(): void
    {
        $eintraege = SeitennavigationInsertTag::baueEintraege(
            [['id' => 11, 'title' => 'Ausschreibung', 'url' => '/turniere/ausschreibung.html']],
            0,
            ['1', '11'],
        );

        $this->assertTrue($eintraege[0]['imPfad']);
    }

    public function testOhneUnterseitenGibtEsKeineEintraege(): void
    {
        $this->assertSame([], SeitennavigationInsertTag::baueEintraege([], 12, [12]));
    }
}
