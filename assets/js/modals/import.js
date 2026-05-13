/**
 * Модалка імпорту Excel
 */
function openImportModal() {
    var content =
        '<form id="importForm" action="' + (typeof basePath !== 'undefined' ? basePath : '') + '/movements/import" method="POST" enctype="multipart/form-data">' +
            '<div class="import-info">' +
                '<p>Файл Excel (.xlsx) повинен мати такі колонки:</p>' +
                '<table class="import-format-table">' +
                    '<tr><th>A</th><th>B</th><th>C</th><th>D</th><th>E</th><th>F</th></tr>' +
                    '<tr><td>Дата</td><td>Звідки</td><td>Куди</td><td>Матеріал</td><td>Кількість</td><td>Примітка</td></tr>' +
                    '<tr class="example"><td>15.03.2025</td><td>Склад ПММ</td><td>OSHKOSH 74-25</td><td>Олива 15W40</td><td>10</td><td>Видача</td></tr>' +
                '</table>' +
                '<ul class="import-hints">' +
                    '<li>Перший рядок — заголовок (буде пропущений)</li>' +
                    '<li>Формат дати: <b>DD.MM.YYYY</b> або YYYY-MM-DD</li>' +
                    '<li>Якщо складу чи матеріалу немає в довіднику — він буде <b>створений автоматично</b></li>' +
                    '<li>Звідки / Куди можна лишити порожнім (прихід / списання)</li>' +
                '</ul>' +
            '</div>' +
            '<div class="form-group">' +
                '<label class="form-label">Файл Excel <span class="required">*</span></label>' +
                '<input type="file" name="file" class="form-input" accept=".xlsx" required id="importFile">' +
            '</div>' +
            '<label class="import-checkbox">' +
                '<input type="checkbox" name="clear_existing" value="1">' +
                '<span>Видалити поточні дані руху перед імпортом</span>' +
            '</label>' +
            '<div id="importPreview" style="display:none"></div>' +
            '<div class="modal-footer">' +
                '<button type="button" class="btn btn-secondary" onclick="closeModal()">Скасувати</button>' +
                '<button type="submit" class="btn btn-primary" id="importSubmitBtn">Імпортувати</button>' +
            '</div>' +
        '</form>';

    openModal('Імпорт руху з Excel', content);
}