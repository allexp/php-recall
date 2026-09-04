const elements = {
    category: document.querySelector('#category'),
    categoryLabel: document.querySelector('#category-label'),
    functionName: document.querySelector('#function-name'),
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
};

let catalog = {};
let history = [];
let historyIndex = -1;
const documentation = new Map();

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

async function initialise() {
    const response = await fetch('/api/catalog');
    catalog = await response.json();
    updateStatistics();
    Object.entries(catalog).forEach(([key, category]) => {
        elements.category.add(new Option(`${category.title} · ${category.functions.length}`, key));
    });
    showRandomFunction();
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
    elements.answer.innerHTML = '';
    elements.manual.innerHTML = '';
}

async function loadDocumentation() {
    const name = history[historyIndex];
    // Не запрашиваем повторно уже открытую документацию в пределах текущей страницы.
    if (documentation.has(name)) return documentation.get(name);
    const response = await fetch(`/api/manual/${encodeURIComponent(name)}`);
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
elements.known.addEventListener('click', () => {
    incrementCounter(counters.known);
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
document.addEventListener('keydown', (event) => {
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
