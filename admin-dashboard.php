<?php
// admin-dashboard.php
require_once "db.php";
session_start();

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? '') !== "admin") { header("Location: login.php"); exit; }

$t_rooms = DB_PREFIX . 'rooms';
$t_res = DB_PREFIX . 'reservation';

// Load room list
$rooms = [];
$res = $conn->query("SELECT id, name FROM $t_rooms ORDER BY name");
while ($row = $res->fetch_assoc()) { $rooms[] = $row; }

// Filters
$room_id = $_GET["room_id"] ?? "";
$start_date = $_GET["start_date"] ?? date("Y-m-01");
$end_date = $_GET["end_date"] ?? date("Y-m-t");

// Stats Query
$sql = "SELECT DATE(begin) as date, COUNT(*) as total FROM $t_res WHERE DATE(begin) BETWEEN ? AND ?";
$params = [$start_date, $end_date]; $types = "ss";
if (!empty($room_id)) { $sql .= " AND room_id = ?"; $params[] = $room_id; $types .= "i"; }
$sql .= " GROUP BY DATE(begin) ORDER BY date";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$labels = []; $data = [];
$start = new DateTime($start_date);
$end = new DateTime($end_date);
$end->modify('+1 day');
$period = new DatePeriod($start, new DateInterval('P1D'), $end);

foreach ($period as $date) {
  $labels[] = $date->format("Y-m-d");
  $data[$date->format("Y-m-d")] = 0;
}
while ($row = $result->fetch_assoc()) { $data[$row["date"]] = (int)$row["total"]; }
$data = array_values($data);
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8"><title>แดชบอร์ดผู้ดูแลระบบ</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style> body { font-family: 'Kanit', sans-serif; background-color: #f8f9fa; } </style>
</head>
<body class="flex flex-col min-h-screen">

<?php include 'header.php'; ?>
<?php include 'navbar.php'; ?>

  <main class="flex-grow max-w-7xl mx-auto w-full p-6">
    <div class="flex items-center gap-3 mb-6">
        <div class="p-3 bg-orange-100 rounded-lg text-theme"><i class="fa-solid fa-chart-pie text-xl"></i></div>
        <h2 class="text-2xl font-bold text-gray-800">แดชบอร์ด <span class="text-theme">สถิติการใช้งาน</span></h2>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 mb-8">
        <form method="get" class="flex flex-wrap gap-4 items-end">
          <div class="flex-1 min-w-[200px]">
              <label class="block text-sm text-gray-500 mb-1">เลือกห้องประชุม</label>
              <select name="room_id" class="w-full border border-gray-200 px-4 py-2 rounded-lg text-sm outline-none">
                <option value="">-- ทุกห้อง --</option>
                <?php foreach ($rooms as $room): ?>
                  <option value="<?= $room['id'] ?>" <?= $room_id == $room['id'] ? 'selected' : '' ?>><?= htmlspecialchars($room['name']) ?></option>
                <?php endforeach; ?>
              </select>
          </div>
          <div><label class="block text-sm text-gray-500 mb-1">ตั้งแต่วันที่</label><input type="date" name="start_date" value="<?= $start_date ?>" class="border border-gray-200 px-4 py-2 rounded-lg text-sm outline-none"></div>
          <div><label class="block text-sm text-gray-500 mb-1">ถึงวันที่</label><input type="date" name="end_date" value="<?= $end_date ?>" class="border border-gray-200 px-4 py-2 rounded-lg text-sm outline-none"></div>
          <button type="submit" class="bg-theme text-white px-6 py-2 rounded-lg text-sm font-medium shadow-md" style="background-color: <?= $sys['system_color'] ?>"><i class="fa-solid fa-filter mr-1"></i> แสดงผล</button>
        </form>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-lg border-t-4 border-theme mb-10" style="border-color: <?= $sys['system_color'] ?>">
        <canvas id="bookingChart" height="100"></canvas>
    </div>

    <h3 class="text-xl font-bold text-gray-800 mb-4 border-l-4 border-theme pl-3" style="border-color: <?= $sys['system_color'] ?>">เมนูจัดการระบบ</h3>
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
      <?php 
        $menus = [
            ['link'=>'admin-reservations.php', 'icon'=>'fa-clipboard-list', 'title'=>'รายการจองทั้งหมด', 'desc'=>'ตรวจสอบประวัติการจอง'],
            ['link'=>'admin_range_booking.php', 'icon'=>'fa-calendar-days', 'title'=>'จองแบบช่วงวัน', 'desc'=>'จองล่วงหน้าหลายวัน'],
            ['link'=>'admin-users.php', 'icon'=>'fa-users-gear', 'title'=>'จัดการผู้ใช้', 'desc'=>'เพิ่ม/ลบ/แก้ไขสมาชิก'],
            ['link'=>'admin-rooms.php', 'icon'=>'fa-door-open', 'title'=>'จัดการห้องประชุม', 'desc'=>'ตั้งค่าห้องและอุปกรณ์'],
            // ['link'=>'admin-majors.php'] <-- ลบออกแล้ว
            ['link'=>'admin-pending.php', 'icon'=>'fa-clock', 'title'=>'รายการรออนุมัติ', 'desc'=>'ตรวจสอบคำขอจอง'],
            ['link'=>'admin-settings.php', 'icon'=>'fa-sliders', 'title'=>'ตั้งค่าระบบ', 'desc'=>'ชื่อเว็บ, โลโก้, สี'],
            ['link'=>'booking_logs.php', 'icon'=>'fa-file-waveform', 'title'=>'Log การใช้งาน', 'desc'=>'ประวัติการกระทำ'],
        ];
        foreach($menus as $m): 
      ?>
      <a href="<?= $m['link'] ?>" class="group block bg-white border border-gray-100 rounded-xl p-5 hover:shadow-xl hover:border-orange-200 transition-all duration-300 transform hover:-translate-y-1">
        <div class="flex items-center justify-between mb-3">
            <div class="p-3 bg-orange-50 rounded-lg text-theme transition-colors" style="color: <?= $sys['system_color'] ?>">
                <i class="fa-solid <?= $m['icon'] ?> text-xl"></i>
            </div>
            <i class="fa-solid fa-chevron-right text-gray-300 group-hover:text-theme"></i>
        </div>
        <h4 class="text-lg font-bold text-gray-700"><?= $m['title'] ?></h4>
        <p class="text-sm text-gray-500 mt-1"><?= $m['desc'] ?></p>
      </a>
      <?php endforeach; ?>
    </div>
  </main>

  <footer class="bg-white border-t border-gray-200 mt-auto py-6 text-center text-sm text-gray-500">
    <?= htmlspecialchars($sys['footer_credit'] ?? '© 2025 Booking System') ?>
  </footer>

  <script>
    const ctx = document.getElementById('bookingChart').getContext('2d');
    const themeColor = '<?= $sys['system_color'] ?>';
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
          label: 'จำนวนการจอง (ครั้ง)',
          data: <?= json_encode($data) ?>,
          backgroundColor: themeColor + 'B3', // Opacity 70%
          borderColor: themeColor,
          borderWidth: 1,
          borderRadius: 4
        }]
      },
      options: { scales: { y: { beginAtZero: true } } }
    });
  </script>
</body>
</html>