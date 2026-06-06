<?php
namespace App\Services;

use Illuminate\Support\Facades\Storage;

class DocumentParser
{
    public function parse(
        string $disk,
        string $path,
        string $mimeType
    ): string {

        if (!Storage::disk($disk)->exists($path)) {
            throw new \RuntimeException("File not found: {$path}");
        }
        $fullPath = Storage::disk($disk)->path($path);

        return match ($mimeType) {
            'text/plain',
            'text/markdown'
            =>  $this->parseText($fullPath),

            'application/pdf'
            => $this->parsePdf($fullPath),

            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            => $this->parseDocx($fullPath),

            default
            => throw new \RuntimeException(
                "Unsupported file type: {$mimeType}"
            ),
        };
    }

    protected function parsePdf(string $path): string
    {
        $parser = new \Smalot\PdfParser\Parser();

        return $parser
            ->parseFile($path)
            ->getText();
    }

    protected function parseDocx(string $path): string
    {
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($path);

        $content = '';

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $content .= $element->getText() . PHP_EOL;
                }
            }
        }

        return $content;
    }

    protected function parseText(string $path): string
    {
        $content = file_get_contents($path);

        if ($content === false) {
            throw new \RuntimeException("Unable to read text file: {$path}");
        }

        // Normalize encoding (VERY important for RAG quality)
        $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');

        // Normalize line breaks
        $content = preg_replace("/\r\n|\r/", "\n", $content);

        // Remove null bytes (can break embeddings)
        $content = str_replace("\0", '', $content);

        return trim($content);
    }
}
