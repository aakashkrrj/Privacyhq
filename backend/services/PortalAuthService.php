<?php
namespace Backend\Services;

class PortalAuthService {
    private $pdo;
    private $hmacSecret;
    private $mockEmailLogPath;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
        // Use an environment variable or fallback for the HMAC secret
        $this->hmacSecret = getenv('PORTAL_HMAC_SECRET') ?: 'default-development-secret-key-12345';
        $this->mockEmailLogPath = __DIR__ . '/../../storage/logs/mock_emails.log';
        
        // Ensure log directory exists
        $logDir = dirname($this->mockEmailLogPath);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
    }

    private function getClientIp() {
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    private function mockSendEmail($email, $subject, $body) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] TO: $email | SUBJECT: $subject | BODY: $body\n";
        file_put_contents($this->mockEmailLogPath, $logEntry, FILE_APPEND);
    }

    public function requestMagicLink($email) {
        $ip = $this->getClientIp();

        // 1. IP Rate Limiting (max 10 per hour)
        $stmtIp = $this->pdo->prepare("SELECT COUNT(*) FROM portal_tokens WHERE ip_address = ? AND created_at > (NOW() - INTERVAL 1 HOUR)");
        $stmtIp->execute([$ip]);
        if ($stmtIp->fetchColumn() >= 10) {
            return false; // Silently rate limited
        }

        // 2. Find Subject
        $stmt = $this->pdo->prepare("SELECT id FROM data_subjects WHERE email = ? AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([$email]);
        $subject = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$subject) {
            return true; // Generic success even if subject doesn't exist
        }
        $subjectId = $subject['id'];

        // 3. Subject Rate Limiting (max 3 magic links per hour)
        $stmtSubj = $this->pdo->prepare("SELECT COUNT(*) FROM portal_tokens WHERE data_subject_id = ? AND token_type = 'magic_link' AND created_at > (NOW() - INTERVAL 1 HOUR)");
        $stmtSubj->execute([$subjectId]);
        if ($stmtSubj->fetchColumn() >= 3) {
            return true; // Generic success to prevent enumeration, but don't send
        }

        // 4. Generate Token
        $rawToken = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $rawToken);
        $expiresAt = date('Y-m-d H:i:s', time() + (15 * 60)); // 15 mins

        // 5. Store Token
        $stmtStore = $this->pdo->prepare("INSERT INTO portal_tokens (data_subject_id, token_hash, token_type, expires_at, ip_address) VALUES (?, ?, 'magic_link', DATE_ADD(NOW(), INTERVAL 15 MINUTE), ?)");
        $stmtStore->execute([$subjectId, $hashedToken, $ip]);

        // 6. Send Mock Email
        $verifyUrl = "http://" . $_SERVER['HTTP_HOST'] . "/governance/portal/verify.php?token=" . $rawToken;
        $this->mockSendEmail($email, "Your PrivacyHQ Portal Access Link", "Click here to access your privacy portal: $verifyUrl");

        return true;
    }

    public function verifyMagicLink($rawToken) {
        if (empty($rawToken) || strlen($rawToken) !== 64) {
            return false;
        }

        $hashedToken = hash('sha256', $rawToken);

        $stmt = $this->pdo->prepare("SELECT id, data_subject_id FROM portal_tokens WHERE token_hash = ? AND token_type = 'magic_link' AND used_at IS NULL AND expires_at > NOW() LIMIT 1");
        $stmt->execute([$hashedToken]);
        $tokenRow = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$tokenRow) {
            return false;
        }

        // Consume token
        $stmtConsume = $this->pdo->prepare("UPDATE portal_tokens SET used_at = NOW() WHERE id = ?");
        $stmtConsume->execute([$tokenRow['id']]);

        // Fetch subject details for session
        $stmtSubj = $this->pdo->prepare("SELECT email FROM data_subjects WHERE id = ?");
        $stmtSubj->execute([$tokenRow['data_subject_id']]);
        $email = $stmtSubj->fetchColumn();

        // 5. Regenerate session ID & set session data
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        } else {
            session_name('privacyhq_portal');
            session_start();
            session_regenerate_id(true);
        }

        $_SESSION['portal_subject_id'] = $tokenRow['data_subject_id'];
        $_SESSION['portal_email'] = $email;
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return true;
    }

    public function requestOtp($subjectId, $email, $actionContext) {
        $ip = $this->getClientIp();

        // Issuance limit (e.g. 5 active OTPs per hour)
        $stmtLimit = $this->pdo->prepare("SELECT COUNT(*) FROM portal_tokens WHERE data_subject_id = ? AND token_type = 'otp' AND created_at > (NOW() - INTERVAL 1 HOUR)");
        $stmtLimit->execute([$subjectId]);
        if ($stmtLimit->fetchColumn() >= 5) {
            return false;
        }

        // Generate 6-digit OTP
        $rawOtp = sprintf("%06d", random_int(0, 999999));
        
        // HMAC hash for low-entropy storage
        $hashedOtp = hash_hmac('sha256', $rawOtp, $this->hmacSecret);
        $expiresAt = date('Y-m-d H:i:s', time() + (10 * 60)); // 10 mins

        $stmtStore = $this->pdo->prepare("INSERT INTO portal_tokens (data_subject_id, token_hash, token_type, action_context, expires_at, ip_address) VALUES (?, ?, 'otp', ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE), ?)");
        $stmtStore->execute([$subjectId, $hashedOtp, $actionContext, $ip]);
        
        $this->mockSendEmail($email, "Your PrivacyHQ Verification Code", "Your verification code for $actionContext is: $rawOtp");

        return true;
    }

    public function verifyOtp($subjectId, $rawOtp, $actionContext) {
        if (!preg_match('/^[0-9]{6}$/', $rawOtp)) {
            return false;
        }

        $hashedOtp = hash_hmac('sha256', $rawOtp, $this->hmacSecret);

        // Find the most recent active OTP for this subject and context
        // We use FOR UPDATE to prevent concurrency bypass
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("SELECT id, token_hash, attempts, used_at, (expires_at < NOW()) as is_expired FROM portal_tokens WHERE data_subject_id = ? AND token_type = 'otp' AND action_context = ? ORDER BY id DESC LIMIT 1 FOR UPDATE");
            $stmt->execute([$subjectId, $actionContext]);
            $tokenRow = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$tokenRow) {
                $this->pdo->rollBack();
                return false;
            }

            // Always increment attempts on the active challenge before checking the guess!
            if ($tokenRow['attempts'] < 5 && $tokenRow['used_at'] === null) {
                // Ensure expires_at is not modified by ON UPDATE CURRENT_TIMESTAMP if applicable
                $stmtUpdate = $this->pdo->prepare("UPDATE portal_tokens SET attempts = attempts + 1, expires_at = expires_at WHERE id = ?");
                $stmtUpdate->execute([$tokenRow['id']]);
            }

            // Check if already used, expired, or attempts >= 5
            if ($tokenRow['used_at'] !== null || $tokenRow['is_expired'] || $tokenRow['attempts'] >= 5) {
                $this->pdo->commit();
                return false;
            }

            // Compare the hash
            if (!hash_equals($tokenRow['token_hash'], $hashedOtp)) {
                $this->pdo->commit();
                return false;
            }

            // Correct! Consume the OTP
            $stmtConsume = $this->pdo->prepare("UPDATE portal_tokens SET used_at = NOW(), expires_at = expires_at WHERE id = ?");
            $stmtConsume->execute([$tokenRow['id']]);
            $this->pdo->commit();

            // Set short-lived session flag bounded to action context
            $_SESSION['portal_stepup'] = [
                'context' => $actionContext,
                'verified_at' => time(),
                'expires_at' => time() + (15 * 60) // 15 mins elevation
            ];

            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }
}
