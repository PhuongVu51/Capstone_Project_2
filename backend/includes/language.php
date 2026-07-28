<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DEFAULT_LANGUAGE', 'en');

define('LANGUAGE_PATH', dirname(__DIR__, 2) . '/frontend/lang/');

function getSupportedLanguages(): array
{
    return ['en', 'vi'];
}

function switchLanguage(): void
{
    if (!isset($_GET['lang'])) {
        return;
    }

    $language = strtolower(trim($_GET['lang']));

    if (in_array($language, getSupportedLanguages(), true)) {
        $_SESSION['language'] = $language;
    }
}

function getCurrentLanguage(): string
{
    if (empty($_SESSION['language'])) {
        $_SESSION['language'] = DEFAULT_LANGUAGE;
    }

    return $_SESSION['language'];
}

function getTranslations(): array
{
    $language = getCurrentLanguage();

    $file = LANGUAGE_PATH . $language . '.php';

    if (!file_exists($file)) {
        $file = LANGUAGE_PATH . DEFAULT_LANGUAGE . '.php';
    }

    $translations = require $file;

    return is_array($translations)
        ? $translations
        : [];
}