/**
 * Індикатор типу руху (надходження/видача/переміщення)
 */
function updateTypeIndicator(from, to) {
    var indicator = document.getElementById('typeIndicator');
    var text = document.getElementById('typeText');
    if (!indicator) return;

    indicator.className = 'type-indicator';

    if (!from && to) {
        indicator.classList.add('type-in');
        text.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14"/><path d="M19 12l-7 7-7-7"/></svg> Надходження на склад';
    } else if (from && !to) {
        indicator.classList.add('type-out');
        text.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19V5"/><path d="M5 12l7-7 7 7"/></svg> Видача / списання';
    } else if (from && to) {
        indicator.classList.add('type-transfer');
        text.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 014-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 01-4 4H3"/></svg> Переміщення між складами';
    } else {
        indicator.classList.add('type-none');
        text.textContent = 'Оберіть хоча б один склад';
    }
}