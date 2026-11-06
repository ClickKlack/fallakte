<?php

use App\Import\Wikipedia\ParsedEpisode;
use App\Import\Wikipedia\Parsers\PolizeirufParser;
use App\Models\Series;

function polizeirufFixture(string $name): string
{
    return file_get_contents(dirname(__DIR__, 3).'/Fixtures/wikipedia/'.$name);
}

test('parser is configured for polizeiruf 110', function () {
    $parser = new PolizeirufParser;

    expect($parser->seriesSlug())->toBe(Series::POLIZEIRUF)
        ->and($parser->pageTitle())->toBe('Liste der Polizeiruf-110-Folgen');
});

describe('real page snapshot', function () {
    beforeEach(fn () => $this->result = (new PolizeirufParser)->parse(polizeirufFixture('polizeiruf-110.wiki')));

    test('reads all episodes without errors', function () {
        $numbered = array_values(array_filter(
            array_map(fn (ParsedEpisode $e) => $e->number, $this->result->episodes),
        ));

        expect($this->result->errors)->toBe([])
            ->and($this->result->skipped)->toBe([])
            ->and($this->result->episodes)->toHaveCount(435)
            ->and($numbered)->toBe(range(1, 428));
    });

    test('films without number keep their position', function () {
        $unnumbered = array_values(array_filter($this->result->episodes, fn (ParsedEpisode $e) => $e->number === null));

        expect(array_map(fn (ParsedEpisode $e) => $e->positionAfter, $unnumbered))->toBe([59, 62, 82, 93, 94, 107, 319])
            ->and($unnumbered[0]->title)->toBe('Gelb ist nicht nur die Farbe der Sonne')
            ->and($unnumbered[0]->wikipediaRef)->toBe('Gelb ist nicht nur die Farbe der Sonne')
            ->and($unnumbered[6]->title)->toBe('Im Alter von …')
            ->and($unnumbered[6]->firstAired->toDateString())->toBe('2011-06-23');
    });

    test('reads the first episode completely', function () {
        $first = $this->result->episodes[0];

        expect($first->number)->toBe(1)
            ->and($first->title)->toBe('Der Fall Lisa Murnau')
            ->and($first->firstAired->toDateString())->toBe('1971-06-27')
            ->and($first->teamRaw)->toBe('Peter Fuchs, Vera Arndt')
            ->and($first->broadcaster)->toBe('DFF')
            ->and($first->wikipediaRef)->toBe('Polizeiruf 110: Der Fall Lisa Murnau');
    });

    test('announced episode is flagged', function () {
        $last = array_slice($this->result->episodes, -1)[0];

        expect($last->number)->toBe(428)
            ->and($last->title)->toBe('Stalker')
            ->and($last->fromAnnouncedSection)->toBeTrue();
    });

    test('double episodes keep their part suffix', function () {
        $titles = array_map(fn (ParsedEpisode $e) => $e->title, $this->result->episodes);

        expect($titles)->toContain('Wendemanöver (1)', 'Wendemanöver (2)');
    });

    test('team values are clean', function () {
        foreach ($this->result->episodes as $episode) {
            expect($episode->teamRaw, (string) $episode->title)->not->toBeNull()
                ->and($episode->teamRaw)->not->toMatch('/\[\[|\]\]|\{\{|<|Gastauftritt|^\|/');
        }
    });
});

describe('edge cases', function () {
    beforeEach(fn () => $this->result = (new PolizeirufParser)->parse(polizeirufFixture('edge-cases-polizeiruf-110.wiki')));

    test('reads the expected rows and ignores other sections', function () {
        expect(array_map(fn (ParsedEpisode $e) => $e->number, $this->result->episodes))->toBe([59, null, 60, 61, 156, 428])
            ->and($this->result->errors)->toBe([]);
    });

    test('unnumbered film is placed after the previous episode', function () {
        $film = $this->result->episodes[1];

        expect($film->positionAfter)->toBe(59)
            ->and($film->broadcaster)->toBe('Fernsehen der DDR')
            ->and($film->firstAired->toDateString())->toBe('1979-06-24')
            ->and($film->teamRaw)->toBe('Woltersdorf');
    });

    test('team spellings are normalized', function () {
        expect($this->result->episodes[2]->teamRaw)->toBe('Peter Fuchs, Arndt')
            ->and($this->result->episodes[3]->teamRaw)->toBe('Herz, Krause')
            ->and($this->result->episodes[4]->teamRaw)->toBe('Ross und Rogov');
    });

    test('titles keep quotes and part suffix', function () {
        expect($this->result->episodes[2]->title)->toBe('Schwere Jahre (1)')
            ->and($this->result->episodes[2]->wikipediaRef)->toBe('Polizeiruf 110: Schwere Jahre (1. Teil)')
            ->and($this->result->episodes[4]->title)->toBe('"In Erinnerung an …"');
    });

    test('commented template row in announced table is ignored', function () {
        expect(array_slice($this->result->episodes, -1)[0]->title)->toBe('Stalker');
    });
});
