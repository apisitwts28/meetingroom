<?php
require_once "config.php";

$start = $_GET["start"];
$end = $_GET["end"];
$rooms = isset($_GET["rooms"]) ? explode(",", $_GET["rooms"]) : [];

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset("utf8");

$t_res = DB_PREFIX . 'reservation';
$t_rooms = DB_PREFIX . 'rooms';
$t_users = DB_PREFIX . 'auth_users';

$sql = "SELECT r.id, r.topic AS title, r.begin AS start, r.end, r.comment AS description, r.attendees, r.approve, rm.name AS room, rm.color AS room_color, u.name AS user
FROM $t_res r
LEFT JOIN $t_rooms rm ON rm.id = r.room_id
LEFT JOIN $t_users u ON u.id = r.member_id
WHERE DATE(r.begin) >= ? AND DATE(r.begin) <= ? AND r.status = 1";

if (!empty($rooms)) {
  $ph = implode(",", array_fill(0, count($rooms), "?"));
  $sql .= " AND r.room_id IN ($ph)";
}

$stmt = $conn->prepare($sql);
$params = array_merge([$start, $end], $rooms);
$stmt->bind_param(str_repeat("s", count($params)), ...$params);
$stmt->execute();
$result = $stmt->get_result();
$events = [];

while ($row = $result->fetch_assoc()) {
  
  // ✅ Logic เปลี่ยนสีตามสถานะ
  if ($row['approve'] == 0) {
      // ถ้ารออนุมัติ -> ให้เป็นสีเหลืองส้ม และเติมคำว่า (รออนุมัติ) หน้าชื่อ
      $color = '#F59E0B'; // Amber-500
      $title_show = "(รออนุมัติ) " . $row["title"];
  } else {
      // ถ้าอนุมัติแล้ว -> ใช้สีประจำห้องตามปกติ
      $color = $row["room_color"];
      $title_show = $row["title"];
  }

  $events[] = [
    "id" => $row["id"],
    "title" => $title_show,
    "start" => date('Y-m-d\TH:i:s', strtotime($row["start"])), 
    "end" => date('Y-m-d\TH:i:s', strtotime($row["end"])),
    "description" => $row["description"],
    "room" => $row["room"],
    "user" => $row["user"],
    "attendees" => $row["attendees"],
    "color" => $color, // ใช้ตัวแปรสีที่เรากำหนดด้านบน
    "textColor" => "#ffffff"
  ];
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode($events);
?>