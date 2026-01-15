<?php
// db.php
if (!file_exists(__DIR__ . '/config.php')) { header("Location: install/install.php"); exit; }
require_once __DIR__ . '/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->set_charset("utf8");
date_default_timezone_set('Asia/Bangkok');

// โหลดค่า Settings จาก Database
$sys = [];
$t_settings = DB_PREFIX . 'settings';
$res = $conn->query("SELECT * FROM $t_settings");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $sys[$row['name']] = $row['value'];
    }
}

// ค่า Default กรณีดึงไม่ได้
if (!isset($sys['system_name'])) $sys['system_name'] = 'Booking System';
if (!isset($sys['system_color'])) $sys['system_color'] = '#D35400';
if (!isset($sys['enable_register'])) $sys['enable_register'] = '1';
?>