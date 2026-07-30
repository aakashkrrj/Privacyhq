<?php
namespace Backend\Controllers;

use Backend\Core\BaseController;
use Backend\Core\ApiResponse;

class ReportController extends BaseController {
    private $reportService;

    public function __construct($reportService) {
        $this->reportService = $reportService;
    }

    public function summary() {
        try {
            $data = $this->reportService->getSummary();
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }
}
