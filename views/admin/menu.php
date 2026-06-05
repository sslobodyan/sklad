<div class="page-header">
    <div>
        <h1 class="page-title">Управління меню</h1>
        <p class="page-subtitle">Редагування назв, груп, порядку та налаштувань пунктів меню</p>
    </div>
</div>

<!-- Форма додавання групи -->
<div class="card filter-panel" style="margin-bottom: 16px;">
    <form method="post" action="<?= $basePath ?>/adminMenu/addGroup" class="filter-grid" style="grid-template-columns: 1fr auto auto;">
        <div class="form-group">
            <label class="form-label">Нова група</label>
            <input type="text" name="group_name" class="form-input" placeholder="Назва групи" required>
        </div>
        <div class="form-group">
            <label class="form-label">Порядок</label>
            <input type="number" name="sort_order" class="form-input" value="999" step="10" style="width: 80px;">
        </div>
        <div class="form-group filter-action-inline">
            <label class="form-label">&nbsp;</label>
            <button type="submit" class="btn btn-primary">+ Додати групу</button>
        </div>
    </form>
</div>

<!-- Таблиця меню -->
<div class="card card-stretch" style="height: calc(100vh - 280px); display: flex; flex-direction: column; min-height: 0;">
    <form method="post" action="<?= $basePath ?>/adminMenu/save" id="menuForm" style="display: flex; flex-direction: column; flex: 1; min-height: 0;">
        <div class="table-scroll" style="flex: 1; overflow: auto; min-height: 0;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 40px">ID</th>
                        <th>Контролер</th>
                        <th>Назва</th>
                        <th style="width: 140px">Група</th>
                        <th style="width: 70px" class="text-center">Порядок</th>
                        <th style="width: 70px" class="text-center">Активний</th>
                        <th style="width: 80px" class="text-center">Потрібна дата</th>
                    <tr>
                </thead>
                <tbody>
                    <?php 
                    $groupsList = [];
                    $itemsList = [];
                    
                    foreach ($menuItems as $item) {
                        if ($item['controller'] === null) {
                            $groupsList[] = $item;
                        } else {
                            $itemsList[] = $item;
                        }
                    }
                    
                    usort($groupsList, fn($a, $b) => $a['sort_order'] - $b['sort_order']);
                    ?>
                    
                    <?php foreach ($groupsList as $group): ?>
                    <tr class="detail-summary-row">
                        <td class="text-muted"><?= $group['id'] ?></td>
                        <td class="font-mono" style="font-size: 11px;">—</td>
                        <td>
                            <input type="text" name="items[<?= $group['id'] ?>][label]" value="<?= htmlspecialchars($group['label']) ?>" class="form-input" style="width: 180px;">
                        </td>
                        <td>
                            <span class="badge">Група</span>
                            <input type="hidden" name="items[<?= $group['id'] ?>][parent_id]" value="">
                        </td>
                        <td class="text-center">
                            <input type="number" name="items[<?= $group['id'] ?>][sort_order]" value="<?= $group['sort_order'] ?>" class="form-input" style="width: 60px; text-align: center; padding: 6px 4px;" step="5" min="0">
                        </td>
                        <td class="text-center">
                            <input type="checkbox" name="items[<?= $group['id'] ?>][is_enabled]" value="1" <?= $group['is_enabled'] ? 'checked' : '' ?>>
                        </td>
                        <td class="text-center">
                            <input type="checkbox" name="items[<?= $group['id'] ?>][requires_date_range]" value="1" <?= $group['requires_date_range'] ? 'checked' : '' ?>>
                        </td>
                    </tr>
                    
                    <?php
                    $groupItems = array_filter($itemsList, fn($item) => $item['parent_id'] == $group['id']);
                    usort($groupItems, fn($a, $b) => $a['sort_order'] - $b['sort_order']);
                    foreach ($groupItems as $item):
                    ?>
                    <tr>
                        <td class="text-muted"><?= $item['id'] ?></td>
                        <td class="font-mono" style="font-size: 11px;"><?= htmlspecialchars($item['controller'] ?? '—') ?></td>
                        <td>
                            <input type="text" name="items[<?= $item['id'] ?>][label]" value="<?= htmlspecialchars($item['label']) ?>" class="form-input" style="width: 180px;">
                        </td>
                        <td>
                            <select name="items[<?= $item['id'] ?>][parent_id]" class="form-select" style="width: 100%;">
                                <option value="">— Без групи —</option>
                                <?php foreach ($groupsList as $g): ?>
                                <option value="<?= $g['id'] ?>" <?= ($item['parent_id'] == $g['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($g['label']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td class="text-center">
                            <input type="number" name="items[<?= $item['id'] ?>][sort_order]" value="<?= $item['sort_order'] ?>" class="form-input" style="width: 60px; text-align: center; padding: 6px 4px;" step="5" min="0">
                        </td>
                        <td class="text-center">
                            <input type="checkbox" name="items[<?= $item['id'] ?>][is_enabled]" value="1" <?= $item['is_enabled'] ? 'checked' : '' ?>>
                        </td>
                        <td class="text-center">
                            <input type="checkbox" name="items[<?= $item['id'] ?>][requires_date_range]" value="1" <?= $item['requires_date_range'] ? 'checked' : '' ?>>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                    
                    <?php
                    $ungrouped = array_filter($itemsList, fn($item) => $item['parent_id'] === null);
                    usort($ungrouped, fn($a, $b) => $a['sort_order'] - $b['sort_order']);
                    foreach ($ungrouped as $item):
                    ?>
                    <tr>
                        <td class="text-muted"><?= $item['id'] ?></td>
                        <td class="font-mono" style="font-size: 11px;"><?= htmlspecialchars($item['controller'] ?? '—') ?></td>
                        <td>
                            <input type="text" name="items[<?= $item['id'] ?>][label]" value="<?= htmlspecialchars($item['label']) ?>" class="form-input" style="width: 180px;">
                        </td>
                        <td>
                            <select name="items[<?= $item['id'] ?>][parent_id]" class="form-select" style="width: 100%;">
                                <option value="">— Без групи —</option>
                                <?php foreach ($groupsList as $g): ?>
                                <option value="<?= $g['id'] ?>" <?= ($item['parent_id'] == $g['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($g['label']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td class="text-center">
                            <input type="number" name="items[<?= $item['id'] ?>][sort_order]" value="<?= $item['sort_order'] ?>" class="form-input" style="width: 60px; text-align: center; padding: 6px 4px;" step="5" min="0">
                        </td>
                        <td class="text-center">
                            <input type="checkbox" name="items[<?= $item['id'] ?>][is_enabled]" value="1" <?= $item['is_enabled'] ? 'checked' : '' ?>>
                        </td>
                        <td class="text-center">
                            <input type="checkbox" name="items[<?= $item['id'] ?>][requires_date_range]" value="1" <?= $item['requires_date_range'] ? 'checked' : '' ?>>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer-info" style="flex-shrink: 0;">
            <button type="submit" class="btn btn-primary">Зберегти меню</button>
        </div>
    </form>
</div>