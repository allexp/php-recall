import { loadTask, loadTaskSolution, runCode } from './api.js';
import { elements } from './dom.js';

let currentTask = null;
const labels = { easy: 'Лёгкая задача', medium: 'Средняя задача', hard: 'Сложная задача' };

export async function showTask() {
    setBusy(true);
    elements.taskError.classList.add('hidden');
    try {
        currentTask = await loadTask(elements.taskDifficulty.value);
        elements.taskLevelLabel.textContent = labels[currentTask.difficulty];
        elements.taskTitle.textContent = currentTask.title;
        elements.taskDescription.textContent = currentTask.description;
        if (window.phpTaskEditor) window.phpTaskEditor.setValue(currentTask.starter_code);
        else elements.taskCode.value = currentTask.starter_code;
        elements.taskResult.classList.add('hidden');
        elements.taskSolution.classList.add('hidden');
        window.phpTaskSolutionViewer?.setValue('');
        elements.taskSolutionShow.classList.remove('hidden');
        elements.taskStatus.textContent = 'Готово к решению';
    } catch (error) { showError(error); } finally { setBusy(false); }
}

export async function runTask() {
    setBusy(true);
    elements.taskError.classList.add('hidden');
    try {
        const code = window.phpTaskEditor?.getValue() ?? elements.taskCode.value;
        const data = await runCode(code);
        elements.taskOutput.textContent = data.output || '(программа не вывела результат)';
        elements.taskResult.classList.remove('hidden');
        elements.taskStatus.textContent = data.timed_out ? 'Остановлено по тайм-ауту' : `Завершено, код выхода: ${data.exit_code}`;
    } catch (error) { showError(error); } finally { setBusy(false); }
}

export async function revealTaskSolution() {
    if (!currentTask) return;
    setBusy(true);
    try {
        const data = await loadTaskSolution(currentTask.id);
        if (window.phpTaskSolutionViewer) window.phpTaskSolutionViewer.setValue(data.solution);
        else elements.taskSolutionCode.value = data.solution;
        elements.taskSolution.classList.remove('hidden');
        elements.taskSolutionShow.classList.add('hidden');
    } catch (error) { showError(error); } finally { setBusy(false); }
}

function setBusy(busy) {
    elements.taskRun.disabled = busy;
    elements.taskNext.disabled = busy;
    elements.taskSolutionShow.disabled = busy;
}

function showError(error) {
    elements.taskError.textContent = error.message;
    elements.taskError.classList.remove('hidden');
    elements.taskStatus.textContent = 'Ошибка';
}
