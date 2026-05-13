/**
 * Модалка для матеріалів
 */
function openMaterialModal(id, name) {
    var isEdit = !!id;
    var title = isEdit ? 'Редагувати матеріал' : 'Новий матеріал';
    var action = (typeof basePath !== 'undefined' ? basePath : '') + '/materials/save/' + (isEdit ? id : '');

    var content =
        '<form id="materialForm" action="' + action + '" method="POST" onsubmit="submitForm(this); return false;">' +
            '<div class="form-group">' +
                '<label class="form-label">Назва матеріалу <span class="required">*</span></label>' +
                '<input type="text" name="name" class="form-input" required ' +
                       'value="' + escapeHtml(name || '') + '" placeholder="Наприклад: Дизельне пальне">' +
            '</div>' +
            '<div class="modal-footer">' +
                '<button type="button" class="btn btn-secondary" onclick="closeModal()">Скасувати</button>' +
                '<button type="submit" class="btn btn-primary">' + (isEdit ? 'Зберегти' : 'Додати') + '</button>' +
            '</div>' +
        '</form>';

    openModal(title, content);
}