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

use Schachbulle\ContaoNavigationBundle\InsertTag\AbstractNavigationInsertTag;

/**
 * Bildet die Ersetzungsschleifen beider Contao-Fassungen nach.
 *
 * Damit lässt sich das Zusammenspiel von Hook und Puffer prüfen, ohne einen
 * Contao-Kern zu starten. Die Schleifen sind Contao\InsertTags::replace()
 * nachgebildet — 4.13 mit Zwischenspeicher, Contao 5 ohne.
 */
trait ErsetzungsSchleifen
{
    /**
     * Bildet die Ersetzungsschleife aus Contao 4.13 nach.
     *
     * Entscheidend ist der Zwischenspeicher: Ein bereits ersetztes Tag wird für
     * jedes weitere gleichlautende Vorkommen wiederverwendet, der Hook also gar
     * nicht mehr aufgerufen. Genau daran scheiterte der frühere Zähler.
     *
     * @param array<int, AbstractNavigationInsertTag> $listener Die angemeldeten Hooks
     *                                                          in ihrer Reihenfolge
     * @param string                                  $puffer   Der Seitenpuffer mit den Inserttags
     *
     * @return string Der ersetzte Puffer
     */
    private function ersetzeWieContao413(array $listener, string $puffer): string
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

            foreach ($listener as $hook) {
                $wert = $hook->doReplace($tag, false, $zwischenspeicher[$strTag], $flags, $tags, $zwischenspeicher, $rit, $cnt);

                if (false !== $wert) {
                    $zwischenspeicher[$strTag] = $wert;

                    break;
                }
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
     * @param array<int, AbstractNavigationInsertTag> $listener Die angemeldeten Hooks
     *                                                          in ihrer Reihenfolge
     * @param string                                  $puffer   Der Seitenpuffer mit den Inserttags
     *
     * @return string Der ersetzte Puffer
     */
    private function ersetzeWieContao5(array $listener, string $puffer): string
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
            $wert = false;

            foreach ($listener as $hook) {
                $wert = $hook->doReplace($tag, false, '', $flags, $tags, [], $rit, $cnt);

                if (false !== $wert) {
                    break;
                }
            }

            $ausgabe[$rit + 1] = false === $wert ? '{{'.$tags[$rit + 1].'}}' : $wert;
        }

        return implode('', $ausgabe);
    }
}
