# Struktur der Wikipedia-Episodenlisten

Stand der Analyse: 6. November 2026. Geprüft wurden die Revisionen, die als Fixtures unter
`tests/Fixtures/wikipedia/` liegen (Revisions-IDs siehe dortige README).

## Abruf

- MediaWiki-API der deutschsprachigen Wikipedia, Wikitext über
  `action=query&prop=revisions&rvprop=content|ids|timestamp&rvslots=main&formatversion=2&redirects=1`
- Beide Listen sind jeweils **eine einzige Seite**, ohne Unterseiten oder Weiterleitungen.
- Eigener User-Agent mit Kontaktangabe (konfiguriert über `.env`, siehe Schritt 4).

| Reihe | Seite | Gelesene Abschnitte | Ignorierte Abschnitte |
|---|---|---|---|
| Tatort | `Liste der Tatort-Folgen` | „Ausgestrahlte Folgen“, „Bisher noch nicht im Fernsehen ausgestrahlte Folgen“ | „ORF-eigene Produktionen“ |
| Polizeiruf 110 | `Liste der Polizeiruf-110-Folgen` | „Gesendete Folgen“, „Noch nicht ausgestrahlte Folgen“ | „Folgen außerhalb der Reihe“, „Verbotene Folgen“ |

## Tabellenaufbau

Beide Listen nutzen eine Wikitabelle je Abschnitt mit neun Spalten, eine Zeile je Folge
(keine `rowspan`/`colspan`). Der Parser findet die Spalten **über die Überschrift**, nicht über die Position:

| Bedeutung | Tatort | Polizeiruf 110 | Muster im Parser |
|---|---|---|---|
| Folgennummer | Folge | Nr. | `^(Folge\|Nr\.?)$` |
| Titel | Titel | Titel | `^Titel$` |
| Erstausstrahlung | Erstausstrahlung / Geplante Erstausstrahlung | Erstausstr. / Geplante Erstausstr. | `Erstausstr` |
| Ermittler | Ermittler | Ermittler (als Link) | `^Ermittler$` |

| Sender (optional) | Sender | Sender | `^Sender$` |

Weitere Spalten (Fall, Autor, Regie, Besonderheiten) werden nicht verwendet.

### Wikitext-Besonderheiten

- Zeilen meist mehrzeilig (`| Wert` je Zeile), vereinzelt einzeilig (`| a || b || c`).
- Zellattribute vor dem Inhalt (`style="…" | Inhalt`); manche Zeilen beginnen mit `||[[…]]`
  (leerer Attributbereich) – wird wie in MediaWiki als Attribut verworfen.
- Kopfzeile beim Polizeiruf nach einem `|- class="…"`; Überschriften teils verlinkt.
- **HTML-Kommentare** enthalten u. a. eine auskommentierte Vorlagenzeile für neue Folgen
  (`{{DatumZelle|2026-MM-TT}}`) – Kommentare werden vor dem Parsen entfernt.
- Mehrzeilige Zellen (Besonderheiten mit `<ref>…</ref>` über mehrere Zeilen).

## Felder

### Folgennummer
- Tatort: lückenlos 1–1344 ausgestrahlt, 1345–1350 angekündigt (Stand der Fixtures).
- Polizeiruf: 1–427 gesendet, 428 angekündigt.
- `–` (teils als `<nowiki>–</nowiki>`) bedeutet: keine Nummer.
  - **Tatort, angekündigt (3 Folgen):** werden übersprungen, bis Wikipedia eine Nummer vergibt.
  - **Polizeiruf, gesendet (7 Filme):** als Einzelfilm gesendete DDR-Produktionen und der 2011
    rekonstruierte Film „Im Alter von …“. Sie werden **ohne Nummer** übernommen, mit der Nummer der
    vorangehenden Folge als Position (`nach 59, 62, 82, 93, 94, 107, 319`). Abgleich beim Import
    über den Artikelnamen (`wikipedia_ref`), siehe Schritt 4.
- ORF-eigene Tatort-Produktionen haben Nummern wie `168a` und werden nicht gelesen.
- Nicht-numerische Nummern in gelesenen Abschnitten und doppelte Nummern werden als Fehler gemeldet.

### Titel
- Link auf den Artikel der Folge: `[[Tatort: Taxi nach Leipzig (1970)|Taxi nach Leipzig]]`.
  Titel = Linktext, `wikipedia_ref` = Linkziel.
- Teilweise mit Zusatz nach `<br />` (`<small>(Folge 1000 trägt den gleichen Titel)</small>`) –
  nur der Teil vor dem Zeilenumbruch ist der Titel.
- HTML-Entities (`&nbsp;`, `&amp;`) werden dekodiert, geschützte Leerzeichen zu normalen.
- Doppelfolgen sind zwei getrennte Zeilen mit eigener Nummer; der Polizeiruf kennzeichnet Teile
  im Titel („Wendemanöver (1)“, „Schwere Jahre (1)“), der Tatort nur in „Besonderheiten“.
- Anführungszeichen im Titel (Polizeiruf „"In Erinnerung an …"“) werden unverändert übernommen.

### Erstausstrahlung
- Fast immer `{{DatumZelle|JJJJ-MM-TT}}`, oft gefolgt von `{{Anker|A1970}}` oder `<ref>`.
- Vereinzelt Klartext (`13. Sep. 2026`, `{{0}}8. Sep. 1985`, `19. Mai 1985`) – wird ebenfalls gelesen.
- Leere Zelle → kein Datum (Folge gilt als angekündigt).
- Nicht lesbare Angaben werden als Fehler gemeldet, die Folge wird ohne Datum übernommen.

### Ermittler (Team-Rohwert)
- Angezeigter Linktext, nicht das Linkziel: `[[Lena Odenthal|Odenthal und Stern]]` → „Odenthal und Stern“.
  Linkziele sind uneinheitlich (mal Figur, mal Stadt-Artikel wie „Polizeiruf 110 (Świecko)“).
- Angaben in Klammern werden entfernt (Gastauftritte, Nebenfiguren):
  „Thiel und Boerne (Gastauftritt Ballauf und Schenk)“ → „Thiel und Boerne“.
- Crossover mit mehreren Teams (getrennt durch `<br />`): das **erstgenannte** Team zählt.
- Ergebnis: ca. 150 verschiedene Rohwerte beim Tatort, ca. 110 beim Polizeiruf. Abweichende
  Schreibweisen desselben Teams („Borowski und Sahin“/„Borowski und Şahin“, „Hinrichs und Dieckmann“/
  „Hinrichs und Diekmann“) und wechselnde Besetzungen (DDR-Polizeiruf: „Peter Fuchs, Vera Arndt“,
  „Peter Fuchs, Vera Arndt, Lutz Subras“ …) werden **in der App zusammengeführt**; über die Aliase
  ordnet der nächste Import sie automatisch richtig zu.

### Sender
- Klartext der Zelle, Links aufgelöst (`[[Deutscher Fernsehfunk|DFF]]` → „DFF“).
- Koproduktionen bleiben als Text erhalten („SWR/SRF“, „Radio Bremen/WDR“, „NDR und MDR“).
- Historische Sender kommen vor (SFB, SDR, SWF, ORB, „Fernsehen der DDR“).

## Abgleich beim Import

- Primär über `(Reihe, Folgennummer)`.
- Rückfall über den Artikelnamen (`wikipedia_ref`) **nur** für Folgen ohne Nummer oder mit manuell
  geänderter Nummer – mehrere Folgen können auf denselben Artikel verlinken
  (z. B. „Wendemanöver (1)“ und „(2)“).
- Der Alias-Abgleich ist exakt (Groß-/Kleinschreibung und Akzente zählen): „Sahin“ und „Şahin“ sind
  verschiedene Rohwerte und werden bewusst in der App zusammengeführt. Unter MariaDB/MySQL ist die Spalte
  dafür auf `utf8mb4_bin` gestellt, damit sie sich wie SQLite verhält.

## Robustheit

Der Parser meldet Fehler statt still falsche Daten zu liefern, wenn
- ein erwarteter Abschnitt fehlt (Seitenstruktur geändert),
- eine benötigte Spalte nicht gefunden wird,
- eine Zeile eine abweichende Zellenzahl hat,
- eine Nummer doppelt oder nicht numerisch ist.
