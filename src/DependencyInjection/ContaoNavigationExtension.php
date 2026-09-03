<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Inserttag {{artikelnavigation}} bereit.
 *
 * @author    Frank Binding
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoNavigationBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * DependencyInjection-Extension des Bundles.
 *
 * Die Basisklasse aus dem HttpKernel gibt es in Symfony 5.4 (Contao 4.13)
 * ebenso wie in Symfony 7 (Contao 5.7). Sie wird der Variante aus der
 * DependencyInjection-Komponente vorgezogen, weil dort schon Erweiterungen
 * mitgeliefert werden, die nicht in jeder Fassung gleich aussehen.
 * ExtensionInterface::load() hat in beiden Fassungen keinen Rückgabetyp,
 * das eigene ": void" ist also erlaubt.
 */
class ContaoNavigationExtension extends Extension
{
    /**
     * Lädt die Service-Konfiguration des Bundles in den Container.
     *
     * @param array<mixed>     $configs   Zusammengeführte Bundle-Konfiguration (hier ungenutzt)
     * @param ContainerBuilder $container Der Container, in den geladen wird
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config'),
        );

        $loader->load('services.yaml');
    }
}
