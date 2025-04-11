<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Http\UploadedFile;

class ChatUploadManager
{
    private const UPLOAD_DIR         = 'uploads';
    private const MAX_FILE_SIZE      = 1024 * 1024 * 10; // 10MB
    private const ALLOWED_MIME_TYPES = [
        'text/plain',
        'text/markdown',
        'text/csv',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.oasis.opendocument.text',
        'text/html',
        'application/json',
        'text/x-php',
        'text/javascript',
        'text/x-python',
        'text/x-java',
        'text/x-c++',
        'text/x-c',
        'application/x-empty'
    ];

    private string $chatId;
    private string $chatPath;
    private FileProcessor $fileProcessor;

    public function __construct(string $chatId)
    {
        $this->chatId        = $chatId;
        $this->chatPath      = self::UPLOAD_DIR.'/'.$chatId;
        $this->fileProcessor = new FileProcessor();

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
        $filePath = $this->chatPath.'/'.$fileName;

        $file->storeAs($this->chatPath, $fileName, 'local');

        try {
            $processedContent = $this->fileProcessor->processFile($filePath, $file->getMimeType());

            return [
                'name' => $fileName,
                'path' => $filePath,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'uploaded_at' => now()->timestamp,
                'processed_content' => $processedContent
            ];
        } catch (\Exception $e) {
            // Delete the file if processing fails
            Storage::disk('local')->delete($filePath);
            throw new \RuntimeException('Failed to process file: '.$e->getMessage());
        }
    }

    public function getFileContent(string $fileName): array
    {
        $path = $this->chatPath.'/'.$fileName;

        if (!Storage::disk('local')->exists($path)) {
            throw new \RuntimeException('File not found');
        }

        $fullPath = Storage::disk('local')->path($path);
        $mimeType = File::mimeType($fullPath);
        return $this->fileProcessor->processFile($path, $mimeType);
    }

    public function getUploads(): array
    {
        if (!Storage::disk('local')->exists($this->chatPath)) {
            return [];
        }

        return collect(Storage::disk('local')->files($this->chatPath))
            ->map(function ($file) {
                $fullPath = Storage::disk('local')->path($file);
                $mimeType = File::mimeType($fullPath);
                return [
                    'name' => basename($file),
                    'path' => $file,
                    'size' => Storage::disk('local')->size($file),
                    'mime_type' => $mimeType,
                    'last_modified' => Storage::disk('local')->lastModified($file),
                    'processed_content' => $this->fileProcessor->processFile($file, $mimeType)
                ];
            })
            ->values()
            ->toArray();
    }

    public function deleteFile(string $fileName): bool
    {
        $path = $this->chatPath.'/'.$fileName;

        if (!Storage::disk('local')->exists($path)) {
            return false;
        }

        return Storage::disk('local')->delete($path);
    }

    private function isValidFile(UploadedFile $file): bool
    {
        return $file->isValid() &&
               $file->getSize() <= self::MAX_FILE_SIZE &&
               in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES);
    }
}
