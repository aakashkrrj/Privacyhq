<?php

namespace Backend\Core;

// Require the global database connection and helpers
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/ApiResponse.php';
require_once __DIR__ . '/BaseController.php';

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
    /**
     * Require a valid authenticated admin session.
     */
    public static function requireAuth(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user_id'])) {
            ApiResponse::error(
                "Unauthorized access. Please log in.",
                [],
                401
            );
        }
    }
}

// Enforce authentication at the shared Admin API boundary globally
\Backend\Core\ApiBootstrap::requireAuth();