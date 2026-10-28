<?php
// auth_agriculteur.php - Protection des pages agriculteur
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'agriculteur') {
    header("Location: ../login.php");
    exit;
}
$basePath = '../';
?>
