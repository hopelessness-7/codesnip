<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('smart_collection_snippet', function (Blueprint $table) {
            $table->id();
            $table->foreignId('smart_collection_id')->constrained('smart_collections');
            $table->foreignId('snippet_id')->constrained('snippets');
            $table->dateTime('matched_at');
            $table->timestamps();

            $table->unique(['smart_collection_id', 'snippet_id']);
            $table->index(['smart_collection_id', 'snippet_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smart_collection_snippet');
    }
};
