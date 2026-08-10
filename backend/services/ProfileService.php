<?php
namespace Backend\Services;

class ProfileService {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Update user profile information.
     */
    public function updateProfile($userId, $firstName, $lastName, $email, $phone, $designation, $department, $profileImage = null) {
        // Validate email uniqueness
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) {
            throw new \Exception("The email address is already in use by another account.");
        }

        if ($profileImage) {
            $sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, designation = ?, department = ?, profile_image = ? WHERE id = ?";
            $params = [$firstName, $lastName, $email, $phone, $designation, $department, $profileImage, $userId];
        } else {
            $sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, designation = ?, department = ? WHERE id = ?";
            $params = [$firstName, $lastName, $email, $phone, $designation, $department, $userId];
        }

        $stmtUpdate = $this->pdo->prepare($sql);
        $stmtUpdate->execute($params);

        // Update session context immediately
        $_SESSION['user_name'] = trim($firstName . ' ' . $lastName);
        $_SESSION['email'] = $email;
        if ($profileImage) {
            $_SESSION['profile_image'] = $profileImage;
        }

        // Log audit event
        if (function_exists('log_audit_event')) {
            log_audit_event($this->pdo, 'User Management', 'Profile Updated', $userId, $userId, null, json_encode(['email' => $email]));
        }

        return true;
    }

    /**
     * Change user password.
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        // Verify current password matches
        $stmt = $this->pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            throw new \Exception("Current password verification failed.");
        }

        // Invalidate all other sessions by changing password
        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmtUpdate = $this->pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmtUpdate->execute([$newHash, $userId]);

        // Log audit event
        if (function_exists('log_audit_event')) {
            log_audit_event($this->pdo, 'User Management', 'Password Changed', $userId, $userId, null, null);
        }

        return true;
    }
}
