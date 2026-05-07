<?php

namespace Tests\Feature;

use App\DTOs\SearchFilters;
use App\Models\Folder;
use App\Livewire\Snippets\Index;
use App\Models\SmartCollection;
use App\Models\Snippet;
use App\Models\Tag;
use App\Models\User;
use App\Services\SnippetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class SnippetSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_find_by_user_applies_language_tag_and_date_filters(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $phpTag = Tag::query()->create(['name' => 'php', 'slug' => 'php']);
        $jsTag = Tag::query()->create(['name' => 'js', 'slug' => 'js']);

        $first = Snippet::query()->create([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'title' => 'Old php',
            'code' => '<?php echo 1;',
            'language' => 'php',
            'is_public' => false,
            'created_at' => Carbon::parse('2026-01-10'),
            'updated_at' => Carbon::parse('2026-01-10'),
        ]);
        $first->tags()->attach($phpTag->id);

        $second = Snippet::query()->create([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'title' => 'New js',
            'code' => 'console.log(2)',
            'language' => 'javascript',
            'is_public' => false,
            'created_at' => Carbon::parse('2026-03-10'),
            'updated_at' => Carbon::parse('2026-03-10'),
        ]);
        $second->tags()->attach($jsTag->id);

        Snippet::query()->create([
            'user_id' => $otherUser->id,
            'uuid' => (string) Str::uuid(),
            'title' => 'Foreign',
            'code' => 'echo 3;',
            'language' => 'php',
            'is_public' => false,
            'created_at' => Carbon::parse('2026-03-11'),
            'updated_at' => Carbon::parse('2026-03-11'),
        ]);

        /** @var SnippetService $snippetService */
        $snippetService = app(SnippetService::class);
        $filters = SearchFilters::fromArray([
            'language' => 'php',
            'tags' => ['php'],
            'created_from' => '2026-01-01',
            'created_to' => '2026-02-01',
            'per_page' => 50,
        ]);

        $result = $snippetService->findByUser($user->id, $filters);

        $this->assertCount(1, $result->items());
        $this->assertSame($first->id, $result->items()[0]->id);
    }

    public function test_user_can_save_and_apply_saved_search_in_livewire(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)->test(Index::class)
            ->set('query', 'api')
            ->set('language', 'php')
            ->set('createdFrom', '2026-01-01')
            ->set('savedSearchName', 'API PHP')
            ->call('saveCurrentSearch');

        $this->assertDatabaseHas('saved_searches', [
            'user_id' => $user->id,
            'name' => 'API PHP',
        ]);

        $savedSearchId = (int) \App\Models\SavedSearch::query()->where('user_id', $user->id)->value('id');

        $component
            ->set('query', '')
            ->set('language', '')
            ->call('applySavedSearch', $savedSearchId)
            ->assertSet('query', 'api')
            ->assertSet('language', 'php')
            ->assertSet('createdFrom', '2026-01-01');
    }

    public function test_find_by_user_filters_by_folder_and_smart_collection(): void
    {
        $user = User::factory()->create();

        $folderA = Folder::query()->create([
            'user_id' => $user->id,
            'name' => 'Backend',
            'slug' => 'backend',
            'color' => '#6366f1',
        ]);
        $folderB = Folder::query()->create([
            'user_id' => $user->id,
            'name' => 'Frontend',
            'slug' => 'frontend',
            'color' => '#22c55e',
        ]);

        $snippetA = Snippet::query()->create([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'title' => 'PHP backend',
            'code' => '<?php echo 1;',
            'language' => 'php',
            'is_public' => false,
        ]);
        $snippetB = Snippet::query()->create([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'title' => 'JS frontend',
            'code' => 'console.log(1);',
            'language' => 'javascript',
            'is_public' => false,
        ]);

        $snippetA->folders()->sync([$folderA->id]);
        $snippetB->folders()->sync([$folderB->id]);

        $collection = SmartCollection::query()->create([
            'user_id' => $user->id,
            'name' => 'Only backend snippet',
            'filters_json' => ['query' => 'backend'],
            'is_system' => false,
        ]);
        $collection->snippets()->sync([$snippetA->id => ['matched_at' => now()]]);

        /** @var SnippetService $snippetService */
        $snippetService = app(SnippetService::class);

        $folderFiltered = $snippetService->findByUser($user->id, SearchFilters::fromArray([
            'folder_ids' => [$folderA->id],
            'per_page' => 50,
        ]));
        $this->assertCount(1, $folderFiltered->items());
        $this->assertSame($snippetA->id, $folderFiltered->items()[0]->id);

        $smartFiltered = $snippetService->findByUser($user->id, SearchFilters::fromArray([
            'smart_collection_id' => $collection->id,
            'per_page' => 50,
        ]));
        $this->assertCount(1, $smartFiltered->items());
        $this->assertSame($snippetA->id, $smartFiltered->items()[0]->id);
    }
}
