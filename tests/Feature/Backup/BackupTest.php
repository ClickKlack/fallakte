<?php

use App\Backup\BackupExporter;
use App\Models\Episode;
use App\Models\ImportRun;
use App\Models\Series;
use App\Models\Team;
use App\Models\TeamAlias;
use App\Models\User;
use Database\Seeders\SeriesSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    Carbon::setTestNow('2026-11-07 10:00');
    $this->seed(SeriesSeeder::class);
    $tatort = Series::where('slug', Series::TATORT)->first();
    $this->team = Team::factory()->for($tatort)->create(['name' => 'Thiel und Boerne', 'city' => 'Münster']);
    TeamAlias::create(['team_id' => $this->team->id, 'raw_value' => 'Thiel und Boerne']);
    Episode::factory()->for($tatort)->for($this->team)->seen('2025-01-02')->create([
        'number' => 1, 'title' => 'Mike & Nisha', 'first_aired' => '1970-11-29', 'broadcaster' => 'NDR', 'manual_fields' => ['title'],
    ]);
    Episode::factory()->for($tatort)->announced()->create(['number' => 2, 'title' => 'Bald']);
    ImportRun::factory()->for($tatort)->create(['errors' => null]);

    $this->user = User::factory()->create(['email' => 'ami@example.org']);
    $this->actingAs($this->user);
});

afterEach(fn () => Carbon::setTestNow());

test('json backup contains all data but no logins', function () {
    $response = $this->get(route('backup.download', 'json'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/json; charset=utf-8')
        ->assertHeader('Content-Disposition', 'attachment; filename="fallakte-backup-2026-11-07.json"');

    $data = json_decode($response->getContent(), true);

    expect($data['app'])->toBe('Amis Fallakte')
        ->and($data['counts'])->toBe(['series' => 2, 'teams' => 1, 'episodes' => 2, 'import_runs' => 1])
        ->and($data['teams'][0])->toMatchArray(['name' => 'Thiel und Boerne', 'city' => 'Münster', 'aliases' => ['Thiel und Boerne'], 'series' => 'tatort'])
        ->and($data['episodes'][0])->toMatchArray([
            'number' => 1, 'title' => 'Mike & Nisha', 'first_aired' => '1970-11-29', 'broadcaster' => 'NDR',
            'team_id' => $this->team->id, 'seen' => true, 'seen_at' => '2025-01-02', 'manual_fields' => ['title'], 'status' => 'aired',
        ])
        ->and($data['episodes'][1]['status'])->toBe('announced')
        ->and($data)->not->toHaveKey('users')
        ->and($response->getContent())->not->toContain('ami@example.org')
        ->and($response->getContent())->not->toContain($this->user->password);
});

test('excel backup has sheets for episodes, teams and imports', function () {
    $response = $this->get(route('backup.download', 'xlsx'))->assertOk();
    $path = $response->baseResponse->getFile()->getPathname();

    expect($response->headers->get('Content-Disposition'))->toContain('fallakte-backup-2026-11-07.xlsx');

    $zip = new ZipArchive;
    expect($zip->open($path))->toBeTrue();
    $workbook = $zip->getFromName('xl/workbook.xml');
    $episodes = $zip->getFromName('xl/worksheets/sheet1.xml');
    $teams = $zip->getFromName('xl/worksheets/sheet2.xml');
    $zip->close();
    unlink($path);

    expect($workbook)->toContain('name="Folgen"', 'name="Teams"', 'name="Importe"')
        ->and(substr_count($episodes, '<row '))->toBe(3)
        ->and($episodes)->toContain('Mike &amp; Nisha', 'Thiel und Boerne', 'ausgestrahlt', 'angekündigt', 'Titel</t>')
        ->and($episodes)->toContain('s="2"><v>25901</v>') // 29.11.1970 als echtes Datum
        ->and($episodes)->toContain('<c r="B2"><v>1</v></c>') // Nummer als Zahl
        ->and($teams)->toContain('Münster')
        ->and($episodes.$teams)->not->toContain('ami@example.org');
});

test('sqlite backup is a complete copy without sessions', function () {
    // VACUUM INTO ist innerhalb einer Transaktion nicht erlaubt: die Testtransaktion verlassen,
    // Daten dauerhaft anlegen und am Ende wieder aufräumen (Ausgangszustand für die übrigen Tests).
    DB::rollBack();
    $tables = ['sessions', 'import_runs', 'episodes', 'team_aliases', 'teams', 'series', 'users'];

    try {
        $this->seed(SeriesSeeder::class);
        $tatort = Series::where('slug', Series::TATORT)->first();
        Team::factory()->for($tatort)->create();
        Episode::factory()->for($tatort)->count(2)->sequence(['title' => 'Mike & Nisha', 'number' => 1], ['title' => 'Bald', 'number' => 2])->create();
        $this->actingAs(User::factory()->create());
        DB::table('sessions')->insert(['id' => 'abc', 'payload' => 'x', 'last_activity' => time()]);

        sqliteBackupAssertions($this);
    } finally {
        foreach ($tables as $table) {
            DB::table($table)->delete();
        }
        DB::beginTransaction();
    }
});

function sqliteBackupAssertions($test): void
{
    $response = $test->get(route('backup.download', 'sqlite'))->assertOk();
    $path = $response->baseResponse->getFile()->getPathname();

    expect($response->headers->get('Content-Disposition'))->toContain('fallakte-backup-2026-11-07.sqlite');

    $copy = new PDO('sqlite:'.$path);
    expect((int) $copy->query('SELECT COUNT(*) FROM episodes')->fetchColumn())->toBe(2)
        ->and((int) $copy->query('SELECT COUNT(*) FROM teams')->fetchColumn())->toBe(1)
        ->and((int) $copy->query('SELECT COUNT(*) FROM users')->fetchColumn())->toBe(1)
        ->and((int) $copy->query('SELECT COUNT(*) FROM sessions')->fetchColumn())->toBe(0)
        ->and($copy->query('SELECT title FROM episodes WHERE number = 1')->fetchColumn())->toBe('Mike & Nisha');
    $copy = null;
    unlink($path);

    expect((int) DB::table('sessions')->count())->toBe(1); // Original bleibt unverändert
}

test('sqlite backup refuses to run inside an open transaction', function () {
    expect(fn () => app(BackupExporter::class)->sqlite())->toThrow(RuntimeException::class, 'Transaktion');
});

test('temporary backup files are removed after sending', function () {
    $response = $this->get(route('backup.download', 'xlsx'));
    $path = $response->baseResponse->getFile()->getPathname();

    $response->baseResponse->sendContent();

    expect(file_exists($path))->toBeFalse();
});

test('leftovers of aborted downloads older than an hour are removed', function () {
    $directory = storage_path('app/private');
    @mkdir($directory, 0775, true);
    file_put_contents($old = $directory.'/backup-alt.xlsx', 'x');
    file_put_contents($fresh = $directory.'/backup-neu.xlsx', 'x');
    touch($old, time() - 7200);

    $response = $this->get(route('backup.download', 'xlsx'));
    @unlink($response->baseResponse->getFile()->getPathname());

    expect(file_exists($old))->toBeFalse()
        ->and(file_exists($fresh))->toBeTrue();
    unlink($fresh);
});

test('about page offers the backup block', function () {
    $this->get(route('about'))->assertInertia(fn ($page) => $page->where('sqliteBackup', true));
});

test('unknown formats are not found', function () {
    $this->get('/backup/csv')->assertNotFound();
});

test('backups require login', function () {
    auth()->logout();

    foreach (['json', 'xlsx', 'sqlite'] as $format) {
        $this->get(route('backup.download', $format))->assertRedirect(route('login'));
    }
});
