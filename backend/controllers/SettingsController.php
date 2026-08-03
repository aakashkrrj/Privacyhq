<?php

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
     * Get current user's profile
     */
    public function profile()
    {
        try {

            $user = $this->settingsService->getProfile(
                $this->getUserId()
            );

            ApiResponse::success("Success", $user);

        } catch (\Exception $e) {

            ApiResponse::error($e->getMessage());

        }
    }

    /**
     * Update profile
     */
    public function updateProfile()
    {
        try {

            $firstName = trim($_POST['first_name'] ?? '');
            $lastName  = trim($_POST['last_name'] ?? '');
            $phone     = trim($_POST['phone'] ?? '');

            $this->settingsService->updateProfile(
                $this->getUserId(),
                $firstName,
                $lastName,
                $phone
            );

            ApiResponse::success(
                "Profile updated successfully."
            );

        } catch (\Exception $e) {

            ApiResponse::error($e->getMessage());

        }
    }
    /**
 * Change Password
 */
public function changePassword()
{
    try {

        $currentPassword = trim($_POST['current_password'] ?? '');
        $newPassword = trim($_POST['new_password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');

        $this->settingsService->changePassword(
            $this->getUserId(),
            $currentPassword,
            $newPassword,
            $confirmPassword
        );

        ApiResponse::success(
            "Password changed successfully."
        );

    } catch (\Exception $e) {

        ApiResponse::error(
            $e->getMessage()
        );

    }
}

    /**
     * Update profile image
     */
    public function updateProfileImage()
    {
        try {

            if (!isset($_FILES['profile_image'])) {
                throw new \Exception("No image uploaded.");
            }

            $file = $_FILES['profile_image'];

            // Temporary implementation
            // We'll replace this with proper upload handling later.
            $imagePath = "uploads/profile/" . basename($file['name']);

            move_uploaded_file(
                $file['tmp_name'],
                "../../" . $imagePath
            );

            $this->settingsService->updateProfileImage(
                $this->getUserId(),
                $imagePath
            );

            ApiResponse::success(
                "Profile image updated.",
                [
                    "image" => $imagePath
                ]
            );

        } catch (\Exception $e) {

            ApiResponse::error($e->getMessage());

        }
    }
}