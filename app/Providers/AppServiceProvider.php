<?php

namespace App\Providers;

use App\Import\ParserRegistry;
use App\Import\Wikipedia\Parsers\PolizeirufParser;
use App\Import\Wikipedia\Parsers\TatortParser;
use App\Import\Wikipedia\WikipediaClient;
use App\Models\User;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Registriert Anwendungsdienste.
     */
    public function register(): void
    {
        $this->app->singleton(ParserRegistry::class, fn () => new ParserRegistry([
            new TatortParser,
            new PolizeirufParser,
        ]));

        $this->app->bind(WikipediaClient::class, fn ($app) => new WikipediaClient(
            $app->make(HttpFactory::class),
            config('services.wikipedia.endpoint'),
            self::wikipediaUserAgent(),
        ));
    }

    /**
     * User-Agent für Wikipedia: WIKIPEDIA_USER_AGENT aus der .env, sonst die Basiskennung
     * mit der E-Mail-Adresse des ersten Benutzers als Kontakt.
     */
    public static function wikipediaUserAgent(): string
    {
        $configured = config('services.wikipedia.user_agent');
        if (is_string($configured) && trim($configured) !== '') {
            return $configured;
        }

        $base = config('services.wikipedia.user_agent_base');
        $email = User::query()->orderBy('id')->value('email');

        return $email ? str_replace(')', "; {$email})", $base) : $base;
    }

    /**
     * Initialisiert Anwendungsdienste.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
