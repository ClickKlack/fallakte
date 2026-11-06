<?php

use App\Import\Wikipedia\WikitextTable;

test('tables are extracted with section, headers and rows', function () {
    $wikitext = <<<'WIKI'
    == Erster Abschnitt ==
    {| class="wikitable"
    ! style="width:3%"| Nr.
    ! [[Link|Titel]]
    |-
    | 1
    | style="color:red" | [[A|Alpha]]
    |-
    |2||Beta
    |}
    == Zweiter ==
    {|
    ! X !! Y
    |-
    | a || b
    |}
    WIKI;

    $tables = WikitextTable::extractAll($wikitext);

    expect($tables)->toHaveCount(2)
        ->and($tables[0]->section)->toBe('Erster Abschnitt')
        ->and($tables[0]->headers)->toBe(['Nr.', 'Titel'])
        ->and($tables[0]->rows)->toBe([['1', '[[A|Alpha]]'], ['2', 'Beta']])
        ->and($tables[1]->section)->toBe('Zweiter')
        ->and($tables[1]->headers)->toBe(['X', 'Y'])
        ->and($tables[1]->rows)->toBe([['a', 'b']]);
});

test('header row may be introduced by a row separator with attributes', function () {
    $tables = WikitextTable::extractAll("{|\n|- class=\"kopf\"\n! A\n! B\n|-\n| 1\n| 2\n|}");

    expect($tables[0]->headers)->toBe(['A', 'B'])
        ->and($tables[0]->rows)->toBe([['1', '2']]);
});

test('multi-line cells are joined', function () {
    $tables = WikitextTable::extractAll("{|\n! A\n|-\n| erste Zeile\nzweite Zeile\n|}");

    expect($tables[0]->rows[0][0])->toBe("erste Zeile\nzweite Zeile");
});

test('an empty attribute part before the first pipe is dropped', function () {
    $tables = WikitextTable::extractAll("{|\n! A\n! B\n|-\n|1\n||[[Link|Titel]]\n|}");

    expect($tables[0]->rows[0])->toBe(['1', '[[Link|Titel]]']);
});

test('commented out rows are ignored', function () {
    $tables = WikitextTable::extractAll("{|\n! A\n|-\n| echt\n<!--\n|-\n| Vorlage\n-->\n|}");

    expect($tables[0]->rows)->toBe([['echt']]);
});

test('column index is found by header pattern', function () {
    $table = WikitextTable::extractAll("{|\n! Folge !! Titel !! Geplante Erstausstrahlung\n|}")[0];

    expect($table->columnIndex('/^Titel$/'))->toBe(1)
        ->and($table->columnIndex('/Erstausstr/'))->toBe(2)
        ->and($table->columnIndex('/^Regie$/'))->toBeNull();
});
