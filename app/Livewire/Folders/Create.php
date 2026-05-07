<?php

namespace App\Livewire\Folders;

use App\Services\FolderService;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Create folder')]
class Create extends Component
{
    public string $name = '';
    public string $slug = '';
    public string $color = '#6366f1';

    public function save(FolderService $folders): void
    {
        $this->slug = $this->slug !== '' ? $this->slug : Str::slug($this->name);

        $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'slug' => ['required', 'string', 'min:2', 'max:120'],
            'color' => ['required', 'string', 'max:20'],
        ]);

        $folders->create([
            'user_id' => (int) auth()->id(),
            'name' => $this->name,
            'slug' => $this->slug,
            'color' => $this->color,
        ]);

        $this->dispatch('app-toast', type: 'success', message: __('folders.toast_created'));
        $this->redirect(route('folders.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.folders.create');
    }
}
