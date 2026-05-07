<div class="flex w-full flex-col gap-4 p-3 sm:p-4 lg:p-6">
    <div>
        <flux:heading size="lg" level="1">{{ __('folders.create_title') }}</flux:heading>
    </div>

    <form wire:submit="save" class="flex max-w-2xl flex-col gap-3 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:input size="sm" wire:model="name" :label="__('folders.field_name')" required />
        <flux:input size="sm" wire:model="slug" :label="__('folders.field_slug')" :placeholder="__('folders.slug_hint')" />
        <flux:input size="sm" wire:model="color" :label="__('folders.field_color')" />

        <div class="flex gap-2">
            <flux:button size="sm" variant="primary" type="submit">{{ __('folders.save') }}</flux:button>
            <flux:button size="sm" variant="ghost" :href="route('folders.index')" wire:navigate>{{ __('folders.cancel') }}</flux:button>
        </div>
    </form>
</div>
