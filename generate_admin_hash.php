<?php
// Script để generate password hash cho admin
// Chạy: php generate_admin_hash.php

$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password: $password\n";
echo "Hash: $hash\n";
echo "\nSQL để insert:\n";
echo "INSERT INTO `admins` (`fullname`, `email`, `password_hash`, `is_active`) VALUES\n";
echo "('Administrator', 'admin@adodas.com', '$hash', 1);\n";

