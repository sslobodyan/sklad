<div class="page-header">
    <div>
        <h1 class="page-title">Користувачі системи</h1>
        <p class="page-subtitle">Управління ролями та обмеженнями</p>
    </div>
    <div class="header-buttons">
        <a href="<?= $basePath ?>/admin/users/edit" class="btn btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Додати
        </a>
    </div>
</div>

<div class="card card-stretch">
    <?php if (empty($users)): ?>
    <div class="empty-state">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
        </svg>
        <p>Користувачів поки немає</p>
        <a href="<?= $basePath ?>/admin/users/edit" class="btn btn-primary btn-sm">Додати першого користувача</a>
    </div>
    <?php else: ?>
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Логін</th>
                    <th>Роль</th>
                    <th>Дозволені склади</th>
                    <th>Дозволені матеріали</th>
                    <th class="text-center">Експорт</th>
                    <th class="text-right">Дії</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td class="font-mono"><?= htmlspecialchars($user['nc_user']) ?></td>
                    <td>
                        <?php
                        $roleLabel = '';
                        switch ($user['role']) {
                            case 'manager': $roleLabel = 'Менеджер'; break;
                            case 'viewer': $roleLabel = 'Спостерігач'; break;
                            case 'fuel': $roleLabel = 'Заправник'; break;
                            default: $roleLabel = $user['role'];
                        }
                        ?>
                        <span class="badge"><?= $roleLabel ?></span>
                    </td>
                    <td class="text-muted"><?= $user['allowed_warehouses'] === null ? 'Всі' : (empty($user['allowed_warehouses']) ? '—' : 'ID: ' . implode(', ', $user['allowed_warehouses'])) ?></td>
                    <td class="text-muted"><?= $user['allowed_materials'] === null ? 'Всі' : (empty($user['allowed_materials']) ? '—' : 'ID: ' . implode(', ', $user['allowed_materials'])) ?></td>
                    <td class="text-center"><?= $user['can_export'] ? '✓' : '—' ?></td>
                    <td class="text-right actions no-print-col">
                        <a href="<?= $basePath ?>/admin/permissions?user_id=<?= $user['id'] ?>" class="btn-icon" title="Права доступу">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 15v2m-6 4h12a2 2 0 002-2v-8a2 2 0 00-2-2H6a2 2 0 00-2 2v8a2 2 0 002 2zm10-10V6a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </a>
                        <a href="<?= $basePath ?>/admin/users/edit?id=<?= $user['id'] ?>" class="btn-icon" title="Редагувати">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                        </a>
                        <a href="<?= $basePath ?>/admin/users/delete?id=<?= $user['id'] ?>" class="btn-icon btn-icon-danger" title="Видалити" onclick="return confirm('Видалити користувача?')">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 6h18"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                            </svg>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="card-footer-info">Показано: <?= count($users) ?> користувачів</div>
    </div>
    <?php endif; ?>
</div>