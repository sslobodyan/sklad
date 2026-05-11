<?php
function formatNum($n) {
    if ($n == 0) return '—';
    return number_format($n, 2, '.', ' ');
}
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Звіт по складу</h1>
        <p class="page-subtitle">Залишки та рух матеріалів</p>
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

<!-- Фільтри -->
<div class="card filter-panel">
    <form method="GET" class="filter-grid filter-grid-with-action">
        <div class="form-group" style="margin-bottom:0">
            <label class="form-label">Склад</label>
            <select name="warehouse_id" class="autocomplete" data-placeholder="— Оберіть склад —" data-submit-on-change>
                <option value="">— Оберіть склад —</option>
                <?php foreach ($warehouses as $w): ?>
                <option value="<?= $w['id'] ?>" <?= $warehouseId == $w['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($w['name']) ?>
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

<?php if (!$warehouseId): ?>
<div class="card">
    <div class="empty-state">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <polygon points="22,3 2,3 10,12.46 10,19 14,21 14,12.46"/>
        </svg>
        <p>Оберіть склад для формування звіту</p>
    </div>
</div>
<?php elseif (empty($report)): ?>
<div class="card">
    <div class="empty-state">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/>
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
        <table class="data-table" id="reportTable">
            <thead>
                <tr>
                    <th style="width:28px"></th>
                    <th>Матеріал</th>
                    <th class="text-right" style="width:100px">Вх. сальдо</th>
                    <th class="text-right" style="width:100px">Прихід</th>
                    <th class="text-right" style="width:100px">Витрата</th>
                    <th class="text-right" style="width:100px">Вих. сальдо</th>
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
                    $rowId = 'mat-' . $row['material_id'];

                    // Рахуємо сальдо по рухах у хронологічному порядку,
                    // а виводимо деталі у зворотному порядку дат
                    $detailRows = [];
                    $runningBalance = (float)$row['opening_balance'];
                    foreach ($row['details'] as $detail) {
                        $runningBalance += (float)$detail['incoming'] - (float)$detail['outgoing'];
                        $detail['balance_after'] = $runningBalance;
                        $detailRows[] = $detail;
                    }
                    $detailRows = array_reverse($detailRows);
                ?>
                <tr class="expandable report-summary-row" onclick="toggleRow('<?= $rowId ?>')">
                    <td>
                        <?php if (!empty($row['details'])): ?>
                        <span class="expand-icon" id="icon-<?= $rowId ?>">▶</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['material_name']) ?></td>
                    <td class="text-right font-mono"><?= formatNum($row['opening_balance']) ?></td>
                    <td class="text-right font-mono"><?= formatNum($row['incoming']) ?></td>
                    <td class="text-right font-mono"><?= formatNum($row['outgoing']) ?></td>
                    <td class="text-right font-mono <?= $row['closing_balance'] < 0 ? 'balance-negative' : '' ?>"><?= formatNum($row['closing_balance']) ?></td>
                </tr>
                <?php foreach ($detailRows as $d): ?>
                <tr class="detail-row" data-parent="<?= $rowId ?>" style="display:none" ondblclick="goToMovement(<?= $d['id'] ?>)">
                    <td></td>
                    <td style="padding-left:24px">
                        <span class="text-muted font-mono" style="font-size:11px"><?= formatDateUa($d['date']) ?></span>
                        <span class="badge <?= $d['incoming'] > 0 ? 'badge-in' : 'badge-out' ?>"><?= $d['type'] ?></span>
                        <span class="text-muted"><?= htmlspecialchars($d['counterpart']) ?></span>
                        <?php if ($d['note']): ?>
                        <span class="text-muted" style="font-style:italic"> — <?= htmlspecialchars($d['note']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td></td>
                    <td class="text-right font-mono"><?= $d['incoming'] > 0 ? formatNum($d['incoming']) : '' ?></td>
                    <td class="text-right font-mono"><?= $d['outgoing'] > 0 ? formatNum($d['outgoing']) : '' ?></td>
                    <td class="text-right font-mono <?= $d['balance_after'] < 0 ? 'balance-negative' : '' ?>"><?= formatNum($d['balance_after']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endforeach; ?>
                
                <tr class="totals-row report-summary-row">
                    <td></td>
                    <td>Разом</td>
                    <td class="text-right font-mono"><?= formatNum($totalOpening) ?></td>
                    <td class="text-right font-mono"><?= formatNum($totalIn) ?></td>
                    <td class="text-right font-mono"><?= formatNum($totalOut) ?></td>
                    <td class="text-right font-mono <?= $totalClosing < 0 ? 'balance-negative' : '' ?>"><?= formatNum($totalClosing) ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="card-footer-info">
        Склад: <strong><?= htmlspecialchars($selectedWarehouse['name'] ?? '') ?></strong> •
        Період: <?= formatDateUa($dateFrom) ?> — <?= formatDateUa($dateTo) ?> •
        Позицій: <?= count($report) ?>
    </div>
</div>

<script>
function toggleRow(id) {
    const rows = document.querySelectorAll('[data-parent="' + id + '"]');
    const icon = document.getElementById('icon-' + id);
    const isHidden = rows[0] && rows[0].style.display === 'none';
    rows.forEach(r => r.style.display = isHidden ? '' : 'none');
    if (icon) {
        icon.classList.toggle('expanded', isHidden);
    }
}

function toggleAll(expand) {
    document.querySelectorAll('.detail-row').forEach(r => r.style.display = expand ? '' : 'none');
    document.querySelectorAll('.expand-icon').forEach(i => i.classList.toggle('expanded', expand));
}

function goToMovement(id) {
    window.location.href = '<?= $basePath ?>/movements?highlight=' + id;
}
</script>
<?php endif; ?>
