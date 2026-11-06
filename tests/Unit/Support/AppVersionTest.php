<?php

use App\Support\AppVersion;

test('version is read from the file', function (string $content, string $expected) {
    $file = tempnam(sys_get_temp_dir(), 'version');
    file_put_contents($file, $content);

    expect(AppVersion::fromFile($file))->toBe($expected);

    unlink($file);
})->with([
    'semver' => ["1.2.3\n", '1.2.3'],
    'with spaces' => ['  0.9.0  ', '0.9.0'],
    'invalid' => ['1.2', 'dev'],
    'text' => ['beta', 'dev'],
    'empty' => ['', 'dev'],
]);

test('missing file means dev', function () {
    expect(AppVersion::fromFile('/does/not/exist/VERSION'))->toBe('dev');
});

test('the repository version file is valid', function () {
    expect(AppVersion::fromFile(dirname(__DIR__, 3).'/VERSION'))->toMatch('/^\d+\.\d+\.\d+$/');
});
