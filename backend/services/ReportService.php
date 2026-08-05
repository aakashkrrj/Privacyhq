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

    public function getVendorRiskReport() {
        return $this->reportSummaryModel->getVendorRiskReport();
    }

    public function getRiskRegisterReport() {
        return $this->reportSummaryModel->getRiskRegisterReport();
    }

    public function getRopaReport() {
        return $this->reportSummaryModel->getRopaReport();
    }

    public function getPoliciesReport() {
        return $this->reportSummaryModel->getPoliciesReport();
    }
}


