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
        $zip = new \ZipArchive();

        if ($zip->open($path) !== true) {
            throw new \RuntimeException("Unable to open DOCX file: {$path}");
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if (!$xml) {
            throw new \RuntimeException("Invalid DOCX: missing document.xml");
        }

        // Better XML cleanup (less noisy than strip_tags)
        $text = preg_replace('/<[^>]+>/', ' ', $xml);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');

        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
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
