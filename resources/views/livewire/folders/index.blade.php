<div class="flex w-full flex-col gap-4 p-3 sm:p-4 lg:p-6">
    <div class="flex items-center justify-between gap-2">
        <div>
            <flux:heading size="lg" level="1">{{ __('folders.title') }}</flux:heading>
            <flux:subheading class="mt-0.5 text-sm">{{ __('folders.subtitle') }}</flux:subheading>
        </div>
        <flux:button size="sm" variant="primary" :href="route('folders.create')" wire:navigate>
            {{ __('folders.create') }}
        </flux:button>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('folders.col_name') }}</flux:table.column>
            <flux:table.column>{{ __('folders.col_slug') }}</flux:table.column>
            <flux:table.column>{{ __('folders.col_snippets') }}</flux:table.column>
            <flux:table.column align="end">{{ __('folders.col_actions') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($folders as $folder)
                <flux:table.row :key="$folder->id">
                    <flux:table.cell class="font-medium">{{ $folder->name }}</flux:table.cell>
                    <flux:table.cell class="text-xs text-zinc-500">{{ $folder->slug }}</flux:table.cell>
                    <flux:table.cell>{{ $folder->snippets()->count() }}</flux:table.cell>
                    <flux:table.cell align="end" class="space-x-1">
                        <flux:button
                            size="sm"
                            variant="ghost"
                            :href="route('snippets.index', ['folderIds' => [$folder->id]])"
                            wire:navigate
                        >
                            {{ __('tags.index.view_snippets') }}
                        </flux:button>
                        <flux:button size="sm" variant="ghost" :href="route('folders.edit', $folder)" wire:navigate>{{ __('folders.edit') }}</flux:button>
                        <flux:button size="sm" variant="danger" wire:click="delete({{ $folder->id }})" wire:confirm="{{ __('folders.delete_confirm') }}">{{ __('folders.delete') }}</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4" align="center" class="py-8 text-sm text-zinc-500">
                        {{ __('folders.empty') }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
