<?php
/**
 * Звіт по використанню ресурсів
 */
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Звіт по використанню ресурсів</h1>
        <p class="page-subtitle">Деталізація витрати ресурсів по матеріалах та складах</p>
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
        <button onclick="window.print()" class="btn btn-secondary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
                <rect x="6" y="14" width="12" height="8"/>
            </svg>
            Друк
        </button>
    </div>
</div>

<!-- Фільтри -->
<div class="card filter-panel">
    <form method="GET" class="filter-grid filter-grid-with-action">
        <div class="form-group" style="margin-bottom:0">
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
        <div class="form-group" style="margin-bottom:0">
            <label class="form-label">Період від</label>
            <input type="date" name="date_from" class="form-input" value="<?= htmlspecialchars($dateFrom) ?>">
        </div>
        <div class="form-group" style="margin-bottom:0">
            <label class="form-label">Період до</label>
            <input type="date" name="date_to" class="form-input" value="<?= htmlspecialchars($dateTo) ?>">
        </div>
        <div class="form-group filter-action-inline" style="margin-bottom:0">
            <label class="form-label">&nbsp;</label>
            <button type="submit" class="btn btn-primary">Сформувати</button>
        </div>
    </form>
</div>

<?php if ($resourceTypeId > 0 && !empty($reportData)): ?>
    <div class="card">
        <div class="table-scroll">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Матеріал / Склад</th>
                        <th class="text-right">Δ ресурсу</th>
                        <th class="text-right">Вх.сальдо</th>
                        <th class="text-right">Надходження</th>
                        <th class="text-right">Списано</th>
                        <th class="text-right">Вих.сальдо</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData as $material): ?>
                        <!-- Група матеріалу -->
                        <tr class="group-row" data-level="material" data-target="material-<?= $material['material_id'] ?>">
                            <td class="group-cell">
                                <span class="collapse-icon">▶</span>
                                <strong><?= htmlspecialchars($material['material_name']) ?></strong>
                            </td>
                            <td class="text-right"><?= number_format($material['total_delta'], 2) ?></td>
                            <td class="text-right"><?= number_format($material['total_opening'], 2) ?></td>
                            <td class="text-right"><?= number_format($material['total_incoming'], 2) ?></td>
                            <td class="text-right"><?= number_format($material['total_consumed'], 2) ?></td>
                            <td class="text-right"><?= number_format($material['total_closing'], 2) ?></td>
                        </tr>
                        
                        <!-- Дочірні групи - склади -->
                        <tbody class="detail-group" id="material-<?= $material['material_id'] ?>" style="display: none;">
                            <?php foreach ($material['warehouses'] as $warehouse): ?>
                                <tr class="group-row sub-group" data-level="warehouse" data-target="warehouse-<?= $material['material_id'] ?>-<?= $warehouse['warehouse_id'] ?>">
                                    <td class="group-cell sub-cell">
                                        <span class="collapse-icon">▶</span>
                                        <span class="sub-indent">└─ </span>
                                        <strong><?= htmlspecialchars($warehouse['warehouse_name']) ?></strong>
                                    </td>
                                    <td class="text-right"><?= number_format($warehouse['total_delta'], 2) ?></td>
                                    <td class="text-right"><?= number_format($warehouse['opening_balance'], 2) ?></td>
                                    <td class="text-right"><?= number_format($warehouse['total_incoming'], 2) ?></td>
                                    <td class="text-right"><?= number_format($warehouse['total_consumed'], 2) ?></td>
                                    <td class="text-right"><?= number_format($warehouse['closing_balance'], 2) ?></td>
                                </tr>
                                
                                <!-- Деталізація по днях -->
                                <tbody class="detail-group" id="warehouse-<?= $material['material_id'] ?>-<?= $warehouse['warehouse_id'] ?>" style="display: none;">
                                    <?php foreach ($warehouse['rows'] as $row): ?>
                                        <tr class="detail-row">
                                            <td class="detail-cell">
                                                <span class="detail-indent">└─ └─ </span>
                                                <?= date('d.m.Y', strtotime($row['date'])) ?>
                                            </td>
                                            <td class="text-right"><?= $row['delta'] ?></td>
                                            <td class="text-right"><?= number_format($row['opening_balance'], 2) ?></td>
                                            <td class="text-right incoming"><?= number_format($row['incoming'], 2) ?></td>
                                            <td class="text-right outgoing"><?= number_format($row['consumed'], 2) ?></td>
                                            <td class="text-right"><?= number_format($row['closing_balance'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            <?php endforeach; ?>
                        </tbody>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="totals-footer">
                    <tr>
                        <th>Загальний підсумок</th>
                        <th class="text-right"><?= number_format($totalDelta, 2) ?></th>
                        <th class="text-right"><?= number_format($totalOpening, 2) ?></th>
                        <th class="text-right"><?= number_format($totalIncoming, 2) ?></th>
                        <th class="text-right"><?= number_format($totalConsumed, 2) ?></th>
                        <th class="text-right"><?= number_format($totalClosing, 2) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
<?php elseif ($resourceTypeId > 0): ?>
    <div class="card">
        <div class="empty-state">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <polygon points="22,3 2,3 10,12.46 10,19 14,21 14,12.46"/>
            </svg>
            <p>Немає даних за вибраний період</p>
        </div>
    </div>
<?php else: ?>
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
<?php endif; ?>

<script>
// Групування як у звіті по складу
document.querySelectorAll('.group-row').forEach(function(row) {
    var icon = row.querySelector('.collapse-icon');
    if (!icon) return;
    
    row.addEventListener('click', function(e) {
        e.stopPropagation();
        var targetId = this.dataset.target;
        if (!targetId) return;
        
        var target = document.getElementById(targetId);
        if (!target) return;
        
        if (target.style.display === 'none') {
            target.style.display = '';
            icon.textContent = '▼';
        } else {
            target.style.display = 'none';
            icon.textContent = '▶';
        }
    });
});
</script>