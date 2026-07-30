<?php
namespace Backend\Services;

class ReportService {
    private $pdo;
    private $reportSummaryModel;

    public function __construct(\PDO $pdo, $reportSummaryModel) {
        $this->pdo = $pdo;
        $this->reportSummaryModel = $reportSummaryModel;
    }

    public function getSummary() {
        return $this->reportSummaryModel->getSummary();
    }
}
