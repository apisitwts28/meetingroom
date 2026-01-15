<?php
require_once "db.php";
session_start();

if (!isset($_SESSION["user_id"])) { header("Location: login.php"); exit; }

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset("utf8");

$t_res = DB_PREFIX . 'reservation';
$t_logs = DB_PREFIX . 'booking_logs';
$id = (int)$_GET['id'];
$user_id = $_SESSION["user_id"];

// ตรวจสอบความเป็นเจ้าของ
$stmt = $conn->prepare("SELECT id FROM $t_res WHERE id = ? AND member_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    // Soft Delete (Status 9)
    $upd = $conn->prepare("UPDATE $t_res SET status = 9, closed = 1 WHERE id = ?");
    $upd->bind_param("i", $id);
    $upd->execute();
    
    // Log
    $conn->query("INSERT INTO $t_logs (user_name, action_type, details) VALUES ('{$_SESSION['user_name']}', 'ยกเลิกการจอง', 'ID: $id')");
}

header("Location: my-bookings.php");
exit;
?>