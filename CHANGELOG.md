# Seiten- und Artikel-Navigation Changelog

## Version 0.3.0 (2026-09-03)

* Add: Das Inserttag `{{seitennavigation}}` listet die Unterseiten der aktuellen bzw. der angegebenen Seite auf. Gefiltert wird wie im Navigationsmodul des Kerns: im Menü versteckte, unveröffentlichte, geschützte und nur für Gäste sichtbare Seiten bleiben draußen
* Add: Beide Navigationen werden über ein Contao-Template ausgegeben (`navigation_seiten.html5` und `navigation_artikel.html5`), das sich im eigenen `templates/`-Verzeichnis überschreiben lässt
* Change: Die gemeinsame Anbindung an beide Inserttag-Schnittstellen liegt jetzt in `AbstractNavigationInsertTag`; beide Inserttags teilen sich das Unterdrücken wiederholter Vorkommen
* Change: `ArtikelnavigationInsertTag::baueNavigation()` ist durch `baueEintraege()` ersetzt; die Methode liefert die Einträge als Feld statt fertiges HTML. Die Ausgabe der Artikelnavigation bleibt dabei zeichengleich zu 0.2.0

## Version 0.2.0 (2026-09-03)

* Add: Der Seitenparameter (`{{artikelnavigation::43}}`) ist umgesetzt; bisher lieferte er nur die Zeichenkette „OK“
* Add: Unit-Tests in `tests/` samt `phpunit.xml.dist`, unter anderem mit den Ersetzungsschleifen aus Contao 4.13 und Contao 5 als Prüfstand
* Change: Läuft unter Contao 4.13 und Contao 5 sowie unter PHP 8.4. Ab Contao 5.2 meldet sich das Inserttag über das Attribut `AsInsertTag` an, davor über den Hook `replaceInsertTags`
* Change: Die Klasse `Classes\Navigation` heißt jetzt `InsertTag\ArtikelnavigationInsertTag`; `extends \Frontend` ist entfallen, weil Contao 5 keine Klassenaliasse mehr registriert
* Change: Die Artikel werden über `ArticleModel::findPublishedByPidAndColumn()` gelesen statt über eine eigene SQL-Abfrage; damit gelten Start- und Ablaufdatum sowie die Vorschau
* Change: Die Adressen entstehen ab Contao 5.3 über `contao.routing.content_url_generator`, darunter über `PageModel::getFrontendUrl()`; `Controller::generateFrontendUrl()` gibt es in Contao 5 nicht mehr
* Fix: Die Navigation erscheint je Seitenaufbau nur noch einmal, auch wenn das Inserttag in mehreren Artikeln einer Seite steht (also wenn die Artikel keinen Anrisstext benutzen)

## Version 0.1.2 (2026-07-30)

* Change: Beschreibung, Keywords und Homepage in der composer.json ergänzt, damit Packagist das Paket verständlich darstellt und über die Suche auffindbar macht

## Version 0.1.1 (2025-10-01)

* Fix: Debug-Ausgabe entfernt

## Version 0.1.0 (2025-10-01)

* Erste Alphaversion mit Artikelnavigation und ohne Seitennavigation

## Version 0.0.1 (2025-09-30)

* Initialversion als Contao-4-Bundle
