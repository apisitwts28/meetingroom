<?php
require_once "db.php";
session_start();

if (!isset($_SESSION["user_id"])) { header("Location: login.php"); exit; }

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset("utf8");

$t_res = DB_PREFIX . 'reservation';
$t_logs = DB_PREFIX . 'booking_logs';

$id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;
$user_id = $_SESSION["user_id"];

// ตรวจสอบสิทธิ์
$stmt = $conn->prepare("SELECT * FROM $t_res WHERE id = ? AND member_id = ? AND status != 9");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) { die("ไม่สามารถแก้ไขรายการนี้ได้"); }

$themeColor = $sys['system_color'] ?? '#4F46E5';
$error = "";
$success = false; // ตัวแปรเช็คสถานะความสำเร็จ

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $topic = trim($_POST["topic"]);
    $attendees = (int) $_POST["attendees"];
    $comment = trim($_POST["comment"]);
    $date = $_POST["date"];
    $start = $_POST["start_time"];
    $end = $_POST["end_time"];
    
    // DateTime format
    $begin_dt = date('Y-m-d H:i:s', strtotime("$date $start"));
    $end_dt   = date('Y-m-d H:i:s', strtotime("$date $end"));
    
    if (strtotime($begin_dt) >= strtotime($end_dt)) {
        $error = "เวลาเริ่มต้นต้องมาก่อนเวลาสิ้นสุด";
    } else {
        // เช็คเวลาซ้อน (ไม่นับตัวเอง)
        $chk = $conn->prepare("SELECT id FROM $t_res WHERE room_id=? AND status=1 AND id!=? AND (begin < ? AND end > ?)");
        $chk->bind_param("iiss", $booking['room_id'], $id, $end_dt, $begin_dt);
        $chk->execute();
        
        if ($chk->get_result()->num_rows > 0) {
            $error = "ช่วงเวลานี้มีการจองซ้อนกับรายการอื่น";
        } else {
            $upd = $conn->prepare("UPDATE $t_res SET topic=?, comment=?, attendees=?, begin=?, end=? WHERE id=?");
            $upd->bind_param("ssissi", $topic, $comment, $attendees, $begin_dt, $end_dt, $id);
            if ($upd->execute()) {
                $conn->query("INSERT INTO $t_logs (user_name, action_type, details) VALUES ('{$_SESSION['user_name']}', 'แก้ไขการจอง', 'ID: $id')");
                $success = true; // ✅ บันทึกสำเร็จ ให้ JS จัดการต่อ
            } else {
                $error = "เกิดข้อผิดพลาดทางฐานข้อมูล";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8"><title>แก้ไขการจอง</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <style> 
      body { font-family: 'Kanit', sans-serif; }
      .btn-save { 
          background-color: <?= $themeColor ?> !important; 
          color: white !important;
      }
      .btn-save:hover { filter: brightness(0.9); }
      .focus-theme:focus { --tw-ring-color: <?= $themeColor ?>; border-color: <?= $themeColor ?>; }
  </style>
</head>
<body class="bg-[#F8FAFC] min-h-screen flex flex-col">
<?php include 'header.php'; ?>
<?php include 'navbar.php'; ?>

<main class="flex-grow flex items-center justify-center p-6">
    <div class="bg-white w-full max-w-lg rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
        
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-500 shadow-sm">
                <i class="fa-solid fa-pen"></i>
            </div>
            <div>
                <h2 class="text-lg font-bold text-gray-800">แก้ไขข้อมูลการจอง</h2>
                <p class="text-xs text-gray-500">ปรับปรุงรายละเอียดการใช้งาน</p>
            </div>
        </div>
        
        <div class="p-6">
            <form method="post" class="space-y-5">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">หัวข้อการจอง</label>
                    <input type="text" name="topic" value="<?= htmlspecialchars($booking['topic']) ?>" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus-theme transition-all">
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">วันที่</label>
                        <input type="date" name="date" value="<?= date('Y-m-d', strtotime($booking['begin'])) ?>" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus-theme transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">จำนวนคน</label>
                        <input type="number" name="attendees" value="<?= $booking['attendees'] ?>" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus-theme transition-all">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">เริ่ม</label>
                        <input type="time" name="start_time" value="<?= date('H:i', strtotime($booking['begin'])) ?>" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus-theme transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">ถึง</label>
                        <input type="time" name="end_time" value="<?= date('H:i', strtotime($booking['end'])) ?>" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus-theme transition-all">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">หมายเหตุ</label>
                    <textarea name="comment" rows="2" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus-theme transition-all"><?= htmlspecialchars($booking['comment']) ?></textarea>
                </div>

                <div class="flex items-center justify-between pt-6 border-t border-gray-100 mt-2">
                    <a href="my-bookings.php" class="text-gray-500 hover:text-gray-700 text-sm font-medium transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-arrow-left"></i> ยกเลิก
                    </a>
                    <button type="submit" class="btn-save px-8 py-2.5 rounded-lg shadow-lg hover:shadow-xl transition-all font-bold transform hover:-translate-y-0.5">
                        บันทึกการแก้ไข
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
    <?php if ($success): ?>
        Swal.fire({
            icon: 'success',
            title: 'บันทึกเรียบร้อย!',
            text: 'ข้อมูลการจองถูกปรับปรุงแล้ว',
            confirmButtonText: 'ตกลง',
            confirmButtonColor: '<?= $themeColor ?>',
            timer: 2000,
            timerProgressBar: true
        }).then((result) => {
            window.location.href = 'my-bookings.php';
        });
    <?php endif; ?>

    <?php if ($error): ?>
        Swal.fire({
            icon: 'error',
            title: 'บันทึกไม่สำเร็จ',
            text: '<?= $error ?>',
            confirmButtonText: 'ลองใหม่',
            confirmButtonColor: '#EF4444'
        });
    <?php endif; ?>
</script>

</body>
</html>