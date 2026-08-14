<?php
namespace Backend\Models;

class DataSubject {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function findByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM data_subjects WHERE (email = ? OR identifier_hash = ?) AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([$email, $email]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function findById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM data_subjects WHERE id = ? AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function create($email, $name = null, $phone = null, $department = null, $type = 'customer') {
        $stmt = $this->pdo->prepare("
            INSERT INTO data_subjects (identifier_hash, name, email, phone, department, type, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'active')
        ");
        $stmt->execute([$email, $name ?: $email, $email, $phone, $department, $type ?: 'customer']);
        return $this->pdo->lastInsertId();
    }

    public function update($id, $name, $email, $phone, $department, $type) {
        $stmt = $this->pdo->prepare("
            UPDATE data_subjects 
            SET name = ?, email = ?, identifier_hash = ?, phone = ?, department = ?, type = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        return $stmt->execute([$name, $email, $email, $phone, $department, $type, $id]);
    }
}
