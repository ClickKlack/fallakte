#!/bin/sh
# Startskript des Containers.
# Bereitet das Datenverzeichnis (storage/) vor, sorgt für einen APP_KEY und startet dann
#   - "apache2-foreground": den Webserver (inkl. Migrationen und Stammdaten),
#   - "scheduler":          den Laravel-Scheduler (wöchentlicher Wikipedia-Import),
#   - alles andere:         den übergebenen Befehl (z. B. "artisan user:create").
set -e

cd /app

# Befehle als www-data ausführen, damit alle Dateien in storage/ dem Webserver gehören
as_www() {
    if [ "$(id -u)" = "0" ]; then
        setpriv --reuid=www-data --regid=www-data --init-groups "$@"
    else
        "$@"
    fi
}

# --- Datenverzeichnis ------------------------------------------------------------------------
mkdir -p \
    storage/app/private \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    "$(dirname "$DB_DATABASE")"

[ -f "$DB_DATABASE" ] || touch "$DB_DATABASE"

if [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data storage bootstrap/cache
fi

# --- APP_KEY -----------------------------------------------------------------------------------
# Ohne Angabe in der .env wird einmalig ein Schlüssel erzeugt und im Datenverzeichnis abgelegt,
# damit Webserver und Scheduler denselben verwenden und er Updates des Images übersteht.
if [ -z "$APP_KEY" ]; then
    key_file=storage/app.key
    if [ ! -s "$key_file" ]; then
        echo "Kein APP_KEY gesetzt – erzeuge neuen Schlüssel in storage/app.key"
        php -r 'echo "base64:".base64_encode(random_bytes(32));' > "$key_file"
        chmod 600 "$key_file"
        [ "$(id -u)" = "0" ] && chown www-data:www-data "$key_file"
    fi
    APP_KEY="$(cat "$key_file")"
    export APP_KEY
fi

# --- Start -------------------------------------------------------------------------------------
case "$1" in
    apache2-foreground)
        as_www php artisan migrate --force --no-interaction
        as_www php artisan db:seed --force --no-interaction
        as_www php artisan optimize
        exec "$@"
        ;;
    scheduler)
        as_www php artisan optimize
        exec setpriv --reuid=www-data --regid=www-data --init-groups php artisan schedule:work
        ;;
    *)
        exec "$@"
        ;;
esac
