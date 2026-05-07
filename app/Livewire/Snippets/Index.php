<?php

namespace App\Livewire\Snippets;

use App\DTOs\SearchFilters;
use App\Services\FolderService;
use App\Repositories\Contracts\TagRepositoryInterface;
use App\Services\SavedSearchService;
use App\Services\SmartCollectionService;
use App\Services\SnippetService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Snippets')]
class Index extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $query = '';

    #[Url(history: true)]
    public string $language = '';

    /** @var list<string> */
    #[Url(history: true)]
    public array $selectedTags = [];

    #[Url(history: true)]
    public string $sortBy = 'updated_at';

    #[Url(history: true)]
    public string $sortDirection = 'desc';

    #[Url(history: true)]
    public string $visibility = 'all';

    #[Url(history: true)]
    public string $createdFrom = '';

    #[Url(history: true)]
    public string $createdTo = '';

    #[Url(history: true)]
    public string $updatedFrom = '';

    #[Url(history: true)]
    public string $updatedTo = '';

    /** @var list<int> */
    #[Url(history: true)]
    public array $folderIds = [];

    #[Url(history: true)]
    public string $smartCollectionId = '';

    public int $perPage = 12;

    public bool $filtersOpen = false;

    public string $savedSearchName = '';

    public ?int $activeSavedSearchId = null;

    public function toggleFilters(): void
    {
        $this->filtersOpen = ! $this->filtersOpen;
    }

    public function updatingQuery(): void
    {
        $this->resetPage();
    }

    public function updatingLanguage(): void
    {
        $this->resetPage();
    }

    public function updatingSortBy(): void
    {
        $this->resetPage();
    }

    public function updatingSortDirection(): void
    {
        $this->resetPage();
    }

    public function updatingVisibility(): void
    {
        $this->resetPage();
    }

    public function updatingCreatedFrom(): void
    {
        $this->resetPage();
    }

    public function updatingCreatedTo(): void
    {
        $this->resetPage();
    }

    public function updatingUpdatedFrom(): void
    {
        $this->resetPage();
    }

    public function updatingUpdatedTo(): void
    {
        $this->resetPage();
    }

    public function updatingFolderIds(): void
    {
        $this->resetPage();
    }

    public function updatingSmartCollectionId(): void
    {
        $this->resetPage();
    }

    public function toggleTag(string $slug): void
    {
        if (in_array($slug, $this->selectedTags, true)) {
            $this->selectedTags = array_values(array_diff($this->selectedTags, [$slug]));
        } else {
            $this->selectedTags[] = $slug;
        }
        $this->resetPage();
    }

    public function clearTagFilters(): void
    {
        $this->selectedTags = [];
        $this->resetPage();
    }

    public function clearAllFilters(): void
    {
        $this->query = '';
        $this->language = '';
        $this->selectedTags = [];
        $this->sortBy = 'updated_at';
        $this->sortDirection = 'desc';
        $this->visibility = 'all';
        $this->createdFrom = '';
        $this->createdTo = '';
        $this->updatedFrom = '';
        $this->updatedTo = '';
        $this->folderIds = [];
        $this->smartCollectionId = '';
        $this->activeSavedSearchId = null;
        $this->resetPage();
    }

    public function removeFilter(string $filter): void
    {
        match ($filter) {
            'query' => $this->query = '',
            'language' => $this->language = '',
            'visibility' => $this->visibility = 'all',
            'created_from' => $this->createdFrom = '',
            'created_to' => $this->createdTo = '',
            'updated_from' => $this->updatedFrom = '',
            'updated_to' => $this->updatedTo = '',
            'smart_collection_id' => $this->smartCollectionId = '',
            default => null,
        };

        if ($filter === 'folder_ids') {
            $this->folderIds = [];
        }

        $this->resetPage();
    }

    /**
     * @return list<array{key:string,label:string,value:string}>
     */
    public function activeFilterChips(): array
    {
        $chips = [];

        if ($this->query !== '') {
            $chips[] = ['key' => 'query', 'label' => __('snippets.index.search'), 'value' => $this->query];
        }
        if ($this->language !== '') {
            $chips[] = ['key' => 'language', 'label' => __('snippets.index.language'), 'value' => __('languages.'.$this->language)];
        }
        if ($this->visibility !== 'all') {
            $chips[] = ['key' => 'visibility', 'label' => __('snippets.index.visibility'), 'value' => $this->visibility === 'public'
                ? __('snippets.index.visibility_public')
                : __('snippets.index.visibility_private')];
        }
        if ($this->createdFrom !== '') {
            $chips[] = ['key' => 'created_from', 'label' => __('snippets.index.created_from'), 'value' => $this->createdFrom];
        }
        if ($this->createdTo !== '') {
            $chips[] = ['key' => 'created_to', 'label' => __('snippets.index.created_to'), 'value' => $this->createdTo];
        }
        if ($this->updatedFrom !== '') {
            $chips[] = ['key' => 'updated_from', 'label' => __('snippets.index.updated_from'), 'value' => $this->updatedFrom];
        }
        if ($this->updatedTo !== '') {
            $chips[] = ['key' => 'updated_to', 'label' => __('snippets.index.updated_to'), 'value' => $this->updatedTo];
        }
        if ($this->smartCollectionId !== '') {
            $chips[] = ['key' => 'smart_collection_id', 'label' => __('snippets.index.smart_collection'), 'value' => '#'.$this->smartCollectionId];
        }
        if ($this->folderIds !== []) {
            $chips[] = ['key' => 'folder_ids', 'label' => __('snippets.index.folders'), 'value' => implode(', ', $this->folderIds)];
        }

        return $chips;
    }

    public function saveCurrentSearch(SavedSearchService $savedSearchService): void
    {
        $name = trim($this->savedSearchName);
        if ($name === '' || auth()->id() === null) {
            return;
        }

        $savedSearch = $savedSearchService->createForUser(
            (int) auth()->id(),
            $name,
            $this->buildFilterPayload()
        );

        $this->activeSavedSearchId = $savedSearch->id;
        $this->savedSearchName = '';
    }

    public function applySavedSearch(int $savedSearchId, SavedSearchService $savedSearchService): void
    {
        if (auth()->id() === null) {
            return;
        }

        $savedSearch = $savedSearchService->findForUser((int) auth()->id(), $savedSearchId);
        if (! $savedSearch) {
            return;
        }

        $filters = SearchFilters::fromArray($savedSearch->filters_json ?? []);
        $this->query = $filters->query;
        $this->selectedTags = $filters->tags;
        $this->language = $filters->language ?? '';
        $this->sortBy = $filters->sortBy;
        $this->sortDirection = $filters->sortDirection;
        $this->createdFrom = $filters->createdFrom ?? '';
        $this->createdTo = $filters->createdTo ?? '';
        $this->updatedFrom = $filters->updatedFrom ?? '';
        $this->updatedTo = $filters->updatedTo ?? '';
        $this->folderIds = $filters->folderIds;
        $this->smartCollectionId = $filters->smartCollectionId !== null ? (string) $filters->smartCollectionId : '';
        $this->visibility = $filters->isPublic === null ? 'all' : ($filters->isPublic ? 'public' : 'private');
        $this->activeSavedSearchId = $savedSearch->id;
        $this->filtersOpen = true;
        $this->resetPage();
    }

    public function deleteSavedSearch(int $savedSearchId, SavedSearchService $savedSearchService): void
    {
        if (auth()->id() === null) {
            return;
        }

        $savedSearchService->deleteForUser((int) auth()->id(), $savedSearchId);
        if ($this->activeSavedSearchId === $savedSearchId) {
            $this->activeSavedSearchId = null;
        }
    }

    private function buildFilterPayload(): array
    {
        $payload = [
            'query' => $this->query,
            'tags' => $this->selectedTags,
            'language' => $this->language !== '' ? $this->language : null,
            'sort_by' => $this->sortBy,
            'sort_direction' => $this->sortDirection,
            'per_page' => $this->perPage,
            'page' => $this->getPage(),
            'created_from' => $this->createdFrom !== '' ? $this->createdFrom : null,
            'created_to' => $this->createdTo !== '' ? $this->createdTo : null,
            'updated_from' => $this->updatedFrom !== '' ? $this->updatedFrom : null,
            'updated_to' => $this->updatedTo !== '' ? $this->updatedTo : null,
            'folder_ids' => $this->folderIds,
            'smart_collection_id' => $this->smartCollectionId !== '' ? (int) $this->smartCollectionId : null,
        ];

        if ($this->visibility === 'public') {
            $payload['is_public'] = true;
        } elseif ($this->visibility === 'private') {
            $payload['is_public'] = false;
        }

        return $payload;
    }

    public function render(
        SnippetService $snippets,
        TagRepositoryInterface $tags,
        SavedSearchService $savedSearchService,
        FolderService $folders,
        SmartCollectionService $smartCollections
    )
    {
        $filters = SearchFilters::fromArray($this->buildFilterPayload());

        return view('livewire.snippets.index', [
            'snippets' => $snippets->findByUser((int) auth()->id(), $filters),
            'tagChips' => $tags->forUserSnippets((int) auth()->id()),
            'folders' => $folders->listForUser((int) auth()->id()),
            'smartCollections' => $smartCollections->listForUser((int) auth()->id()),
            'savedSearches' => $savedSearchService->listForUser((int) auth()->id()),
            'activeFilterChips' => $this->activeFilterChips(),
        ]);
    }
}
