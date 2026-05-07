<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @php($title = $snippet->title . ' - ' . config('app.name', 'CodeSnip'))
    @include('partials.head')
</head>
<body class="min-h-screen bg-zinc-900 text-zinc-100 antialiased">
    <main class="mx-auto flex w-full max-w-6xl flex-col gap-4 p-3 sm:p-4 lg:p-8">
        <header class="rounded-xl border border-zinc-700 bg-zinc-800 p-4">
            <div class="mb-3 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-wide text-zinc-500">{{ config('app.name', 'CodeSnip') }}</p>
                    <h1 class="mt-1 text-lg font-semibold sm:text-xl">{{ $snippet->title }}</h1>
                </div>
                <div class="flex items-center gap-2">
                    <span class="rounded-md border border-zinc-700 bg-zinc-800 px-2 py-1 text-xs font-medium text-zinc-300">
                        {{ $snippet->language_label }}
                    </span>
                    <button
                        id="copy-code-btn"
                        type="button"
                        class="rounded-md border border-zinc-700 bg-zinc-800 px-2 py-1 text-xs font-medium text-zinc-200 transition hover:bg-zinc-700"
                        onclick="window.copyPublicSnippetCode()"
                    >
                        Copy code
                    </button>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3 text-xs text-zinc-400">
                <span>{{ __('snippets.revisions.updated') }}: {{ optional($snippet->updated_at)->format('Y-m-d H:i') }}</span>
                <span class="text-zinc-600">•</span>
                <span>{{ \Illuminate\Support\Str::of($snippet->code)->explode(PHP_EOL)->count() }} lines</span>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                <span id="selected-line-label" class="rounded-md border border-zinc-700 bg-zinc-900 px-2 py-1 text-zinc-300">
                    Line: —
                </span>
                <button
                    id="copy-line-btn"
                    type="button"
                    class="rounded-md border border-zinc-700 bg-zinc-900 px-2 py-1 font-medium text-zinc-200 transition hover:bg-zinc-700"
                    onclick="window.copyPublicSnippetLine()"
                    disabled
                >
                    Copy line
                </button>
                <button
                    id="link-line-btn"
                    type="button"
                    class="rounded-md border border-zinc-700 bg-zinc-900 px-2 py-1 font-medium text-zinc-200 transition hover:bg-zinc-700"
                    onclick="window.copyPublicSnippetLineLink()"
                    disabled
                >
                    Link to line
                </button>
            </div>
        </header>

        <section class="overflow-hidden rounded-xl border border-zinc-700 bg-zinc-800">
            <div
                id="public-code-editor"
                class="min-h-[min(72vh,720px)] overflow-hidden"
            ></div>
        </section>
    </main>

    <script>
        window.publicSnippetEditorState = {
            selectedLine: null,
            view: null,
        };

        window.selectPublicSnippetLine = function selectPublicSnippetLine(lineNumber) {
            const state = window.publicSnippetEditorState;
            const label = document.getElementById('selected-line-label');
            const copyBtn = document.getElementById('copy-line-btn');
            const linkBtn = document.getElementById('link-line-btn');

            if (!state.view || !lineNumber || lineNumber < 1 || lineNumber > state.view.state.doc.lines) {
                state.selectedLine = null;
                if (label) label.textContent = 'Line: —';
                if (copyBtn) copyBtn.disabled = true;
                if (linkBtn) linkBtn.disabled = true;
                return;
            }

            const line = state.view.state.doc.line(lineNumber);
            state.selectedLine = lineNumber;
            state.view.dispatch({
                selection: { anchor: line.from, head: line.to },
            });

            if (label) label.textContent = `Line: ${lineNumber}`;
            if (copyBtn) copyBtn.disabled = false;
            if (linkBtn) linkBtn.disabled = false;
        };

        window.initPublicSnippetEditor = function initPublicSnippetEditor() {
            const host = document.getElementById('public-code-editor');
            if (!host || !window.CodeSnipEditor?.mountReadonly) return;

            window.CodeSnipEditor.mountReadonly(host, {
                code: @js($snippet->code),
                language: @js($snippet->language),
                minHeight: 'min(72vh, 720px)',
                lineWrapping: false,
            });

            const view = host._cmView;
            window.publicSnippetEditorState.view = view ?? null;
            if (!view) return;

            host.addEventListener('click', (event) => {
                const lineEl = event.target?.closest?.('.cm-line');
                if (!lineEl) return;

                try {
                    const pos = view.posAtDOM(lineEl, 0);
                    const line = view.state.doc.lineAt(pos);
                    window.selectPublicSnippetLine(line.number);
                } catch {
                    //
                }
            });

            const hashMatch = (window.location.hash || '').match(/^#L(\d+)$/);
            if (hashMatch) {
                const n = Number(hashMatch[1]);
                if (Number.isFinite(n)) {
                    window.selectPublicSnippetLine(n);
                }
            }
        };

        window.copyPublicSnippetCode = async function copyPublicSnippetCode() {
            const btn = document.getElementById('copy-code-btn');
            const text = @js($snippet->code);
            const original = btn ? btn.textContent.trim() : 'Copy code';

            const setBtn = (label) => {
                if (btn) btn.textContent = label;
            };

            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(text);
                } else {
                    const ta = document.createElement('textarea');
                    ta.value = text;
                    ta.setAttribute('readonly', '');
                    ta.style.position = 'absolute';
                    ta.style.left = '-9999px';
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                }

                setBtn('Copied');
                setTimeout(() => setBtn(original), 1200);
            } catch {
                setBtn('Copy failed');
                setTimeout(() => setBtn(original), 1400);
            }
        };

        window.copyPublicSnippetLine = async function copyPublicSnippetLine() {
            const state = window.publicSnippetEditorState;
            const btn = document.getElementById('copy-line-btn');
            const original = btn ? btn.textContent.trim() : 'Copy line';

            if (!state.view || !state.selectedLine) return;
            const line = state.view.state.doc.line(state.selectedLine).text;

            const setBtn = (label) => {
                if (btn) btn.textContent = label;
            };

            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(line);
                } else {
                    const ta = document.createElement('textarea');
                    ta.value = line;
                    ta.setAttribute('readonly', '');
                    ta.style.position = 'absolute';
                    ta.style.left = '-9999px';
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                }
                setBtn('Copied');
                setTimeout(() => setBtn(original), 1200);
            } catch {
                setBtn('Copy failed');
                setTimeout(() => setBtn(original), 1400);
            }
        };

        window.copyPublicSnippetLineLink = async function copyPublicSnippetLineLink() {
            const state = window.publicSnippetEditorState;
            const btn = document.getElementById('link-line-btn');
            const original = btn ? btn.textContent.trim() : 'Link to line';

            if (!state.selectedLine) return;
            const url = `${window.location.origin}${window.location.pathname}#L${state.selectedLine}`;
            window.location.hash = `L${state.selectedLine}`;

            const setBtn = (label) => {
                if (btn) btn.textContent = label;
            };

            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(url);
                } else {
                    const ta = document.createElement('textarea');
                    ta.value = url;
                    ta.setAttribute('readonly', '');
                    ta.style.position = 'absolute';
                    ta.style.left = '-9999px';
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                }
                setBtn('Link copied');
                setTimeout(() => setBtn(original), 1200);
            } catch {
                setBtn('Copy failed');
                setTimeout(() => setBtn(original), 1400);
            }
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', window.initPublicSnippetEditor, { once: true });
        } else {
            window.initPublicSnippetEditor();
        }
    </script>
</body>
</html>
