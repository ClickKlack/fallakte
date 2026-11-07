<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Vertrauenswürdige Proxys
    |--------------------------------------------------------------------------
    |
    | Vorgeschaltete Reverse-Proxys (z. B. der Synology-Reverse-Proxy vor dem Docker-Container),
    | deren X-Forwarded-*-Header übernommen werden – nötig, damit Laravel hinter einem HTTPS-Proxy
    | korrekte https-Links erzeugt. Kommagetrennte IP-Adressen/Netze oder „*“; leer = keinem Proxy vertrauen.
    |
    */

    'proxies' => env('TRUSTED_PROXIES') ?: null,

];
