# Amis Fallakte

Persönliche Webapp zur Verwaltung gesehener Folgen von **Tatort** und **Polizeiruf 110**.
Der Episodenkatalog wird aus der deutschsprachigen Wikipedia übernommen und lässt sich in der App korrigieren;
eigene Korrekturen und die Haken „gesehen“ bleiben bei späteren Importen erhalten.

- Folgenliste mit Filtern (Reihe, Team, Sender, Status, Jahre, Zeitraum „gesehen am“, Titel), Schnellumschalter
  Alle/Gesehen/Ungesehen, Sortierung (auch nach „gesehen am“) und Abhaken direkt in der Liste
- Folgen bearbeiten und manuell anlegen; manuell geänderte Felder sind vor dem Import geschützt
- Team-Übersicht mit Fortschritt, Bearbeiten, Zusammenführen und Vorschlägen für zusammengehörige Teams
- Statistik: Gesamtfortschritt, Fortschritt nach Ausstrahlungsjahr, gesehene Folgen pro Jahr, Team-Ranglisten
- Für Smartphone und Desktop gestaltet

Spezifikation: [docs/SPEC.md](docs/SPEC.md) · Aufbau der Wikipedia-Listen: [docs/wikipedia-structure.md](docs/wikipedia-structure.md)

## Tech-Stack

PHP 8.3+, Laravel 13, Vue 3 + Inertia.js, Tailwind CSS 4, SQLite (MariaDB/MySQL ebenfalls getestet).

## Lokale Entwicklung

Voraussetzungen: PHP 8.3+ mit `pdo_sqlite`, Composer, Node.js.

```bash
git config core.hooksPath .githooks   # Schutz vor versehentlich committeten Zugangsdaten
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed            # Tabellen und die beiden Reihen
php artisan user:create               # Login anlegen (Registrierung ist deaktiviert)
php artisan episodes:import           # Folgen aus Wikipedia übernehmen
composer run dev                      # Server + Vite
```

Prüfen:

```bash
php artisan test          # PHP (Pest)
npm run test              # Frontend (Vitest)
vendor/bin/pint --test    # Code-Stil PHP
npm run format:check      # Code-Stil JS/Vue (Prettier)
```

Bei `APP_ENV=local` zeigt `/styleguide` alle UI-Bausteine.

## Schutz sensibler Daten

Das Repository ist öffentlich. Hosting-Informationen, Serverpfade und Zugangsdaten gehören ausschließlich
in `.env` bzw. in lokale, von Git ignorierte Dateien (`deploy.local.env`, `*.local`, `*.local.*`, `deploy/`).
Der Pre-Commit-Hook in `.githooks/` blockiert typische Muster; eigene Suchbegriffe (Hostname, SSH-Benutzer,
Domain …) zeilenweise in `.secrets-denylist` eintragen – die Datei wird nie committet.

## Betrieb auf einem Webspace

Zielsystem: Webspace mit SSH, Composer und PHP 8.3+ (`pdo_sqlite`, `zip`), **ohne Node** – das Frontend wird
beim Deployment lokal gebaut und hochgeladen (`public/build` liegt nicht im Repository).

### Ersteinrichtung (einmalig)

1. **Ordner anlegen:** einen Projektordner auf dem Server und – außerhalb davon und außerhalb des Web-Roots –
   einen Ordner für die Datenbank, in den PHP schreiben darf. Leere Datenbankdatei anlegen:
   `touch <pfad-zur-datenbank>/fallakte.sqlite`
2. **Web-Root** der Domain auf `<projektordner>/public` zeigen lassen (in der Verwaltung des Hosters).
3. **Deployment konfigurieren (lokal):** `cp deploy.example.env deploy.local.env` und Zugangsdaten eintragen
   (`DEPLOY_SSH`, `DEPLOY_PATH`, ggf. `DEPLOY_PHP`/`DEPLOY_COMPOSER`). Die Datei wird von Git ignoriert.
4. **`.env` auf dem Server** im Projektordner anlegen (das Deployment überträgt und überschreibt sie nie):
   ```dotenv
   APP_NAME="Amis Fallakte"
   APP_ENV=production
   APP_DEBUG=false
   APP_KEY=
   APP_URL=https://<deine-domain>
   APP_LOCALE=de
   DB_CONNECTION=sqlite
   DB_DATABASE=<pfad-zur-datenbank>/fallakte.sqlite
   SESSION_DRIVER=database
   SESSION_SECURE_COOKIE=true
   CACHE_STORE=database
   LOG_LEVEL=warning
   WIKIPEDIA_USER_AGENT="fallakte/1.0 (+https://github.com/ClickKlack/fallakte; <deine-kontaktadresse>)"
   ```
5. **Erstes Deployment (lokal):** `bin/deploy major` – aus 0.9.x wird Version 1.0.0.
6. **Auf dem Server** (per SSH im Projektordner):
   ```bash
   php artisan key:generate --force   # einmalig: Schlüssel in die .env schreiben
   php artisan db:seed --force        # legt die beiden Reihen an (keine Benutzer)
   php artisan user:create            # zuerst Ami, auf Wunsch danach ein zweiter Login
   php artisan episodes:import
   php artisan config:cache
   ```

> **Mehrere Logins:** `user:create` lässt sich mehrfach ausführen. Alle Logins teilen sich **einen**
> Datenbestand (z. B. Ami als Hauptnutzerin plus ein zweiter Login zum Mitschauen).
> `WIKIPEDIA_USER_AGENT` sollte eine eigene Kontaktadresse enthalten; ohne diese Einstellung
> wird die E-Mail-Adresse des zuerst angelegten Benutzers als Kontakt an Wikipedia übermittelt.

### Deployment

```bash
bin/deploy patch             # Fehlerbehebung:        1.0.0 → 1.0.1
bin/deploy minor             # neue Funktion:         1.0.1 → 1.1.0
bin/deploy major             # großer Versionssprung: 1.1.0 → 2.0.0
bin/deploy none              # ohne neue Version (z. B. nur Server aktualisieren)
bin/deploy patch --dry-run   # Probelauf: zeigt, was passieren würde
```

Das Skript

1. prüft, dass alles committet ist und auf `main` gearbeitet wird,
2. führt alle Tests und Code-Stil-Prüfungen aus (bricht bei Fehlern ab),
3. zählt die Version in `VERSION` hoch (Semantic Versioning) und baut das Frontend,
4. committet nach **Rückfrage** „chore: release vX.Y.Z“ (nur die Datei `VERSION`), setzt den Tag `vX.Y.Z` und pusht,
5. lädt per `rsync` hoch, inklusive des frisch gebauten Frontends – `.env`, Datenbank, `storage/`, Tests,
   Doku und Entwicklungsdateien bleiben außen vor,
6. führt auf dem Server `composer install --no-dev`, `migrate --force` sowie `config:cache`, `route:cache`
   und `view:cache` aus.

Die aktuelle Version steht auf der Seite „Über“.

### Import

- **In der App:** Seite „Über“ → „Jetzt von Wikipedia aktualisieren“ (höchstens alle 5 Minuten).
- **Per SSH:** `php artisan episodes:import` (beide Reihen) bzw. `php artisan episodes:import tatort`;
  mit `--dry-run` nur anzeigen, was sich ändern würde, mit `-v` jede Änderung auflisten.

Es läuft immer nur ein Import gleichzeitig. Manuell korrigierte Felder und „gesehen“ werden nie überschrieben;
in Wikipedia entfernte Folgen werden nicht gelöscht.

### Backup

Seite „Über“ → Block **Backup**: Download als **Excel** (zum Anschauen), **JSON** (maschinenlesbar) oder
**SQLite** (vollständige Kopie der Datenbank inkl. Logins – sicher aufbewahren). Wiederherstellen: die
SQLite-Datei per SSH an die Stelle der Datenbankdatei aus `DB_DATABASE` kopieren (vorher die alte sichern).
Für den Excel-Export braucht der Server die PHP-Erweiterung `zip`.

### Optional: automatischer Import per Cron

Nur falls der Webspace Cronjobs anbietet. In `routes/console.php` einen Zeitplan ergänzen, z. B.

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('episodes:import')->weeklyOn(1, '04:00');
```

und beim Hoster einen Cronjob einrichten, der jede Minute
`cd <projektordner> && php artisan schedule:run` ausführt.

## Quellen und Lizenz der Daten

Folgennummern, Titel, Ausstrahlungsdaten, Sender und Ermittler stammen aus den Episodenlisten der
deutschsprachigen Wikipedia ([Tatort](https://de.wikipedia.org/wiki/Liste_der_Tatort-Folgen),
[Polizeiruf 110](https://de.wikipedia.org/wiki/Liste_der_Polizeiruf-110-Folgen)) und stehen unter
[CC BY-SA 4.0](https://creativecommons.org/licenses/by-sa/4.0/deed.de). „Tatort“ und „Polizeiruf 110“ sind
Marken der jeweiligen Rechteinhaber; das Logo dieser App ist eine eigene Gestaltung.

## Lizenz

Der Quellcode steht unter der [MIT-Lizenz](LICENSE) © 2026 Jörg Schönebaum.
Die Anwendung ist größtenteils mit Unterstützung eines KI-Assistenten entstanden.

Ausgenommen sind die Wikipedia-Auszüge unter `tests/Fixtures/wikipedia`: Sie bleiben unter
[CC BY-SA 4.0](https://creativecommons.org/licenses/by-sa/4.0/deed.de) (Quelle und Autoren: die jeweiligen
Wikipedia-Artikel und deren Versionsgeschichte).
