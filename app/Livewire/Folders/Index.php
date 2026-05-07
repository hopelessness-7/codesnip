<?php

namespace App\Livewire\Folders;

use App\Services\FolderService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Folders')]
class Index extends Component
{
    public function delete(int $folderId, FolderService $folders): void
    {
        $folder = $folders->findForUser((int) auth()->id(), $folderId);
        if (! $folder) {
            return;
        }

        $folders->delete($folder);
        $this->dispatch('app-toast', type: 'success', message: __('folders.toast_deleted'));
    }

    public function render(FolderService $folders)
    {
        return view('livewire.folders.index', [
            'folders' => $folders->listForUser((int) auth()->id()),
        ]);
    }
}
