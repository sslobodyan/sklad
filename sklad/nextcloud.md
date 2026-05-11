# Nextcloud App: Складський облік
## Встановлення

### Варіант A: External Sites (найпростіший)
1. У Nextcloud встановіть додаток **External Sites**
2. Settings → External Sites → Add:
   - Name: `Складський облік`
   - URL: `https://baza.taildd9271.ts.net/sklad/`
   - Icon: `folder`
   - Language: залиште порожнім
3. Готово — з'явиться пункт у верхньому меню


### Варіант B: Nextcloud App (повноцінна інтеграція)
1. Скопіюйте папку `sklad/` в каталог додатків Nextcloud:
```bash
cp -r nextcloud-app/sklad/ /var/www/nextcloud/apps/sklad/
chown -R www-data:www-data /var/www/nextcloud/apps/sklad/
```
2. У Nextcloud увімкніть додаток:
   - Settings → Apps → «Складський облік» → Enable
3. Налаштуйте URL:
   - Settings → Additional settings → Складський облік
   - Вкажіть URL: `/sklad/` або повний `https://...`
4. У навігації Nextcloud з'явиться пункт «Складський облік»

### Що дає NC App порівняно з External Sites
| | External Sites | NC App |
|---|---|---|
| Складність | Нульова | Потребує копіювання файлів |
| Іконка в навігації | ✅ | ✅ |
| Авторизація NC | ❌ (окрема) | Можна додати |
| URL у браузері | Завжди iframe URL | Nextcloud URL |
| Налаштування | Через External Sites | Через адмін-панель NC |
### Вимоги для iframe
У `index.php` складського додатку вже додано:
```php
header('X-Frame-Options: ALLOWALL');
header('Content-Security-Policy: frame-ancestors *');
```
А також cookie з `SameSite=None; Secure` для роботи в iframe.
### Якщо Nextcloud блокує iframe
Перевірте конфігурацію Nextcloud:
```php
// config/config.php
'allow_local_remote_servers' => true,
```
Або в `.htaccess` Nextcloud додайте:
```apache
Header set Content-Security-Policy "frame-src 'self' https://baza.taildd9271.ts.net"
```
