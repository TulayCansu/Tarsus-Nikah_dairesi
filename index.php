<?php
require_once 'includes/auth.php';

if (isset($_SESSION['personel_id'])) {
    header('Location: pages/dashboard/dashboard.php');
} else {
    header('Location: auth/login_view.php');
}
exit;