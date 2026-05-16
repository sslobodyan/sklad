<div class="page-header">
    <div>
        <h1 class="page-title">Витрата ресурсів</h1>
        <p class="page-subtitle">Введення показників одометра, мотогодин тощо</p>
    </div>
    <div class="header-buttons">
        <a href="<?= $basePath ?>/resources/export?<?= http_build_query(array_filter($filters)) ?>" class="btn btn-secondary" title="Експорт у Excel">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                <polyline points="7 10 12 15 17 10"/>
                <line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
            Експорт
        </a>
        <button class="btn btn-secondary" onclick="window.print()" title="Друк">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
                <rect x="6" y="14" width="12" height="8"/>
            </svg>
            Друк
        </button>
    </div>
</div>

<div class="card filter-panel">
    <form method="GET" class="filter-grid filter-grid-with-action">
        <div class="form-group">
            <label class="form-label">Дата від</label>
            <input type="date" name="date_from" class="form-input" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Дата до</label>
            <input type="date" name="date_to" class="form-input" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Склад</label>
            <select name="warehouse_id" class="autocomplete" data-placeholder="Усі склади">
                <option value="">Усі склади</option>
                <?php foreach ($warehousesWithResources as $w): ?>
                <option value="<?= $w['id'] ?>" <?= ($filters['warehouse_id'] ?? '') == $w['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($w['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Тип ресурсу</label>
            <select name="resource_type_id" class="form-input form-select">
                <option value="">Усі</option>
                <?php foreach ($types as $t): ?>
                <option value="<?= $t['id'] ?>" <?= ($filters['resource_type_id'] ?? '') == $t['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($t['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group filter-action-inline">
            <label class="form-label">&nbsp;</label>
            <div class="filter-action-stack">
                <button type="submit" class="btn btn-primary">Застосувати</button>
                <a href="<?= $basePath ?>/resources?date_from=<?= urlencode($globalDateFrom) ?>&date_to=<?= urlencode($globalDateTo) ?>" class="btn btn-secondary btn-sm">Скинути</a>
            </div>
        </div>
    </form>
</div>

<?php
$printFilters = [];
if (!empty($filters['warehouse_id'])) {
    foreach ($warehousesWithResources as $w) {
        if ($w['id'] == $filters['warehouse_id']) { $printFilters[] = 'склад: ' . $w['name']; break; }
    }
}
if (!empty($filters['resource_type_id'])) {
    foreach ($types as $t) {
        if ($t['id'] == $filters['resource_type_id']) { $printFilters[] = 'ресурс: ' . $t['name']; break; }
    }
}
if (!empty($filters['date_from'])) $printFilters[] = 'від ' . formatDateUa($filters['date_from']);
if (!empty($filters['date_to'])) $printFilters[] = 'до ' . formatDateUa($filters['date_to']);
?>
<div class="print-header">
    <?php if ($printFilters): ?>
    <div><strong>Фільтри:</strong> <?= htmlspecialchars(implode(' • ', $printFilters)) ?></div>
    <?php endif; ?>
    <div><strong>Записів:</strong> <?= count($logs) ?> • <strong>Дата друку:</strong> <?= date('d.m.Y H:i') ?></div>
</div>

<div class="card card-stretch">
    <?php if (empty($logs) && empty($warehousesWithResources)): ?>
    <div class="empty-state">
        <p>Спочатку налаштуйте <a href="<?= $basePath ?>/resources/rates">норми списання</a></p>
    </div>
    <?php elseif (empty($logs)): ?>
    <div class="empty-state">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>
        </svg>
        <p>Записів поки немає</p>
        <button class="btn btn-primary btn-sm" onclick="openReadingModal()">Додати перший показник</button>
    </div>
    <?php else: ?>
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-date">Дата</th>
                    <th>Склад</th>
                    <th class="col-resource">Ресурс</th>
                    <th class="text-right col-reading">Показник</th>
                    <th class="text-right col-reading">Попередній</th>
                    <th class="text-right col-delta">Δ Витрата</th>
                    <th class="text-right col-correction">Попр.%</th>
                    <th>Примітка</th>
                    <th class="col-actions no-print-col">
                        <button class="btn btn-primary btn-sm table-header-add" onclick="openReadingModal()">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                            Додати
                        </button>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $l):
                    $isClosed = !empty($closedDate) && $l['log_date'] <= $closedDate;
                    $isHighlight = !empty($highlightId) && $highlightId == $l['id'];
                    $fmt = $l['format'] ?? 'dec2';
                    $jsLog = json_encode([
                        'id' => $l['id'],
                        'log_date' => $l['log_date'],
                        'reading' => (float)$l['reading'],
                        'prev_reading' => $l['prev_reading'] !== null ? (float)$l['prev_reading'] : 0,
                        'note' => $l['note'] ?? '',
                        'warehouse_id' => $l['warehouse_id'],
                        'resource_type_id' => $l['resource_type_id'],
                        'warehouse_name' => $l['warehouse_name'],
                        'type_name' => $l['type_name'],
                        'unit' => $l['unit'],
                        'format' => $fmt,
                        'correction_pct' => (float)($l['correction_pct'] ?? 0),
                    ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
                ?>
                <tr id="rlog-<?= $l['id'] ?>" class="<?= $isHighlight ? 'row-highlight' : '' ?> <?= $isClosed ? 'row-closed' : 'row-editable' ?>"<?= !$isClosed ? " ondblclick='openEditReadingModal($jsLog)' title='Подвійний клік — редагувати'" : " title='Закритий період'" ?>>
                    <td class="font-mono"><?= formatDateUa($l['log_date']) ?></td>
                    <td class="font-medium"><?= htmlspecialchars($l['warehouse_name']) ?></td>
                    <td><?= htmlspecialchars($l['type_name']) ?> <span class="text-muted">(<?= htmlspecialchars($l['unit']) ?>)</span></td>
                    <td class="text-right font-mono font-bold"><?= formatReading($l['reading'], $fmt) ?></td>
                    <td class="text-right font-mono text-muted"><?= $l['prev_reading'] !== null ? formatReading($l['prev_reading'], $fmt) : '—' ?></td>
                    <td class="text-right font-mono num-positive"><?= $l['delta'] !== null ? '+' . formatReading($l['delta'], $fmt) : '—' ?></td>
                    <td class="text-right font-mono"><?php $cp = (float)($l['correction_pct'] ?? 0); echo $cp != 0 ? ($cp > 0 ? '+' : '') . rtrim(rtrim(number_format($cp, 2, '.', ''), '0'), '.') . '%' : ''; ?></td>
                    <td class="text-muted note-cell"><?= htmlspecialchars($l['note'] ?? '') ?></td>
                    <td class="col-actions no-print-col">
                        <div class="actions">
                            <?php if (!$isClosed): ?>
                            <button class="btn-icon" title="Редагувати" onclick='openEditReadingModal(<?= $jsLog ?>)'>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </button>
                            <button class="btn-icon btn-icon-danger" title="Видалити (та пов'язані рухи)"
                                    onclick="confirmDelete('<?= $basePath ?>/resources/deletelog/<?= $l['id'] ?>', 'Видалити цей запис та всі пов\'язані рухи?')">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 6h18"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                                </svg>
                            </button>
                            <?php else: ?>
                            <span class="closed-lock" title="Закритий період">🔒</span>
                            <?php endif; ?>
                            <?php if ($l['delta'] !== null && (float)$l['delta'] > 0): ?>
                            <?php
                                $movParams = [
                                    'warehouse_id' => $l['warehouse_id'],
                                    'date_from' => isset($prevDates[$l['id']]) ? $prevDates[$l['id']] : $l['log_date'],
                                    'date_to' => $l['log_date'],
                                ];
                            ?>
                            <a href="<?= $basePath ?>/movements?<?= http_build_query($movParams) ?>" class="btn-icon btn-icon-goto" title="Показати рухи">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M5 12h14"/><path d="M12 5l7 7-7 7"/>
                                </svg>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer-info">Записів: <?= count($logs) ?></div>
    <?php endif; ?>
</div>

<script>
var resWarehouses = <?= json_encode($warehousesWithResources, JSON_UNESCAPED_UNICODE) ?>;
var resTypes = <?= json_encode($types, JSON_UNESCAPED_UNICODE) ?>;
var currentFormat = 'dec2';
var currentContext = null;

<?php if (!empty($highlightId)): ?>
(function() {
    var el = document.getElementById('rlog-<?= (int)$highlightId ?>');
    if (!el) return;
    el.scrollIntoView({behavior: 'smooth', block: 'center'});
    var timer = setTimeout(function() { clearHL(); }, 15000);
    var table = el.closest('table');
    if (table) {
        table.addEventListener('click', function handler(e) {
            var row = e.target.closest('tr');
            if (row && row !== el) { clearHL(); table.removeEventListener('click', handler); }
        });
    }
    function clearHL() {
        clearTimeout(timer);
        el.classList.add('fade-out');
        setTimeout(function() { el.classList.remove('row-highlight', 'fade-out'); }, 500);
    }
})();
<?php endif; ?>

function formatReadingJs(val, fmt) {
    if (fmt === 'hm') {
        var h = Math.floor(val);
        var m = Math.round((val - h) * 60);
        return h + ':' + (m < 10 ? '0' : '') + m;
    }
    if (fmt === 'int') return Math.round(val).toString();
    return parseFloat(val).toFixed(2);
}

function hmToDecimal(str) {
    var parts = str.split(':');
    var h = parseInt(parts[0]) || 0;
    var m = parseInt(parts[1]) || 0;
    return h + m / 60;
}

function hmToDecimalSafe(str) {
    if (!str || str.indexOf(':') === -1) return str;
    return hmToDecimal(str);
}

function buildReadingInput(id, value, fmt) {
    if (fmt === 'hm') {
        var display = value !== '' && value !== null ? formatReadingJs(parseFloat(value), 'hm') : '';
        return '<input type="text" class="form-input" id="' + id + '_display" placeholder="год:хв (напр. 1250:30)" value="' + display + '" required ' +
               'oninput="this.value=this.value.replace(/[^0-9:]/g,\'\')">' +
               '<input type="hidden" name="reading" id="' + id + '" value="' + (value || '') + '">';
    }
    var step = fmt === 'int' ? '1' : '0.01';
    var ph = fmt === 'int' ? '0' : '0.00';
    var extra = fmt === 'int' ? ' onkeydown="if(event.key==\'.\'||event.key==\',\')event.preventDefault()"' : '';
    return '<input type="number" name="reading" class="form-input" id="' + id + '" step="' + step + '" min="0" required placeholder="' + ph + '" value="' + (value || '') + '"' + extra + '>';
}

function getCurrentReadingValue(form) {
    var hmDisplay = form.querySelector('#readingValue_display, #editReadingValue_display');
    if (hmDisplay) return parseFloat(hmToDecimalSafe(hmDisplay.value)) || 0;
    var num = form.querySelector('input[name="reading"]');
    return num ? (parseFloat(num.value) || 0) : 0;
}

function renderContextBox(data) {
    var fmt = data.format || 'dec2';
    var html = '<div class="resource-context-box">';
    if (data.prev_date) {
        html += '⬅ Попередній: <strong>' + formatReadingJs(data.prev_reading, fmt) + '</strong> ' + escapeHtml(data.unit) +
                ' <span class="text-muted">(від ' + data.prev_date + ')</span>';
    } else {
        html += '⬅ Попередніх немає — початковий показник';
    }
    if (data.next_reading !== null) {
        html += '<br>➡ Наступний: <strong>' + formatReadingJs(data.next_reading, fmt) + '</strong> ' + escapeHtml(data.unit) +
                ' <span class="text-muted">(від ' + data.next_date + ')</span>';
    }
    html += '</div>';
    return html;
}

function showContextWarning(message) {
    var info = document.getElementById('contextInfo');
    if (!info) return;
    var box = info.querySelector('.resource-context-box');
    if (!box) {
        info.innerHTML = '<div class="resource-context-box"></div>';
        box = info.querySelector('.resource-context-box');
    }
    var oldErr = info.querySelector('.resource-context-error');
    if (oldErr) oldErr.remove();
    box.innerHTML += '<div class="resource-context-error">⚠ ' + escapeHtml(message) + '</div>';
}

function validateReadingValue(form) {
    if (!currentContext) return true;
    var reading = getCurrentReadingValue(form);
    var prev = currentContext.prev_date ? parseFloat(currentContext.prev_reading) : null;
    var next = currentContext.next_reading !== null ? parseFloat(currentContext.next_reading) : null;
    var oldErr = document.querySelector('#contextInfo .resource-context-error');
    if (oldErr) oldErr.remove();
    if (prev !== null && reading < prev) {
        showContextWarning('Показник не може бути меншим за попередній (' + formatReadingJs(prev, currentContext.format) + ')');
        return false;
    }
    if (next !== null && reading > next) {
        showContextWarning('Показник не може бути більшим за наступний (' + formatReadingJs(next, currentContext.format) + ' від ' + currentContext.next_date + ')');
        return false;
    }
    return true;
}

function openReadingModal() {
    var whOpts = '<option value="">— Оберіть —</option>';
    resWarehouses.forEach(function(w) {
        whOpts += '<option value="' + w.id + '">' + escapeHtml(w.name) + ' (' + escapeHtml(w.resource_names) + ')</option>';
    });
    var content =
        '<form action="' + basePath + '/resources/add" method="POST" onsubmit="return beforeSubmitReading(this)">' +
            '<div class="form-group">' +
                '<label class="form-label">Дата <span class="required">*</span></label>' +
                '<input type="date" name="log_date" class="form-input" required id="readingDate" value="' + new Date().toISOString().split('T')[0] + '" onchange="loadContext()">' +
            '</div>' +
            '<div class="form-group">' +
                '<label class="form-label">Склад <span class="required">*</span></label>' +
                '<select name="warehouse_id" class="autocomplete" data-placeholder="— Оберіть —" required id="readingWh" onchange="loadResourceTypes()">' + whOpts + '</select>' +
            '</div>' +
            '<div class="form-group">' +
                '<label class="form-label">Ресурс <span class="required">*</span></label>' +
                '<select name="resource_type_id" class="form-input form-select" required id="readingResType" onchange="loadContext()">' +
                    '<option value="">Спочатку оберіть склад</option>' +
                '</select>' +
            '</div>' +
            '<div id="contextInfo" class="resource-context-container"></div>' +
            '<div class="form-row">' +
                '<div class="form-group" id="readingInputWrap">' +
                    '<label class="form-label">Показник <span class="required">*</span></label>' +
                    '<input type="number" name="reading" class="form-input" step="0.01" min="0" required placeholder="0" id="readingValue">' +
                '</div>' +
                '<div class="form-group">' +
                    '<label class="form-label">Поправка, %</label>' +
                    '<input type="number" name="correction_pct" class="form-input" step="0.01" value="0" placeholder="0">' +
                '</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label class="form-label">Примітка</label>' +
                '<input type="text" name="note" class="form-input" placeholder="Необов\'язково">' +
            '</div>' +
            '<div class="modal-footer">' +
                '<button type="button" class="btn btn-secondary" onclick="closeModal()">Скасувати</button>' +
                '<button type="submit" class="btn btn-primary">Записати</button>' +
            '</div>' +
        '</form>';
    openModal('Новий показник ресурсу', content);
}

function beforeSubmitReading(form) {
    var hmDisplay = form.querySelector('#readingValue_display');
    if (hmDisplay) {
        var hidden = form.querySelector('#readingValue');
        hidden.value = hmToDecimalSafe(hmDisplay.value);
    }
    if (!validateReadingValue(form)) return false;
    submitForm(form);
    return false;
}

function loadResourceTypes() {
    var whId = document.getElementById('readingWh').value;
    var sel = document.getElementById('readingResType');
    sel.innerHTML = '<option value="">— Оберіть —</option>';
    var info = document.getElementById('contextInfo');
    if (info) info.innerHTML = '';
    currentContext = null;
    if (!whId) return;
    var wh = resWarehouses.find(function(w) { return w.id == whId; });
    if (!wh) return;
    resTypes.forEach(function(t) {
        if (wh.resource_names && wh.resource_names.indexOf(t.name) !== -1) {
            var opt = document.createElement('option');
            opt.value = t.id;
            opt.textContent = t.name + ' (' + t.unit + ')';
            sel.appendChild(opt);
        }
    });
}

function loadContext(excludeId) {
    var whEl = document.getElementById('readingWh');
    var rtEl = document.getElementById('readingResType');
    var dateEl = document.getElementById('readingDate');
    var info = document.getElementById('contextInfo');
    var whId = whEl ? whEl.value : '';
    var rtId = rtEl ? rtEl.value : '';
    var date = dateEl ? dateEl.value : '';
    if (!whId || !rtId || !date) {
        if (info) info.innerHTML = '';
        currentContext = null;
        return;
    }
    fetch(basePath + '/resources/context?warehouse_id=' + whId + '&resource_type_id=' + rtId + '&date=' + date + (excludeId ? '&exclude_id=' + excludeId : ''))
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) return;
            currentContext = data;
            currentFormat = data.format || 'dec2';
            var wrap = document.getElementById('readingInputWrap');
            if (wrap) {
                var curVal = '';
                var existing = wrap.querySelector('input[name="reading"]');
                if (existing) curVal = existing.value;
                wrap.innerHTML = '<label class="form-label">Показник <span class="required">*</span></label>' + buildReadingInput('readingValue', curVal, currentFormat);
            }
            if (info) info.innerHTML = renderContextBox(data);
        });
}

function openEditReadingModal(data) {
    var fmt = data.format || 'dec2';
    var readingInput = buildReadingInput('readingValue', data.reading, fmt);
    var content =
        '<form action="' + basePath + '/resources/editlog/' + data.id + '" method="POST" onsubmit="return beforeSubmitEdit(this)">' +
            '<div class="type-indicator type-transfer">' +
                escapeHtml(data.warehouse_name) + ' — ' + escapeHtml(data.type_name) + ' (' + escapeHtml(data.unit) + ')' +
            '</div>' +
            '<input type="hidden" id="editExcludeId" value="' + data.id + '">' +
            '<input type="hidden" id="readingWh" value="' + (data.warehouse_id || '') + '">' +
            '<input type="hidden" id="readingResType" value="' + (data.resource_type_id || '') + '">' +
            '<div class="form-group">' +
                '<label class="form-label">Дата <span class="required">*</span></label>' +
                '<input type="date" name="log_date" class="form-input" required id="readingDate" value="' + data.log_date + '" onchange="loadContext(document.getElementById(\'editExcludeId\').value)">' +
            '</div>' +
            '<div id="contextInfo" class="resource-context-container"></div>' +
            '<div class="form-row">' +
                '<div class="form-group" id="readingInputWrap">' +
                    '<label class="form-label">Показник <span class="required">*</span></label>' +
                    readingInput +
                '</div>' +
                '<div class="form-group">' +
                    '<label class="form-label">Поправка, %</label>' +
                    '<input type="number" name="correction_pct" class="form-input" step="0.01" value="' + (data.correction_pct || 0) + '">' +
                '</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label class="form-label">Примітка</label>' +
                '<input type="text" name="note" class="form-input" value="' + escapeHtml(data.note) + '" placeholder="Необов\'язково">' +
            '</div>' +
            '<div class="modal-footer">' +
                '<button type="button" class="btn btn-secondary" onclick="closeModal()">Скасувати</button>' +
                '<button type="submit" class="btn btn-primary">Зберегти</button>' +
            '</div>' +
        '</form>';
    openModal('Редагувати показник', content);
    setTimeout(function() { loadContext(data.id); }, 100);
}

function beforeSubmitEdit(form) {
    var hmDisplay = form.querySelector('#readingValue_display');
    if (hmDisplay) {
        var hidden = form.querySelector('input[name="reading"]');
        hidden.value = hmToDecimalSafe(hmDisplay.value);
    }
    if (!validateReadingValue(form)) return false;
    submitForm(form);
    return false;
}

function openEditReadingModal(data) {
    var fmt = data.format || 'dec2';
    var readingInput = buildReadingInput('readingValue', data.reading, fmt);
    var content =
        '<form action="' + basePath + '/resources/editlog/' + data.id + '" method="POST" onsubmit="return beforeSubmitEdit(this)">' +
            '<div class="type-indicator type-transfer">' +
                escapeHtml(data.warehouse_name) + ' — ' + escapeHtml(data.type_name) + ' (' + escapeHtml(data.unit) + ')' +
            '</div>' +
            '<input type="hidden" id="editExcludeId" value="' + data.id + '">' +
            '<input type="hidden" id="readingWh" value="' + (data.warehouse_id || '') + '">' +
            '<input type="hidden" id="readingResType" value="' + (data.resource_type_id || '') + '">' +
            '<div class="form-group">' +
                '<label class="form-label">Дата <span class="required">*</span></label>' +
                '<input type="date" name="log_date" class="form-input" required id="readingDate" value="' + data.log_date + '" onchange="loadContext(document.getElementById(\'editExcludeId\').value)">' +
            '</div>' +
            '<div id="contextInfo" class="resource-context-container"></div>' +
            '<div class="form-row">' +
                '<div class="form-group" id="readingInputWrap">' +
                    '<label class="form-label">Показник <span class="required">*</span></label>' +
                    readingInput +
                '</div>' +
                '<div class="form-group">' +
                    '<label class="form-label">Поправка, %</label>' +
                    '<input type="number" name="correction_pct" class="form-input" step="0.01" value="' + (data.correction_pct || 0) + '">' +
                '</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label class="form-label">Примітка</label>' +
                '<input type="text" name="note" class="form-input" value="' + escapeHtml(data.note) + '" placeholder="Необов\'язково">' +
            '</div>' +
            '<div class="modal-footer modal-meta" id="resourceLogMetaFooter">' +
                '<div class="modal-meta-info"></div>' +
                '<div class="modal-meta-buttons">' +
                    '<button type="button" class="btn btn-secondary" onclick="closeModal()">Скасувати</button>' +
                    '<button type="submit" class="btn btn-primary">Зберегти</button>' +
                '</div>' +
            '</div>' +
        '</form>';
    openModal('Редагувати показник', content);
    
    // Завантажуємо мета-інформацію
    fetch(basePath + '/resources/getlog/' + data.id)
        .then(function(r) { return r.json(); })
        .then(function(result) {
            if (result.success && result.data) {
                var metaHtml = '<div class="modal-meta-line modal-meta-author">';
                if (result.data.author) {
                    metaHtml += escapeHtml(result.data.author);
                }
                metaHtml += '</div><div class="modal-meta-line modal-meta-date">';
                if (result.data.updated_at && result.data.updated_at !== result.data.created_at) {
                    var updated = new Date(result.data.updated_at);
                    metaHtml += updated.toLocaleDateString('uk-UA') + ' ' + updated.toLocaleTimeString('uk-UA', {hour:'2-digit', minute:'2-digit'});
                } else if (result.data.created_at) {
                    var created = new Date(result.data.created_at);
                    metaHtml += created.toLocaleDateString('uk-UA') + ' ' + created.toLocaleTimeString('uk-UA', {hour:'2-digit', minute:'2-digit'});
                }
                metaHtml += '</div>';
                document.querySelector('#resourceLogMetaFooter .modal-meta-info').innerHTML = metaHtml;
            }
        })
        .catch(function() {});
    
    setTimeout(function() { loadContext(data.id); }, 100);
}
</script>
