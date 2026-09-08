import { loadDocumentation } from './api.js';
import { elements } from './dom.js';
import { categoryTitle, conceptSummary, currentPool, state } from './state.js';

function definitionWithoutFunctionName(definition, slug) {
    // В режиме обратного вопроса название функции удаляется только из начала определения.
    const escapedSlug = slug.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    return definition.replace(new RegExp(`^\\s*${escapedSlug}(?:\\s*\\(\\s*\\))?\\s*(?:[—–:-]\\s*)?`, 'i'), '').trim();
}

export function renderCycleComplete(total) {
    elements.studyContent.classList.add('hidden');
    elements.cycleComplete.classList.remove('hidden');
    elements.previous.disabled = state.historyIndex <= 0;
    elements.progress.textContent = `${total} функций показано без повторений`;
}

export function updateCycleProgress() {
    elements.repeatProgress.textContent = `${state.shownFunctions.size}/${currentPool(elements.category.value).length}`;
    elements.repeatProgress.classList.toggle('hidden', !state.withoutRepeats || state.mode !== 'functions');
}

export function renderItem() {
    const slug = state.history[state.historyIndex];
    const concept = state.mode === 'concepts' ? conceptSummary(slug) : null;
    const isDefinitionQuiz = state.mode === 'functions' && state.quizMode === 'definition';
    elements.cycleComplete.classList.add('hidden');
    elements.studyContent.classList.remove('hidden');
    elements.itemName.textContent = isDefinitionQuiz ? '' : (state.mode === 'functions' ? `${slug}()` : concept?.title ?? slug);
    elements.quizMode.classList.toggle('hidden', state.mode !== 'functions');
    elements.itemName.classList.toggle('hidden', isDefinitionQuiz);
    elements.questionDefinition.classList.add('hidden');
    elements.questionDefinition.textContent = '';
    elements.categoryLabel.textContent = categoryTitle(slug);
    elements.prompt.textContent = state.mode === 'functions'
        ? (isDefinitionQuiz ? 'Вспомните название этой функции.' : 'Вспомните, что делает эта функция.')
        : 'Вспомните определение и смысл этой концепции.';
    elements.previous.disabled = state.historyIndex <= 0;
    elements.progress.textContent = `${currentPool(elements.category.value).length} ${state.mode === 'functions' ? 'функций' : 'концепций'} в подборке`;
    elements.reveal.textContent = 'Показать';
    elements.reveal.classList.remove('hidden');
    elements.known.classList.remove('hidden');
    elements.showFull.classList.add('hidden');
    elements.loading.classList.add('hidden');
    elements.error.classList.add('hidden');
    elements.answer.classList.add('hidden');
    elements.manual.classList.add('hidden');
    elements.source.classList.add('hidden');
    elements.answer.replaceChildren();
    elements.manual.replaceChildren();
    if (isDefinitionQuiz) renderDefinitionQuestion(slug);
}

async function renderDefinitionQuestion(slug) {
    const requestedMode = state.mode;
    elements.loading.classList.remove('hidden');
    try {
        const data = await loadDocumentation(slug, requestedMode);
        // Медленный ответ не должен перерисовать карточку, которую пользователь уже сменил.
        if (state.mode !== 'functions' || state.quizMode !== 'definition' || state.history[state.historyIndex] !== slug) return;
        elements.questionDefinition.textContent = definitionWithoutFunctionName(data.short_description, slug);
        elements.questionDefinition.classList.remove('hidden');
    } catch (error) {
        if (state.mode !== 'functions' || state.quizMode !== 'definition' || state.history[state.historyIndex] !== slug) return;
        elements.error.textContent = error.message;
        elements.error.classList.remove('hidden');
    } finally {
        if (state.mode === 'functions' && state.quizMode === 'definition' && state.history[state.historyIndex] === slug) {
            elements.loading.classList.add('hidden');
        }
    }
}

function appendExamples(container, examples) {
    // Код добавляется через textContent, чтобы пример отображался как текст и не исполнялся браузером.
    examples.forEach((example) => {
        const title = document.createElement('div');
        const pre = document.createElement('pre');
        const code = document.createElement('code');
        title.className = 'example-title';
        title.textContent = example.title || `Пример на ${example.language}`;
        code.textContent = example.code;
        pre.append(code);
        container.append(title, pre);
    });
}

export function renderConceptFull(concept) {
    elements.manual.innerHTML = concept.full_description;
    appendExamples(elements.manual, concept.code_examples);
    const sections = document.createElement('div');
    sections.className = 'concept-sections';
    concept.sections.forEach((section) => {
        const details = document.createElement('details');
        const summary = document.createElement('summary');
        const body = document.createElement('div');
        details.className = 'concept-section';
        summary.textContent = section.title;
        body.className = 'concept-section-body';
        body.innerHTML = section.description;
        appendExamples(body, section.code_examples);
        details.append(summary, body);
        sections.append(details);
    });
    elements.manual.append(sections);
}
