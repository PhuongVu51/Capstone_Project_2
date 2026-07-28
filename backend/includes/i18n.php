<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    if (in_array($lang, ['en', 'vi'])) {
        $_SESSION['lang'] = $lang;
    }
    // Remove ?lang=xx from URL if possible, or just keep it
    $url = strtok($_SERVER["REQUEST_URI"], '?');
    $query = $_GET;
    unset($query['lang']);
    if (count($query) > 0) {
        $url .= '?' . http_build_query($query);
    }
    header("Location: " . $url);
    exit();
}

$current_lang = $_SESSION['lang'] ?? 'vi';
$lang_file = __DIR__ . '/../lang/' . $current_lang . '.php';

if (file_exists($lang_file)) {
    global $translations;
    $translations = include $lang_file;
} else {
    $translations = [];
}

function __($key) {
    global $translations;
    return $translations[$key] ?? $key;
}
