/**
 * Складський облік — JavaScript
 * 
 * ДОДАНО: applyDateRange() для date panel
 */

// ========== Sidebar ==========
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
}

document.addEventListener('click', function(e) {
    var sidebar = document.getElementById('sidebar');
    var toggle = document.getElementById('sidebarToggle');
    if (sidebar && sidebar.classList.contains('open') && !sidebar.contains(e.target) && !toggle.contains(e.target)) {
        sidebar.classList.remove('open');
    }
});

// ========== Date Panel ==========
function toggleDatePanel(anchorEl) {
    var panel = document.getElementById('datePanel');
    var content = document.getElementById('datePanelContent');
    if (panel.classList.contains('open')) {
        panel.classList.remove('open');
        return;
    }
    if (anchorEl) {
        var rect = anchorEl.getBoundingClientRect();
        if (window.innerWidth <= 900) {
            content.style.top = '8px';
            content.style.left = '8px';
            content.style.right = '8px';
            content.style.width = 'auto';
        } else {
            content.style.top = (rect.bottom + 4) + 'px';
            content.style.right = (window.innerWidth - rect.right) + 'px';
            content.style.left = 'auto';
            content.style.width = '300px';
        }
    }
    panel.classList.add('open');
}

/**
 * Застосувати вибраний діапазон дат
 */
function applyDateRange() {
    var dateFrom = document.getElementById('dateFrom').value;
    var dateTo = document.getElementById('dateTo').value;
    
    if (!dateFrom || !dateTo) {
        alert('Оберіть обидві дати');
        return;
    }
    
    // Зберігаємо в cookie та сесію через AJAX
    fetch(window.basePath + '/settings/dates', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'date_from=' + encodeURIComponent(dateFrom) + '&date_to=' + encodeURIComponent(dateTo)
    })
    .then(function() {
        toggleDatePanel();
        location.reload();
    })
    .catch(function() {
        alert('Помилка збереження');
    });
}

// ========== Modal ==========
function openModal(title, content) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalBody').innerHTML = content;
    document.getElementById('modalBackdrop').classList.add('open');
    document.getElementById('modal').classList.add('open');
    document.body.style.overflow = 'hidden';

    setTimeout(function() {
        document.querySelectorAll('#modal select.autocomplete').forEach(function(sel) {
            buildAutocomplete(sel);
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
        if (dp && dp.classList.contains('open')) toggleDatePanel();
    }
});

// ========== AJAX Form Submit ==========
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

// ========== Autocomplete Select ==========
function buildAutocomplete(select) {
    if (select.dataset.acDone) return;
    select.dataset.acDone = '1';
    select.style.display = 'none';

    var placeholder = select.dataset.placeholder || '— Оберіть —';
    var submitOnChange = select.hasAttribute('data-submit-on-change');

    var options = [];
    for (var i = 0; i < select.options.length; i++) {
        options.push({
            value: select.options[i].value,
            text: select.options[i].textContent.trim(),
            selected: select.options[i].selected
        });
    }

    var currentOpt = options.find(function(o) { return o.selected && o.value; });
    var displayText = currentOpt ? currentOpt.text : placeholder;

    var wrap = document.createElement('div');
    wrap.className = 'ac-wrap';

    var display = document.createElement('div');
    display.className = 'ac-display' + (currentOpt ? ' has-value' : '');
    display.innerHTML = '<span class="ac-text">' + escapeHtml(displayText) + '</span>' +
        '<svg class="ac-arrow" width="12" height="12" viewBox="0 0 12 12"><path fill="currentColor" d="M6 8L1 3h10z"/></svg>';

    var dropdown = document.createElement('div');
    dropdown.className = 'ac-dropdown';

    var search = document.createElement('input');
    search.type = 'text';
    search.className = 'ac-input';
    search.placeholder = 'Пошук...';
    search.autocomplete = 'off';

    var list = document.createElement('div');
    list.className = 'ac-list';

    options.forEach(function(opt) {
        var item = document.createElement('div');
        item.className = 'ac-item' + (opt.selected && opt.value ? ' selected' : '');
        item.dataset.value = opt.value;
        item.textContent = opt.text;
        list.appendChild(item);
    });

    dropdown.appendChild(search);
    dropdown.appendChild(list);
    wrap.appendChild(display);
    wrap.appendChild(dropdown);
    select.parentNode.insertBefore(wrap, select.nextSibling);

    var isOpen = false;

    function open() {
        if (isOpen) return;
        closeAllAutocompletes();
        isOpen = true;
        wrap.classList.add('open');
        search.value = '';
        filterList('');
        search.focus();
        positionDropdown();
    }

    function close() {
        if (!isOpen) return;
        isOpen = false;
        wrap.classList.remove('open');
        wrap.classList.remove('drop-up');
        dropdown.classList.remove('drop-up');
    }

    function choose(value, text) {
        select.value = value;
        display.querySelector('.ac-text').textContent = text;
        display.classList.toggle('has-value', !!value);

        list.querySelectorAll('.ac-item').forEach(function(it) {
            it.classList.toggle('selected', it.dataset.value === value);
        });

        close();

        var evt = document.createEvent('HTMLEvents');
        evt.initEvent('change', true, false);
        select.dispatchEvent(evt);

        if (submitOnChange) {
            var form = select.closest('form');
            if (form) form.submit();
        }
    }

    function filterList(query) {
        var q = query.toLowerCase();
        var items = list.querySelectorAll('.ac-item');
        var anyVisible = false;
        items.forEach(function(item) {
            var match = !q || item.textContent.toLowerCase().indexOf(q) !== -1;
            item.style.display = match ? '' : 'none';
            if (match) anyVisible = true;
        });
        if (!anyVisible) {
            var empty = list.querySelector('.ac-empty');
            if (!empty) {
                empty = document.createElement('div');
                empty.className = 'ac-empty';
                empty.textContent = 'Нічого не знайдено';
                empty.style.cssText = 'padding:8px 12px;color:#999;font-size:13px;';
                list.appendChild(empty);
            }
        } else {
            var empty = list.querySelector('.ac-empty');
            if (empty) empty.remove();
        }
    }

    function positionDropdown() {
        var rect = wrap.getBoundingClientRect();
        var spaceBelow = window.innerHeight - rect.bottom;
        var spaceAbove = rect.top;
        var dropHeight = 200;
        
        if (spaceBelow < dropHeight && spaceAbove > spaceBelow) {
            wrap.classList.add('drop-up');
            dropdown.classList.add('drop-up');
        } else {
            wrap.classList.remove('drop-up');
            dropdown.classList.remove('drop-up');
        }
    }

    function closeAllAutocompletes() {
        document.querySelectorAll('.ac-wrap.open').forEach(function(w) {
            w.classList.remove('open');
        });
    }

    display.addEventListener('click', function(e) {
        e.stopPropagation();
        if (isOpen) {
            close();
        } else {
            open();
        }
    });

    search.addEventListener('input', function() {
        filterList(this.value);
    });

    search.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            var visible = list.querySelectorAll('.ac-item:not([style*="display:none"])');
            if (visible.length > 0) {
                choose(visible[0].dataset.value, visible[0].textContent);
            }
        } else if (e.key === 'Escape') {
            close();
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            var visible = list.querySelectorAll('.ac-item:not([style*="display:none"])');
            if (visible.length > 0) {
                choose(visible[0].dataset.value, visible[0].textContent);
            }
        }
    });

    document.addEventListener('click', function(e) {
        if (!wrap.contains(e.target)) {
            close();
        }
    });
}

// ========== Helper: escape HTML ==========
function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
