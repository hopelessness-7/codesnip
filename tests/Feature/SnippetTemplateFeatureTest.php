<?php

namespace Tests\Feature;

use App\Livewire\SnippetTemplates\Create as SnippetTemplatesCreate;
use App\Livewire\Snippets\Create as SnippetsCreate;
use App\Models\Snippet;
use App\Models\SnippetTemplate;
use App\Models\User;
use App\Services\SnippetTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SnippetTemplateFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_can_create_template_record_with_all_fields(): void
    {
        $user = User::factory()->create();

        /** @var SnippetTemplateService $service */
        $service = app(SnippetTemplateService::class);

        $template = $service->create([
            'user_id' => $user->id,
            'name' => 'API Endpoint',
            'description' => 'REST endpoint scaffold',
            'title_template' => '[[entity]] endpoint',
            'code_template' => "<?php\nRoute::get('/[[entity]]', fn () => []);\n",
            'language' => 'php',
            'default_tags_json' => ['api', 'php', 'http'],
            'is_favorite' => true,
        ]);

        $this->assertInstanceOf(SnippetTemplate::class, $template);
        $this->assertSame($user->id, $template->user_id);
        $this->assertSame('API Endpoint', $template->name);
        $this->assertSame('[[entity]] endpoint', $template->title_template);
        $this->assertSame('php', $template->language);
        $this->assertTrue((bool) $template->is_favorite);

        $this->assertDatabaseHas('snippet_templates', [
            'id' => $template->id,
            'user_id' => $user->id,
            'name' => 'API Endpoint',
            'language' => 'php',
            'is_favorite' => true,
        ]);

        $this->assertSame(['api', 'php', 'http'], $template->default_tags_json);
    }

    public function test_user_can_create_snippet_template_from_livewire_page(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(SnippetTemplatesCreate::class)
            ->set('name', 'Service template')
            ->set('description', 'Reusable service')
            ->set('titleTemplate', '[[service_name]] service')
            ->set('codeTemplate', "<?php\nclass [[service_name]]Service {}\n")
            ->set('language', 'php')
            ->set('defaultTagsInput', 'php, service')
            ->set('isFavorite', true)
            ->call('save');

        $this->assertDatabaseHas('snippet_templates', [
            'user_id' => $user->id,
            'name' => 'Service template',
            'language' => 'php',
            'is_favorite' => true,
        ]);
    }

    public function test_service_extracts_and_applies_template_variables(): void
    {
        /** @var SnippetTemplateService $service */
        $service = app(SnippetTemplateService::class);

        $vars = $service->extractVariables(
            '[[project_name]] API',
            "<?php\n// [[project_name]]\nclass [[class_name]] {}\n"
        );

        $this->assertSame(['class_name', 'project_name'], $vars);

        $applied = $service->applyVariables('Hello [[name]]!', ['name' => 'CodeSnip']);
        $this->assertSame('Hello CodeSnip!', $applied);
    }

    public function test_user_can_create_snippet_from_template_with_variable_values(): void
    {
        $user = User::factory()->create();
        $template = SnippetTemplate::query()->create([
            'user_id' => $user->id,
            'name' => 'Controller template',
            'title_template' => '[[entity]] controller',
            'code_template' => "<?php\nclass [[entity]]Controller {}\n",
            'language' => 'php',
            'default_tags_json' => ['php', 'controller'],
            'is_favorite' => false,
        ]);

        Livewire::actingAs($user)
            ->test(SnippetsCreate::class, ['template' => (string) $template->id])
            ->set('templateId', (string) $template->id)
            ->set('templateVariableValues', ['entity' => 'User'])
            ->call('updatedTemplateVariableValues')
            ->call('save');

        $snippet = Snippet::query()->where('user_id', $user->id)->latest('id')->first();
        $this->assertNotNull($snippet);
        $this->assertSame('User controller', $snippet->title);
        $this->assertStringContainsString('class UserController', $snippet->code);
        $this->assertSame('php', $snippet->language);
    }

    public function test_user_cannot_access_another_users_template_via_service(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $template = SnippetTemplate::query()->create([
            'user_id' => $owner->id,
            'name' => 'Private template',
            'title_template' => '[[x]]',
            'code_template' => '[[x]]',
            'language' => 'php',
            'is_favorite' => false,
        ]);

        /** @var SnippetTemplateService $service */
        $service = app(SnippetTemplateService::class);
        $found = $service->findForUser($other->id, $template->id);

        $this->assertNull($found);
    }
}
