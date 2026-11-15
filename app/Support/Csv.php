<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV-Export für Excel (Semikolon, UTF-8 mit BOM).
 */
class Csv
{
    /** @param Collection<int, array<string, mixed>> $rows */
    public static function download(Collection $rows, string $filename, ?array $headers = null): StreamedResponse
    {
        $headers ??= array_keys($rows->first() ?? []);

        return response()->streamDownload(function () use ($rows, $headers) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers, ';');
            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($key) => self::safe($row[$key] ?? ''), $headers), ';');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Verhindert, dass Excel Eingaben als Formel ausführt. */
    public static function safe(mixed $value): string
    {
        $value = (string) ($value ?? '');

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
