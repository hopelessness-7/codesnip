<div class="flex w-full flex-col gap-4 p-3 sm:p-4 lg:p-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="lg" level="1">{{ __('snippets.index.title') }}</flux:heading>
            <flux:subheading class="mt-0.5 text-sm">{{ __('snippets.index.subtitle') }}</flux:subheading>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <flux:button size="sm" variant="ghost" wire:click="toggleFilters" type="button">
                {{ $filtersOpen ? __('snippets.index.filters_hide') : __('snippets.index.filters_show') }}
            </flux:button>
            <flux:button size="sm" variant="primary" :href="route('snippets.create')" wire:navigate>{{ __('snippets.index.new') }}</flux:button>
        </div>
    </div>

    <div class="flex flex-col gap-2 rounded-lg border border-zinc-200 bg-white p-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-wrap items-center gap-2">
            <flux:input size="sm" wire:model="savedSearchName" :placeholder="__('snippets.index.saved_name_ph')" class="min-w-[220px]" />
            <flux:button size="sm" variant="primary" wire:click="saveCurrentSearch" type="button">{{ __('snippets.index.saved_save') }}</flux:button>
        </div>
        @if ($savedSearches->isNotEmpty())
            <div class="flex flex-wrap gap-1.5">
                @foreach ($savedSearches as $savedSearch)
                    <div class="inline-flex items-center gap-1 rounded border border-zinc-200 bg-zinc-50 p-1 dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:button
                            size="xs"
                            :variant="$activeSavedSearchId === $savedSearch->id ? 'primary' : 'ghost'"
                            wire:click="applySavedSearch({{ $savedSearch->id }})"
                            type="button"
                        >
                            {{ $savedSearch->name }}
                        </flux:button>
                        <flux:button size="xs" variant="ghost" wire:click="deleteSavedSearch({{ $savedSearch->id }})" type="button">×</flux:button>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @if (count($activeFilterChips) > 0 || count($selectedTags) > 0)
        <div class="flex flex-wrap items-center gap-1.5 rounded-lg border border-zinc-200 bg-white p-2 text-xs dark:border-zinc-700 dark:bg-zinc-900">
            @foreach ($activeFilterChips as $chip)
                <span class="inline-flex items-center gap-1 rounded bg-zinc-100 px-2 py-1 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                    <span class="font-medium">{{ $chip['label'] }}:</span> {{ $chip['value'] }}
                    <button type="button" class="ml-1 opacity-70 hover:opacity-100" wire:click="removeFilter('{{ $chip['key'] }}')">×</button>
                </span>
            @endforeach

            @foreach ($selectedTags as $selectedTag)
                <span class="inline-flex items-center gap-1 rounded bg-zinc-100 px-2 py-1 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                    <span class="font-medium">{{ __('snippets.index.tags_heading') }}:</span> {{ $selectedTag }}
                    <button type="button" class="ml-1 opacity-70 hover:opacity-100" wire:click="toggleTag('{{ $selectedTag }}')">×</button>
                </span>
            @endforeach

            <flux:button size="xs" variant="ghost" type="button" wire:click="clearAllFilters">{{ __('snippets.index.clear_all') }}</flux:button>
        </div>
    @endif

    @if ($filtersOpen)
        <div class="flex flex-col gap-3 rounded-lg border border-zinc-200 bg-white p-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                <flux:input size="sm" wire:model.live.debounce.400ms="query" :label="__('snippets.index.search')" :placeholder="__('snippets.index.search_ph')" />
                <flux:select size="sm" wire:model.live="language" :label="__('snippets.index.language')" :placeholder="__('snippets.index.language_all')">
                    <flux:select.option value="">{{ __('snippets.index.language_all') }}</flux:select.option>
                    @foreach (\App\Enums\SnippetLanguage::cases() as $lang)
                        <flux:select.option value="{{ $lang->value }}">{{ __('languages.'.$lang->value) }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select size="sm" wire:model.live="visibility" :label="__('snippets.index.visibility')">
                    <flux:select.option value="all">{{ __('snippets.index.visibility_all') }}</flux:select.option>
                    <flux:select.option value="public">{{ __('snippets.index.visibility_public') }}</flux:select.option>
                    <flux:select.option value="private">{{ __('snippets.index.visibility_private') }}</flux:select.option>
                </flux:select>
                <flux:select size="sm" wire:model.live="sortBy" :label="__('snippets.index.sort_by')">
                    <flux:select.option value="relevance">{{ __('snippets.index.sort_relevance') }}</flux:select.option>
                    <flux:select.option value="updated_at">{{ __('snippets.index.sort_updated') }}</flux:select.option>
                    <flux:select.option value="created_at">{{ __('snippets.index.sort_created') }}</flux:select.option>
                    <flux:select.option value="title">{{ __('snippets.index.sort_title') }}</flux:select.option>
                    <flux:select.option value="language">{{ __('snippets.index.sort_language') }}</flux:select.option>
                </flux:select>
            </div>
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                <flux:input size="sm" type="date" wire:model.live="createdFrom" :label="__('snippets.index.created_from')" />
                <flux:input size="sm" type="date" wire:model.live="createdTo" :label="__('snippets.index.created_to')" />
                <flux:input size="sm" type="date" wire:model.live="updatedFrom" :label="__('snippets.index.updated_from')" />
                <flux:input size="sm" type="date" wire:model.live="updatedTo" :label="__('snippets.index.updated_to')" />
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('snippets.index.sort_dir') }}:</span>
                <flux:radio.group wire:model.live="sortDirection" variant="segmented">
                    <flux:radio value="desc">{{ __('snippets.index.sort_desc') }}</flux:radio>
                    <flux:radio value="asc">{{ __('snippets.index.sort_asc') }}</flux:radio>
                </flux:radio.group>
            </div>
            @if ($tagChips->isNotEmpty())
                <div>
                    <div class="mb-1.5 flex items-center justify-between gap-2">
                        <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('snippets.index.tags_heading') }}</span>
                        @if (count($selectedTags) > 0)
                            <flux:button size="xs" variant="ghost" wire:click="clearTagFilters">{{ __('snippets.index.tags_clear') }}</flux:button>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($tagChips as $tag)
                            <flux:button
                                size="xs"
                                :variant="in_array($tag->slug, $selectedTags, true) ? 'primary' : 'ghost'"
                                wire:click="toggleTag({{ json_encode($tag->slug) }})"
                            >
                                {{ $tag->name }}
                                <span class="ml-0.5 text-[10px] opacity-80">({{ $tag->user_snippets_count }})</span>
                            </flux:button>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    <flux:table :paginate="$snippets">
        <flux:table.columns>
            <flux:table.column>{{ __('snippets.index.col_title') }}</flux:table.column>
            <flux:table.column>{{ __('snippets.index.col_language') }}</flux:table.column>
            <flux:table.column>{{ __('snippets.index.col_tags') }}</flux:table.column>
            <flux:table.column>{{ __('snippets.index.col_updated') }}</flux:table.column>
            <flux:table.column align="end">{{ __('snippets.index.col_actions') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($snippets as $snippet)
                <flux:table.row :key="$snippet->id">
                    <flux:table.cell class="font-medium">
                        <div class="flex flex-col gap-0.5">
                            <span>{{ $snippet->title }}</span>
                            @if ($snippet->is_public)
                                <span class="inline-flex w-fit rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">{{ __('snippets.index.public') }}</span>
                            @endif
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        @php
                            $lv = $snippet->language instanceof \App\Enums\SnippetLanguage ? $snippet->language->value : (string) ($snippet->language ?? 'unknown');
                        @endphp
                        <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('languages.'.$lv) }}</span>
                    </flux:table.cell>
                    <flux:table.cell class="max-w-xs whitespace-normal">
                        <div class="flex flex-wrap gap-1">
                            @foreach ($snippet->tags as $tag)
                                <span class="rounded bg-zinc-100 px-1.5 py-0.5 text-[11px] text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">{{ $tag->name }}</span>
                            @endforeach
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ $snippet->updated_at?->diffForHumans() }}
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        @can('update', $snippet)
                            <flux:button size="sm" variant="ghost" :href="route('snippets.edit', $snippet)" wire:navigate>{{ __('snippets.index.edit') }}</flux:button>
                        @endcan
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" align="center" class="py-8 text-sm text-zinc-500">
                        {{ __('snippets.index.empty') }}
                        <flux:link :href="route('snippets.create')" wire:navigate class="ms-1">{{ __('snippets.index.create_link') }}</flux:link>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
