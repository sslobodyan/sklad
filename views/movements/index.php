<?php
function getMovementType($m) {
    if (!$m['warehouse_from_id'] && $m['warehouse_to_id']) return 'in';
    if ($m['warehouse_from_id'] && !$m['warehouse_to_id']) return 'out';
    return 'transfer';
}

function getTypeLabel($type) {
    switch ($type) {
        case 'in':       return ['Прихід',    'badge-in'];
        case 'out':      return ['Списання',  'badge-out'];
        default:         return ['Переміщ.',  'badge-transfer'];
    }
}

function sortUrl($col, $currentSort, $currentDir, $basePath, $filters) {
    $dir = ($currentSort === $col && $currentDir === 'asc') ? 'desc' : 'asc';
    $params = array_filter($filters);
    $params['sort'] = $col;
    $params['order'] = $dir;
    return $basePath . '/movements?' . http_build_query($params);
}

function sortIcon($col, $currentSort, $currentDir) {
    if ($currentSort !== $col) {
        return '<span class="sort-icon sort-inactive">⇅</span>';
    }
    return $currentDir === 'asc'
        ? '<span class="sort-icon sort-asc">▲</span>'
        : '<span class="sort-icon sort-desc">▼</span>';
}

$sortKey = $sortKey ?? 'date';
$sortDir = $sortDir ?? 'desc';
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Рух матеріалів</h1>
        <p class="page-subtitle">Прихід, списання та переміщення</p>
    </div>
    <div class="header-buttons">
        <button class="date-range-btn" onclick="toggleDatePanel(this)" title="Глобальний період та закритий період">
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
        <button class="btn btn-secondary" onclick="openImportModal()" title="Імпорт з Excel">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                <polyline points="17 8 12 3 7 8"/>
                <line x1="12" y1="3" x2="12" y2="15"/>
            </svg>
            Імпорт
        </button>
        <a href="<?= $basePath ?>/movements/export?<?= http_build_query(array_filter($filters)) ?>" class="btn btn-secondary" title="Експорт у Excel">
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

<!-- Фільтри -->
<div class="card filter-panel">
    <form method="GET" class="filter-grid filter-grid-with-action">
        <input type="hidden" name="sort" value="<?= htmlspecialchars($sortKey) ?>">
        <input type="hidden" name="order" value="<?= htmlspecialchars($sortDir) ?>">
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
                <option value="__incoming" <?= ($filters['warehouse_id'] ?? '') === '__incoming' ? 'selected' : '' ?>>⬇ Прихід ззовні</option>
                <option value="__writeoff" <?= ($filters['warehouse_id'] ?? '') === '__writeoff' ? 'selected' : '' ?>>⬆ Списання</option>
                <?php foreach ($warehouses as $w): ?>
                <option value="<?= $w['id'] ?>" <?= ($filters['warehouse_id'] ?? '') == $w['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($w['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Матеріал</label>
            <select name="material_id" class="autocomplete" data-placeholder="Усі матеріали">
                <option value="">Усі матеріали</option>
                <?php foreach ($materials as $m): ?>
                <option value="<?= $m['id'] ?>" <?= ($filters['material_id'] ?? '') == $m['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($m['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group filter-action-inline">
            <label class="form-label">&nbsp;</label>
            <div class="filter-action-stack">
                <button type="submit" class="btn btn-primary">Застосувати</button>
                <a href="<?= $basePath ?>/movements?date_from=<?= urlencode($globalDateFrom) ?>&date_to=<?= urlencode($globalDateTo) ?>" class="btn btn-secondary btn-sm">Скинути</a>
            </div>
        </div>
    </form>
</div>

<?php
$printFilters = [];
if (!empty($filters['date_from'])) $printFilters[] = 'від ' . formatDateUa($filters['date_from']);
if (!empty($filters['date_to'])) $printFilters[] = 'до ' . formatDateUa($filters['date_to']);
if (!empty($filters['warehouse_id'])) {
    if ($filters['warehouse_id'] === '__incoming') $printFilters[] = 'тип: Прихід ззовні';
    elseif ($filters['warehouse_id'] === '__writeoff') $printFilters[] = 'тип: Списання';
    else {
        foreach ($warehouses as $w) {
            if ($w['id'] == $filters['warehouse_id']) { $printFilters[] = 'склад: ' . $w['name']; break; }
        }
    }
}
if (!empty($filters['material_id'])) {
    foreach ($materials as $mt) {
        if ($mt['id'] == $filters['material_id']) { $printFilters[] = 'матеріал: ' . $mt['name']; break; }
    }
}
$sortNames = ['date'=>'Дата','from'=>'Звідки','to'=>'Куди','material'=>'Матеріал','quantity'=>'Кількість','note'=>'Примітка'];
$printSort = ($sortNames[$sortKey] ?? 'Дата') . ' (' . ($sortDir === 'asc' ? '↑' : '↓') . ')';
?>
<div class="print-header">
    <?php if ($printFilters): ?>
    <div><strong>Фільтри:</strong> <?= htmlspecialchars(implode(' • ', $printFilters)) ?></div>
    <?php endif; ?>
    <div><strong>Сортування:</strong> <?= $printSort ?> • <strong>Записів:</strong> <?= count($movements) ?> • <strong>Дата друку:</strong> <?= date('d.m.Y H:i') ?></div>
</div>

<div class="card card-stretch">
    <?php if (empty($movements)): ?>
    <div class="empty-state">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M8 3l4 8 5-5 5 15H2L8 3z"/>
        </svg>
        <p>Руху матеріалів поки немає</p>
        <button class="btn btn-primary btn-sm" onclick="openMovementModal()">Додати перший рух</button>
    </div>
    <?php else: ?>
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-date">
                        <a href="<?= sortUrl('date', $sortKey, $sortDir, $basePath, $filters) ?>" class="sort-header">
                            Дата <?= sortIcon('date', $sortKey, $sortDir) ?>
                        </a>
                    </th>
                    <th class="col-type">Тип</th>
                    <th class="col-warehouse">
                        <a href="<?= sortUrl('from', $sortKey, $sortDir, $basePath, $filters) ?>" class="sort-header">
                            Звідки <?= sortIcon('from', $sortKey, $sortDir) ?>
                        </a>
                    </th>
                    <th class="col-arrow"></th>
                    <th class="col-warehouse">
                        <a href="<?= sortUrl('to', $sortKey, $sortDir, $basePath, $filters) ?>" class="sort-header">
                            Куди <?= sortIcon('to', $sortKey, $sortDir) ?>
                        </a>
                    </th>
                    <th>
                        <a href="<?= sortUrl('material', $sortKey, $sortDir, $basePath, $filters) ?>" class="sort-header">
                            Матеріал <?= sortIcon('material', $sortKey, $sortDir) ?>
                        </a>
                    </th>
                    <th class="col-quantity text-right">
                        <a href="<?= sortUrl('quantity', $sortKey, $sortDir, $basePath, $filters) ?>" class="sort-header">
                            К-сть <?= sortIcon('quantity', $sortKey, $sortDir) ?>
                        </a>
                    </th>
                    <th>
                        <a href="<?= sortUrl('note', $sortKey, $sortDir, $basePath, $filters) ?>" class="sort-header">
                            Примітка <?= sortIcon('note', $sortKey, $sortDir) ?>
                        </a>
                    </th>
                    <th class="col-actions no-print-col">
                        <button class="btn btn-primary btn-sm table-header-add" title="Додати рух" onclick="openMovementModal()">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                            Додати
                        </button>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($movements as $m):
                    $type = getMovementType($m);
                    list($typeText, $typeCls) = getTypeLabel($type);
                    $isHighlight = isset($highlightId) && $highlightId == $m['id'];
                    $isClosed = !empty($closedDate) && $m['movement_date'] <= $closedDate;
                    $isAuto = !empty($m['resource_log_id']);
                    $isLocked = $isClosed || $isAuto;

                    $jsData = json_encode([
                        'id' => $m['id'],
                        'movement_date' => $m['movement_date'],
                        'warehouse_from_id' => $m['warehouse_from_id'],
                        'warehouse_to_id' => $m['warehouse_to_id'],
                        'material_id' => $m['material_id'],
                        'quantity' => $m['quantity'],
                        'note' => $m['note'] ?? '',
                        'resource_log_id' => $m['resource_log_id'] ?? null,
                        'resource_value' => $m['resource_value'] ?? null,
                        'resource_delta' => $m['resource_delta'] ?? null,
                        'resource_rate' => $m['resource_rate'] ?? null,
                        'resource_correction' => $m['resource_correction'] ?? null,
                        'resource_unit' => $m['resource_unit'] ?? '',
                        'resource_format' => $m['resource_format'] ?? 'dec2',
                    ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
                ?>
                <?php
                    $trClasses = [];
                    if ($isHighlight) $trClasses[] = 'row-highlight';
                    if ($isClosed && !$isAuto) $trClasses[] = 'row-closed';
                    if ($isAuto && $isClosed) $trClasses[] = 'row-closed';
                    if ($isAuto) $trClasses[] = 'row-auto';
                    if (!$isLocked) $trClasses[] = 'row-editable';
                ?>
                <tr id="row-<?= $m['id'] ?>" class="<?= implode(' ', $trClasses) ?>"<?php
                    if (!$isLocked) {
                        echo " ondblclick='openMovementModal($jsData)' title='Подвійний клік — редагувати'";
                    } elseif ($isAuto) {
                        echo " ondblclick='viewMovementModal($jsData)' title='Подвійний клік — перегляд'";
                    } elseif ($isClosed) {
                        echo " ondblclick='viewMovementModal($jsData)' title='Подвійний клік — перегляд (закритий період)'";
                    }
                ?>>
                    <td class="font-mono"><?= formatDateUa($m['movement_date']) ?></td>
                    <td><span class="badge <?= $typeCls ?>"><?= $typeText ?></span></td>
                    <td><?= $m['warehouse_from_name'] ? htmlspecialchars($m['warehouse_from_name']) : '<span class="text-muted">ззовні</span>' ?></td>
                    <td class="text-muted">→</td>
                    <td><?= $m['warehouse_to_name'] ? htmlspecialchars($m['warehouse_to_name']) : '<span class="text-muted">списання</span>' ?></td>
                    <td class="font-medium"><?= htmlspecialchars($m['material_name']) ?></td>
                    <td class="text-right font-mono font-bold"><?= number_format((float)$m['quantity'], 2, '.', '') ?></td>
                    <td class="text-muted note-cell"><?= htmlspecialchars($m['note'] ?? '') ?></td>

<td class="col-actions no-print-col">
    <div class="actions">
        <?php if (!$isAuto): ?>
        <!-- РУЧНИЙ ЗАПИС: Копіювання доступне ЗАВЖДИ (навіть у закритому періоді) -->
        <button class="btn-icon" title="Створити на основі цього" onclick='copyMovement(<?= $jsData ?>)'>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                <path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>
            </svg>
        </button>
        <?php endif; ?>
        
        <?php if ($isAuto): ?>
            <!-- АВТОМАТИЧНИЙ ЗАПИС (з resource_log_id) -->
            <?php if ($isClosed): ?>
                <span class="closed-lock" title="Закритий період">🔒</span>
            <?php endif; ?>
            <a href="<?= $basePath ?>/resources?highlight=<?= $m['resource_log_id'] ?>" class="btn-icon btn-icon-goto" title="Перейти до Витрати ресурсів">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14"/><path d="M12 5l7 7-7 7"/>
                </svg>
            </a>
        <?php elseif (!$isClosed): ?>
            <!-- РУЧНИЙ ЗАПИС у ВІДКРИТОМУ періоді: редагування + видалення -->
            <button class="btn-icon" title="Редагувати" onclick='openMovementModal(<?= $jsData ?>)'>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
            </button>
            <button class="btn-icon btn-icon-danger" title="Видалити"
                    onclick="confirmDelete('<?= $basePath ?>/movements/delete/<?= $m['id'] ?>', 'Видалити цей запис руху?')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 6h18"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                </svg>
            </button>
        <?php elseif ($isClosed && !$isAuto): ?>
            <!-- РУЧНИЙ ЗАПИС у ЗАКРИТОМУ періоді: тільки копіювання (вже додано вище), показуємо замок -->
            <span class="closed-lock" title="Закритий період (тільки перегляд і копіювання)">🔒</span>
        <?php endif; ?>
    </div>
</td>

                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer-info">Показано: <?= count($movements) ?> записів</div>
    <?php endif; ?>
</div>

<script>
window.warehousesList = <?= json_encode($warehouses, JSON_UNESCAPED_UNICODE) ?>;
window.materialsList = <?= json_encode($materials, JSON_UNESCAPED_UNICODE) ?>;

<?php if (!empty($highlightId)): ?>
(function() {
    var el = document.getElementById('row-<?= (int)$highlightId ?>');
    if (!el) return;

    el.scrollIntoView({behavior: 'smooth', block: 'center'});

    var timer = setTimeout(function() { clearHighlight(); }, 15000);

    var table = el.closest('table');
    if (table) {
        table.addEventListener('click', function handler(e) {
            var row = e.target.closest('tr');
            if (row && row !== el) {
                clearHighlight();
                table.removeEventListener('click', handler);
            }
        });
    }

    function clearHighlight() {
        clearTimeout(timer);
        el.classList.add('fade-out');
        setTimeout(function() {
            el.classList.remove('row-highlight', 'fade-out');
        }, 500);
    }
})();
<?php endif; ?>
</script>