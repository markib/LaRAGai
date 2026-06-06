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
        Schema::table('documents', function (Blueprint $table) {
            $table->string('filename')->nullable();
            $table->string('original_filename')->nullable();

            $table->string('disk')->default('local');
            $table->string('path')->nullable();

            $table->string('mime_type')->nullable();

            $table->bigInteger('size')->default(0);

            $table->enum('status', [
                'uploaded',
                'processing',
                'indexed',
                'failed',
            ])->default('uploaded');

            $table->text('error_message')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn([
                'filename',
                'original_filename',
                'disk',
                'path',
                'mime_type',
                'size',
                'status',
                'error_message',
            ]);
        });
    }
};
