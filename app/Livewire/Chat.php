<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Illuminate\Support\Str;
use App\Services\RagService;
use App\Repositories\ConversationRepository;

class Chat extends Component
{
    public ?string $sessionId = null;
    public array $messages = [];
    public string $currentQuery = '';
  
    
    
    public array $retrievedDocuments = [];
    public int $topK = 5;
    public string $errorMessage = '';

    protected RagService $ragService;
    protected ConversationRepository $conversationRepository;

    public function mount(): void
    {
        $this->bootServices();
        $this->sessionId ??= (string) Str::uuid();
        $this->loadMessages();
    }

    public function hydrate(): void
    {
        $this->bootServices();
    }

    protected function bootServices(): void
    {
        $this->ragService = app(RagService::class);
        $this->conversationRepository = app(ConversationRepository::class);
    }

    public function loadMessages(): void
    {
        try {
            $this->messages = $this->conversationRepository->getMessages($this->sessionId);
        } catch (\Throwable $e) {
            $this->errorMessage = 'Failed to load messages.';
            logger()->error($e);
        }
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

            $answer = $result['answer'] ?? 'Sorry, I could not generate a response.';
            $this->retrievedDocuments = $result['documents'] ?? [];

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
            // Always executed

            $this->currentQuery = '';

            // Optional analytics/logging
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

    #[Computed]
    public function messageList(): array
    {
        return $this->messages;
    }

    public function render()
    {
        return view('livewire.chat', [
            'messages' => $this->messageList(),
        ]);
    }
}
