<?php
require_once __DIR__ . '/../backend/connection/db_connect.php';
require_once __DIR__ . '/../backend/models/ShiftModel.php';

echo "=== TEST 1: REAL-TIME SHIFT IN ShiftModel ===\n";
$shiftModel = new ShiftModel();
$cur = $shiftModel->getRealTimeShift();
echo "Current Shift ID: {$cur['SHF_shift_id']}\n";
echo "Date: {$cur['SHF_shift_date']}\n";
echo "Type: {$cur['SHF_shift_type']}\n";
echo "Status: {$cur['SHF_status']}\n";

echo "\n=== TEST 2: ALL CURRENTLY OPEN SHIFTS ===\n";
$openShifts = $pdo->query("SELECT * FROM SHIFTS WHERE SHF_status = 'Open'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($openShifts as $os) {
    echo "  Shift {$os['SHF_shift_id']}: {$os['SHF_shift_date']} - {$os['SHF_shift_type']} ({$os['SHF_status']})\n";
}

echo "\n=== TEST 3: SIMULATING LOG_BATCH.PHP FETCH ===\n";
$lang = 'vi';
require_once __DIR__ . '/../backend/includes/i18n.php';
$currentShiftId = (int)$cur['SHF_shift_id'];
$shifts = $pdo->query("SELECT SHF_shift_id, SHF_shift_date, SHF_shift_type FROM SHIFTS WHERE SHF_status = 'Open' ORDER BY SHF_shift_date DESC, SHF_shift_id DESC")->fetchAll();

echo "Dropdown options generated for log_batch.php:\n";
foreach($shifts as $sh) {
    $isCurrent = ($sh['SHF_shift_id'] == $currentShiftId);
    $label = $sh['SHF_shift_date'] . ' - ' . translate_shift_name($sh['SHF_shift_type']);
    if ($isCurrent) {
        $label .= ' (Ca hiện tại)';
    }
    $selectedAttr = $isCurrent ? '[SELECTED]' : '';
    echo "  <option value=\"{$sh['SHF_shift_id']}\" $selectedAttr>$label</option>\n";
}

echo "\n=== TEST 4: SHIFT TABLE DISTRIBUTION ===\n";
$counts = $pdo->query("SELECT SHF_shift_type, SHF_status, COUNT(*) as cnt FROM SHIFTS GROUP BY SHF_shift_type, SHF_status")->fetchAll(PDO::FETCH_ASSOC);
print_r($counts);
