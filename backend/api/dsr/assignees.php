<?php
// backend/api/dsr/assignees.php

use Backend\Core\ApiBootstrap;
require_once __DIR__ . '/../../../backend/core/ApiBootstrap.php';

ApiBootstrap::requireMethod('GET');

try {
    $stmt = $pdo->query("SELECT id, email, first_name, last_name FROM users WHERE status = 'active' ORDER BY first_name ASC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    Backend\Core\ApiResponse::success('Success', $users);
} catch (\Exception $e) {
    Backend\Core\ApiResponse::error($e->getMessage());
}
