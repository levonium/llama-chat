<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;

class FileProcessor
{
    private PdfParser $pdfParser;
    private array $supportedMimeTypes = [
        'text/plain' => 'processText',
        'text/markdown' => 'processText',
        'text/csv' => 'processCsv',
        'application/pdf' => 'processPdf',
        'application/msword' => 'processWord',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'processWord',
        'application/vnd.oasis.opendocument.text' => 'processWord',
        'text/html' => 'processHtml',
        'application/json' => 'processJson',
        'text/x-php' => 'processCode',
        'text/javascript' => 'processCode',
        'text/x-python' => 'processCode',
        'text/x-java' => 'processCode',
        'text/x-c++' => 'processCode',
        'text/x-c' => 'processCode',
        'application/x-empty' => 'processText',
    ];

    public function __construct()
    {
        $this->pdfParser = new PdfParser();
    }

    public function processFile(string $filePath, string $mimeType): array
    {
        if ($mimeType === 'application/x-empty') {
            $mimeType = 'text/plain';
        }

        if (!isset($this->supportedMimeTypes[$mimeType])) {
            throw new \InvalidArgumentException("Unsupported file type: {$mimeType}\n\nSupported types are: ".implode(', ', array_keys($this->supportedMimeTypes)));
        }

        $method  = $this->supportedMimeTypes[$mimeType];
        $content = Storage::disk('local')->get($filePath);

        return [
            'content' => $this->$method($content),
            'type' => $mimeType,
            'size' => Storage::disk('local')->size($filePath),
            'processed_at' => now()->timestamp
        ];
    }

    private function processText(string $content): string
    {
        return $content;
    }

    private function processCsv(string $content): string
    {
        $rows      = str_getcsv($content, "\n");
        $processed = [];

        foreach ($rows as $row) {
            $processed[] = implode(' | ', str_getcsv($row));
        }

        return implode("\n", $processed);
    }

    private function processPdf(string $content): string
    {
        $pdf = $this->pdfParser->parseContent($content);
        return $pdf->getText();
    }

    private function processWord(string $content): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'word_');
        file_put_contents($tempFile, $content);

        try {
            $phpWord = WordIOFactory::load($tempFile);
            $text    = '';

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text .= $element->getText()."\n";
                    } elseif (method_exists($element, 'getElements')) {
                        foreach ($element->getElements() as $subElement) {
                            if (method_exists($subElement, 'getText')) {
                                $text .= $subElement->getText()."\n";
                            }
                        }
                    }
                }
            }

            return trim($text);
        } finally {
            unlink($tempFile);
        }
    }

    private function processHtml(string $content): string
    {
        return strip_tags($content);
    }

    private function processJson(string $content): string
    {
        $data = json_decode($content, true);
        return json_encode($data, JSON_PRETTY_PRINT);
    }

    private function processCode(string $content): string
    {
        // For code files, we'll keep the original content but add syntax highlighting
        return $content;
    }
}
