<?php
// governance/backend/controllers/UserController.php

namespace Backend\Controllers;

use Backend\Core\BaseController;
use Backend\Core\ApiResponse;

class UserController extends BaseController
{
    private $userService;

    public function __construct($userService)
    {
        $this->userService = $userService;
    }

    public function dashboard()
    {
        $this->checkPermission('manage_users');
        try {
            $data = $this->userService->getDashboardMetrics();
            ApiResponse::success('Users dashboard telemetry loaded', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function list()
    {
        $this->checkPermission('manage_users');
        try {
            $filters = [
                'search' => trim($_GET['search'] ?? ''),
                'role_id' => trim($_GET['role_id'] ?? $_GET['role'] ?? ''),
                'status' => trim($_GET['status'] ?? ''),
                'date_from' => trim($_GET['date_from'] ?? ''),
                'date_to' => trim($_GET['date_to'] ?? '')
            ];
            $page = filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT) ?: 1;
            $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 20;
            $sortField = trim($_GET['sort'] ?? 'id');
            $sortDir = trim($_GET['dir'] ?? 'DESC');

            $data = $this->userService->getUsers($filters, $page, $limit, $sortField, $sortDir);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function get()
    {
        $this->checkPermission('manage_users');
        try {
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: (int)($_GET['id'] ?? 0);
            if (!$id) {
                throw new \Exception("Valid user ID is required.");
            }
            $data = $this->userService->getUserById($id);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function create()
    {
        $this->checkPermission('manage_users');
        try {
            $data = [
                'first_name' => trim($_POST['first_name'] ?? ''),
                'last_name' => trim($_POST['last_name'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'role_id' => filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT) ?: 5,
                'status' => trim($_POST['status'] ?? 'active'),
                'password' => $_POST['password'] ?? '',
                'confirm_password' => $_POST['confirm_password'] ?? $_POST['password_confirm'] ?? ''
            ];

            $newId = $this->userService->createUser($data, $this->getUserId());
            ApiResponse::success('User account created successfully!', ['id' => $newId]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function update()
    {
        $this->checkPermission('manage_users');
        try {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: (int)($_POST['id'] ?? 0);
            if (!$id) {
                throw new \Exception("Valid user ID is required.");
            }

            $data = [
                'first_name' => trim($_POST['first_name'] ?? ''),
                'last_name' => trim($_POST['last_name'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'role_id' => filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT) ?: 5,
                'status' => trim($_POST['status'] ?? 'active'),
                'password' => $_POST['password'] ?? '',
                'confirm_password' => $_POST['confirm_password'] ?? $_POST['password_confirm'] ?? ''
            ];

            $this->userService->updateUser($id, $data, $this->getUserId());
            ApiResponse::success('User profile updated successfully!');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function delete()
    {
        $this->checkPermission('manage_users');
        try {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: (int)($_POST['id'] ?? 0);
            if (!$id) {
                throw new \Exception("Valid user ID is required.");
            }

            $this->userService->deleteUser($id, $this->getUserId());
            ApiResponse::success('User account deleted successfully!');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function roles()
    {
        $this->checkPermission('manage_users');
        try {
            $data = $this->userService->getRolesAndPermissionsMatrix();
            ApiResponse::success('Roles and permissions matrix loaded', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function saveRoles()
    {
        $this->checkPermission('manage_users');
        try {
            $roleId = filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT) ?: 0;
            $permissionIds = $_POST['permission_ids'] ?? [];
            if (is_string($permissionIds)) {
                $permissionIds = json_decode($permissionIds, true) ?: [];
            }

            $this->userService->saveRolePermissionsMatrix($roleId, $permissionIds, $this->getUserId());
            ApiResponse::success('Role permissions matrix saved successfully!');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function export()
    {
        $this->checkPermission('manage_users');
        try {
            $filters = [
                'search' => trim($_GET['search'] ?? ''),
                'role_id' => trim($_GET['role_id'] ?? $_GET['role'] ?? ''),
                'status' => trim($_GET['status'] ?? ''),
                'date_from' => trim($_GET['date_from'] ?? ''),
                'date_to' => trim($_GET['date_to'] ?? '')
            ];
            $format = strtolower(trim($_GET['format'] ?? 'csv'));

            $this->userService->exportUsers($filters, $format, $this->getUserId());
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function import()
    {
        $this->checkPermission('manage_users');
        try {
            if (empty($_FILES['csv_file']['tmp_name'])) {
                throw new \Exception("Please select a valid CSV file to upload.");
            }

            $tmpPath = $_FILES['csv_file']['tmp_name'];
            $res = $this->userService->importUsersFromCsv($tmpPath, $this->getUserId());
            ApiResponse::success("Bulk CSV User Import Completed! Successfully imported {$res['imported_count']} user(s). Failed: {$res['failed_count']}.", $res);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }
}
