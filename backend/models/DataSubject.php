<?php
namespace Backend\Models;

class DataSubject {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function findByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM data_subjects WHERE identifier_hash = ? AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function create($email, $type = 'customer') {
        $stmt = $this->pdo->prepare("INSERT INTO data_subjects (identifier_hash, type, status) VALUES (?, ?, 'active')");
        $stmt->execute([$email, $type]);
        return $this->pdo->lastInsertId();
    }
}
