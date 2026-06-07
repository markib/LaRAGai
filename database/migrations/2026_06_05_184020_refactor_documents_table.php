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
            if (!Schema::hasColumn('documents', 'filename')) {
                $table->string('filename')->nullable();
            }

            if (!Schema::hasColumn('documents', 'original_filename')) {
                $table->string('original_filename')->nullable();
            }

            if (!Schema::hasColumn('documents', 'disk')) {
                $table->string('disk')->default('local');
            }

            if (!Schema::hasColumn('documents', 'path')) {
                $table->string('path')->nullable();
            }

            if (!Schema::hasColumn('documents', 'mime_type')) {
                $table->string('mime_type')->nullable();
            }

            if (!Schema::hasColumn('documents', 'size')) {
                $table->bigInteger('size')->default(0);
            }

            if (!Schema::hasColumn('documents', 'status')) {
                $table->enum('status', [
                    'uploaded',
                    'processing',
                    'indexed',
                    'failed',
                ])->default('uploaded');
            }

            if (!Schema::hasColumn('documents', 'error_message')) {
                $table->text('error_message')->nullable();
            }
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
