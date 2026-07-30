<?php
namespace Backend\Models;

class ConsentPurpose {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function findByName($name) {
        $stmt = $this->pdo->prepare("SELECT id FROM consent_purposes WHERE purpose_name = ? LIMIT 1");
        $stmt->execute([$name]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function create($name) {
        $stmt = $this->pdo->prepare("INSERT INTO consent_purposes (purpose_name) VALUES (?)");
        $stmt->execute([$name]);
        return $this->pdo->lastInsertId();
    }
}
