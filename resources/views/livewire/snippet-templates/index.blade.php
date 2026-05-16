<div class="flex w-full flex-col gap-4 p-3 sm:p-4 lg:p-6">
    <div class="flex items-center justify-between gap-2">
        <div>
            <flux:heading size="lg" level="1">{{ __('snippet_templates.title') }}</flux:heading>
            <flux:subheading class="mt-0.5 text-sm">{{ __('snippet_templates.subtitle') }}</flux:subheading>
        </div>
        <flux:button size="sm" variant="primary" :href="route('snippet-templates.create')" wire:navigate>
            {{ __('snippet_templates.create') }}
        </flux:button>
    </div>

    <flux:table :paginate="$templates">
        <flux:table.columns>
            <flux:table.column>{{ __('snippet_templates.col_name') }}</flux:table.column>
            <flux:table.column>{{ __('snippet_templates.col_language') }}</flux:table.column>
            <flux:table.column>{{ __('snippet_templates.col_variables') }}</flux:table.column>
            <flux:table.column align="end">{{ __('snippet_templates.col_actions') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($templates as $template)
                <flux:table.row :key="$template->id">
                    <flux:table.cell class="font-medium">{{ $template->name }}</flux:table.cell>
                    <flux:table.cell>{{ __('languages.'.$template->language) }}</flux:table.cell>
                    <flux:table.cell class="max-w-sm whitespace-normal">
                        <div class="flex flex-wrap gap-1">
                            @forelse ($template->template_variables as $var)
                                <span class="rounded bg-zinc-100 px-1.5 py-0.5 text-[11px] dark:bg-zinc-800">[[{{ $var }}]]</span>
                            @empty
                                <span class="text-xs text-zinc-500">{{ __('snippet_templates.no_variables') }}</span>
                            @endforelse
                        </div>
                    </flux:table.cell>
                    <flux:table.cell align="end" class="space-x-1">
                        <flux:button size="sm" variant="ghost" :href="route('snippets.create', ['template' => $template->id])" wire:navigate>{{ __('snippet_templates.use') }}</flux:button>
                        <flux:button size="sm" variant="ghost" :href="route('snippet-templates.edit', $template)" wire:navigate>{{ __('snippet_templates.edit') }}</flux:button>
                        <flux:button size="sm" variant="danger" wire:click="delete({{ $template->id }})" wire:confirm="{{ __('snippet_templates.delete_confirm') }}">{{ __('snippet_templates.delete') }}</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4" align="center" class="py-8 text-sm text-zinc-500">
                        {{ __('snippet_templates.empty') }}
                        <flux:link :href="route('snippet-templates.create')" wire:navigate class="ms-1">{{ __('snippet_templates.create_link') }}</flux:link>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
