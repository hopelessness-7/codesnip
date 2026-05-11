<div class="flex w-full flex-col gap-4 p-3 sm:p-4 lg:p-6">
    <div>
        <flux:heading size="lg" level="1">{{ __('snippets.import.title') }}</flux:heading>
        <flux:subheading class="mt-0.5 text-sm">{{ __('snippets.import.subtitle') }}</flux:subheading>
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:select size="sm" wire:model.live="importSource" :label="__('snippets.import.source_label')">
            <flux:select.option value="zip">ZIP</flux:select.option>
            <flux:select.option value="gist">GitHub Gist</flux:select.option>
        </flux:select>

        <div class="mt-3 grid gap-2 rounded border border-zinc-200 p-3 dark:border-zinc-700 sm:grid-cols-2">
            <flux:select size="sm" wire:model.live="onDuplicate" :label="__('snippets.import.on_duplicate_label')">
                <flux:select.option value="skip">{{ __('snippets.import.on_duplicate_skip') }}</flux:select.option>
                <flux:select.option value="update">{{ __('snippets.import.on_duplicate_update') }}</flux:select.option>
                <flux:select.option value="create_copy">{{ __('snippets.import.on_duplicate_copy') }}</flux:select.option>
            </flux:select>

            <flux:select size="sm" wire:model.live="defaultVisibility" :label="__('snippets.import.default_visibility_label')">
                <flux:select.option value="keep">{{ __('snippets.import.default_visibility_keep') }}</flux:select.option>
                <flux:select.option value="public">{{ __('snippets.import.default_visibility_public') }}</flux:select.option>
                <flux:select.option value="private">{{ __('snippets.import.default_visibility_private') }}</flux:select.option>
            </flux:select>

            <flux:checkbox wire:model.live="preserveUuid" :label="__('snippets.import.preserve_uuid')" />
            <flux:checkbox wire:model.live="dryRun" :label="__('snippets.import.dry_run')" />
        </div>

        @if ($importSource === 'gist')
            <div class="mt-3 flex flex-col gap-3 rounded border border-zinc-200 p-3 dark:border-zinc-700">
                @if ($hasGithubToken)
                    <div class="rounded border border-emerald-700/40 bg-emerald-900/30 px-3 py-2 text-sm text-emerald-200">
                        {{ __('snippets.import.gist_token_present') }}
                    </div>
                @else
                    <div class="rounded border border-rose-700/40 bg-rose-900/30 px-3 py-2 text-sm text-rose-200">
                        {{ __('snippets.import.gist_missing_token') }}
                    </div>
                @endif

                <div class="grid gap-2">
                    <flux:input
                        size="sm"
                        wire:model.defer="githubToken"
                        type="password"
                        :label="__('snippets.import.github_token_label')"
                        :placeholder="__('snippets.import.github_token_placeholder')"
                    />
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:button size="sm" variant="primary" wire:click="saveGithubToken" type="button">
                            {{ __('snippets.import.save_token') }}
                        </flux:button>
                        @if ($hasGithubToken)
                            <flux:button size="sm" variant="ghost" wire:click="clearGithubToken" type="button">
                                {{ __('snippets.import.remove_token') }}
                            </flux:button>
                        @endif
                    </div>
                </div>

                <flux:input
                    size="sm"
                    wire:model.defer="gistUrl"
                    :label="__('snippets.import.gist_url_label')"
                    placeholder="https://gist.github.com/..."
                />
                <flux:button size="sm" variant="primary" wire:click="importFromGist" type="button">
                    {{ __('snippets.import.import_gist') }}
                </flux:button>
            </div>
        @endif

        @if ($importSource === 'zip')
            <div class="mt-3 flex flex-col gap-3 rounded border border-zinc-200 p-3 dark:border-zinc-700">
                <flux:input
                    size="sm"
                    wire:model="zipFile"
                    type="file"
                    accept=".zip"
                    :label="__('snippets.import.zip_file_label')"
                />
                <flux:button size="sm" variant="primary" wire:click="importFromZip" type="button">
                    {{ __('snippets.import.import_zip') }}
                </flux:button>
            </div>
        @endif
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="md">{{ __('snippets.import.export_title') }}</flux:heading>
        <flux:subheading class="mt-1 text-sm">{{ __('snippets.import.export_subtitle') }}</flux:subheading>

        <div class="mt-3 grid gap-2 rounded border border-zinc-200 p-3 dark:border-zinc-700">
            <flux:checkbox wire:model.live="selectAllForExport" :label="__('snippets.import.export_all_label')" />
            @if (! $selectAllForExport)
                <flux:input
                    size="sm"
                    wire:model.live.debounce.300ms="exportSnippetSearch"
                    :label="__('snippets.import.export_search_label')"
                    :placeholder="__('snippets.import.export_search_placeholder')"
                />
                @if ($exportSnippetSearch === '')
                    <p class="text-xs text-zinc-500">{{ __('snippets.import.export_showing_first_three') }}</p>
                @endif
                <div class="max-h-56 overflow-y-auto rounded border border-zinc-200 p-2 dark:border-zinc-700">
                    @forelse ($snippets as $snippet)
                        <label class="mb-1 flex items-center gap-2 rounded px-1 py-1 text-sm text-zinc-800 transition-colors hover:bg-zinc-100/70 dark:text-zinc-200 dark:hover:bg-zinc-700/40">
                            <input type="checkbox" wire:model.live="exportSnippetIds" value="{{ $snippet->id }}" class="rounded border-zinc-300">
                            <span class="truncate">{{ $snippet->title }}</span>
                            <span class="ml-auto text-xs text-zinc-500">#{{ $snippet->id }}</span>
                        </label>
                    @empty
                        <p class="text-sm text-zinc-500">{{ __('snippets.import.no_snippets') }}</p>
                    @endforelse
                </div>
            @endif
        </div>

        <div class="mt-3 flex flex-wrap gap-2">
            <flux:button size="sm" variant="primary" wire:click="exportZip" type="button">
                {{ __('snippets.import.export_zip') }}
            </flux:button>
            <flux:button size="sm" variant="ghost" wire:click="exportToGist" type="button">
                {{ __('snippets.import.export_gist') }}
            </flux:button>
        </div>
    </div>
</div>
