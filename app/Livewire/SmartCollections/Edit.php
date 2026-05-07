<?php

namespace App\Livewire\SmartCollections;

use App\Enums\SnippetLanguage;
use App\Models\SmartCollection;
use App\Services\SmartCollectionService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Edit smart collection')]
class Edit extends Component
{
    public SmartCollection $collection;
    public string $name = '';
    public string $language = '';
    public string $visibility = 'all';
    public string $tagsInput = '';
    public string $tagsMode = 'all';
    public string $query = '';
    public string $createdFrom = '';
    public string $createdTo = '';
    public string $updatedFrom = '';
    public string $updatedTo = '';

    public function mount(SmartCollection $smartCollection): void
    {
        abort_unless($smartCollection->user_id === (int) auth()->id(), 404);
        $this->collection = $smartCollection;

        $filters = $smartCollection->filters_json ?? [];
        $this->name = $smartCollection->name;
        $this->language = (string) ($filters['language'] ?? '');
        $this->visibility = ! array_key_exists('is_public', $filters) || $filters['is_public'] === null
            ? 'all'
            : ((bool) $filters['is_public'] ? 'public' : 'private');
        $this->tagsInput = collect($filters['tags'] ?? [])->implode(', ');
        $this->tagsMode = in_array(($filters['tags_mode'] ?? 'all'), ['all', 'any'], true) ? $filters['tags_mode'] : 'all';
        $this->query = (string) ($filters['query'] ?? '');
        $this->createdFrom = (string) ($filters['created_from'] ?? '');
        $this->createdTo = (string) ($filters['created_to'] ?? '');
        $this->updatedFrom = (string) ($filters['updated_from'] ?? '');
        $this->updatedTo = (string) ($filters['updated_to'] ?? '');
    }

    public function save(SmartCollectionService $collections): void
    {
        $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'language' => ['nullable', 'string', 'in:'.collect(SnippetLanguage::cases())->map->value->implode(',')],
            'visibility' => ['required', 'in:all,public,private'],
            'tagsInput' => ['nullable', 'string', 'max:1000'],
            'tagsMode' => ['required', 'in:all,any'],
            'query' => ['nullable', 'string', 'max:200'],
            'createdFrom' => ['nullable', 'date'],
            'createdTo' => ['nullable', 'date'],
            'updatedFrom' => ['nullable', 'date'],
            'updatedTo' => ['nullable', 'date'],
        ]);

        $collections->update($this->collection, [
            'name' => $this->name,
            'filters_json' => $this->buildFiltersPayload(),
        ]);

        $collections->rebuildMembershipForCollection($this->collection->id);
        $this->dispatch('app-toast', type: 'success', message: __('smart_collections.toast_updated'));
        $this->redirect(route('smart-collections.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.smart-collections.edit', [
            'languages' => SnippetLanguage::cases(),
        ]);
    }

    private function buildFiltersPayload(): array
    {
        $tags = collect(preg_split('/[,\n;]+/', $this->tagsInput) ?: [])
            ->map(fn ($tag) => mb_strtolower(trim((string) $tag)))
            ->filter()
            ->values()
            ->all();

        return [
            'language' => $this->language !== '' ? $this->language : null,
            'is_public' => $this->visibility === 'all' ? null : $this->visibility === 'public',
            'tags' => $tags,
            'tags_mode' => $this->tagsMode,
            'query' => trim($this->query),
            'created_from' => $this->createdFrom !== '' ? $this->createdFrom : null,
            'created_to' => $this->createdTo !== '' ? $this->createdTo : null,
            'updated_from' => $this->updatedFrom !== '' ? $this->updatedFrom : null,
            'updated_to' => $this->updatedTo !== '' ? $this->updatedTo : null,
        ];
    }
}
