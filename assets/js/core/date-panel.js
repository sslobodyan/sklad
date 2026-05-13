/**
 * Панель вибору дат
 */
function toggleDatePanel(anchorEl) {
    var panel = document.getElementById('datePanel');
    var content = document.getElementById('datePanelContent');
    if (panel.classList.contains('open')) {
        panel.classList.remove('open');
        return;
    }
    if (anchorEl) {
        var rect = anchorEl.getBoundingClientRect();
        if (window.innerWidth <= 900) {
            content.style.top = '8px';
            content.style.left = '8px';
            content.style.right = '8px';
            content.style.width = 'auto';
        } else {
            content.style.top = (rect.bottom + 4) + 'px';
            content.style.right = (window.innerWidth - rect.right) + 'px';
            content.style.left = 'auto';
            content.style.width = '300px';
        }
    }
    panel.classList.add('open');
}