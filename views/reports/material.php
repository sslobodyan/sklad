<?php
function formatNum($n) {
    if ($n == 0) return '—';
    return number_format($n, 2, '.', ' ');
}
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Звіт по матеріалу</h1>
        <p class="page-subtitle">Наявність на складах за період</p>
    </div>
    <div class="header-buttons">
        <button class="date-range-btn" onclick="toggleDatePanel(this)" title="Глобальний період">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            <span class="dr-dates">
                <?= formatDateUa($globalDateFrom) ?> — <?= formatDateUa($globalDateTo) ?>
                <?php if (!empty($closedDate)): ?>
                <span class="dr-closed">🔒 <?= formatDateUa($closedDate) ?></span>
                <?php endif; ?>
            </span>
        </button>
        <button onclick="window.print()" class="btn btn-secondary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
                <rect x="6" y="14" width="12" height="8"/>
            </svg>
            Друк
        </button>
    </div>
</div>

<div class="card filter-panel">
    <form method="GET" id="reportForm" class="filter-grid filter-grid-with-action">
        <div class="form-group">
            <label class="form-label">Матеріал</label>
            <select name="material_id" class="autocomplete" data-placeholder="— Оберіть матеріал —" data-submit-on-change>
                <option value="">— Оберіть матеріал —</option>
                <?php foreach ($materials as $m): ?>
                <option value="<?= $m['id'] ?>" <?= $materialId == $m['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($m['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Склади
                <?php if (!empty($selectedWarehouseIds)): ?>
                <span class="filter-badge">(<?= count($selectedWarehouseIds) ?>)</span>
                <?php endif; ?>
            </label>
            <button type="button" class="wh-filter-btn" onclick="openWarehouseFilter()">
                <span><?= empty($selectedWarehouseIds) ? 'Усі склади' : count($selectedWarehouseIds) . ' обрано' ?></span>
                <svg width="10" height="10" viewBox="0 0 10 10"><path fill="currentColor" d="M5 7L1 3h8z"/></svg>
            </button>
            <input type="hidden" name="wh" id="whFilterValue" value="<?= htmlspecialchars(implode(',', $selectedWarehouseIds)) ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Період від</label>
            <input type="date" name="date_from" class="form-input" value="<?= htmlspecialchars($dateFrom) ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Період до</label>
            <input type="date" name="date_to" class="form-input" value="<?= htmlspecialchars($dateTo) ?>">
        </div>
        <div class="form-group filter-action-inline">
            <label class="form-label">&nbsp;</label>
            <div class="filter-action-stack">
                <button type="submit" class="btn btn-primary">Сформувати</button>
                <?php if (!empty($selectedWarehouseIds)): ?>
                <a href="<?= $basePath ?>/reports/material?material_id=<?= $materialId ?>&date_from=<?= $dateFrom ?>&date_to=<?= $dateTo ?>" class="btn btn-secondary btn-sm">Скинути склади</a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<?php if (!$materialId): ?>
<div class="card">
    <div class="empty-state">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <polygon points="22,3 2,3 10,12.46 10,19 14,21 14,12.46"/>
        </svg>
        <p>Оберіть матеріал для формування звіту</p>
    </div>
</div>
<?php elseif (empty($report)): ?>
<div class="card">
    <div class="empty-state">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/>
        </svg>
        <p>Немає даних за вказаний період</p>
    </div>
</div>
<?php else: ?>

<div class="report-controls">
    <button class="btn btn-sm btn-secondary" onclick="toggleAll(true)">Розгорнути все</button>
    <button class="btn btn-sm btn-secondary" onclick="toggleAll(false)">Згорнути все</button>
</div>

<div class="card card-stretch">
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-expand"></th>
                    <th>Склад</th>
                    <th class="text-right col-amount">Вх. сальдо</th>
                    <th class="text-right col-amount">Прихід</th>
                    <th class="text-right col-amount">Витрата</th>
                    <th class="text-right col-amount">Вих. сальдо</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $totalOpening = 0; $totalIn = 0; $totalOut = 0; $totalClosing = 0;
                foreach ($report as $row):
                    $totalOpening += $row['opening_balance'];
                    $totalIn += $row['incoming'];
                    $totalOut += $row['outgoing'];
                    $totalClosing += $row['closing_balance'];
                    $rowId = 'wh-' . $row['warehouse_id'];
                ?>
                <tr class="expandable report-summary-row" data-row-id="<?= $rowId ?>" onclick="toggleRow(this)">
                    <td class="col-expand">
                        <?php if (!empty($row['details'])): ?>
                        <span class="expand-icon" id="icon-<?= $rowId ?>">▶</span>
                        <?php endif; ?>
                    </td>
                    <td class="font-medium"><?= htmlspecialchars($row['warehouse_name']) ?></td>
                    <td class="text-right"><?= $row['opening_balance'] != 0 ? number_format($row['opening_balance'], 2) : '—' ?></td>
                    <td class="text-right"><?= $row['incoming'] != 0 ? number_format($row['incoming'], 2) : '—' ?></td>
                    <td class="text-right"><?= $row['outgoing'] != 0 ? number_format($row['outgoing'], 2) : '—' ?></td>
                    <td class="text-right <?= $row['closing_balance'] < 0 ? 'balance-negative' : '' ?>"><?= $row['closing_balance'] != 0 ? number_format($row['closing_balance'], 2) : '—' ?></td>
                </tr>
                
                <?php if (!empty($row['details'])): ?>
                <?php 
                    $detailRows = [];
                    $runningBalance = (float)$row['opening_balance'];
                    foreach ($row['details'] as $detail) {
                        $runningBalance += (float)$detail['incoming'] - (float)$detail['outgoing'];
                        $detail['balance_after'] = $runningBalance;
                        $detailRows[] = $detail;
                    }
                    $detailRows = array_reverse($detailRows);
                ?>
                <?php foreach ($detailRows as $d): ?>
                <tr class="detail-row" data-parent="<?= $rowId ?>" style="display:none" ondblclick="goToMovement(<?= $d['id'] ?>)">
                    <td class="col-expand"></td>
                    <td class="detail-cell">
                        <span class="text-muted font-mono"><?= formatDateUa($d['date']) ?></span>
                        <span class="badge <?= $d['incoming'] > 0 ? 'badge-in' : 'badge-out' ?>"><?= $d['type'] ?></span>
                        <span class="text-muted"><?= htmlspecialchars($d['counterpart']) ?></span>
                        <?php if ($d['note']): ?>
                        <span class="text-muted detail-note"> — <?= htmlspecialchars($d['note']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right">—</td>
                    <td class="text-right"><?= $d['incoming'] > 0 ? number_format($d['incoming'], 2) : '—' ?></td>
                    <td class="text-right"><?= $d['outgoing'] > 0 ? number_format($d['outgoing'], 2) : '—' ?></td>
                    <td class="text-right <?= $d['balance_after'] < 0 ? 'balance-negative' : '' ?>"><?= number_format($d['balance_after'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                <?php endforeach; ?>
                
                <tr class="totals-row report-summary-row">
                    <td class="col-expand"></td>
                    <td class="font-bold">Разом</td>
                    <td class="text-right"><?= number_format($totalOpening, 2) ?></td>
                    <td class="text-right"><?= number_format($totalIn, 2) ?></td>
                    <td class="text-right"><?= number_format($totalOut, 2) ?></td>
                    <td class="text-right <?= $totalClosing < 0 ? 'balance-negative' : '' ?>"><?= number_format($totalClosing, 2) ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="card-footer-info">
        Матеріал: <strong><?= htmlspecialchars($selectedMaterial['name'] ?? '') ?></strong> •
        Період: <?= formatDateUa($dateFrom) ?> — <?= formatDateUa($dateTo) ?> •
        Складів: <?= count($report) ?>
        <?php if (!empty($selectedWarehouseIds)): ?>
        • Фільтр: <?= count($selectedWarehouseIds) ?> складів
        <?php endif; ?>
    </div>
</div>

<script>
var allWarehouses = <?= json_encode($warehouses ?? [], JSON_UNESCAPED_UNICODE) ?>;
var selectedWhIds = <?= json_encode($selectedWarehouseIds, JSON_UNESCAPED_UNICODE) ?>;

function toggleRow(row) {
    var rowId = row.dataset.rowId;
    var rows = document.querySelectorAll('[data-parent="' + rowId + '"]');
    var icon = document.getElementById('icon-' + rowId);
    var isHidden = rows[0] && rows[0].style.display === 'none';
    rows.forEach(function(r) { r.style.display = isHidden ? '' : 'none'; });
    if (icon) icon.classList.toggle('expanded', isHidden);
}

function toggleAll(expand) {
    document.querySelectorAll('.detail-row').forEach(function(r) { r.style.display = expand ? '' : 'none'; });
    document.querySelectorAll('.expand-icon').forEach(function(i) { i.classList.toggle('expanded', expand); });
}

function goToMovement(id) {
    window.location.href = '<?= $basePath ?>/movements?highlight=' + id;
}

function openWarehouseFilter() {
    var checked = {};
    selectedWhIds.forEach(function(id) { checked[id] = true; });

    var searchHtml = '<input type="text" class="form-input" id="whFilterSearch" placeholder="Пошук складу..." oninput="filterWhList()">';
    
    var listHtml = '<div class="wh-check-list" id="whCheckList">';
    allWarehouses.forEach(function(w) {
        var isChecked = checked[w.id] ? ' checked' : '';
        listHtml += '<label class="wh-check-item" data-name="' + escapeHtml(w.name.toLowerCase()) + '">' +
            '<input type="checkbox" value="' + w.id + '"' + isChecked + '>' +
            '<span>' + escapeHtml(w.name) + '</span>' +
        '</label>';
    });
    listHtml += '</div>';

    var content = searchHtml + listHtml +
        '<div class="wh-check-actions">' +
            '<button type="button" class="btn btn-sm btn-secondary" onclick="toggleWhAll(true)">Обрати все</button>' +
            '<button type="button" class="btn btn-sm btn-secondary" onclick="toggleWhAll(false)">Зняти все</button>' +
        '</div>' +
        '<div class="modal-footer">' +
            '<button type="button" class="btn btn-secondary" onclick="closeModal()">Скасувати</button>' +
            '<button type="button" class="btn btn-primary" onclick="applyWhFilter()">Застосувати</button>' +
        '</div>';

    openModal('Фільтр по складах', content);
}

function filterWhList() {
    var q = document.getElementById('whFilterSearch').value.toLowerCase();
    document.querySelectorAll('.wh-check-item').forEach(function(item) {
        var name = item.dataset.name;
        item.style.display = (!q || name.indexOf(q) !== -1) ? '' : 'none';
    });
}

function toggleWhAll(check) {
    document.querySelectorAll('#whCheckList input[type="checkbox"]').forEach(function(cb) {
        if (cb.closest('.wh-check-item').style.display !== 'none') {
            cb.checked = check;
        }
    });
}

function applyWhFilter() {
    var ids = [];
    document.querySelectorAll('#whCheckList input[type="checkbox"]:checked').forEach(function(cb) {
        ids.push(cb.value);
    });
    document.getElementById('whFilterValue').value = ids.join(',');
    closeModal();
    document.getElementById('reportForm').submit();
}

function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
<?php endif; ?>