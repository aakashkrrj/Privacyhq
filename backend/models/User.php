<?php

namespace Backend\Models;

class User
{
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Fetch user by ID
     */
    public function findById($id)
{
    $stmt = $this->pdo->prepare("
        SELECT
            u.id,
            u.role_id,
            u.first_name,
            u.last_name,
            u.email,
            u.phone,
            u.profile_image,
            u.status,
            r.role_name AS role_name
        FROM users u
        LEFT JOIN roles r
            ON u.role_id = r.id
        WHERE u.id = ?
        LIMIT 1
    ");

    $stmt->execute([$id]);

    return $stmt->fetch(\PDO::FETCH_ASSOC);
}

    /**
     * Update profile
     */
    public function updateProfile($id, $firstName, $lastName, $phone)
    {
        $stmt = $this->pdo->prepare("
            UPDATE users
            SET
                first_name = ?,
                last_name = ?,
                phone = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([
            $firstName,
            $lastName,
            $phone,
            $id
        ]);
    }
public function changePassword($userId, $currentPassword, $newPassword)
{
    // Fetch current password hash
    $stmt = $this->pdo->prepare("
        SELECT password_hash
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$userId]);

    $user = $stmt->fetch(\PDO::FETCH_ASSOC);

    if (!$user) {
        throw new \Exception("User not found.");
    }

    // Verify current password
    if (password_verify(
    $currentPassword,
    $user['password_hash'])){
        throw new \Exception("Current password is incorrect.");
    }

    // Hash new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update password
    $stmt = $this->pdo->prepare("
        UPDATE users
        SET password_hash = ?,
            updated_at = NOW()
        WHERE id = ?
    ");

    return $stmt->execute([
        $hashedPassword,
        $userId
    ]);
}
/**
 * Get notification preferences
 */
public function getNotificationPreferences($userId)
{
    $stmt = $this->pdo->prepare("
        SELECT *
        FROM notification_preferences
        WHERE user_id = ?
        LIMIT 1
    ");

    $stmt->execute([$userId]);

    return $stmt->fetch(\PDO::FETCH_ASSOC);
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
    $stmt = $this->pdo->prepare("
        UPDATE notification_preferences
        SET
            email_notifications = ?,
            in_app_notifications = ?,
            privacy_incident_alerts = ?,
            consent_updates = ?,
            assessment_reminders = ?,
            risk_alerts = ?,
            system_announcements = ?,
            updated_at = NOW()
        WHERE user_id = ?
    ");

    return $stmt->execute([
        $emailNotifications,
        $inAppNotifications,
        $privacyIncidentAlerts,
        $consentUpdates,
        $assessmentReminders,
        $riskAlerts,
        $systemAnnouncements,
        $userId
    ]);
}
    /**
     * Update profile image
     */
    public function updateProfileImage($id, $image)
    {
        $stmt = $this->pdo->prepare("
            UPDATE users
            SET profile_image = ?
            WHERE id = ?
        ");

        return $stmt->execute([$image, $id]);
    }
}