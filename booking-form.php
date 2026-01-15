<?php
require_once "db.php";
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION["user_id"])) {
  header("Location: login.php"); exit;
}

$room_id = isset($_GET["room_id"]) ? (int) $_GET["room_id"] : 0;
$user_id = $_SESSION["user_id"];
$role = $_SESSION["role"] ?? 'user';

$t_rooms = DB_PREFIX . 'rooms';
$t_res = DB_PREFIX . 'reservation';
$t_logs = DB_PREFIX . 'booking_logs';

// Fetch room details
$stmt = $conn->prepare("SELECT id, name, is_active FROM $t_rooms WHERE id = ?");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();

if (!$room) { die("ไม่พบข้อมูลห้องประชุมที่เลือก"); }
if ($room['is_active'] == 0 && $role !== 'admin') { die("ห้องนี้ปิดปรับปรุงชั่วคราว"); }

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $topic = trim($_POST["topic"]);
    $attendees = (int) $_POST["attendees"];
    $comment = trim($_POST["comment"]);
    $date = $_POST["date"];
    $start_time = $_POST["start_time"];
    $end_time = $_POST["end_time"];
    
    if (strtotime($start_time) >= strtotime($end_time)) {
        $error = "เวลาเริ่มต้นต้องก่อนเวลาสิ้นสุด";
    } else {
        // ✅ สร้าง DateTime ที่ถูกต้อง 100%
        $begin = date('Y-m-d H:i:s', strtotime("$date $start_time"));
        $end   = date('Y-m-d H:i:s', strtotime("$date $end_time"));

        // Check Overlap
        $chk_sql = "SELECT id FROM $t_res WHERE room_id = ? AND status = 1 AND (begin < ? AND end > ?)";
        $chk_stmt = $conn->prepare($chk_sql);
        $chk_stmt->bind_param("iss", $room_id, $end, $begin);
        $chk_stmt->execute();
        
        if ($chk_stmt->get_result()->num_rows > 0) {
            $error = "ช่วงเวลานี้มีผู้จองแล้ว กรุณาเลือกเวลาใหม่";
        } else {
            $is_auto = ($sys['auto_approve'] ?? '0') === '1';
            $approve_status = $is_auto ? 1 : 0;
            
            // Insert
            $sql = "INSERT INTO $t_res (room_id, member_id, topic, comment, attendees, begin, end, status, approve, create_date) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())";
            $stmt = $conn->prepare($sql);
            
            // ✅ แก้ไข Type String ให้ถูกต้อง: i=int, s=string
            // i (room), i (user), s (topic), s (comment), i (attendees), s (begin), s (end), i (approve)
            $stmt->bind_param("iississi", $room_id, $user_id, $topic, $comment, $attendees, $begin, $end, $approve_status);
            
            if ($stmt->execute()) {
                $res_id = $stmt->insert_id;
                $success = "จองห้องสำเร็จ! " . ($is_auto ? "(ระบบอนุมัติอัตโนมัติ)" : "(กรุณารอการอนุมัติ)");
                
                // Log
                $log_msg = "จองห้อง: {$room['name']} ($date $start_time-$end_time)";
                $conn->query("INSERT INTO $t_logs (reservation_id, user_name, action_type, room_name, details) VALUES ($res_id, '{$_SESSION['user_name']}', 'สร้างการจอง', '{$room['name']}', '$log_msg')");
            } else {
                $error = "เกิดข้อผิดพลาด: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8"><title>จองห้องประชุม</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <style> :root { --primary: <?= $sys['system_color'] ?? '#4F46E5' ?>; } .btn-primary { background-color: var(--primary); } </style>
</head>
<body class="flex flex-col min-h-screen bg-[#F8FAFC] font-kanit">

<?php include 'header.php'; ?>
<?php include 'navbar.php'; ?>

  <main class="flex-grow flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-2xl rounded-2xl shadow-xl border border-gray-100 p-6 sm:p-8">
        <div class="mb-6 pb-4 border-b border-gray-100">
            <h2 class="text-2xl font-bold text-gray-800">จองห้องประชุม</h2>
            <p class="text-gray-500 mt-1 text-sm">สถานที่: <span class="font-bold text-primary"><?= htmlspecialchars($room['name']) ?></span></p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 text-red-600 p-3 rounded-lg mb-4 text-center border border-red-200 text-sm"><?= $error ?></div>
        <?php endif; ?>

        <form method="post" class="space-y-5">
            <div><label class="block text-sm font-bold text-gray-700 mb-1">หัวข้อการจอง <span class="text-red-500">*</span></label>
            <input type="text" name="topic" required placeholder="เช่น ประชุมแผนงานประจำปี" class="w-full border border-gray-200 bg-gray-50 rounded-lg px-4 py-2.5 outline-none focus:ring-2 focus:ring-primary transition-all"></div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><label class="block text-sm font-bold text-gray-700 mb-1">วันที่ใช้งาน <span class="text-red-500">*</span></label>
                <input type="date" name="date" required min="<?= date('Y-m-d') ?>" class="w-full border border-gray-200 bg-gray-50 rounded-lg px-4 py-2.5 outline-none focus:ring-2 focus:ring-primary transition-all"></div>
                <div><label class="block text-sm font-bold text-gray-700 mb-1">จำนวนผู้เข้าร่วม (คน)</label>
                <input type="number" name="attendees" min="1" value="1" class="w-full border border-gray-200 bg-gray-50 rounded-lg px-4 py-2.5 outline-none focus:ring-2 focus:ring-primary transition-all"></div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-bold text-gray-700 mb-1">เวลาเริ่ม <span class="text-red-500">*</span></label>
                <input type="time" name="start_time" required class="w-full border border-gray-200 bg-gray-50 rounded-lg px-4 py-2.5 outline-none focus:ring-2 focus:ring-primary transition-all"></div>
                <div><label class="block text-sm font-bold text-gray-700 mb-1">เวลาสิ้นสุด <span class="text-red-500">*</span></label>
                <input type="time" name="end_time" required class="w-full border border-gray-200 bg-gray-50 rounded-lg px-4 py-2.5 outline-none focus:ring-2 focus:ring-primary transition-all"></div>
            </div>

            <div><label class="block text-sm font-bold text-gray-700 mb-1">หมายเหตุ (ถ้ามี)</label>
            <textarea name="comment" rows="2" placeholder="เช่น ขอโปรเจคเตอร์, เตรียมน้ำดื่ม" class="w-full border border-gray-200 bg-gray-50 rounded-lg px-4 py-2.5 outline-none focus:ring-2 focus:ring-primary transition-all"></textarea></div>

            <div class="pt-6 flex justify-between items-center border-t border-gray-100">
                <a href="booking-select.php" class="text-gray-500 hover:text-gray-700 text-sm font-medium transition-colors flex items-center"><i class="fa-solid fa-arrow-left mr-2"></i> ย้อนกลับ</a>
                <button type="submit" class="btn-primary text-white px-8 py-3 rounded-xl font-bold shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-0.5 text-sm">ยืนยันการจอง</button>
            </div>
        </form>
    </div>
  </main>
  
  <footer class="text-center py-6 text-xs text-gray-400"><?= htmlspecialchars($sys['footer_credit'] ?? 'Booking System') ?></footer>

  <script>
    <?php if ($success): ?>
        Swal.fire({ icon: 'success', title: 'บันทึกสำเร็จ!', text: '<?= $success ?>', confirmButtonText: 'ตกลง', confirmButtonColor: 'var(--primary)' })
        .then(() => { window.location.href = 'my-bookings.php'; });
    <?php endif; ?>
  </script>
</body>
</html>