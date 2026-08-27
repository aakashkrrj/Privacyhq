<?php
// governance/backend/controllers/SettingsController.php

namespace Backend\Controllers;

use Backend\Core\BaseController;
use Backend\Core\ApiResponse;

class SettingsController extends BaseController
{
    private $settingsService;

    public function __construct($settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Get Profile Information (Row 141)
     */
    public function profile()
    {
        try {
            $user = $this->settingsService->getProfile($this->getUserId());

            if (isset($user['profile_image'])) {
                $user['profile_image'] = getProfileImageUrl($user['profile_image']);
            }

            ApiResponse::success("Success", $user);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    /**
     * Edit Profile (Row 142)
     */
    public function updateProfile()
    {
        try {
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName  = trim($_POST['last_name'] ?? '');
            $phone     = trim($_POST['phone'] ?? '');

            // Image upload handling
            $dbPath = null;
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['profile_image'];
                $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($file['type'], $allowed)) {
                    throw new \Exception("Invalid profile image type. Allowed types: JPEG, PNG, GIF, WebP.");
                }

                $uploadsDir = __DIR__ . '/../../uploads';
                if (!is_dir($uploadsDir)) {
                    mkdir($uploadsDir, 0777, true);
                }

                $filename = time() . '_' . basename($file['name']);
                $destPath = $uploadsDir . '/' . $filename;
                if (move_uploaded_file($file['tmp_name'], $destPath)) {
                    $dbPath = 'uploads/' . $filename;
                }
            }

            $this->settingsService->updateProfile($this->getUserId(), $firstName, $lastName, $phone);

            if ($dbPath) {
                $this->settingsService->updateProfileImage($this->getUserId(), $dbPath);
                $_SESSION['profile_image'] = $dbPath;
            }

            ApiResponse::success(
                "Profile updated successfully.",
                [
                    "profile_image" => getProfileImageUrl($dbPath ?? $_SESSION['profile_image'] ?? null)
                ]
            );
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    /**
     * Update Profile Image (Row 142)
     */
    public function updateProfileImage()
    {
        try {
            if (empty($_FILES['profile_image'])) {
                throw new \Exception("No image uploaded.");
            }

            $imagePath = $this->settingsService->updateProfileImage($this->getUserId(), $_FILES['profile_image']);

            $_SESSION['profile_image'] = $imagePath;

            ApiResponse::success("Profile avatar updated successfully.", [
                "image" => $imagePath,
                "profile_image" => getProfileImageUrl($imagePath)
            ]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    /**
     * Change Password (Row 143)
     */
    public function changePassword()
    {
        try {
            $currentPassword = trim($_POST['current_password'] ?? '');
            $newPassword     = trim($_POST['new_password'] ?? '');
            $confirmPassword = trim($_POST['confirm_password'] ?? '');

            $this->settingsService->changePassword($this->getUserId(), $currentPassword, $newPassword, $confirmPassword);
            ApiResponse::success("Password updated successfully.");
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    /**
     * Two-Factor Authentication 2FA (Row 144)
     */
    public function get2faStatus()
    {
        try {
            $status = $this->settingsService->get2faStatus($this->getUserId());
            ApiResponse::success("2FA status retrieved", $status);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function setup2fa()
    {
        try {
            $data = $this->settingsService->setup2fa($this->getUserId());
            ApiResponse::success("2FA setup secret generated", $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function enable2fa()
    {
        try {
            $secret  = trim($_POST['secret'] ?? '');
            $otpCode = trim($_POST['otp_code'] ?? $_POST['code'] ?? '');

            $res = $this->settingsService->enable2fa($this->getUserId(), $secret, $otpCode);
            ApiResponse::success("Two-Factor Authentication enabled successfully!", $res);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function disable2fa()
    {
        try {
            $currentPassword = trim($_POST['current_password'] ?? '');
            $this->settingsService->disable2fa($this->getUserId(), $currentPassword);
            ApiResponse::success("Two-Factor Authentication disabled successfully.");
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    /**
     * Notification Preferences (Row 145)
     */
    public function notificationPreferences()
    {
        try {
            $preferences = $this->settingsService->getNotificationPreferences($this->getUserId());
            ApiResponse::success("Notification preferences fetched successfully.", $preferences);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function updateNotificationPreferences()
    {
        try {
            $emailNotifications    = !empty($_POST['email_notifications']) ? 1 : 0;
            $inAppNotifications    = !empty($_POST['in_app_notifications']) ? 1 : 0;
            $privacyIncidentAlerts = !empty($_POST['privacy_incident_alerts']) ? 1 : 0;
            $consentUpdates        = !empty($_POST['consent_updates']) ? 1 : 0;
            $assessmentReminders   = !empty($_POST['assessment_reminders']) ? 1 : 0;
            $riskAlerts            = !empty($_POST['risk_alerts']) ? 1 : 0;
            $systemAnnouncements   = !empty($_POST['system_announcements']) ? 1 : 0;

            $this->settingsService->updateNotificationPreferences(
                $this->getUserId(),
                $emailNotifications,
                $inAppNotifications,
                $privacyIncidentAlerts,
                $consentUpdates,
                $assessmentReminders,
                $riskAlerts,
                $systemAnnouncements
            );

            ApiResponse::success("Notification preferences updated successfully.");
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    /**
     * API Key Management (Row 146)
     */
    public function listApiKeys()
    {
        try {
            $keys = $this->settingsService->listApiKeys($this->getUserId());
            ApiResponse::success("API keys retrieved", $keys);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function createApiKey()
    {
        try {
            $keyName = trim($_POST['key_name'] ?? '');
            $scopes  = trim($_POST['scopes'] ?? 'read,write');

            $res = $this->settingsService->createApiKey($this->getUserId(), $keyName, $scopes);
            ApiResponse::success("API Key generated successfully! Save your key now.", $res);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function revokeApiKey()
    {
        try {
            $keyId = filter_input(INPUT_POST, 'key_id', FILTER_VALIDATE_INT) ?: (int)($_POST['id'] ?? 0);
            if (!$keyId) {
                throw new \Exception("Valid API Key ID is required.");
            }

            $this->settingsService->revokeApiKey($this->getUserId(), $keyId);
            ApiResponse::success("API Key revoked successfully.");
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    /**
     * Team Permissions Matrix (Row 147)
     */
    public function teamPermissions()
    {
        try {
            $data = $this->settingsService->getRolesAndPermissionsMatrix();
            ApiResponse::success("Team permissions matrix loaded", $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function saveTeamPermissions()
    {
        $this->checkPermission('manage_users');
        try {
            $roleId = filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT) ?: 0;
            $permissionIds = $_POST['permission_ids'] ?? [];
            if (is_string($permissionIds)) {
                $permissionIds = json_decode($permissionIds, true) ?: [];
            }

            $this->settingsService->saveRolePermissionsMatrix($roleId, $permissionIds, $this->getUserId());
            ApiResponse::success("Team permissions matrix updated successfully.");
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    /**
     * Compliance Documents Vault (Row 148)
     */
    public function listDocuments()
    {
        try {
            $docs = $this->settingsService->listComplianceDocuments();
            ApiResponse::success("Compliance documents retrieved", $docs);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function uploadDocument()
    {
        try {
            $title    = trim($_POST['title'] ?? '');
            $category = trim($_POST['category'] ?? 'General Compliance');

            if (empty($_FILES['document_file'])) {
                throw new \Exception("Please select a valid document file to upload.");
            }

            $docId = $this->settingsService->uploadComplianceDocument($title, $category, $_FILES['document_file'], $this->getUserId());
            ApiResponse::success("Compliance document uploaded successfully!", ["id" => $docId]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function downloadDocument()
    {
        try {
            $docId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: (int)($_GET['id'] ?? 0);
            if (!$docId) {
                throw new \Exception("Valid document ID is required.");
            }

            $this->settingsService->downloadComplianceDocument($docId, $this->getUserId());
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function deleteDocument()
    {
        try {
            $docId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: (int)($_POST['id'] ?? 0);
            if (!$docId) {
                throw new \Exception("Valid document ID is required.");
            }

            $this->settingsService->deleteComplianceDocument($docId, $this->getUserId());
            ApiResponse::success("Compliance document deleted successfully.");
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }
}