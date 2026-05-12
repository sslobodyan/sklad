<?php
/**
 * Налаштування "тупого складу" (заправка)
 */
$selectedMaterialIds = $currentMaterials ?? [];
?>

<div class="page-header">
    <div>
        <h1 class="page-title">⛽ Налаштування заправки</h1>
        <p class="page-subtitle">Спрощена сторінка для заправника</p>
    </div>
</div>

<!-- Фільтри -->
<div class="card filter-panel">
    <form method="post" action="<?= BASE_PATH ?>/settings/simplesave" class="filter-grid filter-grid-with-action">
        
        <div class="form-group" style="margin-bottom:0">
            <label class="form-label">Склад заправника</label>
            <select name="simple_warehouse" class="autocomplete" data-placeholder="Оберіть склад..." required>
                <option value="">— Оберіть склад —</option>
                <?php foreach ($warehouses as $w): ?>
                    <option value="<?= $w['id'] ?>" <?= $w['id'] == $currentWarehouse ? 'selected' : '' ?>>
                        <?= htmlspecialchars($w['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group" style="margin-bottom:0">
            <label class="form-label">Матеріали
                <?php if (!empty($selectedMaterialIds)): ?>
                <span style="font-weight:400;color:var(--blue)">(<?= count($selectedMaterialIds) ?>)</span>
                <?php endif; ?>
            </label>
            <button type="button" class="wh-filter-btn" onclick="openMaterialFilter()">
                <span><?= empty($selectedMaterialIds) ? 'Усі матеріали' : count($selectedMaterialIds) . ' обрано' ?></span>
                <svg width="10" height="10" viewBox="0 0 10 10"><path fill="currentColor" d="M5 7L1 3h8z"/></svg>
            </button>
            <input type="hidden" name="simple_materials" id="materialFilterValue" value="<?= htmlspecialchars(implode(',', $selectedMaterialIds)) ?>">
        </div>
        
        <div class="form-group filter-action-inline" style="margin-bottom:0">
            <label class="form-label">&nbsp;</label>
            <div class="filter-action-stack">
                <button type="submit" class="btn btn-primary">💾 Зберегти</button>
                <?php if (!empty($selectedMaterialIds)): ?>
                <a href="<?= BASE_PATH ?>/settings/simple" class="btn btn-secondary btn-sm">Скинути</a>
                <?php endif; ?>
            </div>
        </div>
        
    </form>
</div>

<?php if ($currentWarehouse): ?>
<div class="card" style="max-width: 600px; margin-top: 16px;padding: 20px;">
    <h3 style="margin-bottom: 12px;">🔗 Посилання для заправника</h3>
    <p style="margin-bottom: 12px; color: var(--text-secondary);">
        Надішліть це посилання заправнику. Він зможе працювати тільки з цією спрощеною сторінкою.
    </p>
    <div class="url-box">
        <?php
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $url = $protocol . '://' . $host . BASE_PATH . '/simple';
        ?>
        <code><?= htmlspecialchars($url) ?></code>
        <button type="button" class="btn btn-sm" onclick="copyUrl()" title="Копіювати">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>
            </svg>
        </button>
    </div>
</div>
<?php endif; ?>

<style>
.filter-panel {
    padding: 16px;
    margin-bottom: 16px;
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
    align-items: end;
}

.filter-grid-with-action {
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) auto;
}

.form-group {
    margin-bottom: 0;
}

.form-label {
    display: block;
    font-weight: 600;
    font-size: 13px;
    color: var(--text);
    margin-bottom: 6px;
}

.wh-filter-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 10px 12px;
    font-size: 14px;
    border: 1px solid var(--border);
    border-radius: 6px;
    background: white;
    color: var(--text);
    cursor: pointer;
    transition: all 0.15s;
}

.wh-filter-btn:hover {
    border-color: var(--blue);
    background: var(--blue-light);
}

.wh-filter-btn span {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.filter-action-inline {
    display: flex;
    flex-direction: column;
}

.filter-action-stack {
    display: flex;
    gap: 8px;
    align-items: flex-end;
    height: 42px;
}

.url-box {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 6px;
}

.url-box code {
    flex: 1;
    font-family: 'Monaco', 'Consolas', monospace;
    font-size: 12px;
    color: var(--text);
    word-break: break-all;
}

.btn-sm {
    padding: 6px 10px;
    font-size: 13px;
}
</style>

<script>
// Відкрити фільтр матеріалів
function openMaterialFilter() {
    var materials = <?= json_encode($materials) ?>;
    var selected = <?= json_encode($selectedMaterialIds) ?>;
    
    var html = '<div class=\"material-filter-modal\">';
    html += '<div class=\"mf-header\">';
    html += '<input type=\"text\" class=\"mf-search\" placeholder=\"Пошук матеріалів...\" oninput=\"filterMaterials(this.value)\">';
    html += '<div class=\"mf-actions\">';
    html += '<button type=\"button\" class=\"btn btn-sm\" onclick=\"selectAllMaterials()\">Обрати все</button>';
    html += '<button type=\"button\" class=\"btn btn-sm btn-secondary\" onclick=\"deselectAllMaterials()\">Зняти все</button>';
    html += '</div>';
    html += '</div>';
    html += '<div class=\"mf-list\" id=\"materialFilterList\">';
    
    materials.forEach(function(m) {
        var isChecked = selected.indexOf(m.id) !== -1 ? 'checked' : '';
        html += '<label class=\"mf-item\">';
        html += '<input type=\"checkbox\" value=\"' + m.id + '\" ' + isChecked + ' onchange=\"updateMaterialCount()\">';
        html += '<span>' + escapeHtml(m.name) + '</span>';
        html += '</label>';
    });
    
    html += '</div>';
    html += '<div class=\"mf-footer\">';
    html += '<button type=\"button\" class=\"btn btn-primary\" onclick=\"applyMaterialFilter()\">Застосувати</button>';
    html += '</div>';
    html += '</div>';
    
    openModal('Дозволені матеріали', html);
    
    // Підрахувати кількість
    updateMaterialCount();
}

// Фільтрація списку
function filterMaterials(query) {
    var q = query.toLowerCase();
    var items = document.querySelectorAll('.mf-item');
    items.forEach(function(item) {
        var text = item.querySelector('span').textContent.toLowerCase();
        item.style.display = text.indexOf(q) !== -1 ? '' : 'none';
    });
}

// Обрати все
function selectAllMaterials() {
    document.querySelectorAll('.mf-item input[type="checkbox"]').forEach(function(cb) {
        if (cb.closest('.mf-item').style.display !== 'none') {
            cb.checked = true;
        }
    });
    updateMaterialCount();
}

// Зняти все
function deselectAllMaterials() {
    document.querySelectorAll('.mf-item input[type="checkbox"]').forEach(function(cb) {
        cb.checked = false;
    });
    updateMaterialCount();
}

// Оновити лічильник
function updateMaterialCount() {
    var count = document.querySelectorAll('.mf-item input[type="checkbox"]:checked').length;
    var footer = document.querySelector('.mf-footer');
    if (footer) {
        footer.dataset.count = count;
    }
}

// Застосувати фільтр
function applyMaterialFilter() {
    var selected = [];
    document.querySelectorAll('.mf-item input[type="checkbox"]:checked').forEach(function(cb) {
        selected.push(cb.value);
    });
    
    document.getElementById('materialFilterValue').value = selected.join(',');
    
    // Оновити текст кнопки
    var btn = document.querySelector('.wh-filter-btn span');
    if (btn) {
        btn.textContent = selected.length === 0 ? 'Усі матеріали' : selected.length + ' обрано';
    }
    
    // Оновити badge
    var badge = document.querySelector('.form-label span[style]');
    if (selected.length > 0) {
        if (!badge) {
            var label = document.querySelector('.wh-filter-btn').closest('.form-group').querySelector('.form-label');
            badge = document.createElement('span');
            badge.style.cssText = 'font-weight:400;color:var(--blue)';
            label.appendChild(badge);
        }
        badge.textContent = '(' + selected.length + ')';
    } else if (badge) {
        badge.remove();
    }
    
    closeModal();
}

// Копіювати URL
function copyUrl() {
    var code = document.querySelector('.url-box code');
    var text = code.textContent;
    
    navigator.clipboard.writeText(text).then(function() {
        var btn = document.querySelector('.url-box .btn');
        var originalHtml = btn.innerHTML;
        btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2e7d32" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>';
        setTimeout(function() {
            btn.innerHTML = originalHtml;
        }, 2000);
    }).catch(function() {
        alert('Не вдалося скопіювати. Скопіюйте вручну.');
    });
}

function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<style>
.material-filter-modal {
    display: flex;
    flex-direction: column;
    height: 400px;
}

.mf-header {
    padding: 12px 16px;
    border-bottom: 1px solid var(--border);
    background: #f8fafc;
}

.mf-search {
    width: 100%;
    padding: 8px 12px;
    font-size: 14px;
    border: 1px solid var(--border);
    border-radius: 6px;
    margin-bottom: 10px;
}

.mf-actions {
    display: flex;
    gap: 8px;
}

.mf-list {
    flex: 1;
    overflow-y: auto;
    padding: 8px;
}

.mf-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    cursor: pointer;
    border-radius: 4px;
    transition: background 0.15s;
}

.mf-item:hover {
    background: var(--bg);
}

.mf-item input[type="checkbox"] {
    width: 16px;
    height: 16px;
    cursor: pointer;
    flex-shrink: 0;
}

.mf-item span {
    font-size: 14px;
    line-height: 1.3;
}

.mf-footer {
    padding: 12px 16px;
    border-top: 1px solid var(--border);
    background: #f8fafc;
    display: flex;
    justify-content: flex-end;
}
</style>
