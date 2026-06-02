<?php
/**
 * Сторінка привітання / Dashboard
 */
?>

<div class="dashboard">
    <!-- Flash повідомлення будуть тут -->
    
    <?php if (!$sessionValid): ?>
    <div class="alert alert-warning">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <div>
            <strong>Увага!</strong> Сесія не визначена. Можливо, ви зайшли без авторизації Nextcloud.<br>
            Деякі функції можуть бути недоступні. Зверніться до адміністратора.
        </div>
    </div>
    <?php endif; ?>
    
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
            <div class="stat-icon">🏭</div>
            <div class="stat-value"><?= $stats['warehouses'] ?></div>
            <div class="stat-label">Складів</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-value"><?= $stats['materials'] ?></div>
            <div class="stat-label">Матеріалів</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">⚡</div>
            <div class="stat-value"><?= $stats['resource_types'] ?></div>
            <div class="stat-label">Типів ресурсів</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📅</div>
            <div class="stat-value"><?= $stats['movements_today'] ?></div>
            <div class="stat-label">Рухів сьогодні</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-value"><?= $stats['resource_logs_today'] ?></div>
            <div class="stat-label">Списань ресурсів</div>
        </div>
    </div>
    
    <!-- Останні рухи -->
    <div class="recent-movements card">
        <div class="card-header">
            <h2>Останні записи</h2>
            <a href="<?= $basePath ?>/movements" class="link">Всі записи →</a>
        </div>
        
        <?php if (empty($stats['last_movements'])): ?>
        <div class="empty-state">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M3 6h18M9 4v2m6-2v2M5 10h14M6 14h12M7 18h10"/>
            </svg>
            <p>Ще немає жодного запису руху</p>
            <a href="<?= $basePath ?>/movements" class="btn btn-primary">Створити перший запис</a>
        </div>
        <?php else: ?>
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
                        <td><?= date('d.m.Y', strtotime($movement['movement_date'])) ?></td>
                        <td><?= htmlspecialchars($movement['warehouse_from_name'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($movement['warehouse_to_name'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($movement['material_name']) ?></td>
                        <td class="text-right font-mono"><?= number_format((float)$movement['quantity'], 2, '.', ' ') ?></td>
                        <td class="note-cell"><?= htmlspecialchars($movement['note'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>