<?php

use App\Import\Wikipedia\ParsedEpisode;
use App\Import\Wikipedia\Parsers\TatortParser;
use App\Models\Series;

function tatortFixture(string $name): string
{
    return file_get_contents(dirname(__DIR__, 3).'/Fixtures/wikipedia/'.$name);
}

/** @param list<ParsedEpisode> $episodes */
function byTitle(array $episodes, string $title): ParsedEpisode
{
    foreach ($episodes as $episode) {
        if ($episode->title === $title) {
            return $episode;
        }
    }

    throw new RuntimeException("Folge {$title} nicht gefunden");
}

test('parser is configured for tatort', function () {
    $parser = new TatortParser;

    expect($parser->seriesSlug())->toBe(Series::TATORT)
        ->and($parser->pageTitle())->toBe('Liste der Tatort-Folgen');
});

describe('real page snapshot', function () {
    beforeEach(fn () => $this->result = (new TatortParser)->parse(tatortFixture('tatort.wiki')));

    test('reads all numbered episodes without errors', function () {
        $numbers = array_map(fn (ParsedEpisode $e) => $e->number, $this->result->episodes);

        expect($this->result->errors)->toBe([])
            ->and($this->result->episodes)->toHaveCount(1350)
            ->and($numbers)->toBe(range(1, 1350));
    });

    test('skips announced episodes without number', function () {
        expect($this->result->skipped)->toHaveCount(3)
            ->and($this->result->skipped[0])->toContain('Zwischenwelten');
    });

    test('ignores ORF-only productions', function () {
        $titles = array_map(fn (ParsedEpisode $e) => $e->title, $this->result->episodes);

        expect($titles)->not->toContain('Fahrerflucht');
    });

    test('reads the first episode completely', function () {
        $first = $this->result->episodes[0];

        expect($first->number)->toBe(1)
            ->and($first->title)->toBe('Taxi nach Leipzig')
            ->and($first->firstAired->toDateString())->toBe('1970-11-29')
            ->and($first->teamRaw)->toBe('Trimmel')
            ->and($first->wikipediaRef)->toBe('Tatort: Taxi nach Leipzig (1970)')
            ->and($first->fromAnnouncedSection)->toBeFalse();
    });

    test('every episode has a date and a team', function () {
        foreach ($this->result->episodes as $episode) {
            expect($episode->firstAired, "Folge {$episode->number}")->not->toBeNull()
                ->and($episode->teamRaw, "Folge {$episode->number}")->not->toBeNull();
        }
    });

    test('marks episodes from the announced section', function () {
        $announced = array_filter($this->result->episodes, fn (ParsedEpisode $e) => $e->fromAnnouncedSection);

        expect($announced)->toHaveCount(6);
    });

    test('handles special characters, plain text dates and team spellings', function () {
        $episodes = $this->result->episodes;

        expect(byTitle($episodes, 'Saarbrücken, an einem Montag …')->number)->toBe(2)
            ->and(byTitle($episodes, 'Mike & Nisha')->number)->toBe(1313)
            ->and(byTitle($episodes, 'König in Gelb')->firstAired->toDateString())->toBe('2026-09-13')
            ->and(byTitle($episodes, 'Kressin und der tote Mann im Fleet')->teamRaw)->toBe('Kressin')
            ->and(byTitle($episodes, 'Unter Freunden')->teamRaw)->toBe('Şahin und Krieger')
            ->and(byTitle($episodes, 'Explosive Mischung')->teamRaw)->toBe('Odenthal und Stern');
    });

    test('double episodes are two separate episodes', function () {
        expect(byTitle($this->result->episodes, 'Unter Freunden')->number)->toBe(1342)
            ->and(byTitle($this->result->episodes, 'Unter Feinden')->number)->toBe(1343);
    });

    test('team values never contain markup or guest appearances', function () {
        foreach ($this->result->episodes as $episode) {
            expect($episode->teamRaw)->not->toMatch('/\[\[|\]\]|\{\{|<|Gastauftritt|^\|/');
        }
    });
});

describe('edge cases', function () {
    beforeEach(fn () => $this->result = (new TatortParser)->parse(tatortFixture('edge-cases-tatort.wiki')));

    test('reads valid rows and reports broken ones', function () {
        $numbers = array_map(fn (ParsedEpisode $e) => $e->number, $this->result->episodes);

        expect($numbers)->toBe([1, 2, 3, 4, 5, 6, 7, 8, 9, 12])
            ->and($this->result->errors)->toHaveCount(4)
            ->and($this->result->errors[0])->toContain('Kaputt')
            ->and($this->result->errors[1])->toContain('doppelt')
            ->and($this->result->errors[2])->toContain('3 statt 9 Zellen')
            ->and($this->result->errors[3])->toContain('11a');
    });

    test('title is taken before line break annotations', function () {
        expect($this->result->episodes[0]->title)->toBe('Taxi nach Leipzig');
    });

    test('inline row with plain text date and entity in title', function () {
        $episode = byTitle($this->result->episodes, 'Mike & Nisha');

        expect($episode->number)->toBe(4)
            ->and($episode->firstAired->toDateString())->toBe('2026-09-13')
            ->and($episode->teamRaw)->toBe('Thiel und Boerne');
    });

    test('crossover double episode uses the first named team', function () {
        expect(byTitle($this->result->episodes, 'Kinderland')->teamRaw)->toBe('Ehrlicher und Kain')
            ->and(byTitle($this->result->episodes, 'Ihr Kinderlein kommet')->teamRaw)->toBe('Ballauf und Schenk');
    });

    test('missing and unreadable dates become null', function () {
        expect(byTitle($this->result->episodes, 'Ohne Datum')->firstAired)->toBeNull()
            ->and(byTitle($this->result->episodes, 'Kaputt')->firstAired)->toBeNull();
    });

    test('title without link has no wikipedia reference', function () {
        $episode = byTitle($this->result->episodes, 'Ohne Link');

        expect($episode->wikipediaRef)->toBeNull()
            ->and($episode->teamRaw)->toBe('Unbekannt');
    });

    test('announced section is flagged and unnumbered rows are skipped', function () {
        expect(byTitle($this->result->episodes, 'Bald')->fromAnnouncedSection)->toBeTrue()
            ->and($this->result->skipped)->toBe(['„Zwischenwelten“: angekündigt, noch ohne Folgennummer']);
    });

    test('missing sections are reported as error', function () {
        $result = (new TatortParser)->parse("== Andere Seite ==\n{|\n! Folge\n|}");

        expect($result->episodes)->toBe([])
            ->and($result->errors)->toHaveCount(2)
            ->and($result->errors[0])->toContain('Ausgestrahlte Folgen');
    });

    test('missing columns are reported as error', function () {
        $result = (new TatortParser)->parse("=== Ausgestrahlte Folgen ===\n{|\n! Folge !! Titel\n|-\n| 1 || X\n|}");

        expect($result->errors[0])->toContain('Spalte(n) nicht gefunden: date, team');
    });
});
