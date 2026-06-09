<div
    class="chat-container"
    x-data
    x-init="
        $watch(
            () => $wire.messages.length,
            () => {
                requestAnimationFrame(() => {
                    let c = document.querySelector('.messages-container');
                    if(c) c.scrollTop = c.scrollHeight;
                });
            }
        );
    ">

    <div class="messages-container">
        @if ($messages)
        @foreach ($messages as $message)
        <div class="message-wrapper message-{{ $message['role'] }}">
            <div class="message-content">
                <div class="message-role">
                    <strong>{{ ucfirst($message['role']) }}</strong>
                    <span class="timestamp">{{ \Carbon\Carbon::parse($message['created_at'])->format('M d, H:i') }}</span>
                </div>
                <div class="message-text">
                    {!! nl2br(e($message['message'])) !!}
                </div>
            </div>
        </div>
        @endforeach
        @else
        <div class="empty-state">
            <p>Start a conversation by asking a question.</p>
        </div>
        @endif

        <div
            wire:loading.flex
            wire:target="submitQuery"
            class="message-wrapper message-assistant">
            <div class="message-content">

                <div class="message-role">
                    <strong>Assistant</strong>
                </div>
                <!-- Animated thinking dots INSIDE message -->
                <span class="typing-indicator">
                    <span></span>
                    <span></span>
                    <span></span>
                </span>
                <div
                    wire:stream="answer"
                    class="message-text"></div>

            </div>
        </div>
    </div>

    @if ($errorMessage)
    <div class="error-banner">
        <strong>Error:</strong> {{ $errorMessage }}
    </div>
    @endif

    @if(count($retrievedDocuments))
    <div class="documents-panel">
        <h3 class="documents-title">Retrieved Documents ({{ count($retrievedDocuments) }})</h3>
        <div class="documents-list">
            @foreach ($retrievedDocuments as $doc)
            
            <div class="document-item">
                <div class="document-header">
                    <span class="document-source">
                        {{ $doc['original_filename']
        ?? $doc['filename']
        ?? $doc['metadata']['filename']
        ?? 'Unknown' }}
                    </span>
                    <span class="document-score">Score: {{ round($doc['score'], 3) }}</span>
                </div>
                <p class="document-preview">
                    {{ substr($doc['payload']['text'] ?? '', 0, 200) }}...
                </p>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <form wire:submit.prevent="submitQuery" class="query-form">
        <div class="input-wrapper">
            <textarea
                wire:model.live="currentQuery"
                class="query-input"
                rows="3"
                placeholder="Ask anything about your documents..."
                wire:keydown.enter.prevent="submitQuery"></textarea>

            <button
                type="submit"
                class="submit-button flex items-center justify-center gap-2"
                wire:loading.attr="disabled"
                wire:target="submitQuery">
                <!-- Default state -->
                <span
                    wire:loading.remove
                    wire:target="submitQuery">
                    Ask
                </span>
                <!-- Loading state -->
                <span
                    class="flex items-center gap-2"
                    wire:loading
                    wire:target="submitQuery">
                    <span class="loading-spinner"></span>
                    Processing...
                </span>
            </button>
        </div>
    </form>



</div>