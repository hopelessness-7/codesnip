<?php

namespace App\Livewire\SnippetTemplates;

use App\Services\SnippetTemplateService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Snippet templates')]
class Index extends Component
{
    public function delete(int $templateId, SnippetTemplateService $templates): void
    {
        $template = $templates->findForUser((int) auth()->id(), $templateId);
        if (! $template) {
            return;
        }

        $templates->delete($template);
        $this->dispatch('app-toast', type: 'success', message: __('snippet_templates.toast_deleted'));
    }

    public function render(SnippetTemplateService $templates)
    {
        $items = $templates->listForUser((int) auth()->id())->map(function ($template) use ($templates) {
            $template->template_variables = $templates->extractVariables(
                (string) $template->title_template,
                (string) $template->code_template
            );

            return $template;
        });

        return view('livewire.snippet-templates.index', [
            'templates' => $items,
        ]);
    }
}
