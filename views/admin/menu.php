<div class="page-header">
    <div>
        <h1 class="page-title">Управління меню</h1>
        <p class="page-subtitle">Редагування назв та налаштувань пунктів меню</p>
    </div>
</div>

<div class="card card-stretch">
    <?php if (empty($menuItems)): ?>
    <div class="empty-state">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
        <p>Пунктів меню поки немає</p>
    </div>
    <?php else: ?>
    <div class="table-scroll">
        <form method="post" action="<?= $basePath ?>/adminMenu/save">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 50px">ID</th>
                        <th>Контролер</th>
                        <th>Назва</th>
                        <th class="text-center" style="width: 80px">Активний</th>
                        <th class="text-center" style="width: 100px">Потрібна дата</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($menuItems as $item): ?>
                    <tr>
                        <td><?= $item['id'] ?></td>
                        <td class="font-mono"><?= htmlspecialchars($item['controller'] ?? '—') ?></td>
                        <td><input type="text" name="items[<?= $item['id'] ?>][label]" value="<?= htmlspecialchars($item['label']) ?>" class="form-input" style="width: 250px;"></td>
                        <td class="text-center"><input type="checkbox" name="items[<?= $item['id'] ?>][is_enabled]" value="1" <?= $item['is_enabled'] ? 'checked' : '' ?>></td>
                        <td class="text-center"><input type="checkbox" name="items[<?= $item['id'] ?>][requires_date_range]" value="1" <?= $item['requires_date_range'] ? 'checked' : '' ?>></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="card-footer-info">
                <button type="submit" class="btn btn-primary">Зберегти меню</button>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>