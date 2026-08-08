<?php
require_once __DIR__ . '/../backend/connection/db_connect.php';
$newHash = password_hash('123456', PASSWORD_BCRYPT);
$stmt = $pdo->prepare("UPDATE USERS SET USR_password_hash = ?");
$stmt->execute([$newHash]);
echo "Updated " . $stmt->rowCount() . " users. New hash: " . $newHash . "\n";

// Verify it works
$check = password_verify('123456', $newHash);
echo "Verify test: " . ($check ? "OK" : "FAIL") . "\n";
