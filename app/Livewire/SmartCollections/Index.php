<?php

namespace App\Livewire\SmartCollections;

use App\Services\SmartCollectionService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Smart collections')]
class Index extends Component
{
    use WithPagination;

    public int $perPage = 12;

    public function rebuild(int $collectionId, SmartCollectionService $collections): void
    {
        $collection = $collections->findForUser((int) auth()->id(), $collectionId);
        if (! $collection) {
            return;
        }

        $collections->rebuildMembershipForCollection($collection->id);
        $this->dispatch('app-toast', type: 'success', message: __('smart_collections.toast_rebuilt'));
    }

    public function delete(int $collectionId, SmartCollectionService $collections): void
    {
        $collection = $collections->findForUser((int) auth()->id(), $collectionId);
        if (! $collection) {
            return;
        }

        $collections->delete($collection);
        $this->resetPage();
        $this->dispatch('app-toast', type: 'success', message: __('smart_collections.toast_deleted'));
    }

    public function render(SmartCollectionService $collections)
    {
        return view('livewire.smart-collections.index', [
            'collections' => $collections->paginateForUser((int) auth()->id(), $this->perPage),
        ]);
    }
}
