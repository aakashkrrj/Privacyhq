<?php
namespace Backend\Models;

class ConsentHistory {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function insert($consentId, $prevStatus, $newStatus, $userId, $reason = null) {
        $stmt = $this->pdo->prepare("
            INSERT INTO consent_history (consent_id, previous_status, new_status, changed_by, reason)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$consentId, $prevStatus, $newStatus, $userId, $reason]);
    }
}
