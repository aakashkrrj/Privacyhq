<?php
require 'backend/config/db.php';
$h = password_hash('Admin123!', PASSWORD_BCRYPT);
$pdo->prepare('UPDATE users SET password_hash=? WHERE email=?')->execute([$h, 'admin@privacyhq.com']);
echo "Updated password hash to: $h\n";
