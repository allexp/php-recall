import { runCode } from './api.js';
import { elements } from './dom.js';

export async function runSandbox() {
    // Повторный запуск блокируется до получения ответа от изолированного контейнера.
    elements.sandboxRun.disabled = true;
    elements.sandboxStatus.textContent = 'Создаю изолированный контейнер…';
    elements.sandboxError.classList.add('hidden');
    elements.sandboxResult.classList.add('hidden');
    try {
        const code = window.phpSandboxEditor?.getValue() ?? elements.sandboxCode.value;
        const data = await runCode(code);
        elements.sandboxOutput.textContent = data.output || '(программа не вывела результат)';
        elements.sandboxResult.classList.remove('hidden');
        elements.sandboxStatus.textContent = data.timed_out
            ? 'Остановлено по тайм-ауту'
            : `Завершено, код выхода: ${data.exit_code}`;
    } catch (error) {
        elements.sandboxError.textContent = error.message;
        elements.sandboxError.classList.remove('hidden');
        elements.sandboxStatus.textContent = 'Ошибка запуска';
    } finally {
        elements.sandboxRun.disabled = false;
    }
}
