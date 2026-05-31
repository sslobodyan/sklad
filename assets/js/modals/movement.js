/**
 * Модалки для рухів (перегляд, редагування, створення)
 */

// Спільна функція для додавання історії змін
function addHistoryFeature(container, movementId) {
    if (!container || !movementId) return;
    
    var historyData = null;
    var showHistory = false;
    var originalContent = null;
    
    // Перевіряємо чи вже є кнопка історії
    if (container.querySelector('.history-btn')) return;
    
    // Завантажуємо історію
    fetch((typeof basePath !== 'undefined' ? basePath : '') + '/movements/history/' + movementId)
        .then(function(r) { return r.json(); })
        .then(function(result) {
            if (result.success && result.history && result.history.length > 0) {
                historyData = result.history;
                originalContent = container.innerHTML;
                
                // Створюємо обгортку для контенту та кнопки
                var wrapper = document.createElement('div');
                wrapper.style.display = 'flex';
                wrapper.style.alignItems = 'flex-start';
                wrapper.style.justifyContent = 'space-between';
                wrapper.style.width = '100%';
                wrapper.style.gap = '8px';
                
                // Контейнер для текстового вмісту (мета-інформація або історія)
                var contentDiv = document.createElement('div');
                contentDiv.style.flex = '1';
                contentDiv.style.minWidth = '0';
                contentDiv.innerHTML = originalContent;
                
                // Створюємо кнопку для історії (пісочний годинник)
                var historyBtn = document.createElement('button');
                historyBtn.className = 'btn-icon history-btn';
                historyBtn.style.flexShrink = '0';
                historyBtn.style.padding = '2px';
                historyBtn.style.cursor = 'pointer';
                historyBtn.style.display = 'inline-flex';
                historyBtn.style.alignItems = 'center';
                historyBtn.style.justifyContent = 'center';
                historyBtn.style.backgroundColor = 'transparent';
                historyBtn.style.border = 'none';
                historyBtn.style.alignSelf = 'center';
                historyBtn.setAttribute('title', 'Історія змін');
                historyBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                    '<ellipse cx="12" cy="6" rx="4" ry="2"/>' +
                    '<ellipse cx="12" cy="18" rx="4" ry="2"/>' +
                    '<path d="M8 6v2c0 2 1.5 3 4 3s4-1 4-3V6"/>' +
                    '<path d="M8 18v-2c0-2 1.5-3 4-3s4 1 4 3v2"/>' +
                    '<path d="M12 11v2"/>' +
                '</svg>';
                
                // Очищуємо контейнер і додаємо обгортку
                container.innerHTML = '';
                wrapper.appendChild(contentDiv);
                wrapper.appendChild(historyBtn);
                container.appendChild(wrapper);
                
                historyBtn.onclick = function(e) {
                    e.stopPropagation();
                    e.preventDefault();
                    
                    if (!showHistory) {
                        var historyHtml = '<div style="max-height: 36px; overflow-y: auto; padding-right: 4px;">';
                        historyData.forEach(function(h) {
                            historyHtml += 
                                '<div style="border-bottom: 1px solid #f0f0f0; padding: 2px 0; display: flex; justify-content: space-between; gap: 8px; font-size: 9px;">' +
                                    '<div>' + escapeHtml(h.changed_by || '—') + '</div>' +
                                    '<div style="font-family: monospace;">' + h.changed_at_formatted + '</div>' +
                                '</div>';
                        });
                        historyHtml += '</div>';
                        contentDiv.innerHTML = historyHtml;
                        showHistory = true;
                        historyBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                            '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>' +
                            '<line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>' +
                            '<line x1="3" y1="10" x2="21" y2="10"/>' +
                        '</svg>';
                        historyBtn.setAttribute('title', 'Повернутись до мета-інформації');
                        // Вирівнюємо кнопку по центру відносно контенту
                        historyBtn.style.alignSelf = 'flex-start';
                        historyBtn.style.marginTop = '2px';
                    } else {
                        contentDiv.innerHTML = originalContent;
                        showHistory = false;
                        historyBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                            '<ellipse cx="12" cy="6" rx="4" ry="2"/>' +
                            '<ellipse cx="12" cy="18" rx="4" ry="2"/>' +
                            '<path d="M8 6v2c0 2 1.5 3 4 3s4-1 4-3V6"/>' +
                            '<path d="M8 18v-2c0-2 1.5-3 4-3s4 1 4 3v2"/>' +
                            '<path d="M12 11v2"/>' +
                        '</svg>';
                        historyBtn.setAttribute('title', 'Історія змін');
                        historyBtn.style.alignSelf = 'center';
                    }
                };
            }
        })
        .catch(function() {});
}
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
        var correction = parseFloat(data.resource_correction) || 0;
        var correctionDisplay = '—';
        if (correction !== 0) {
            correctionDisplay = (correction > 0 ? '+' : '') + correction + '%';
        }
        extraRows +=
            '<tr><td class="view-label">Норма<\/td><td class="font-mono">' + escapeHtml(String(data.resource_rate ?? '—')) + '<\/td><\/tr>' +
            '<tr><td class="view-label">Поправка<\/td><td class="font-mono">' + escapeHtml(correctionDisplay) + '<\/td><\/tr>' +
            '<tr><td class="view-label">Показник<\/td><td class="font-mono">' + escapeHtml(formatResourceValue(data.resource_value, data.resource_format || 'dec2')) +
                ' (' + escapeHtml(data.resource_unit || '—') + ')<\/td><\/tr>';
    }

    var content =
        '<table class="view-table">' +
            '<tr><td class="view-label">Дата<\/td><td>' + dateStr + '<\/td><\/tr>' +
            '<tr><td class="view-label">Звідки<\/td><td>' + escapeHtml(fromName) + '<\/td><\/tr>' +
            '<tr><td class="view-label">Куди<\/td><td>' + escapeHtml(toName) + '<\/td><\/tr>' +
            '<tr><td class="view-label">Матеріал<\/td><td>' + escapeHtml(matName) + '<\/td><\/tr>' +
            '<tr><td class="view-label">Кількість<\/td><td class="font-mono font-bold">' + parseFloat(data.quantity).toFixed(2) + '<\/td><\/tr>' +
            extraRows +
            '<tr><td class="view-label">Примітка<\/td><td>' + escapeHtml(data.note || '—') + '<\/td><\/tr>' +
        '<\/table>' +
        '<div class="modal-footer modal-meta" id="viewMovementMetaFooter">' +
            '<div class="modal-meta-info" id="metaInfoContainer" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;"></div>' +
            '<div class="modal-meta-buttons">' +
                '<button type="button" class="btn btn-secondary" onclick="closeModal()">Закрити</button>' +
            '</div>' +
        '</div>';

    openModal('Перегляд руху', content);
    
    var metaContainer = document.getElementById('metaInfoContainer');
    
    // Завантажуємо мета-інформацію
    if (data.id) {
        fetch((typeof basePath !== 'undefined' ? basePath : '') + '/movements/getone/' + data.id)
            .then(function(r) { return r.json(); })
            .then(function(result) {
                if (result.success && result.data) {
                    var metaHtml = 
                        '<div class="modal-meta-line modal-meta-author">' + escapeHtml(result.data.author || '') + '</div>' +
                        '<div class="modal-meta-line modal-meta-date">';
                    if (result.data.updated_at && result.data.updated_at !== result.data.created_at) {
                        var updated = new Date(result.data.updated_at);
                        metaHtml += updated.toLocaleDateString('uk-UA') + ' ' + updated.toLocaleTimeString('uk-UA', {hour:'2-digit', minute:'2-digit'});
                    } else if (result.data.created_at) {
                        var created = new Date(result.data.created_at);
                        metaHtml += created.toLocaleDateString('uk-UA') + ' ' + created.toLocaleTimeString('uk-UA', {hour:'2-digit', minute:'2-digit'});
                    }
                    metaHtml += '</div>';
                    metaContainer.innerHTML = metaHtml;
                    
                    // Додаємо історію
                    addHistoryFeature(metaContainer, data.id);
                }
            })
            .catch(function() {});
    }
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
            '<div class="modal-footer modal-meta" id="movementMetaFooter">' +
                '<div class="modal-meta-info" id="movementMetaInfo" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;"></div>' +
                '<div class="modal-meta-buttons">' +
                    '<button type="button" class="btn btn-secondary" onclick="closeModal()">Скасувати</button>' +
                    '<button type="submit" class="btn btn-primary">' + (isEdit ? 'Зберегти' : 'Додати') + '</button>' +
                '</div>' +
            '</div>' +
        '</form>';

    openModal(title, content);

    var metaContainer = document.getElementById('movementMetaInfo');

    if (isEdit) {
        // Завантажуємо мета-інформацію
        fetch((typeof basePath !== 'undefined' ? basePath : '') + '/movements/getone/' + data.id)
            .then(function(r) { return r.json(); })
            .then(function(result) {
                if (result.success && result.data) {
                    var metaHtml = 
                        '<div class="modal-meta-line modal-meta-author">' + escapeHtml(result.data.author || '') + '</div>' +
                        '<div class="modal-meta-line modal-meta-date">';
                    if (result.data.updated_at && result.data.updated_at !== result.data.created_at) {
                        var updated = new Date(result.data.updated_at);
                        metaHtml += updated.toLocaleDateString('uk-UA') + ' ' + updated.toLocaleTimeString('uk-UA', {hour:'2-digit', minute:'2-digit'});
                    } else if (result.data.created_at) {
                        var created = new Date(result.data.created_at);
                        metaHtml += created.toLocaleDateString('uk-UA') + ' ' + created.toLocaleTimeString('uk-UA', {hour:'2-digit', minute:'2-digit'});
                    }
                    metaHtml += '</div>';
                    metaContainer.innerHTML = metaHtml;
                    
                    // Додаємо історію
                    addHistoryFeature(metaContainer, data.id);
                }
            })
            .catch(function() {});
    }

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

/**
 * Створення копії існуючого руху
 * @param {Object} data - дані руху (з `$jsData`)
 */
function copyMovement(data) {
    // Створюємо об'єкт-копію без id та без resource_log_id
    var copyData = {
        movement_date: data.movement_date,
        warehouse_from_id: data.warehouse_from_id,
        warehouse_to_id: data.warehouse_to_id,
        material_id: data.material_id,
        quantity: data.quantity,
        note: data.note || '',
        // НЕ передаємо id, resource_log_id, resource_*
        // це буде новий запис
    };
    // Відкриваємо модальне вікно для створення НОВОГО запису
    // Передаємо copyData без поля id
    openMovementModal(copyData);
}