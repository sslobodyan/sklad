/**
 * Підтвердження видалення
 */
function confirmDelete(url, message) {
    message = message || 'Ви впевнені, що хочете видалити?';

    var content =
        '<div class="confirm-message">' +
            '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="1.5">' +
                '<path d="M12 9v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>' +
            '</svg>' +
            '<p>' + message + '</p>' +
        '</div>' +
        '<div class="modal-footer">' +
            '<button type="button" class="btn btn-secondary" onclick="closeModal()">Скасувати</button>' +
            '<a href="' + url + '" class="btn btn-danger">Видалити</a>' +
        '</div>';

    openModal('Підтвердження', content);
}