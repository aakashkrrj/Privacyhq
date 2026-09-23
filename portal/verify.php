<?php
$token = $_GET['token'] ?? '';
header('Location: ../backend/api/portal/auth/verify-token.php?token=' . urlencode($token));
exit;
