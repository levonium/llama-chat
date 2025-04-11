<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class ChatUploadManager
{
    private const UPLOAD_DIR         = 'uploads';
    private const MAX_FILE_SIZE      = 1024 * 1024 * 10; // 10MB
    private const ALLOWED_MIME_TYPES = [
        'text/plain',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.oasis.opendocument.text', // LibreOffice Writer
        'application/vnd.oasis.opendocument.spreadsheet', // LibreOffice Calc
        'text/markdown',
        'text/csv',
        'application/json',
    ];

    private string $chatId;
    private string $chatPath;

    public function __construct(string $chatId)
    {
        $this->chatId   = $chatId;
        $this->chatPath = self::UPLOAD_DIR.'/'.$chatId;

        if (!Storage::disk('local')->exists(self::UPLOAD_DIR)) {
            Storage::disk('local')->makeDirectory(self::UPLOAD_DIR);
        }

        if (!Storage::disk('local')->exists($this->chatPath)) {
            Storage::disk('local')->makeDirectory($this->chatPath);
        }
    }

    public function upload(UploadedFile $file): array
    {
        if (!$this->isValidFile($file)) {
            throw new \InvalidArgumentException('Invalid file type or size');
        }

        $fileName = $file->getClientOriginalName();

        $file->storeAs($this->chatPath, $fileName, 'local');

        return [
            'name' => $fileName,
            'path' => $this->chatPath.'/'.$fileName,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_at' => now()->timestamp
        ];
    }

    public function getFileContent(string $fileName): string
    {
        $path = $this->chatPath.'/'.$fileName;

        if (!Storage::disk('local')->exists($path)) {
            throw new \RuntimeException('File not found');
        }

        return Storage::disk('local')->get($path);
    }

    public function getUploads(): array
    {
        if (!Storage::disk('local')->exists($this->chatPath)) {
            return [];
        }

        return collect(Storage::disk('local')->files($this->chatPath))
            ->map(function ($file) {
                return [
                    'name' => basename($file),
                    'path' => $file,
                    'size' => Storage::disk('local')->size($file),
                    'last_modified' => Storage::disk('local')->lastModified($file)
                ];
            })
            ->values()
            ->toArray();
    }

    private function isValidFile(UploadedFile $file): bool
    {
        return $file->isValid() &&
               $file->getSize() <= self::MAX_FILE_SIZE &&
               in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES);
    }
}
