<?php

namespace App\Livewire\Snippets;

use App\Enums\SnippetLanguage;
use App\Livewire\Concerns\ParsesSnippetTags;
use App\Models\Snippet;
use App\Services\FolderService;
use App\Services\SnippetService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Edit snippet')]
class Edit extends Component
{
    use ParsesSnippetTags;

    public Snippet $snippet;

    public string $title = '';

    public string $code = '';

    public string $language = 'php';

    public bool $is_public = false;

    public string $tagsInput = '';

    /** @var list<int> */
    public array $folderIds = [];

    public ?string $shareUrl = null;

    public int $editorRenderKey = 0;

    public function mount(Snippet $snippet): void
    {
        $this->authorize('update', $snippet);
        $this->snippet = $snippet;
        $this->title = $snippet->title;
        $this->code = $snippet->code;
        $this->language = $snippet->language instanceof SnippetLanguage
            ? $snippet->language->value
            : (string) $snippet->language;
        $this->is_public = (bool) $snippet->is_public;
        $this->tagsInput = $snippet->tags->pluck('name')->implode(', ');
        $this->folderIds = $snippet->folders->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->shareUrl = null;
    }

    public function save(SnippetService $snippets, FolderService $folders): void
    {
        $this->authorize('update', $this->snippet);

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

        $snippets->update($this->snippet, [
            'title' => $this->title,
            'code' => $this->code,
            'language' => $this->language,
            'tags' => $tags,
            'is_public' => $this->is_public,
            'user_id' => $this->snippet->user_id,
            'folder_ids' => $allowedFolderIds,
        ]);

        $this->snippet->refresh()->load(['tags', 'folders']);
        $this->tagsInput = $this->snippet->tags->pluck('name')->implode(', ');
        $this->folderIds = $this->snippet->folders->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->dispatch('app-toast', type: 'success', message: __('snippets.edit.saved_text'));
    }

    public function deleteSnippet(SnippetService $snippets): void
    {
        $this->authorize('delete', $this->snippet);
        $snippets->delete($this->snippet);
        $this->redirect(route('snippets.index'), navigate: true);
    }

    public function generateShareLink(SnippetService $snippets): void
    {
        $this->authorize('update', $this->snippet);
        $payload = $snippets->generatePublicLink($this->snippet->id);
        $this->shareUrl = $payload['url'] ?? null;
    }

    #[On('snippet-rolled-back')]
    public function handleSnippetRolledBack(int $snippetId, ?int $revisionVersion = null): void
    {
        if ($snippetId !== $this->snippet->id) {
            return;
        }

        $this->snippet->refresh()->load('tags');
        $this->title = $this->snippet->title;
        $this->code = $this->snippet->code;
        $this->language = (string) $this->snippet->language;
        $this->is_public = (bool) $this->snippet->is_public;
        $this->tagsInput = $this->snippet->tags->pluck('name')->implode(', ');
        $this->editorRenderKey++;
        $this->dispatch(
            'app-toast',
            type: 'success',
            message: $revisionVersion
                ? __('snippets.edit.rollback_applied_version', ['version' => $revisionVersion])
                : __('snippets.edit.rollback_applied')
        );
    }

    public function render(FolderService $folders)
    {
        return view('livewire.snippets.edit', [
            'languages' => SnippetLanguage::cases(),
            'folders' => $folders->listForUser((int) auth()->id()),
            'publicPageUrl' => $this->snippet->is_public ? route('snippets.publicOpen', ['uuid' => $this->snippet->uuid]) : null,
        ]);
    }
}
