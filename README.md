# Seiten- und Artikel-Navigation für Contao

## Entwickler ##

**Frank Binding**

## Voraussetzungen ##

* Contao 4.13 oder Contao 5
* PHP 8.0 oder neuer (geprüft bis PHP 8.4)

Ab Contao 5.2 melden sich die Inserttags über das Attribut `AsInsertTag` an,
in älteren Fassungen über den Hook `replaceInsertTags`. Beides geschieht von
selbst, es ist nichts einzustellen.

## Anwendung ##

### Seitennavigation ###

Dieser Inserttag listet die Unterseiten der aktuellen Seite auf:

```php
{{seitennavigation}}
```

Optional kann als Parameter die ID der Elternseite (hier 43) angegeben werden;
dann erscheinen deren Unterseiten, egal wo der Inserttag steht:

```php
{{seitennavigation::43}}
```

Damit lässt sich einer Rubrikseite ein Untermenü mitgeben, ohne dafür ein
Navigationsmodul in das Seitenlayout zu hängen.

Aufgelistet werden die direkten Unterseiten in ihrer Reihenfolge im Seitenbaum.
Es gelten dieselben Regeln wie im Navigationsmodul von Contao:

* im Menü versteckte Seiten bleiben draußen,
* unveröffentlichte Seiten sowie Seiten außerhalb ihres Anzeigezeitraums
  ebenfalls — im Vorschaumodus dagegen erscheinen sie,
* geschützte Seiten nur für Mitglieder der berechtigten Gruppen,
* nur für Gäste sichtbare Seiten verschwinden, sobald jemand angemeldet ist,
* Startpunkte einer Website und nicht aufrufbare Seitentypen bleiben außen vor.

Die gerade aufgerufene Seite steht unverlinkt da. Eine Seite, die lediglich im
Pfad zur aktuellen Seite liegt, bleibt ein Verweis und bekommt die CSS-Klasse
`trail`.

### Artikelnavigation ###

Dieser Inserttag fügt eine Artikelnavigation für die aktuelle Seite ein:

```php
{{artikelnavigation}}
```

Optional kann auch als Parameter die ID der Seite (hier 43) angegeben werden:

```php
{{artikelnavigation::43}}
```

Aufgelistet werden die veröffentlichten Artikel der Seite aus der Spalte
`main`, in ihrer Sortierreihenfolge. Der erste Eintrag trägt den Titel der
Seite, denn wer die Seite ohne Artikelparameter aufruft, landet beim ersten
Artikel. Der gerade aufgerufene Eintrag bleibt unverlinkt, die übrigen sind
Verweise.

Beispiel für eine Seite „Turniere“ mit den Artikeln „Übersicht“,
„Ausschreibung“ und „Ergebnisse“, aufgerufen ohne Artikelparameter:

```html
Turniere | <a href="…/articles/ausschreibung.html">Ausschreibung</a> | <a href="…/articles/ergebnisse.html">Ergebnisse</a>
```

### Mehrfache Ausgabe ###

Jede Navigation erscheint je Seitenaufbau **nur einmal**. Das Inserttag darf
also in jedem Artikel einer Seite stehen: Zeigt Contao alle Artikel auf einmal
an (weil sie keinen Anrisstext benutzen), erscheint die Navigation trotzdem nur
beim ersten Artikel. `{{artikelnavigation}}` und `{{artikelnavigation::43}}`
gelten dabei als zwei verschiedene Navigationen und werden getrennt gezählt;
für die Seitennavigation gilt dasselbe.

Der Parameter `unterdrueckt` ist für diesen Zweck reserviert: Unter Contao 4.13
schreibt das Bundle die weiteren Vorkommen im Seitenpuffer darauf um, weil
Contao dort das Ergebnis eines Inserttags zwischenspeichert und den Hook kein
zweites Mal aufruft.

## Gestaltung ##

Beide Navigationen werden über ein Contao-Template ausgegeben:

| Inserttag | Template |
| --- | --- |
| `{{seitennavigation}}` | `navigation_seiten.html5` |
| `{{artikelnavigation}}` | `navigation_artikel.html5` |

Zum Anpassen eine Kopie der Datei aus
`vendor/schachbulle/contao-navigation-bundle/src/Resources/contao/templates/`
in das eigene `templates/`-Verzeichnis legen und dort ändern.

Im Template stehen bereit:

| Variable | Inhalt |
| --- | --- |
| `$this->eintraege` | Liste der Einträge mit `titel`, `url`, `aktiv` und `imPfad` |
| `$this->trenner` | Trennzeichen zwischen zwei Einträgen, voreingestellt ` \| ` |
| `$this->seite` | `PageModel` der Seite, auf die sich die Navigation bezieht |

Gibt es nichts aufzulisten, wird das Template gar nicht erst aufgerufen — ein
Rahmen im Template landet also nie leer auf der Seite.
