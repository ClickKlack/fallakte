# SPEC – Fallakte (Tatort & Polizeiruf 110)

## 0. Projektname

| Verwendung | Name |
|---|---|
| Anzeige im Frontend (Seitentitel, Header, Login, `APP_NAME`) | **Amis Fallakte** |
| Git-Repository, Projektordner, Slugs, Composer-/npm-Paketname | **fallakte** |

Im Code wird der Anzeigename nur über `config('app.name')` bzw. die zentrale Übersetzungsdatei verwendet, nie hart kodiert.

## 1. Ziel

Persönliche Webapp zur Verwaltung meiner gesehenen Folgen von **Tatort** und **Polizeiruf 110**.
Der Episodenkatalog wird aus Wikipedia importiert und kann manuell korrigiert werden. Ich hake ab, was ich gesehen habe, und sehe Fortschritt pro Ermittlerteam und gesamt.

Single-User-Anwendung, Oberflächensprache Deutsch.

## 2. Tech-Stack (verbindlich)

- PHP 8.3+, **Laravel 13** (ursprünglich Laravel 11 – ist EOL und wegen offener Sicherheitslücken nicht mehr installierbar)
- **Vue 3 + Inertia.js**, Tailwind CSS
- **SQLite** (Single-User, wenige tausend Datensätze; Migrationen bleiben MariaDB-kompatibel, damit ein späterer Wechsel möglich ist)
- Auth: **Laravel Breeze (Vue/Inertia)**, Registrierung deaktiviert
- Tests: Pest oder PHPUnit

## 2a. Code-Konventionen (verbindlich)

- **Englisch:** alle Bezeichner – Tabellen, Spalten, Models, Klassen, Methoden, Funktionen, Variablen, Routen-Namen, Enum-Werte, Testnamen, Commit-Messages
- **Deutsch:** alle Code-Kommentare und PHPDoc-Beschreibungen sowie alle Texte der Benutzeroberfläche
- UI-Texte nicht hart im Vue-Code verteilen, sondern zentral (z. B. `lang/de/*.php` bzw. eine zentrale Übersetzungsdatei fürs Frontend)
- PSR-12, Laravel-Konventionen (Pint), Vue Composition API mit `<script setup>`

## 3. Datenmodell

### series
| Feld | Typ | Hinweis |
|---|---|---|
| id | PK | |
| name | string | „Tatort", „Polizeiruf 110" |
| slug | string, unique | `tatort`, `polizeiruf-110` |

Per Seeder angelegt.

### teams
| Feld | Typ | Hinweis |
|---|---|---|
| id | PK | |
| series_id | FK | |
| name | string | Anzeigename, z. B. „Münster – Thiel/Boerne" |
| city | string, nullable | |
| investigators | string, nullable | Namen der Ermittler |
| timestamps | | |

### team_aliases
| Feld | Typ | Hinweis |
|---|---|---|
| id | PK | |
| series_id | FK | redundant zum Team, sichert den Unique-Index `(series_id, raw_value)` ab; wird beim Speichern aus dem Team übernommen |
| team_id | FK | |
| raw_value | string, unique je Serie | Rohwert aus Wikipedia (Ermittler-/Ort-Spalte) |

Zweck: Wikipedia schreibt Ermittlerteams nicht einheitlich. Der Import ordnet Rohwerte über diese Tabelle einem Team zu.

### episodes
| Feld | Typ | Hinweis |
|---|---|---|
| id | PK | |
| series_id | FK | |
| number | int, nullable | Folgennummer laut Wikipedia; leer bei Filmen ohne offizielle Nummer (siehe `docs/wikipedia-structure.md`) |
| sort_key | int | Reihenfolge: Nummer × 100; Filme ohne Nummer: vorherige Nummer × 100 + laufender Index → Ersatznummer „59a“ |
| title | string | |
| first_aired | date, nullable | Erstausstrahlung |
| broadcaster | string, nullable | produzierender Sender laut Wikipedia (z. B. „NDR“, „SWR/SRF“, „DFF“) |
| team_id | FK, nullable | |
| status | enum `aired`, `announced` | siehe 4.3 |
| seen | bool, default false | |
| seen_at | date, nullable | optional |
| wikipedia_ref | string, nullable | Artikelname der Folge (Linkziel des Titels); Abgleichsschlüssel für Filme ohne Nummer |
| manual_fields | JSON, default `[]` | Liste manuell geänderter Felder, siehe 4.2 |
| timestamps | | |

Unique-Index: `(series_id, number)`.

### import_runs
Protokoll je Importlauf: Serie, Start/Ende, Anzahl neu / aktualisiert / übersprungen (geschützt) / Fehler, Fehlermeldungen als Text.

## 4. Import aus Wikipedia

### 4.1 Ablauf
- Artisan-Command: `php artisan episodes:import {series?} {--dry-run}`
  - ohne Argument: beide Serien
  - `--dry-run`: nur Ausgabe der Änderungen, nichts schreiben
- Quellen: die deutschsprachigen Wikipedia-Episodenlisten zu Tatort und Polizeiruf 110.
- Abruf über die **MediaWiki-API** (Wikitext), kein HTML-Scraping. Eigener User-Agent mit Kontaktangabe setzen.
- **Vor der Implementierung die aktuelle Struktur der beiden Listen prüfen** (Seitennamen, ggf. Unterseiten, Tabellenspalten) und den Parser darauf auslegen. Parser je Serie als eigene Klasse, gemeinsame Schnittstelle.
- Upsert über `(series_id, number)`; Rückfall über `wikipedia_ref` nur für Folgen ohne Nummer oder mit manuell geänderter Nummer.
- In Wikipedia entfernte Folgen werden nicht gelöscht.
- Der Import fasst `seen` und `seen_at` **nie** an.

### 4.2 Schutz manueller Korrekturen
- Wird `title`, `first_aired`, `broadcaster`, `team_id` oder `number` in der App geändert, wird der Feldname in `manual_fields` eingetragen.
- Der Import überschreibt diese Felder nicht und zählt sie als „übersprungen (geschützt)".
- In der Bearbeitungsmaske lässt sich der Schutz je Feld zurücksetzen („wieder von Wikipedia übernehmen").

### 4.3 Angekündigte Folgen
- Folgen ohne Ausstrahlungsdatum oder mit Datum in der Zukunft werden mit `status = announced` importiert.
- Beim nächsten Import bzw. beim Anzeigen wird der Status anhand des Datums auf `aired` gesetzt.
- Angekündigte Folgen:
  - erscheinen in der Liste (eigener Filter, optisch abgesetzt)
  - können **nicht** als gesehen markiert werden
  - zählen **nicht** in Statistiken und Team-Fortschritt

### 4.4 Team-Zuordnung
- Rohwert über `team_aliases` auflösen.
- Unbekannter Rohwert: neues Team mit dem Rohwert als Namen anlegen, Alias dazu speichern.
- Teams können in der App umbenannt und **zusammengeführt** werden (Merge verschiebt Folgen und Aliase auf das Zielteam, löscht das Quellteam).

### 4.5 Tests zum Import
- Parser-Tests mit gespeicherten Wikitext-Fixtures je Serie (inkl. Sonderfälle: fehlendes Datum, Doppelfolgen, abweichende Ermittlerschreibweisen).
- Test für Schutzlogik und dafür, dass `seen` beim Import unverändert bleibt.
- Tests dürfen **keine echten HTTP-Aufrufe** an Wikipedia machen (`Http::fake()` bzw. Fixtures).

## 5. Funktionen der Oberfläche

### 5.1 Folgenliste (Startseite)
- Tabelle: Reihe, Nr., Titel, Erstausstrahlung, Sender, Team, Gesehen (Haken), Gesehen am
- Filter (kombinierbar, im URL-Query gehalten):
  - Reihe (Tatort / Polizeiruf / beide)
  - Team
  - Sender (einzelne Sender; Koproduktionen wie „SWR/SRF“ werden bei beiden beteiligten Sendern gefunden)
  - Status: gesehen / ungesehen / angekündigt / alle
  - Jahr der Erstausstrahlung (von–bis)
  - Titelsuche (Teilstring)
- Sortierung nach Nr., Datum, Titel, Team
- Paginierung
- **Abhaken direkt in der Liste** per Klick (ohne Seitenwechsel); Datum optional über kleinen Datepicker, Vorbelegung heute, leer lassen erlaubt
- Haken entfernen setzt `seen_at` auf null

### 5.2 Folge bearbeiten
- Formular für Titel, Nr., Erstausstrahlung, Sender, Team, Gesehen, Gesehen am
- Anzeige, welche Felder geschützt sind, mit Reset je Feld
- Manuelles Anlegen einer Folge möglich (z. B. wenn Wikipedia hinterherhinkt); alle Felder gelten dann als geschützt

### 5.3 Team-Übersicht
- Je Reihe gruppiert, je Team: Name, Stadt, Zeitraum (erste–letzte Ausstrahlung), **x von y gesehen** mit Fortschrittsbalken
- Klick führt zur Folgenliste mit gesetztem Teamfilter
- Teams umbenennen, Stadt/Ermittler pflegen, Teams zusammenführen

### 5.4 Statistik-Dashboard
- Gesamtfortschritt je Reihe und gesamt (Anzahl + Prozent)
- Fortschritt nach Ausstrahlungsjahr (Balkendiagramm)
- Gesehene Folgen pro Jahr nach `seen_at` (nur Einträge mit Datum)
- Top-Teams nach Fortschritt bzw. Teams mit den meisten ungesehenen Folgen
- Letzter Importlauf mit Ergebnis

## 6. Auth & Sicherheit
- Login per Breeze, **Registrierung deaktiviert**
- Benutzer anlegen per Artisan-Command: `php artisan user:create`
- Alle Routen außer Login hinter `auth`

## 7. Betrieb / Deployment
- Zielsystem: Webspace mit **SSH und Composer, aber ohne Node**; PHP 8.3+ mit `pdo_sqlite`
- SQLite-Datei liegt außerhalb des Web-Roots in einem für PHP beschreibbaren Verzeichnis (Pfad nur in `.env`); Deployment darf die Datei nie überschreiben
- Frontend-Build (`npm run build`) erfolgt lokal; `public/build` wird mit deployed
- README mit Deployment-Schritten: Upload, `composer install --no-dev`, `.env`, `php artisan migrate --force`, `php artisan db:seed --force` (legt die Reihen an, keine Benutzer), `php artisan user:create`, `php artisan episodes:import`
- Import wird manuell per SSH ausgeführt **oder über den Knopf „Jetzt von Wikipedia aktualisieren“ auf der Über-Seite** (synchron im Webaufruf, Erstimport ca. 9 s; höchstens alle 5 Minuten; gemeinsame Sperre mit dem Artisan-Befehl gegen parallele Läufe); optional über Laravel-Scheduler, falls Cron auf dem Webspace verfügbar ist (im README beschreiben, nicht voraussetzen)
- Mehrere Logins sind möglich (`user:create` mehrfach), teilen sich aber **einen gemeinsamen Datenbestand** – z. B. Ami als Hauptnutzerin plus ein zweiter Login zum Mitschauen. Für den Wikipedia-User-Agent in der produktiven `.env` `WIKIPEDIA_USER_AGENT` mit eigener Kontaktadresse setzen (sonst wird die E-Mail des zuerst angelegten Benutzers verwendet)

## 8. Nicht im Umfang
- Bewertungen, Notizen, Merkliste
- Mehrere Benutzer
- Mehrfach-Sichtungen (nur ein Haken + ein Datum je Folge)
- Andere Krimireihen

## 9. Abnahmekriterien
1. Nach `episodes:import` sind beide Reihen vollständig mit Nr., Titel, Datum und Team vorhanden.
2. Eine Folge lässt sich in der Liste mit einem Klick abhaken, mit und ohne Datum.
3. Eine manuell geänderte Folge behält ihre Korrektur nach erneutem Import; `seen` bleibt immer erhalten.
4. Angekündigte Folgen sind sichtbar, nicht abhakbar und fehlen in allen Statistiken.
5. Zwei Teams lassen sich zusammenführen; der nächste Import ordnet die Rohwerte beider dem Zielteam zu.
6. Filter sind kombinierbar und bleiben beim Neuladen erhalten.
7. Ohne Login ist keine Seite erreichbar; Registrierung ist nicht möglich.
8. Parser- und Import-Tests laufen grün.

## 10. Teststrategie (verbindlich)

- Zu jedem Schritt werden **Unit- und Feature-Tests** angelegt, die die neue Funktionalität abdecken
- Unit-Tests für Logik ohne Framework-Abhängigkeit (Parser, Statusermittlung, Team-Auflösung, Schutzlogik)
- Feature-Tests für HTTP-Endpunkte, Inertia-Responses, Commands, Auth
- Test-Datenbank: SQLite in-memory (in `phpunit.xml` konfiguriert); Migrationen sollen datenbankneutral bleiben (keine SQLite-spezifischen Konstrukte)
- Factories für alle Models
- Kein Netzwerkzugriff in Tests
- Frontend: Vitest für nicht-triviale Vue-Logik (z. B. Filterzustand, Datumsformatierung) – keine Snapshot-Tests

## 11. Entwicklungsplan

**Regel: Ein Schritt gilt erst als abgeschlossen, wenn `php artisan test` (und ab Schritt 6 zusätzlich `npm run test`) vollständig grün ist und Pint keine Fehler meldet. Erst dann wird mit dem nächsten Schritt begonnen.** Nach jedem Schritt: kurze Zusammenfassung, was gebaut wurde, **Smoketest durch den Nutzer**, und erst nach dessen ausdrücklicher Bestätigung Commit.

### Schritt 0 – Git-Repository anlegen
- Repository `fallakte` anlegen (öffentlich, daher strikt: keine Hosting-Infos, Pfade oder Zugangsdaten im Repo), Standard-Branch `main`
- Laravel-Projekt im Repo-Root erzeugen, Laravel-Standard-`.gitignore` übernehmen und ergänzen (`.env`, `node_modules/`, `vendor/`, IDE-Ordner); `public/build` **nicht** ignorieren, da es für das Deployment mit eingecheckt wird (siehe Abschnitt 7)
- `SPEC.md` unter `docs/SPEC.md` ablegen, `README.md` als Gerüst anlegen
- `.env.example` mit `APP_NAME="Amis Fallakte"`
- Erster Commit „chore: initial project setup", Push auf das Remote
- Commit-Messages ab hier nach Conventional Commits (`feat:`, `fix:`, `test:`, `chore:`, `docs:`), Englisch
- **Abnahme:** Repo mit lauffähigem Laravel-Grundgerüst ist auf dem Remote; `php artisan test` läuft mit den Standard-Beispieltests grün

### Schritt 1 – Projektgerüst
- Laravel 13, Breeze (Vue/Inertia), Tailwind, Pint, Pest/PHPUnit, Vitest einrichten
- Registrierung deaktivieren, `user:create`-Command
- **Tests:** Login funktioniert; Register-Route nicht erreichbar; geschützte Route leitet ohne Login um; `user:create` legt Benutzer an

### Schritt 2 – Datenmodell
- Migrationen, Models, Beziehungen, Enum `EpisodeStatus`, Factories, Seeder für `series`
- **Tests:** Beziehungen; Unique-Constraint `(series_id, number)`; Statusermittlung aus Datum (kein Datum / Zukunft → `announced`, Vergangenheit → `aired`)

### Schritt 3 – Wikipedia-Analyse und Parser
- Struktur der beiden Listen prüfen und in `docs/wikipedia-structure.md` dokumentieren
- Wikitext-Fixtures unter `tests/Fixtures` speichern
- Parser je Serie hinter gemeinsamem Interface; liefert DTOs (`ParsedEpisode`)
- **Tests (Unit):** korrekte Anzahl und Felder aus Fixtures; Sonderfälle (fehlendes Datum, Doppelfolgen, abweichende Schreibweisen, Sonderzeichen im Titel)

### Schritt 4 – Import-Service und Command
- `EpisodeImporter`-Service, Team-Auflösung über `team_aliases`, Schutzlogik `manual_fields`, `import_runs`-Protokoll
- Command `episodes:import {series?} {--dry-run}`, HTTP-Client mit User-Agent
- **Tests:** neue Folgen werden angelegt; geänderte aktualisiert; geschützte Felder bleiben; `seen`/`seen_at` bleiben immer erhalten; unbekannter Rohwert erzeugt Team + Alias; `--dry-run` schreibt nichts; `import_runs` korrekt befüllt

### Schritt 5 – Folgenliste (Backend)
- Controller/Query für Liste mit Filtern, Sortierung, Paginierung
- Endpunkt zum Abhaken / Entfernen des Hakens inkl. optionalem Datum
- **Tests:** jeder Filter einzeln und kombiniert; Sortierung; angekündigte Folge kann nicht abgehakt werden (422); Haken entfernen setzt `seen_at` auf null; Validierung des Datums

### Schritt 5a – UI-Grundlagen (Design)
Zeitpunkt bewusst nach dem Import (echte Daten vorhanden) und vor der ersten echten Seite, damit Schritte 6–9 auf einheitlichen Bausteinen aufbauen.
- **Gestaltung im Tatort-Stil:** klar und logisch, Grundfarbe **Dunkelblau mit weißer Schrift**, eine Akzentfarbe für Hervorhebungen; Farben als zentrale Design-Tokens (Tailwind-Theme), nicht verstreut im Code
- **Logo:** eigene Bildmarke, die an den Tatort-Vorspann erinnert (z. B. Fadenkreuz mit Auge) plus Schriftzug „Amis Fallakte“ als SVG; **kein Nachbau des geschützten ARD-Logos** (öffentliches Repo)
- **Responsive / Mobile first:** alle Seiten am Smartphone bequem bedienbar (Touch-Ziele ≥ 44 px, keine horizontale Seiten-Scrollbar, Tabellen werden auf schmalen Bildschirmen zu Karten/Listen, Filter einklappbar)
- Layout mit Navigation (Folgen, Teams, Statistik, Profil, Abmelden), mobil als einklappbares Menü
- Basiskomponenten: Tabelle/Liste, Filterleiste, Abhak-Checkbox mit Datum, Status-Kennzeichen „angekündigt“, Fortschrittsbalken, Buttons, Bestätigungsdialog, Pagination
- Login-Seite und vorhandene Seiten (Dashboard-Hülle, Profil) im neuen Design
- **Tests:** Vitest für Komponenten mit Logik (z. B. Fortschrittsberechnung, Datumsformatierung); Feature-Test, dass die Seiten weiterhin rendern
- **Smoketest** zusätzlich auf dem Smartphone (bzw. in der mobilen Ansicht der Browser-Entwicklertools)

### Schritt 6 – Folgenliste (Frontend)
- Vue-Seite mit Tabelle, Filterleiste (Zustand im URL-Query), Inline-Abhaken mit Datepicker
- **Tests:** Vitest für Filter-/URL-Synchronisation; Feature-Test, dass die Inertia-Seite mit den erwarteten Props rendert

### Schritt 7 – Folge bearbeiten und anlegen
- Formular, Markierung geschützter Felder, Reset je Feld, manuelles Anlegen
- **Tests:** Änderung trägt Feld in `manual_fields` ein; Reset entfernt es; manuell angelegte Folge hat alle Felder geschützt; Validierung

### Schritt 8 – Teams
- Team-Übersicht mit Fortschritt, Bearbeiten, Merge
- **Tests:** Fortschritt „x von y" ohne angekündigte Folgen; Merge verschiebt Folgen und Aliase und löscht Quellteam; Merge in Team einer anderen Serie wird abgelehnt

### Schritt 9 – Dashboard
- Statistik-Service + Seite mit Diagrammen
- **Tests (Unit):** alle Kennzahlen gegen bekannte Factory-Daten; angekündigte Folgen zählen nicht; Folgen ohne `seen_at` fehlen in „gesehen pro Jahr"

### Schritt 9a – Über-Seite
Vom Nutzer gewünschter Zwischenschritt vor dem Abschluss. Inhalte werden zu Beginn des Schritts abgestimmt; naheliegend:
- Widmung: „Für Ami mit ❤️ erstellt.“
- Name und Logo der App, kurzer Zweck
- Datenquelle mit Quellenangabe: deutschsprachige Wikipedia, Lizenz CC BY-SA 4.0, Links zu den beiden Episodenlisten
- Stand der Daten (letzter Import je Reihe)
- Hinweis, dass die Bildmarke eine eigene Gestaltung ist (kein Logo der ARD)
- **Keine** Hosting-Informationen, Pfade oder persönlichen Kontaktdaten
- Im Block Datenstand: Knopf für den manuellen Import beider Reihen (mit Bestätigung, Sperre und 5-Minuten-Bremse)
- Version nach Semantic Versioning aus der Datei `VERSION` (Start 0.9.0; Hochzählen übernimmt das Deploy-Skript, Schritt 10)
- Link in der Hauptnavigation, aber dezenter dargestellt
- **Tests:** Seite rendert, nur mit Login erreichbar; Version wird gelesen, ungültig/fehlend → „dev“

### Erweiterung – Backup (nach 1.0.0)
Block „Backup“ auf der Über-Seite mit drei Downloads (nur Herunterladen, keine Wiederherstellung in der App):
- **SQLite:** vollständige, konsistente Kopie der Datenbank per `VACUUM INTO` (inkl. Logins mit verschlüsselten Passwörtern, ohne Sitzungen/Cache/Jobs) – nur bei SQLite-Datenbank angeboten; Wiederherstellen per SSH durch Zurückkopieren
- **JSON:** Reihen, Teams mit Schreibweisen, Folgen (inkl. gesehen, geschützte Felder) und Importläufe – ohne Logins
- **Excel (.xlsx):** Blätter Folgen, Teams, Importe; echte Datums- und Zahlenwerte, fixierte Kopfzeile, Filter – ohne externe Bibliothek (PHP-Erweiterung `zip` erforderlich)
- Dateiname `fallakte-backup-JJJJ-MM-TT.<endung>`; temporäre Dateien werden nach dem Senden gelöscht, Reste älter als 1 Stunde automatisch entfernt
- **Tests:** Inhalt und Datenschutz (keine Logins in JSON/Excel), gültige xlsx-Struktur, SQLite-Kopie vollständig und ohne Sitzungen, Login-Pflicht

### Schritt 10 – Abschluss
- **Deploy-Skript** (lokal ausgeführt): zählt die Version in `VERSION` nach Semantic Versioning hoch (`major` / `minor` / `patch`), baut das Frontend, lädt hoch und führt auf dem Server `composer install --no-dev`, `php artisan migrate --force` und `php artisan config:cache` aus (sonst bleibt die alte Version im Konfig-Cache). Zugangsdaten und Ziel (Host, Pfad, Benutzer) liest es aus einer **lokalen, ignorierten** Datei – nie aus dem Repo
- README mit Installation, Deployment (lokaler Build, Upload `public/build`), Import, optional Scheduler, zweitem Login und `WIKIPEDIA_USER_AGENT`
- Komplettlauf aller Tests, Pint, Prüfung aller Abnahmekriterien aus Abschnitt 9

Bei Unklarheiten zur Wikipedia-Struktur oder zur Team-Zuordnung: **nachfragen statt raten.**
