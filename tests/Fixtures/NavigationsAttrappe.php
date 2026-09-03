<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Inserttag {{artikelnavigation}} bereit.
 *
 * @author    Frank Binding
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoNavigationBundle\Tests\Fixtures;

use Schachbulle\ContaoNavigationBundle\InsertTag\ArtikelnavigationInsertTag;

/**
 * Attrappe für die Tests.
 *
 * Ersetzt den Datenbankzugriff durch eine feste Zeichenkette und zählt mit,
 * wie oft die Navigation tatsächlich aufgebaut wurde. Damit lässt sich die
 * Unterdrückung von Wiederholungen prüfen, ohne einen Contao-Kern zu starten.
 */
final class NavigationsAttrappe extends ArtikelnavigationInsertTag
{
    /**
     * Zählt die Aufrufe von baue(), also die tatsächlich erzeugten Navigationen.
     */
    public int $aufrufe = 0;

    /**
     * Liefert statt der echten Navigation eine erkennbare Ersatzausgabe.
     *
     * @param string|null $parameter Die gewünschte Seiten-ID, oder null für die aktuelle Seite
     *
     * @return string Die Ersatzausgabe, in der der Parameter sichtbar bleibt
     */
    protected function baue(?string $parameter): string
    {
        ++$this->aufrufe;

        return '<nav>'.($parameter ?? 'aktuell').'</nav>';
    }
}
