<?php

namespace App\Jobs;

use App\DTO\RetrievalResult;
use App\Events\AnswerGenerated;
use App\Events\RetrievalProgressUpdated;
use App\Repositories\ConversationRepository;
use App\Services\RagService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessRagQuery implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $sessionId,
        public string $query,
        public int $topK = 5,
    ) {}

    public function handle(RagService $ragService, ConversationRepository $conversationRepository): void
    {
        // 1. Save user message to database
        $conversationRepository->appendMessage($this->sessionId, 'user', $this->query);

        // 2. Broadcast initial progress event over Reverb/Echo
        RetrievalProgressUpdated::dispatch($this->sessionId, 'Searching embeddings', 0);

        try {
            $result = $ragService->answer(
                query: $this->query,
                sessionId: $this->sessionId,
                limit: $this->topK,
                progressCallback: function (string $label, int $percent) {
                    // 3. Broadcast real-time step changes over Reverb/Echo
                    RetrievalProgressUpdated::dispatch($this->sessionId, $label, $percent);
                }
            );

            $answer = $result['answer'] ?? 'Sorry, I could not generate a response.';
            $docs = $result['documents'] ?? [];

            // 4. Save completed assistant message to database
            $conversationRepository->appendMessage($this->sessionId, 'assistant', $answer);

            $normalizedDocs = array_map(function (mixed $doc): array {
                if ($doc instanceof RetrievalResult) {
                    return $doc->toLivewire();
                }

                return (array) $doc;
            }, $docs);

            // 5. Broadcast the final layout to the browser
            AnswerGenerated::dispatch(
                sessionId: $this->sessionId,
                answer: $answer,
                documents: $normalizedDocs,
            );
        } catch (\Throwable $e) {
            logger()->error('ProcessRagQuery failed', [
                'session_id' => $this->sessionId,
                'error' => $e->getMessage(),
            ]);

            AnswerGenerated::dispatch(
                sessionId: $this->sessionId,
                answer: 'Sorry, something went wrong while processing your query.',
                documents: [],
            );
        }
    }
}
