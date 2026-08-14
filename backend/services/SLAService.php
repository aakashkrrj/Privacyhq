<?php
namespace Backend\Services;

class SLAService {
    /**
     * Compute SLA due date based on module standards.
     */
    public function calculateDueDate($module, $startDate = null) {
        $start = $startDate ? strtotime($startDate) : time();
        $days = match($module) {
            'DSR' => 30,
            'Incident' => 3,
            'Assessment' => 14,
            'Policy' => 45,
            default => 7
        };
        return date('Y-m-d', strtotime("+$days days", $start));
    }
}
