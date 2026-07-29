<?php
// backend/core/BaseModel.php

require_once __DIR__ . '/../connection/db_connect.php';

class BaseModel {
    protected $pdo;

    public function __construct($pdoInstance = null) {
        if ($pdoInstance) {
            $this->pdo = $pdoInstance;
        } else {
            global $pdo; // Fallback cho code cũ
            $this->pdo = $pdo;
        }
    }
}
?>
