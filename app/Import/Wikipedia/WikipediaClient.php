<?php

namespace App\Import\Wikipedia;

use Illuminate\Http\Client\Factory as HttpFactory;
use RuntimeException;

/**
 * Lädt den Wikitext einer Seite über die MediaWiki-API der deutschsprachigen Wikipedia.
 */
class WikipediaClient
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $endpoint,
        private readonly string $userAgent,
    ) {}

    /**
     * @return array{wikitext: string, revision: int}
     *
     * @throws RuntimeException wenn die Seite nicht geladen werden kann
     */
    public function fetchWikitext(string $pageTitle): array
    {
        $response = $this->http
            ->withUserAgent($this->userAgent)
            ->acceptJson()
            ->timeout(30)
            ->retry(2, 1000, throw: false)
            ->get($this->endpoint, [
                'action' => 'query',
                'prop' => 'revisions',
                'rvprop' => 'content|ids',
                'rvslots' => 'main',
                'titles' => $pageTitle,
                'redirects' => 1,
                'format' => 'json',
                'formatversion' => 2,
            ]);

        if ($response->failed()) {
            throw new RuntimeException("Wikipedia-Abruf für „{$pageTitle}“ fehlgeschlagen (HTTP {$response->status()}).");
        }

        $page = $response->json('query.pages.0');
        $revision = $page['revisions'][0] ?? null;
        $content = $revision['slots']['main']['content'] ?? null;

        if (! is_array($page) || ($page['missing'] ?? false) || ! is_string($content) || $content === '') {
            throw new RuntimeException("Wikipedia-Seite „{$pageTitle}“ nicht gefunden oder leer.");
        }

        return ['wikitext' => $content, 'revision' => (int) ($revision['revid'] ?? 0)];
    }
}
