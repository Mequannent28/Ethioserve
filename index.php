<?php
session_start();
require_once __DIR__ . '/includes/config.php';

$base = BASE_URL;

if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: {$base}/admin/dashboard.php");
    } elseif ($_SESSION['role'] == 'hotel') {
        header("Location: {$base}/hotel/dashboard.php");
    } elseif ($_SESSION['role'] == 'broker') {
        header("Location: {$base}/broker/dashboard.php");
    } elseif ($_SESSION['role'] == 'transport') {
        header("Location: {$base}/transport/dashboard.php");
    } else {
        header("Location: {$base}/customer/index.php");
    }
} else {
    header("Location: {$base}/customer/index.php");
}
exit();
?>