<?php
// governance/backend/services/UserService.php

namespace Backend\Services;

use Backend\Core\PdfGenerator;
use Backend\Core\XlsxGenerator;

class UserService
{
    private $pdo;
    private $userModel;

    public function __construct(\PDO $pdo, $userModel = null)
    {
        $this->pdo = $pdo;
        $this->userModel = $userModel ?: new \Backend\Models\User($pdo);
    }

    public function getDashboardMetrics()
    {
        return $this->userModel->getDashboardMetrics();
    }

    public function getUsers($filters = [], $page = 1, $limit = 20, $sortField = 'id', $sortDir = 'DESC')
    {
        $page = max(1, (int)$page);
        $limit = max(1, min(200, (int)$limit));
        $offset = ($page - 1) * $limit;

        $search = $filters['search'] ?? null;
        $roleId = $filters['role_id'] ?? $filters['role'] ?? null;
        $status = $filters['status'] ?? null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        return $this->userModel->getUsersList($search, $roleId, $status, $dateFrom, $dateTo, $limit, $offset, $sortField, $sortDir);
    }

    public function getUserById($id)
    {
        if (empty($id) || !is_numeric($id)) {
            throw new \Exception("Valid user ID is required.");
        }
        $user = $this->userModel->getUserById((int)$id);
        if (!$user) {
            throw new \Exception("User record not found.");
        }
        return $user;
    }

    public function createUser($data, $currentUserId = 1)
    {
        $firstName = trim($data['first_name'] ?? '');
        $lastName = trim($data['last_name'] ?? '');
        $email = strtolower(trim($data['email'] ?? ''));
        $roleId = (int)($data['role_id'] ?? 0);
        $password = $data['password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? $data['password_confirm'] ?? '';

        if (empty($firstName)) {
            throw new \Exception("First name is required.");
        }
        if (empty($lastName)) {
            throw new \Exception("Last name is required.");
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("Valid email address is required.");
        }

        // Email uniqueness check
        $existing = $this->userModel->getUserByEmail($email);
        if ($existing) {
            throw new \Exception("A user with email address '{$email}' already exists.");
        }

        if (empty($password)) {
            throw new \Exception("Password is required.");
        }
        if (strlen($password) < 8) {
            throw new \Exception("Password must be at least 8 characters long.");
        }
        if ($confirmPassword !== '' && $password !== $confirmPassword) {
            throw new \Exception("Password and confirm password do not match.");
        }

        if ($roleId <= 0) {
            throw new \Exception("Please select a valid role.");
        }

        $newUserId = $this->userModel->createUser($data);

        if (function_exists('log_audit_event')) {
            log_audit_event(
                $this->pdo,
                'User Management',
                'Add User',
                $currentUserId,
                $newUserId,
                null,
                json_encode(['email' => $email, 'role_id' => $roleId, 'name' => "$firstName $lastName"])
            );
        }

        return $newUserId;
    }

    public function updateUser($id, $data, $currentUserId = 1)
    {
        $id = (int)$id;
        $existing = $this->getUserById($id);

        $firstName = trim($data['first_name'] ?? '');
        $lastName = trim($data['last_name'] ?? '');
        $email = strtolower(trim($data['email'] ?? ''));
        $roleId = (int)($data['role_id'] ?? 0);

        if (empty($firstName)) {
            throw new \Exception("First name is required.");
        }
        if (empty($lastName)) {
            throw new \Exception("Last name is required.");
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("Valid email address is required.");
        }

        // Email uniqueness check if changed
        if (strtolower($existing['email']) !== $email) {
            $other = $this->userModel->getUserByEmail($email);
            if ($other && (int)$other['id'] !== $id) {
                throw new \Exception("Another user with email address '{$email}' already exists.");
            }
        }

        if (!empty($data['password'])) {
            if (strlen($data['password']) < 8) {
                throw new \Exception("Password must be at least 8 characters long.");
            }
            if (!empty($data['confirm_password']) && $data['password'] !== $data['confirm_password']) {
                throw new \Exception("Password and confirm password do not match.");
            }
        }

        $res = $this->userModel->updateUser($id, $data);

        if (function_exists('log_audit_event')) {
            log_audit_event(
                $this->pdo,
                'User Management',
                'Edit User',
                $currentUserId,
                $id,
                json_encode(['email' => $existing['email'], 'role_id' => $existing['role_id'], 'status' => $existing['status']]),
                json_encode(['email' => $email, 'role_id' => $roleId, 'status' => $data['status'] ?? $existing['status']])
            );
        }

        return $res;
    }

    public function deleteUser($id, $currentUserId = 1)
    {
        $id = (int)$id;
        $existing = $this->getUserById($id);

        $res = $this->userModel->deleteUser($id, $currentUserId);

        if (function_exists('log_audit_event')) {
            log_audit_event(
                $this->pdo,
                'User Management',
                'Delete User',
                $currentUserId,
                $id,
                json_encode(['email' => $existing['email'], 'name' => "{$existing['first_name']} {$existing['last_name']}"]),
                null
            );
        }

        return $res;
    }

    public function getRolesAndPermissionsMatrix()
    {
        return $this->userModel->getRolesAndPermissionsMatrix();
    }

    public function saveRolePermissionsMatrix($roleId, array $permissionIds, $currentUserId = 1)
    {
        $res = $this->userModel->saveRolePermissionsMatrix($roleId, $permissionIds);

        if (function_exists('log_audit_event')) {
            log_audit_event(
                $this->pdo,
                'User Management',
                'Update Role Permissions Matrix',
                $currentUserId,
                $roleId,
                null,
                json_encode(['permission_count' => count($permissionIds)])
            );
        }

        return $res;
    }

    public function getAllRoles()
    {
        return $this->userModel->getAllRoles();
    }

    /**
     * Export Users with Binary PDF, Binary XLSX & Formula Protection (Row 139)
     */
    public function exportUsers($filters = [], $format = 'csv', $currentUserId = 1)
    {
        $list = $this->userModel->getUsersList(
            $filters['search'] ?? null,
            $filters['role_id'] ?? null,
            $filters['status'] ?? null,
            $filters['date_from'] ?? null,
            $filters['date_to'] ?? null,
            10000,
            0,
            'id',
            'DESC'
        );

        $data = $list['items'];
        $headers = [
            'id' => 'User ID',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'role_name' => 'Role Name',
            'status' => 'Status',
            'last_login_at' => 'Last Login',
            'created_at' => 'Created Date'
        ];

        // Sanitize rows for formula injection
        $sanitizedData = [];
        foreach ($data as $row) {
            $sRow = [];
            foreach ($headers as $key => $label) {
                $val = $row[$key] ?? '';
                if (is_array($val)) $val = json_encode($val);
                $sRow[$key] = $this->sanitizeSpreadsheetVal($val);
            }
            $sanitizedData[] = $sRow;
        }

        $title = 'User Accounts & Access Control Inventory';
        $baseFileName = 'PrivacyHQ_Users_Inventory_' . date('Y-m-d');

        // Log audit event for export
        if (function_exists('log_audit_event')) {
            log_audit_event($this->pdo, 'User Management', 'Export Users', $currentUserId, null, null, json_encode(['format' => $format, 'count' => count($sanitizedData)]));
        }

        if ($format === 'pdf') {
            $pdfGen = new PdfGenerator();
            $pdfGen->addHeader('PrivacyHQ — ' . $title, 'Export Date: ' . date('Y-m-d H:i:s') . ' | Total Users: ' . count($sanitizedData));
            $pdfGen->addMetadataBlocks([
                'Module' => 'User Management',
                'Total Accounts' => count($sanitizedData),
                'Status' => 'Active',
                'Export' => 'Binary PDF'
            ]);
            $pdfGen->addTable(array_values($headers), $sanitizedData);
            $pdfBytes = $pdfGen->output();

            $filename = $baseFileName . '.pdf';
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($pdfBytes));
            echo $pdfBytes;
            exit();
        } elseif ($format === 'excel' || $format === 'xlsx') {
            $xlsxGen = new XlsxGenerator($headers, $sanitizedData, 'PrivacyHQ — ' . $title);
            $xlsxBytes = $xlsxGen->output();

            $filename = $baseFileName . '.xlsx';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($xlsxBytes));
            echo $xlsxBytes;
            exit();
        } else {
            $filename = $baseFileName . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $filename);

            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($output, ['PrivacyHQ User Accounts Register Export']);
            fputcsv($output, ['Export Date: ' . date('Y-m-d H:i:s'), 'Total Accounts: ' . count($sanitizedData)]);
            fputcsv($output, []);
            fputcsv($output, array_values($headers));

            foreach ($sanitizedData as $row) {
                fputcsv($output, array_values($row));
            }
            fclose($output);
            exit();
        }
    }

    /**
     * Bulk CSV User Import with Row-by-Row Validation (Row 139)
     */
    public function importUsersFromCsv($filePath, $currentUserId = 1)
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new \Exception("Uploaded CSV file could not be read.");
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \Exception("Failed opening CSV file stream.");
        }

        // Read header row
        $rawHeaders = fgetcsv($handle);
        if (!$rawHeaders) {
            fclose($handle);
            throw new \Exception("CSV file is empty.");
        }

        // Normalize header keys
        $headers = array_map(function($h) {
            return strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', $h))));
        }, $rawHeaders);

        $rolesMap = [];
        $rolesList = $this->userModel->getAllRoles();
        foreach ($rolesList as $r) {
            $rolesMap[strtolower($r['role_name'])] = (int)$r['id'];
            $rolesMap[$r['id']] = (int)$r['id'];
        }

        $successCount = 0;
        $failedCount = 0;
        $rowErrors = [];
        $rowNumber = 1;

        $this->pdo->beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;
                if (empty(array_filter($row))) continue; // skip blank rows

                $rowData = [];
                foreach ($headers as $idx => $key) {
                    $rowData[$key] = trim($row[$idx] ?? '');
                }

                $firstName = $rowData['first_name'] ?? $rowData['firstname'] ?? $rowData['name'] ?? '';
                $lastName = $rowData['last_name'] ?? $rowData['lastname'] ?? 'User';
                $email = strtolower($rowData['email'] ?? '');
                $phone = $rowData['phone'] ?? $rowData['mobile'] ?? '';
                $roleInput = strtolower($rowData['role'] ?? $rowData['role_name'] ?? $rowData['role_id'] ?? 'business user');
                $status = strtolower($rowData['status'] ?? 'active');

                // Validation
                if (empty($firstName)) {
                    $failedCount++;
                    $rowErrors[] = "Row #{$rowNumber}: First name is required.";
                    continue;
                }
                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $failedCount++;
                    $rowErrors[] = "Row #{$rowNumber}: Invalid email address '{$email}'.";
                    continue;
                }

                // Duplicate check
                $existing = $this->userModel->getUserByEmail($email);
                if ($existing) {
                    $failedCount++;
                    $rowErrors[] = "Row #{$rowNumber}: Email '{$email}' already exists.";
                    continue;
                }

                $roleId = $rolesMap[$roleInput] ?? 5; // Default Business User
                if (!in_array($status, ['active', 'inactive', 'suspended'])) {
                    $status = 'active';
                }

                // Secure random initial password
                $initialPassword = 'Pass_' . bin2hex(random_bytes(4)) . '!';

                $this->userModel->createUser([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'phone' => $phone,
                    'role_id' => $roleId,
                    'status' => $status,
                    'password' => $initialPassword
                ]);

                $successCount++;
            }

            fclose($handle);
            $this->pdo->commit();

            if (function_exists('log_audit_event')) {
                log_audit_event(
                    $this->pdo,
                    'User Management',
                    'Bulk CSV User Import',
                    $currentUserId,
                    null,
                    null,
                    json_encode(['imported' => $successCount, 'failed' => $failedCount])
                );
            }

            return [
                'imported_count' => $successCount,
                'failed_count' => $failedCount,
                'errors' => $rowErrors
            ];
        } catch (\Exception $e) {
            fclose($handle);
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    private function sanitizeSpreadsheetVal($val)
    {
        $str = (string)$val;
        if ($str === '') return '';
        $firstChar = substr($str, 0, 1);
        if (in_array($firstChar, ['=', '+', '-', '@', "\t", "\r"])) {
            return "'" . $str;
        }
        return $str;
    }
}
