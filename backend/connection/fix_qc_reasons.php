<?php
require_once __DIR__ . '/db_connect.php';

// Fetch all inspections
$stmt = $pdo->query("SELECT QCI_inspection_id, QCI_rejection_reason FROM QC_INSPECTIONS");
$rows = $stmt->fetchAll();

$updateStmt = $pdo->prepare("UPDATE QC_INSPECTIONS SET QCI_rejection_reason_en = :en WHERE QCI_inspection_id = :id");

$count = 0;
foreach ($rows as $row) {
    $id = $row['QCI_inspection_id'];
    $reason = $row['QCI_rejection_reason'] ?? '';
    
    if (empty($reason) || $reason === 'None') {
        $en = 'None';
    } else {
        $r = strtolower($reason);
        if (str_contains($r, 'han g') || str_contains($r, 'han') || str_contains($r, 'gỉ') || str_contains($r, 'gi')) {
            if (str_contains($r, 'phía ngoài') || str_contains($r, 'ngoai')) {
                $en = 'External Rusty Cans (Internal Unverified)';
            } elseif (str_contains($r, 'hỏng') || str_contains($r, 'hong')) {
                $en = 'Rust & Damaged Cans';
            } else {
                $en = 'Rust / Corrosion';
            }
        } elseif (str_contains($r, 'móp') || str_contains($r, 'mop') || str_contains($r, '10')) {
            if (str_contains($r, '10')) {
                $en = 'Dented Cans (10 Units)';
            } elseif (str_contains($r, 'méo') || str_contains($r, 'meo')) {
                $en = 'Dented & Deformed Cans';
            } else {
                $en = 'Dented Cans';
            }
        } elseif (str_contains($r, 'méo') || str_contains($r, 'meo')) {
            $en = 'Deformed Cans';
        } elseif (str_contains($r, 'cắt') || str_contains($r, 'cut')) {
            $en = 'Miscut Specification Batch';
        } elseif (str_contains($r, 'tibit')) {
            $en = 'Tibit Defect';
        } elseif (str_contains($r, '27') || str_contains($r, 'thùng') || str_contains($r, 'thung')) {
            $en = '27 Cartons + 7 Cans (Severe Rust)';
        } elseif (str_contains($r, 'hồng yên') || str_contains($r, 'hong yen')) {
            $en = 'Hong Yen Returned Batch';
        } elseif (str_contains($r, 'trả') || str_contains($r, 'kh')) {
            $en = 'Customer Return (Canceled Label)';
        } elseif (str_contains($r, 'đen') || str_contains($r, 'den')) {
            $en = 'Black Labeled Batch (Transferred VP)';
        } elseif (str_contains($r, 'trắng') || str_contains($r, 'trang')) {
            $en = 'White Labeled Batch (Transferred VP)';
        } elseif (str_contains($r, 'damaged')) {
            $en = 'Damaged Goods Seed Record';
        } else {
            // Fallback unaccented English string
            $en = 'Defect Item / QC Rejection';
        }
    }

    $updateStmt->execute([':en' => $en, ':id' => $id]);
    $count++;
}

echo "Successfully updated $count QC rejection reasons to English.\n";
