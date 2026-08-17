<?php
// frontend/api_chatbot.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../backend/api/chatbot.php';
?>
