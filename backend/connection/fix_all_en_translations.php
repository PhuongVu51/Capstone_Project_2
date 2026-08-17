<?php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../includes/i18n.php';

echo "Updating PRODUCTS table with clean English names using translate_product_name...\n";
$stmtP = $pdo->query("SELECT PRD_product_id, PRD_product_name FROM PRODUCTS");
$upP = $pdo->prepare("UPDATE PRODUCTS SET PRD_product_name_en = :en WHERE PRD_product_id = :id");
$countP = 0;

$_SESSION['lang'] = 'en';

while ($row = $stmtP->fetch()) {
    $en = translate_product_name($row['PRD_product_name']);
    $upP->execute([':en' => $en, ':id' => $row['PRD_product_id']]);
    $countP++;
}
echo "Successfully updated $countP PRODUCTS with English names.\n";
