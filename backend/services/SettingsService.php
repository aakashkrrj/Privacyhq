<?php
// governance/backend/services/SettingsService.php

namespace Backend\Services;

class SettingsService
{
    private $pdo;
    private $userModel;

    public function __construct(\PDO $pdo, $userModel)
    {
        $this->pdo = $pdo;
        $this->userModel = $userModel;
    }

    /**
     * Get Logged-in User Profile (Row 141)
     */
    public function getProfile($userId)
    {
        $user = $this->userModel->findById($userId);
        if (!$user) {
            throw new \Exception("User profile not found.");
        }
        // Redact secrets
        unset($user['password_hash']);
        unset($user['two_factor_secret']);
        return $user;
    }

    /**
     * Update Profile (Row 142)
     */
    public function updateProfile($userId, $firstName, $lastName, $phone)
    {
        $firstName = trim($firstName);
        $lastName = trim($lastName);
        $phone = trim($phone);

        if (empty($firstName) || empty($lastName)) {
            throw new \Exception("First name and Last name are required.");
        }

        try {
            $this->pdo->beginTransaction();

            $this->userModel->updateProfile($userId, $firstName, $lastName, $phone);

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'Settings', 'Update Profile', $userId, $userId, null, json_encode([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone' => $phone
                ]));
            }

            $this->pdo->commit();
            return true;
        } catch (\Throwable $t) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $t;
        }
    }

    /**
     * Update Profile Image (Row 142)
     */
    public function updateProfileImage($userId, $file)
    {
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new \Exception("Valid profile image file is required.");
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowed)) {
            throw new \Exception("Invalid image format. Allowed formats: JPG, PNG, WEBP.");
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            throw new \Exception("Profile image size exceeds 5MB limit.");
        }

        $targetDir = __DIR__ . '/../../uploads/profile/';
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $newFileName = 'avatar_' . $userId . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $targetPath = $targetDir . $newFileName;
        $dbPath = 'uploads/profile/' . $newFileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new \Exception("Failed saving uploaded profile image.");
        }

        $this->userModel->updateProfileImage($userId, $dbPath);

        if (function_exists('log_audit_event')) {
            log_audit_event($this->pdo, 'Settings', 'Update Profile Image', $userId, $userId, null, json_encode(['path' => $dbPath]));
        }

        return $dbPath;
    }

    /**
     * Change Password (Row 143)
     */
    public function changePassword($userId, $currentPassword, $newPassword, $confirmPassword)
    {
        $currentPassword = trim($currentPassword);
        $newPassword = trim($newPassword);
        $confirmPassword = trim($confirmPassword);

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            throw new \Exception("All password fields are required.");
        }

        if ($newPassword !== $confirmPassword) {
            throw new \Exception("New password and Confirm password do not match.");
        }

        if (strlen($newPassword) < 8) {
            throw new \Exception("Password must be at least 8 characters long.");
        }

        $res = $this->userModel->changePassword($userId, $currentPassword, $newPassword);

        if (function_exists('log_audit_event')) {
            log_audit_event($this->pdo, 'Settings', 'Change Password', $userId, $userId, null, json_encode(['status' => 'success']));
        }

        return $res;
    }

    /**
     * Two-Factor Authentication 2FA (Row 144)
     */
    public function get2faStatus($userId)
    {
        $user = $this->userModel->findById($userId);
        return [
            'enabled' => !empty($user['two_factor_enabled']),
            'updated_at' => $user['two_factor_updated_at'] ?? null
        ];
    }

    public function setup2fa($userId)
    {
        // Base32 secret generation
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 16; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }

        $user = $this->userModel->findById($userId);
        $appName = rawurlencode('PrivacyHQ Governance (' . ($user['email'] ?? 'User') . ')');
        $qrUrl = "otpauth://totp/{$appName}?secret={$secret}&issuer=PrivacyHQ";

        return [
            'secret' => $secret,
            'qr_url' => $qrUrl
        ];
    }

    public function enable2fa($userId, $secret, $otpCode)
    {
        $otpCode = trim($otpCode);
        if (empty($otpCode) || strlen($otpCode) < 6) {
            throw new \Exception("Please enter a valid 6-digit 2FA verification code.");
        }

        // Generate 8 single-use recovery codes
        $recoveryCodes = [];
        for ($i = 0; $i < 8; $i++) {
            $recoveryCodes[] = bin2hex(random_bytes(4)) . '-' . bin2hex(random_bytes(4));
        }

        $this->userModel->enable2fa($userId, $secret);

        if (function_exists('log_audit_event')) {
            log_audit_event($this->pdo, 'Settings', 'Enable Two-Factor Authentication', $userId, $userId, null, json_encode(['status' => 'enabled']));
        }

        return [
            'enabled' => true,
            'recovery_codes' => $recoveryCodes
        ];
    }

    public function disable2fa($userId, $currentPassword)
    {
        $user = $this->userModel->getUserWithAuth($userId);
        if (!password_verify($currentPassword, $user['password_hash'])) {
            throw new \Exception("Incorrect current password confirmation for 2FA disable.");
        }

        $this->userModel->disable2fa($userId);

        if (function_exists('log_audit_event')) {
            log_audit_event($this->pdo, 'Settings', 'Disable Two-Factor Authentication', $userId, $userId, null, json_encode(['status' => 'disabled']));
        }

        return true;
    }

    /**
     * Notification Preferences (Row 145)
     */
    public function getNotificationPreferences($userId)
    {
        return $this->userModel->getNotificationPreferences($userId);
    }

    public function updateNotificationPreferences($userId, $emailNotifications, $inAppNotifications, $privacyIncidentAlerts, $consentUpdates, $assessmentReminders, $riskAlerts, $systemAnnouncements)
    {
        try {
            $this->pdo->beginTransaction();

            $this->userModel->updateNotificationPreferences(
                $userId,
                $emailNotifications,
                $inAppNotifications,
                $privacyIncidentAlerts,
                $consentUpdates,
                $assessmentReminders,
                $riskAlerts,
                $systemAnnouncements
            );

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'Settings', 'Notification Preferences Updated', $userId, $userId, null, json_encode([
                    'email' => $emailNotifications,
                    'in_app' => $inAppNotifications,
                    'incidents' => $privacyIncidentAlerts
                ]));
            }

            $this->pdo->commit();
            return true;
        } catch (\Throwable $t) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $t;
        }
    }

    /**
     * API Key Management (Row 146)
     */
    public function listApiKeys($userId)
    {
        return $this->userModel->listApiKeys($userId);
    }

    public function createApiKey($userId, $keyName, $scopes = 'read,write')
    {
        $keyName = trim($keyName);
        if (empty($keyName)) {
            throw new \Exception("API Key label/name is required.");
        }

        // Generate cryptographically secure token
        $rawSecret = 'phq_live_' . bin2hex(random_bytes(16));
        $prefix = substr($rawSecret, 0, 12);
        $hash = hash('sha256', $rawSecret);

        $keyId = $this->userModel->createApiKey($userId, $keyName, $prefix, $hash, $scopes);

        if (function_exists('log_audit_event')) {
            log_audit_event($this->pdo, 'Settings', 'Create API Key', $userId, $userId, null, json_encode(['key_id' => $keyId, 'prefix' => $prefix]));
        }

        return [
            'id' => $keyId,
            'key_name' => $keyName,
            'key_prefix' => $prefix,
            'raw_api_key' => $rawSecret // Displayed ONCE to user
        ];
    }

    public function revokeApiKey($userId, $keyId)
    {
        $res = $this->userModel->revokeApiKey($userId, $keyId);

        if (function_exists('log_audit_event')) {
            log_audit_event($this->pdo, 'Settings', 'Revoke API Key', $userId, $userId, null, json_encode(['key_id' => $keyId]));
        }

        return $res;
    }

    /**
     * Compliance Documents Vault (Row 148)
     */
    public function listComplianceDocuments()
    {
        return $this->userModel->listComplianceDocuments();
    }

    public function uploadComplianceDocument($title, $category, $file, $userId)
    {
        $title = trim($title);
        $category = trim($category) ?: 'General Compliance';

        if (empty($title)) {
            throw new \Exception("Compliance document title is required.");
        }

        if (empty($file['tmp_name']) || !file_exists($file['tmp_name'])) {
            throw new \Exception("Valid document file upload is required.");
        }

        $origName = basename($file['name']);
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $allowedExts = ['pdf', 'docx', 'xlsx', 'png', 'jpg', 'jpeg', 'txt'];

        if (!in_array($ext, $allowedExts)) {
            throw new \Exception("File extension '.{$ext}' is not permitted. Allowed extensions: PDF, DOCX, XLSX, PNG, JPG, TXT.");
        }

        if ($file['size'] > 10 * 1024 * 1024) {
            throw new \Exception("Compliance document size exceeds 10MB limit.");
        }

        $targetDir = __DIR__ . '/../../uploads/documents/';
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $randomName = 'doc_' . bin2hex(random_bytes(10)) . '.' . $ext;
        $targetPath = $targetDir . $randomName;
        $dbPath = 'uploads/documents/' . $randomName;

        if (!@move_uploaded_file($file['tmp_name'], $targetPath)) {
            if (!@copy($file['tmp_name'], $targetPath)) {
                throw new \Exception("Failed saving uploaded compliance document.");
            }
        }

        $mimeType = $file['type'] ?: 'application/octet-stream';
        $docId = $this->userModel->createComplianceDocument($title, $category, $randomName, $origName, $dbPath, $file['size'], $mimeType, $userId);

        if (function_exists('log_audit_event')) {
            log_audit_event($this->pdo, 'Settings', 'Upload Compliance Document', $userId, $docId, null, json_encode(['title' => $title, 'file' => $origName]));
        }

        return $docId;
    }

    public function downloadComplianceDocument($docId, $userId)
    {
        $doc = $this->userModel->getComplianceDocumentById($docId);
        if (!$doc) {
            throw new \Exception("Compliance document not found.");
        }

        $fullPath = __DIR__ . '/../../' . $doc['file_path'];
        if (!file_exists($fullPath) || !is_readable($fullPath)) {
            throw new \Exception("Document file stream unavailable on storage server.");
        }

        if (function_exists('log_audit_event')) {
            log_audit_event($this->pdo, 'Settings', 'Download Compliance Document', $userId, $docId, null, json_encode(['title' => $doc['title']]));
        }

        header('Content-Type: ' . $doc['mime_type']);
        header('Content-Disposition: attachment; filename="' . $doc['original_name'] . '"');
        header('Content-Length: ' . filesize($fullPath));
        readfile($fullPath);
        exit();
    }

    public function deleteComplianceDocument($docId, $userId)
    {
        $doc = $this->userModel->getComplianceDocumentById($docId);
        if (!$doc) {
            throw new \Exception("Compliance document not found.");
        }

        $res = $this->userModel->deleteComplianceDocument($docId);

        if (function_exists('log_audit_event')) {
            log_audit_event($this->pdo, 'Settings', 'Delete Compliance Document', $userId, $docId, null, json_encode(['title' => $doc['title']]));
        }

        return $res;
    }

    public function getRolesAndPermissionsMatrix()
    {
        return $this->userModel->getRolesAndPermissionsMatrix();
    }

    public function saveRolePermissionsMatrix($roleId, array $permissionIds, $userId)
    {
        $res = $this->userModel->saveRolePermissionsMatrix($roleId, $permissionIds);

        if (function_exists('log_audit_event')) {
            log_audit_event($this->pdo, 'Settings', 'Update Team Permissions Matrix', $userId, $roleId, null, json_encode(['permission_count' => count($permissionIds)]));
        }

        return $res;
    }
}