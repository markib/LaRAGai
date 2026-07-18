<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('document_embeddings', function (Blueprint $table) {
            Schema::table('document_embeddings', function (Blueprint $table) {
                $table->dropColumn('embedding');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_embeddings', function (Blueprint $table) {
            //
        });
    }
};
