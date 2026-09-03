<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Inserttag {{artikelnavigation}} bereit.
 *
 * @author    Frank Binding
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoNavigationBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Schachbulle\ContaoNavigationBundle\ContaoNavigationBundle;

class Plugin implements BundlePluginInterface
{
    /**
     * Meldet das Bundle beim Contao Manager an.
     *
     * Das Bundle wird nach dem Contao-Kern geladen, damit dessen Klassen und
     * Dienste beim Registrieren des Inserttags bereits zur Verfügung stehen.
     *
     * @param ParserInterface $parser Der Parser des Contao Managers (hier ungenutzt)
     *
     * @return array<int, BundleConfig> Die Bundle-Konfiguration
     */
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(ContaoNavigationBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }
}
