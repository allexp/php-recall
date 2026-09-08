// Изменяемое состояние хранится централизованно, чтобы модули не создавали несогласованные копии.
export const state = {
    functionCatalog: {},
    conceptCatalog: {},
    mode: 'functions',
    quizMode: 'name',
    history: [],
    historyIndex: -1,
    withoutRepeats: false,
    shownFunctions: new Set(),
    documentation: new Map(),
    isAuthenticated: document.body.dataset.authenticated === '1',
    knownItems: { functions: new Set(), concepts: new Set() },
};

export const counters = {
    known: 'php_recall_known',
    reveal: 'php_recall_reveal',
};

export function categories() {
    return state.mode === 'functions' ? state.functionCatalog : state.conceptCatalog;
}

export function categoryItems(category) {
    return state.mode === 'functions' ? category.functions : category.materials.map((material) => material.slug);
}

export function currentPool(selectedCategory) {
    // Категория «Все» объединяет элементы и удаляет возможные дубликаты между разделами.
    if (selectedCategory === 'all') {
        return [...new Set(Object.values(categories()).flatMap(categoryItems))];
    }
    const category = categories()[selectedCategory];
    return category ? categoryItems(category) : [];
}

export function categoryTitle(slug) {
    return Object.values(categories()).find((category) => categoryItems(category).includes(slug))?.title
        ?? (state.mode === 'functions' ? 'Функция PHP' : 'Концепция разработки');
}

export function conceptSummary(slug) {
    return Object.values(state.conceptCatalog)
        .flatMap((category) => category.materials)
        .find((item) => item.slug === slug);
}

export function resetHistory() {
    // Новый цикл всегда начинается без истории навигации и отметок уже показанных функций.
    state.shownFunctions.clear();
    state.history = [];
    state.historyIndex = -1;
}
