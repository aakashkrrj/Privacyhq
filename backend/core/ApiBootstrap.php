<?php

namespace Backend\Core;

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/ApiResponse.php';
require_once __DIR__ . '/BaseController.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Central Authentication Interceptor
if (empty($_SESSION['user_id'])) {
    \Backend\Core\ApiResponse::error("Unauthorized access. Valid session required.", [], 401);
    exit;
}

// 2. Central CSRF Interceptor
$currentMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if (in_array($currentMethod, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        \Backend\Core\ApiResponse::error("CSRF token validation failed.", [], 403);
        exit;
    }
}

class ApiBootstrap
{
    /**
     * Ensure the request method matches.
     */
    public static function requireMethod($method)
    {
        $currentMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (strtoupper($currentMethod) !== strtoupper($method)) {
            ApiResponse::error(
                "Invalid request method. Expected " . strtoupper($method),
                [],
                405
            );
        }
    }

    /**
     * Validate CSRF Token.
     */
    public static function requireCsrf()
    {
        $token = $_POST['csrf_token'] ?? '';

        if (!verify_csrf_token($token)) {
            ApiResponse::error(
                "CSRF token validation failed.",
                [],
                403
            );
        }
    }
}