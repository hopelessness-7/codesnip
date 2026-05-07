<?php

namespace App\Livewire\Snippets;

use App\Services\SnippetRevisionService;
use App\Services\SnippetService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class RevisionsPanel  extends Component
{
    use AuthorizesRequests;

    public int $snippetId;

    public array $revisions = [];

    public ?int $selectedRevisionId = null;

    public ?array $selectedRevision = null;

    public ?array $selectedDiff = null;

    public ?int $pendingRollbackRevisionId = null;

    public string $diffMode = 'current';

    public function mount(int $snippetId): void
    {
        $this->snippetId = $snippetId;
        $this->loadRevisions();
    }

    public function loadRevisions(?SnippetService $snippetService = null, ?SnippetRevisionService $snippetRevisionService = null): void
    {
        $snippetService ??= app(SnippetService::class);
        $snippetRevisionService ??= app(SnippetRevisionService::class);
        $snippet = $snippetService->find($this->snippetId);
        $this->authorize('update', $snippet);
        $this->revisions = $snippetRevisionService->getRevisionsForSnippet($snippet);

        if (count($this->revisions) === 0) {
            $this->selectedRevisionId = null;
            $this->selectedRevision = null;
            $this->selectedDiff = null;

            return;
        }

        $revisionIds = collect($this->revisions)->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $targetRevisionId = $this->selectedRevisionId;

        if ($targetRevisionId === null || ! in_array($targetRevisionId, $revisionIds, true)) {
            $targetRevisionId = (int) $this->revisions[0]['id'];
        }

        $this->selectRevision($targetRevisionId, $snippetService, $snippetRevisionService);
    }

    public function updatedDiffMode(SnippetService $snippetService, SnippetRevisionService $snippetRevisionService): void
    {
        if (! in_array($this->diffMode, ['current', 'previous'], true)) {
            $this->diffMode = 'current';
        }

        if ($this->selectedRevisionId !== null) {
            $this->selectRevision($this->selectedRevisionId, $snippetService, $snippetRevisionService);
        }
    }

    public function selectRevision(int $revisionId, SnippetService $snippetService, SnippetRevisionService $snippetRevisionService): void
    {
        $snippet = $snippetService->find($this->snippetId);
        $this->authorize('update', $snippet);
        $revision = $snippetRevisionService->getRevisionForSnippet($snippet, $revisionId);

        if (! $revision) {
            return;
        }

        $this->selectedRevisionId = $revisionId;
        $this->selectedRevision = $revision;
        $this->selectedDiff = $this->diffMode === 'previous'
            ? $snippetRevisionService->buildRevisionDiffWithPrevious($snippet, $revisionId)
            : $snippetRevisionService->buildRevisionDiff($snippet, $revisionId);
    }

    public function confirmRollback(int $revisionId): void
    {
        $this->pendingRollbackRevisionId = $revisionId;
    }

    public function rollback(SnippetService $snippetService, SnippetRevisionService $snippetRevisionService): void
    {
        if (! $this->pendingRollbackRevisionId) {
            return;
        }

        $snippet = $snippetService->find($this->snippetId);
        $this->authorize('update', $snippet);
        $targetRevision = $snippetRevisionService->getRevisionForSnippet($snippet, $this->pendingRollbackRevisionId);

        $restored = $snippetService->rollbackToRevision($snippet, $this->pendingRollbackRevisionId);

        if (! $restored) {
            return;
        }

        $this->pendingRollbackRevisionId = null;
        $this->loadRevisions($snippetService, $snippetRevisionService);
        $this->dispatch('app-toast', type: 'success', message: __('snippets.revisions.rollback_done_text'));
        $this->dispatch(
            'snippet-rolled-back',
            snippetId: $this->snippetId,
            revisionVersion: $targetRevision['version'] ?? null
        );
    }

    public function render(): View
    {
        return view('livewire.snippets.revisions-panel');
    }
}
