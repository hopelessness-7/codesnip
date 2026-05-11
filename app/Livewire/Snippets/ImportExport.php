<?php

namespace App\Livewire\Snippets;

use App\DTOs\ImportOptionsData;
use App\Services\GithubGistTransferService;
use App\Services\SnippetService;
use App\Services\SnippetZipArchiveService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Import / Export')]
class ImportExport extends Component
{
    use WithFileUploads;

    public string $importSource = 'zip';

    public ?TemporaryUploadedFile $zipFile = null;

    public string $gistUrl = '';

    public string $githubToken = '';

    public string $onDuplicate = 'skip';

    public bool $preserveUuid = true;

    public string $defaultVisibility = 'keep';

    public bool $dryRun = false;

    /** @var list<int> */
    public array $exportSnippetIds = [];

    public ?string $gistDescription = null;

    public bool $selectAllForExport = true;

    public string $exportSnippetSearch = '';

    public function saveGithubToken(): void
    {
        $this->validate([
            'githubToken' => ['required', 'string', 'min:20', 'max:2048'],
        ]);

        $user = auth()->user();
        if (! $user) {
            return;
        }

        $user->github_personal_access_token = trim($this->githubToken);
        $user->save();
        $this->githubToken = '';

        $this->dispatch('app-toast', message: __('snippets.import.toast_token_saved'), type: 'success');
    }

    public function clearGithubToken(): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $user->github_personal_access_token = null;
        $user->save();

        $this->dispatch('app-toast', message: __('snippets.import.toast_token_removed'), type: 'success');
    }

    public function importFromZip(SnippetZipArchiveService $zipService): void
    {
        $this->validate([
            'zipFile' => ['required', 'file', 'mimes:zip', 'max:51200'],
        ]);

        $result = $zipService->importZip(
            auth()->user(),
            (string) $this->zipFile?->getRealPath(),
            $this->buildImportOptions()
        );

        $this->zipFile = null;
        $this->dispatchImportResultToast($result);
    }

    public function importFromGist(GithubGistTransferService $gistService): void
    {
        if (! $this->hasGithubToken()) {
            $this->dispatch('app-toast', message: __('snippets.import.gist_missing_token'), type: 'error');

            return;
        }

        $this->validate([
            'gistUrl' => ['required', 'string', 'min:8', 'max:500'],
        ]);

        try {
            $result = $gistService->importFromGistUrl(auth()->user(), $this->gistUrl, $this->buildImportOptions());
            $this->dispatchImportResultToast($result);
        } catch (\Throwable $e) {
            $this->dispatch('app-toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function exportZip(SnippetZipArchiveService $zipService)
    {
        $ids = $this->resolveExportSnippetIds();
        if ($ids === []) {
            $this->dispatch('app-toast', message: __('snippets.import.toast_select_snippets'), type: 'error');

            return null;
        }

        $path = $zipService->exportZip(auth()->user(), $ids);

        return response()->download($path, basename($path))->deleteFileAfterSend(true);
    }

    public function exportToGist(GithubGistTransferService $gistService): void
    {
        if (! $this->hasGithubToken()) {
            $this->dispatch('app-toast', message: __('snippets.import.gist_missing_token'), type: 'error');

            return;
        }

        $ids = $this->resolveExportSnippetIds();
        if ($ids === []) {
            $this->dispatch('app-toast', message: __('snippets.import.toast_select_snippets'), type: 'error');

            return;
        }

        try {
            $url = $gistService->exportToGist(auth()->user(), $ids, $this->gistDescription);
            $this->dispatch('app-toast', message: __('snippets.import.toast_gist_exported').': '.$url, type: 'success');
        } catch (\Throwable $e) {
            $this->dispatch('app-toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function updatedSelectAllForExport(bool $value): void
    {
        if ($value) {
            $this->exportSnippetIds = [];
        }
    }

    public function render(SnippetService $snippetService)
    {
        $userId = (int) auth()->id();
        $search = trim($this->exportSnippetSearch);

        $snippets = $search === ''
            ? $snippetService->getRecentByUser($userId, 3)
            : $snippetService->searchForUser($userId, $search, 12);

        return view('livewire.snippets.import-export', [
            'hasGithubToken' => $this->hasGithubToken(),
            'snippets' => $snippets,
        ]);
    }

    private function hasGithubToken(): bool
    {
        $token = (string) (auth()->user()?->github_personal_access_token ?? '');

        return trim($token) !== '';
    }

    private function buildImportOptions(): ImportOptionsData
    {
        return ImportOptionsData::fromArray([
            'on_duplicate' => $this->onDuplicate,
            'preserve_uuid' => $this->preserveUuid,
            'default_is_public' => $this->mapDefaultVisibility(),
            'dry_run' => $this->dryRun,
        ]);
    }

    private function dispatchImportResultToast(\App\DTOs\ImportResultData $result): void
    {
        $message = __('snippets.import.toast_import_result', [
            'created' => $result->created,
            'updated' => $result->updated,
            'skipped' => $result->skipped,
            'failed' => $result->failed,
        ]);

        $this->dispatch('app-toast', message: $message, type: $result->failed > 0 ? 'warning' : 'success');
    }

    /**
     * @return list<int>
     */
    private function resolveExportSnippetIds(): array
    {
        if ($this->selectAllForExport) {
            return [];
        }

        return collect($this->exportSnippetIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function mapDefaultVisibility(): ?bool
    {
        return match ($this->defaultVisibility) {
            'public' => true,
            'private' => false,
            default => null,
        };
    }
}
