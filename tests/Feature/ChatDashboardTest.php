<?php

use App\DTO\RetrievalResult;
use App\Livewire\Chat;
use Livewire\Livewire;
use Tests\TestCase;

describe('chat dashboard', function () {
    it('renders the redesigned chat workspace', function () {
        /** @var TestCase $this */
        $response = $this->get('/chat');

        $response->assertStatus(200);
        $response->assertSee('AI Assistant Workspace');
    });

    it('renders retrieval dto results without crashing', function () {
        Livewire::test(Chat::class)
            ->set('retrievedDocuments', [
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
            ])
            ->assertSee('Employee Handbook.pdf');
    });
});
