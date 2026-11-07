<?php

use App\Backup\XlsxWriter;
use Illuminate\Support\Carbon;

test('column letters', function (int $index, string $expected) {
    expect(XlsxWriter::column($index))->toBe($expected);
})->with([[0, 'A'], [25, 'Z'], [26, 'AA'], [27, 'AB'], [701, 'ZZ'], [702, 'AAA']]);

test('excel date serial numbers', function () {
    expect(XlsxWriter::excelDate(Carbon::parse('1970-11-29', 'UTC')))->toBe(25901.0)
        ->and(XlsxWriter::excelDate(Carbon::parse('2026-11-07 12:00', 'UTC')))->toBe(46333.5);
});

test('writes a valid workbook with escaped text, dates, numbers and booleans', function () {
    $path = tempnam(sys_get_temp_dir(), 'xlsx');

    (new XlsxWriter)
        ->addSheet('Folgen: [Tatort]', ['Titel', 'Datum', 'Nr.', 'Gesehen'], [
            ['Mike & Nisha <test> "x"'."\x07", Carbon::parse('1970-11-29'), 1342, true],
            [null, null, 2.5, false],
        ])
        ->addSheet('Zweites Blatt mit einem sehr langen Namen über 31 Zeichen', ['A'], [])
        ->save($path);

    $zip = new ZipArchive;
    expect($zip->open($path))->toBeTrue();

    foreach (['[Content_Types].xml', '_rels/.rels', 'xl/workbook.xml', 'xl/_rels/workbook.xml.rels', 'xl/styles.xml', 'xl/worksheets/sheet1.xml', 'xl/worksheets/sheet2.xml'] as $part) {
        $xml = $zip->getFromName($part);
        expect($xml)->not->toBeFalse("{$part} fehlt")
            ->and((new DOMDocument)->loadXML($xml))->toBeTrue("{$part} ist kein gültiges XML");
    }

    $workbook = $zip->getFromName('xl/workbook.xml');
    $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    unlink($path);

    expect($workbook)->toContain('name="Folgen Tatort"')
        ->and($workbook)->toContain('name="Zweites Blatt mit einem sehr la"')
        ->and($sheet)->toContain('Mike &amp; Nisha &lt;test&gt; &quot;x&quot;</t>')
        ->and($sheet)->not->toContain("\x07")
        ->and($sheet)->toContain('<c r="B2" s="2"><v>25901</v></c>')
        ->and($sheet)->toContain('<c r="C2"><v>1342</v></c>')
        ->and($sheet)->toContain('<c r="D2" t="b"><v>1</v></c>')
        ->and($sheet)->toContain('<c r="C3"><v>2.5</v></c>')
        ->and($sheet)->toContain('<autoFilter ref="A1:D3"/>');
});
