<?php

use App\Import\Wikipedia\TeamName;

test('team raw value is derived from the investigator cell', function (string $cell, ?string $expected) {
    expect(TeamName::fromCell($cell))->toBe($expected);
})->with([
    'display text of link' => ['[[Lena Odenthal|Odenthal und Stern]]', 'Odenthal und Stern'],
    'link without display text' => ['[[Thiel und Boerne]]', 'Thiel und Boerne'],
    'several links' => ['[[Dieter Eppler|Liersdahl]] und [[Manfred Heidmann|Schäfermann]]', 'Liersdahl und Schäfermann'],
    'guest appearance on new line' => ['[[Kressin]]<br />(Gastauftritt&nbsp;[[Paul Trimmel|Trimmel]])', 'Kressin'],
    'guest appearance inline' => ['[[Thiel und Boerne]] (Gastauftritt [[Ballauf und Schenk]])', 'Thiel und Boerne'],
    'guest appearance with colon' => ['Faber und Bönisch (Gastauftritt: Krusenstern)', 'Faber und Bönisch'],
    'minor character in parentheses' => ['[[Peter Borgelt|Peter Fuchs]], Arndt, ([[Werner Tietze|Woltersdorf]])', 'Peter Fuchs, Arndt'],
    'crossover takes first team' => ['[[Ehrlicher und Kain]]<br /><br />[[Ballauf und Schenk]]', 'Ehrlicher und Kain'],
    'ref and comment removed' => ['[[Ross und Rogov]]<ref>x</ref><!-- k -->', 'Ross und Rogov'],
    'special characters kept' => ['[[Şahin und Krieger]]', 'Şahin und Krieger'],
    'empty cell' => ['', null],
    'only guest' => ['(Gastauftritt Veigl)', null],
]);
