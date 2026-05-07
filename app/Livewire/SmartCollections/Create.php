<?php

namespace App\Livewire\SmartCollections;

use App\Enums\SnippetLanguage;
use App\Services\SmartCollectionService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Create smart collection')]
class Create extends Component
{
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

        $collection = $collections->create([
            'user_id' => (int) auth()->id(),
            'name' => $this->name,
            'filters_json' => $this->buildFiltersPayload(),
            'is_system' => false,
        ]);

        $collections->rebuildMembershipForCollection($collection->id);
        $this->dispatch('app-toast', type: 'success', message: __('smart_collections.toast_created'));
        $this->redirect(route('smart-collections.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.smart-collections.create', [
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
