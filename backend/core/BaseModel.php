<?php
// backend/core/BaseModel.php

require_once __DIR__ . '/../connection/db_connect.php';

class BaseModel {
    protected $pdo;

    public function __construct() {
        global $pdo; // Lấy kết nối từ db_connect.php
        $this->pdo = $pdo;
    }
}
?>
