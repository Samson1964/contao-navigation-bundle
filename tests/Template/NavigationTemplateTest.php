<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt die Inserttags {{artikelnavigation}} und
 * {{seitennavigation}} bereit.
 *
 * @author    Frank Binding
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoNavigationBundle\Tests\Template;

use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoNavigationBundle\InsertTag\AbstractNavigationInsertTag;
use Schachbulle\ContaoNavigationBundle\Tests\Fixtures\TemplateAttrappe;

/**
 * Prüft die mitgelieferten Templates.
 *
 * Die Templates greifen nur über $this auf ihre Daten zu und geben mit echo
 * aus. Deshalb lassen sie sich ohne Contao ausführen, indem eine Closure an ein
 * Objekt mit den passenden Eigenschaften gebunden wird.
 */
class NavigationTemplateTest extends TestCase
{
    /**
     * Die Ausgabe muss zeichengleich zu der bleiben, die bis Fassung 0.2.0 fest
     * im Code stand — sonst ändert sich mit dem Umstieg auf Templates das
     * Aussehen bestehender Seiten.
     */
    public function testArtikelnavigationBleibtInDerAusgabeUnveraendert(): void
    {
        $ausgabe = $this->rendere('navigation_artikel', [
            ['titel' => 'Meine Seite', 'url' => '/meine-seite.html', 'aktiv' => false, 'imPfad' => true],
            ['titel' => 'Zweiter', 'url' => '/articles/zweiter.html', 'aktiv' => true, 'imPfad' => true],
            ['titel' => 'Dritter', 'url' => '/articles/dritter.html', 'aktiv' => false, 'imPfad' => false],
        ]);

        $this->assertSame(
            '<a href="/meine-seite.html">Meine Seite</a> | Zweiter | <a href="/articles/dritter.html">Dritter</a>',
            $ausgabe,
        );
    }

    public function testArtikelnavigationOhneAusgewaehltenArtikel(): void
    {
        $ausgabe = $this->rendere('navigation_artikel', [
            ['titel' => 'Meine Seite', 'url' => '/meine-seite.html', 'aktiv' => true, 'imPfad' => true],
            ['titel' => 'Zweiter', 'url' => '/articles/zweiter.html', 'aktiv' => false, 'imPfad' => false],
        ]);

        $this->assertSame('Meine Seite | <a href="/articles/zweiter.html">Zweiter</a>', $ausgabe);
    }

    public function testSeitennavigationKennzeichnetDenPfad(): void
    {
        $ausgabe = $this->rendere('navigation_seiten', [
            ['titel' => 'Ausschreibung', 'url' => '/turniere/ausschreibung.html', 'aktiv' => false, 'imPfad' => true],
            ['titel' => 'Ergebnisse', 'url' => '/turniere/ergebnisse.html', 'aktiv' => true, 'imPfad' => true],
            ['titel' => 'Archiv', 'url' => '/turniere/archiv.html', 'aktiv' => false, 'imPfad' => false],
        ]);

        $this->assertSame(
            '<a href="/turniere/ausschreibung.html" class="trail">Ausschreibung</a> | Ergebnisse | <a href="/turniere/archiv.html">Archiv</a>',
            $ausgabe,
        );
    }

    public function testBeideTemplatesLiegenImBundle(): void
    {
        $this->assertFileExists($this->pfad('navigation_artikel'));
        $this->assertFileExists($this->pfad('navigation_seiten'));
    }

    /**
     * Führt ein Template mit den übergebenen Einträgen aus.
     *
     * @param string                                                                   $name      Templatename ohne Endung
     * @param array<int, array{titel: string, url: string, aktiv: bool, imPfad: bool}> $eintraege Die Einträge
     *
     * @return string Die Ausgabe des Templates
     */
    private function rendere(string $name, array $eintraege): string
    {
        $daten = new TemplateAttrappe([
            'eintraege' => $eintraege,
            'trenner' => AbstractNavigationInsertTag::TRENNER,
            'seite' => null,
        ]);

        $ausfuehren = function (string $pfad): void {
            include $pfad;
        };

        ob_start();

        try {
            $ausfuehren->call($daten, $this->pfad($name));
        } finally {
            $ausgabe = ob_get_clean();
        }

        return (string) $ausgabe;
    }

    /**
     * Liefert den Pfad zu einem Template des Bundles.
     *
     * @param string $name Templatename ohne Endung
     *
     * @return string Der absolute Pfad zur .html5-Datei
     */
    private function pfad(string $name): string
    {
        return __DIR__.'/../../src/Resources/contao/templates/'.$name.'.html5';
    }
}
