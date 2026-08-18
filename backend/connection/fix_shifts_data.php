<?php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../models/ShiftModel.php';

echo "=== FIXING SHIFTS DATA DISTRIBUTION ===\n";

// 1. Close all old dummy open shifts
$pdo->exec("UPDATE SHIFTS SET SHF_status = 'Closed', SHF_closed_at = NOW() WHERE SHF_status = 'Open'");

// 2. Distribute shift types across historical records (1 to 403) so it's not 100% Morning
// Pattern: 60% Morning, 30% Afternoon, 10% Overtime
$shifts = $pdo->query("SELECT SHF_shift_id FROM SHIFTS WHERE SHF_shift_id <= 403 ORDER BY SHF_shift_id ASC")->fetchAll(PDO::FETCH_ASSOC);
$stmtUpdate = $pdo->prepare("UPDATE SHIFTS SET SHF_shift_type = ? WHERE SHF_shift_id = ?");

$morningCount = 0;
$afternoonCount = 0;
$overtimeCount = 0;

foreach ($shifts as $s) {
    $id = (int)$s['SHF_shift_id'];
    $rem = $id % 10;
    if ($rem === 0) {
        $type = 'Overtime';
        $overtimeCount++;
    } elseif ($rem === 3 || $rem === 7 || $rem === 9) {
        $type = 'Afternoon';
        $afternoonCount++;
    } else {
        $type = 'Morning';
        $morningCount++;
    }
    $stmtUpdate->execute([$type, $id]);
}

echo "Updated historical shifts: Morning = $morningCount, Afternoon = $afternoonCount, Overtime = $overtimeCount\n";

// 3. Auto-open real-time shift for current time
$shiftModel = new ShiftModel();
$realTimeShift = $shiftModel->getRealTimeShift();

echo "\nCurrent Real-Time Shift auto-opened:\n";
print_r($realTimeShift);

echo "\n=== OPEN SHIFTS AFTER FIX ===\n";
$openShifts = $pdo->query("SELECT * FROM SHIFTS WHERE SHF_status = 'Open'")->fetchAll(PDO::FETCH_ASSOC);
print_r($openShifts);

echo "\n=== SHIFTS DISTRIBUTION SUMMARY IN DB ===\n";
$dist = $pdo->query("SELECT SHF_shift_type, SHF_status, COUNT(*) as count FROM SHIFTS GROUP BY SHF_shift_type, SHF_status")->fetchAll(PDO::FETCH_ASSOC);
print_r($dist);

echo "\nDONE!\n";
