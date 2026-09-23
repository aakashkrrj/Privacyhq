<?php
// backend/api/portal/PortalBootstrap.php

// 1. Configure the portal session name BEFORE session_start()
if (session_status() === PHP_SESSION_NONE) {
    session_name('privacyhq_portal');
    session_start();
}

// Require the existing DB and CSRF logic. 
// db.php will see session_status() is ACTIVE and won't call session_start() again.
require_once __DIR__ . '/../../config/db.php';

// Force JSON response for API endpoints
if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
    header('Content-Type: application/json');
}

// 4. Require $_SESSION['portal_subject_id'] for authenticated portal endpoints.
// Exceptions for public auth endpoints
$is_public_endpoint = (
    strpos($_SERVER['REQUEST_URI'] ?? '', '/auth/request-link.php') !== false ||
    strpos($_SERVER['REQUEST_URI'] ?? '', '/auth/verify-token.php') !== false
);

if (!$is_public_endpoint) {
    if (empty($_SESSION['portal_subject_id'])) {
        if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
            header('HTTP/1.1 401 Unauthorized');
            echo json_encode(["status" => "error", "message" => "Unauthorized access. Portal session required."]);
        } else {
            header('Location: index.php');
        }
        exit;
    }
}
