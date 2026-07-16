<?php
session_start();

if (isset($_SESSION['personel_id'])) {
    header('Location: pages/dashboard/dashboard.php');
    exit;
} else {
    header('Location: auth/login_view.php');
    exit;
}
