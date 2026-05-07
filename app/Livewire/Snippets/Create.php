<?php

namespace App\Livewire\Snippets;

use App\Enums\SnippetLanguage;
use App\Livewire\Concerns\ParsesSnippetTags;
use App\Services\FolderService;
use App\Services\SnippetService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
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

    public function render(FolderService $folders)
    {
        return view('livewire.snippets.create', [
            'languages' => SnippetLanguage::cases(),
            'folders' => $folders->listForUser((int) auth()->id()),
        ]);
    }
}
