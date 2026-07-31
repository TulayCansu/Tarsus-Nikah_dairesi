<?php
session_start();
if (!isset($_SESSION['sayac'])) {
    $_SESSION['sayac'] = 0;
}
$_SESSION['sayac']++;
echo 'Session ID: ' . session_id() . '<br>';
echo 'Sayaç: ' . $_SESSION['sayac'];