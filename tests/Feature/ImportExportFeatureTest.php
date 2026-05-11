<?php

namespace Tests\Feature;

use App\Livewire\Snippets\ImportExport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ImportExportFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_save_github_token_from_import_export_page(): void
    {
        $user = User::factory()->create();
        $token = 'ghp_example_token_value_1234567890';

        Livewire::actingAs($user)
            ->test(ImportExport::class)
            ->set('importSource', 'gist')
            ->set('githubToken', $token)
            ->call('saveGithubToken');

        $user->refresh();
        $this->assertSame($token, $user->github_personal_access_token);

        $rawToken = DB::table('users')->where('id', $user->id)->value('github_personal_access_token');
        $this->assertNotNull($rawToken);
        $this->assertNotSame($token, $rawToken);
    }
}
