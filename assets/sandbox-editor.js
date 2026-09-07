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

const textarea = document.querySelector('#sandbox-code');

if (textarea) {
    const runCode = () => {
        document.querySelector('#sandbox-run')?.click();
        return true;
    };
    const state = EditorState.create({
        doc: textarea.value,
        extensions: [
            lineNumbers(), highlightActiveLineGutter(), drawSelection(), highlightActiveLine(),
            indentOnInput(), bracketMatching(), closeBrackets(), syntaxHighlighting(phpHighlightStyle), php(),
            keymap.of([
                {key: 'Ctrl-Enter', run: runCode},
                {key: 'Mod-Enter', run: runCode},
                indentWithTab,
                ...closeBracketsKeymap,
                ...defaultKeymap,
            ]),
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

    window.phpSandboxEditor = {
        getValue: () => view.state.doc.toString(),
        focus: () => view.focus(),
    };
}
