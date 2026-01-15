<?php
require_once "db.php";
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset("utf8");

$t_res = DB_PREFIX . 'reservation';
$t_rooms = DB_PREFIX . 'rooms';
$t_users = DB_PREFIX . 'auth_users';

$today = date('Y-m-d');

// ดึงเวลาเริ่มและจบมาแสดง
$sql = "SELECT r.topic, r.begin, r.end, rm.name AS room_name, rm.color, u.name AS user_name
        FROM $t_res r
        LEFT JOIN $t_rooms rm ON rm.id = r.room_id
        LEFT JOIN $t_users u ON u.id = r.member_id
        WHERE DATE(r.begin) = ? AND r.status = 1
        ORDER BY r.begin ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$bookings = $stmt->get_result();
?>

<?php if ($bookings->num_rows === 0): ?>
  <div class="text-center py-8 text-gray-400">
      <i class="fa-regular fa-calendar-xmark text-3xl mb-2 opacity-50"></i>
      <p class="text-sm">ไม่มีรายการจองวันนี้</p>
  </div>
<?php else: ?>
  <div class="space-y-3">
  <?php while ($b = $bookings->fetch_assoc()): 
      // จัดรูปแบบเวลา เช่น 09:00 - 10:00
      $timeStr = date('H:i', strtotime($b['begin'])) . " - " . date('H:i', strtotime($b['end']));
  ?>
    <div class="flex gap-3 relative pl-3 group">
      <div class="absolute left-0 top-1 bottom-1 w-1 rounded-full" style="background-color: <?= $b['color'] ?>;"></div>
      
      <div class="flex-1">
          <div class="flex justify-between items-start">
              <span class="text-xs font-bold text-gray-400 bg-gray-50 px-1.5 py-0.5 rounded border border-gray-100 whitespace-nowrap">
                  <?= $timeStr ?>
              </span>
          </div>
          <div class="mt-1">
              <p class="text-sm font-bold text-gray-800 leading-tight group-hover:text-theme transition-colors">
                  <?= htmlspecialchars($b['topic']) ?>
              </p>
              <div class="flex items-center gap-2 mt-1 text-xs text-gray-500">
                  <span class="truncate max-w-[100px]"><i class="fa-solid fa-location-dot mr-1 text-gray-300"></i><?= htmlspecialchars($b['room_name']) ?></span>
                  <span class="w-1 h-1 bg-gray-300 rounded-full"></span>
                  <span class="truncate max-w-[80px]"><?= htmlspecialchars($b['user_name']) ?></span>
              </div>
          </div>
      </div>
    </div>
  <?php endwhile; ?>
  </div>
<?php endif; ?>