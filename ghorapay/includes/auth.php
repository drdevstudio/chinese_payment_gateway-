<?php
require_once __DIR__ . '/../config.php';

function merchantAuth(): array {
    if (empty($_SESSION['merchant_id'])) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
    $merchant = fbGet('merchants/' . $_SESSION['merchant_id']);
    if (!is_array($merchant) || ($merchant['status'] ?? '') === 'deleted') {
        session_destroy();
        header('Location: ' . SITE_URL . '/login.php?err=suspended');
        exit;
    }
    if (($merchant['status'] ?? '') === 'suspended') {
        session_destroy();
        header('Location: ' . SITE_URL . '/login.php?err=suspended');
        exit;
    }
    return $merchant;
}

function adminAuth(): array {
    if (empty($_SESSION['admin_username'])) {
        header('Location: ' . SITE_URL . '/admin/login.php');
        exit;
    }
    $admin = fbGet('admins/' . $_SESSION['admin_username']);
    if (!is_array($admin)) {
        session_destroy();
        header('Location: ' . SITE_URL . '/admin/login.php');
        exit;
    }
    return $admin;
}
