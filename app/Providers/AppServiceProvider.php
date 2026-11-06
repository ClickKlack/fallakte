<?php

namespace App\Providers;

use App\Import\ParserRegistry;
use App\Import\Wikipedia\Parsers\PolizeirufParser;
use App\Import\Wikipedia\Parsers\TatortParser;
use App\Import\Wikipedia\WikipediaClient;
use App\Models\User;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Pdo\Sqlite;

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

        Event::listen(ConnectionEstablished::class, function (ConnectionEstablished $event) {
            if ($event->connection->getDriverName() === 'sqlite') {
                self::registerSqliteFunctions($event->connection->getPdo());
            }
        });
    }

    /**
     * SQLite kennt Groß-/Kleinschreibung nur für ASCII. unicode_lower() wandelt auch Umlaute um,
     * damit die Titelsuche „über“ und „Über“ gleich behandelt (MariaDB kann das von Haus aus).
     */
    public static function registerSqliteFunctions(\PDO $pdo): void
    {
        $lower = fn (?string $value) => $value === null ? null : mb_strtolower($value);

        if ($pdo instanceof Sqlite) {
            $pdo->createFunction('unicode_lower', $lower, 1, \PDO::SQLITE_DETERMINISTIC);
        } else {
            // PHP < 8.4
            $pdo->sqliteCreateFunction('unicode_lower', $lower, 1, \PDO::SQLITE_DETERMINISTIC);
        }
    }
}
