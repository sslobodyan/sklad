/**
 * Складський облік — JavaScript
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
        // Для мобільного — панель як компактний drawer по центру/на всю ширину
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

// ========== Modal ==========
function openModal(title, content) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalBody').innerHTML = content;
    document.getElementById('modalBackdrop').classList.add('open');
    document.getElementById('modal').classList.add('open');
    document.body.style.overflow = 'hidden';

    // Перетворюємо всі <select class="autocomplete"> в модалці
    setTimeout(function() {
        document.querySelectorAll('#modal select.autocomplete').forEach(function(sel) {
            buildAutocomplete(sel);
        });
        // Фокус на перший input
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
        console.log(formData);
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
//
// Обгортає будь-який <select class="autocomplete"> у красивий випадаючий список
// з полем пошуку.
//
// Атрибути:
//   data-placeholder="..."   — текст коли нічого не вибрано
//   data-submit-on-change    — відправити форму після вибору
//
function buildAutocomplete(select) {
    if (select.dataset.acDone) return;
    select.dataset.acDone = '1';
    select.style.display = 'none';

    var placeholder = select.dataset.placeholder || '— Оберіть —';
    var submitOnChange = select.hasAttribute('data-submit-on-change');

    // Збираємо опції
    var options = [];
    for (var i = 0; i < select.options.length; i++) {
        options.push({
            value: select.options[i].value,
            text: select.options[i].textContent.trim(),
            selected: select.options[i].selected
        });
    }

    // Поточне значення
    var currentOpt = options.find(function(o) { return o.selected && o.value; });
    var displayText = currentOpt ? currentOpt.text : placeholder;

    // Створюємо DOM
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

    // ---- Логіка ----
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

        // Оновити selected клас
        list.querySelectorAll('.ac-item').forEach(function(it) {
            it.classList.toggle('selected', it.dataset.value === value);
        });

        close();

        // Trigger change
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
    }

    function positionDropdown() {
        // Перевірка чи достатньо місця знизу
        var rect = wrap.getBoundingClientRect();
        var spaceBelow = window.innerHeight - rect.bottom;
        if (spaceBelow < 250) {
            wrap.classList.add('drop-up');
            dropdown.classList.add('drop-up');
        } else {
            wrap.classList.remove('drop-up');
            dropdown.classList.remove('drop-up');
        }
    }

    // ---- Events ----
    display.addEventListener('click', function(e) {
        e.stopPropagation();
        if (isOpen) close(); else open();
    });

    search.addEventListener('input', function() {
        filterList(this.value);
    });

    search.addEventListener('click', function(e) { e.stopPropagation(); });

    search.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            close();
        }
        if (e.key === 'Enter') {
            e.preventDefault();
            // Вибрати перший видимий
            var visible = list.querySelector('.ac-item:not([style*="display: none"])');
            if (visible) {
                choose(visible.dataset.value, visible.textContent);
            }
        }
    });

    list.addEventListener('click', function(e) {
        e.stopPropagation();
        var item = e.target.closest('.ac-item');
        if (item) {
            choose(item.dataset.value, item.textContent);
        }
    });

    // Закриття по кліку ззовні
    document.addEventListener('click', function(e) {
        if (isOpen && !wrap.contains(e.target)) {
            close();
        }
    });
}

function closeAllAutocompletes() {
    document.querySelectorAll('.ac-wrap.open').forEach(function(w) {
        w.classList.remove('open');
    });
}

// Автоматичне підключення на сторінці (не в модалці)
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('select.autocomplete').forEach(function(sel) {
        buildAutocomplete(sel);
    });
});

// ========== Warehouse Modal ==========
function openWarehouseModal(id, name) {
    var isEdit = !!id;
    var title = isEdit ? 'Редагувати склад' : 'Новий склад';
    var action = isEdit ? basePath + '/warehouses/save/' + id : basePath + '/warehouses/save';

    var content =
        '<form id="warehouseForm" action="' + action + '" method="POST" onsubmit="submitForm(this); return false;">' +
            '<div class="form-group">' +
                '<label class="form-label">Назва складу <span class="required">*</span></label>' +
                '<input type="text" name="name" class="form-input" required ' +
                       'value="' + escapeHtml(name || '') + '" placeholder="Наприклад: Основний склад">' +
            '</div>' +
            '<div class="modal-footer">' +
                '<button type="button" class="btn btn-secondary" onclick="closeModal()">Скасувати</button>' +
                '<button type="submit" class="btn btn-primary">' + (isEdit ? 'Зберегти' : 'Додати') + '</button>' +
            '</div>' +
        '</form>';

    openModal(title, content);
}

// ========== Material Modal ==========
function openMaterialModal(id, name) {
    var isEdit = !!id;
    var title = isEdit ? 'Редагувати матеріал' : 'Новий матеріал';
    var action = isEdit ? basePath + '/materials/save/' + id : basePath + '/materials/save';

    var content =
        '<form id="materialForm" action="' + action + '" method="POST" onsubmit="submitForm(this); return false;">' +
            '<div class="form-group">' +
                '<label class="form-label">Назва матеріалу <span class="required">*</span></label>' +
                '<input type="text" name="name" class="form-input" required ' +
                       'value="' + escapeHtml(name || '') + '" placeholder="Наприклад: Дизельне пальне">' +
            '</div>' +
            '<div class="modal-footer">' +
                '<button type="button" class="btn btn-secondary" onclick="closeModal()">Скасувати</button>' +
                '<button type="submit" class="btn btn-primary">' + (isEdit ? 'Зберегти' : 'Додати') + '</button>' +
            '</div>' +
        '</form>';

    openModal(title, content);
}

// ========== Movement View (read-only) ==========
function viewMovementModal(data) {
    if (!data) return;

    var warehouses = window.warehousesList || [];
    var materials = window.materialsList || [];

    var fromName = '—';
    var toName = '—';
    warehouses.forEach(function(w) {
        if (w.id == data.warehouse_from_id) fromName = w.name;
        if (w.id == data.warehouse_to_id) toName = w.name;
    });
    if (!data.warehouse_from_id) fromName = 'Ззовні (прихід)';
    if (!data.warehouse_to_id) toName = 'Списання';

    var matName = '—';
    materials.forEach(function(m) {
        if (m.id == data.material_id) matName = m.name;
    });

    var parts = data.movement_date.split('-');
    var dateStr = parts.length === 3 ? parts[2] + '.' + parts[1] + '.' + parts[0] : data.movement_date;

    function formatResourceValue(val, fmt) {
        if (val === null || val === '' || typeof val === 'undefined') return '—';
        val = parseFloat(val);
        if (fmt === 'hm') {
            var h = Math.floor(val);
            var m = Math.round((val - h) * 60);
            return h + ':' + (m < 10 ? '0' : '') + m;
        }
        if (fmt === 'int') return Math.round(val).toString();
        return val.toFixed(2);
    }

    var extraRows = '';
    if (data.resource_log_id) {
        extraRows += '<tr><td class="view-label">Норма</td><td>' + escapeHtml(String(data.resource_rate ?? '—')) + '</td></tr>';
        extraRows += '<tr><td class="view-label">Ресурс</td><td>' + escapeHtml(data.resource_unit || '—') + '</td></tr>';
        extraRows += '<tr><td class="view-label">Показник</td><td>' + escapeHtml(formatResourceValue(data.resource_value, data.resource_format || 'dec2')) + '</td></tr>';
    }

    var content =
        '<table class="view-table">' +
            '<tr><td class="view-label">Дата</td><td>' + dateStr + '</td></tr>' +
            '<tr><td class="view-label">Звідки</td><td>' + escapeHtml(fromName) + '</td></tr>' +
            '<tr><td class="view-label">Куди</td><td>' + escapeHtml(toName) + '</td></tr>' +
            '<tr><td class="view-label">Матеріал</td><td>' + escapeHtml(matName) + '</td></tr>' +
            '<tr><td class="view-label">Кількість</td><td class="font-mono font-bold">' + parseFloat(data.quantity).toFixed(2) + '</td></tr>' +
            extraRows +
            '<tr><td class="view-label">Примітка</td><td>' + escapeHtml(data.note || '—') + '</td></tr>' +
        '</table>' +
        '<div class="modal-footer">' +
            '<button type="button" class="btn btn-secondary" onclick="closeModal()">Закрити</button>' +
        '</div>';

    openModal('Перегляд руху', content);
}

// ========== Movement Modal ==========
function openMovementModal(data) {
    data = data || {};
    var isEdit = !!data.id;
    var title = isEdit ? 'Редагувати рух' : 'Новий рух матеріалу';
    var action = isEdit ? basePath + '/movements/save/' + data.id : basePath + '/movements/save';

    var warehouses = window.warehousesList || [];
    var materials = window.materialsList || [];

    // Матеріал select
    var materialOpts = '<option value="">— Оберіть матеріал —</option>';
    materials.forEach(function(m) {
        materialOpts += '<option value="' + m.id + '"' + (data.material_id == m.id ? ' selected' : '') + '>' + escapeHtml(m.name) + '</option>';
    });

    // Склад-звідки
    var fromOpts = '<option value="">Ззовні (прихід)</option>';
    warehouses.forEach(function(w) {
        fromOpts += '<option value="' + w.id + '"' + (data.warehouse_from_id == w.id ? ' selected' : '') + '>' + escapeHtml(w.name) + '</option>';
    });

    // Склад-куди
    var toOpts = '<option value="">Списання (видача)</option>';
    warehouses.forEach(function(w) {
        toOpts += '<option value="' + w.id + '"' + (data.warehouse_to_id == w.id ? ' selected' : '') + '>' + escapeHtml(w.name) + '</option>';
    });

    var today = new Date().toISOString().split('T')[0];

    var content =
        '<form id="movementForm" action="' + action + '" method="POST" onsubmit="submitForm(this); return false;">' +
            '<div class="type-indicator type-none" id="typeIndicator"><span id="typeText">Оберіть хоча б один склад</span></div>' +

            '<div class="form-row">' +
                '<div class="form-group">' +
                    '<label class="form-label">Дата <span class="required">*</span></label>' +
                    '<input type="date" name="movement_date" class="form-input" required value="' + (data.movement_date || today) + '">' +
                '</div>' +
                '<div class="form-group">' +
                    '<label class="form-label">Кількість <span class="required">*</span></label>' +
                    '<input type="number" name="quantity" class="form-input" required min="0.01" step="0.01" value="' + (data.quantity || '') + '" placeholder="0">' +
                '</div>' +
            '</div>' +

            '<div class="form-group">' +
                '<label class="form-label">Матеріал <span class="required">*</span></label>' +
                '<select name="material_id" class="autocomplete" data-placeholder="— Оберіть матеріал —" required>' + materialOpts + '</select>' +
            '</div>' +

            '<div class="form-row">' +
                '<div class="form-group">' +
                    '<label class="form-label">Звідки</label>' +
                    '<select name="warehouse_from_id" class="autocomplete" data-placeholder="Ззовні (прихід)" id="selFrom">' + fromOpts + '</select>' +
                    '<div class="form-hint">Порожньо = ззовні</div>' +
                '</div>' +
                '<div class="form-group">' +
                    '<label class="form-label">Куди</label>' +
                    '<select name="warehouse_to_id" class="autocomplete" data-placeholder="Списання (видача)" id="selTo">' + toOpts + '</select>' +
                    '<div class="form-hint">Порожньо = списання</div>' +
                '</div>' +
            '</div>' +

            '<div class="form-group">' +
                '<label class="form-label">Примітка</label>' +
                '<input type="text" name="note" class="form-input" value="' + escapeHtml(data.note || '') + '" placeholder="Необов\'язкове поле">' +
            '</div>' +

            '<div class="modal-footer">' +
                '<button type="button" class="btn btn-secondary" onclick="closeModal()">Скасувати</button>' +
                '<button type="submit" class="btn btn-primary">' + (isEdit ? 'Зберегти' : 'Додати') + '</button>' +
            '</div>' +
        '</form>';

    openModal(title, content);

    // Після побудови autocomplete — підключити зміну типу
    setTimeout(function() {
        var fromSel = document.querySelector('#selFrom');
        var toSel = document.querySelector('#selTo');
        if (fromSel && toSel) {
            var update = function() { updateTypeIndicator(fromSel.value, toSel.value); };
            fromSel.addEventListener('change', update);
            toSel.addEventListener('change', update);
            update();
        }
    }, 100);
}

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

// ========== Delete Confirmation ==========
function confirmDelete(url, message) {
    message = message || 'Ви впевнені, що хочете видалити?';

    var content =
        '<div class="confirm-message">' +
            '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="1.5">' +
                '<path d="M12 9v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>' +
            '</svg>' +
            '<p>' + message + '</p>' +
        '</div>' +
        '<div class="modal-footer">' +
            '<button type="button" class="btn btn-secondary" onclick="closeModal()">Скасувати</button>' +
            '<a href="' + url + '" class="btn btn-danger">Видалити</a>' +
        '</div>';

    openModal('Підтвердження', content);
}

// ========== Import Modal ==========
function openImportModal() {
    var content =
        '<form id="importForm" action="' + basePath + '/movements/import" method="POST" enctype="multipart/form-data">' +
            '<div class="import-info">' +
                '<p>Файл Excel (.xlsx) повинен мати такі колонки:</p>' +
                '<table class="import-format-table">' +
                    '<tr><th>A</th><th>B</th><th>C</th><th>D</th><th>E</th><th>F</th></tr>' +
                    '<tr><td>Дата</td><td>Звідки</td><td>Куди</td><td>Матеріал</td><td>Кількість</td><td>Примітка</td></tr>' +
                    '<tr class="example"><td>15.03.2025</td><td>Склад ПММ</td><td>OSHKOSH 74-25</td><td>Олива 15W40</td><td>10</td><td>Видача</td></tr>' +
                '</table>' +
                '<ul class="import-hints">' +
                    '<li>Перший рядок — заголовок (буде пропущений)</li>' +
                    '<li>Формат дати: <b>DD.MM.YYYY</b> або YYYY-MM-DD</li>' +
                    '<li>Якщо складу чи матеріалу немає в довіднику — він буде <b>створений автоматично</b></li>' +
                    '<li>Звідки / Куди можна лишити порожнім (прихід / списання)</li>' +
                '</ul>' +
            '</div>' +
            '<div class="form-group">' +
                '<label class="form-label">Файл Excel <span class="required">*</span></label>' +
                '<input type="file" name="file" class="form-input" accept=".xlsx" required id="importFile">' +
            '</div>' +
            '<label class="import-checkbox">' +
                '<input type="checkbox" name="clear_existing" value="1">' +
                '<span>Видалити поточні дані руху перед імпортом</span>' +
            '</label>' +
            '<div id="importPreview" style="display:none"></div>' +
            '<div class="modal-footer">' +
                '<button type="button" class="btn btn-secondary" onclick="closeModal()">Скасувати</button>' +
                '<button type="submit" class="btn btn-primary" id="importSubmitBtn">Імпортувати</button>' +
            '</div>' +
        '</form>';

    openModal('Імпорт руху з Excel', content);
}

// ========== Utility ==========
function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
