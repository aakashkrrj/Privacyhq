<?php
// backend/api/documents/upload.php
header("Content-Type: application/json");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../services/DocumentService.php';

// Auth Check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

/** @var PDO $pdo */

try {
    $documentService = new \Backend\Services\DocumentService($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = trim($_POST['title'] ?? '');
        $linkedModule = trim($_POST['linked_module'] ?? '');
        $linkedRecordId = filter_input(INPUT_POST, 'linked_record_id', FILTER_VALIDATE_INT);
        $uploadedBy = $_SESSION['user_id'];

        if (empty($title) || !isset($_FILES['file'])) {
            throw new Exception("Title and File are required.");
        }

        $file = $_FILES['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload failed with error code: " . $file['error']);
        }

        // Save file in an uploads folder inside workspace
        $uploadsDir = __DIR__ . '/../../../uploads';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0777, true);
        }

        $filename = time() . '_' . basename($file['name']);
        $destPath = $uploadsDir . '/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $destPath)) {
            // Store relative path in DB
            $dbPath = 'uploads/' . $filename;
            $docId = $documentService->uploadDocument($title, $dbPath, $uploadedBy, $linkedModule, $linkedRecordId);
            echo json_encode(["success" => true, "doc_id" => $docId, "message" => "Document uploaded and linked successfully."]);
        } else {
            throw new Exception("Failed to move uploaded file.");
        }
    } else {
        // GET Request - List documents
        $linkedModule = $_GET['linked_module'] ?? null;
        $linkedRecordId = filter_input(INPUT_GET, 'linked_record_id', FILTER_VALIDATE_INT) ?: null;

        $docs = $documentService->getDocuments($linkedModule, $linkedRecordId);
        echo json_encode(["success" => true, "data" => $docs]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
