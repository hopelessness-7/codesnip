<?php

namespace Tests\Feature;

use App\Models\Snippet;
use App\Models\Tag;
use App\Models\User;
use App\Services\SnippetRevisionService;
use App\Services\SnippetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SnippetRevisionTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_without_changes_does_not_create_revision(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $snippet = Snippet::query()->create([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'title' => 'Initial title',
            'code' => 'echo "Hello";',
            'language' => 'php',
            'is_public' => false,
        ]);

        /** @var SnippetService $snippetService */
        $snippetService = app(SnippetService::class);

        $snippetService->update($snippet, [
            'title' => 'Initial title',
            'code' => 'echo "Hello";',
            'language' => 'php',
            'is_public' => false,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseCount('snippet_revisions', 0);
    }

    public function test_update_with_changes_creates_revision_snapshot(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $snippet = Snippet::query()->create([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'title' => 'Old title',
            'code' => 'echo "old";',
            'language' => 'php',
            'is_public' => false,
        ]);

        /** @var SnippetService $snippetService */
        $snippetService = app(SnippetService::class);

        $snippetService->update($snippet, [
            'title' => 'New title',
            'code' => 'echo "new";',
            'language' => 'php',
            'is_public' => true,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseCount('snippet_revisions', 1);
        $this->assertDatabaseHas('snippet_revisions', [
            'snippet_id' => $snippet->id,
            'version' => 1,
            'title' => 'Old title',
            'code' => 'echo "old";',
            'is_public' => 0,
            'created_by' => $user->id,
        ]);
    }

    public function test_rollback_restores_content_and_creates_new_revision(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $snippet = Snippet::query()->create([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'title' => 'Original title',
            'code' => 'echo "v1";',
            'language' => 'php',
            'is_public' => false,
        ]);

        $tag = Tag::query()->create([
            'name' => 'backend',
            'slug' => 'backend',
            'description' => null,
            'category' => null,
            'snippets_count' => 0,
            'is_ai_generated' => false,
        ]);
        $snippet->tags()->attach($tag->id);

        /** @var SnippetService $snippetService */
        $snippetService = app(SnippetService::class);
        /** @var SnippetRevisionService $snippetRevisionService */
        $snippetRevisionService = app(SnippetRevisionService::class);

        $snippetService->update($snippet, [
            'title' => 'Changed title',
            'code' => 'echo "v2";',
            'language' => 'php',
            'is_public' => true,
            'tags' => ['frontend'],
            'user_id' => $user->id,
        ]);

        $revision = $snippetRevisionService->getRevisionsForSnippet($snippet)[0] ?? null;
        $this->assertNotNull($revision);

        $restored = $snippetService->rollbackToRevision($snippet->fresh(), (int) $revision['id']);
        $this->assertTrue($restored);

        $snippet->refresh()->load('tags');
        $this->assertSame('Original title', $snippet->title);
        $this->assertSame('echo "v1";', $snippet->code);
        $this->assertFalse((bool) $snippet->is_public);
        $this->assertSame(['backend'], $snippet->tags->pluck('name')->values()->all());
        $this->assertDatabaseCount('snippet_revisions', 2);
    }
}
