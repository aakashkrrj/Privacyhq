<?php
require 'backend/config/db.php';
$stmt = $pdo->prepare('SELECT password_hash FROM users WHERE email=?');
$stmt->execute(['admin@privacyhq.com']);
$hash = $stmt->fetchColumn();
var_dump($hash);
var_dump(password_verify('Admin123!', $hash));
