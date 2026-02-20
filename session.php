<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login()
{
    if (empty($_SESSION['username'])) {
        header('Location: index.php');
        exit;
    }
}

function is_admin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function get_username()
{
    return $_SESSION['username'] ?? '';
}
