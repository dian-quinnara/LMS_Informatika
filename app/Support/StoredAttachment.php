<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Lampiran di penyimpanan privat (disk local): simpan dan tampilkan tanpa storage:link.
 */
class StoredAttachment
{
    /**
     * @return array{attachment_path: string, attachment_name: string, attachment_mime: string|null}
     */
    public static function store(UploadedFile $file, string $directory): array
    {
        return [
            'attachment_path' => $file->store($directory, 'local'),
            'attachment_name' => mb_substr(basename($file->getClientOriginalName()), 0, 200),
            'attachment_mime' => $file->getMimeType(),
        ];
    }

    /**
     * Gambar, video, dan PDF ditampilkan langsung; dokumen lain diunduh.
     */
    public static function stream(?string $path, ?string $name, ?string $mime): StreamedResponse
    {
        abort_if($path === null || ! Storage::disk('local')->exists($path), 404);

        $inline = str_starts_with((string) $mime, 'image/')
            || str_starts_with((string) $mime, 'video/')
            || $mime === 'application/pdf';

        return Storage::disk('local')->response(
            $path,
            $name,
            ['Content-Type' => $mime ?? 'application/octet-stream', 'X-Content-Type-Options' => 'nosniff'],
            $inline ? 'inline' : 'attachment',
        );
    }

    public static function isImage(?string $mime): bool
    {
        return str_starts_with((string) $mime, 'image/');
    }
}
