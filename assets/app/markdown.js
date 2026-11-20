const safeLinkProtocols = new Set(['http:', 'https:', 'mailto:']);

function appendInlineMarkdown(container, source) {
    const pattern = /(`+)([^`]+?)\1|\[([^\]]+)]\(([^\s)]+)(?:\s+["']([^"']*)["'])?\)|(\*\*|__)(.+?)\6|(\*|_)(.+?)\8/g;
    let offset = 0;
    let match;

    while ((match = pattern.exec(source)) !== null) {
        container.append(document.createTextNode(source.slice(offset, match.index)));
        if (match[1]) {
            const code = document.createElement('code');
            code.textContent = match[2];
            container.append(code);
        } else if (match[3]) {
            appendLink(container, match[3], match[4], match[5]);
        } else {
            const emphasis = document.createElement(match[6] ? 'strong' : 'em');
            appendInlineMarkdown(emphasis, match[7] ?? match[9]);
            container.append(emphasis);
        }
        offset = pattern.lastIndex;
    }

    container.append(document.createTextNode(source.slice(offset)));
}

function appendLink(container, label, href, title) {
    let parsedUrl;
    try {
        parsedUrl = new URL(href, window.location.href);
    } catch {
        container.append(document.createTextNode(label));
        return;
    }

    if (!safeLinkProtocols.has(parsedUrl.protocol)) {
        container.append(document.createTextNode(label));
        return;
    }

    const link = document.createElement('a');
    link.href = parsedUrl.href;
    link.textContent = label;
    if (title) link.title = title;
    if (parsedUrl.origin !== window.location.origin) {
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
    }
    container.append(link);
}

function appendParagraph(container, lines) {
    if (!lines.length) return;
    const paragraph = document.createElement('p');
    appendInlineMarkdown(paragraph, lines.join(' '));
    container.append(paragraph);
    lines.length = 0;
}

function appendList(container, lines, startIndex) {
    const firstItem = lines[startIndex].match(/^\s*(?:([-+*])|(\d+)\.)\s+(.+)$/);
    const ordered = Boolean(firstItem[2]);
    const list = document.createElement(ordered ? 'ol' : 'ul');
    if (ordered && firstItem[2] !== '1') list.start = Number(firstItem[2]);
    let index = startIndex;

    while (index < lines.length) {
        const itemMatch = lines[index].match(/^\s*(?:([-+*])|(\d+)\.)\s+(.+)$/);
        if (!itemMatch || Boolean(itemMatch[2]) !== ordered) break;
        const item = document.createElement('li');
        appendInlineMarkdown(item, itemMatch[3]);
        list.append(item);
        index += 1;
    }

    container.append(list);
    return index;
}

/** Безопасно преобразует поддерживаемую Markdown-разметку в DOM-узлы. */
export function renderMarkdown(container, markdown) {
    const lines = String(markdown ?? '').replace(/\r\n?/g, '\n').split('\n');
    const paragraphLines = [];
    container.replaceChildren();

    for (let index = 0; index < lines.length;) {
        const line = lines[index];
        const fence = line.match(/^\s*```\s*([\w+-]*)\s*$/);
        const heading = line.match(/^\s*(#{1,6})\s+(.+)$/);
        const listItem = line.match(/^\s*(?:[-+*]|\d+\.)\s+(.+)$/);

        if (fence) {
            appendParagraph(container, paragraphLines);
            const codeLines = [];
            index += 1;
            while (index < lines.length && !/^\s*```\s*$/.test(lines[index])) {
                codeLines.push(lines[index]);
                index += 1;
            }
            if (index < lines.length) index += 1;
            const pre = document.createElement('pre');
            const code = document.createElement('code');
            code.textContent = codeLines.join('\n');
            if (fence[1]) code.dataset.language = fence[1].toLowerCase();
            pre.append(code);
            container.append(pre);
            continue;
        }
        if (heading) {
            appendParagraph(container, paragraphLines);
            const element = document.createElement(`h${heading[1].length}`);
            appendInlineMarkdown(element, heading[2]);
            container.append(element);
            index += 1;
            continue;
        }
        if (listItem) {
            appendParagraph(container, paragraphLines);
            index = appendList(container, lines, index);
            continue;
        }
        if (/^\s*(?:---+|___+|\*\*\*+)\s*$/.test(line)) {
            appendParagraph(container, paragraphLines);
            container.append(document.createElement('hr'));
            index += 1;
            continue;
        }
        if (line.trim() === '') {
            appendParagraph(container, paragraphLines);
        } else {
            paragraphLines.push(line.trim());
        }
        index += 1;
    }

    appendParagraph(container, paragraphLines);
}
