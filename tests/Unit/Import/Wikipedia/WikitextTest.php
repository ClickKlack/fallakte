<?php

use App\Import\Wikipedia\Wikitext;

test('internal links are reduced to their display text', function () {
    expect(Wikitext::toPlainText('[[Lena Odenthal|Odenthal und Stern]]'))->toBe('Odenthal und Stern')
        ->and(Wikitext::toPlainText('[[Thiel und Boerne]]'))->toBe('Thiel und Boerne');
});

test('refs, templates, comments and markup are removed', function () {
    $text = "''Kursiv'' {{Anker|A1970}}<ref name=\"x\" /> Text<ref>Nachweis\nüber Zeilen</ref> <!-- weg --><small>klein</small>";

    expect(Wikitext::toPlainText($text))->toBe('Kursiv Text klein');
});

test('nested templates are removed completely', function () {
    expect(Wikitext::toPlainText('A {{Außen|{{Innen|x}}|y}} B'))->toBe('A B');
});

test('html entities and non-breaking spaces are decoded', function () {
    expect(Wikitext::toPlainText('Mike &amp; Nisha'))->toBe('Mike & Nisha')
        ->and(Wikitext::toPlainText('Montag&nbsp;…'))->toBe('Montag …')
        ->and(Wikitext::toPlainText("Montag\u{00A0}…"))->toBe('Montag …');
});

test('nowiki content is kept', function () {
    expect(Wikitext::toPlainText('<nowiki>–</nowiki>'))->toBe('–');
});

test('first link target is returned', function () {
    expect(Wikitext::firstLinkTarget('[[Tatort: Taxi nach Leipzig (1970)|Taxi nach Leipzig]]<br />[[Andere]]'))
        ->toBe('Tatort: Taxi nach Leipzig (1970)')
        ->and(Wikitext::firstLinkTarget('[[Martin_Eigler_(Regisseur)|Martin Eigler]]'))->toBe('Martin Eigler (Regisseur)')
        ->and(Wikitext::firstLinkTarget('Ohne Link'))->toBeNull();
});

test('top level split ignores separators inside links and templates', function () {
    expect(Wikitext::splitTopLevel('a||[[x|y]]||{{t|1||2}}', '||'))->toBe(['a', '[[x|y]]', '{{t|1||2}}']);
});

test('cells are split at line breaks', function () {
    expect(Wikitext::splitLines('A<br />B<br>C<BR/>D'))->toBe(['A', 'B', 'C', 'D']);
});
