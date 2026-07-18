<?php

use App\Livewire\Chat;
use App\Services\RagService;
use Livewire\Livewire;
use Tests\TestCase;

describe('Chat::submitQuery', function () {
    it('validates empty query and sets error message', function () {
        /** @var TestCase $this */
        Livewire::test(Chat::class)
            ->set('currentQuery', '')
            ->call('submitQuery')
            ->assertSet('errorMessage', 'Please enter a query.')
            ->assertSet('currentQuery', '')
            ->assertCount('messages', 0);
    });

    it('validates whitespace-only query and sets error message', function () {
        /** @var TestCase $this */
        Livewire::test(Chat::class)
            ->set('currentQuery', '   ')
            ->call('submitQuery')
            ->assertSet('errorMessage', 'Please enter a query.')
            ->assertSet('currentQuery', '   ')
            ->assertCount('messages', 0);
    });

    it('calls RagService::answer with correct parameters', function () {
        /** @var TestCase $this */
        $this->mock(RagService::class, function ($mock) {
            $mock->shouldReceive('answer')
                ->once()
                ->withArgs(fn ($query, $sessionId, $limit, $progressCallback) =>
                    $query === 'What is RAG?' && $limit === 5
                )
                ->andReturn([
                    'answer' => 'Mocked answer.',
                    'documents' => [],
                    'session_id' => null,
                ]);
        });

        Livewire::test(Chat::class)
            ->set('currentQuery', 'What is RAG?')
            ->call('submitQuery');
    });

    it('clears currentQuery after submission', function () {
        /** @var TestCase $this */
        $this->mock(RagService::class, function ($mock) {
            $mock->shouldReceive('answer')
                ->andReturn([
                    'answer' => 'Mocked answer.',
                    'documents' => [],
                    'session_id' => null,
                ]);
        });

        Livewire::test(Chat::class)
            ->set('currentQuery', 'What is RAG?')
            ->call('submitQuery')
            ->assertSet('currentQuery', '');
    });

    it('adds user message to messages array', function () {
        /** @var TestCase $this */
        $this->mock(RagService::class, function ($mock) {
            $mock->shouldReceive('answer')
                ->andReturn([
                    'answer' => 'Mocked answer.',
                    'documents' => [],
                    'session_id' => null,
                ]);
        });

        Livewire::test(Chat::class)
            ->set('currentQuery', 'Hello world')
            ->call('submitQuery')
            ->assertSet('currentQuery', '')
            ->assertSet('messages.0.role', 'user')
            ->assertSet('messages.0.message', 'Hello world');
    });

    it('stores the trimmed query in the user message', function () {
        /** @var TestCase $this */
        $this->mock(RagService::class, function ($mock) {
            $mock->shouldReceive('answer')
                ->andReturn([
                    'answer' => 'Mocked answer.',
                    'documents' => [],
                    'session_id' => null,
                ]);
        });

        Livewire::test(Chat::class)
            ->set('currentQuery', '  trimmed query  ')
            ->call('submitQuery')
            ->assertSet('currentQuery', '')
            ->assertSet('messages.0.message', 'trimmed query');
    });

    it('resets retrieval steps to initial state', function () {
        /** @var TestCase $this */
        $this->mock(RagService::class, function ($mock) {
            $mock->shouldReceive('answer')
                ->andReturn([
                    'answer' => 'Mocked answer.',
                    'documents' => [],
                    'session_id' => null,
                ]);
        });

        Livewire::test(Chat::class)
            ->set('currentQuery', 'test query')
            ->call('submitQuery')
            ->assertSet('retrievalSteps', [
                ['label' => 'Searching embeddings', 'percent' => '0%', 'tone' => 'from-indigo-500 to-sky-500', 'completed' => false, 'active' => false],
                ['label' => 'Searching BM25',       'percent' => '0%', 'tone' => 'from-violet-500 to-indigo-500', 'completed' => false, 'active' => false],
                ['label' => 'Hybrid ranking',      'percent' => '0%', 'tone' => 'from-emerald-500 to-green-500', 'completed' => false, 'active' => false],
                ['label' => 'Generating answer',   'percent' => '0%', 'tone' => 'from-amber-500 to-orange-500', 'completed' => false, 'active' => false],
            ]);
    });

    it('resets errorMessage before processing', function () {
        /** @var TestCase $this */
        $this->mock(RagService::class, function ($mock) {
            $mock->shouldReceive('answer')
                ->andReturn([
                    'answer' => 'Mocked answer.',
                    'documents' => [],
                    'session_id' => null,
                ]);
        });

        Livewire::test(Chat::class)
            ->set('currentQuery', 'test')
            ->set('errorMessage', 'Previous error')
            ->call('submitQuery')
            ->assertSet('errorMessage', '');
    });

    it('message array contains required fields', function () {
        /** @var TestCase $this */
        $this->mock(RagService::class, function ($mock) {
            $mock->shouldReceive('answer')
                ->andReturn([
                    'answer' => 'Mocked answer.',
                    'documents' => [],
                    'session_id' => null,
                ]);
        });

        $component = Livewire::test(Chat::class);

        $component
            ->set('currentQuery', 'test')
            ->call('submitQuery');

        $message = $component->get('messages')[0];

        expect($message)
            ->toHaveKey('role', 'user')
            ->toHaveKey('message', 'test')
            ->toHaveKey('created_at');
    });

    it('triggers stream for answer placeholder', function () {
        /** @var TestCase $this */
        $this->mock(RagService::class, function ($mock) {
            $mock->shouldReceive('answer')
                ->andReturn([
                    'answer' => 'Mocked answer.',
                    'documents' => [],
                    'session_id' => null,
                ]);
        });

        Livewire::test(Chat::class)
            ->set('currentQuery', 'test')
            ->call('submitQuery')
            ->assertSet('currentQuery', '')
            ->assertSuccessful();
    });

    it('sessionId is set on mount and does not change', function () {
        /** @var TestCase $this */
        $this->mock(RagService::class, function ($mock) {
            $mock->shouldReceive('answer')
                ->andReturn([
                    'answer' => 'Mocked answer.',
                    'documents' => [],
                    'session_id' => null,
                ]);
        });

        $component = Livewire::test(Chat::class);
        $sessionId = $component->get('sessionId');

        expect($sessionId)->not->toBeNull();

        $component
            ->set('currentQuery', 'test')
            ->call('submitQuery')
            ->assertSet('sessionId', $sessionId);
    });
});
