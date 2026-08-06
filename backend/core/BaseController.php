<?php
namespace Backend\Core;

abstract class BaseController {
    protected function getUserId(): int {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['user_id'] ?? 1;
    }

    protected function checkPermission(string $permission): void {
        if (function_exists('require_permission')) {
            require_permission($permission);
        }
    }
}
