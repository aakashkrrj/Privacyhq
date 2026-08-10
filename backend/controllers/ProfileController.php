<?php
namespace Backend\Controllers;

use Backend\Core\BaseController;
use Backend\Core\ApiResponse;

class ProfileController extends BaseController {
    private $profileService;

    public function __construct($profileService) {
        $this->profileService = $profileService;
    }

    /**
     * Update current logged in user profile.
     */
    public function updateProfile() {
        try {
            $userId = $this->getUserId();
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName = trim($_POST['last_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $designation = trim($_POST['designation'] ?? '');
            $department = trim($_POST['department'] ?? '');

            if (empty($firstName) || empty($email)) {
                throw new \Exception("First Name and Email are required fields.");
            }

            // Image upload handling
            $dbPath = null;
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['profile_image'];
                $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($file['type'], $allowed)) {
                    throw new \Exception("Invalid profile image type. Allowed types: JPEG, PNG, GIF, WebP.");
                }

                $uploadsDir = __DIR__ . '/../../../uploads';
                if (!is_dir($uploadsDir)) {
                    mkdir($uploadsDir, 0777, true);
                }

                $filename = time() . '_' . basename($file['name']);
                $destPath = $uploadsDir . '/' . $filename;
                if (move_uploaded_file($file['tmp_name'], $destPath)) {
                    $dbPath = 'uploads/' . $filename;
                }
            }

            $this->profileService->updateProfile($userId, $firstName, $lastName, $email, $phone, $designation, $department, $dbPath);
            ApiResponse::success('Profile updated successfully.', ['profile_image' => $dbPath]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    /**
     * Change user password.
     */
    public function changePassword() {
        try {
            $userId = $this->getUserId();
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                throw new \Exception("All password fields are required.");
            }

            if ($newPassword !== $confirmPassword) {
                throw new \Exception("New password and confirmation password do not match.");
            }

            if (strlen($newPassword) < 8) {
                throw new \Exception("New password must be at least 8 characters long.");
            }

            $this->profileService->changePassword($userId, $currentPassword, $newPassword);
            ApiResponse::success('Password updated successfully.');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }
}
