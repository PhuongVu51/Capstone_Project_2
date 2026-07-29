<?php
// backend/controllers/DashboardController.php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/WarehouseDashboardModel.php';
require_once __DIR__ . '/../models/ShiftModel.php';

class DashboardController {
    private $dashboardModel;
    private $shiftModel;

    public function __construct() {
        $this->dashboardModel = new WarehouseDashboardModel();
        $this->shiftModel = new ShiftModel();
    }

    public function getWarehouseDashboardData() {
        $lang = $_SESSION['lang'] ?? 'vi';

        $totalKg = $this->dashboardModel->getTotalStockKg();
        $totalUnitsRaw = $totalKg / 5; // 1 unit = 5kg assumption
        $displayTotalUnits = $totalUnitsRaw >= 1000 ? number_format($totalUnitsRaw/1000, 1) . "k" : number_format($totalUnitsRaw, 0);

        $incomingCount = $this->dashboardModel->getIncomingTodayCount();
        $pendingValidationCount = $this->dashboardModel->getPendingValidationCount();

        $cap = $this->dashboardModel->getWarehouseCapacity();
        $capCur = floatval($cap['cur_load'] ?? 0);
        $capMax = floatval($cap['max_cap'] ?? 0);
        $capacityPercent = $capMax > 0 ? ($capCur / $capMax) * 100 : 0;
        $remainingUnits = $capMax > $capCur ? number_format(($capMax - $capCur)/5, 1) . ' units' : '0 units';

        $movements = $this->dashboardModel->getRecentMovements($lang, 5);
        $node = $this->dashboardModel->getNodeStatus(1);
        $currentShift = $this->shiftModel->getCurrentOpenShift();

        $closedShift = null;
        $closedShiftSummary = null;

        if (isset($_GET['closed_shift_id'])) {
            $closedShiftId = (int) $_GET['closed_shift_id'];
            if ($closedShiftId > 0) {
                $closedShift = $this->shiftModel->getShiftById($closedShiftId);
                $closedShiftSummary = $this->shiftModel->getShiftSummary($closedShiftId);
            }
        }

        return [
            'displayTotalUnits' => $displayTotalUnits,
            'incomingCount' => $incomingCount,
            'pendingValidationCount' => $pendingValidationCount,
            'capacityPercent' => $capacityPercent,
            'remainingUnits' => $remainingUnits,
            'movements' => $movements,
            'node' => $node,
            'currentShift' => $currentShift,
            'closedShift' => $closedShift,
            'closedShiftSummary' => $closedShiftSummary,
            'lang' => $lang
        ];
    }
}
?>
