<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (env('RAG_VECTOR_STORE', 'mysql') === 'qdrant') {
            // Qdrant stores vectors externally, so local vector_records are not needed.
            return;
        }

        if (env('DB_CONNECTION', 'sqlite') === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
            $dimension = (int) env('PGVECTOR_DIM', 1536);
            DB::statement(sprintf(
                'CREATE TABLE vector_records (
                    id bigserial primary key,
                    document_id bigint not null references documents(id) on delete cascade,
                    vector vector(%d) not null,
                    metadata json null,
                    created_at timestamp(0) without time zone null,
                    updated_at timestamp(0) without time zone null
                )',
                $dimension
            ));

            return;
        }

        Schema::create('vector_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->json('vector');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('document_id')->references('id')->on('documents')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vector_records');
    }
};
