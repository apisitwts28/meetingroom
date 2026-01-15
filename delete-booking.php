<?php
require_once "db.php";
session_start();

if (!isset($_SESSION["user_id"])) { header("Location: login.php"); exit; }

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset("utf8");

$t_res = DB_PREFIX . 'reservation';
$id = (int)$_GET['id'];
$user_id = $_SESSION["user_id"];

// ลบถาวร (เฉพาะที่เป็นเจ้าของ และสถานะต้องเป็น 9 ยกเลิกแล้ว หรือ Closed)
$stmt = $conn->prepare("DELETE FROM $t_res WHERE id = ? AND member_id = ? AND status = 9");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();

header("Location: my-bookings.php");
exit;
?>