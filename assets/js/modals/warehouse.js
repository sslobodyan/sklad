/**
 * Модалка для складів
 */
function openWarehouseModal(id, name) {
    var isEdit = !!id;
    var title = isEdit ? 'Редагувати склад' : 'Новий склад';
    var action = (typeof basePath !== 'undefined' ? basePath : '') + '/warehouses/save/' + (isEdit ? id : '');

    var content =
        '<form id="warehouseForm" action="' + action + '" method="POST" onsubmit="submitForm(this); return false;">' +
            '<div class="form-group">' +
                '<label class="form-label">Назва складу <span class="required">*</span></label>' +
                '<input type="text" name="name" class="form-input" required ' +
                       'value="' + escapeHtml(name || '') + '" placeholder="Наприклад: Основний склад">' +
            '</div>' +
            '<div class="modal-footer">' +
                '<button type="button" class="btn btn-secondary" onclick="closeModal()">Скасувати</button>' +
                '<button type="submit" class="btn btn-primary">' + (isEdit ? 'Зберегти' : 'Додати') + '</button>' +
            '</div>' +
        '</form>';

    openModal(title, content);
}