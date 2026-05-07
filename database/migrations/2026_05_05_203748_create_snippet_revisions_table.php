<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('snippet_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('snippet_id')->constrained('snippets');
            $table->integer('version');
            $table->string('title');
            $table->longText('code');
            $table->string('language');
            $table->boolean('is_public');
            $table->json('tags_json')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index('snippet_id');
            $table->unique(['snippet_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('snippet_revisions');
    }
};
