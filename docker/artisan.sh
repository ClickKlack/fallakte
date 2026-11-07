#!/bin/sh
# Kurzbefehl „artisan …“ im Container (z. B. im Terminal des Synology Container Managers).
# Läuft immer als www-data, damit neu angelegte Dateien (Logs, Cache) für den Webserver schreibbar bleiben.
# Ohne gesetzten APP_KEY wird der im Datenverzeichnis abgelegte Schlüssel verwendet.
set -e
cd /app

if [ -z "$APP_KEY" ] && [ -s storage/app.key ]; then
    APP_KEY="$(cat storage/app.key)"
    export APP_KEY
fi

if [ "$(id -u)" = "0" ]; then
    exec setpriv --reuid=www-data --regid=www-data --init-groups php artisan "$@"
fi

exec php artisan "$@"
