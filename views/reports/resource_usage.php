<?php
/**
 * Детальний звіт по витраті ресурсів
 * Групування: Склад → Матеріал → рядки resource_logs
 */

function fmtRes($value, string $fmt): string {
    if ($value === null || $value === '') return '—';
    $v = (float)$value;
    switch ($fmt) {
        case 'int': return number_format($v, 0, '.', ' ');
        case 'hm':
            $h = (int)floor($v);
            $m = (int)round(($v - $h) * 60);
            return $h . ':' . str_pad($m, 2, '0', STR_PAD_LEFT);
        default: return number_format($v, 2, '.', ' ');
    }
}
function fmtQty(float $v): string {
    return number_format($v, 2, '.', ' ');
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Звіт по ресурсу</h1>
        <p class="page-subtitle">Детальна витрата матеріалів за показниками ресурсу</p>
    </div>
    <?php if (!empty($report)): ?>
    <div class="header-buttons">
        <button class="btn btn-secondary" onclick="window.print()" title="Друк">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
                <rect x="6" y="14" width="12" height="8"/>
            </svg>
            Друк
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- Фільтри -->
<div class="card filter-panel">
    <form method="GET" class="filter-grid filter-grid-with-action">

        <div class="form-group" style="margin-bottom:0">
            <label class="form-label">Тип ресурсу <span class="required">*</span></label>
            <select name="resource_type_id" class="form-input form-select" onchange="this.form.submit()">
                <option value="">— Оберіть —</option>
                <?php foreach ($types as $t): ?>
                <option value="<?= $t['id'] ?>" <?= $resourceTypeId == $t['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($t['name']) ?> (<?= htmlspecialchars($t['unit']) ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin-bottom:0">
            <label class="form-label">Дата від</label>
            <input type="date" name="date_from" class="form-input" value="<?= htmlspecialchars($dateFrom) ?>">
        </div>

        <div class="form-group" style="margin-bottom:0">
            <label class="form-label">Дата до</label>
            <input type="date" name="date_to" class="form-input" value="<?= htmlspecialchars($dateTo) ?>">
        </div>

        <div class="form-group" style="margin-bottom:0">
            <label class="form-label">
                Склади
                <?php if (!empty($selectedWarehouseIds)): ?>
                <span style="font-weight:400;color:var(--blue)">(<?= count($selectedWarehouseIds) ?>)</span>
                <?php endif; ?>
            </label>
            <button type="button" class="wh-filter-btn" onclick="openWarehouseFilter()">
                <span id="whBtnLabel"><?= empty($selectedWarehouseIds) ? 'Усі склади' : count($selectedWarehouseIds) . ' обрано' ?></span>
                <svg width="10" height="10" viewBox="0 0 10 10"><path fill="currentColor" d="M5 7L1 3h8z"/></svg>
            </button>
            <input type="hidden" name="warehouse_ids" id="warehouseFilterValue"
                   value="<?= htmlspecialchars(implode(',', $selectedWarehouseIds)) ?>">
        </div>

        <div class="form-group filter-action-inline" style="margin-bottom:0">
            <label class="form-label">&nbsp;</label>
            <div class="filter-action-stack">
                <button type="submit" class="btn btn-primary">Сформувати</button>
                <a href="<?= $basePath ?>/reports/resource" class="btn btn-secondary btn-sm">Скинути</a>
            </div>
        </div>
    </form>
</div>

<?php if (!$resourceTypeId): ?>
<div class="card">
    <div class="empty-state">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>
        </svg>
        <p>Оберіть тип ресурсу для формування звіту</p>
    </div>
</div>

<?php elseif (empty($report)): ?>
<div class="card">
    <div class="empty-state">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
            <path d="M14 2v6h6"/>
        </svg>
        <p>Немає даних за вказаний період</p>
    </div>
</div>

<?php else: ?>

<?php
// Глобальні підсумки по всіх складах
$grandTotalDelta = 0;
$grandMaterials = [];
foreach ($report as $whRow) {
    $grandTotalDelta += (float)$whRow['total_delta'];
    foreach ($whRow['materials'] as $matId => $mat) {
        if (!isset($grandMaterials[$matId])) {
            $grandMaterials[$matId] = ['name'=>$mat['name'],'received'=>0,'consumed'=>0,'opening'=>0,'closing'=>0];
        }
        $grandMaterials[$matId]['received'] += $mat['received'];
        $grandMaterials[$matId]['consumed'] += $mat['consumed_total'];
        $grandMaterials[$matId]['opening']  += $mat['opening_balance'];
        $grandMaterials[$matId]['closing']  += $mat['closing_balance'];
    }
}
$fmt  = $report[0]['resource_format'] ?? 'dec2';
$unit = $report[0]['resource_unit']   ?? '';
?>

<div class="print-header">
    <div>
        <strong><?= htmlspecialchars($selectedType['name'] ?? '') ?></strong>
        &nbsp;•&nbsp; <?= formatDateUa($dateFrom) ?> — <?= formatDateUa($dateTo) ?>
        &nbsp;•&nbsp; Дата друку: <?= date('d.m.Y H:i') ?>
    </div>
</div>

<!-- Загальний підсумок (екран) -->
<div class="card rr-summary-card no-print">
    <div class="rr-summary-header">Загальний підсумок</div>
    <div class="rr-summary-body">
        <div class="rr-summary-item">
            <span class="rr-summary-label">Загальна витрата ресурсу</span>
            <span class="rr-summary-value"><?= fmtRes($grandTotalDelta, $fmt) ?> <?= htmlspecialchars($unit) ?></span>
        </div>
        <?php foreach ($grandMaterials as $gm): ?>
        <div class="rr-summary-item rr-summary-material">
            <span class="rr-summary-label"><?= htmlspecialchars($gm['name']) ?></span>
            <div class="rr-summary-row">
                <span class="rr-badge rr-badge-opening">Початок: <?= fmtQty($gm['opening']) ?></span>
                <span class="rr-badge rr-badge-in">+ Надійшло: <?= fmtQty($gm['received']) ?></span>
                <span class="rr-badge rr-badge-out">− Списано: <?= fmtQty($gm['consumed']) ?></span>
                <span class="rr-badge <?= $gm['closing'] < 0 ? 'rr-badge-neg' : 'rr-badge-closing' ?>">
                    Залишок: <?= fmtQty($gm['closing']) ?>
                </span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Деталізація по складах -->
<?php foreach ($report as $whRow): ?>
<?php $whFmt = $whRow['resource_format']; $whUnit = $whRow['resource_unit']; ?>
<div class="card rr-warehouse-card">

    <div class="rr-wh-header">
        <div class="rr-wh-title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                <rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>
            </svg>
            <?= htmlspecialchars($whRow['warehouse_name']) ?>
        </div>
        <div class="rr-wh-resource-summary">
            <?php if ($whRow['opening_reading'] !== null): ?>
            <span class="rr-res-chip">Початок: <?= fmtRes($whRow['opening_reading'], $whFmt) ?> <?= htmlspecialchars($whUnit) ?></span>
            <?php endif; ?>
            <span class="rr-res-chip rr-res-chip-delta">Δ <?= fmtRes($whRow['total_delta'], $whFmt) ?> <?= htmlspecialchars($whUnit) ?></span>
            <?php if ($whRow['closing_reading'] !== null): ?>
            <span class="rr-res-chip">Кінець: <?= fmtRes($whRow['closing_reading'], $whFmt) ?> <?= htmlspecialchars($whUnit) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <?php foreach ($whRow['materials'] as $matId => $mat): ?>
    <div class="rr-material-block">

        <div class="rr-mat-header">
            <div class="rr-mat-title"><?= htmlspecialchars($mat['name']) ?></div>
            <div class="rr-mat-totals">
                <span class="rr-badge rr-badge-opening" title="Залишок на початок">На початок: <?= fmtQty($mat['opening_balance']) ?></span>
                <?php if ($mat['received'] > 0): ?>
                <span class="rr-badge rr-badge-in" title="Надійшло за період">+ <?= fmtQty($mat['received']) ?></span>
                <?php endif; ?>
                <span class="rr-badge rr-badge-out" title="Списано за період">− <?= fmtQty($mat['consumed_total']) ?></span>
                <span class="rr-badge <?= $mat['closing_balance'] < 0 ? 'rr-badge-neg' : 'rr-badge-closing' ?>" title="Залишок на кінець">
                    На кінець: <?= fmtQty($mat['closing_balance']) ?>
                </span>
            </div>
        </div>

        <?php if (!empty($mat['rows'])): ?>
        <div class="table-scroll rr-detail-scroll">
            <table class="data-table rr-detail-table">
                <thead>
                    <tr>
                        <th style="width:90px">Дата</th>
                        <th class="text-right" style="width:130px">Показник (поперед.)</th>
                        <th class="text-right" style="width:90px">Δ <?= htmlspecialchars($whUnit) ?></th>
                        <th class="text-right" style="width:75px">Норма</th>
                        <th class="text-right" style="width:70px">Поправка</th>
                        <th class="text-right" style="width:95px">Списано</th>
                        <th>Примітка</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($mat['received'] > 0): ?>
                    <tr class="rr-row-received">
                        <td colspan="5" class="text-muted" style="font-size:12px;font-style:italic">
                            Надійшло за <?= formatDateUa($dateFrom) ?> — <?= formatDateUa($dateTo) ?>
                        </td>
                        <td class="text-right num-positive font-bold">+ <?= fmtQty($mat['received']) ?></td>
                        <td></td>
                    </tr>
                    <?php endif;
                    foreach ($mat['rows'] as $row):
                        $corrPct = (float)$row['correction_pct'];
                        $rateStr = rtrim(rtrim(number_format($row['rate'], 6, '.', ''), '0'), '.');
                    ?>
                    <tr>
                        <td class="font-mono"><?= formatDateUa($row['log_date']) ?></td>
                        <td class="text-right font-mono">
                            <?= fmtRes($row['reading'], $whFmt) ?>
                            <?php if ($row['prev_reading'] !== null): ?>
                            <span class="text-muted" style="font-size:11px">(<?= fmtRes($row['prev_reading'], $whFmt) ?>)</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right font-mono font-bold">+<?= fmtRes($row['delta'], $whFmt) ?></td>
                        <td class="text-right font-mono text-muted"><?= $rateStr ?></td>
                        <td class="text-right font-mono text-muted">
                            <?= $corrPct != 0 ? ($corrPct > 0 ? '+' : '') . rtrim(rtrim(number_format($corrPct, 2, '.', ''), '0'), '.') . '%' : '' ?>
                        </td>
                        <td class="text-right font-mono num-negative">−<?= fmtQty($row['consumed']) ?></td>
                        <td class="text-muted" style="font-size:12px"><?= htmlspecialchars($row['note']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="rr-row-total">
                        <td colspan="2" class="text-muted" style="font-size:12px">Разом за період</td>
                        <td class="text-right font-mono font-bold">+<?= fmtRes($whRow['total_delta'], $whFmt) ?></td>
                        <td></td><td></td>
                        <td class="text-right font-mono font-bold num-negative">−<?= fmtQty($mat['consumed_total']) ?></td>
                        <td></td>
                    </tr>
                    <tr class="rr-row-balance">
                        <td colspan="5" class="text-right font-bold" style="padding-right:8px">Залишок на кінець:</td>
                        <td class="text-right font-bold font-mono <?= $mat['closing_balance'] < 0 ? 'balance-negative' : 'num-positive' ?>">
                            <?= fmtQty($mat['closing_balance']) ?>
                        </td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div style="padding:12px 16px;color:var(--text-muted);font-size:13px;font-style:italic">
            Жодного списання у вказаному діапазоні
        </div>
        <?php endif; ?>

    </div>
    <?php endforeach; ?>

</div>
<?php endforeach; ?>

<!-- Підсумки тільки для друку -->
<div class="rr-grand-total print-only">
    <strong>ЗАГАЛЬНИЙ ПІДСУМОК:</strong>
    Витрата ресурсу: <?= fmtRes($grandTotalDelta, $fmt) ?> <?= htmlspecialchars($unit) ?>
    <?php foreach ($grandMaterials as $gm): ?>
    &nbsp;|&nbsp; <?= htmlspecialchars($gm['name']) ?>:
    поч. <?= fmtQty($gm['opening']) ?>,
    +<?= fmtQty($gm['received']) ?>,
    −<?= fmtQty($gm['consumed']) ?>,
    кінець <?= fmtQty($gm['closing']) ?>
    <?php endforeach; ?>
</div>

<?php endif; ?>

<script>
var resReportWarehouses = <?= json_encode(array_map(function($w){
    return ['id'=>$w['id'],'name'=>$w['name']];
}, $warehouses), JSON_UNESCAPED_UNICODE) ?>;
var selectedWhIds = <?= json_encode(array_map('intval', $selectedWarehouseIds)) ?>;

function openWarehouseFilter() {
    var html = '<div class="material-filter-modal">';
    html += '<div class="mf-header">';
    html += '<input type="text" class="mf-search" placeholder="Пошук складів..." oninput="filterWh(this.value)">';
    html += '<div class="mf-actions">';
    html += '<button type="button" class="btn btn-sm" onclick="selectAllWh()">Обрати всі</button>';
    html += '<button type="button" class="btn btn-sm btn-secondary" onclick="deselectAllWh()">Зняти всі</button>';
    html += '</div></div>';
    html += '<div class="mf-list" id="whFilterList">';
    resReportWarehouses.forEach(function(w) {
        var checked = selectedWhIds.indexOf(w.id) !== -1 ? 'checked' : '';
        html += '<label class="mf-item"><input type="checkbox" value="' + w.id + '" ' + checked + '>';
        html += '<span>' + escapeHtml(w.name) + '</span></label>';
    });
    html += '</div><div class="mf-footer">';
    html += '<button type="button" class="btn btn-primary" onclick="applyWhFilter()">Застосувати</button>';
    html += '</div></div>';
    openModal('Вибір складів', html);
}
function filterWh(q) {
    document.querySelectorAll('#whFilterList .mf-item').forEach(function(item) {
        item.style.display = item.querySelector('span').textContent.toLowerCase().indexOf(q.toLowerCase()) !== -1 ? '' : 'none';
    });
}
function selectAllWh() {
    document.querySelectorAll('#whFilterList .mf-item input').forEach(function(cb) {
        if (cb.closest('.mf-item').style.display !== 'none') cb.checked = true;
    });
}
function deselectAllWh() {
    document.querySelectorAll('#whFilterList .mf-item input').forEach(function(cb) { cb.checked = false; });
}
function applyWhFilter() {
    var ids = [];
    document.querySelectorAll('#whFilterList .mf-item input:checked').forEach(function(cb) { ids.push(cb.value); });
    document.getElementById('warehouseFilterValue').value = ids.join(',');
    document.getElementById('whBtnLabel').textContent = ids.length === 0 ? 'Усі склади' : ids.length + ' обрано';
    closeModal();
}
function escapeHtml(t) { var d = document.createElement('div'); d.textContent = t; return d.innerHTML; }
</script>

<style>
.wh-filter-btn {
    width:100%;display:flex;align-items:center;justify-content:space-between;
    gap:8px;padding:10px 12px;font-size:14px;
    border:1px solid var(--border);border-radius:6px;
    background:white;color:var(--text);cursor:pointer;
}
.wh-filter-btn:hover { border-color:var(--blue);background:var(--blue-light); }

.rr-summary-card { padding:16px 20px;margin-bottom:16px; }
.rr-summary-header { font-weight:700;font-size:12px;text-transform:uppercase;
    letter-spacing:.06em;color:var(--text-muted);margin-bottom:12px; }
.rr-summary-body { display:flex;flex-direction:column;gap:10px; }
.rr-summary-item { display:flex;align-items:center;gap:14px;flex-wrap:wrap; }
.rr-summary-label { font-weight:600;font-size:14px;min-width:170px; }
.rr-summary-value { font-size:18px;font-weight:700;color:var(--blue);font-variant-numeric:tabular-nums; }
.rr-summary-material { padding:8px 0;border-top:1px solid var(--border); }
.rr-summary-row { display:flex;gap:7px;flex-wrap:wrap; }

.rr-warehouse-card { margin-bottom:20px;overflow:hidden; }
.rr-wh-header {
    display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;
    gap:10px;padding:13px 18px;
    background:linear-gradient(135deg,#1e40af 0%,#1d4ed8 100%);color:white;
}
.rr-wh-title { display:flex;align-items:center;gap:8px;font-weight:700;font-size:15px; }
.rr-wh-resource-summary { display:flex;gap:7px;flex-wrap:wrap;align-items:center; }
.rr-res-chip { padding:3px 10px;border-radius:20px;font-size:12px;font-weight:500;
    background:rgba(255,255,255,.18);color:white;font-variant-numeric:tabular-nums; }
.rr-res-chip-delta { background:rgba(255,255,255,.35);font-weight:700; }

.rr-material-block { border-top:1px solid var(--border); }
.rr-mat-header {
    display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;
    gap:10px;padding:9px 16px;background:#f8fafc;
}
.rr-mat-title { font-weight:700;font-size:14px;color:var(--text); }
.rr-mat-totals { display:flex;gap:6px;flex-wrap:wrap; }

.rr-badge { padding:3px 9px;border-radius:12px;font-size:12px;font-weight:600;
    white-space:nowrap;font-variant-numeric:tabular-nums; }
.rr-badge-opening { background:#e0e7ff;color:#3730a3; }
.rr-badge-in      { background:#dcfce7;color:#166534; }
.rr-badge-out     { background:#fee2e2;color:#991b1b; }
.rr-badge-closing { background:#e0f2fe;color:#075985; }
.rr-badge-neg     { background:#fef9c3;color:#92400e;font-weight:700; }

.rr-detail-scroll { border-top:1px solid var(--border); }
.rr-detail-table { font-size:13px; }
.rr-detail-table th { font-size:11px;padding:6px 10px;background:#f1f5f9; }
.rr-detail-table td { padding:6px 10px; }
.rr-row-received td { background:#f0fdf4;font-size:13px; }
.rr-row-total td    { background:#f1f5f9;font-weight:600;border-top:2px solid var(--border); }
.rr-row-balance td  { background:#eff6ff; }
.balance-negative   { color:var(--danger);font-weight:700; }
.num-positive       { color:var(--success); }
.num-negative       { color:var(--danger); }

.print-only { display:none; }
.rr-grand-total { margin-top:16px;padding:10px 16px;background:#f8fafc;
    border:1px solid var(--border);border-radius:6px;font-size:13px; }

.material-filter-modal { display:flex;flex-direction:column;height:380px; }
.mf-header { padding:12px 16px;border-bottom:1px solid var(--border);background:#f8fafc; }
.mf-search { width:100%;padding:8px 12px;font-size:14px;border:1px solid var(--border);
    border-radius:6px;margin-bottom:10px; }
.mf-actions { display:flex;gap:8px; }
.mf-list { flex:1;overflow-y:auto;padding:8px; }
.mf-item { display:flex;align-items:center;gap:10px;padding:8px 10px;cursor:pointer;border-radius:4px; }
.mf-item:hover { background:var(--bg); }
.mf-item input[type="checkbox"] { width:16px;height:16px; }
.mf-footer { padding:12px 16px;border-top:1px solid var(--border);background:#f8fafc;
    display:flex;justify-content:flex-end; }

@media print {
    .no-print,.filter-panel { display:none !important; }
    .print-only { display:block !important; }
    .rr-wh-header { background:#1e40af !important;-webkit-print-color-adjust:exact;print-color-adjust:exact; }
    .rr-warehouse-card { page-break-inside:avoid;break-inside:avoid; }
    .rr-material-block { page-break-inside:avoid;break-inside:avoid; }
}
</style>
