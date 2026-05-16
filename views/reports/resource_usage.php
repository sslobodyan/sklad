<?php
/**
 * Звіт по використанню ресурсів
 */

$resourceName = 'Δ ресурсу';
$resourceUnit = '';
if ($resourceTypeId > 0) {
    $typeInfo = $this->db->query("SELECT name, unit FROM resource_types WHERE id = ?", [$resourceTypeId])->fetch();
    if ($typeInfo) {
        $resourceName = $typeInfo['name'];
        $resourceUnit = $typeInfo['unit'];
    }
}

function formatNum($n) {
    if ($n == 0) return '—';
    return number_format($n, 2, '.', ' ');
}
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Звіт по використанню ресурсу</h1>
        <p class="page-subtitle">Деталізація витрати ресурсу по матеріалах та складах</p>
    </div>
    <div class="header-buttons">
        <button class="date-range-btn" onclick="toggleDatePanel(this)" title="Глобальний період">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            <span class="dr-dates">
                <?= date('d.m.Y', strtotime($dateFrom)) ?> — <?= date('d.m.Y', strtotime($dateTo)) ?>
                <?php
                $closedDate = (new ConfigModel($this->db))->getClosedDate();
                if ($closedDate): ?>
                    <span class="dr-closed">🔒 <?= date('d.m.Y', strtotime($closedDate)) ?></span>
                <?php endif; ?>
            </span>
        </button>
        <a href="<?= BASE_PATH ?>/reports/resource/export?resource_type_id=<?= $resourceTypeId ?>&date_from=<?= $dateFrom ?>&date_to=<?= $dateTo ?>" class="btn btn-secondary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                <polyline points="7 10 12 15 17 10"/>
                <line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
            Експорт
        </a>
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
    <form method="GET" class="filter-grid filter-grid-with-action">
        <div class="form-group">
            <label class="form-label">Тип ресурсу</label>
            <select name="resource_type_id" class="autocomplete" data-placeholder="— Оберіть тип ресурсу —" data-submit-on-change>
                <option value="">— Оберіть тип ресурсу —</option>
                <?php foreach ($types as $type): ?>
                <option value="<?= $type['id'] ?>" <?= $resourceTypeId == $type['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($type['name']) ?> (<?= htmlspecialchars($type['unit']) ?>)
                </option>
                <?php endforeach; ?>
            </select>
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
            <button type="submit" class="btn btn-primary">Сформувати</button>
        </div>
    </form>
</div>

<?php if (!$resourceTypeId): ?>
<div class="card">
    <div class="empty-state">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <circle cx="12" cy="16" r="0.5" fill="currentColor" stroke="none"/>
        </svg>
        <p>Оберіть тип ресурсу для формування звіту</p>
    </div>
</div>
<?php elseif (empty($reportData)): ?>
<div class="card">
    <div class="empty-state">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <polygon points="22,3 2,3 10,12.46 10,19 14,21 14,12.46"/>
        </svg>
        <p>Немає даних за вибраний період</p>
    </div>
</div>
<?php else: ?>

<div class="report-controls">
    <button class="btn btn-sm btn-secondary" onclick="expandAll()">Розгорнути все</button>
    <button class="btn btn-sm btn-secondary" onclick="collapseAll()">Згорнути все</button>
</div>

<div class="card card-stretch">
    <div class="table-scroll">
        <table class="data-table" id="reportTable">
            <thead>
                <tr>
                    <th class="col-expand"></th>
                    <th>Матеріал / Склад / Дата</th>
                    <th class="text-right col-reading">Лічильник<br>(<?= htmlspecialchars($resourceUnit) ?>)</th>
                    <th class="text-right col-delta"><?= $resourceName ?><br>(<?= htmlspecialchars($resourceUnit) ?>)</th>
                    <th class="text-right col-rate">Норма<br>списання</th>
                    <th class="text-right col-correction">Поправка<br>%</th>
                    <th class="text-right col-balance">Вхідне<br>сальдо</th>
                    <th class="text-right col-incoming">Надійшло</th>
                    <th class="text-right col-outgoing">Списано</th>
                    <th class="text-right col-balance">Вихідне<br>сальдо</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reportData as $material): ?>
                <!-- Група матеріалу -->
                <tr class="expandable report-summary-row" onclick="toggleRow('material-<?= $material['material_id'] ?>')">
                    <td class="col-expand">
                        <span class="expand-icon" id="icon-material-<?= $material['material_id'] ?>">▶</span>
                    </td>
                    <td class="font-medium"><?= htmlspecialchars($material['material_name']) ?></td>
                    <td class="text-right">—</td>
                    <td class="text-right font-bold"><?= formatNum($material['total_delta']) ?></td>
                    <td class="text-right">—</td>
                    <td class="text-right">—</td>
                    <td class="text-right font-bold"><?= formatNum($material['total_opening']) ?></td>
                    <td class="text-right font-bold"><?= formatNum($material['total_incoming']) ?></td>
                    <td class="text-right font-bold"><?= formatNum($material['total_consumed']) ?></td>
                    <td class="text-right font-bold"><?= formatNum($material['total_closing']) ?></td>
                </tr>
                
                <!-- Деталізація по складах -->
                <tbody class="detail-group" id="material-<?= $material['material_id'] ?>" style="display:none">
                    <?php foreach ($material['warehouses'] as $warehouse): ?>
                    <!-- Заголовок складу -->
                    <tr class="expandable detail-summary-row" onclick="toggleRow('warehouse-<?= $material['material_id'] ?>-<?= $warehouse['warehouse_id'] ?>')">
                        <td class="col-expand">
                            <span class="expand-icon" id="icon-warehouse-<?= $material['material_id'] ?>-<?= $warehouse['warehouse_id'] ?>">▶</span>
                        </td>
                        <td class="detail-cell">
                            <strong><?= htmlspecialchars($warehouse['warehouse_name']) ?></strong>
                        </td>
                        <td class="text-right">—</td>
                        <td class="text-right"><?= formatNum($warehouse['total_delta']) ?></td>
                        <td class="text-right">—</td>
                        <td class="text-right">—</td>
                        <td class="text-right"><?= formatNum($warehouse['opening_balance']) ?></td>
                        <td class="text-right"><?= formatNum($warehouse['total_incoming']) ?></td>
                        <td class="text-right"><?= formatNum($warehouse['total_consumed']) ?></td>
                        <td class="text-right"><?= formatNum($warehouse['closing_balance']) ?></td>
                    </tr>
                    
                    <!-- Деталізація по днях -->
                    <tbody class="detail-group" id="warehouse-<?= $material['material_id'] ?>-<?= $warehouse['warehouse_id'] ?>" style="display:none">
                        <?php foreach ($warehouse['rows'] as $row): ?>
                        <tr class="detail-row">
                            <td class="col-expand"></td>
                            <td class="detail-cell">
                                <?= date('d.m.Y', strtotime($row['date'])) ?>
                                <?php if (!empty($row['note'])): ?>
                                <span class="detail-note">(<?= htmlspecialchars($row['note']) ?>)</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right"><?= $row['reading'] ?></td>
                            <td class="text-right"><?= $row['delta'] ?></td>
                            <td class="text-right"><?= $row['rate'] ?></td>
                            <td class="text-right"><?= $row['correction_pct'] ?></td>
                            <td class="text-right"><?= formatNum($row['opening_balance']) ?></td>
                            <td class="text-right incoming"><?= $row['incoming'] ?></td>
                            <td class="text-right outgoing"><?= $row['consumed'] ?></td>
                            <td class="text-right balance"><?= formatNum($row['closing_balance']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <?php endforeach; ?>
                </tbody>
                <?php endforeach; ?>
                
                <!-- Підсумок -->
                <tr class="totals-row report-summary-row">
                    <td class="col-expand"></td>
                    <td class="font-bold">Загальний підсумок</td>
                    <td class="text-right">—</td>
                    <td class="text-right font-bold"><?= formatNum($totalDelta) ?></td>
                    <td class="text-right">—</td>
                    <td class="text-right">—</td>
                    <td class="text-right font-bold"><?= formatNum($totalOpening) ?></td>
                    <td class="text-right font-bold"><?= formatNum($totalIncoming) ?></td>
                    <td class="text-right font-bold"><?= formatNum($totalConsumed) ?></td>
                    <td class="text-right font-bold"><?= formatNum($totalClosing) ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="card-footer-info">
        Ресурс: <strong><?= htmlspecialchars($resourceName) ?> (<?= htmlspecialchars($resourceUnit) ?>)</strong> •
        Період: <?= date('d.m.Y', strtotime($dateFrom)) ?> — <?= date('d.m.Y', strtotime($dateTo)) ?> •
        Матеріалів: <?= count($reportData) ?>
    </div>
</div>

<script>
function toggleRow(id) {
    var rows = document.querySelectorAll('#reportTable .detail-group#' + id + ', #reportTable tbody.detail-group#' + id);
    var icon = document.getElementById('icon-' + id);
    if (!rows.length) return;
    
    var isHidden = rows[0].style.display === 'none';
    rows.forEach(function(r) { r.style.display = isHidden ? '' : 'none'; });
    if (icon) icon.classList.toggle('expanded', isHidden);
}

function expandAll() {
    document.querySelectorAll('#reportTable .detail-group').forEach(function(el) {
        el.style.display = '';
    });
    document.querySelectorAll('#reportTable .expand-icon').forEach(function(icon) {
        icon.classList.add('expanded');
    });
}

function collapseAll() {
    document.querySelectorAll('#reportTable .detail-group').forEach(function(el) {
        el.style.display = 'none';
    });
    document.querySelectorAll('#reportTable .expand-icon').forEach(function(icon) {
        icon.classList.remove('expanded');
    });
}
</script>
<?php endif; ?>