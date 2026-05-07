import { EditorView, basicSetup } from 'codemirror';
import { Compartment, EditorState } from '@codemirror/state';
import { linter, lintGutter } from '@codemirror/lint';
import { javascript } from '@codemirror/lang-javascript';
import { php } from '@codemirror/lang-php';
import { html } from '@codemirror/lang-html';
import { css } from '@codemirror/lang-css';
import { json } from '@codemirror/lang-json';
import { markdown } from '@codemirror/lang-markdown';
import { python } from '@codemirror/lang-python';
import { sql } from '@codemirror/lang-sql';
import { yaml } from '@codemirror/lang-yaml';
import { StreamLanguage } from '@codemirror/language';
import { shell } from '@codemirror/legacy-modes/mode/shell';
import { dockerFile } from '@codemirror/legacy-modes/mode/dockerfile';
import { oneDark } from '@codemirror/theme-one-dark';

const editorRegistry = new Map();

function languageSupport(lang) {
    switch (lang) {
        case 'php':
            return php();
        case 'javascript':
            return javascript();
        case 'typescript':
            return javascript({ typescript: true });
        case 'python':
            return python();
        case 'sql':
            return sql();
        case 'html':
            return html();
        case 'css':
            return css();
        case 'json':
            return json();
        case 'markdown':
            return markdown();
        case 'yaml':
            return yaml();
        case 'shell':
            return StreamLanguage.define(shell);
        case 'dockerfile':
            return StreamLanguage.define(dockerFile);
        default:
            return [];
    }
}

function lightChrome() {
    return EditorView.theme({
        '&': { height: '100%', backgroundColor: '#ffffff', color: '#171717' },
        '.cm-gutters': { backgroundColor: '#fafafa', color: '#737373', borderRight: '1px solid #e5e5e5' },
        '.cm-activeLineGutter': { backgroundColor: '#f5f5f5' },
    });
}

function pickTheme() {
    return document.documentElement.classList.contains('dark') ? oneDark : lightChrome();
}

function getDraftKey(livewireId, property) {
    return `codesnip:draft:${livewireId}:${property}`;
}

function sanitizeCode(text) {
    const normalized = (text ?? '')
        .split('\n')
        .map((line) => line.replace(/[ \t]+$/g, ''))
        .join('\n');

    return normalized.endsWith('\n') || normalized === '' ? normalized : `${normalized}\n`;
}

function tryFormatCode(text, language) {
    if (language === 'json') {
        try {
            return `${JSON.stringify(JSON.parse(text), null, 2)}\n`;
        } catch {
            return sanitizeCode(text);
        }
    }

    return sanitizeCode(text);
}

function detectJsonError(text) {
    try {
        JSON.parse(text);
        return null;
    } catch (error) {
        const message = error instanceof Error ? error.message : 'Invalid JSON';
        const match = message.match(/position (\d+)/i);
        const pos = match ? Number(match[1]) : 0;
        return { message, pos: Number.isFinite(pos) ? pos : 0 };
    }
}

function buildDiagnostics(state, language) {
    const diagnostics = [];
    const text = state.doc.toString();

    // Generic: trailing whitespace warnings.
    for (let lineNo = 1; lineNo <= state.doc.lines; lineNo += 1) {
        const line = state.doc.line(lineNo);
        const trailing = line.text.match(/[ \t]+$/);
        if (trailing) {
            diagnostics.push({
                from: Math.max(line.from, line.to - trailing[0].length),
                to: line.to,
                severity: 'warning',
                message: 'Trailing whitespace',
            });
        }
    }

    // Language specific: JSON parser diagnostics.
    if (language === 'json' && text.trim() !== '') {
        const jsonError = detectJsonError(text);
        if (jsonError) {
            const from = Math.min(Math.max(0, jsonError.pos), state.doc.length);
            diagnostics.push({
                from,
                to: Math.min(state.doc.length, from + 1),
                severity: 'error',
                message: jsonError.message,
            });
        }
    }

    return diagnostics;
}

function mount(hostEl, livewireId, { property = 'code', language = 'php', autosave = false, autosaveDelay = 1500 } = {}) {
    const lw = window.Livewire?.find(livewireId);
    if (!lw || !hostEl) {
        return () => {};
    }

    if (hostEl._cmView) {
        try {
            hostEl._cmView.destroy();
        } catch {
            //
        }
        hostEl._cmView = null;
    }

    hostEl.innerHTML = '';

    const themeComp = new Compartment();
    const wrapComp = new Compartment();
    const lintComp = new Compartment();
    const draftKey = getDraftKey(livewireId, property);
    const currentValue = lw.get(property) ?? '';
    const draftValue = localStorage.getItem(draftKey) ?? '';
    const initialDoc = currentValue !== '' ? currentValue : draftValue;
    let autosaveTimer = null;

    const updateListener = EditorView.updateListener.of((update) => {
        if (update.docChanged) {
            const value = update.state.doc.toString();
            lw.set(property, value);
            localStorage.setItem(draftKey, value);

            if (autosave) {
                if (autosaveTimer) {
                    clearTimeout(autosaveTimer);
                }

                autosaveTimer = setTimeout(() => {
                    if (typeof lw.call === 'function') {
                        lw.call('save');
                    }
                }, autosaveDelay);
            }
        }
    });

    const extensions = [
        basicSetup,
        EditorView.theme({
            '&': { minHeight: 'min(70vh, 520px)' },
            '.cm-editor': { minHeight: 'min(70vh, 520px)' },
            '.cm-scroller': {
                minHeight: 'min(70vh, 520px)',
                fontFamily: 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace',
                fontSize: '13px',
                lineHeight: '1.45',
            },
        }),
        themeComp.of(pickTheme()),
        wrapComp.of([]),
        languageSupport(language),
        lintComp.of([
            lintGutter(),
            linter((view) => buildDiagnostics(view.state, language), {
                delay: 200,
            }),
        ]),
        EditorView.domEventHandlers({
            keydown: (event) => {
                if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 's') {
                    event.preventDefault();
                    if (typeof lw.call === 'function') {
                        lw.call('save');
                    }
                    return true;
                }

                return false;
            },
        }),
        updateListener,
    ];

    const state = EditorState.create({
        doc: initialDoc,
        extensions,
    });

    const view = new EditorView({ state, parent: hostEl });
    hostEl._cmView = view;
    editorRegistry.set(livewireId, {
        view,
        lw,
        property,
        language,
        draftKey,
        wrapComp,
        lintComp,
        lineWrapping: false,
    });

    const mo = new MutationObserver(() => {
        view.dispatch({ effects: themeComp.reconfigure(pickTheme()) });
    });
    mo.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

    const onNav = () => {
        mo.disconnect();
        document.removeEventListener('livewire:navigating', onNav);
        if (autosaveTimer) {
            clearTimeout(autosaveTimer);
            autosaveTimer = null;
        }
        editorRegistry.delete(livewireId);
        try {
            view.destroy();
        } catch {
            //
        }
        hostEl._cmView = null;
    };
    document.addEventListener('livewire:navigating', onNav);

    return () => {
        mo.disconnect();
        document.removeEventListener('livewire:navigating', onNav);
        if (autosaveTimer) {
            clearTimeout(autosaveTimer);
            autosaveTimer = null;
        }
        editorRegistry.delete(livewireId);
        try {
            view.destroy();
        } catch {
            //
        }
        hostEl._cmView = null;
    };
}

function getEditorById(livewireId) {
    return editorRegistry.get(livewireId) ?? null;
}

function format(livewireId) {
    const editor = getEditorById(livewireId);
    if (!editor) return false;

    const { view, lw, property, language } = editor;
    const current = view.state.doc.toString();
    const formatted = tryFormatCode(current, language);
    if (formatted === current) return true;

    view.dispatch({
        changes: { from: 0, to: view.state.doc.length, insert: formatted },
    });
    lw.set(property, formatted);
    localStorage.setItem(editor.draftKey, formatted);
    return true;
}

function toggleWrap(livewireId) {
    const editor = getEditorById(livewireId);
    if (!editor) return false;

    editor.lineWrapping = !editor.lineWrapping;
    editor.view.dispatch({
        effects: editor.wrapComp.reconfigure(editor.lineWrapping ? EditorView.lineWrapping : []),
    });
    return editor.lineWrapping;
}

async function copy(livewireId) {
    const editor = getEditorById(livewireId);
    if (!editor) return false;

    const text = editor.view.state.doc.toString();
    if (navigator.clipboard && window.isSecureContext) {
        await navigator.clipboard.writeText(text);
        return true;
    }

    const ta = document.createElement('textarea');
    ta.value = text;
    ta.setAttribute('readonly', '');
    ta.style.position = 'absolute';
    ta.style.left = '-9999px';
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    document.body.removeChild(ta);
    return true;
}

function clearDraft(livewireId) {
    const editor = getEditorById(livewireId);
    if (!editor) return false;
    localStorage.removeItem(editor.draftKey);
    return true;
}

function mountReadonly(hostEl, { code = '', language = 'php', minHeight = 'min(72vh, 720px)', lineWrapping = false } = {}) {
    if (!hostEl) {
        return () => {};
    }

    if (hostEl._cmView) {
        try {
            hostEl._cmView.destroy();
        } catch {
            //
        }
        hostEl._cmView = null;
    }

    hostEl.innerHTML = '';

    const themeComp = new Compartment();

    const extensions = [
        basicSetup,
        EditorState.readOnly.of(true),
        EditorView.editable.of(false),
        ...(lineWrapping ? [EditorView.lineWrapping] : []),
        EditorView.theme({
            '&': { minHeight, height: '100%' },
            '.cm-editor': { minHeight, height: '100%' },
            '.cm-scroller': {
                minHeight,
                fontFamily: 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace',
                fontSize: '13px',
                lineHeight: '1.55',
            },
        }),
        themeComp.of(pickTheme()),
        languageSupport(language),
    ];

    const state = EditorState.create({
        doc: code ?? '',
        extensions,
    });

    const view = new EditorView({ state, parent: hostEl });
    hostEl._cmView = view;

    const mo = new MutationObserver(() => {
        view.dispatch({ effects: themeComp.reconfigure(pickTheme()) });
    });
    mo.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

    return () => {
        mo.disconnect();
        try {
            view.destroy();
        } catch {
            //
        }
        hostEl._cmView = null;
    };
}

window.CodeSnipEditor = { mount, mountReadonly, format, toggleWrap, copy, clearDraft };
