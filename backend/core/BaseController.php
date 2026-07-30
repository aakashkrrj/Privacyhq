<?php
namespace Backend\Core;

abstract class BaseController {
    protected function getUserId(): int {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['user_id'] ?? 1;
    }
}
