<!-- Вітальна картка -->
<div class="welcome-card">
    <div class="welcome-text">
        <h1>Вітаємо, <?= htmlspecialchars($displayName ?: ($username ?: 'Гість')) ?>!</h1>
        <p>Система складського обліку</p>
        <?php if ($username): ?>
        <div class="welcome-details">
            <span class="badge badge-secondary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                <?= htmlspecialchars($username) ?>
            </span>
            <?php if ($isAdmin): ?>
            <span class="badge badge-admin">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 1l3 5 6 1-4 4 1 6-6-3-6 3 1-6-4-4 6-1z"/>
                </svg>
                Адміністратор
            </span>
            <?php endif; ?>
            <?php if (!empty($groups)): ?>
            <span class="badge badge-info">
                Групи: <?= htmlspecialchars(implode(', ', $groups)) ?>
            </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php if ($lastLogin): ?>
    <div class="welcome-lastlogin">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <polyline points="12 6 12 12 16 14"/>
        </svg>
        Останній вхід: <?= date('d.m.Y H:i', strtotime($lastLogin)) ?>
    </div>
    <?php endif; ?>
</div>

<!-- Статистика -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><?= $warehouseIcon ?? '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>' ?></div>
        <div class="stat-value"><?= $stats['warehouses'] ?></div>
        <div class="stat-label">Складів</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><?= $materialIcon ?? '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16.5 9.4l-9-5.19M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>' ?></div>
        <div class="stat-value"><?= $stats['materials'] ?></div>
        <div class="stat-label">Матеріалів</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><?= $resourceTypeIcon ?? '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h7"/></svg>' ?></div>
        <div class="stat-value"><?= $stats['resource_types'] ?></div>
        <div class="stat-label">Типів ресурсів</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><?= $movementIcon ?? '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3l4 8 5-5 5 15H2L8 3z"/></svg>' ?></div>
        <div class="stat-value"><?= $stats['movements_today'] ?></div>
        <div class="stat-label">Переміщень сьогодні</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><?= $resourceLogIcon ?? '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>' ?></div>
        <div class="stat-value"><?= $stats['resource_logs_today'] ?></div>
        <div class="stat-label">Списань сьогодні</div>
    </div>
</div>

<!-- Останні рухи -->
<div class="card card-stretch">
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Дата</th>
                    <th>Зі складу</th>
                    <th>На склад</th>
                    <th>Матеріал</th>
                    <th class="text-right">Кількість</th>
                    <th>Примітка</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stats['last_movements'] as $movement): ?>
                <tr>
                    <td class="font-mono"><?= date('d.m.Y', strtotime($movement['movement_date'])) ?></td>
                    <td class="note-cell"><?= htmlspecialchars($movement['warehouse_from_name'] ?? '—') ?></td>
                    <td class="note-cell"><?= htmlspecialchars($movement['warehouse_to_name'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($movement['material_name']) ?></td>
                    <td class="text-right font-mono"><?= number_format((float)$movement['quantity'], 2, '.', ' ') ?></td>
                    <td class="note-cell"><?= htmlspecialchars($movement['note'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer-info">Показано: <?= count($stats['last_movements']) ?> записів</div>
</div>