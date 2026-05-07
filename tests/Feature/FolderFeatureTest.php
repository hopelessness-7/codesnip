<?php

namespace Tests\Feature;

use App\Models\Folder;
use App\Models\Snippet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FolderFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_folder_and_attach_snippet(): void
    {
        $user = User::factory()->create();

        $folder = Folder::query()->create([
            'user_id' => $user->id,
            'name' => 'Backend',
            'slug' => 'backend',
            'color' => '#6366f1',
        ]);

        $snippet = Snippet::query()->create([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'title' => 'API snippet',
            'code' => '<?php echo 1;',
            'language' => 'php',
            'is_public' => false,
        ]);

        $snippet->folders()->sync([$folder->id]);

        $this->assertDatabaseHas('folders', [
            'user_id' => $user->id,
            'name' => 'Backend',
        ]);

        $this->assertDatabaseHas('folder_snippet', [
            'folder_id' => $folder->id,
            'snippet_id' => $snippet->id,
        ]);
    }
}
