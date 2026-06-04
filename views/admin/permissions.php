<div class="page-header">
    <div>
        <h1 class="page-title">Права доступу</h1>
        <p class="page-subtitle">Налаштування рівнів доступу до пунктів меню</p>
    </div>
    <div class="header-buttons">
        <a href="<?= $basePath ?>/adminUsers" class="btn btn-secondary">Користувачі</a>
    </div>
</div>

<div class="card filter-panel">
    <form method="get" action="<?= $basePath ?>/adminPermissions" class="filter-grid filter-grid-with-action">
        <div class="form-group">
            <label class="form-label">Користувач</label>
            <select name="user_id" class="form-select" onchange="this.form.submit()">
                <option value="">-- Виберіть користувача --</option>
                <?php foreach ($users as $user): ?>
                <option value="<?= $user['id'] ?>" <?= ($selectedUser && $selectedUser['id'] == $user['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($user['nc_user']) ?> (<?= $user['role'] ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<?php if ($selectedUser): ?>
<div class="card card-stretch">
    <div class="table-scroll">
        <form method="post" action="<?= $basePath ?>/adminPermissions/save">
            <input type="hidden" name="nc_user" value="<?= htmlspecialchars($selectedUser['nc_user']) ?>">
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Пункт меню</th>
                        <th class="text-center" style="width: 100px">Заборонено</th>
                        <th class="text-center" style="width: 100px">Перегляд</th>
                        <th class="text-center" style="width: 100px">Редагування</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groups as $group): ?>
                    <tr class="detail-summary-row">
                        <td colspan="4"><strong><?= htmlspecialchars($group['label']) ?></strong></td>
                    </tr>
                    <?php
                    $groupItems = array_filter($menuItems, function($item) use ($group) {
                        return $item['parent_id'] == $group['id'] && $item['controller'] !== null;
                    });
                    foreach ($groupItems as $item):
                        $currentLevel = $userPermissions[$item['id']] ?? 'none';
                    ?>
                    <tr>
                        <td style="padding-left: 24px;"><?= htmlspecialchars($item['label']) ?> (<?= htmlspecialchars($item['controller']) ?>)</td>
                        <td class="text-center">
                            <input type="radio" name="permissions[<?= $item['id'] ?>]" value="none" <?= $currentLevel === 'none' ? 'checked' : '' ?>>
                        </td>
                        <td class="text-center">
                            <input type="radio" name="permissions[<?= $item['id'] ?>]" value="view" <?= $currentLevel === 'view' ? 'checked' : '' ?>>
                        </td>
                        <td class="text-center">
                            <input type="radio" name="permissions[<?= $item['id'] ?>]" value="edit" <?= $currentLevel === 'edit' ? 'checked' : '' ?>>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="card-footer-info">
                <button type="submit" class="btn btn-primary">Зберегти права</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>