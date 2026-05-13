/**
 * Модалки для рухів (перегляд, редагування, створення)
 */
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

function openMovementModal(data) {
    data = data || {};
    var isEdit = !!data.id;
    var title = isEdit ? 'Редагувати рух' : 'Новий рух матеріалу';
    var action = (typeof basePath !== 'undefined' ? basePath : '') + '/movements/save/' + (isEdit ? data.id : '');

    var warehouses = window.warehousesList || [];
    var materials = window.materialsList || [];

    var materialOpts = '<option value="">— Оберіть матеріал —</option>';
    materials.forEach(function(m) {
        materialOpts += '<option value="' + m.id + '"' + (data.material_id == m.id ? ' selected' : '') + '>' + escapeHtml(m.name) + '</option>';
    });

    var fromOpts = '<option value="">Ззовні (прихід)</option>';
    warehouses.forEach(function(w) {
        fromOpts += '<option value="' + w.id + '"' + (data.warehouse_from_id == w.id ? ' selected' : '') + '>' + escapeHtml(w.name) + '</option>';
    });

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

    setTimeout(function() {
        var fromSel = document.querySelector('#selFrom');
        var toSel = document.querySelector('#selTo');
        if (fromSel && toSel && typeof updateTypeIndicator === 'function') {
            var update = function() { updateTypeIndicator(fromSel.value, toSel.value); };
            fromSel.addEventListener('change', update);
            toSel.addEventListener('change', update);
            update();
        }
    }, 100);
}