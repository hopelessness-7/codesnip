<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-3 sm:p-4 lg:p-6">
        <div>
            <flux:heading size="lg" level="1">{{ __('dashboard.title') }}</flux:heading>
            <flux:subheading class="mt-0.5 text-sm">{{ __('dashboard.subtitle', ['app' => config('app.name')]) }}</flux:subheading>
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
            <flux:button size="sm" variant="ghost" :href="route('snippets.index')" wire:navigate>{{ __('dashboard.btn_all') }}</flux:button>
        </div>
    </div>
</x-layouts.app>
