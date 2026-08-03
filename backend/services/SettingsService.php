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