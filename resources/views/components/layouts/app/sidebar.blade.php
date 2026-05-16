<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky stashable class="border-r border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('dashboard') }}" class="mr-5 flex items-center space-x-2" wire:navigate>
                <x-app-logo class="size-8" href="#"></x-app-logo>
            </a>

            <flux:navlist variant="outline">
                <flux:navlist.group :heading="__('nav.platform')" class="grid">
                    <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>{{ __('nav.dashboard') }}</flux:navlist.item>
                    <flux:navlist.item
                        icon="layout-grid"
                        :href="route('snippets.index')"
                        :current="request()->routeIs('snippets.index') || request()->routeIs('snippets.create') || request()->routeIs('snippets.edit') || request()->routeIs('snippets.import-export')"
                        wire:navigate
                    >{{ __('nav.snippets') }}</flux:navlist.item>
                    <flux:navlist.item
                        icon="arrow-up-tray"
                        :href="route('snippets.import-export')"
                        :current="request()->routeIs('snippets.import-export')"
                        wire:navigate
                    >{{ __('nav.snippets_import_export') }}</flux:navlist.item>
                    <flux:navlist.item
                        icon="document-duplicate"
                        :href="route('snippet-templates.index')"
                        :current="request()->routeIs('snippet-templates.index') || request()->routeIs('snippet-templates.create') || request()->routeIs('snippet-templates.edit')"
                        wire:navigate
                    >{{ __('nav.snippet_templates') }}</flux:navlist.item>
                    <flux:navlist.item
                        icon="folder"
                        :href="route('folders.index')"
                        :current="request()->routeIs('folders.index') || request()->routeIs('folders.create') || request()->routeIs('folders.edit')"
                        wire:navigate
                    >{{ __('nav.folders') }}</flux:navlist.item>
                    <flux:navlist.item
                        icon="funnel"
                        :href="route('smart-collections.index')"
                        :current="request()->routeIs('smart-collections.index') || request()->routeIs('smart-collections.create') || request()->routeIs('smart-collections.edit')"
                        wire:navigate
                    >{{ __('nav.smart_collections') }}</flux:navlist.item>
                    <flux:navlist.item icon="book-open-text" :href="route('tags.index')" :current="request()->routeIs('tags.index')" wire:navigate>{{ __('nav.tags') }}</flux:navlist.item>
                </flux:navlist.group>
            </flux:navlist>

            <flux:spacer />

            <flux:navlist variant="outline">
                <flux:navlist.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                    Repository
                </flux:navlist.item>

                <flux:navlist.item icon="book-open-text" href="https://laravel.com/docs/starter-kits" target="_blank">
                    Documentation
                </flux:navlist.item>
            </flux:navlist>

            <!-- Desktop User Menu -->
            <flux:dropdown position="bottom" align="start">
                <flux:profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevrons-up-down"
                />

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-left text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item href="/settings/profile" icon="cog" wire:navigate>{{ __('nav.settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('locale.switch', ['locale' => 'en'])" icon="language">{{ __('nav.locale_en') }}</flux:menu.item>
                        <flux:menu.item :href="route('locale.switch', ['locale' => 'ru'])" icon="language">{{ __('nav.locale_ru') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('nav.logout') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-left text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item href="/settings/profile" icon="cog" wire:navigate>{{ __('nav.settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('locale.switch', ['locale' => 'en'])" icon="language">{{ __('nav.locale_en') }}</flux:menu.item>
                        <flux:menu.item :href="route('locale.switch', ['locale' => 'ru'])" icon="language">{{ __('nav.locale_ru') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('nav.logout') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        <div
            x-data="{
                visible: false,
                message: '',
                type: 'success',
                queue: [],
                timer: null,
                duration: 2600,
                consume() {
                    if (this.visible || this.queue.length === 0) return;
                    const next = this.queue.shift();
                    this.message = next.message ?? '';
                    this.type = next.type ?? 'success';
                    this.visible = true;
                    if (this.timer) clearTimeout(this.timer);
                    this.timer = setTimeout(() => this.hide(), this.duration);
                },
                hide() {
                    this.visible = false;
                    if (this.timer) {
                        clearTimeout(this.timer);
                        this.timer = null;
                    }
                    setTimeout(() => this.consume(), 120);
                },
                show(detail) {
                    this.queue.push({
                        message: detail?.message ?? '',
                        type: detail?.type ?? 'success',
                    });
                    this.consume();
                }
            }"
            x-on:app-toast.window="show($event.detail)"
            x-show="visible"
            x-transition.opacity.duration.200ms
            class="fixed left-1/2 top-4 z-[100] -translate-x-1/2"
            style="display: none;"
        >
            <div
                class="flex min-w-[280px] max-w-[92vw] items-center justify-between gap-3 rounded-lg border px-4 py-3 text-sm font-medium shadow-xl"
                :class="{
                    'border-emerald-600/40 bg-emerald-900/95 text-emerald-100': type === 'success',
                    'border-rose-600/40 bg-rose-900/95 text-rose-100': type === 'error',
                    'border-zinc-600/40 bg-zinc-900/95 text-zinc-100': type !== 'success' && type !== 'error'
                }"
            >
                <span class="block overflow-hidden text-ellipsis whitespace-nowrap" x-text="message"></span>
                <button
                    type="button"
                    class="pointer-events-auto rounded px-1 py-0.5 text-base leading-none opacity-80 transition hover:opacity-100"
                    @click="hide()"
                    aria-label="Close notification"
                >
                    ×
                </button>
            </div>
        </div>

        @stack('scripts')

        @fluxScripts
    </body>
</html>
