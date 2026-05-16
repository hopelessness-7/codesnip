<?php

namespace App\Livewire\Tags;

use App\Services\TagService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Tags')]
class Index extends Component
{
    use WithPagination;

    public int $perPage = 12;

    public function render(TagService $tags)
    {
        return view('livewire.tags.index', [
            'tags' => $tags->paginateForUserSnippets((int) auth()->id(), $this->perPage),
        ]);
    }
}
