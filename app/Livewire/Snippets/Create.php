<?php

namespace App\Livewire\Snippets;

use App\Enums\SnippetLanguage;
use App\Livewire\Concerns\ParsesSnippetTags;
use App\Services\FolderService;
use App\Services\SnippetService;
use App\Services\SnippetTemplateService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('New snippet')]
class Create extends Component
{
    use ParsesSnippetTags;

    public string $title = '';

    public string $code = '';

    public string $language = 'php';

    public bool $is_public = false;

    public string $tagsInput = '';

    /** @var list<int> */
    public array $folderIds = [];

    #[Url(as: 'template', history: true)]
    public string $templateId = '';

    /** @var array<string, string> */
    public array $templateVariableValues = [];

    /** @var list<string> */
    public array $templateVariables = [];

    public int $editorRenderKey = 0;

    public function mount(SnippetTemplateService $templates): void
    {
        if ($this->templateId !== '') {
            $this->loadTemplateData($templates);
        }
    }

    public function updatedTemplateId(): void
    {
        $this->loadTemplateData(app(SnippetTemplateService::class));
    }

    public function updatedTemplateVariableValues(): void
    {
        if ($this->templateId === '') {
            return;
        }

        $this->loadTemplateData(app(SnippetTemplateService::class), false);
    }

    public function save(SnippetService $snippets, FolderService $folders): void
    {
        $this->validate([
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'code' => ['required', 'string'],
            'language' => ['required', 'string', 'in:'.collect(SnippetLanguage::cases())->map->value->implode(',')],
            'is_public' => ['boolean'],
            'tagsInput' => ['nullable', 'string', 'max:5000'],
            'folderIds' => ['array'],
            'folderIds.*' => ['integer', 'exists:folders,id'],
            'templateId' => ['nullable', 'string'],
            'templateVariableValues' => ['array'],
        ]);

        $tags = $this->parseTagsFromInput($this->tagsInput);

        $allowedFolderIds = $folders->listForUser((int) auth()->id())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->intersect($this->folderIds)
            ->values()
            ->all();

        $snippets->create([
            'title' => $this->title,
            'code' => $this->code,
            'language' => $this->language,
            'tags' => $tags,
            'is_public' => $this->is_public,
            'user_id' => auth()->id(),
            'folder_ids' => $allowedFolderIds,
        ]);

        $this->redirect(route('snippets.index'), navigate: true);
    }

    public function render(FolderService $folders, SnippetTemplateService $templates)
    {
        return view('livewire.snippets.create', [
            'languages' => SnippetLanguage::cases(),
            'folders' => $folders->listForUser((int) auth()->id()),
            'templates' => $templates->listForUser((int) auth()->id()),
        ]);
    }

    private function loadTemplateData(SnippetTemplateService $templates, bool $initializeValues = true): void
    {
        $templateId = (int) $this->templateId;
        if ($templateId <= 0) {
            $this->templateVariables = [];
            $this->templateVariableValues = [];

            return;
        }

        $template = $templates->findForUser((int) auth()->id(), $templateId);
        if (! $template) {
            $this->templateVariables = [];
            $this->templateVariableValues = [];
            $this->templateId = '';

            return;
        }

        $variables = $templates->extractVariables(
            (string) $template->title_template,
            (string) $template->code_template
        );

        if ($initializeValues) {
            $current = $this->templateVariableValues;
            $this->templateVariableValues = [];
            foreach ($variables as $var) {
                $this->templateVariableValues[$var] = $current[$var] ?? $this->defaultTemplateVariableValue($var);
            }
        }

        $preview = $templates->buildPreview($template, $this->templateVariableValues);
        $this->templateVariables = $preview['variables'];
        $this->title = (string) ($preview['title'] ?? $this->title);
        $this->code = (string) ($preview['code'] ?? $this->code);
        $this->language = (string) ($preview['language'] ?? $this->language);
        $this->tagsInput = collect($preview['tags'] ?? [])->implode(', ');
        $this->editorRenderKey++;
    }

    private function defaultTemplateVariableValue(string $variable): string
    {
        $normalized = str_replace('_', ' ', trim($variable));
        $words = collect(explode(' ', $normalized))
            ->filter()
            ->map(fn (string $word) => ucfirst(strtolower($word)));

        return $words->implode('');
    }
}
