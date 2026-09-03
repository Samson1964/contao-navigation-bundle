<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Inserttag {{artikelnavigation}} bereit.
 *
 * @author    Frank Binding
 * @license   LGPL-3.0-or-later
 */

use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Schachbulle\ContaoNavigationBundle\InsertTag\ArtikelnavigationInsertTag;

/*
 * Ab Contao 5.2 wird das Inserttag über das Attribut AsInsertTag registriert
 * (siehe services.yaml). Nur ältere Fassungen (Contao 4.13 bis 5.1) benötigen
 * den dort inzwischen als veraltet markierten Hook "replaceInsertTags".
 */
if (!class_exists(AsInsertTag::class)) {
    $GLOBALS['TL_HOOKS']['replaceInsertTags'][] = [ArtikelnavigationInsertTag::class, 'doReplace'];
}
