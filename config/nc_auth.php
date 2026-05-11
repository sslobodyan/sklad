<?php
/**
 * Конфігурація авторизації через Nextcloud
 *
 * Секрет має збігатися з системним секретом Nextcloud (config/config.php → 'secret')
 * Отримати: sudo -u www-data php /var/www/nextcloud/occ config:system:get secret
 */
return [
    // Секрет для перевірки підпису HMAC
    // ОБОВ'ЯЗКОВО змініть на значення з Nextcloud!
    'secret' => '12Y8BmV18a4a2goAbFYu4ImzPcVevXroe/DFQYG0aWYdxrSf',
    // Максимальний час життя підпису (секунди)
    // Захист від replay-атак
    'max_age' => 18000,
];