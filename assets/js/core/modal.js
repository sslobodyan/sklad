/**
 * Базові функції модальних вікон
 */
function openModal(title, content) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalBody').innerHTML = content;
    document.getElementById('modalBackdrop').classList.add('open');
    document.getElementById('modal').classList.add('open');
    document.body.style.overflow = 'hidden';

    setTimeout(function() {
        document.querySelectorAll('#modal select.autocomplete').forEach(function(sel) {
            if (typeof buildAutocomplete === 'function') buildAutocomplete(sel);
        });
        var first = document.querySelector('#modal .ac-input, #modal input:not([type="hidden"])');
        if (first) first.focus();
    }, 50);
}

function closeModal() {
    document.getElementById('modalBackdrop').classList.remove('open');
    document.getElementById('modal').classList.remove('open');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
        var dp = document.getElementById('datePanel');
        if (dp && dp.classList.contains('open') && typeof toggleDatePanel === 'function') toggleDatePanel();
    }
});