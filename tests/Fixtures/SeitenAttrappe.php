<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt die Inserttags {{artikelnavigation}} und
 * {{seitennavigation}} bereit.
 *
 * @author    Frank Binding
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoNavigationBundle\Tests\Fixtures;

use Schachbulle\ContaoNavigationBundle\InsertTag\SeitennavigationInsertTag;

/**
 * Attrappe der Seitennavigation für die Tests.
 *
 * Arbeitet wie NavigationsAttrappe, nur für das Inserttag {{seitennavigation}}.
 */
final class SeitenAttrappe extends SeitennavigationInsertTag
{
    /**
     * Zählt die Aufrufe von baue(), also die tatsächlich erzeugten Navigationen.
     */
    public int $aufrufe = 0;

    /**
     * Liefert statt der echten Navigation eine erkennbare Ersatzausgabe.
     *
     * @param string|null $parameter Die ID der Elternseite, oder null für die aktuelle Seite
     *
     * @return string Die Ersatzausgabe, in der der Parameter sichtbar bleibt
     */
    protected function baue(?string $parameter): string
    {
        ++$this->aufrufe;

        return '<nav>'.($parameter ?? 'aktuell').'</nav>';
    }
}
