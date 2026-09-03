<?php

declare(strict_types=1);

/*
 * Bootstrap fuer die Unit-Tests.
 *
 * Existiert ein Composer-Autoloader (vendor/autoload.php), wird dieser genutzt.
 * Andernfalls wird ein minimaler PSR-4-Autoloader registriert, damit die Tests
 * auch ohne installierte Abhaengigkeiten mit einem eigenstaendigen PHPUnit
 * laufen (z. B. tools/phpunit9/vendor/bin/phpunit).
 */

$autoload = __DIR__.'/../vendor/autoload.php';

if (is_file($autoload)) {
    require $autoload;

    return;
}

spl_autoload_register(
    static function (string $class): void {
        $map = [
            'Schachbulle\\ContaoNavigationBundle\\Tests\\' => __DIR__.'/',
            'Schachbulle\\ContaoNavigationBundle\\' => __DIR__.'/../src/',
        ];

        foreach ($map as $prefix => $directory) {
            if (0 !== strncmp($class, $prefix, \strlen($prefix))) {
                continue;
            }

            $file = $directory.str_replace('\\', '/', substr($class, \strlen($prefix))).'.php';

            if (is_file($file)) {
                require $file;
            }

            return;
        }
    }
);
