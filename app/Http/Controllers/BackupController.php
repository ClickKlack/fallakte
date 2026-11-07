<?php

namespace App\Http\Controllers;

use App\Backup\BackupExporter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Backup-Downloads: SQLite (komplett), JSON und Excel (ohne Logins).
 * Temporäre Dateien werden nach dem Senden gelöscht.
 */
class BackupController extends Controller
{
    public function __invoke(string $format, BackupExporter $exporter): Response
    {
        return match ($format) {
            'sqlite' => $this->file($exporter->sqliteAvailable() ? $exporter->sqlite() : abort(404), $exporter->filename('sqlite'), 'application/vnd.sqlite3'),
            'json' => response($exporter->json(), 200, [
                'Content-Type' => 'application/json; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="'.$exporter->filename('json').'"',
                'Cache-Control' => 'no-store',
            ]),
            'xlsx' => $this->file($exporter->xlsx(), $exporter->filename('xlsx'), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            default => abort(404),
        };
    }

    private function file(string $path, string $name, string $type): BinaryFileResponse
    {
        return response()->download($path, $name, ['Content-Type' => $type, 'Cache-Control' => 'no-store'])
            ->deleteFileAfterSend();
    }
}
