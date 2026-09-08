import { state } from './state.js';

async function jsonRequest(path, options, fallbackMessage) {
    // API возвращает пользовательское описание ошибки, но для некорректного ответа предусмотрен резервный текст.
    const response = await fetch(path, options);
    const data = await response.json();
    if (!response.ok) throw new Error(data.error || fallbackMessage);
    return data;
}

export async function loadCatalogs() {
    // Независимые каталоги загружаются одновременно, чтобы не задерживать запуск приложения.
    const [functionsResponse, conceptsResponse] = await Promise.all([
        fetch('api/catalog'),
        fetch('api/concepts'),
    ]);
    if (!functionsResponse.ok || !conceptsResponse.ok) throw new Error('Не удалось загрузить каталоги.');
    state.functionCatalog = await functionsResponse.json();
    state.conceptCatalog = await conceptsResponse.json();
}

export async function loadKnowledge() {
    if (!state.isAuthenticated) return;
    const data = await jsonRequest('api/knowledge', undefined, 'Не удалось загрузить прогресс пользователя.');
    state.knownItems.functions = new Set(data.functions ?? []);
    state.knownItems.concepts = new Set(data.concepts ?? []);
}

export async function setKnowledge(slug, isKnown, mode) {
    if (!state.isAuthenticated) return;
    const path = mode === 'functions'
        ? `api/knowledge/${encodeURIComponent(slug)}`
        : `api/knowledge/concept/${encodeURIComponent(slug)}`;
    const data = await jsonRequest(path, {
        method: isKnown ? 'PUT' : 'DELETE',
        headers: { 'X-CSRF-Token': document.body.dataset.knowledgeToken },
    }, 'Не удалось сохранить прогресс.');
    // Используется режим, для которого начался запрос, даже если пользователь уже переключил вкладку.
    state.knownItems[mode] = new Set(data[mode] ?? []);
}

export async function loadDocumentation(slug, mode) {
    // Ключ включает режим, потому что функция и концепция теоретически могут иметь одинаковый slug.
    const key = `${mode}:${slug}`;
    if (state.documentation.has(key)) return state.documentation.get(key);
    const path = mode === 'functions'
        ? `api/manual/${encodeURIComponent(slug)}`
        : `api/concepts/${encodeURIComponent(slug)}`;
    const data = await jsonRequest(path, undefined, 'Не удалось загрузить материал.');
    state.documentation.set(key, data);
    return data;
}

export function runCode(code) {
    return jsonRequest('api/sandbox/run', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.body.dataset.sandboxToken,
        },
        body: JSON.stringify({ code }),
    }, 'Не удалось выполнить код.');
}

export function loadTask(difficulty) {
    return jsonRequest(`api/tasks/${encodeURIComponent(difficulty)}`, undefined, 'Не удалось загрузить задачу.');
}

export function loadTaskSolution(id) {
    return jsonRequest(`api/tasks/${id}/solution`, undefined, 'Не удалось загрузить решение.');
}
