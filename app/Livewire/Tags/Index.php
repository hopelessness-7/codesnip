<?php

namespace App\Livewire\Tags;

use App\Repositories\Contracts\TagRepositoryInterface;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Tags')]
class Index extends Component
{
    public function render(TagRepositoryInterface $tags)
    {
        return view('livewire.tags.index', [
            'tags' => $tags->forUserSnippets((int) auth()->id()),
        ]);
    }
}
