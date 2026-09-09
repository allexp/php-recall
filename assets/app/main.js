import { loadCatalogs, loadKnowledge } from './api.js';
import { elements } from './dom.js';
import { runSandbox } from './sandbox.js';
import { state } from './state.js';
import { revealTaskSolution, runTask, showTask } from './tasks.js';
import {
    initialiseView,
    markCurrentAsKnown,
    resetFunctionCycle,
    reveal,
    setMode,
    setQuizMode,
    setWithoutRepeats,
    showFull,
    showPreviousItem,
    showRandomItem,
} from './study.js';

elements.next.addEventListener('click', showRandomItem);
elements.known.addEventListener('click', markCurrentAsKnown);
elements.previous.addEventListener('click', showPreviousItem);
elements.reveal.addEventListener('click', reveal);
elements.showFull.addEventListener('click', showFull);
elements.category.addEventListener('change', resetFunctionCycle);
elements.functionsMode.addEventListener('click', () => setMode('functions'));
elements.conceptsMode.addEventListener('click', () => setMode('concepts'));
elements.frameworksMode.addEventListener('click', () => setMode('frameworks'));
elements.tasksMode.addEventListener('click', () => { setMode('tasks'); showTask(); });
elements.quizByName.addEventListener('click', () => setQuizMode('name'));
elements.quizByDefinition.addEventListener('click', () => setQuizMode('definition'));
elements.withoutRepeats.addEventListener('click', () => setWithoutRepeats(!state.withoutRepeats));
elements.resetCycle.addEventListener('click', resetFunctionCycle);
elements.sandboxMode.addEventListener('click', () => setMode('sandbox'));
elements.sandboxRun.addEventListener('click', runSandbox);
elements.taskDifficulty.addEventListener('change', showTask);
elements.taskNext.addEventListener('click', showTask);
elements.taskRun.addEventListener('click', runTask);
elements.taskSolutionShow.addEventListener('click', revealTaskSolution);

document.addEventListener('keydown', (event) => {
    if (state.mode === 'sandbox' && event.ctrlKey && event.key === 'Enter') {
        event.preventDefault();
        runSandbox();
        return;
    }
    if (state.mode === 'sandbox') return;
    if (state.mode === 'tasks') return;
    if (!elements.cycleComplete.classList.contains('hidden')) {
        if (event.key === 'ArrowLeft') showPreviousItem();
        return;
    }
    if (event.key === 'ArrowRight') showRandomItem();
    if (event.key === 'ArrowLeft') showPreviousItem();
    if ((event.key === ' ' || event.key === 'Enter') && !elements.reveal.classList.contains('hidden')) {
        event.preventDefault();
        reveal();
    }
});

async function initialise() {
    await loadCatalogs();
    await loadKnowledge();
    initialiseView();
}

initialise().catch(() => {
    elements.progress.textContent = 'Ошибка загрузки';
    elements.error.textContent = 'Не удалось запустить приложение.';
    elements.error.classList.remove('hidden');
});
