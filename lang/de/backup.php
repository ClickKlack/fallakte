<?php

// Texte für die Backups (Excel-Blätter und -Spalten).
return [
    'yes' => 'ja',
    'no' => 'nein',
    'status' => ['aired' => 'ausgestrahlt', 'announced' => 'angekündigt'],
    'fields' => [
        'title' => 'Titel', 'first_aired' => 'Erstausstrahlung', 'broadcaster' => 'Sender',
        'team_id' => 'Team', 'number' => 'Nummer',
    ],
    'sheets' => ['episodes' => 'Folgen', 'teams' => 'Teams', 'imports' => 'Importe'],
    'columns' => [
        'episodes' => ['Reihe', 'Nr.', 'Titel', 'Erstausstrahlung', 'Sender', 'Team', 'Status', 'Gesehen', 'Gesehen am', 'Manuell geändert'],
        'teams' => ['Reihe', 'Team', 'Stadt', 'Ermittler', 'Ausgestrahlte Folgen', 'Gesehen', 'Schreibweisen bei Wikipedia'],
        'imports' => ['Reihe', 'Gestartet', 'Neu', 'Aktualisiert', 'Übersprungen (geschützt)', 'Fehler', 'Fehlermeldungen'],
    ],
];
