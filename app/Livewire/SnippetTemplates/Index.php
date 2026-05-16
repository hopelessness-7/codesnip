<?php

namespace App\Livewire\SnippetTemplates;

use App\Services\SnippetTemplateService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Snippet templates')]
class Index extends Component
{
    use WithPagination;

    public int $perPage = 12;

    public function delete(int $templateId, SnippetTemplateService $templates): void
    {
        $template = $templates->findForUser((int) auth()->id(), $templateId);
        if (! $template) {
            return;
        }

        $templates->delete($template);
        $this->resetPage();
        $this->dispatch('app-toast', type: 'success', message: __('snippet_templates.toast_deleted'));
    }

    public function render(SnippetTemplateService $templates)
    {
        return view('livewire.snippet-templates.index', [
            'templates' => $templates->paginateForUser((int) auth()->id(), $this->perPage),
        ]);
    }
}
