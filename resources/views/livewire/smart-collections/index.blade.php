<div class="flex w-full flex-col gap-4 p-3 sm:p-4 lg:p-6">
    <div class="flex items-center justify-between gap-2">
        <div>
            <flux:heading size="lg" level="1">{{ __('smart_collections.title') }}</flux:heading>
            <flux:subheading class="mt-0.5 text-sm">{{ __('smart_collections.subtitle') }}</flux:subheading>
        </div>
        <flux:button size="sm" variant="primary" :href="route('smart-collections.create')" wire:navigate>
            {{ __('smart_collections.create') }}
        </flux:button>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('smart_collections.col_name') }}</flux:table.column>
            <flux:table.column>{{ __('smart_collections.col_rules') }}</flux:table.column>
            <flux:table.column>{{ __('smart_collections.col_snippets') }}</flux:table.column>
            <flux:table.column align="end">{{ __('smart_collections.col_actions') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($collections as $collection)
                <flux:table.row :key="$collection->id">
                    <flux:table.cell class="font-medium">{{ $collection->name }}</flux:table.cell>
                    <flux:table.cell class="text-xs text-zinc-500">
                        {{ json_encode($collection->filters_json, JSON_UNESCAPED_UNICODE) }}
                    </flux:table.cell>
                    <flux:table.cell>{{ $collection->snippets()->count() }}</flux:table.cell>
                    <flux:table.cell align="end" class="space-x-1">
                        <flux:button
                            size="sm"
                            variant="ghost"
                            :href="route('snippets.index', ['smartCollectionId' => $collection->id])"
                            wire:navigate
                        >
                            {{ __('tags.index.view_snippets') }}
                        </flux:button>
                        <flux:button size="sm" variant="ghost" wire:click="rebuild({{ $collection->id }})">{{ __('smart_collections.rebuild') }}</flux:button>
                        <flux:button size="sm" variant="ghost" :href="route('smart-collections.edit', $collection)" wire:navigate>{{ __('smart_collections.edit') }}</flux:button>
                        <flux:button size="sm" variant="danger" wire:click="delete({{ $collection->id }})" wire:confirm="{{ __('smart_collections.delete_confirm') }}">{{ __('smart_collections.delete') }}</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4" align="center" class="py-8 text-sm text-zinc-500">
                        {{ __('smart_collections.empty') }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
