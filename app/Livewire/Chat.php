<?php

namespace App\Livewire;

use App\DTO\RetrievalResult;
use App\Events\RetrievalProgressUpdated;
use App\Jobs\ProcessRagQuery;
use App\Repositories\ConversationRepository;
use App\Services\RagService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
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

    /**
     * The in-flight assistant reply placeholder.
     * Non-empty while a ProcessRagQuery job is running for this session.
     */
    public string $currentAnswer = '';

    /**
     * The progress label and percent for the in-flight job (server-driven, used
     * by the chat scroll area when the job is running). Mirrors the Echo
     * progress path so the user sees movement even when Reverb is offline.
     */
    public string $progressLabel = '';

    public int $progressPercent = 0;

    public bool $isProcessing = false;

    protected RagService $ragService;

    protected ConversationRepository $conversationRepository;

    /**
     * @var array<int, RetrievalResult|array<string, mixed>>
     */
    public array $retrievalResults = [];

    /** @var array<int, array{label: string, percent: string, tone: string, completed: bool, active: bool}> */
    public array $retrievalSteps = [];

    public function mount(): void
    {
        $this->bootServices();
        $this->sessionId ??= (string) Str::uuid();
        $this->retrievedDocuments = [];
        $this->retrievalResults = $this->loadRetrievalResults();
        $this->resetRetrievalSteps();
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

    #[On('loadMessages')]
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
     * Server-driven signal from ProcessRagQuery: the assistant reply has been
     * saved to the conversation. Refresh messages and clear the in-flight UI.
     */
    #[On('chat-answer-received')]
    public function onChatAnswerReceived(?string $sessionId = null): void
    {
        if ($sessionId !== null && $sessionId !== $this->sessionId) {
            return;
        }

        $this->isProcessing = false;
        $this->currentAnswer = '';
        $this->progressLabel = '';
        $this->progressPercent = 100;
        $this->loadMessages();
    }

    /**
     * Server-driven progress signal from ProcessRagQuery. Updates the
     * retrievalSteps and in-flight progress state on the component so the
     * chat area shows movement even when Reverb/Echo is not running.
     */
    #[On('chat-progress-updated')]
    public function onChatProgressUpdated(?string $label = null, int $percent = 0, ?string $sessionId = null): void
    {
        if ($sessionId !== null && $sessionId !== $this->sessionId) {
            return;
        }

        if ($label === null) {
            return;
        }

        $this->isProcessing = true;
        $this->progressLabel = $label;
        $this->progressPercent = $percent;
        $this->applyProgressStep($label, $percent);
    }

    /**
     * @return array<int, RetrievalResult|array<string, mixed>>
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
                limit: $this->topK,
                progressCallback: function (string $label, int $percent) {
                    // 3. Broadcast real-time step changes over Reverb/Echo
                    RetrievalProgressUpdated::dispatch($this->sessionId, $label, $percent);
                }
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

            // 3. ⚡ SUCCESS! Stream has ended, now push the Reverb progress bar to 100%
            broadcast(new RetrievalProgressUpdated(
                sessionId: $this->sessionId,
                label: 'Generating answer',
                percent: 100
            ));

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

    /**
     * Mirror of the Alpine `update()` stage map in chat.blade.php. Used by the
     * server-driven progress listener so the chat area animates even when
     * Reverb is offline.
     */
    protected function applyProgressStep(string $label, int $percent): void
    {
        $stages = [
            'Searching embeddings' => 0,
            'Vector search' => 0,
            'Searching BM25' => 1,
            'BM25 search' => 1,
            'Hybrid ranking' => 2,
            'Generating answer' => 3,
            'Retrieval complete' => 3,
        ];

        $current = $stages[$label] ?? null;

        if ($current === null) {
            return;
        }

        foreach ($this->retrievalSteps as $index => $step) {
            if ($index < $current) {
                $this->retrievalSteps[$index]['percent'] = '100%';
                $this->retrievalSteps[$index]['completed'] = true;
                $this->retrievalSteps[$index]['active'] = false;
            } elseif ($index === $current) {
                $this->retrievalSteps[$index]['percent'] = $percent.'%';
                $this->retrievalSteps[$index]['active'] = $percent < 100;
                $this->retrievalSteps[$index]['completed'] = $percent >= 100;
            } else {
                $this->retrievalSteps[$index]['percent'] = '0%';
                $this->retrievalSteps[$index]['active'] = false;
                $this->retrievalSteps[$index]['completed'] = false;
            }
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
        $this->currentAnswer = '';
        $this->progressLabel = '';
        $this->progressPercent = 0;
        $this->isProcessing = false;
        $this->errorMessage = '';
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

    protected function resetRetrievalSteps(): void
    {
        $this->retrievalSteps = [
            ['label' => 'Searching embeddings', 'percent' => '0%', 'tone' => 'from-indigo-500 to-sky-500', 'completed' => false, 'active' => false],
            ['label' => 'Searching BM25',       'percent' => '0%', 'tone' => 'from-violet-500 to-indigo-500', 'completed' => false, 'active' => false],
            ['label' => 'Hybrid ranking',      'percent' => '0%', 'tone' => 'from-emerald-500 to-green-500', 'completed' => false, 'active' => false],
            ['label' => 'Generating answer',   'percent' => '0%', 'tone' => 'from-amber-500 to-orange-500', 'completed' => false, 'active' => false],
        ];
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
     * Normalize documents whether they come as RetrievalResult objects or arrays
     *
     * @param  array<int, RetrievalResult|array<string, mixed>> $documents
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

            // Fallback for unexpected types
            return (array) $document;
        }, $documents);
    }
}
