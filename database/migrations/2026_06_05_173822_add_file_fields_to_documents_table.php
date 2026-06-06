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
            $table->string('path')->nullable();
            $table->integer('size')->nullable();
            $table->string('mime_type')->nullable();
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
                'path',
                'size',
                'mime_type',
            ]);
        });
    }
};
