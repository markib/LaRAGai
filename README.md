# LaRAGai

Laravel RAG project with React TypeScript frontend and Ollama local LLM integration.

## Overview

This repository contains a full-stack Laravel + React project designed for local RAG workflows with Ollama:

- Document ingestion and preprocessing
- Local vector search and retrieval
- Ollama-powered generation
- Conversation/session tracking
- React TypeScript frontend SPA
- Vite frontend tooling

## Key directories

- `app/Services` — core RAG orchestration and provider adapters
- `app/Repositories` — document, vector, and conversation persistence
- `app/Models` — Eloquent models for storage
- `app/Http/Controllers` — API endpoints for ingestion and query
- `app/Jobs` — async ingestion and vector indexing
- `app/Console/Commands` — batch import and maintenance commands
- `config` — Laravel and RAG configuration
- `routes` — web and API routing
- `resources/js` — React TypeScript frontend
- `resources/views` — SPA view template

## Startup

1. Copy `.env.example` to `.env`.
2. Run `composer install`.
3. Run `npm install`.
4. Run `php artisan key:generate`.
5. Run `php artisan migrate`.
6. Run `npm run dev`.
7. Run `php artisan serve`.

## Notes

- `OLLAMA_HOST` defaults to `http://127.0.0.1:11434`.
- `RAG_PROVIDER` defaults to `ollama`.
- `RAG_VECTOR_STORE` can be set to `qdrant` to use Qdrant vector storage.
- `QDRANT_HOST` should point at your Qdrant API endpoint.
- `QDRANT_API_KEY` is used when your Qdrant deployment requires authentication.
- The React frontend loads from `resources/js/app.tsx` and mounts into `resources/views/app.blade.php`.

## Qdrant API endpoints

- `POST /api/qdrant/points` — add or update a point
- `POST /api/qdrant/points/search` — search vector points
- `GET /api/qdrant/points/{documentId}` — fetch a point by document ID
- `DELETE /api/qdrant/points/{documentId}` — delete a point
- `POST /api/qdrant/points/clear` — remove all points from the Qdrant collection
- Optional Qdrant vector store support via `RAG_VECTOR_STORE=qdrant`.
