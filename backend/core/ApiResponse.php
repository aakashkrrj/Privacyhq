<?php
namespace Backend\Core;

class ApiResponse {
    public static function success($message = "Success", $data = []) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'status' => 'success', // For backward compatibility
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }

    public static function error($message = "Error", $errors = [], $statusCode = 400) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'status' => 'error', // For backward compatibility
            'message' => $message,
            'errors' => $errors
        ]);
        exit;
    }
}
