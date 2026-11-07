# CLAUDE.md – Amis Fallakte

Persönliche Single-User-Webapp zum Abhaken gesehener Tatort- und Polizeiruf-110-Folgen.
**Verbindliche Spezifikation: [docs/SPEC.md](docs/SPEC.md)** – vor jeder Arbeit den aktuellen Schritt aus Abschnitt 11 lesen.

## Unverhandelbare Regeln

### 1. Keine sensiblen Daten im Repository (Repo ist öffentlich)
- **Niemals** Hosting-Informationen (Hostnamen, Domains des Webspaces, Provider, SSH-Benutzer, Ports),
  absolute Pfade (lokale Home-Verzeichnisse ebenso wie Webspace-/Serverpfade)
  oder Zugangsdaten (Passwörter, Tokens, `APP_KEY`, private Schlüssel) in versionierte Dateien schreiben –
  auch nicht in Kommentare, README, Doku, Tests, Fixtures, Commit-Messages oder PR-Texte.
- Solche Werte gehören nur in `.env` oder in ignorierte lokale Dateien (`*.local`, `*.local.*`, `deploy/`, `.secrets-denylist`).
- In Doku nur Platzhalter verwenden (`<ssh-user>@<host>`, `<pfad-zur-sqlite-datei>`).
- `.env`, `.env.*` (außer `.env.example`), `*.sqlite`, Schlüsseldateien nie stagen. Kein `git add -A`/`git add .` ohne vorher `git status` geprüft zu haben.
- Der Pre-Commit-Hook `.githooks/pre-commit` muss aktiv sein (`git config core.hooksPath .githooks`). Niemals `--no-verify` verwenden.
- Der User-Agent für die Wikipedia-API kommt aus der `.env` (Kontaktangabe nicht hart kodieren).

### 2. Commits nur nach Smoketest und Bestätigung
- Nach jedem Schritt: Tests + Pint grün, kurze Zusammenfassung, dann **Anleitung für einen Nutzer-Smoketest** (was aufrufen/ausführen, was erwartet wird).
- **Erst wenn der Nutzer den Smoketest bestätigt und den Commit ausdrücklich freigibt, wird committet.** Gilt für jeden Commit, auch kleine Fixes. Push ebenfalls nur nach Freigabe.
- Vor jedem Commit `git diff --cached` auf sensible Daten prüfen.
- Commit-Messages und PR-Texte enthalten **keinen Verweis auf Claude/KI** (kein `Co-Authored-By`, kein „Generated with …“).

### 3. Schrittweise nach Plan
- Ein Schritt aus SPEC Abschnitt 11 gilt erst als fertig, wenn `php artisan test` (ab Schritt 6 zusätzlich `npm run test`) grün ist und `vendor/bin/pint --test` keine Fehler meldet.
- Bei Unklarheiten (insbesondere Wikipedia-Struktur, Team-Zuordnung): **nachfragen statt raten.**

## Tech-Stack
- PHP 8.3+, **Laravel 13** (Spec nannte ursprünglich 11 – EOL), Vue 3 + Inertia.js (Composition API, `<script setup>`), Tailwind CSS
- **SQLite** (Dev und Prod; Tests in-memory über `phpunit.xml`). Migrationen datenbankneutral halten, damit MariaDB möglich bleibt.
- Auth: Laravel Breeze (Vue/Inertia), Registrierung deaktiviert, Benutzer per `php artisan user:create`
- Tests: Pest/PHPUnit, Vitest für nicht-triviale Vue-Logik (keine Snapshot-Tests)
- Zielsystem: Webspace mit SSH + Composer, **ohne Node** → `npm run build` lokal beim Deployment, `public/build` ist **nicht** im Repo (wird per rsync hochgeladen)

## Konventionen
- **Englisch:** alle Bezeichner (Tabellen, Spalten, Klassen, Methoden, Variablen, Routen-Namen, Enum-Werte, Testnamen) und Commit-Messages (Conventional Commits: `feat:`, `fix:`, `test:`, `chore:`, `docs:`)
- **Deutsch:** Code-Kommentare, PHPDoc-Beschreibungen, alle UI-Texte
- UI-Texte zentral (`lang/de/*.php` bzw. zentrale Frontend-Übersetzungsdatei), nie verstreut hart kodiert
- Anzeigename „Amis Fallakte" nur über `config('app.name')` / Übersetzungen; technischer Name `fallakte`
- PSR-12 / Pint, Laravel-Konventionen
- Zu jedem Schritt Unit- und Feature-Tests; Factories für alle Models; **keine echten HTTP-Aufrufe in Tests** (`Http::fake()`, Fixtures unter `tests/Fixtures`)
- Der Import fasst `seen`/`seen_at` nie an; manuell geänderte Felder (`manual_fields`) werden nicht überschrieben

## Befehle
```bash
php artisan test              # PHP-Tests
vendor/bin/pint --test        # Code-Stil prüfen (ohne --test: korrigieren)
npm run build                 # Frontend-Build (lokal, nicht im Repo)
npm run test                  # Vitest (ab Schritt 6)
php artisan episodes:import {series?} {--dry-run}
npm run format            # Prettier (JS/Vue)
bin/deploy <major|minor|patch|none> [--dry-run]   # Deployment – nur der Nutzer führt es aus
```

## Deployment
- `bin/deploy` liest Ziel und Zugang ausschließlich aus der ignorierten `deploy.local.env` (Vorlage `deploy.example.env` nur mit Platzhaltern).
- Alternativ Docker (z. B. Synology): `Dockerfile`, `docker-compose.yml` (Services `app` + `scheduler`), Vorlage `docker.example.env`, Daten im ignorierten `data/`. Anleitung im README.
- Das Skript zählt `VERSION` (Semantic Versioning) hoch und committet/taggt/pusht nach Rückfrage – es wird **nur vom Nutzer** gestartet, nie von Claude.
