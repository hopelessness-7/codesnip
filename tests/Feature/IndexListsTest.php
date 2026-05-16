<?php

namespace Tests\Feature;

use App\Models\Folder;
use App\Models\SmartCollection;
use App\Models\Snippet;
use App\Models\SnippetTemplate;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class IndexListsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_index_pages(): void
    {
        $this->get(route('folders.index'))->assertRedirect(route('login'));
        $this->get(route('smart-collections.index'))->assertRedirect(route('login'));
        $this->get(route('snippet-templates.index'))->assertRedirect(route('login'));
        $this->get(route('tags.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_index_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('folders.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('smart-collections.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('snippet-templates.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('tags.index'))
            ->assertOk();
    }

    public function test_folders_index_paginates_at_twelve_per_page(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 13; $i++) {
            Folder::query()->create([
                'user_id' => $user->id,
                'name' => 'Folder '.$i,
                'slug' => 'folder-'.$i,
                'color' => '#6366f1',
            ]);
        }

        Livewire::actingAs($user)
            ->test(\App\Livewire\Folders\Index::class)
            ->assertViewHas('folders', fn ($paginator) => $paginator->count() === 12 && $paginator->total() === 13)
            ->call('gotoPage', 2)
            ->assertViewHas('folders', fn ($paginator) => $paginator->count() === 1);
    }

    public function test_tags_index_lists_only_user_snippet_tags(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $userTag = Tag::query()->create(['name' => 'api', 'slug' => 'api']);
        $otherTag = Tag::query()->create(['name' => 'vue', 'slug' => 'vue']);

        $snippet = Snippet::query()->create([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'title' => 'Mine',
            'code' => 'code',
            'language' => 'php',
            'is_public' => false,
        ]);
        $snippet->tags()->sync([$userTag->id]);

        $otherSnippet = Snippet::query()->create([
            'user_id' => $other->id,
            'uuid' => (string) Str::uuid(),
            'title' => 'Theirs',
            'code' => 'code',
            'language' => 'php',
            'is_public' => false,
        ]);
        $otherSnippet->tags()->sync([$otherTag->id]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Tags\Index::class)
            ->assertSee('api')
            ->assertDontSee('vue');
    }

    public function test_smart_collections_index_shows_rules_summary(): void
    {
        $user = User::factory()->create();

        SmartCollection::query()->create([
            'user_id' => $user->id,
            'name' => 'PHP public',
            'filters_json' => [
                'language' => 'php',
                'is_public' => true,
            ],
            'is_system' => false,
        ]);

        $this->actingAs($user)
            ->get(route('smart-collections.index'))
            ->assertOk()
            ->assertSee(__('languages.php'), false)
            ->assertSee(__('smart_collections.rule_visibility_public'), false);
    }

    public function test_snippet_templates_index_paginates(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 13; $i++) {
            SnippetTemplate::query()->create([
                'user_id' => $user->id,
                'name' => 'Template '.$i,
                'language' => 'php',
                'title_template' => '[[name]]',
                'code_template' => 'echo [[name]];',
            ]);
        }

        Livewire::actingAs($user)
            ->test(\App\Livewire\SnippetTemplates\Index::class)
            ->assertViewHas('templates', fn ($paginator) => $paginator->count() === 12 && $paginator->total() === 13);
    }
}
