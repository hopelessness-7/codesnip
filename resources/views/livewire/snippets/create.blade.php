<div class="flex w-full flex-col gap-4 p-3 sm:p-4 lg:p-6">
    <div>
        <flux:heading size="lg" level="1">{{ __('snippets.create.title') }}</flux:heading>
        <flux:subheading class="mt-0.5 text-sm">{{ __('snippets.create.subtitle') }}</flux:subheading>
    </div>

    <form wire:submit="save" class="flex flex-col gap-4 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:input size="sm" wire:model="title" :label="__('snippets.create.title_label')" required />

        <flux:select size="sm" wire:model.live="language" :label="__('snippets.create.language_label')" required>
            @foreach ($languages as $lang)
                <flux:select.option value="{{ $lang->value }}">{{ __('languages.'.$lang->value) }}</flux:select.option>
            @endforeach
        </flux:select>

        <div class="grid gap-1.5">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ __('snippets.create.code_label') }}</span>
                <div class="flex flex-wrap gap-1.5">
                    <flux:button size="xs" variant="ghost" type="button" x-on:click="window.CodeSnipEditor?.format('{{ $this->getId() }}')">Format</flux:button>
                    <flux:button size="xs" variant="ghost" type="button" x-on:click="window.CodeSnipEditor?.toggleWrap('{{ $this->getId() }}')">Wrap</flux:button>
                    <flux:button size="xs" variant="ghost" type="button" x-on:click="window.CodeSnipEditor?.copy('{{ $this->getId() }}')">Copy</flux:button>
                    <flux:button size="xs" variant="ghost" type="button" x-on:click="window.CodeSnipEditor?.clearDraft('{{ $this->getId() }}')">Clear draft</flux:button>
                </div>
            </div>
            <div
                wire:key="cm-{{ $language }}"
                wire:ignore
                class="min-h-[min(70vh,520px)] overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700"
                x-data
                x-init="
                    queueMicrotask(() => {
                        if (! window.CodeSnipEditor) return;
                        window.CodeSnipEditor.mount($refs.cm, '{{ $this->getId() }}', { property: 'code', language: @js($language), autosave: false });
                    });
                "
                x-ref="cm"
            ></div>
        </div>

        <flux:input
            size="sm"
            wire:model="tagsInput"
            :label="__('snippets.create.tags_label')"
            :description="__('snippets.create.tags_desc')"
            :placeholder="__('snippets.create.tags_ph')"
        />

        <div class="grid gap-2">
            <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ __('snippets.create.folders_label') }}</span>
            @if ($folders->isEmpty())
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('snippets.create.folders_empty') }}</p>
            @else
                <div class="flex flex-wrap gap-2">
                    @foreach ($folders as $folder)
                        <label class="inline-flex items-center gap-1.5 rounded border border-zinc-200 px-2 py-1 text-xs dark:border-zinc-700">
                            <input type="checkbox" wire:model="folderIds" value="{{ $folder->id }}" class="rounded border-zinc-300">
                            <span>{{ $folder->name }}</span>
                        </label>
                    @endforeach
                </div>
            @endif
        </div>

        <flux:checkbox wire:model="is_public" :label="__('snippets.create.public_label')" />

        <div class="flex flex-wrap gap-2">
            <flux:button size="sm" variant="primary" type="submit">{{ __('snippets.create.save') }}</flux:button>
            <flux:button size="sm" variant="ghost" :href="route('snippets.index')" wire:navigate type="button">{{ __('snippets.create.cancel') }}</flux:button>
        </div>
    </form>
</div>
