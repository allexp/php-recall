const elements = {
    functionsMode: document.querySelector('#functions-mode'), conceptsMode: document.querySelector('#concepts-mode'), sandboxMode: document.querySelector('#sandbox-mode'),
    stage: document.querySelector('#stage'), categoryPicker: document.querySelector('#category-picker'), studyCard: document.querySelector('#study-card'), statistics: document.querySelector('#statistics'),
    category: document.querySelector('#category'), categoryLabel: document.querySelector('#category-label'),
    quizMode: document.querySelector('#quiz-mode'), quizByName: document.querySelector('#quiz-by-name'), quizByDefinition: document.querySelector('#quiz-by-definition'),
    itemName: document.querySelector('#function-name'), questionDefinition: document.querySelector('#question-definition'), prompt: document.querySelector('#prompt'),
    previous: document.querySelector('#previous'), next: document.querySelector('#next'), reveal: document.querySelector('#reveal'),
    known: document.querySelector('#known'), showFull: document.querySelector('#show-full'), loading: document.querySelector('#loading'),
    error: document.querySelector('#error'), answer: document.querySelector('#answer'), manual: document.querySelector('#manual'),
    source: document.querySelector('#source'), progress: document.querySelector('#progress'), knownCount: document.querySelector('#known-count'),
    revealCount: document.querySelector('#reveal-count'), categoryStatistics: document.querySelector('#category-statistics'),
    categoryStatisticsList: document.querySelector('#category-statistics-list'),
    sandboxPanel: document.querySelector('#sandbox-panel'), sandboxCode: document.querySelector('#sandbox-code'), sandboxRun: document.querySelector('#sandbox-run'),
    sandboxStatus: document.querySelector('#sandbox-status'), sandboxResult: document.querySelector('#sandbox-result'), sandboxOutput: document.querySelector('#sandbox-output'), sandboxError: document.querySelector('#sandbox-error'),
};

let functionCatalog = {}, conceptCatalog = {}, mode = 'functions', quizMode = 'name', history = [], historyIndex = -1;
const documentation = new Map();
const isAuthenticated = document.body.dataset.authenticated === '1';
const knownItems = { functions: new Set(), concepts: new Set() };
const counters = { known: 'php_recall_known', reveal: 'php_recall_reveal' };

function readCounter(name) {
    const prefix = `${name}=`;
    const cookie = document.cookie.split('; ').find((item) => item.startsWith(prefix));
    if (!cookie) return 0;
    const value = Number.parseInt(decodeURIComponent(cookie.slice(prefix.length)), 10);
    return Number.isFinite(value) && value >= 0 ? value : 0;
}

function updateStatistics() {
    elements.knownCount.textContent = readCounter(counters.known).toLocaleString('ru-RU');
    elements.revealCount.textContent = readCounter(counters.reveal).toLocaleString('ru-RU');
}

function incrementCounter(name) {
    const value = readCounter(name) + 1;
    document.cookie = `${name}=${value}; Max-Age=${60 * 60 * 24 * 365}; Path=/; SameSite=Lax`;
    updateStatistics();
}

function categories() { return mode === 'functions' ? functionCatalog : conceptCatalog; }
function categoryItems(category) {
    return mode === 'functions' ? category.functions : category.materials.map((material) => material.slug);
}
function currentPool() {
    if (elements.category.value === 'all') return [...new Set(Object.values(categories()).flatMap(categoryItems))];
    const category = categories()[elements.category.value];
    return category ? categoryItems(category) : [];
}
function categoryTitle(slug) {
    return Object.values(categories()).find((category) => categoryItems(category).includes(slug))?.title
        ?? (mode === 'functions' ? 'Функция PHP' : 'Концепция разработки');
}
function conceptSummary(slug) {
    return Object.values(conceptCatalog).flatMap((category) => category.materials).find((item) => item.slug === slug);
}

function definitionWithoutFunctionName(definition, slug) {
    const escapedSlug = slug.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    return definition.replace(new RegExp(`^\\s*${escapedSlug}(?:\\s*\\(\\s*\\))?\\s*(?:[—–:-]\\s*)?`, 'i'), '').trim();
}

function refillCategories() {
    elements.category.replaceChildren(new Option(mode === 'functions' ? 'Все функции' : 'Все концепции', 'all'));
    Object.entries(categories()).forEach(([key, category]) => {
        elements.category.add(new Option(`${category.title} · ${categoryItems(category).length}`, key));
    });
    elements.category.setAttribute('aria-label', mode === 'functions' ? 'Категория функций' : 'Категория концепций');
}

function renderCategoryStatistics() {
    if (!elements.categoryStatistics || !elements.categoryStatisticsList) return;
    elements.categoryStatisticsList.replaceChildren();
    Object.values(categories()).forEach((category) => {
        const items = categoryItems(category);
        const knownCount = items.filter((slug) => knownItems[mode].has(slug)).length;
        if (!knownCount) return;
        const row = document.createElement('div'), title = document.createElement('dt'), count = document.createElement('dd');
        title.textContent = category.title;
        count.textContent = `${knownCount} из ${items.length}`;
        row.append(title, count);
        elements.categoryStatisticsList.append(row);
    });
    elements.categoryStatistics.classList.toggle('hidden', !elements.categoryStatisticsList.children.length);
}

async function loadKnowledge() {
    if (!isAuthenticated) return;
    const response = await fetch('api/knowledge');
    if (!response.ok) throw new Error('Не удалось загрузить прогресс пользователя.');
    const data = await response.json();
    knownItems.functions = new Set(data.functions ?? []);
    knownItems.concepts = new Set(data.concepts ?? []);
    renderCategoryStatistics();
}

async function setKnowledge(slug, isKnown) {
    if (!isAuthenticated) return;
    const path = mode === 'functions' ? `api/knowledge/${encodeURIComponent(slug)}` : `api/knowledge/concept/${encodeURIComponent(slug)}`;
    const response = await fetch(path, { method: isKnown ? 'PUT' : 'DELETE', headers: { 'X-CSRF-Token': document.body.dataset.knowledgeToken } });
    const data = await response.json();
    if (!response.ok) throw new Error(data.error || 'Не удалось сохранить прогресс.');
    knownItems[mode] = new Set(data[mode] ?? []);
    renderCategoryStatistics();
}

function showRandomItem() {
    const pool = currentPool();
    if (!pool.length) return;
    const alternatives = pool.filter((slug) => slug !== history[historyIndex]);
    const choices = alternatives.length ? alternatives : pool;
    history = history.slice(0, historyIndex + 1);
    history.push(choices[Math.floor(Math.random() * choices.length)]);
    historyIndex = history.length - 1;
    renderItem();
}

function renderItem() {
    const slug = history[historyIndex], concept = mode === 'concepts' ? conceptSummary(slug) : null;
    const isDefinitionQuiz = mode === 'functions' && quizMode === 'definition';
    elements.itemName.textContent = isDefinitionQuiz ? '' : (mode === 'functions' ? `${slug}()` : concept?.title ?? slug);
    elements.quizMode.classList.toggle('hidden', mode !== 'functions');
    elements.itemName.classList.toggle('hidden', isDefinitionQuiz);
    elements.questionDefinition.classList.add('hidden'); elements.questionDefinition.textContent = '';
    elements.categoryLabel.textContent = categoryTitle(slug);
    elements.prompt.textContent = mode === 'functions' ? (isDefinitionQuiz ? 'Вспомните название этой функции.' : 'Вспомните, что делает эта функция.') : 'Вспомните определение и смысл этой концепции.';
    elements.previous.disabled = historyIndex <= 0;
    elements.progress.textContent = `${currentPool().length} ${mode === 'functions' ? 'функций' : 'концепций'} в подборке`;
    elements.reveal.textContent = 'Показать';
    elements.reveal.classList.remove('hidden'); elements.known.classList.remove('hidden');
    elements.showFull.classList.add('hidden'); elements.loading.classList.add('hidden'); elements.error.classList.add('hidden');
    elements.answer.classList.add('hidden'); elements.manual.classList.add('hidden'); elements.source.classList.add('hidden');
    elements.answer.replaceChildren(); elements.manual.replaceChildren();
    if (isDefinitionQuiz) renderDefinitionQuestion(slug);
}

async function renderDefinitionQuestion(slug) {
    elements.loading.classList.remove('hidden');
    try {
        const data = await loadDocumentation();
        if (mode !== 'functions' || quizMode !== 'definition' || history[historyIndex] !== slug) return;
        elements.questionDefinition.textContent = definitionWithoutFunctionName(data.short_description, slug);
        elements.questionDefinition.classList.remove('hidden');
    } catch (error) {
        if (mode !== 'functions' || quizMode !== 'definition' || history[historyIndex] !== slug) return;
        elements.error.textContent = error.message; elements.error.classList.remove('hidden');
    } finally {
        if (mode === 'functions' && quizMode === 'definition' && history[historyIndex] === slug) elements.loading.classList.add('hidden');
    }
}

async function loadDocumentation() {
    const slug = history[historyIndex], key = `${mode}:${slug}`;
    if (documentation.has(key)) return documentation.get(key);
    const path = mode === 'functions' ? `api/manual/${encodeURIComponent(slug)}` : `api/concepts/${encodeURIComponent(slug)}`;
    const response = await fetch(path), data = await response.json();
    if (!response.ok) throw new Error(data.error || 'Не удалось загрузить материал.');
    documentation.set(key, data);
    return data;
}

function appendExamples(container, examples) {
    examples.forEach((example) => {
        const title = document.createElement('div'), pre = document.createElement('pre'), code = document.createElement('code');
        title.className = 'example-title'; title.textContent = example.title || `Пример на ${example.language}`; code.textContent = example.code;
        pre.append(code); container.append(title, pre);
    });
}

function renderConceptFull(concept) {
    elements.manual.innerHTML = concept.full_description;
    appendExamples(elements.manual, concept.code_examples);
    const sections = document.createElement('div'); sections.className = 'concept-sections';
    concept.sections.forEach((section) => {
        const details = document.createElement('details'), summary = document.createElement('summary'), body = document.createElement('div');
        details.className = 'concept-section'; summary.textContent = section.title; body.className = 'concept-section-body'; body.innerHTML = section.description;
        appendExamples(body, section.code_examples); details.append(summary, body); sections.append(details);
    });
    elements.manual.append(sections);
}

async function reveal() {
    incrementCounter(counters.reveal); elements.reveal.classList.add('hidden'); elements.loading.classList.remove('hidden'); elements.error.classList.add('hidden');
    try { await setKnowledge(history[historyIndex], false); } catch (error) { elements.error.textContent = error.message; elements.error.classList.remove('hidden'); }
    try {
        const data = await loadDocumentation();
        if (mode === 'functions') {
            if (quizMode === 'definition') {
                const name = document.createElement('strong');
                name.textContent = `${history[historyIndex]}()`; elements.answer.replaceChildren(name);
            } else {
                elements.answer.innerHTML = data.summary;
            }
            elements.source.href = data.source; elements.source.textContent = 'Открыть на php.net ↗';
        } else {
            const definition = document.createElement('strong'), description = document.createElement('p');
            definition.textContent = data.definition; description.textContent = data.short_description; elements.answer.replaceChildren(definition, description);
            if (data.source_url) { elements.source.href = data.source_url; elements.source.textContent = 'Открыть источник ↗'; }
        }
        elements.answer.classList.remove('hidden'); elements.showFull.classList.remove('hidden');
        elements.source.classList.toggle('hidden', !(data.source || data.source_url));
    } catch (error) {
        elements.error.textContent = error.message; elements.error.classList.remove('hidden'); elements.reveal.textContent = 'Попробовать снова'; elements.reveal.classList.remove('hidden');
    } finally { elements.loading.classList.add('hidden'); }
}

async function showFull() {
    const data = await loadDocumentation();
    if (mode === 'functions') elements.manual.innerHTML = data.full; else renderConceptFull(data);
    elements.manual.classList.remove('hidden'); elements.showFull.classList.add('hidden');
    elements.manual.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function setMode(nextMode) {
    if (mode === nextMode) return;
    mode = nextMode; elements.functionsMode.classList.toggle('active', mode === 'functions'); elements.conceptsMode.classList.toggle('active', mode === 'concepts'); elements.sandboxMode.classList.toggle('active', mode === 'sandbox');
    const isSandbox = mode === 'sandbox';
    elements.stage.classList.toggle('sandbox-stage', isSandbox);
    elements.categoryPicker.classList.toggle('hidden', isSandbox); elements.studyCard.classList.toggle('hidden', isSandbox); elements.statistics.classList.toggle('hidden', isSandbox);
    elements.previous.classList.toggle('hidden', isSandbox); elements.next.classList.toggle('hidden', isSandbox); elements.sandboxPanel.classList.toggle('hidden', !isSandbox);
    if (elements.categoryStatistics) elements.categoryStatistics.classList.toggle('hidden', isSandbox || !elements.categoryStatisticsList.children.length);
    if (isSandbox) { elements.progress.textContent = 'Безопасное выполнение PHP 8.3'; (window.phpSandboxEditor?.focus ?? (() => elements.sandboxCode.focus()))(); return; }
    refillCategories(); history = []; historyIndex = -1; renderCategoryStatistics(); showRandomItem();
}

function setQuizMode(nextQuizMode) {
    if (quizMode === nextQuizMode) return;
    quizMode = nextQuizMode;
    elements.quizByName.classList.toggle('active', quizMode === 'name'); elements.quizByDefinition.classList.toggle('active', quizMode === 'definition');
    elements.quizByName.setAttribute('aria-pressed', String(quizMode === 'name')); elements.quizByDefinition.setAttribute('aria-pressed', String(quizMode === 'definition'));
    renderItem();
}

async function runSandbox() {
    elements.sandboxRun.disabled = true; elements.sandboxStatus.textContent = 'Создаю изолированный контейнер…';
    elements.sandboxError.classList.add('hidden'); elements.sandboxResult.classList.add('hidden');
    try {
        const response = await fetch('api/sandbox/run', {
            method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': document.body.dataset.sandboxToken },
            body: JSON.stringify({ code: window.phpSandboxEditor?.getValue() ?? elements.sandboxCode.value }),
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'Не удалось выполнить код.');
        elements.sandboxOutput.textContent = data.output || '(программа не вывела результат)';
        elements.sandboxResult.classList.remove('hidden');
        elements.sandboxStatus.textContent = data.timed_out ? 'Остановлено по тайм-ауту' : `Завершено, код выхода: ${data.exit_code}`;
    } catch (error) {
        elements.sandboxError.textContent = error.message; elements.sandboxError.classList.remove('hidden'); elements.sandboxStatus.textContent = 'Ошибка запуска';
    } finally { elements.sandboxRun.disabled = false; }
}

elements.next.addEventListener('click', showRandomItem);
elements.known.addEventListener('click', async () => {
    incrementCounter(counters.known);
    try { await setKnowledge(history[historyIndex], true); } catch (error) { elements.error.textContent = error.message; elements.error.classList.remove('hidden'); }
    showRandomItem();
});
elements.previous.addEventListener('click', () => { if (historyIndex > 0) { historyIndex -= 1; renderItem(); } });
elements.reveal.addEventListener('click', reveal); elements.showFull.addEventListener('click', showFull);
elements.category.addEventListener('change', () => { history = []; historyIndex = -1; showRandomItem(); });
elements.functionsMode.addEventListener('click', () => setMode('functions')); elements.conceptsMode.addEventListener('click', () => setMode('concepts'));
elements.quizByName.addEventListener('click', () => setQuizMode('name')); elements.quizByDefinition.addEventListener('click', () => setQuizMode('definition'));
elements.sandboxMode.addEventListener('click', () => setMode('sandbox')); elements.sandboxRun.addEventListener('click', runSandbox);
document.addEventListener('keydown', (event) => {
    if (mode === 'sandbox' && event.ctrlKey && event.key === 'Enter') { event.preventDefault(); runSandbox(); return; }
    if (mode === 'sandbox') return;
    if (event.key === 'ArrowRight') showRandomItem();
    if (event.key === 'ArrowLeft' && historyIndex > 0) { historyIndex -= 1; renderItem(); }
    if ((event.key === ' ' || event.key === 'Enter') && !elements.reveal.classList.contains('hidden')) { event.preventDefault(); reveal(); }
});

async function initialise() {
    const [functionsResponse, conceptsResponse] = await Promise.all([fetch('api/catalog'), fetch('api/concepts')]);
    if (!functionsResponse.ok || !conceptsResponse.ok) throw new Error('Не удалось загрузить каталоги.');
    functionCatalog = await functionsResponse.json(); conceptCatalog = await conceptsResponse.json();
    refillCategories(); updateStatistics(); await loadKnowledge(); showRandomItem();
}
initialise().catch(() => { elements.progress.textContent = 'Ошибка загрузки'; elements.error.textContent = 'Не удалось запустить приложение.'; elements.error.classList.remove('hidden'); });
