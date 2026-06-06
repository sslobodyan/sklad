<?php
$hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
$password = 'admin';

if (password_verify($password, $hash)) {
    echo "OK - пароль вірний";
} else {
    echo "ERROR - пароль невірний\n\n";
    echo password_hash('admin', PASSWORD_DEFAULT);
}