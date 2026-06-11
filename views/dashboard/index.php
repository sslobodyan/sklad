<!-- Вітальна картка -->
<div class="welcome-card">
    <div class="welcome-text">
        <h1>Вітаємо, <?= htmlspecialchars($displayName ?: ($username ?: 'Гість')) ?>!</h1>
        <p>Система складського обліку 
		<?php if (!empty($_SESSION['nc_db_group'])): ?>
        <span class="db-name"><?= htmlspecialchars($_SESSION['nc_db_group']) ?></span>
        <?php endif; ?>
		</p>
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

        <button class="icon-btn" onclick="openChangePasswordModal()" title="Змінити пароль">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
        </button>

        <a href="<?= $basePath ?>/logout" class="logout-btn" title="Вихід">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
        </a>

</div>

<!-- Статистика -->
<div class="stats-grid">
    <a href="<?= $basePath ?>/warehouses" class="stat-card">
        <div class="stat-icon"><?= $warehouseIcon ?? '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>' ?></div>
        <div class="stat-value"><?= $stats['warehouses'] ?></div>
        <div class="stat-label">Складів</div>
    </a>
    <a href="<?= $basePath ?>/materials" class="stat-card">
        <div class="stat-icon"><?= $materialIcon ?? '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16.5 9.4l-9-5.19M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>' ?></div>
        <div class="stat-value"><?= $stats['materials'] ?></div>
        <div class="stat-label">Матеріалів</div>
    </a>
<!--
    <a href="<?= $basePath ?>/resourceTypes" class="stat-card">
        <div class="stat-icon"><?= $resourceTypeIcon ?? '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h7"/></svg>' ?></div>
        <div class="stat-value"><?= $stats['resource_types'] ?></div>
        <div class="stat-label">Типів ресурсів</div>
    </a>
-->
    <a href="<?= $basePath ?>/movements" class="stat-card">
        <div class="stat-icon"><?= $movementIcon ?? '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3l4 8 5-5 5 15H2L8 3z"/></svg>' ?></div>
        <div class="stat-value"><?= $stats['movements_today'] ?></div>
        <div class="stat-label">Переміщень сьогодні</div>
    </a>
    <a href="<?= $basePath ?>/resources" class="stat-card">
        <div class="stat-icon"><?= $resourceLogIcon ?? '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>' ?></div>
        <div class="stat-value"><?= $stats['resource_logs_today'] ?></div>
        <div class="stat-label">Списань сьогодні</div>
    </a>
</div>

<!-- Останні рухи -->
<h2><span class="table-last-head">Останні переміщення:</span></h2>
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

<!-- Модальне вікно зміни пароля -->
<div id="changePasswordModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Зміна пароля</h3>
            <button class="modal-close" onclick="closeChangePasswordModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="changePasswordForm" onsubmit="submitChangePassword(event)">
                <div class="form-group">
                    <label class="form-label">Поточний пароль</label>
                    <input type="password" id="current_password" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Новий пароль</label>
                    <input type="password" id="new_password" class="form-input" required>
                    <div class="form-hint">Мінімум 6 символів</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Підтвердження</label>
                    <input type="password" id="confirm_password" class="form-input" required>
                </div>
                <div id="passwordError" class="error-message" style="display: none; color: #c62828;"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeChangePasswordModal()">Скасувати</button>
                    <button type="submit" class="btn btn-primary">Змінити пароль</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background: rgba(255,255,255,0.15);
    border-radius: 50%;
    border: none;
    cursor: pointer;
    transition: background 0.2s;
}

.icon-btn:hover {
    background: rgba(255,255,255,0.3);
    text-decoration: none;
}

.icon-btn svg {
    stroke: white;
}

.modal {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    z-index: 1000;
    width: 400px;
    max-width: 90%;
}

.modal-content {
    width: 100%;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid #eee;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px solid #eee;
}

.error-message {
    margin-top: 12px;
    font-size: 13px;
}
</style>

<script>
function openChangePasswordModal() {
    document.getElementById('changePasswordModal').style.display = 'block';
    document.getElementById('current_password').value = '';
    document.getElementById('new_password').value = '';
    document.getElementById('confirm_password').value = '';
    document.getElementById('passwordError').style.display = 'none';
}

function closeChangePasswordModal() {
    document.getElementById('changePasswordModal').style.display = 'none';
}

function submitChangePassword(event) {
    event.preventDefault();
    
    const currentPassword = document.getElementById('current_password').value;
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const errorDiv = document.getElementById('passwordError');
    
    if (newPassword.length < 6) {
        errorDiv.textContent = 'Новий пароль повинен містити не менше 6 символів';
        errorDiv.style.display = 'block';
        return;
    }
    
    if (newPassword !== confirmPassword) {
        errorDiv.textContent = 'Новий пароль і підтвердження не співпадають';
        errorDiv.style.display = 'block';
        return;
    }
    
    fetch(window.basePath + '/dashboard/changePassword', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'current_password=' + encodeURIComponent(currentPassword) + 
              '&new_password=' + encodeURIComponent(newPassword) +
              '&confirm_password=' + encodeURIComponent(confirmPassword)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeChangePasswordModal();
        } else {
            errorDiv.textContent = data.error;
            errorDiv.style.display = 'block';
        }
    })
    .catch(error => {
        errorDiv.textContent = 'Помилка при зміні пароля';
        errorDiv.style.display = 'block';
    });
}

// Закриття модалки при кліку поза нею
document.getElementById('changePasswordModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeChangePasswordModal();
    }
});
</script>


</div>


