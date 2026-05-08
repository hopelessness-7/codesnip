<?php

namespace App\Livewire\SnippetTemplates;

use App\Enums\SnippetLanguage;
use App\Livewire\Concerns\ParsesSnippetTags;
use App\Models\SnippetTemplate;
use App\Services\SnippetTemplateService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Edit snippet template')]
class Edit extends Component
{
    use ParsesSnippetTags;

    public SnippetTemplate $snippetTemplate;

    public string $name = '';
    public string $description = '';
    public string $titleTemplate = '';
    public string $codeTemplate = '';
    public string $language = 'php';
    public string $defaultTagsInput = '';
    public bool $isFavorite = false;

    public function mount(SnippetTemplate $snippetTemplate): void
    {
        abort_unless($snippetTemplate->user_id === (int) auth()->id(), 404);

        $this->snippetTemplate = $snippetTemplate;
        $this->name = $snippetTemplate->name;
        $this->description = (string) ($snippetTemplate->description ?? '');
        $this->titleTemplate = $snippetTemplate->title_template;
        $this->codeTemplate = $snippetTemplate->code_template;
        $this->language = $snippetTemplate->language;
        $this->defaultTagsInput = collect($snippetTemplate->default_tags_json ?? [])->implode(', ');
        $this->isFavorite = (bool) $snippetTemplate->is_favorite;
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

        $templates->update($this->snippetTemplate, [
            'name' => $this->name,
            'description' => $this->description !== '' ? $this->description : null,
            'title_template' => $this->titleTemplate,
            'code_template' => $this->codeTemplate,
            'language' => $this->language,
            'default_tags_json' => $this->parseTagsFromInput($this->defaultTagsInput),
            'is_favorite' => $this->isFavorite,
        ]);

        $this->dispatch('app-toast', type: 'success', message: __('snippet_templates.toast_updated'));
        $this->redirect(route('snippet-templates.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.snippet-templates.edit', [
            'languages' => SnippetLanguage::cases(),
        ]);
    }
}
