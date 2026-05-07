<div class="flex w-full flex-col gap-4 p-3 sm:p-4 lg:p-6">
    <div>
        <flux:heading size="lg" level="1">{{ __('tags.index.title') }}</flux:heading>
        <flux:subheading class="mt-0.5 text-sm">{{ __('tags.index.subtitle') }}</flux:subheading>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('tags.index.col_name') }}</flux:table.column>
            <flux:table.column>{{ __('tags.index.col_slug') }}</flux:table.column>
            <flux:table.column align="end">{{ __('tags.index.col_count') }}</flux:table.column>
            <flux:table.column>{{ __('tags.index.col_source') }}</flux:table.column>
            <flux:table.column align="end">{{ __('tags.index.col_actions') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($tags as $tag)
                <flux:table.row :key="$tag->id">
                    <flux:table.cell class="font-medium">{{ $tag->name }}</flux:table.cell>
                    <flux:table.cell class="font-mono text-xs text-zinc-500">{{ $tag->slug }}</flux:table.cell>
                    <flux:table.cell align="end">{{ $tag->user_snippets_count }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($tag->is_ai_generated)
                            <span class="text-xs text-violet-600 dark:text-violet-400">{{ __('tags.index.source_ai') }}</span>
                        @else
                            <span class="text-xs text-zinc-500">{{ __('tags.index.source_manual') }}</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <flux:button
                            size="sm"
                            variant="ghost"
                            :href="route('snippets.index', ['selectedTags' => [$tag->slug]])"
                            wire:navigate
                        >
                            {{ __('tags.index.view_snippets') }}
                        </flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" align="center" class="py-8 text-sm text-zinc-500">
                        {{ __('tags.index.empty') }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
