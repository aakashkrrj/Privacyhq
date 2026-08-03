<?php

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
     * Get logged-in user's profile
     */
    public function getProfile($userId)
    {
        $user = $this->userModel->findById($userId);

        if (!$user) {
            throw new \Exception("User not found.");
        }

        return $user;
    }

    /**
     * Update profile
     */
    public function updateProfile($userId, $firstName, $lastName, $phone)
    {
        if (empty($firstName) || empty($lastName)) {
            throw new \Exception("First name and Last name are required.");
        }

        try {
            $this->pdo->beginTransaction();

            $this->userModel->updateProfile(
                $userId,
                $firstName,
                $lastName,
                $phone
            );

            // Audit Log
            if (function_exists('log_audit_event')) {
                log_audit_event(
                    $this->pdo,
                    'Settings',
                    'Update Profile',
                    $userId,
                    $userId,
                    null,
                    json_encode([
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'phone' => $phone
                    ])
                );
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
public function changePassword(
    $userId,
    $currentPassword,
    $newPassword,
    $confirmPassword
)
{
    // Validate input
    if (
        empty($currentPassword) ||
        empty($newPassword) ||
        empty($confirmPassword)
    ) {
        throw new \Exception("All fields are required.");
    }

    if ($newPassword !== $confirmPassword) {
        throw new \Exception("New password and Confirm password do not match.");
    }

    if (strlen($newPassword) < 8) {
        throw new \Exception("Password must be at least 8 characters.");
    }

    return $this->userModel->changePassword(
        $userId,
        $currentPassword,
        $newPassword
    );
}
/**
 * Get notification preferences
 */
public function getNotificationPreferences($userId)
{
    return $this->userModel->getNotificationPreferences($userId);
}
/**
 * Update notification preferences
 */
public function updateNotificationPreferences(
    $userId,
    $emailNotifications,
    $inAppNotifications,
    $privacyIncidentAlerts,
    $consentUpdates,
    $assessmentReminders,
    $riskAlerts,
    $systemAnnouncements
)
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

        // Audit Log
        if (function_exists('log_audit_event')) {

            log_audit_event(
                $this->pdo,
                'Settings',
                'Notification Preferences Updated',
                $userId,
                $userId,
                null,
                json_encode([
                    'email_notifications' => $emailNotifications,
                    'in_app_notifications' => $inAppNotifications,
                    'privacy_incident_alerts' => $privacyIncidentAlerts,
                    'consent_updates' => $consentUpdates,
                    'assessment_reminders' => $assessmentReminders,
                    'risk_alerts' => $riskAlerts,
                    'system_announcements' => $systemAnnouncements
                ])
            );

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
     * Update profile image
     */
    public function updateProfileImage($userId, $image)
    {
        return $this->userModel->updateProfileImage(
            $userId,
            $image
        );
    }
}