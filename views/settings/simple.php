<?php
/**
 * Налаштування "тупого складу" (заправка)
 */
?>

<div class="page-header">
    <h1>⛽ Налаштування заправки</h1>
</div>

<div class="card" style="max-width: 600px;">
    <form method="post" action="<?= BASE_PATH ?>/settings/simplesave">
        
        <div class="form-group">
            <label for="simple_warehouse">Склад заправника</label>
            <p class="form-hint">Цей склад буде доступний на спрощеній сторінці заправки</p>
            <select name="simple_warehouse" id="simple_warehouse" class="form-control">
                <option value="">— Не вибрано —</option>
                <?php foreach ($warehouses as $w): ?>
                    <option value="<?= $w['id'] ?>" <?= $w['id'] == $currentWarehouse ? 'selected' : '' ?>>
                        <?= htmlspecialchars($w['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Дозволені матеріали</label>
            <p class="form-hint">Якщо нічого не вибрано — доступні всі матеріали</p>
            <div class="checkbox-list">
                <?php foreach ($materials as $m): ?>
                    <label class="checkbox-item">
                        <input type="checkbox" 
                               name="simple_materials[]" 
                               value="<?= $m['id'] ?>"
                               <?= in_array($m['id'], $currentMaterials) ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars($m['name']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-group" style="margin-top: 24px;">
            <button type="submit" class="btn btn-primary">💾 Зберегти</button>
        </div>
        
    </form>
</div>

<?php if ($currentWarehouse): ?>
<div class="card" style="max-width: 600px; margin-top: 16px;">
    <h3 style="margin-bottom: 12px;">🔗 Посилання для заправника</h3>
    <p style="margin-bottom: 12px; color: var(--text-secondary);">
        Надішліть це посилання заправнику. Він зможе працювати тільки з цією спрощеною сторінкою.
    </p>
    <div style="background: var(--bg); padding: 12px; border-radius: 6px; word-break: break-all; font-family: monospace; font-size: 13px;">
        <?php
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $url = $protocol . '://' . $host . BASE_PATH . '/simple';
        ?>
        <a href="<?= $url ?>" target="_blank"><?= htmlspecialchars($url) ?></a>
    </div>
</div>
<?php endif; ?>

<style>
.form-hint {
    font-size: 12px;
    color: var(--text-secondary);
    margin-bottom: 8px;
}

.checkbox-list {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: 8px;
    background: var(--bg);
}

.checkbox-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px;
    cursor: pointer;
    border-radius: 4px;
    font-weight: normal;
}

.checkbox-item:hover {
    background: white;
}

.checkbox-item input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}
</style>
