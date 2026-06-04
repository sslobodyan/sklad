<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Складський облік') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/main.css">
    <style>
        .flash-container {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 9999;
            pointer-events: none;
        }
        .flash-message {
            max-width: 500px;
            margin: 12px auto;
            padding: 14px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            opacity: 0;
            transform: translateY(-20px);
            transition: opacity 0.3s ease, transform 0.3s ease;
            pointer-events: auto;
        }
        .flash-message.visible { opacity: 1; transform: translateY(0); }
        .flash-message.hiding { opacity: 0; transform: translateY(-20px); }
        .flash-message.success { background: #2e7d32; color: white; }
        .flash-message.error { background: #c62828; color: white; }
        .flash-message.info { background: #1565c0; color: white; }
        .flash-message.warning { background: #f57c00; color: white; }
        .flash-message .flash-icon { font-size: 18px; flex-shrink: 0; }
        .flash-message .flash-text { flex: 1; }
        .flash-message .flash-close {
            background: none; border: none; color: inherit;
            cursor: pointer; padding: 4px; font-size: 18px; opacity: 0.8;
        }
        .flash-message .flash-close:hover { opacity: 1; }
    </style>
</head>
<body>
    <div class="flash-container" id="flashContainer"></div>

    <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">☰</button>

    <script>
        window.basePath = <?= json_encode($basePath) ?>;
        window.flashMessages = <?= json_encode($flashMessages ?? []) ?>;
        window.isAdmin = <?= json_encode(in_array('admin', $_SESSION['nc_groups'] ?? [])) ?>;

        window.applyDateRange = function() {
            var dateFrom = document.getElementById('dateFrom').value;
            var dateTo = document.getElementById('dateTo').value;
            if (!dateFrom || !dateTo) {
                alert('Оберіть обидві дати');
                return;
            }
            fetch(window.basePath + '/settings/dates', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'date_from=' + encodeURIComponent(dateFrom) + '&date_to=' + encodeURIComponent(dateTo)
            })
            .then(function() { closeDatePanel(); location.reload(); })
            .catch(function() { alert('Помилка збереження'); });
        };

        function closeDatePanel() {
            var panel = document.getElementById('datePanel');
            if (panel) panel.classList.remove('open');
        }
    </script>

    <div class="app">
        <div class="app-body">
            <aside class="sidebar" id="sidebar">
                <nav class="sidebar-nav">
                    <?php
                    $database = $db ?? null;
                    $menuItems = [];
                    
                    if ($database) {
                        try {
                            $menuModel = new MenuModel($database);
                            $menuItems = $menuModel->getUserMenu(NC_USER);
                        } catch (Exception $e) {
                            error_log('MenuModel error: ' . $e->getMessage());
                        }
                    }
                    
                    foreach ($menuItems as $group):
                        if (empty($group['items'])) continue;
                    ?>
                        <div class="nav-group">
                            <div class="nav-group-label"><?= htmlspecialchars($group['label']) ?></div>
                            <?php foreach ($group['items'] as $item): ?>
                                <?php
                                // Формуємо URL
                                if (!empty($item['url'])) {
                                    $url = $item['url'];
                                } else {
                                    $url = BASE_PATH . '/' . $item['controller'];
                                }
                                
                                if ($item['requires_date_range']) {
                                    $params = [];
                                    if (!empty($globalDateFrom)) $params['date_from'] = $globalDateFrom;
                                    if (!empty($globalDateTo)) $params['date_to'] = $globalDateTo;
                                    if (!empty($params)) {
                                        $url .= '?' . http_build_query($params);
                                    }
                                }
                                
                                $active = ($activePage === $item['controller']) ? 'active' : '';
                                ?>
                                <a href="<?= htmlspecialchars($url) ?>" class="nav-item <?= $active ?>">
                                    <?php if (!empty($item['icon_svg'])): ?>
                                        <?= $item['icon_svg'] ?>
                                    <?php else: ?>
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"/>
                                        </svg>
                                    <?php endif; ?>
                                    <span><?= htmlspecialchars($item['label']) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </nav>
            </aside>

            <main class="main">
                <?= $content ?>
            </main>
        </div>
    </div>

    <div class="date-panel" id="datePanel">
        <div class="date-panel-overlay" onclick="closeDatePanel()"></div>
        <div class="date-panel-content" id="datePanelContent">
            <div class="dp-section">
                <div class="dp-label">Період для звітів</div>
                <div class="dp-row">
                    <div class="dp-field">
                        <label class="dp-field-label">Від</label>
                        <input type="date" id="dateFrom" value="<?= htmlspecialchars($globalDateFrom) ?>">
                    </div>
                    <div class="dp-field">
                        <label class="dp-field-label">До</label>
                        <input type="date" id="dateTo" value="<?= htmlspecialchars($globalDateTo) ?>">
                    </div>
                </div>

                <div class="dp-presets">
                    <a href="<?= $basePath ?>/settings/preset/current-month" class="dp-preset-btn">Поточний місяць</a>
                    <a href="<?= $basePath ?>/settings/preset/last-month" class="dp-preset-btn">Минулий місяць</a>
                    <a href="<?= $basePath ?>/settings/preset/today" class="dp-preset-btn">Сьогодні</a>
                    <a href="<?= $basePath ?>/settings/preset/current-year" class="dp-preset-btn">Поточний рік</a>
                </div>

                <button class="dp-btn" onclick="applyDateRange()">Застосувати</button>
            </div>
            <div class="dp-divider"></div>
            <div class="dp-section">
                <div class="dp-label">Закритий період</div>
                <form method="post" action="<?= $basePath ?>/settings/closeperiod">
                    <div class="dp-row">
                        <div class="dp-field">
                            <label class="dp-field-label">Закрито по</label>
                            <input type="date" name="closed_date" value="<?= htmlspecialchars($closedDate ?? '') ?>">
                        </div>
                    </div>
                    <?php if (!empty($closedDate)): ?>
                    <div class="dp-closed-info">🔒 Рухи по <?= date('d.m.Y', strtotime($closedDate)) ?> заблоковані</div>
                    <?php endif; ?>
                    <button type="submit" class="dp-btn">Зберегти</button>
                </form>
            </div>
        </div>
    </div>

    <div class="modal-backdrop" id="modalBackdrop" onclick="closeModal()"></div>
    <div class="modal" id="modal">
        <div class="modal-header">
            <h3 id="modalTitle"></h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body" id="modalBody"></div>
    </div>

    <script src="<?= $basePath ?>/assets/js/core/utils.js"></script>
    <script src="<?= $basePath ?>/assets/js/core/sidebar.js"></script>
    <script src="<?= $basePath ?>/assets/js/core/date-panel.js"></script>
    <script src="<?= $basePath ?>/assets/js/core/modal.js"></script>
    <script src="<?= $basePath ?>/assets/js/core/ajax.js"></script>
    <script src="<?= $basePath ?>/assets/js/components/autocomplete.js"></script>
    <script src="<?= $basePath ?>/assets/js/components/type-indicator.js"></script>
    <script src="<?= $basePath ?>/assets/js/modals/warehouse.js"></script>
    <script src="<?= $basePath ?>/assets/js/modals/material.js"></script>
    <script src="<?= $basePath ?>/assets/js/modals/movement.js"></script>
    <script src="<?= $basePath ?>/assets/js/modals/delete.js"></script>
    <script src="<?= $basePath ?>/assets/js/modals/import.js"></script>
    <script src="<?= $basePath ?>/assets/js/main.js"></script>

    <script>
        (function() {
            var container = document.getElementById('flashContainer');
            var messages = window.flashMessages || [];
            function showFlash(message, type) {
                var icons = { success: '✓', error: '⚠', info: 'ℹ', warning: '⚡' };
                var div = document.createElement('div');
                div.className = 'flash-message ' + type;
                div.innerHTML = '<span class="flash-icon">' + (icons[type] || 'ℹ') + '</span>' +
                    '<span class="flash-text">' + escapeHtml(message) + '</span>' +
                    '<button class="flash-close" onclick="this.parentElement.remove()">&times;</button>';
                container.appendChild(div);
                requestAnimationFrame(function() { div.classList.add('visible'); });
                setTimeout(function() {
                    div.classList.add('hiding');
                    setTimeout(function() { div.remove(); }, 300);
                }, 4000);
            }
            function escapeHtml(text) {
                var div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
            messages.forEach(function(msg) { showFlash(msg.message, msg.type); });
        })();
    </script>
</body>
</html>