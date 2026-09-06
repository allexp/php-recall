const elements = {
    functionsMode: document.querySelector('#functions-mode'),
    conceptsMode: document.querySelector('#concepts-mode'),
    studyCard: document.querySelector('#study-card'),
    conceptList: document.querySelector('#concept-list'),
    category: document.querySelector('#category'),
    categoryLabel: document.querySelector('#category-label'),
    functionName: document.querySelector('#function-name'),
    prompt: document.querySelector('#prompt'),
    previous: document.querySelector('#previous'),
    next: document.querySelector('#next'),
    reveal: document.querySelector('#reveal'),
    known: document.querySelector('#known'),
    showFull: document.querySelector('#show-full'),
    loading: document.querySelector('#loading'),
    error: document.querySelector('#error'),
    answer: document.querySelector('#answer'),
    manual: document.querySelector('#manual'),
    source: document.querySelector('#source'),
    progress: document.querySelector('#progress'),
    knownCount: document.querySelector('#known-count'),
    revealCount: document.querySelector('#reveal-count'),
    categoryStatistics: document.querySelector('#category-statistics'),
    categoryStatisticsList: document.querySelector('#category-statistics-list'),
};

let catalog = {};
let conceptCatalog = {};
let mode = 'functions';
let history = [];
let historyIndex = -1;
const documentation = new Map();
const isAuthenticated = document.body.dataset.authenticated === '1';
const knownFunctions = new Set();

// Счётчики сохраняются на год и относятся только к текущему браузеру.
const counters = {
    known: 'php_recall_known',
    reveal: 'php_recall_reveal',
};

function readCounter(name) {
    const prefix = `${name}=`;
    const cookie = document.cookie.split('; ').find((item) => item.startsWith(prefix));
    if (!cookie) return 0;
    const value = Number.parseInt(decodeURIComponent(cookie.slice(prefix.length)), 10);
    return Number.isFinite(value) && value >= 0 ? value : 0;
}

function writeCounter(name, value) {
    const maxAge = 60 * 60 * 24 * 365;
    document.cookie = `${name}=${encodeURIComponent(value)}; Max-Age=${maxAge}; Path=/; SameSite=Lax`;
}

function updateStatistics() {
    elements.knownCount.textContent = readCounter(counters.known).toLocaleString('ru-RU');
    elements.revealCount.textContent = readCounter(counters.reveal).toLocaleString('ru-RU');
}

function incrementCounter(name) {
    writeCounter(name, readCounter(name) + 1);
    updateStatistics();
}

function renderCategoryStatistics() {
    if (!elements.categoryStatistics || !elements.categoryStatisticsList) return;
    elements.categoryStatisticsList.replaceChildren();

    Object.values(catalog).forEach((category) => {
        const knownCount = category.functions.filter((name) => knownFunctions.has(name)).length;
        if (knownCount === 0) return;

        const row = document.createElement('div');
        const title = document.createElement('dt');
        const count = document.createElement('dd');
        title.textContent = category.title;
        count.textContent = `${knownCount} из ${category.functions.length}`;
        row.append(title, count);
        elements.categoryStatisticsList.append(row);
    });

    elements.categoryStatistics.classList.toggle('hidden', elements.categoryStatisticsList.children.length === 0);
}

async function loadKnowledge() {
    if (!isAuthenticated) return;
    const response = await fetch('api/knowledge');
    if (!response.ok) throw new Error('Не удалось загрузить прогресс пользователя.');
    const data = await response.json();
    knownFunctions.clear();
    data.functions.forEach((name) => knownFunctions.add(name));
    renderCategoryStatistics();
}

async function setKnowledge(functionName, isKnown) {
    if (!isAuthenticated) return;
    const response = await fetch(`api/knowledge/${encodeURIComponent(functionName)}`, {
        method: isKnown ? 'PUT' : 'DELETE',
        headers: { 'X-CSRF-Token': document.body.dataset.knowledgeToken },
    });
    const data = await response.json();
    if (!response.ok) throw new Error(data.error || 'Не удалось сохранить прогресс.');
    knownFunctions.clear();
    data.functions.forEach((name) => knownFunctions.add(name));
    renderCategoryStatistics();
}

async function initialise() {
    const [functionsResponse, conceptsResponse] = await Promise.all([fetch('api/catalog'), fetch('api/concepts')]);
    if (!functionsResponse.ok || !conceptsResponse.ok) throw new Error('Не удалось загрузить каталоги.');
    catalog = await functionsResponse.json();
    conceptCatalog = await conceptsResponse.json();
    updateStatistics();
    Object.entries(catalog).forEach(([key, category]) => {
        elements.category.add(new Option(`${category.title} · ${category.functions.length}`, key));
    });
    await loadKnowledge();
    renderConceptList();
    showRandomFunction();
}

function appendExamples(container, examples) {
    examples.forEach((example) => {
        const title = document.createElement('div');
        title.className = 'example-title';
        title.textContent = example.title || `Пример на ${example.language}`;
        const pre = document.createElement('pre');
        const code = document.createElement('code');
        code.textContent = example.code;
        pre.append(code);
        container.append(title, pre);
    });
}

function renderConceptList() {
    elements.conceptList.replaceChildren();
    Object.values(conceptCatalog).forEach((category) => {
        const heading = document.createElement('h2');
        heading.className = 'concept-group-title';
        heading.textContent = category.title;
        elements.conceptList.append(heading);
        category.materials.forEach((material) => {
            const card = document.createElement('button');
            card.className = 'concept-card';
            card.type = 'button';
            const title = document.createElement('strong');
            const description = document.createElement('span');
            title.textContent = material.title;
            description.textContent = material.short_description;
            card.append(title, description);
            card.addEventListener('click', () => openConcept(material.slug));
            elements.conceptList.append(card);
        });
    });
}

async function openConcept(slug) {
    elements.conceptList.classList.add('hidden');
    elements.studyCard.classList.remove('hidden');
    elements.loading.classList.remove('hidden');
    elements.error.classList.add('hidden');
    try {
        const response = await fetch(`api/concepts/${encodeURIComponent(slug)}`);
        const concept = await response.json();
        if (!response.ok) throw new Error(concept.error || 'Не удалось загрузить концепцию.');
        elements.categoryLabel.textContent = 'Концепция разработки';
        elements.functionName.textContent = concept.title;
        elements.prompt.textContent = concept.definition;
        elements.answer.replaceChildren();
        const shortDescription = document.createElement('p');
        shortDescription.textContent = concept.short_description;
        elements.answer.append(shortDescription);
        elements.answer.insertAdjacentHTML('beforeend', concept.full_description);
        elements.answer.classList.remove('hidden');
        elements.manual.replaceChildren();
        appendExamples(elements.manual, concept.code_examples);
        const sections = document.createElement('div');
        sections.className = 'concept-sections';
        concept.sections.forEach((section) => {
            const details = document.createElement('details');
            details.className = 'concept-section';
            const summary = document.createElement('summary');
            const body = document.createElement('div');
            summary.textContent = section.title;
            body.className = 'concept-section-body';
            body.innerHTML = section.description;
            appendExamples(body, section.code_examples);
            details.append(summary, body);
            sections.append(details);
        });
        elements.manual.append(sections);
        elements.manual.classList.remove('hidden');
        elements.source.classList.toggle('hidden', !concept.source_url);
        if (concept.source_url) {
            elements.source.href = concept.source_url;
            elements.source.textContent = 'Открыть источник ↗';
        }
    } catch (error) {
        elements.error.textContent = error.message;
        elements.error.classList.remove('hidden');
    } finally {
        elements.loading.classList.add('hidden');
    }
}

function setMode(nextMode) {
    mode = nextMode;
    const concepts = mode === 'concepts';
    elements.functionsMode.classList.toggle('active', !concepts);
    elements.conceptsMode.classList.toggle('active', concepts);
    elements.category.closest('label').classList.toggle('hidden', concepts);
    elements.previous.classList.toggle('hidden', concepts);
    elements.next.classList.toggle('hidden', concepts);
    elements.studyCard.classList.toggle('hidden', concepts);
    elements.conceptList.classList.toggle('hidden', !concepts);
    elements.reveal.classList.toggle('hidden', concepts);
    elements.known.classList.toggle('hidden', concepts);
    elements.showFull.classList.add('hidden');
    elements.progress.textContent = concepts
        ? `${Object.values(conceptCatalog).reduce((total, category) => total + category.materials.length, 0)} концепций`
        : `${currentPool().length} функций в подборке`;
    if (!concepts) renderFunction();
}

function currentPool() {
    // Общая подборка не должна содержать дубликаты из пересекающихся категорий.
    if (elements.category.value === 'all') {
        return [...new Set(Object.values(catalog).flatMap((category) => category.functions))];
    }
    return catalog[elements.category.value]?.functions ?? [];
}

function categoryTitle(functionName) {
    const match = Object.values(catalog).find((category) => category.functions.includes(functionName));
    return match?.title ?? 'Функция PHP';
}

function showRandomFunction() {
    const pool = currentPool();
    if (!pool.length) return;
    const current = history[historyIndex];
    const alternatives = pool.filter((name) => name !== current);
    const choices = alternatives.length ? alternatives : pool;
    const name = choices[Math.floor(Math.random() * choices.length)];
    // После возврата назад новая функция начинает отдельную ветку истории.
    history = history.slice(0, historyIndex + 1);
    history.push(name);
    historyIndex = history.length - 1;
    renderFunction();
}

function renderFunction() {
    const name = history[historyIndex];
    elements.functionName.textContent = `${name}()`;
    elements.categoryLabel.textContent = categoryTitle(name);
    elements.prompt.textContent = 'Вспомните, что делает эта функция.';
    elements.previous.disabled = historyIndex <= 0;
    elements.next.disabled = false;
    elements.progress.textContent = `${currentPool().length} функций в подборке`;
    elements.reveal.classList.remove('hidden');
    elements.showFull.classList.add('hidden');
    elements.loading.classList.add('hidden');
    elements.error.classList.add('hidden');
    elements.answer.classList.add('hidden');
    elements.manual.classList.add('hidden');
    elements.source.classList.add('hidden');
    elements.source.textContent = 'Открыть на php.net ↗';
    elements.answer.innerHTML = '';
    elements.manual.innerHTML = '';
}

async function loadDocumentation() {
    const name = history[historyIndex];
    // Не запрашиваем повторно уже открытую документацию в пределах текущей страницы.
    if (documentation.has(name)) return documentation.get(name);
    const response = await fetch(`api/manual/${encodeURIComponent(name)}`);
    const data = await response.json();
    if (!response.ok) throw new Error(data.error || 'Не удалось загрузить документацию.');
    documentation.set(name, data);
    return data;
}

async function reveal() {
    incrementCounter(counters.reveal);
    elements.reveal.classList.add('hidden');
    elements.loading.classList.remove('hidden');
    elements.error.classList.add('hidden');
    const name = history[historyIndex];
    try {
        await setKnowledge(name, false);
    } catch (error) {
        elements.error.textContent = error.message;
        elements.error.classList.remove('hidden');
    }
    try {
        const data = await loadDocumentation();
        elements.answer.innerHTML = data.summary;
        elements.answer.classList.remove('hidden');
        elements.showFull.classList.remove('hidden');
        elements.source.href = data.source;
        elements.source.classList.remove('hidden');
    } catch (error) {
        elements.error.textContent = error.message;
        elements.error.classList.remove('hidden');
        elements.reveal.textContent = 'Попробовать снова';
        elements.reveal.classList.remove('hidden');
    } finally {
        elements.loading.classList.add('hidden');
    }
}

async function showFull() {
    const data = await loadDocumentation();
    elements.manual.innerHTML = data.full;
    elements.manual.classList.remove('hidden');
    elements.showFull.classList.add('hidden');
    elements.manual.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

elements.next.addEventListener('click', showRandomFunction);
elements.known.addEventListener('click', async () => {
    incrementCounter(counters.known);
    const name = history[historyIndex];
    try {
        await setKnowledge(name, true);
    } catch (error) {
        elements.error.textContent = error.message;
        elements.error.classList.remove('hidden');
    }
    showRandomFunction();
});
elements.previous.addEventListener('click', () => {
    if (historyIndex > 0) {
        historyIndex -= 1;
        renderFunction();
    }
});
elements.reveal.addEventListener('click', reveal);
elements.showFull.addEventListener('click', showFull);
elements.category.addEventListener('change', () => {
    history = [];
    historyIndex = -1;
    showRandomFunction();
});
elements.functionsMode.addEventListener('click', () => setMode('functions'));
elements.conceptsMode.addEventListener('click', () => setMode('concepts'));
document.addEventListener('keydown', (event) => {
    if (mode !== 'functions') return;
    if (event.key === 'ArrowRight') showRandomFunction();
    if (event.key === 'ArrowLeft' && historyIndex > 0) {
        historyIndex -= 1;
        renderFunction();
    }
    if ((event.key === ' ' || event.key === 'Enter') && !elements.reveal.classList.contains('hidden')) {
        event.preventDefault();
        reveal();
    }
});

initialise().catch(() => {
    elements.progress.textContent = 'Ошибка загрузки';
    elements.error.textContent = 'Не удалось запустить приложение.';
    elements.error.classList.remove('hidden');
});
