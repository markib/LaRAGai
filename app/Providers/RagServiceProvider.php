<?php

namespace App\Providers;

use App\Repositories\ConversationRepository;
use App\Repositories\DocumentRepository;
use App\Repositories\QdrantVectorRepository;
use App\Repositories\VectorRepositoryInterface;
use App\Services\Contracts\EmbeddingProviderInterface;
use App\Services\Contracts\GenerationProviderInterface;
use App\Services\Contracts\RetrievalProviderInterface;
use App\Services\Providers\LocalRetrievalProvider;
use App\Services\Providers\OllamaProvider;
use App\Services\Providers\OpenAIProvider;
use App\Services\Retrieval\PostgresBm25Retriever;
use Illuminate\Support\ServiceProvider;

class RagServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $defaultProvider = config('rag.provider', 'ollama');
        $providerClass = $defaultProvider === 'openai' ? OpenAIProvider::class : OllamaProvider::class;

        $this->app->singleton(EmbeddingProviderInterface::class, $providerClass);
        $this->app->singleton(GenerationProviderInterface::class, $providerClass);
        $this->app->singleton(
            PostgresBm25Retriever::class
        );

        $this->app->singleton(VectorRepositoryInterface::class, function () {
            if (config('rag.vector_store') === 'qdrant') {
                return new QdrantVectorRepository;
            }
        });

        $this->app->singleton(RetrievalProviderInterface::class, function ($app) {
            return new LocalRetrievalProvider(
                $app->make(EmbeddingProviderInterface::class),
                $app->make(QdrantVectorRepository::class),
                $app->make(DocumentRepository::class),
                $app->make(PostgresBm25Retriever::class),
            );
        });

        $this->app->singleton(DocumentRepository::class);
        $this->app->singleton(ConversationRepository::class);
    }
}
