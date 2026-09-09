import {closeBrackets, closeBracketsKeymap} from '@codemirror/autocomplete';
import {defaultKeymap, indentWithTab} from '@codemirror/commands';
import {php} from '@codemirror/lang-php';
import {bracketMatching, HighlightStyle, indentOnInput, syntaxHighlighting} from '@codemirror/language';
import {EditorState} from '@codemirror/state';
import {EditorView, drawSelection, highlightActiveLine, highlightActiveLineGutter, keymap, lineNumbers} from '@codemirror/view';
import {tags} from '@lezer/highlight';

const phpHighlightStyle = HighlightStyle.define([
    {tag: [tags.keyword, tags.controlKeyword, tags.definitionKeyword, tags.moduleKeyword], color: '#c792ea', fontWeight: '700'},
    {tag: [tags.variableName, tags.local(tags.variableName)], color: '#82aaff'},
    {tag: [tags.function(tags.variableName), tags.function(tags.propertyName)], color: '#5de4c7'},
    {tag: [tags.definition(tags.function(tags.variableName)), tags.definition(tags.variableName)], color: '#ffcb6b'},
    {tag: [tags.className, tags.typeName, tags.namespace], color: '#ffd580'},
    {tag: [tags.propertyName, tags.attributeName], color: '#89ddff'},
    {tag: [tags.string, tags.special(tags.string)], color: '#c3e88d'},
    {tag: [tags.number, tags.bool, tags.null, tags.atom], color: '#f78c6c'},
    {tag: [tags.operator, tags.operatorKeyword], color: '#ff5370'},
    {tag: [tags.comment, tags.lineComment, tags.blockComment], color: '#768390', fontStyle: 'italic'},
    {tag: [tags.meta, tags.processingInstruction], color: '#ff9cac'},
    {tag: [tags.punctuation, tags.separator], color: '#a6accd'},
]);

const storageKey = 'php_recall_sandbox_code';

function createPhpEditor(selector, runButtonSelector, persist = false, readOnly = false) {
    const textarea = document.querySelector(selector);
    if (!textarea) return null;

    let initialCode = textarea.value;
    if (persist) {
        try {
            initialCode = localStorage.getItem(storageKey) ?? initialCode;
        } catch {
            // Редактор продолжает работать, если браузер запретил локальное хранилище.
        }
    }

    const runCode = () => {
        document.querySelector(runButtonSelector)?.click();
        return true;
    };
    const state = EditorState.create({
        doc: initialCode,
        extensions: [
            lineNumbers(), highlightActiveLineGutter(), drawSelection(), highlightActiveLine(),
            indentOnInput(), bracketMatching(), closeBrackets(), syntaxHighlighting(phpHighlightStyle), php(),
            EditorState.readOnly.of(readOnly),
            EditorView.editable.of(!readOnly),
            keymap.of([
                {key: 'Ctrl-Enter', run: runCode},
                {key: 'Mod-Enter', run: runCode},
                indentWithTab,
                ...closeBracketsKeymap,
                ...defaultKeymap,
            ]),
            EditorView.updateListener.of((update) => {
                if (!persist || !update.docChanged) return;
                try {
                    localStorage.setItem(storageKey, update.state.doc.toString());
                } catch {
                    // Ошибка localStorage не должна мешать вводу и запуску кода.
                }
            }),
            EditorView.theme({
                '&': {height: '100%', color: '#dce2e8', backgroundColor: '#0d1014'},
                '.cm-content': {caretColor: '#a8ff78', padding: '16px 0'},
                '.cm-cursor, .cm-dropCursor': {borderLeftColor: '#a8ff78'},
                '.cm-gutters': {backgroundColor: '#101419', color: '#65707c', border: 'none'},
                '.cm-activeLine, .cm-activeLineGutter': {backgroundColor: 'rgba(168, 255, 120, .06)'},
                '.cm-selectionBackground, ::selection': {backgroundColor: 'rgba(94, 73, 255, .35) !important'},
                '.cm-focused': {outline: 'none'},
            }, {dark: true}),
        ],
    });
    const editorHost = document.createElement('div');
    editorHost.className = 'sandbox-editor-host';
    textarea.before(editorHost);
    const view = new EditorView({state, parent: editorHost});
    textarea.remove();

    return {
        getValue: () => view.state.doc.toString(),
        focus: () => view.focus(),
        setValue: (code) => view.dispatch({changes: {from: 0, to: view.state.doc.length, insert: code}}),
    };
}

window.phpSandboxEditor = createPhpEditor('#sandbox-code', '#sandbox-run', true);
window.phpTaskEditor = createPhpEditor('#task-code', '#task-run');
window.phpTaskSolutionViewer = createPhpEditor('#task-solution-code', '', false, true);
