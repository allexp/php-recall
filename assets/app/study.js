import { loadDocumentation, setKnowledge } from './api.js';
import { elements } from './dom.js';
import { renderConceptFull, renderCycleComplete, renderItem, updateCycleProgress } from './render.js';
import { incrementCounter, updateStatistics } from './statistics.js';
import { categories, categoryItems, counters, currentPool, resetHistory, state } from './state.js';

export function refillCategories() {
    const allTitle = state.mode === 'functions' ? 'Все функции' : 'Все концепции';
    elements.category.replaceChildren(new Option(allTitle, 'all'));
    Object.entries(categories()).forEach(([key, category]) => {
        elements.category.add(new Option(`${category.title} · ${categoryItems(category).length}`, key));
    });
    elements.category.setAttribute('aria-label', state.mode === 'functions' ? 'Категория функций' : 'Категория концепций');
}

export function showRandomItem() {
    const pool = currentPool(elements.category.value);
    if (!pool.length) return;
    const available = state.mode === 'functions' && state.withoutRepeats
        ? pool.filter((slug) => !state.shownFunctions.has(slug))
        : pool;
    if (!available.length) {
        renderCycleComplete(pool.length);
        return;
    }
    // Если есть выбор, одна и та же карточка не показывается два раза подряд.
    const alternatives = available.filter((slug) => slug !== state.history[state.historyIndex]);
    const choices = alternatives.length ? alternatives : available;
    state.history = state.history.slice(0, state.historyIndex + 1);
    state.history.push(choices[Math.floor(Math.random() * choices.length)]);
    state.historyIndex = state.history.length - 1;
    if (state.mode === 'functions' && state.withoutRepeats) state.shownFunctions.add(state.history[state.historyIndex]);
    updateCycleProgress();
    renderItem();
}

export function resetFunctionCycle() {
    resetHistory();
    elements.cycleComplete.classList.add('hidden');
    elements.studyContent.classList.remove('hidden');
    updateCycleProgress();
    showRandomItem();
}

export function renderCategoryStatistics() {
    if (!elements.categoryStatistics || !elements.categoryStatisticsList) return;
    elements.categoryStatisticsList.replaceChildren();
    Object.values(categories()).forEach((category) => {
        const items = categoryItems(category);
        const knownCount = items.filter((slug) => state.knownItems[state.mode].has(slug)).length;
        if (!knownCount) return;
        const row = document.createElement('div');
        const title = document.createElement('dt');
        const count = document.createElement('dd');
        title.textContent = category.title;
        count.textContent = `${knownCount} из ${items.length}`;
        row.append(title, count);
        elements.categoryStatisticsList.append(row);
    });
    elements.categoryStatistics.classList.toggle('hidden', !elements.categoryStatisticsList.children.length);
}

export async function markCurrentAsKnown() {
    // Значения фиксируются до await, чтобы переключение режима не изменило назначение ответа.
    const slug = state.history[state.historyIndex];
    const requestedMode = state.mode;
    incrementCounter(counters.known);
    try {
        await setKnowledge(slug, true, requestedMode);
        if (state.mode === requestedMode) renderCategoryStatistics();
    } catch (error) {
        showError(error);
    }
    showRandomItem();
}

export async function reveal() {
    // Карточка и режим фиксируются для защиты интерфейса от устаревшего асинхронного ответа.
    const slug = state.history[state.historyIndex];
    const requestedMode = state.mode;
    incrementCounter(counters.reveal);
    elements.reveal.classList.add('hidden');
    elements.loading.classList.remove('hidden');
    elements.error.classList.add('hidden');
    try {
        await setKnowledge(slug, false, requestedMode);
        if (state.mode === requestedMode) renderCategoryStatistics();
    } catch (error) {
        showError(error);
    }
    try {
        const data = await loadDocumentation(slug, requestedMode);
        if (state.history[state.historyIndex] !== slug || state.mode !== requestedMode) return;
        if (state.mode === 'functions') {
            if (state.quizMode === 'definition') {
                const name = document.createElement('strong');
                name.textContent = `${slug}()`;
                elements.answer.replaceChildren(name);
            } else {
                elements.answer.innerHTML = data.summary;
            }
            elements.source.href = data.source;
            elements.source.textContent = 'Открыть на php.net ↗';
        } else {
            const definition = document.createElement('strong');
            const description = document.createElement('p');
            definition.textContent = data.definition;
            description.textContent = data.short_description;
            elements.answer.replaceChildren(definition, description);
            if (data.source_url) {
                elements.source.href = data.source_url;
                elements.source.textContent = 'Открыть источник ↗';
            }
        }
        elements.answer.classList.remove('hidden');
        elements.showFull.classList.remove('hidden');
        elements.source.classList.toggle('hidden', !(data.source || data.source_url));
    } catch (error) {
        showError(error);
        elements.reveal.textContent = 'Попробовать снова';
        elements.reveal.classList.remove('hidden');
    } finally {
        elements.loading.classList.add('hidden');
    }
}

export async function showFull() {
    const slug = state.history[state.historyIndex];
    const requestedMode = state.mode;
    try {
        const data = await loadDocumentation(slug, requestedMode);
        if (state.history[state.historyIndex] !== slug || state.mode !== requestedMode) return;
        if (state.mode === 'functions') elements.manual.innerHTML = data.full;
        else renderConceptFull(data);
        elements.manual.classList.remove('hidden');
        elements.showFull.classList.add('hidden');
        elements.manual.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } catch (error) {
        showError(error);
    }
}

export function showPreviousItem() {
    if (state.historyIndex <= 0) return;
    state.historyIndex -= 1;
    renderItem();
}

export function setMode(nextMode) {
    if (state.mode === nextMode) return;
    state.mode = nextMode;
    elements.functionsMode.classList.toggle('active', state.mode === 'functions');
    elements.conceptsMode.classList.toggle('active', state.mode === 'concepts');
    elements.sandboxMode.classList.toggle('active', state.mode === 'sandbox');
    const isSandbox = state.mode === 'sandbox';
    elements.stage.classList.toggle('sandbox-stage', isSandbox);
    elements.categoryPicker.classList.toggle('hidden', isSandbox);
    elements.studyCard.classList.toggle('hidden', isSandbox);
    elements.statistics.classList.toggle('hidden', isSandbox);
    elements.previous.classList.toggle('hidden', isSandbox);
    elements.next.classList.toggle('hidden', isSandbox);
    elements.sandboxPanel.classList.toggle('hidden', !isSandbox);
    if (elements.categoryStatistics) {
        elements.categoryStatistics.classList.toggle('hidden', isSandbox || !elements.categoryStatisticsList.children.length);
    }
    // Sandbox использует отдельную панель и не участвует в цикле учебных карточек.
    if (isSandbox) {
        elements.progress.textContent = 'Безопасное выполнение PHP 8.3';
        (window.phpSandboxEditor?.focus ?? (() => elements.sandboxCode.focus()))();
        return;
    }
    elements.studyControls.classList.toggle('hidden', state.mode !== 'functions');
    elements.repeatControl.classList.toggle('hidden', state.mode !== 'functions');
    refillCategories();
    resetHistory();
    renderCategoryStatistics();
    showRandomItem();
}

export function setQuizMode(nextQuizMode) {
    if (state.quizMode === nextQuizMode) return;
    state.quizMode = nextQuizMode;
    elements.quizByName.classList.toggle('active', state.quizMode === 'name');
    elements.quizByDefinition.classList.toggle('active', state.quizMode === 'definition');
    elements.quizByName.setAttribute('aria-pressed', String(state.quizMode === 'name'));
    elements.quizByDefinition.setAttribute('aria-pressed', String(state.quizMode === 'definition'));
    if (elements.cycleComplete.classList.contains('hidden')) renderItem();
}

export function setWithoutRepeats(enabled) {
    state.withoutRepeats = enabled;
    elements.withoutRepeats.classList.toggle('active', state.withoutRepeats);
    elements.withoutRepeats.setAttribute('aria-pressed', String(state.withoutRepeats));
    updateCycleProgress();
    resetFunctionCycle();
}

export function initialiseView() {
    refillCategories();
    updateStatistics();
    renderCategoryStatistics();
    showRandomItem();
}

function showError(error) {
    elements.error.textContent = error.message;
    elements.error.classList.remove('hidden');
}
