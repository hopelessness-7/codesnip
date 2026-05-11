<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('snippets', function (Blueprint $table): void {
            $table->text('ai_summary')->nullable()->after('is_public');
            $table->longText('ai_explanation')->nullable()->after('ai_summary');
            $table->longText('ai_generated_test')->nullable()->after('ai_explanation');
        });
    }

    public function down(): void
    {
        Schema::table('snippets', function (Blueprint $table): void {
            $table->dropColumn(['ai_summary', 'ai_explanation', 'ai_generated_test']);
        });
    }
};
