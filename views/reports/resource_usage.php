<?php
/**
 * Звіт про витрачання ресурсів
 */
?>

<div class="page-header">
    <div>
        <h1 class="page-title">📊 Звіт про витрачання ресурсів</h1>
        <p class="page-subtitle">Витрачання матеріалів за витратою ресурсів</p>
    </div>
</div>

<!-- Фільтри -->
<div class="card filter-panel">
    <form method="GET" class="filter-grid filter-grid-with-action">
        
        <div class="form-group" style="margin-bottom:0">
            <label class="form-label">Період від</label>
            <input type="date" name="date_from" class="form-input" value="<?= htmlspecialchars($dateFrom) ?>">
        </div>
        
        <div class="form-group" style="margin-bottom:0">
            <label class="form-label">Період до</label>
            <input type="date" name="date_to" class="form-input" value="<?= htmlspecialchars($dateTo) ?>">
        </div>
        
        <div class="form-group" style="margin-bottom:0">
            <label class="form-label">Склади
                <?php if (!empty($selectedWarehouseIds)): ?>
                <span style="font-weight:400;color:var(--blue)">(<?= count($selectedWarehouseIds) ?>)</span>
                <?php endif; ?>
            </label>
            <button type="button" class="wh-filter-btn" onclick="openWarehouseFilter()">
                <span><?= empty($selectedWarehouseIds) ? 'Усі склади' : count($selectedWarehouseIds) . ' обрано' ?></span>
                <svg width="10" height="10" viewBox="0 0 10 10"><path fill="currentColor" d="M5 7L1 3h8z"/></svg>
            </button>
            <input type="hidden" name="warehouse_ids" id="warehouseFilterValue" value="<?= htmlspecialchars(implode(',', $selectedWarehouseIds)) ?>">
        </div>
        
        <div class="form-group" style="margin-bottom:0">
            <label class="form-label">Тип ресурсу</label>
            <select name="resource_type_id" class="form-input" onchange="this.form.submit()">
                <option value="">— Оберіть тип —</option>
                <?php foreach ($types as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= $resourceTypeId == $t['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['name']) ?> (<?= htmlspecialchars($t['unit']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group filter-action-inline" style="margin-bottom:0">
            <label class="form-label">&nbsp;</label>
            <div class="filter-action-stack">
                <button type="submit" class="btn btn-primary">Сформувати</button>
                <?php if (!empty($selectedWarehouseIds)): ?>
                <a href="<?= $basePath ?>/reports/resource?resource_type_id=<?= $resourceTypeId ?>&date_from=<?= $dateFrom ?>&date_to=<?= $dateTo ?>" class="btn btn-secondary btn-sm">Скинути</a>
                <?php endif; ?>
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
            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/>
        </svg>
        <p>Немає даних за вказаний період</p>
    </div>
</div>
<?php else: ?>

<div class="card card-stretch">
    <div class="table-scroll">
        <table class="data-table" id="reportTable">
            <thead>
                <tr>
                    <th rowspan="2" style="width:200px">Склад</th>
                    <th colspan="3" style="text-align:center">Ресурс</th>
                    <?php 
                    // Збираємо унікальні матеріали
                    $allMaterials = [];
                    foreach ($report as $row) {
                        foreach ($row['materials'] as $matId => $mat) {
                            $allMaterials[$matId] = $mat['name'];
                        }
                    }
                    foreach ($allMaterials as $matId => $matName):
                    ?>
                    <th colspan="5" style="text-align:center"><?= htmlspecialchars($matName) ?></th>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <th class="text-right num">Початок</th>
                    <th class="text-right num">Зараз</th>
                    <th class="text-right num">Дельта</th>
                    <?php foreach ($allMaterials as $matId => $matName): ?>
                    <th class="text-right num">Надійшло</th>
                    <th class="text-right num">Норма</th>
                    <th class="text-right num">Поправка</th>
                    <th class="text-right num">Списано</th>
                    <th class="text-right num">Залишок</th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php 
                $totalOpening = 0;
                $totalCurrent = 0;
                $totalDelta = 0;
                foreach ($report as $row):
                    $totalOpening += (float)$row['opening_reading'];
                    $totalCurrent += (float)$row['current_reading'];
                    $totalDelta += (float)$row['resource_delta'];
                ?>
                <tr class="report-summary-row">
                    <td><?= htmlspecialchars($row['warehouse_name']) ?></td>
                    <td class="text-right num"><?= number_format($row['opening_reading'], 2, '.', ' ') ?></td>
                    <td class="text-right num"><?= number_format($row['current_reading'], 2, '.', ' ') ?></td>
                    <td class="text-right num"><?= number_format($row['resource_delta'], 2, '.', ' ') ?></td>
                    <?php foreach ($allMaterials as $matId => $matName): ?>
                    <?php $mat = $row['materials'][$matId] ?? null; ?>
                    <td class="text-right num"><?= $mat ? number_format($mat['received'], 2, '.', ' ') : '—' ?></td>
                    <td class="text-right num"><?= $mat ? number_format($mat['rate'], 4, '.', ' ') : '—' ?></td>
                    <td class="text-right num"><?= $mat && $mat['correction'] ? number_format($mat['correction'], 1, '.', ' ') . '%' : '—' ?></td>
                    <td class="text-right num"><?= $mat ? number_format($mat['consumed'], 2, '.', ' ') : '—' ?></td>
                    <td class="text-right num <?= ($mat && $mat['balance'] < 0) ? 'balance-negative' : '' ?>"><?= $mat ? number_format($mat['balance'], 2, '.', ' ') : '—' ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
                
                <!-- Підсумковий рядок -->
                <tr class="report-total-row">
                    <td><strong>Разом</strong></td>
                    <td class="text-right num"><strong><?= number_format($totalOpening, 2, '.', ' ') ?></strong></td>
                    <td class="text-right num"><strong><?= number_format($totalCurrent, 2, '.', ' ') ?></strong></td>
                    <td class="text-right num"><strong><?= number_format($totalDelta, 2, '.', ' ') ?></strong></td>
                    <?php foreach ($totals['materials'] as $matId => $mat): ?>
                    <td class="text-right num"><strong><?= number_format($mat['received'], 2, '.', ' ') ?></strong></td>
                    <td class="text-right num"><strong><?= number_format($mat['rate'], 4, '.', ' ') ?></strong></td>
                    <td class="text-right num"><strong><?= $mat['correction'] ? number_format($mat['correction'], 1, '.', ' ') . '%' : '—' ?></strong></td>
                    <td class="text-right num"><strong><?= number_format($mat['consumed'], 2, '.', ' ') ?></strong></td>
                    <td class="text-right num"><strong><?= number_format($mat['balance'], 2, '.', ' ') ?></strong></td>
                    <?php endforeach; ?>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<style>
.filter-panel {
    padding: 16px;
    margin-bottom: 16px;
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 16px;
    align-items: end;
}

.filter-grid-with-action {
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)) auto;
}

.form-group {
    margin-bottom: 0;
}

.form-label {
    display: block;
    font-weight: 600;
    font-size: 13px;
    color: var(--text);
    margin-bottom: 6px;
}

.form-input {
    width: 100%;
    padding: 10px 12px;
    font-size: 14px;
    border: 1px solid var(--border);
    border-radius: 6px;
    background: white;
}

.wh-filter-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 10px 12px;
    font-size: 14px;
    border: 1px solid var(--border);
    border-radius: 6px;
    background: white;
    color: var(--text);
    cursor: pointer;
}

.wh-filter-btn:hover {
    border-color: var(--blue);
    background: var(--blue-light);
}

.filter-action-inline {
    display: flex;
    flex-direction: column;
}

.filter-action-stack {
    display: flex;
    gap: 8px;
    align-items: flex-end;
    height: 42px;
}

.data-table th {
    background: #f8fafc;
    border: 1px solid var(--border);
}

.data-table td {
    border: 1px solid var(--border);
}

.num {
    font-variant-numeric: tabular-nums;
}

.balance-negative {
    color: var(--danger);
    font-weight: 600;
}

.report-total-row {
    background: #f0f2f5;
    font-weight: 600;
}

.report-total-row td {
    border-top: 2px solid var(--border);
}
</style>

<script>
// Відкрити фільтр складів
function openWarehouseFilter() {
    var warehouses = <?= json_encode($warehouses) ?>;
    var selected = <?= json_encode($selectedWarehouseIds) ?>;
    
    var html = '<div class="material-filter-modal">';
    html += '<div class="mf-header">';
    html += '<input type="text" class="mf-search" placeholder="Пошук складів..." oninput="filterWarehouses(this.value)">';
    html += '<div class="mf-actions">';
    html += '<button type="button" class="btn btn-sm" onclick="selectAllWarehouses()">Обрати все</button>';
    html += '<button type="button" class="btn btn-sm btn-secondary" onclick="deselectAllWarehouses()">Зняти все</button>';
    html += '</div>';
    html += '</div>';
    html += '<div class="mf-list" id="warehouseFilterList">';
    
    warehouses.forEach(function(w) {
        var isChecked = selected.indexOf(w.id) !== -1 ? 'checked' : '';
        html += '<label class="mf-item">';
        html += '<input type="checkbox" value="' + w.id + '" ' + isChecked + ' onchange="updateWarehouseCount()">';
        html += '<span>' + escapeHtml(w.name) + '</span>';
        html += '</label>';
    });
    
    html += '</div>';
    html += '<div class="mf-footer">';
    html += '<button type="button" class="btn btn-primary" onclick="applyWarehouseFilter()">Застосувати</button>';
    html += '</div>';
    html += '</div>';
    
    openModal('Склади', html);
    updateWarehouseCount();
}

function filterWarehouses(query) {
    var q = query.toLowerCase();
    var items = document.querySelectorAll('.mf-item');
    items.forEach(function(item) {
        var text = item.querySelector('span').textContent.toLowerCase();
        item.style.display = text.indexOf(q) !== -1 ? '' : 'none';
    });
}

function selectAllWarehouses() {
    document.querySelectorAll('.mf-item input[type="checkbox"]').forEach(function(cb) {
        if (cb.closest('.mf-item').style.display !== 'none') {
            cb.checked = true;
        }
    });
    updateWarehouseCount();
}

function deselectAllWarehouses() {
    document.querySelectorAll('.mf-item input[type="checkbox"]').forEach(function(cb) {
        cb.checked = false;
    });
    updateWarehouseCount();
}

function updateWarehouseCount() {
    var count = document.querySelectorAll('.mf-item input[type="checkbox"]:checked').length;
    var btn = document.querySelector('.wh-filter-btn span');
    if (btn) {
        btn.textContent = count === 0 ? 'Усі склади' : count + ' обрано';
    }
}

function applyWarehouseFilter() {
    var selected = [];
    document.querySelectorAll('.mf-item input[type="checkbox"]:checked').forEach(function(cb) {
        selected.push(cb.value);
    });
    
    document.getElementById('warehouseFilterValue').value = selected.join(',');
    closeModal();
}

function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<style>
.material-filter-modal {
    display: flex;
    flex-direction: column;
    height: 400px;
}

.mf-header {
    padding: 12px 16px;
    border-bottom: 1px solid var(--border);
    background: #f8fafc;
}

.mf-search {
    width: 100%;
    padding: 8px 12px;
    font-size: 14px;
    border: 1px solid var(--border);
    border-radius: 6px;
    margin-bottom: 10px;
}

.mf-actions {
    display: flex;
    gap: 8px;
}

.mf-list {
    flex: 1;
    overflow-y: auto;
    padding: 8px;
}

.mf-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    cursor: pointer;
    border-radius: 4px;
}

.mf-item:hover {
    background: var(--bg);
}

.mf-item input[type="checkbox"] {
    width: 16px;
    height: 16px;
}

.mf-footer {
    padding: 12px 16px;
    border-top: 1px solid var(--border);
    background: #f8fafc;
    display: flex;
    justify-content: flex-end;
}
</style>
