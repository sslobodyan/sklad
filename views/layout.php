<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Складський облік') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/style.css">
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
        
        .dp-presets {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
            margin-bottom: 8px;
        }
        .dp-presets a {
            padding: 8px 4px;
            font-size: 11px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 4px;
            color: var(--text);
            text-decoration: none;
            text-align: center;
            transition: all 0.2s;
        }
        .dp-presets a:hover {
            background: var(--blue-light);
            border-color: var(--blue);
            color: var(--blue);
        }
    </style>
</head>
<body>
    <div class="flash-container" id="flashContainer"></div>
    
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
            .then(function() {
                closeDatePanel();
                location.reload();
            })
            .catch(function() {
                alert('Помилка збереження');
            });
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
                    <div class="nav-group">
                        <div class="nav-group-label">Довідники</div>
                        <a href="<?= $basePath ?>/warehouses" class="nav-item <?= $activePage === 'warehouses' ? 'active' : '' ?>">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                                <rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>
                            </svg>
                            <span>Склади</span>
                        </a>
                        <a href="<?= $basePath ?>/materials" class="nav-item <?= $activePage === 'materials' ? 'active' : '' ?>">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16.5 9.4l-9-5.19M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>
                            </svg>
                            <span>Матеріали</span>
                        </a>
                    </div>
                    <div class="nav-group">
                        <div class="nav-group-label">Документи</div>
                        <a href="<?= $basePath ?>/movements?date_from=<?= urlencode($globalDateFrom) ?>&date_to=<?= urlencode($globalDateTo) ?>" class="nav-item <?= $activePage === 'movements' ? 'active' : '' ?>">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M8 3l4 8 5-5 5 15H2L8 3z"/>
                            </svg>
                            <span>Рух матеріалів</span>
                        </a>
                        <a href="<?= $basePath ?>/resources?date_from=<?= urlencode($globalDateFrom) ?>&date_to=<?= urlencode($globalDateTo) ?>" class="nav-item <?= $activePage === 'resources' ? 'active' : '' ?>">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>
                            </svg>
                            <span>Витрата ресурсів</span>
                        </a>
                    </div>
                    <div class="nav-group">
                        <div class="nav-group-label">Ресурси</div>
                        <a href="<?= $basePath ?>/resources/rates" class="nav-item <?= $activePage === 'resource-rates' ? 'active' : '' ?>">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/>
                            </svg>
                            <span>Норми</span>
                        </a>
                        <a href="<?= $basePath ?>/resources/types" class="nav-item <?= $activePage === 'resource-types' ? 'active' : '' ?>">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 6h16M4 12h16M4 18h7"/>
                            </svg>
                            <span>Типи ресурсів</span>
                        </a>
                    </div>
                    <div class="nav-group">
                        <div class="nav-group-label">Звіти</div>
                        <a href="<?= $basePath ?>/reports/warehouse" class="nav-item <?= $activePage === 'report-warehouse' ? 'active' : '' ?>">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/>
                            </svg>
                            <span>Звіт по складу</span>
                        </a>
                        <a href="<?= $basePath ?>/reports/material" class="nav-item <?= $activePage === 'report-material' ? 'active' : '' ?>">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/>
                            </svg>
                            <span>Звіт по матеріалу</span>
                        </a>
                        <a href="<?= $basePath ?>/reports/resource" class="nav-item <?= $activePage === 'report-resource' ? 'active' : '' ?>">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>
                            </svg>
                            <span>Звіт по ресурсу</span>
                        </a>
                    </div>
                    <?php if (in_array('admin', $_SESSION['nc_groups'] ?? [])): ?>
                    <div class="nav-group">
                        <div class="nav-group-label">Система</div>
                        <a href="<?= $basePath ?>/settings/simple" class="nav-item <?= $activePage === 'settings-simple' ? 'active' : '' ?>">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="3"/><path d="M12 1v6m0 6v6m11-7h-6m-6 0H1"/>
                            </svg>
                            <span>Заправка</span>
                        </a>
                    </div>
                    <?php endif; ?>
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
                    <a href="<?= $basePath ?>/settings/preset/current-month">Поточний місяць</a>
                    <a href="<?= $basePath ?>/settings/preset/last-month">Минулий місяць</a>
                    <a href="<?= $basePath ?>/settings/preset/today">Сьогодні</a>
                    <a href="<?= $basePath ?>/settings/preset/current-year">Поточний рік</a>
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

	<!-- Core -->
	<script src="<?= $basePath ?>/assets/js/core/utils.js"></script>
	<script src="<?= $basePath ?>/assets/js/core/sidebar.js"></script>
	<script src="<?= $basePath ?>/assets/js/core/date-panel.js"></script>
	<script src="<?= $basePath ?>/assets/js/core/modal.js"></script>
	<script src="<?= $basePath ?>/assets/js/core/ajax.js"></script>

	<!-- Components -->
	<script src="<?= $basePath ?>/assets/js/components/autocomplete.js"></script>
	<script src="<?= $basePath ?>/assets/js/components/type-indicator.js"></script>

	<!-- Modals -->
	<script src="<?= $basePath ?>/assets/js/modals/warehouse.js"></script>
	<script src="<?= $basePath ?>/assets/js/modals/material.js"></script>
	<script src="<?= $basePath ?>/assets/js/modals/movement.js"></script>
	<script src="<?= $basePath ?>/assets/js/modals/delete.js"></script>
	<script src="<?= $basePath ?>/assets/js/modals/import.js"></script>

	<!-- Main -->
	<script src="<?= $basePath ?>/assets/js/main.js"></script>


    <script>
        (function() {
            var container = document.getElementById('flashContainer');
            var messages = window.flashMessages || [];
            
            function showFlash(message, type) {
                var icons = { success: '✓', error: '⚠', info: 'ℹ', warning: '⚡' };
                var div = document.createElement('div');
                div.className = 'flash-message ' + type;
                div.innerHTML = 
                    '<span class="flash-icon">' + (icons[type] || 'ℹ') + '</span>' +
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

