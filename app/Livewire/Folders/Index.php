<?php

namespace App\Livewire\Folders;

use App\Services\FolderService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Folders')]
class Index extends Component
{
    use WithPagination;

    public int $perPage = 12;

    public function delete(int $folderId, FolderService $folders): void
    {
        $folder = $folders->findForUser((int) auth()->id(), $folderId);
        if (! $folder) {
            return;
        }

        $folders->delete($folder);
        $this->resetPage();
        $this->dispatch('app-toast', type: 'success', message: __('folders.toast_deleted'));
    }

    public function render(FolderService $folders)
    {
        return view('livewire.folders.index', [
            'folders' => $folders->paginateForUser((int) auth()->id(), $this->perPage),
        ]);
    }
}
