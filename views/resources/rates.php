<div class="page-header">
    <div>
        <h1 class="page-title">Норми списання</h1>
        <p class="page-subtitle">Прив'язка ресурсів до складів та норми витрати матеріалів</p>
    </div>
</div>

<div class="card filter-panel">
    <form method="GET" class="filter-grid filter-grid-with-action">
        <div class="form-group">
            <label class="form-label">Склад</label>
            <select name="warehouse_id" class="autocomplete" data-placeholder="— Оберіть склад —" data-submit-on-change>
                <option value="">— Оберіть склад —</option>
                <?php foreach ($warehouses as $w): ?>
                <option value="<?= $w['id'] ?>" <?= $warehouseId == $w['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($w['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($warehouseId && !empty($warehouseResources)): ?>
        <div class="form-group">
            <label class="form-label">Ресурс</label>
            <select name="resource_type_id" class="form-input form-select" onchange="this.form.submit()">
                <option value="">— Оберіть ресурс —</option>
                <?php foreach ($warehouseResources as $wr): ?>
                <option value="<?= $wr['resource_type_id'] ?>" <?= $resourceTypeId == $wr['resource_type_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($wr['type_name']) ?> (<?= htmlspecialchars($wr['unit']) ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
    </form>
</div>

<?php if (!$warehouseId): ?>
<div class="card">
    <div class="empty-state">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
            <line x1="3" y1="9" x2="21" y2="9"/>
            <line x1="9" y1="21" x2="9" y2="9"/>
        </svg>
        <p>Оберіть склад</p>
    </div>
</div>

<?php elseif ($selectedWarehouse): ?>

<!-- Прив'язані ресурси -->
<div class="card resources-card">
    <div class="resources-header">
        <strong>Ресурси складу «<?= htmlspecialchars($selectedWarehouse['name']) ?>»</strong>
        <form method="POST" action="<?= $basePath ?>/resources/addresource" class="add-resource-form">
            <input type="hidden" name="warehouse_id" value="<?= $warehouseId ?>">
            <select name="resource_type_id" class="form-input form-select" required>
                <option value="">+ Додати ресурс</option>
                <?php foreach ($types as $t): ?>
                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?> (<?= htmlspecialchars($t['unit']) ?>)</option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary btn-sm">Додати</button>
        </form>
    </div>
    <div class="resources-list">
        <?php if (empty($warehouseResources)): ?>
        <div class="resources-empty">Ресурсів не прив'язано</div>
        <?php else: ?>
        <?php foreach ($warehouseResources as $wr): ?>
        <span class="resource-tag">
            <?= htmlspecialchars($wr['type_name']) ?> (<?= htmlspecialchars($wr['unit']) ?>)
            <form method="POST" action="<?= $basePath ?>/resources/removeresource" class="resource-tag-form">
                <input type="hidden" name="warehouse_id" value="<?= $warehouseId ?>">
                <input type="hidden" name="resource_type_id" value="<?= $wr['resource_type_id'] ?>">
                <button type="submit" class="resource-tag-remove" title="Видалити" onclick="return confirm('Видалити ресурс?')">&times;</button>
            </form>
        </span>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Норми для обраного ресурсу -->
<?php if ($resourceTypeId): ?>
<?php
    $selectedType = null;
    foreach ($types as $t) { if ($t['id'] == $resourceTypeId) { $selectedType = $t; break; } }
?>
<div class="card card-stretch">
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Матеріал</th>
                    <th class="col-rate">Норма на 1 <?= htmlspecialchars($selectedType['unit'] ?? '') ?></th>
                    <th>Віднести на склад</th>
                    <th class="col-spread">По днях</th>
                    <th class="col-actions">
                        <button class="btn btn-primary btn-sm table-header-add" onclick="openRateModal()">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                            Додати
                        </button>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rates)): ?>
                <tr><td colspan="5" class="text-muted empty-table">Норм поки немає</td></tr>
                <?php endif; ?>
                <?php foreach ($rates as $r): ?>
                <tr class="row-editable" ondblclick="openRateModal(<?= $r['id'] ?>, <?= $r['material_id'] ?>, <?= $r['rate'] ?>, <?= $r['source_warehouse_id'] ?: 0 ?>, <?= !empty($r['spread_by_day']) ? 1 : 0 ?>)">
                    <td class="font-medium"><?= htmlspecialchars($r['material_name']) ?></td>
                    <td class="font-mono text-right"><?= $r['rate'] ?></td>
                    <td><?= $r['source_warehouse_name'] ? htmlspecialchars($r['source_warehouse_name']) : '<span class="text-muted">нікуди (списання)</span>' ?></td>
                    <td class="text-center"><?= !empty($r['spread_by_day']) ? '✓' : '' ?></td>
                    <td class="col-actions">
                        <div class="actions">
                            <button class="btn-icon" title="Редагувати"
                                    onclick="openRateModal(<?= $r['id'] ?>, <?= $r['material_id'] ?>, <?= $r['rate'] ?>, <?= $r['source_warehouse_id'] ?: 0 ?>, <?= !empty($r['spread_by_day']) ? 1 : 0 ?>)">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </button>
                            <button class="btn-icon btn-icon-danger" title="Видалити"
                                    onclick="confirmDelete('<?= $basePath ?>/resources/deleterate/<?= $r['id'] ?>')">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 6h18"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
var rateWarehouseId = <?= $warehouseId ?>;
var rateResourceTypeId = <?= $resourceTypeId ?>;
var rateMaterials = <?= json_encode($materials, JSON_UNESCAPED_UNICODE) ?>;
var rateWarehouses = <?= json_encode($warehouses, JSON_UNESCAPED_UNICODE) ?>;

function openRateModal(id, materialId, rate, sourceWhId, spreadByDay) {
    var isEdit = !!id;
    var title = isEdit ? 'Редагувати норму' : 'Нова норма списання';

    var matOpts = '<option value="">— Оберіть —</option>';
    rateMaterials.forEach(function(m) {
        matOpts += '<option value="' + m.id + '"' + (m.id == materialId ? ' selected' : '') + '>' + escapeHtml(m.name) + '</option>';
    });

    var whOpts = '<option value="">Нікуди (списання)</option>';
    rateWarehouses.forEach(function(w) {
        whOpts += '<option value="' + w.id + '"' + (w.id == sourceWhId ? ' selected' : '') + '>' + escapeHtml(w.name) + '</option>';
    });

    var content =
        '<form action="' + basePath + '/resources/saverate" method="POST" onsubmit="submitForm(this); return false;">' +
            '<input type="hidden" name="warehouse_id" value="' + rateWarehouseId + '">' +
            '<input type="hidden" name="resource_type_id" value="' + rateResourceTypeId + '">' +
            '<div class="form-group">' +
                '<label class="form-label">Матеріал <span class="required">*</span></label>' +
                '<select name="material_id" class="autocomplete" data-placeholder="— Оберіть —" required>' + matOpts + '</select>' +
            '</div>' +
            '<div class="form-group">' +
                '<label class="form-label">Норма на 1 <?= htmlspecialchars($selectedType['unit'] ?? '') ?> <span class="required">*</span></label>' +
                '<input type="number" name="rate" class="form-input" step="0.000001" min="0.000001" required value="' + (rate || '') + '" placeholder="0.35">' +
            '</div>' +
            '<div class="form-group">' +
                '<label class="form-label">Віднести на склад</label>' +
                '<select name="source_warehouse_id" class="autocomplete" data-placeholder="Нікуди (списання)">' + whOpts + '</select>' +
                '<div class="form-hint">Порожньо = просто списання, інакше — переміщення</div>' +
            '</div>' +
            '<label class="checkbox-label spread-checkbox">' +
                '<input type="checkbox" name="spread_by_day" value="1"' + (spreadByDay ? ' checked' : '') + '>' +
                '<span>Рознести по днях (витрата рівномірно по кожному дню)</span>' +
            '</label>' +
            '<div class="modal-footer modal-meta" id="rateMetaFooter">' +
                '<div class="modal-meta-info"></div>' +
                '<div class="modal-meta-buttons">' +
                    '<button type="button" class="btn btn-secondary" onclick="closeModal()">Скасувати</button>' +
                    '<button type="submit" class="btn btn-primary">' + (isEdit ? 'Зберегти' : 'Додати') + '</button>' +
                '</div>' +
            '</div>' +
        '</form>';

    openModal(title, content);

    if (isEdit) {
        fetch(basePath + '/resources/getrate/' + id)
            .then(function(r) { return r.json(); })
            .then(function(result) {
                if (result.success && result.data) {
                    var metaHtml = '<div class="modal-meta-line modal-meta-author">';
                    if (result.data.author) {
                        metaHtml += escapeHtml(result.data.author);
                    }
                    metaHtml += '</div><div class="modal-meta-line modal-meta-date">';
                    if (result.data.updated_at && result.data.updated_at !== result.data.created_at) {
                        var updated = new Date(result.data.updated_at);
                        metaHtml += updated.toLocaleDateString('uk-UA') + ' ' + updated.toLocaleTimeString('uk-UA', {hour:'2-digit', minute:'2-digit'});
                    } else if (result.data.created_at) {
                        var created = new Date(result.data.created_at);
                        metaHtml += created.toLocaleDateString('uk-UA') + ' ' + created.toLocaleTimeString('uk-UA', {hour:'2-digit', minute:'2-digit'});
                    }
                    metaHtml += '</div>';
                    document.querySelector('#rateMetaFooter .modal-meta-info').innerHTML = metaHtml;
                }
            })
            .catch(function() {});
    }
}

</script>
<?php endif; ?>
<?php endif; ?>