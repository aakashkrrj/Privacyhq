<?php
namespace Backend\Services;

class DocumentService {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Upload and save a document.
     */
    public function uploadDocument($title, $filePath, $uploadedBy, $linkedModule = null, $linkedRecordId = null, $version = '1.0') {
        $stmt = $this->pdo->prepare("
            INSERT INTO document_repository (title, file_path, version, linked_module, linked_record_id, uploaded_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$title, $filePath, $version, $linkedModule, $linkedRecordId, $uploadedBy]);
        return $this->pdo->lastInsertId();
    }

    /**
     * Get documents linked to a record or general list.
     */
    public function getDocuments($linkedModule = null, $linkedRecordId = null) {
        $sql = "SELECT d.*, u.email as uploader_email 
                FROM document_repository d
                JOIN users u ON d.uploaded_by = u.id";
        
        $params = [];
        if ($linkedModule !== null) {
            $sql .= " WHERE d.linked_module = ?";
            $params[] = $linkedModule;
            if ($linkedRecordId !== null) {
                $sql .= " AND d.linked_record_id = ?";
                $params[] = $linkedRecordId;
            }
        }
        $sql .= " ORDER BY d.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
