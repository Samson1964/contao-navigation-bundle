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

/**
 * Ersatz für Contao\FrontendTemplate in den Template-Tests.
 *
 * Contao reicht die Daten eines Templates über die magischen Methoden __set()
 * und __get() durch; diese Attrappe macht es genauso. Eine eigene Klasse ist
 * nötig, weil sich eine Closure nicht an die interne Klasse stdClass binden
 * lässt („Cannot bind closure to scope of internal class").
 */
final class TemplateAttrappe
{
    /**
     * Die Daten des Templates.
     *
     * @var array<string, mixed>
     */
    private array $daten;

    /**
     * @param array<string, mixed> $daten Die Werte, die dem Template zur Verfügung stehen
     */
    public function __construct(array $daten = [])
    {
        $this->daten = $daten;
    }

    /**
     * Liefert einen Wert des Templates.
     *
     * @param string $name Name des Wertes
     *
     * @return mixed Der Wert, oder null wenn er nicht gesetzt wurde
     */
    public function __get(string $name)
    {
        return $this->daten[$name] ?? null;
    }

    /**
     * Setzt einen Wert des Templates.
     *
     * @param string $name Name des Wertes
     * @param mixed  $wert Der Wert
     */
    public function __set(string $name, $wert): void
    {
        $this->daten[$name] = $wert;
    }

    /**
     * Meldet, ob ein Wert gesetzt ist.
     *
     * @param string $name Name des Wertes
     */
    public function __isset(string $name): bool
    {
        return isset($this->daten[$name]);
    }
}
