<?php

namespace App\Services\Common;

use App\Services\Common;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

class FileUploadService
{
    /**
     * Move an uploaded file into base_path({directory}) — the project root,
     * e.g. sms/Files/images — with a generated filename, and return the
     * relative path to store in the database (e.g. "Files/images/xyz.png").
     * Anchored to base_path() rather than a bare relative path, which
     * resolves against PHP's current working directory and varies between
     * artisan serve, a real vhost, queue workers, and scheduled commands.
     */
    public static function store(UploadedFile $file, string $directory = 'Files/images'): string
    {
        $directory = trim($directory, '/');
        $destination = base_path($directory);

        if (! File::isDirectory($destination)) {
            File::makeDirectory($destination, 0755, true);
        }

        $filename = (new Common())->IDgenerator() . '.' . $file->getClientOriginalExtension();

        $file->move($destination, $filename);

        return $directory . '/' . $filename;
    }

    /**
     * Delete a previously stored file given its relative path
     * (e.g. "Files/images/xyz.png"). Silently no-ops for empty paths or
     * the shared default avatar, so it's always safe to call without
     * extra guard checks at the call site.
     */
    public static function delete(?string $path): void
    {
        if (empty($path) || $path === 'Files/images/avatar.png') {
            return;
        }

        $fullPath = base_path($path);

        if (File::exists($fullPath)) {
            File::delete($fullPath);
        }
    }
}
