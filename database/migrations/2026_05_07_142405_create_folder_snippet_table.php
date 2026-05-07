<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('folder_snippet', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->constrained('folders');
            $table->foreignId('snippet_id')->constrained('snippets');
            $table->timestamps();

            $table->unique(['folder_id', 'snippet_id']);
            $table->index(['folder_id', 'snippet_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folder_snippet');
    }
};
