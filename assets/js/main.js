/**
 * Головний файл — ініціалізація всіх компонентів
 */
document.addEventListener('DOMContentLoaded', function() {
    // Ініціалізація autocomplete для всіх select, які вже є на сторінці (не в модалках)
    if (typeof buildAutocomplete === 'function') {
        document.querySelectorAll('select.autocomplete').forEach(function(sel) {
            buildAutocomplete(sel);
        });
    }
});