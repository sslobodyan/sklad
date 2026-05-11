<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Складський облік') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/style.css">
</head>
<body>
    <div class="app">
        <div class="app-body">
            <!-- Sidebar -->
            <aside class="sidebar" id="sidebar">
                <nav class="sidebar-nav">
                    <div class="nav-group">
                        <div class="nav-group-label">Довідники</div>
                        <a href="<?= $basePath ?>/warehouses" class="nav-item <?= $activePage === 'warehouses' ? 'active' : '' ?>">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 3h7v7H3z"/><path d="M14 3h7v7h-7z"/><path d="M3 14h7v7H3z"/><path d="M14 14h7v7h-7z"/>
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
                            <span>Норми списання</span>
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
                    </div>

<div class="nav-group">
    <div class="nav-group-label">Система</div>
    <a href="<?= BASE_PATH ?>/settings/simple" class="nav-item <?= $activePage === 'settings-simple' ? 'active' : '' ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="3"/>
            <path d="M12 1v6m0 6v6m11-7h-6m-6 0H1"/>
        </svg>
        Заправка
    </a>
</div>

    
                </nav>
            </aside>

            <!-- Sidebar toggle for mobile -->
            <button class="sidebar-toggle" onclick="toggleSidebar()" id="sidebarToggle">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <line x1="3" y1="12" x2="21" y2="12"/>
                    <line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>

            <!-- Main Content -->
            <main class="main">
                <?php if (!empty($flash)): ?>
                <div class="alert alert-<?= $flash['type'] ?>" id="flashAlert">
                    <?= htmlspecialchars($flash['message']) ?>
                    <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
                </div>
                <?php endif; ?>

                <?= $content ?>
            </main>
        </div>
    </div>

    <!-- Date Panel Dropdown -->
    <div class="date-panel" id="datePanel">
        <div class="date-panel-overlay" onclick="toggleDatePanel()"></div>
        <div class="date-panel-content" id="datePanelContent">

            <div class="dp-section">
                <div class="dp-label">Період для звітів</div>
                <form action="<?= $basePath ?>/settings/dates" method="POST">
                    <div class="dp-row">
                        <div class="dp-field">
                            <span class="dp-field-label">Від</span>
                            <input type="date" name="date_from" value="<?= $globalDateFrom ?>">
                        </div>
                        <div class="dp-field">
                            <span class="dp-field-label">До</span>
                            <input type="date" name="date_to" value="<?= $globalDateTo ?>">
                        </div>
                    </div>
                    <div class="dp-presets">
                        <a href="<?= $basePath ?>/settings/preset/current-month">Поточний місяць</a>
                        <a href="<?= $basePath ?>/settings/preset/last-month">Минулий місяць</a>
                        <a href="<?= $basePath ?>/settings/preset/current-year">Поточний рік</a>
                    </div>
                    <button type="submit" class="dp-btn">Застосувати</button>
                </form>
            </div>

            <div class="dp-divider"></div>

            <div class="dp-section">
                <div class="dp-label">Закритий період</div>
                <form action="<?= $basePath ?>/settings/closeperiod" method="POST">
                    <div class="dp-row">
                        <div class="dp-field" style="flex:1">
                            <span class="dp-field-label">Закрито по</span>
                            <input type="date" name="closed_date" value="<?= $closedDate ?? '' ?>">
                        </div>
                        <button type="submit" class="dp-btn" style="align-self:flex-end">
                            <?= $closedDate ? 'Оновити' : 'Закрити' ?>
                        </button>
                    </div>
                    <?php if ($closedDate): ?>
                    <div class="dp-closed-info">🔒 Рухи по <?= formatDateUa($closedDate) ?> заблоковані</div>
                    <button type="submit" class="dp-clear-btn" onclick="this.form.querySelector('input[name=closed_date]').value=''">
                        Зняти блокування
                    </button>
                    <?php endif; ?>
                </form>
            </div>

        </div>
    </div>

    <!-- Modal Container -->
    <div class="modal-backdrop" id="modalBackdrop" onclick="closeModal()"></div>
    <div class="modal" id="modal">
        <div class="modal-header">
            <div class="modal-title" id="modalTitle"></div>
            <button class="modal-close" onclick="closeModal()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="modal-body" id="modalBody"></div>
    </div>

    <script src="<?= $basePath ?>/assets/js/app.js"></script>
    <script>
        window.basePath = '<?= $basePath ?>';
        window.globalDateFrom = '<?= $globalDateFrom ?>';
        window.globalDateTo = '<?= $globalDateTo ?>';

        var flash = document.getElementById('flashAlert');
        if (flash) {
            setTimeout(function() {
                flash.style.opacity = '0';
                setTimeout(function() { flash.remove(); }, 300);
            }, 3000);
        }
    </script>
</body>
</html>
