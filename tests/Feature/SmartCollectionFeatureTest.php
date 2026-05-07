<?php

namespace Tests\Feature;

use App\Models\SmartCollection;
use App\Models\Snippet;
use App\Models\Tag;
use App\Models\User;
use App\Services\SmartCollectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SmartCollectionFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_rebuild_membership_attaches_matching_snippets(): void
    {
        $user = User::factory()->create();

        $tag = Tag::query()->create(['name' => 'api', 'slug' => 'api']);

        $matching = Snippet::query()->create([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'title' => 'PHP API',
            'code' => '<?php echo "api";',
            'language' => 'php',
            'is_public' => true,
        ]);
        $matching->tags()->sync([$tag->id]);

        Snippet::query()->create([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'title' => 'JS client',
            'code' => 'console.log(1);',
            'language' => 'javascript',
            'is_public' => true,
        ]);

        $collection = SmartCollection::query()->create([
            'user_id' => $user->id,
            'name' => 'PHP API',
            'filters_json' => [
                'language' => 'php',
                'is_public' => true,
                'tags' => ['api'],
                'tags_mode' => 'all',
                'query' => 'api',
            ],
            'is_system' => false,
        ]);

        /** @var SmartCollectionService $service */
        $service = app(SmartCollectionService::class);
        $service->rebuildMembershipForCollection($collection->id);

        $this->assertDatabaseHas('smart_collection_snippet', [
            'smart_collection_id' => $collection->id,
            'snippet_id' => $matching->id,
        ]);
    }
}
