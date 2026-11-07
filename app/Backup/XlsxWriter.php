<?php

namespace App\Backup;

use DateTimeInterface;
use RuntimeException;
use ZipArchive;

/**
 * Schreibt einfache Excel-Dateien (.xlsx, Office Open XML) ohne externe Bibliothek:
 * mehrere Blätter, fette Kopfzeile, fixierte erste Zeile, Autofilter, echte Datumswerte.
 * Werte: string, int, float, bool, null oder DateTimeInterface (wird als Datum formatiert).
 */
final class XlsxWriter
{
    /** @var list<array{name: string, headers: list<string>, rows: iterable<list<mixed>>, widths: list<int>}> */
    private array $sheets = [];

    /**
     * @param  list<string>  $headers
     * @param  iterable<list<mixed>>  $rows
     * @param  list<int>  $widths  Spaltenbreiten in Zeichen (optional)
     */
    public function addSheet(string $name, array $headers, iterable $rows, array $widths = []): self
    {
        $this->sheets[] = ['name' => self::sheetName($name), 'headers' => $headers, 'rows' => $rows, 'widths' => $widths];

        return $this;
    }

    public function save(string $path): void
    {
        if ($this->sheets === []) {
            throw new RuntimeException('Mindestens ein Blatt ist erforderlich.');
        }

        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Excel-Datei konnte nicht angelegt werden: {$path}");
        }

        $count = count($this->sheets);
        $zip->addFromString('[Content_Types].xml', $this->contentTypes($count));
        $zip->addFromString('_rels/.rels', $this->rootRels());
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels($count));
        $zip->addFromString('xl/styles.xml', $this->styles());

        foreach ($this->sheets as $index => $sheet) {
            $zip->addFromString('xl/worksheets/sheet'.($index + 1).'.xml', $this->worksheet($sheet));
        }

        $zip->close();
    }

    /** Blattnamen: höchstens 31 Zeichen, ohne die in Excel verbotenen Zeichen. */
    private static function sheetName(string $name): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', str_replace(['\\', '/', '?', '*', '[', ']', ':'], ' ', $name)) ?? '');

        return mb_substr($name === '' ? 'Blatt' : $name, 0, 31);
    }

    /** XML-sicherer Text: Steuerzeichen entfernen, Sonderzeichen maskieren. */
    private static function escape(string $text): string
    {
        $text = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $text) ?? '';

        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /** Spaltenbuchstaben: 0 → A, 25 → Z, 26 → AA … */
    public static function column(int $index): string
    {
        $name = '';
        for ($n = $index + 1; $n > 0; $n = intdiv($n - 1, 26)) {
            $name = chr(65 + ($n - 1) % 26).$name;
        }

        return $name;
    }

    /** Excel-Datum: Tage seit dem 30.12.1899 (mit Tagesbruchteil für Uhrzeiten). */
    public static function excelDate(DateTimeInterface $date): float
    {
        $days = (int) floor(($date->getTimestamp() + $date->getOffset()) / 86400) + 25569;
        $seconds = ((int) $date->format('H')) * 3600 + ((int) $date->format('i')) * 60 + (int) $date->format('s');

        return $days + $seconds / 86400;
    }

    private function cell(mixed $value, string $ref, bool $header = false): string
    {
        $style = $header ? ' s="1"' : '';

        return match (true) {
            $value === null || $value === '' => '',
            $value instanceof DateTimeInterface => sprintf(
                '<c r="%s" s="%d"><v>%s</v></c>',
                $ref,
                $value->format('H:i:s') === '00:00:00' ? 2 : 3,
                rtrim(rtrim(number_format(self::excelDate($value), 6, '.', ''), '0'), '.'),
            ),
            is_bool($value) => sprintf('<c r="%s" t="b"%s><v>%d</v></c>', $ref, $style, $value ? 1 : 0),
            is_int($value), is_float($value) => sprintf('<c r="%s"%s><v>%s</v></c>', $ref, $style, $value),
            default => sprintf('<c r="%s" t="inlineStr"%s><is><t xml:space="preserve">%s</t></is></c>', $ref, $style, self::escape((string) $value)),
        };
    }

    /**
     * @param  array{name: string, headers: list<string>, rows: iterable<list<mixed>>, widths: list<int>}  $sheet
     */
    private function worksheet(array $sheet): string
    {
        $rows = [];
        $line = 1;

        $cells = '';
        foreach ($sheet['headers'] as $col => $header) {
            $cells .= $this->cell($header, self::column($col).$line, true);
        }
        $rows[] = "<row r=\"{$line}\">{$cells}</row>";

        foreach ($sheet['rows'] as $row) {
            $line++;
            $cells = '';
            foreach (array_values($row) as $col => $value) {
                $cells .= $this->cell($value, self::column($col).$line);
            }
            $rows[] = "<row r=\"{$line}\">{$cells}</row>";
        }

        $lastColumn = self::column(max(0, count($sheet['headers']) - 1));
        $cols = '';
        foreach ($sheet['widths'] as $col => $width) {
            $n = $col + 1;
            $cols .= "<col min=\"{$n}\" max=\"{$n}\" width=\"{$width}\" customWidth=\"1\"/>";
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .($cols !== '' ? "<cols>{$cols}</cols>" : '')
            .'<sheetData>'.implode('', $rows).'</sheetData>'
            ."<autoFilter ref=\"A1:{$lastColumn}{$line}\"/>"
            .'</worksheet>';
    }

    private function contentTypes(int $count): string
    {
        $sheets = '';
        for ($i = 1; $i <= $count; $i++) {
            $sheets .= "<Override PartName=\"/xl/worksheets/sheet{$i}.xml\" ContentType=\"application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml\"/>";
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .$sheets
            .'</Types>';
    }

    private function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function workbook(): string
    {
        $sheets = '';
        foreach ($this->sheets as $index => $sheet) {
            $id = $index + 1;
            $sheets .= '<sheet name="'.self::escape($sheet['name'])."\" sheetId=\"{$id}\" r:id=\"rId{$id}\"/>";
        }

        $names = '';
        foreach ($this->sheets as $index => $sheet) {
            // Benannter Bereich für den Autofilter (von Excel erwartet)
            $last = self::column(max(0, count($sheet['headers']) - 1));
            $names .= "<definedName name=\"_xlnm._FilterDatabase\" localSheetId=\"{$index}\" hidden=\"1\">'"
                .str_replace("'", "''", self::escape($sheet['name']))."'!\$A\$1:\${$last}\$1</definedName>";
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            ."<sheets>{$sheets}</sheets>"
            ."<definedNames>{$names}</definedNames>"
            .'</workbook>';
    }

    private function workbookRels(int $count): string
    {
        $rels = '';
        for ($i = 1; $i <= $count; $i++) {
            $rels .= "<Relationship Id=\"rId{$i}\" Type=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet\" Target=\"worksheets/sheet{$i}.xml\"/>";
        }
        $styles = $count + 1;
        $rels .= "<Relationship Id=\"rId{$styles}\" Type=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles\" Target=\"styles.xml\"/>";

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.$rels.'</Relationships>';
    }

    /** Stile: 0 Standard, 1 fett (Kopfzeile), 2 Datum TT.MM.JJJJ, 3 Datum mit Uhrzeit. */
    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="2"><numFmt numFmtId="164" formatCode="dd\.mm\.yyyy"/><numFmt numFmtId="165" formatCode="dd\.mm\.yyyy\ hh:mm"/></numFmts>'
            .'<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            .'<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="4">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            .'<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            .'<xf numFmtId="165" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            .'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }
}
