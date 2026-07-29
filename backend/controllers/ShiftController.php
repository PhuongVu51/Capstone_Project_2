<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/ShiftModel.php';

require_role(['Warehouse_Staff', 'Production_Manager', 'Director'], '../../frontend/login.php');

class ShiftController
{
    private $shiftModel;

    public function __construct()
    {
        $this->shiftModel = new ShiftModel();
    }

    public function handleClose()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ../../frontend/shift_close.php');
            exit();
        }

        $shiftId = (int) ($_POST['shift_id'] ?? 0);
        $userId = (int) ($_SESSION['user_id'] ?? 0);

        if ($shiftId <= 0 || $userId <= 0) {
            header('Location: ../../frontend/shift_close.php?error=invalid_shift');
            exit();
        }

        try {
            $this->shiftModel->closeShift($shiftId, $userId);
            header('Location: ../../frontend/dashboard_warehouse.php?closed_shift_id=' . urlencode((string) $shiftId));
            exit();
        } catch (Exception $e) {
            header('Location: ../../frontend/shift_close.php?error=' . urlencode($e->getMessage()));
            exit();
        }
    }
}

if (isset($_GET['action'])) {
    $controller = new ShiftController();

    if ($_GET['action'] === 'close') {
        $controller->handleClose();
    }
}
?>
