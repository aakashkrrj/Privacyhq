<?php
namespace Backend\Controllers;

use Backend\Core\BaseController;
use Backend\Core\ApiResponse;

class CookieGovernanceController extends BaseController {
    private $cookieModel;

    public function __construct($cookieModel) {
        $this->cookieModel = $cookieModel;
    }

    public function index() {
        try {
            $data = $this->cookieModel->getPlaceholderDataset();
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }
}
