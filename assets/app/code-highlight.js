import hljs from 'highlight.js/lib/core';
import bash from 'highlight.js/lib/languages/bash';
import css from 'highlight.js/lib/languages/css';
import javascript from 'highlight.js/lib/languages/javascript';
import json from 'highlight.js/lib/languages/json';
import php from 'highlight.js/lib/languages/php';
import sql from 'highlight.js/lib/languages/sql';
import typescript from 'highlight.js/lib/languages/typescript';
import xml from 'highlight.js/lib/languages/xml';

hljs.registerLanguage('bash', bash);
hljs.registerLanguage('css', css);
hljs.registerLanguage('javascript', javascript);
hljs.registerLanguage('json', json);
hljs.registerLanguage('php', php);
hljs.registerLanguage('sql', sql);
hljs.registerLanguage('typescript', typescript);
hljs.registerLanguage('xml', xml);

const languageAliases = {
    html: 'xml',
    js: 'javascript',
    shell: 'bash',
    ts: 'typescript',
};

/** Подсвечивает ещё не обработанные блоки кода внутри контейнера. */
export function highlightCodeBlocks(container) {
    container.querySelectorAll('pre code:not(.hljs), pre:not(:has(code)):not(.hljs)').forEach((element) => {
        const requestedLanguage = (element.dataset.language || element.getAttribute('language') || '').toLowerCase();
        const language = languageAliases[requestedLanguage] || requestedLanguage;
        if (language && hljs.getLanguage(language)) element.classList.add(`language-${language}`);
        hljs.highlightElement(element);
    });
}
