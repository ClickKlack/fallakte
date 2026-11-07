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

PHP 8.4+, Laravel 13, Vue 3 + Inertia.js, Tailwind CSS 4, SQLite (MariaDB/MySQL ebenfalls getestet).

## Lokale Entwicklung

Voraussetzungen: PHP 8.4+ mit `pdo_sqlite`, Composer, Node.js.

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

Zielsystem: Webspace mit SSH, Composer und PHP 8.4+ (`pdo_sqlite`, `zip`), **ohne Node** – das Frontend wird
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

Nur falls der Webspace Cronjobs anbietet. Der Zeitplan ist bereits eingebaut (Standard: montags 04:00,
änderbar über `IMPORT_SCHEDULE`/`IMPORT_TIMEZONE` in der `.env`, leer = aus). Beim Hoster einen Cronjob
einrichten, der jede Minute `cd <projektordner> && php artisan schedule:run` ausführt.

## Betrieb mit Docker (z. B. Synology NAS)

Das Image wird direkt auf dem Zielsystem aus dem Repository gebaut, eine Registry ist nicht nötig. Die
`docker-compose.yml` startet zwei Container aus demselben Image:

- **app** – Webserver (Apache + PHP 8.4); führt beim Start Migrationen und Stammdaten aus
- **scheduler** – führt den automatischen Wikipedia-Import aus (Standard: montags 04:00)

Alle Daten – SQLite-Datenbank, Logs, Sessions und der automatisch erzeugte Schlüssel `app.key` – liegen im
Ordner `data/` neben der `docker-compose.yml`.

### Einrichtung auf einer Synology NAS

Voraussetzung: DSM 7.2 oder neuer mit dem Paket **Container Manager** (Paket-Zentrum). Das Image läuft auf
Intel/AMD- und ARM64-Modellen.

1. **Ordner anlegen:** In der File Station z. B. `docker/fallakte` im Freigabeordner `docker` anlegen.
2. **Code holen** – eine der beiden Varianten:
   - *ohne Kommandozeile:* auf GitHub „Code → Download ZIP“, das ZIP per File Station in den Ordner
     hochladen und dort entpacken (Rechtsklick → Entpacken), sodass `docker-compose.yml` direkt im Ordner liegt;
   - *per SSH* (Systemsteuerung → Terminal & SNMP → SSH aktivieren; Git z. B. über das Paket „Git Server“):
     ```bash
     cd <pfad-zum-freigabeordner-docker>
     git clone https://github.com/ClickKlack/fallakte.git fallakte
     ```
3. **Konfiguration:** `docker.example.env` im selben Ordner als `.env` kopieren und anpassen (in der File
   Station: Kopie anlegen, umbenennen, mit dem Texteditor-Paket bearbeiten). Mindestens:
   - `APP_URL` – Adresse, unter der die App aufgerufen wird (siehe „Zugriff“ unten)
   - `APP_PORT` – Port auf der NAS (Standard `8080`; muss frei sein)
   - `WIKIPEDIA_USER_AGENT` – mit eigener Kontaktadresse

   `APP_KEY` kann leer bleiben: Beim ersten Start wird ein Schlüssel erzeugt und in `data/app.key` abgelegt.
4. **Projekt anlegen:** Container Manager → **Projekt** → **Erstellen**
   - Projektname: `fallakte`
   - Pfad: den Ordner aus Schritt 1 wählen
   - Quelle: „Vorhandene docker-compose.yml verwenden“
   - Weiter → Fertig. Der erste Build dauert einige Minuten (Frontend und PHP-Abhängigkeiten werden gebaut).
5. **Logins anlegen und ersten Import starten:** Container Manager → **Container** → `fallakte-app-1` →
   **Aktion → Terminal öffnen** → **Erstellen** → Befehl `bash`, dann:
   ```bash
   artisan user:create
   artisan episodes:import
   ```
   Per SSH alternativ im Projektordner: `sudo docker compose exec app artisan user:create`.
   (`artisan` ist ein Kurzbefehl im Container, der als Webserver-Benutzer läuft.)
6. Die App im Browser unter der `APP_URL` aufrufen und anmelden.

### Zugriff

- **Nur im Heimnetz (einfachste Variante):** `APP_URL=http://<nas-ip>:8080`,
  `SESSION_SECURE_COOKIE=false`. Aufruf über `http://<nas-ip>:8080`.
- **Über HTTPS mit eigener Domain:** Reverse-Proxy der DSM verwenden. Systemsteuerung → Anmeldeportal →
  Erweitert → **Reverse Proxy** → Erstellen:
  - Quelle: Protokoll `HTTPS`, Hostname `<deine-domain>`, Port `443`
  - Ziel: Protokoll `HTTP`, Hostname `localhost`, Port `8080` (bzw. `APP_PORT`)

  Ein Zertifikat für die Domain unter Systemsteuerung → Sicherheit → Zertifikat einrichten (z. B. Let's Encrypt)
  und dem Reverse-Proxy-Eintrag zuweisen. In der `.env` dann `APP_URL=https://<deine-domain>` und
  `SESSION_SECURE_COOKIE=true` setzen und das Projekt neu starten. `TRUSTED_PROXIES` ist in der Vorlage
  bereits so gesetzt, dass die Weiterleitungs-Header des Reverse-Proxys akzeptiert werden.

Nach Änderungen an der `.env`: Container Manager → Projekt → `fallakte` → **Aktion → Stopp**, dann **Start**
(bzw. `sudo docker compose up -d`).

### Update auf eine neue Version

1. Neuen Code holen: per SSH `git pull` im Projektordner bzw. das neue ZIP über die alten Dateien entpacken
   (`.env` und `data/` bleiben dabei unangetastet).
2. Container Manager → Projekt → `fallakte` → **Aktion → Erstellen** (baut das Image neu und startet die
   Container), per SSH: `sudo docker compose up -d --build`.

Migrationen laufen beim Start automatisch.

### Backup

Den Ordner `data/` sichern (z. B. mit Hyper Backup) – er enthält Datenbank und Schlüssel. Zusätzlich ist der
Download auf der Seite „Über“ → Backup möglich. Wiederherstellen: Projekt stoppen, die SQLite-Datei nach
`data/database/fallakte.sqlite` kopieren, Projekt starten.

### Automatischer Import

Der Container `scheduler` führt `episodes:import` nach `IMPORT_SCHEDULE` aus (Cron-Ausdruck, Standard
`0 4 * * 1` = montags 04:00 in `IMPORT_TIMEZONE`). Leerer Wert schaltet den automatischen Import ab.
Ergebnis: Seite „Über“ → letzter Import, Fehler im Protokoll des Containers.

### Lokal testen

Im Entwicklungsordner ist die `.env` bereits für die lokale Entwicklung belegt, daher eine eigene Datei verwenden:

```bash
cp docker.example.env .env.docker            # .env.docker wird von Git ignoriert
ENV_FILE=.env.docker docker compose --env-file .env.docker up -d --build
```

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
