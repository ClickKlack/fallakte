# Amis Fallakte

Persönliche Webapp zur Verwaltung gesehener Folgen von **Tatort** und **Polizeiruf 110**.
Der Episodenkatalog wird aus der deutschsprachigen Wikipedia importiert und kann manuell korrigiert werden.

Spezifikation: [docs/SPEC.md](docs/SPEC.md)

## Tech-Stack

- PHP 8.3+, Laravel 13
- Vue 3 + Inertia.js, Tailwind CSS
- SQLite
- Laravel Breeze (Login, Registrierung deaktiviert)

## Lokale Entwicklung

```bash
git config core.hooksPath .githooks   # Schutz vor versehentlich committeten Zugangsdaten
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm install
npm run dev
```

Tests:

```bash
php artisan test
```

### Schutz sensibler Daten

Das Repository ist öffentlich. Hosting-Informationen, Serverpfade und Zugangsdaten gehören
ausschließlich in `.env` bzw. in lokale, ignorierte Dateien (`*.local`, `*.local.*`, `deploy/`).
Der Pre-Commit-Hook in `.githooks/` blockiert typische Muster. Eigene Suchbegriffe
(z. B. Hostname, SSH-Benutzer, Domain) zeilenweise in `.secrets-denylist` eintragen –
die Datei wird nie committet.

## Deployment

_Folgt in Schritt 10._ Kurzfassung: Frontend lokal bauen (`npm run build`, `public/build` ist eingecheckt),
hochladen, `composer install --no-dev`, `.env` anlegen (SQLite-Pfad außerhalb des Web-Roots),
`php artisan migrate --force`, `php artisan user:create`, `php artisan episodes:import`.
