<?php

use App\Models\Series;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SeriesSeeder;

test('series seeder creates both series', function () {
    $this->seed(SeriesSeeder::class);

    expect(Series::orderBy('slug')->pluck('name', 'slug')->all())->toBe([
        'polizeiruf-110' => 'Polizeiruf 110',
        'tatort' => 'Tatort',
    ]);
});

test('series seeder is idempotent', function () {
    $this->seed(SeriesSeeder::class);
    $this->seed(SeriesSeeder::class);

    expect(Series::count())->toBe(2);
});

test('database seeder creates no users', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::count())->toBe(0)
        ->and(Series::count())->toBe(2);
});
