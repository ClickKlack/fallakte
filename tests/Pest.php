<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Hilfsfunktionen
|--------------------------------------------------------------------------
*/

/**
 * Simuliert die MediaWiki-API: Seitentitel => Wikitext (oder HTTP-Status als int).
 *
 * @param  array<string, string|int>  $pages
 */
function fakeWikipedia(array $pages): void
{
    // Http::fake() hängt Regeln nur an (die erste passende gewinnt) – daher gemeinsamer Speicher,
    // damit ein späterer Aufruf im selben Test die Seiten ersetzt.
    $GLOBALS['fakeWikipediaPages'] = $pages;

    Http::fake([
        'de.wikipedia.org/w/api.php*' => function (Request $request) {
            $title = $request->data()['titles'] ?? '';
            $page = $GLOBALS['fakeWikipediaPages'][$title] ?? null;

            if (is_int($page)) {
                return Http::response('Fehler', $page);
            }

            return Http::response(['query' => ['pages' => [
                $page === null
                    ? ['title' => $title, 'missing' => true]
                    : ['title' => $title, 'revisions' => [['revid' => 123, 'slots' => ['main' => ['content' => $page]]]]],
            ]]]);
        },
    ]);
}

/** Liest eine Wikitext-Fixture. */
function wikiFixture(string $name): string
{
    return file_get_contents(__DIR__.'/Fixtures/wikipedia/'.$name);
}
