<?php
require_once __DIR__ . '/db_connect.php';

function removeVietnameseAccents($str) {
    if (!$str) return '';
    $str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/u", "a", $str);
    $str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/u", "e", $str);
    $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/u", "i", $str);
    $str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/u", "o", $str);
    $str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/u", "u", $str);
    $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/u", "y", $str);
    $str = preg_replace("/(đ)/u", "d", $str);
    $str = preg_replace("/(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)/u", "A", $str);
    $str = preg_replace("/(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)/u", "E", $str);
    $str = preg_replace("/(Ì|Í|Ị|Ỉ|Ĩ)/u", "I", $str);
    $str = preg_replace("/(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)/u", "O", $str);
    $str = preg_replace("/(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)/u", "U", $str);
    $str = preg_replace("/(Ỳ|Ý|Ỵ|Ỷ|Ỹ)/u", "Y", $str);
    $str = preg_replace("/(Đ)/u", "D", $str);
    $str = preg_replace('/[^\x20-\x7E]/', '', $str);
    return trim(preg_replace('/\s+/', ' ', $str));
}

function cleanProductEn($name) {
    $clean = removeVietnameseAccents($name);
    
    $map = [
        'Dua mieng' => 'Cucumber Slices',
        'Dua khoanh' => 'Cucumber Rounds',
        'Dua bao tu' => 'Baby Cucumbers',
        'Dua' => 'Cucumber',
        'Vai dong lon' => 'Canned Lychee',
        'Vai' => 'Lychee',
        'Ngo dong lon' => 'Canned Sweetcorn',
        'Ngo' => 'Sweetcorn',
        'Khom dong lon' => 'Canned Pineapple',
        'Khom la' => 'Pineapple Slices',
        'Khom' => 'Pineapple',
        'Nhan dong lon' => 'Canned Longan',
        'Nhan' => 'Longan',
        'Nuoc dua' => 'Coconut Water',
        'kho mo' => 'Easy-open Can',
        'de mo' => 'Easy-open Can',
        'TL' => 'Net Wt',
        'duong thap' => 'Low Sugar',
        'duong trung' => 'Medium Sugar',
        'duong cao' => 'High Sugar',
        'nuoc duong' => 'Syrup',
        'lo' => 'Jar',
        'lon' => 'Can',
        'thung' => 'Carton',
        'hang loi' => 'Defect Grade',
        'ban gat dua' => 'Cucumber Scraper Bench',
        'ban inox chan cao' => 'High Stainless Bench',
        'bang dinh trang' => 'White Tape',
        'bang dinh vang' => 'Yellow Tape',
        'bang tai inox' => 'Stainless Conveyor',
        'binh tam giac khong nut' => 'Conical Flask (No Stopper)',
        'bo lap trinh PLC dan thanh trung' => 'PLC Pasteurization Controller',
        'bong den bat con trung' => 'Insect Trap Lamp'
    ];

    foreach ($map as $vi => $en) {
        $clean = str_ireplace($vi, $en, $clean);
    }

    return $clean;
}

echo "Updating PRODUCTS table with clean English names...\n";
$stmtP = $pdo->query("SELECT PRD_product_id, PRD_product_name FROM PRODUCTS");
$upP = $pdo->prepare("UPDATE PRODUCTS SET PRD_product_name_en = :en WHERE PRD_product_id = :id");
$countP = 0;
while ($row = $stmtP->fetch()) {
    $en = cleanProductEn($row['PRD_product_name']);
    $upP->execute([':en' => $en, ':id' => $row['PRD_product_id']]);
    $countP++;
}
echo "Updated $countP PRODUCTS cleanly.\n";
