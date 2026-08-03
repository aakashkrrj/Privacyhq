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
    if (!password_verify($currentPassword, $user['password'])) {
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