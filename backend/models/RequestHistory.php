<?php
namespace Backend\Models;

class RequestHistory {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function insert($requestId, $userId, $prevStatus, $newStatus, $comments = null) {
        $stmt = $this->pdo->prepare("
            INSERT INTO request_history (data_request_id, changed_by, previous_status, new_status, comments)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$requestId, $userId, $prevStatus, $newStatus, $comments]);
    }
}
