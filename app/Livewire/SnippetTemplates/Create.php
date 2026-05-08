<?php

namespace App\Livewire\SnippetTemplates;

use App\Enums\SnippetLanguage;
use App\Livewire\Concerns\ParsesSnippetTags;
use App\Services\SnippetTemplateService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Create snippet template')]
class Create extends Component
{
    use ParsesSnippetTags;

    public string $name = '';
    public string $description = '';
    public string $titleTemplate = '';
    public string $codeTemplate = '';
    public string $language = 'php';
    public string $defaultTagsInput = '';
    public bool $isFavorite = false;

    public function mount(SnippetTemplateService $templates): void
    {
        $this->fillFromLanguagePreset($templates);
    }

    public function updatedLanguage(SnippetTemplateService $templates): void
    {
        $this->fillFromLanguagePreset($templates);
    }

    public function save(SnippetTemplateService $templates): void
    {
        $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
            'titleTemplate' => ['required', 'string', 'min:2', 'max:255'],
            'codeTemplate' => ['required', 'string'],
            'language' => ['required', 'string', 'in:'.collect(SnippetLanguage::cases())->map->value->implode(',')],
            'defaultTagsInput' => ['nullable', 'string', 'max:5000'],
            'isFavorite' => ['boolean'],
        ]);

        $templates->create([
            'user_id' => (int) auth()->id(),
            'name' => $this->name,
            'description' => $this->description !== '' ? $this->description : null,
            'title_template' => $this->titleTemplate,
            'code_template' => $this->codeTemplate,
            'language' => $this->language,
            'default_tags_json' => $this->parseTagsFromInput($this->defaultTagsInput),
            'is_favorite' => $this->isFavorite,
        ]);

        $this->dispatch('app-toast', type: 'success', message: __('snippet_templates.toast_created'));
        $this->redirect(route('snippet-templates.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.snippet-templates.create', [
            'languages' => SnippetLanguage::cases(),
        ]);
    }

    public function applyLanguagePreset(SnippetTemplateService $templates): void
    {
        $this->fillFromLanguagePreset($templates);
    }

    private function fillFromLanguagePreset(SnippetTemplateService $templates): void
    {
        $preset = $templates->getLanguagePreset($this->language);
        if (! $preset) {
            return;
        }

        $this->description = $preset['description'];
        $this->titleTemplate = $preset['title_template'];
        $this->codeTemplate = $preset['code_template'];
        $this->defaultTagsInput = collect($preset['default_tags'])->implode(', ');
    }
}
