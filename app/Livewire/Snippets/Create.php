<?php

namespace App\Livewire\Snippets;

use App\Enums\SnippetLanguage;
use App\Livewire\Concerns\ParsesSnippetTags;
use App\Services\SnippetService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('New snippet')]
class Create extends Component
{
    use ParsesSnippetTags;

    public string $title = '';

    public string $code = '';

    public string $language = 'php';

    public bool $is_public = false;

    public string $tagsInput = '';

    public function save(SnippetService $snippets): void
    {
        $this->validate([
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'code' => ['required', 'string'],
            'language' => ['required', 'string', 'in:'.collect(SnippetLanguage::cases())->map->value->implode(',')],
            'is_public' => ['boolean'],
            'tagsInput' => ['nullable', 'string', 'max:5000'],
        ]);

        $tags = $this->parseTagsFromInput($this->tagsInput);

        $snippets->create([
            'title' => $this->title,
            'code' => $this->code,
            'language' => $this->language,
            'tags' => $tags,
            'is_public' => $this->is_public,
            'user_id' => auth()->id(),
        ]);

        $this->redirect(route('snippets.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.snippets.create', [
            'languages' => SnippetLanguage::cases(),
        ]);
    }
}
