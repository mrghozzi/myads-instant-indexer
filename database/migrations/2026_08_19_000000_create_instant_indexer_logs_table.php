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
        if (!Schema::hasTable('instant_indexer_logs')) {
            Schema::create('instant_indexer_logs', function (Blueprint $table) {
                $table->id();
                $table->text('url');
                $table->string('engine', 50)->default('google'); // google, indexnow, bing, yandex
                $table->string('action', 50)->default('URL_UPDATED'); // URL_UPDATED, URL_DELETED, MANUAL_SUBMIT, BATCH_SUBMIT
                $table->string('status', 20)->default('pending'); // success, error, pending
                $table->integer('http_code')->nullable();
                $table->longText('response_body')->nullable();
                $table->string('source_type', 50)->nullable(); // forum_topic, status_video, product, news, directory, page, manual, batch
                $table->unsignedBigInteger('source_id')->nullable();
                $table->text('error_message')->nullable();
                $table->integer('execution_time_ms')->nullable();
                $table->timestamps();

                $table->index(['engine', 'status']);
                $table->index(['source_type', 'source_id']);
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instant_indexer_logs');
    }
};
