<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use App\Models\Document;
use App\Jobs\IndexDocumentJob;
use Illuminate\Support\Str;

class DocumentUpload extends Component
{
    use WithFileUploads;

    public ?TemporaryUploadedFile $file = null;

    public bool $isUploading = false;
    public string $uploadStatus = '';
    public array $uploadedDocuments = [];

    protected $listeners = [
        'documents-updated' => 'loadDocuments',
    ];

    public function mount(): void
    {
        $this->loadDocuments();
    }

    public function loadDocuments(): void
    {
      

        $this->uploadedDocuments = Document::query()
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($doc) => [
                'id' => $doc->id,
                'filename' => $doc->filename,
                'original_filename' => $doc->original_filename,
                'created_at' => optional($doc->created_at)->format('M d, Y H:i'),
            ])
            ->toArray();
    }

    public function updatedFile(): void
    {
        if (! $this->file) return;

        $this->validate([
            'file' => 'file|max:10240|mimes:txt,pdf,doc,docx,md',
        ]);

        $this->uploadStatus = 'File ready to upload ✅';
    }

    public function uploadDocument(): void
    {
        if (! $this->file) {
            $this->uploadStatus = 'Please select a file.';
            return;
        }

        $this->isUploading = true;
        $this->uploadStatus = 'Uploading...';

        try {
            $this->validate([
                'file' => 'required|file|max:10240|mimes:txt,pdf,doc,docx,md',
            ]);

            $originalName = $this->file->getClientOriginalName();

            $safeName = now()->timestamp . '_' . preg_replace(
                '/[^A-Za-z0-9_\-\.]/',
                '_',
                $originalName
            );

            // ✅ FIX: get size safely (Livewire tmp bug workaround)
            $realPath = $this->file->getRealPath();
            $size = ($realPath && file_exists($realPath))
                ? filesize($realPath)
                : 0;

            $mimeType = $this->file->getMimeType();

            $storedName = Str::uuid() . '.' . $this->file->extension();


            $path = $this->file->storeAs('documents', $safeName, 'local');

            $document = Document::create([
                'filename'  => $storedName,
                'original_filename' => $originalName,
                'disk'      => 'local',   
                'path'      => $path,
                'size'      => $size,
                'mime_type' => $mimeType,
                'source'    => 'livewire_upload_' . uniqid(),
                'status'    => 'uploaded',
            ]);

            // ✅ Dispatch job correctly (matches constructor)
            IndexDocumentJob::dispatch($document->id);

            $this->uploadStatus = "✅ '{$originalName}' uploaded successfully!";

            $this->reset('file');

            // ✅ reload data
            $this->loadDocuments();

            // ✅ trigger UI refresh (important in v4)
            $this->dispatch('documents-updated');
            $this->dispatch('$refresh');

        } catch (\Throwable $e) {
            logger()->error('Upload Error: ' . $e->getMessage());

            $this->uploadStatus = '❌ Error: ' . $e->getMessage();
        } finally {
            $this->isUploading = false;
        }
    }

    public function render()
    {
        return view('livewire.document-upload');
    }
}