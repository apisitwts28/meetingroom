<?php
require_once "db.php";
session_start();

// ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? '') !== "admin") { 
    header("Location: login.php"); exit; 
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset("utf8");

$t_res = DB_PREFIX . 'reservation';
$t_rooms = DB_PREFIX . 'rooms';
$t_logs = DB_PREFIX . 'booking_logs';

$id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

// ดึงข้อมูลการจอง
$stmt = $conn->prepare("SELECT * FROM $t_res WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) { die("ไม่พบข้อมูล"); }

$rooms = $conn->query("SELECT id, name FROM $t_rooms");
$themeColor = $sys['system_color'] ?? '#4F46E5'; 

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $topic = trim($_POST["topic"]);
    $room_id = (int) $_POST["room_id"];
    $attendees = (int) $_POST["attendees"];
    $comment = trim($_POST["comment"]);
    $date = $_POST["date"];
    $start = $_POST["start_time"];
    $end = $_POST["end_time"];
    
    // ✅ รับค่าสถานะใหม่จากฟอร์ม
    $approve_status = (int) $_POST['approve_status']; // 0=รอ, 1=อนุมัติ, 2=ไม่
    $booking_status = (int) $_POST['booking_status']; // 1=ปกติ, 9=ยกเลิก
    
    // DateTime format
    $begin_dt = date('Y-m-d H:i:s', strtotime("$date $start"));
    $end_dt   = date('Y-m-d H:i:s', strtotime("$date $end"));

    // ✅ เพิ่มการ Update approve และ status
    $sql = "UPDATE $t_res SET room_id=?, topic=?, comment=?, attendees=?, begin=?, end=?, approve=?, status=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ississiii", $room_id, $topic, $comment, $attendees, $begin_dt, $end_dt, $approve_status, $booking_status, $id);
    
    if ($stmt->execute()) {
        // Log การกระทำ
        $log_detail = "ID: $id | Status: $booking_status | Approve: $approve_status";
        $conn->query("INSERT INTO $t_logs (user_name, action_type, details) VALUES ('ADMIN', 'แก้ไข/เปลี่ยนสถานะ', '$log_detail')");
        header("Location: admin-reservations.php"); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8"><title>แก้ไขข้อมูล (Admin)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
      body{font-family:'Kanit',sans-serif;}
      .btn-theme { background-color: <?= $themeColor ?>; color: white; }
      .btn-theme:hover { filter: brightness(0.9); }
      .focus-theme:focus { border-color: <?= $themeColor ?>; --tw-ring-color: <?= $themeColor ?>; }
      .text-theme { color: <?= $themeColor ?>; }
  </style>
</head>
<body class="bg-[#F8FAFC] min-h-screen flex items-center justify-center p-4 sm:p-6">
    
    <div class="bg-white w-full max-w-2xl rounded-2xl shadow-xl overflow-hidden border border-gray-100">
        
        <div class="bg-gray-900 px-6 sm:px-8 py-5 flex items-center justify-between">
            <div class="flex items-center gap-3 text-white">
                <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold">แก้ไขข้อมูลการจอง</h2>
                    <p class="text-xs text-gray-400">สิทธิ์ผู้ดูแลระบบ (Administrator)</p>
                </div>
            </div>
            <a href="admin-reservations.php" class="text-gray-400 hover:text-white transition"><i class="fa-solid fa-xmark text-xl"></i></a>
        </div>
        
        <form method="post" class="p-6 sm:p-8 space-y-6">
            
            <div class="bg-yellow-50/50 border border-yellow-100 rounded-xl p-5">
                <h3 class="text-xs font-bold text-yellow-700 uppercase mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-sliders"></i> จัดการสถานะ (Admin Actions)
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">ผลการอนุมัติ</label>
                        <select name="approve_status" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus-theme font-semibold">
                            <option value="0" <?= $booking['approve']==0 ? 'selected':'' ?>>⏳ รออนุมัติ (Pending)</option>
                            <option value="1" <?= $booking['approve']==1 ? 'selected':'' ?> class="text-green-600">✅ อนุมัติ (Approved)</option>
                            <option value="2" <?= $booking['approve']==2 ? 'selected':'' ?> class="text-red-600">❌ ไม่อนุมัติ (Rejected)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">สถานะรายการ</label>
                        <select name="booking_status" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus-theme font-semibold">
                            <option value="1" <?= $booking['status']==1 ? 'selected':'' ?> class="text-blue-600">🔵 ใช้งานปกติ (Active)</option>
                            <option value="9" <?= $booking['status']==9 ? 'selected':'' ?> class="text-gray-400">🗑️ ยกเลิกรายการ (Cancelled)</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1">ห้องประชุม</label>
                    <div class="relative">
                        <select name="room_id" class="w-full border border-gray-200 bg-gray-50 rounded-xl px-4 py-3 appearance-none focus:outline-none focus:ring-2 focus-theme focus:bg-white transition-all">
                            <?php 
                            $rooms->data_seek(0);
                            while($r = $rooms->fetch_assoc()): 
                            ?>
                                <option value="<?= $r['id'] ?>" <?= $r['id']==$booking['room_id']?'selected':'' ?>><?= htmlspecialchars($r['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                        <div class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"><i class="fa-solid fa-chevron-down"></i></div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1">หัวข้อการจอง</label>
                    <input type="text" name="topic" value="<?= htmlspecialchars($booking['topic']) ?>" required class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus-theme transition-all">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">วันที่</label>
                        <input type="date" name="date" value="<?= date('Y-m-d', strtotime($booking['begin'])) ?>" required class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus-theme transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">จำนวนผู้เข้าร่วม</label>
                        <input type="number" name="attendees" value="<?= $booking['attendees'] ?>" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus-theme transition-all">
                    </div>
                </div>
                
                <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-3 text-center">ช่วงเวลาการใช้งาน</label>
                    <div class="flex items-center gap-3">
                        <input type="time" name="start_time" value="<?= date('H:i', strtotime($booking['begin'])) ?>" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-center font-bold text-gray-700 focus:ring-2 focus-theme outline-none">
                        <span class="text-gray-400"><i class="fa-solid fa-arrow-right"></i></span>
                        <input type="time" name="end_time" value="<?= date('H:i', strtotime($booking['end'])) ?>" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-center font-bold text-gray-700 focus:ring-2 focus-theme outline-none">
                    </div>
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1">หมายเหตุเพิ่มเติม</label>
                    <textarea name="comment" rows="2" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus-theme transition-all"><?= htmlspecialchars($booking['comment']) ?></textarea>
                </div>
            </div>

            <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 pt-4 border-t border-gray-50">
                <a href="admin-reservations.php" class="px-6 py-3 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 transition font-bold text-sm text-center">ยกเลิก</a>
                <button type="submit" class="btn-theme px-8 py-3 rounded-xl shadow-lg hover:shadow-xl transition-all font-bold text-sm transform hover:-translate-y-0.5 text-center">
                    <i class="fa-solid fa-save mr-2"></i> บันทึกข้อมูล
                </button>
            </div>
        </form>
    </div>
</body>
</html>