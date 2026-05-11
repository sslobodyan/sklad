<?php
/**
 * Налаштування "тупого складу" (заправка)
 */
?>

<div class="page-header">
    <div>
        <h1 class="page-title">⛽ Налаштування заправки</h1>
        <p class="page-subtitle">Спрощена сторінка для заправника</p>
    </div>
</div>

<div class="card" style="max-width: 600px;">
    <form method="post" action="<?= BASE_PATH ?>/settings/simplesave">
        
        <div class="form-group">
            <label for="simple_warehouse" class="form-label">Склад заправника</label>
            <p class="form-hint">Цей склад буде доступний на спрощеній сторінці заправки</p>
            <select name="simple_warehouse" id="simple_warehouse" class="form-input autocomplete" data-placeholder="Оберіть склад..." required>
                <option value="">— Оберіть склад —</option>
                <?php foreach ($warehouses as $w): ?>
                    <option value="<?= $w['id'] ?>" <?= $w['id'] == $currentWarehouse ? 'selected' : '' ?>>
                        <?= htmlspecialchars($w['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="simple_materials" class="form-label">Дозволені матеріали</label>
            <p class="form-hint">Оберіть матеріали, які будуть доступні заправнику. Якщо нічого не вибрано — доступні всі.</p>
            <select name="simple_materials[]" id="simple_materials" class="form-input" multiple size="8">
                <?php foreach ($materials as $m): ?>
                    <option value="<?= $m['id'] ?>" <?= in_array($m['id'], $currentMaterials) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="form-hint" style="margin-top: 8px;">
                💡 Утримуйте Ctrl (Cmd на Mac) для вибору кількох матеріалів
            </p>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Зберегти налаштування</button>
        </div>
        
    </form>
</div>

<?php if ($currentWarehouse): ?>
<div class="card" style="max-width: 600px; margin-top: 16px;">
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
.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    font-weight: 600;
    font-size: 14px;
    color: var(--text);
    margin-bottom: 6px;
}

.form-hint {
    font-size: 12px;
    color: var(--text-secondary);
    margin-bottom: 8px;
    margin-top: -4px;
}

.form-input {
    width: 100%;
    padding: 10px 12px;
    font-size: 14px;
    border: 1px solid var(--border);
    border-radius: 6px;
    background: white;
    color: var(--text);
}

.form-input:focus {
    outline: none;
    border-color: var(--blue);
    box-shadow: 0 0 0 3px rgba(0, 130, 201, 0.1);
}

.form-input[multiple] {
    height: auto;
    cursor: pointer;
}

.form-actions {
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px solid var(--border);
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
</script>
