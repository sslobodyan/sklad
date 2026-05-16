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
            '<div class="modal-footer meta-footer" id="materialMetaFooter">' +
                '<div class="meta-info-side"></div>' +
                '<div class="modal-buttons">' +
                    '<button type="button" class="btn btn-secondary" onclick="closeModal()">Скасувати</button>' +
                    '<button type="submit" class="btn btn-primary">' + (isEdit ? 'Зберегти' : 'Додати') + '</button>' +
                '</div>' +
            '</div>' +
        '</form>';

    openModal(title, content);
    
    if (isEdit) {
        fetch((typeof basePath !== 'undefined' ? basePath : '') + '/materials/getone/' + id)
            .then(function(r) { return r.json(); })
            .then(function(result) {
                if (result.success && result.data) {
                    var metaHtml = '<div class="meta-line">';
                    if (result.data.author) {
                        metaHtml += '<span class="meta-author">' + escapeHtml(result.data.author) + '</span>';
                    }
                    metaHtml += '</div><div class="meta-line">';
                    if (result.data.updated_at && result.data.updated_at !== result.data.created_at) {
                        var updated = new Date(result.data.updated_at);
                        metaHtml += '<span class="meta-date">' + updated.toLocaleDateString('uk-UA') + ' ' + updated.toLocaleTimeString('uk-UA', {hour:'2-digit', minute:'2-digit'}) + '</span>';
                    } else if (result.data.created_at) {
                        var created = new Date(result.data.created_at);
                        metaHtml += '<span class="meta-date">' + created.toLocaleDateString('uk-UA') + ' ' + created.toLocaleTimeString('uk-UA', {hour:'2-digit', minute:'2-digit'}) + '</span>';
                    }
                    metaHtml += '</div>';
                    document.querySelector('#materialMetaFooter .meta-info-side').innerHTML = metaHtml;
                }
            })
            .catch(function() {});
    }
}