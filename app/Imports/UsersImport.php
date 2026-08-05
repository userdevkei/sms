<?php

namespace App\Imports;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;

class UsersImport
{
    /**
     * Reads the uploaded file and returns rows as an associative Collection,
     * keyed by normalized header names (matches WithHeadingRow's behavior).
     */
    public function toCollection(UploadedFile $file): Collection
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);

        if (empty($rows)) {
            return collect();
        }

        $headers = array_map(function ($h) {
            return \Illuminate\Support\Str::of((string) $h)
                ->trim()
                ->lower()
                ->snake()
                ->replace(' ', '_')
                ->value();
        }, array_shift($rows));

        $data = collect($rows)->map(function ($row) use ($headers) {
            $row = array_pad($row, count($headers), null);
            return array_combine($headers, $row);
        })->filter(function ($row) {
            // Skip fully blank rows (common at the end of exported sheets)
            return collect($row)->filter(fn ($v) => trim((string) $v) !== '')->isNotEmpty();
        })->values();

        return $data;
    }
}
