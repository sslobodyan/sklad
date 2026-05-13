/**
 * AJAX-відправка форм
 */
function submitForm(form) {
    var formData = new FormData(form);
    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) { return r.json(); })
    .then(function(result) {
        if (result.success) {
            closeModal();
            location.reload();
        } else {
            var err = result.error || 'Помилка збереження';
            var info = document.getElementById('contextInfo');
            if (info && (err.indexOf('Показник не може') !== -1)) {
                var box = info.querySelector('.resource-context-box');
                if (!box) {
                    info.innerHTML = '<div class="resource-context-box"></div>';
                    box = info.querySelector('.resource-context-box');
                }
                var oldErr = box.querySelector('.resource-context-error');
                if (oldErr) oldErr.remove();
                box.innerHTML += '<div class="resource-context-error">⚠ ' + escapeHtml(err) + '</div>';
            } else {
                showFormError(err);
            }
        }
    })
    .catch(function() {
        showFormError('Помилка з\'єднання');
    });
}

function showFormError(message) {
    var el = document.getElementById('modalError');
    if (!el) {
        el = document.createElement('div');
        el.id = 'modalError';
        el.className = 'modal-error';
        document.getElementById('modalBody').prepend(el);
    }
    el.textContent = message;
    el.style.display = 'block';
}