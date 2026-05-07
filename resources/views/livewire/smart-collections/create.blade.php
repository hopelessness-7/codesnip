<div class="flex w-full flex-col gap-4 p-3 sm:p-4 lg:p-6">
    <div>
        <flux:heading size="lg" level="1">{{ __('smart_collections.create_title') }}</flux:heading>
    </div>

    <form wire:submit="save" class="flex flex-col gap-3 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:input size="sm" wire:model="name" :label="__('smart_collections.field_name')" required />
        <flux:input size="sm" wire:model="query" :label="__('smart_collections.field_query')" />
        <flux:input size="sm" wire:model="tagsInput" :label="__('smart_collections.field_tags')" :placeholder="__('smart_collections.tags_hint')" />
        <div class="grid gap-2 sm:grid-cols-3">
            <flux:select size="sm" wire:model="language" :label="__('smart_collections.field_language')">
                <flux:select.option value="">{{ __('snippets.index.language_all') }}</flux:select.option>
                @foreach ($languages as $lang)
                    <flux:select.option value="{{ $lang->value }}">{{ __('languages.'.$lang->value) }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select size="sm" wire:model="visibility" :label="__('smart_collections.field_visibility')">
                <flux:select.option value="all">{{ __('snippets.index.visibility_all') }}</flux:select.option>
                <flux:select.option value="public">{{ __('snippets.index.visibility_public') }}</flux:select.option>
                <flux:select.option value="private">{{ __('snippets.index.visibility_private') }}</flux:select.option>
            </flux:select>
            <flux:select size="sm" wire:model="tagsMode" :label="__('smart_collections.field_tags_mode')">
                <flux:select.option value="all">{{ __('smart_collections.tags_mode_all') }}</flux:select.option>
                <flux:select.option value="any">{{ __('smart_collections.tags_mode_any') }}</flux:select.option>
            </flux:select>
        </div>
        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
            <flux:input size="sm" type="date" wire:model="createdFrom" :label="__('snippets.index.created_from')" />
            <flux:input size="sm" type="date" wire:model="createdTo" :label="__('snippets.index.created_to')" />
            <flux:input size="sm" type="date" wire:model="updatedFrom" :label="__('snippets.index.updated_from')" />
            <flux:input size="sm" type="date" wire:model="updatedTo" :label="__('snippets.index.updated_to')" />
        </div>
        <div class="flex gap-2">
            <flux:button size="sm" variant="primary" type="submit">{{ __('smart_collections.save') }}</flux:button>
            <flux:button size="sm" variant="ghost" :href="route('smart-collections.index')" wire:navigate>{{ __('smart_collections.cancel') }}</flux:button>
        </div>
    </form>
</div>
