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
 * Get notification preferences
 */
public function notificationPreferences()
{
    try {

        $preferences = $this->settingsService->getNotificationPreferences(
            $this->getUserId()
        );

        ApiResponse::success(
            "Notification preferences fetched successfully.",
            $preferences
        );

    } catch (\Exception $e) {

        ApiResponse::error(
            $e->getMessage()
        );

    }
}
/**
 * Update notification preferences
 */
public function updateNotificationPreferences()
{
    try {

        $emailNotifications = (!empty($_POST['email_notifications']) && $_POST['email_notifications'] !== '0' && $_POST['email_notifications'] !== 'false') ? 1 : 0;
        $inAppNotifications = (!empty($_POST['in_app_notifications']) && $_POST['in_app_notifications'] !== '0' && $_POST['in_app_notifications'] !== 'false') ? 1 : 0;
        $privacyIncidentAlerts = (!empty($_POST['privacy_incident_alerts']) && $_POST['privacy_incident_alerts'] !== '0' && $_POST['privacy_incident_alerts'] !== 'false') ? 1 : 0;
        $consentUpdates = (!empty($_POST['consent_updates']) && $_POST['consent_updates'] !== '0' && $_POST['consent_updates'] !== 'false') ? 1 : 0;
        $assessmentReminders = (!empty($_POST['assessment_reminders']) && $_POST['assessment_reminders'] !== '0' && $_POST['assessment_reminders'] !== 'false') ? 1 : 0;
        $riskAlerts = (!empty($_POST['risk_alerts']) && $_POST['risk_alerts'] !== '0' && $_POST['risk_alerts'] !== 'false') ? 1 : 0;
        $systemAnnouncements = (!empty($_POST['system_announcements']) && $_POST['system_announcements'] !== '0' && $_POST['system_announcements'] !== 'false') ? 1 : 0;

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

        ApiResponse::success(
            "Notification preferences updated successfully."
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