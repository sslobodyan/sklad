/**
 * Утиліти
 */
function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function closeAllAutocompletes() {
    document.querySelectorAll('.ac-wrap.open').forEach(function(w) {
        w.classList.remove('open');
    });
}