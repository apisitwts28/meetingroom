<?php
// admin_range_booking.php
require_once "db.php";
session_start();

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? '') !== 'admin') { 
    header("Location: login.php"); exit; 
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset("utf8");

$t_res = DB_PREFIX . 'reservation';
$t_rooms = DB_PREFIX . 'rooms';
$t_logs = DB_PREFIX . 'booking_logs';

$user_id = $_SESSION["user_id"];
$msg = "";
$error = "";

// ดึงรายชื่อห้อง
$rooms = $conn->query("SELECT * FROM $t_rooms WHERE is_active = 1");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $room_id = (int)$_POST["room_id"];
    $topic = trim($_POST["topic"]);
    $start_d = $_POST["start_date"];
    $end_d = $_POST["end_date"];
    $start_t = $_POST["start_time"];
    $end_t = $_POST["end_time"];
    $days = $_POST["days"] ?? []; // Array of days (0=Sunday, 1=Monday...)

    if (empty($topic) || empty($start_d) || empty($end_d) || empty($start_t) || empty($end_t)) {
        $error = "กรุณากรอกข้อมูลให้ครบถ้วน";
    } elseif (empty($days)) {
        $error = "กรุณาเลือกวันในสัปดาห์ที่ต้องการจอง";
    } else {
        $current = strtotime($start_d);
        $end = strtotime($end_d);
        $count = 0;
        $fail = 0;

        while ($current <= $end) {
            $w = date('w', $current); // 0-6
            if (in_array($w, $days)) {
                $date_str = date('Y-m-d', $current);
                $begin = "$date_str $start_t:00";
                $stop  = "$date_str $end_t:00";

                // Check Overlap
                $chk = $conn->prepare("SELECT id FROM $t_res WHERE room_id=? AND status=1 AND (begin < ? AND end > ?)");
                $chk->bind_param("iss", $room_id, $stop, $begin);
                $chk->execute();
                if ($chk->get_result()->num_rows == 0) {
                    // Insert (Admin จอง = อนุมัติเลย approve=1)
                    $ins = $conn->prepare("INSERT INTO $t_res (room_id, member_id, topic, begin, end, status, approve) VALUES (?, ?, ?, ?, ?, 1, 1)");
                    $ins->bind_param("iisss", $room_id, $user_id, $topic, $begin, $stop);
                    $ins->execute();
                    $count++;
                } else {
                    $fail++;
                }
            }
            $current = strtotime('+1 day', $current);
        }
        
        $msg = "บันทึกสำเร็จ $count รายการ" . ($fail > 0 ? " (ชนกับรายการอื่น $fail รายการ)" : "");
        // Log
        $conn->query("INSERT INTO $t_logs (user_name, action_type, details) VALUES ('{$_SESSION['user_name']}', 'จองแบบช่วงวัน', '$msg')");
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8"><title>จองห้องแบบช่วงวัน</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>body{font-family:'Kanit',sans-serif;}</style>
</head>
<body class="flex flex-col min-h-screen bg-gray-50">
<?php include 'header.php'; ?>
<?php include 'navbar.php'; ?>

<main class="flex-grow max-w-4xl mx-auto w-full p-6">
    <div class="bg-white rounded-xl shadow-lg border-t-4 border-theme overflow-hidden" style="border-color: <?= $sys['system_color'] ?>">
        <div class="p-6 border-b border-gray-100">
            <h2 class="text-2xl font-bold text-gray-800"><i class="fa-regular fa-calendar-days mr-2"></i> จองห้องแบบระบุช่วงวัน</h2>
            <p class="text-gray-500 text-sm mt-1">สำหรับจองห้องซ้ำๆ หลายวัน (เช่น ประชุมทุกวันจันทร์)</p>
        </div>
        
        <form method="post" class="p-6 space-y-6">
            <?php if($error): ?><div class="bg-red-100 text-red-700 p-3 rounded text-center"><?= $error ?></div><?php endif; ?>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">หัวข้อการจอง</label>
                <input type="text" name="topic" required class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-gray-200 outline-none">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">เลือกห้องประชุม</label>
                <select name="room_id" class="w-full border rounded-lg px-3 py-2 bg-white">
                    <?php while($r = $rooms->fetch_assoc()): ?>
                        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <h4 class="font-bold text-gray-700 border-b pb-2">ช่วงวันที่</h4>
                    <div><label class="text-xs text-gray-500">ตั้งแต่วันที่</label><input type="date" name="start_date" required class="w-full border rounded px-2 py-1"></div>
                    <div><label class="text-xs text-gray-500">ถึงวันที่</label><input type="date" name="end_date" required class="w-full border rounded px-2 py-1"></div>
                </div>
                <div class="space-y-4">
                    <h4 class="font-bold text-gray-700 border-b pb-2">เวลาใช้งาน</h4>
                    <div><label class="text-xs text-gray-500">เวลาเริ่ม</label><input type="time" name="start_time" required class="w-full border rounded px-2 py-1"></div>
                    <div><label class="text-xs text-gray-500">เวลาสิ้นสุด</label><input type="time" name="end_time" required class="w-full border rounded px-2 py-1"></div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">เลือกวันในสัปดาห์</label>
                <div class="flex flex-wrap gap-3">
                    <?php 
                    $days_label = ['อาทิตย์','จันทร์','อังคาร','พุธ','พฤหัสบดี','ศุกร์','เสาร์'];
                    foreach($days_label as $k => $d): 
                    ?>
                    <label class="flex items-center space-x-2 bg-gray-50 px-3 py-2 rounded cursor-pointer border hover:bg-gray-100">
                        <input type="checkbox" name="days[]" value="<?= $k ?>" class="w-4 h-4 text-blue-600 rounded">
                        <span class="text-sm text-gray-700"><?= $d ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="w-full bg-gray-800 text-white font-bold py-3 rounded-lg shadow hover:bg-black transition mt-4">บันทึกการจอง</button>
        </form>
    </div>
</main>
<script>
    <?php if ($msg): ?>
        Swal.fire({ icon: 'info', title: 'ผลการทำงาน', text: '<?= $msg ?>' });
    <?php endif; ?>
</script>
</body>
</html>