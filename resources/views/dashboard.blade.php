<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-3 sm:p-4 lg:p-6">
        <div>
            <flux:heading size="lg" level="1">{{ __('dashboard.title') }}</flux:heading>
            <flux:subheading class="mt-0.5 text-sm">{{ __('dashboard.subtitle', ['app' => config('app.name')]) }}</flux:subheading>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('dashboard.kpi_snippets_total') }}</div>
                <div class="mt-1 text-2xl font-semibold">{{ $stats['snippets_total'] }}</div>
            </div>
            <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('dashboard.kpi_snippets_public') }}</div>
                <div class="mt-1 text-2xl font-semibold">{{ $stats['snippets_public'] }}</div>
            </div>
            <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('dashboard.kpi_folders') }}</div>
                <div class="mt-1 text-2xl font-semibold">{{ $stats['folders_total'] }}</div>
            </div>
            <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('dashboard.kpi_smart_collections') }}</div>
                <div class="mt-1 text-2xl font-semibold">{{ $stats['smart_collections_total'] }}</div>
            </div>
            <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('dashboard.kpi_without_folder') }}</div>
                <div class="mt-1 text-2xl font-semibold">{{ $stats['snippets_without_folder'] }}</div>
            </div>
        </div>

        <div class="grid gap-3 md:grid-cols-2">
            <a
                href="{{ route('snippets.index') }}"
                wire:navigate
                class="flex flex-col gap-1.5 rounded-lg border border-zinc-200 bg-white p-4 text-sm transition hover:border-zinc-300 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600"
            >
                <flux:heading size="md">{{ __('dashboard.card_snippets_title') }}</flux:heading>
                <flux:subheading class="text-sm">{{ __('dashboard.card_snippets_text') }}</flux:subheading>
                <span class="text-xs font-medium text-violet-600 dark:text-violet-400">{{ __('dashboard.card_snippets_link') }}</span>
            </a>
            <a
                href="{{ route('tags.index') }}"
                wire:navigate
                class="flex flex-col gap-1.5 rounded-lg border border-zinc-200 bg-white p-4 text-sm transition hover:border-zinc-300 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600"
            >
                <flux:heading size="md">{{ __('dashboard.card_tags_title') }}</flux:heading>
                <flux:subheading class="text-sm">{{ __('dashboard.card_tags_text') }}</flux:subheading>
                <span class="text-xs font-medium text-violet-600 dark:text-violet-400">{{ __('dashboard.card_tags_link') }}</span>
            </a>
        </div>

        <div class="flex flex-wrap gap-2">
            <flux:button size="sm" variant="primary" :href="route('snippets.create')" wire:navigate>{{ __('dashboard.btn_new') }}</flux:button>
            <flux:button size="sm" variant="ghost" :href="route('snippet-templates.index')" wire:navigate>{{ __('dashboard.btn_templates') }}</flux:button>
            <flux:button size="sm" variant="ghost" :href="route('snippets.index')" wire:navigate>{{ __('dashboard.btn_all') }}</flux:button>
            <flux:button size="sm" variant="ghost" :href="route('folders.create')" wire:navigate>{{ __('dashboard.btn_new_folder') }}</flux:button>
            <flux:button size="sm" variant="ghost" :href="route('smart-collections.create')" wire:navigate>{{ __('dashboard.btn_new_collection') }}</flux:button>
        </div>

        <div class="grid gap-3 lg:grid-cols-3">
            <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900 lg:col-span-2">
                <div class="mb-2">
                    <flux:heading size="md">{{ __('dashboard.recent_title') }}</flux:heading>
                    <flux:subheading class="text-sm">{{ __('dashboard.recent_subtitle') }}</flux:subheading>
                </div>
                <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($recentSnippets as $snippet)
                        <div class="flex items-center justify-between py-2">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-medium">{{ $snippet->title }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ __('languages.'.$snippet->language) }} · {{ $snippet->updated_at?->diffForHumans() }}
                                </div>
                            </div>
                            <flux:button size="xs" variant="ghost" :href="route('snippets.edit', $snippet)" wire:navigate>{{ __('dashboard.recent_open') }}</flux:button>
                        </div>
                    @empty
                        <div class="py-4 text-sm text-zinc-500 dark:text-zinc-400">{{ __('dashboard.recent_empty') }}</div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="mb-2">
                    <flux:heading size="md">{{ __('dashboard.top_tags_title') }}</flux:heading>
                    <flux:subheading class="text-sm">{{ __('dashboard.top_tags_subtitle') }}</flux:subheading>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    @forelse($topTags as $tag)
                        <a
                            href="{{ route('snippets.index', ['selectedTags' => [$tag->slug]]) }}"
                            wire:navigate
                            class="inline-flex items-center gap-1 rounded bg-zinc-100 px-2 py-1 text-xs text-zinc-700 transition hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700"
                        >
                            <span>{{ $tag->name }}</span>
                            <span class="opacity-70">({{ $tag->user_snippets_count }})</span>
                        </a>
                    @empty
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('dashboard.top_tags_empty') }}</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
