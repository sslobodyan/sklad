<div class="page-header">
    <div>
        <h1 class="page-title">Типи ресурсів</h1>
        <p class="page-subtitle">Пробіг, мотогодини тощо</p>
    </div>
</div>

<div class="card card-stretch">
    <?php if (empty($types)): ?>
    <div class="empty-state">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M4 6h16M4 12h16M4 18h7"/>
        </svg>
        <p>Типів ресурсів поки немає</p>
        <button class="btn btn-primary btn-sm" onclick="openResourceTypeModal()">Додати перший тип</button>
    </div>
    <?php else: ?>
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-index">#</th>
                    <th>Назва</th>
                    <th class="col-unit">Одиниця</th>
                    <th class="col-format">Формат вводу</th>
                    <th class="col-actions">
                        <button class="btn btn-primary btn-sm table-header-add" onclick="openResourceTypeModal()">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                            Додати
                        </button>
                    </th>
                </table>
            </thead>
            <tbody>
                <?php foreach ($types as $i => $t): ?>
                <tr class="row-editable" ondblclick="openResourceTypeModal(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['name'])) ?>', '<?= htmlspecialchars(addslashes($t['unit'])) ?>', '<?= $t['format'] ?? 'int' ?>')">
                    <td class="text-muted"><?= $i + 1 ?></td>
                    <td class="font-medium"><?= htmlspecialchars($t['name']) ?></td>
                    <td><?= htmlspecialchars($t['unit']) ?></td>
                    <td><?= formatLabel($t['format'] ?? 'int') ?></td>
                    <td class="col-actions">
                        <div class="actions">
                            <button class="btn-icon" title="Редагувати"
                                    onclick="openResourceTypeModal(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['name'])) ?>', '<?= htmlspecialchars(addslashes($t['unit'])) ?>', '<?= $t['format'] ?? 'int' ?>')">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </button>
                            <button class="btn-icon btn-icon-danger" title="Видалити"
                                    onclick="confirmDelete('<?= $basePath ?>/resources/deletetype/<?= $t['id'] ?>', 'Видалити тип «<?= htmlspecialchars(addslashes($t['name'])) ?>»?')">
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
    <?php endif; ?>
</div>

<script>
function openResourceTypeModal(id, name, unit, format) {
    var isEdit = !!id;
    var title = isEdit ? 'Редагувати тип' : 'Новий тип ресурсу';
    var action = isEdit ? basePath + '/resources/savetype/' + id : basePath + '/resources/savetype';
    format = format || 'int';

    var content =
        '<form action="' + action + '" method="POST" onsubmit="submitForm(this); return false;">' +
            '<div class="form-group">' +
                '<label class="form-label">Назва <span class="required">*</span></label>' +
                '<input type="text" name="name" class="form-input" required value="' + escapeHtml(name || '') + '" placeholder="Наприклад: Пробіг">' +
            '</div>' +
            '<div class="form-row">' +
                '<div class="form-group">' +
                    '<label class="form-label">Одиниця виміру <span class="required">*</span></label>' +
                    '<input type="text" name="unit" class="form-input" required value="' + escapeHtml(unit || '') + '" placeholder="км, год">' +
                '</div>' +
                '<div class="form-group">' +
                    '<label class="form-label">Формат вводу <span class="required">*</span></label>' +
                    '<select name="format" class="form-input form-select">' +
                        '<option value="int"' + (format === 'int' ? ' selected' : '') + '>Цілі числа (15230)</option>' +
                        '<option value="dec2"' + (format === 'dec2' ? ' selected' : '') + '>До сотих (15.75)</option>' +
                        '<option value="hm"' + (format === 'hm' ? ' selected' : '') + '>Години:хвилини (1250:30)</option>' +
                    '</select>' +
                '</div>' +
            '</div>' +
            '<div class="modal-footer">' +
                '<button type="button" class="btn btn-secondary" onclick="closeModal()">Скасувати</button>' +
                '<button type="submit" class="btn btn-primary">' + (isEdit ? 'Зберегти' : 'Додати') + '</button>' +
            '</div>' +
        '</form>';

    openModal(title, content);
}
</script>