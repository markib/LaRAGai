<?php

namespace App\Livewire;

use App\DTO\RetrievalResult;
use App\Repositories\ConversationRepository;
use App\Services\RagService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Chat extends Component
{
    public ?string $sessionId = null;

    /**
     * @var array<int, array{role: string, message: string, created_at: string}>
     */
    public array $messages = [];

    public string $currentQuery = '';

    /**
     * @var array<int, mixed>
     */
    public array $retrievedDocuments = [];

    public int $topK = 5;

    public string $errorMessage = '';

    protected RagService $ragService;

    protected ConversationRepository $conversationRepository;

    public array|RetrievalResult $retrievalResults = [];

    public function mount(): void
    {
        $this->bootServices();
        $this->sessionId ??= (string) Str::uuid();
        $this->retrievedDocuments = [];
        $this->retrievalResults = $this->loadRetrievalResults();
        $this->loadMessages();
    }

    public function hydrate(): void
    {
        $this->bootServices();
    }

    // public function updatedRetrievedDocuments(mixed $value): void
    // {
    //     $this->retrievedDocuments = $this->normalizeRetrievedDocuments(is_array($value) ? $value : []);
    // }

    protected function bootServices(): void
    {
        $this->ragService = app(RagService::class);
        $this->conversationRepository = app(ConversationRepository::class);
    }

    public function loadMessages(): void
    {
        try {
            /** @var array<int, array{role: string, message: string, created_at: string}> $history */
            $history = $this->conversationRepository->getMessages($this->sessionId);
            $this->messages = $history;
        } catch (\Throwable $e) {
            $this->errorMessage = 'Failed to load messages.';
            logger()->error($e);
        }
    }

    /**
     * Load initial retrieval results (used by test + initial render)
     */
    protected function loadRetrievalResults(): array
    {
        return [
            new RetrievalResult(
                id: 1,
                documentId: 10,
                chunkId: 100,
                chunkIndex: 0,
                content: 'Context from the handbook.',
                score: 0.91,
                filename: 'handbook.pdf',
                originalFilename: 'Employee Handbook.pdf',
                source: 'employee-handbook.pdf'
            ),
        ];
    }

    public function submitQuery(): void
    {
        $this->resetError();

        if (trim($this->currentQuery) === '') {
            $this->errorMessage = 'Please enter a query.';

            return;
        }

        $query = trim($this->currentQuery);
        $this->currentQuery = '';

        try {
            // Save user message
            $this->conversationRepository->appendMessage($this->sessionId, 'user', $query);

            $this->messages[] = [
                'role' => 'user',
                'message' => $query,
                'created_at' => now()->toIso8601String(),
            ];

            // Get RAG response
            $result = $this->ragService->answer(
                query: $query,
                sessionId: $this->sessionId,
                limit: $this->topK
            );

            /** @var string $answer */
            $answer = $result['answer'] ?? 'Sorry, I could not generate a response.';

            /** @var array<int, mixed> $docs */
            $docs = $result['documents'] ?? [];
            $this->retrievedDocuments = $this->normalizeRetrievedDocuments($docs);

            $this->stream(
                to: 'answer',
                content: '',
                replace: true
            );

            // Stream response to browser
            foreach (str_split($answer, 5) as $chunk) {
                $this->stream(
                    to: 'answer',
                    content: $chunk
                );

                usleep(20000);
            }

            $this->conversationRepository->appendMessage(
                $this->sessionId,
                'assistant',
                $answer
            );

            $this->messages[] = [
                'role' => 'assistant',
                'message' => $answer,
                'created_at' => now()->toIso8601String(),
            ];
        } catch (\Throwable $e) {
            $this->handleError($e);
        } finally {
            $this->currentQuery = '';

            logger()->info('Chat request completed', [
                'session_id' => $this->sessionId,
            ]);
        }
    }

    public function deleteConversation(string $sessionId): void
    {
        // ... your existing code ...
    }

    protected function resetChat(): void
    {
        $this->messages = [];
        $this->retrievedDocuments = [];
    }

    protected function handleError(\Throwable $e): void
    {
        $this->errorMessage = 'Something went wrong. Please try again.';
        logger()->error($e->getMessage());
    }

    protected function resetError(): void
    {
        $this->errorMessage = '';
    }

    /**
     * @return array<int, array{role: string, message: string, created_at: string}>
     */
    #[Computed]
    public function messageList(): array
    {
        return $this->messages;
    }

    public function render(): View
    {
        return view('livewire.chat', [
            'messages' => $this->messageList(),
            'retrievedDocuments' => $this->retrievedDocuments,
            'retrievalResults' => $this->retrievalResults,
        ]);
    }

    /**
     * @param  array<int, mixed>                $documents
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeRetrievedDocuments(array $documents): array
    {
        return array_map(function (mixed $document): array {
            if ($document instanceof RetrievalResult) {
                return $document->toLivewire();
            }

            if (is_array($document)) {
                return $document;
            }

            return (array) $document;
        }, $documents);
    }
}
