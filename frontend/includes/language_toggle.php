<?php

if (!isset($lang) || !isset($currentLanguage)) {
    return;
}

$currentPage = basename($_SERVER['PHP_SELF']);

$query = $_GET;

unset($query['lang']);

$queryString = http_build_query($query);

$viUrl = $currentPage .
    ($queryString ? '?' . $queryString . '&lang=vi' : '?lang=vi');

$enUrl = $currentPage .
    ($queryString ? '?' . $queryString . '&lang=en' : '?lang=en');

?>

<style>

.language-switcher{
    display:flex;
    align-items:center;
    gap:8px;
    margin-left:auto;
}

.language-btn{

    text-decoration:none;

    padding:6px 12px;

    border-radius:8px;

    border:1px solid #d1d5db;

    font-size:13px;

    font-weight:600;

    color:#374151;

    transition:.25s;

}

.language-btn:hover{

    background:#f3f4f6;

}

.language-active{

    background:#2563eb;

    color:white;

    border-color:#2563eb;

}

</style>

<div class="language-switcher">

    <span style="font-size:13px;font-weight:600;color:#6b7280;">

        🌐 <?= htmlspecialchars($lang['language']) ?>

    </span>

    <a
        href="<?= htmlspecialchars($viUrl) ?>"
        class="language-btn <?= $currentLanguage === 'vi' ? 'language-active' : '' ?>"
    >
        VI
    </a>

    <a
        href="<?= htmlspecialchars($enUrl) ?>"
        class="language-btn <?= $currentLanguage === 'en' ? 'language-active' : '' ?>"
    >
        EN
    </a>

</div>