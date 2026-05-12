(function() {
    document.addEventListener('DOMContentLoaded', function() {
        var btn = document.getElementById('sklad-save');
        if (!btn) return;
        btn.addEventListener('click', function() {
            var url = document.getElementById('sklad-url').value;
            var msg = document.getElementById('sklad-msg');
            var formData = new FormData();
            formData.append('external_url', url);
            var xhr = new XMLHttpRequest();
            xhr.open('POST', OC.generateUrl('/apps/sklad/api/settings'));
            xhr.setRequestHeader('requesttoken', OC.requestToken);
            xhr.onload = function() {
                msg.style.display = 'inline';
                if (xhr.status >= 200 && xhr.status < 300) {
                    msg.textContent = '✓ Збережено';
                    msg.style.color = 'green';
                } else {
                    msg.textContent = '✗ Помилка ' + xhr.status;
                    msg.style.color = 'red';
                }
                setTimeout(function() { msg.style.display = 'none'; }, 5000);
            };
            xhr.onerror = function() {
                msg.textContent = '✗ Помилка з\'єднання';
                msg.style.color = 'red';
                msg.style.display = 'inline';
            };
            xhr.send(formData);
        });
    });
})();