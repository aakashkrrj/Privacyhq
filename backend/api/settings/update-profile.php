<?php
// governance/backend/api/settings/update-profile.php
require_once __DIR__ . '/bootstrap.php';
\Backend\Core\ApiBootstrap::requireMethod('POST');
\Backend\Core\ApiBootstrap::requireCsrf();

if (isset($_FILES['profile_image'])) {
    $controller->updateProfileImage();
} else {
    $controller->updateProfile();
}