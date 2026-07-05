<?php

namespace App\Livewire;

use App\Jobs\IndexDocumentJob;
use App\Models\Document;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class DocumentUpload extends Component
{
    use WithFileUploads;

    public ?TemporaryUploadedFile $file = null;

    public bool $isUploading = false;

    public ?int $activeDocumentId = null;

    public ?string $uploadStartedAt = null;

    public ?string $uploadFinishedAt = null;

    public string $uploadStatus = '';

    public string $uploadStage = 'Ready';

    public int $uploadProgress = 0;

    /**
     * @var array<int, array{label: string, state: string}>
     */
    public array $uploadSteps = [];

    /**
     * @var array<int, array{id: int, filename: string, original_filename: string, created_at: string}>
     */
    public array $uploadedDocuments = [];

    /**
     * @var array<string, string>
     */
    protected $listeners = [
        'documents-updated' => 'loadDocuments',
    ];

    public function mount(): void
    {
        $this->resetUploadProgress();
        $this->loadDocuments();
    }

    public function loadDocuments(): void
    {
        /** @var Collection<int, Document> $documents */
        $documents = Document::query()
            ->latest()
            ->limit(10)
            ->get();

        $this->uploadedDocuments = $documents->map(fn (Document $doc) => [
            'id' => $doc->id,
            'filename' => $doc->filename,
            'original_filename' => $doc->original_filename,
            'created_at' => $doc->created_at ? $doc->created_at->format('M d, Y H:i') : '',
        ])->toArray();
    }

    public function updatedFile(): void
    {
        if (! $this->file) {
            return;
        }

        $this->validate([
            'file' => 'file|max:10240|mimes:txt,pdf,doc,docx,md',
        ]);

        $this->resetUploadProgress();
        $this->uploadStage = 'Ready';
        $this->uploadProgress = 8;
        $this->uploadStatus = 'File ready to upload ✅';
    }

    public function uploadDocument(): void
    {
        if (! $this->file) {
            $this->uploadStatus = 'Please select a file.';

            return;
        }

        $this->isUploading = true;
        $this->uploadStartedAt = now()->toIso8601String();
        $this->uploadFinishedAt = null;
        $this->uploadStatus = 'Preparing upload...';
        $this->resetUploadProgress();

        try {
            $this->validate([
                'file' => 'required|file|max:10240|mimes:txt,pdf,doc,docx,md',
            ]);

            $this->setUploadProgress('Uploading file', 20, 1);
            usleep(150000);

            $originalName = $this->file->getClientOriginalName();

            $safeName = now()->timestamp.'_'.preg_replace(
                '/[^A-Za-z0-9_\-\.]/',
                '_',
                $originalName
            );

            $realPath = $this->file->getRealPath();
            $size = ($realPath && file_exists($realPath))
                ? filesize($realPath)
                : 0;

            $mimeType = $this->file->getMimeType();

            $storedName = Str::uuid().'.'.$this->file->extension();

            $path = $this->file->storeAs('documents', $safeName, 'local');

            $this->setUploadProgress('Parsing document', 45, 2);
            usleep(150000);

            /** @var Document $document */
            $document = Document::query()->create([
                'filename' => $storedName,
                'original_filename' => $originalName,
                'disk' => 'local',
                'path' => $path,
                'size' => $size,
                'mime_type' => $mimeType,
                'source' => 'livewire_upload_'.uniqid(),
                'status' => 'uploaded',
            ]);

            $this->setUploadProgress('Chunking content', 65, 3);
            usleep(150000);

            $this->setUploadProgress('Embedding vectors', 82, 4);
            usleep(150000);

            IndexDocumentJob::dispatch($document->id);

            $this->activeDocumentId = $document->id;
            $this->setUploadProgress('Queued for indexing', 35, 5);
            $this->uploadStatus = "✅ '{$originalName}' uploaded successfully! Queueing indexing...";

            $this->reset('file');

            $this->loadDocuments();

            $this->dispatch('documents-updated');
            $this->dispatch('$refresh');
        } catch (\Throwable $e) {
            logger()->error('Upload Error: '.$e->getMessage());

            $this->activeDocumentId = null;
            $this->uploadStartedAt = null;
            $this->uploadFinishedAt = now()->toIso8601String();
            $this->isUploading = false;
            $this->uploadStage = 'Upload failed';
            $this->uploadProgress = 0;
            $this->uploadStatus = '❌ Error: '.$e->getMessage();
        }
    }

    public function refreshUploadStatus(): void
    {
        if (! $this->activeDocumentId) {
            return;
        }

        $document = Document::query()->find($this->activeDocumentId);

        if (! $document) {
            $this->activeDocumentId = null;
            $this->isUploading = false;

            return;
        }

        if ($document->status === 'processing') {
            $this->isUploading = true;
            $this->setUploadProgress('Indexing in queue', 72, 5);
            $this->uploadStatus = 'Processing document in the background…';

            return;
        }

        if ($document->status === 'indexed') {
            $this->isUploading = false;
            $this->setUploadProgress('Indexed successfully', 100, 5);
            $this->uploadStatus = 'Document indexed and ready for retrieval.';
            $this->uploadStartedAt = null;
            $this->activeDocumentId = null;
            $this->loadDocuments();
            $this->dispatch('documents-updated');

            return;
        }

        if ($document->status === 'failed') {
            $this->isUploading = false;
            $this->uploadStage = 'Indexing failed';
            $this->uploadProgress = 0;
            $this->uploadStatus = 'Indexing failed. Check the document and retry.';
            $this->uploadStartedAt = null;
            $this->activeDocumentId = null;

            return;
        }

        $this->isUploading = true;
        $this->setUploadProgress('Queued for indexing', 35, 5);
        $this->uploadStatus = 'Waiting for the queue to start processing…';
    }

    protected function resetUploadProgress(): void
    {
        $this->uploadStage = 'Ready';
        $this->uploadProgress = 0;
        $this->uploadStartedAt = now()->toIso8601String();
        $this->uploadFinishedAt = null;
        $this->uploadSteps = [
            ['label' => 'Queued', 'state' => 'pending'],
            ['label' => 'Uploading', 'state' => 'pending'],
            ['label' => 'Parsing', 'state' => 'pending'],
            ['label' => 'Chunking', 'state' => 'pending'],
            ['label' => 'Embedding', 'state' => 'pending'],
            ['label' => 'Indexing', 'state' => 'pending'],
        ];
    }

    protected function setUploadProgress(string $stage, int $progress, int $activeStep): void
    {
        $this->uploadStage = $stage;
        $this->uploadProgress = $progress;
        $this->uploadSteps = array_map(function (array $step, int $index) use ($activeStep): array {
            if ($index < $activeStep) {
                $state = 'done';
            } elseif ($index === $activeStep) {
                $state = 'active';
            } else {
                $state = 'pending';
            }

            return ['label' => $step['label'], 'state' => $state];
        }, $this->uploadSteps, array_keys($this->uploadSteps));
    }

    public function render(): View
    {
        return view('livewire.document-upload', [
            'elapsedSeconds' => $this->uploadStartedAt ? max(1, now()->diffInSeconds($this->uploadStartedAt)) : 0,
            'startedAtLabel' => $this->uploadStartedAt ? now()->parse($this->uploadStartedAt)->format('H:i:s') : null,
            'finishedAtLabel' => $this->uploadFinishedAt ? now()->parse($this->uploadFinishedAt)->format('H:i:s') : null,
        ]);
    }
}
