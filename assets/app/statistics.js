import { elements } from './dom.js';
import { categories, categoryItems, counters, state } from './state.js';

function readCounter(name) {
    // Повреждённое или отрицательное значение cookie не должно попадать в интерфейс.
    const prefix = `${name}=`;
    const cookie = document.cookie.split('; ').find((item) => item.startsWith(prefix));
    if (!cookie) return 0;
    const value = Number.parseInt(decodeURIComponent(cookie.slice(prefix.length)), 10);
    return Number.isFinite(value) && value >= 0 ? value : 0;
}

export function updateStatistics() {
    elements.knownCount.textContent = readCounter(counters.known).toLocaleString('ru-RU');
    elements.revealCount.textContent = readCounter(counters.reveal).toLocaleString('ru-RU');
}

export function incrementCounter(name) {
    // Гостевые счётчики сохраняются на год и доступны на всех маршрутах приложения.
    const value = readCounter(name) + 1;
    document.cookie = `${name}=${value}; Max-Age=${60 * 60 * 24 * 365}; Path=/; SameSite=Lax`;
    updateStatistics();
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
