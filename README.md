# Seiten- und Artikel-Navigation für Contao

## Entwickler ##

**Frank Binding**

## Voraussetzungen ##

* Contao 4.13 oder Contao 5
* PHP 8.0 oder neuer (geprüft bis PHP 8.4)

Ab Contao 5.2 meldet sich das Inserttag über das Attribut `AsInsertTag` an,
in älteren Fassungen über den Hook `replaceInsertTags`. Beides geschieht von
selbst, es ist nichts einzustellen.

## Anwendung ##

### Seitennavigation ###

Noch nicht implementiert!

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
Verweise. Als Trennzeichen dient ` | `.

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
gelten dabei als zwei verschiedene Navigationen und werden getrennt gezählt.

Der Parameter `unterdrueckt` ist für diesen Zweck reserviert: Unter Contao 4.13
schreibt das Bundle die weiteren Vorkommen im Seitenpuffer darauf um, weil
Contao dort das Ergebnis eines Inserttags zwischenspeichert und den Hook kein
zweites Mal aufruft.
