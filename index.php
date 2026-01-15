<?php
if (!file_exists('config.php')) { header("Location: install/install.php"); exit(); }
require_once 'db.php';

$t_rooms = DB_PREFIX . 'rooms';
$t_res   = DB_PREFIX . 'reservation';
$rooms = [];
$res = $conn->query("SELECT id, name, color, is_active FROM $t_rooms ORDER BY is_active DESC, name ASC");
if ($res) { while ($row = $res->fetch_assoc()) $rooms[] = $row; }

$today = date('Y-m-d');
$stats_today = 0;
if ($conn->query("SHOW TABLES LIKE '$t_res'")->num_rows > 0) {
    $q_stat = $conn->query("SELECT COUNT(*) as cnt FROM $t_res WHERE DATE(begin) = '$today' AND status = 1");
    $stats_today = $q_stat->fetch_assoc()['cnt'];
}
function ThaiDate($strDate) {
    $strYear = date("Y",strtotime($strDate))+543;
    $strDay= date("j",strtotime($strDate));
    $strMonthCut = Array("","ม.ค.","ก.พ.","มี.ค.","เม.ย.","พ.ค.","มิ.ย.","ก.ค.","ส.ค.","ก.ย.","ต.ค.","พ.ย.","ธ.ค.");
    $strMonthThai=$strMonthCut[date("n",strtotime($strDate))];
    return "$strDay $strMonthThai $strYear";
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8" />
  <title><?= htmlspecialchars($sys['system_name']) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
  <script src="assets/js/calendar.js?v=<?= time() ?>" defer></script>
  <style>
    body { font-family: 'Kanit', sans-serif; background-color: #F8FAFC; }
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
    .fc-toolbar-title { font-size: 1.1rem !important; font-weight: 600; color: #1e293b; }
    .fc-button-primary { background-color: #fff !important; color: #475569 !important; border: 1px solid #e2e8f0 !important; font-weight: 500; }
    .fc-button-active { background-color: var(--primary) !important; color: #fff !important; border-color: var(--primary) !important; }
    @media (max-width: 640px) {
        .fc-header-toolbar { flex-wrap: wrap; gap: 4px; justify-content: space-between; margin-bottom: 0.5rem !important; }
        .fc-button { padding: 4px 8px !important; font-size: 0.75rem !important; }
        .fc-toolbar-title { font-size: 1rem !important; }
    }
  </style>
</head>
<body class="flex flex-col min-h-screen">

<?php include 'header.php'; ?>
<?php include 'navbar.php'; ?>

<main class="flex-grow max-w-7xl mx-auto w-full px-2 sm:px-6 lg:px-8 pt-4 pb-12 sm:py-6">
    
    <div class="flex items-center justify-between mb-4 px-1">
        <h2 class="text-xl sm:text-2xl font-bold text-gray-800 flex items-center gap-2">
            <span class="bg-[var(--primary)] text-white w-8 h-8 rounded-lg flex items-center justify-center text-sm shadow-sm">
                <i class="fa-regular fa-calendar-check"></i>
            </span>
            <span class="text-base sm:text-2xl">ปฏิทินการจอง</span>
        </h2>
        <div class="text-right">
             <span class="text-sm font-bold text-[var(--primary)] bg-[var(--primary)]/10 px-2 py-1 rounded-md">
                <?= ThaiDate(date('Y-m-d')) ?>
             </span>
        </div>
    </div>

    <div class="flex flex-col lg:grid lg:grid-cols-12 gap-6">
        <div class="order-1 lg:col-span-9">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-2 sm:p-6 h-full relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-[var(--primary)] to-slate-200"></div>
                <div id="calendar" class="min-h-[500px] sm:min-h-[650px]"></div>
            </div>
        </div>

        <div class="order-2 lg:col-span-3 space-y-4">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 flex items-center justify-between">
                <div>
                    <p class="text-[10px] text-slate-400 font-bold uppercase">การจองวันนี้</p>
                    <div class="flex items-baseline gap-1">
                        <h2 class="text-2xl font-bold text-[var(--primary)]"><?= $stats_today ?></h2>
                        <span class="text-xs text-slate-500">รายการ</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-3 bg-slate-50 border-b border-slate-100 flex justify-between items-center" onclick="document.getElementById('roomFilterContent').classList.toggle('hidden');">
                    <h3 class="font-bold text-slate-700 text-sm"><i class="fa-solid fa-filter text-slate-400 mr-2"></i> เลือกห้อง</h3>
                    <i class="fa-solid fa-chevron-down text-slate-400"></i>
                </div>
                <div id="roomFilterContent" class="hidden lg:block p-3">
                    <div class="space-y-1 max-h-[200px] overflow-y-auto custom-scrollbar">
                        <?php foreach ($rooms as $room): ?>
                        <label class="flex items-center gap-2 p-2 hover:bg-slate-50 rounded cursor-pointer">
                            <input type="checkbox" class="room-filter w-4 h-4 text-[var(--primary)] rounded focus:ring-[var(--primary)]" value="<?= $room['id'] ?>" checked>
                            <span class="w-2 h-2 rounded-full" style="background-color: <?= htmlspecialchars($room['color']) ?>;"></span>
                            <span class="text-xs text-slate-600 truncate"><?= htmlspecialchars($room['name']) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<footer class="bg-white border-t border-slate-200 mt-auto py-6">
    <div class="max-w-7xl mx-auto px-6 text-center">
        <p class="text-xs text-slate-400"><?= htmlspecialchars($sys['system_name']) ?></p>
    </div>
</footer>

<div id="eventModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden z-50 flex items-center justify-center p-4 transition-opacity duration-200">
  <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden transform scale-95 transition-transform duration-200" id="modalContent">
    <div class="bg-white px-6 py-4 border-b border-slate-100 flex justify-between items-start">
        <div>
            <h3 class="text-lg font-bold text-slate-800 leading-tight" id="modalTitle">-</h3>
            <p class="text-sm text-[var(--primary)] font-medium mt-1 flex items-center gap-2"><i class="fa-solid fa-location-dot"></i> <span id="modalRoom">-</span></p>
        </div>
        <button onclick="closeModal()" class="w-8 h-8 rounded-full bg-slate-50 text-slate-400 hover:bg-slate-100 flex items-center justify-center transition"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="p-6 space-y-4">
      <div class="grid grid-cols-2 gap-4">
          <div class="flex items-start gap-3"><div class="w-8 h-8 rounded-full bg-slate-50 text-slate-400 flex items-center justify-center flex-shrink-0"><i class="fa-regular fa-clock"></i></div><div><p class="text-[10px] text-slate-400 font-bold uppercase">เวลา</p><p class="text-sm font-medium text-slate-700" id="modalTime">-</p></div></div>
          <div class="flex items-start gap-3"><div class="w-8 h-8 rounded-full bg-slate-50 text-slate-400 flex items-center justify-center flex-shrink-0"><i class="fa-regular fa-user"></i></div><div><p class="text-[10px] text-slate-400 font-bold uppercase">ผู้จอง</p><p class="text-sm font-medium text-slate-700" id="modalUser">-</p></div></div>
      </div>
      <div class="pt-4 border-t border-slate-50"><p class="text-[10px] text-slate-400 font-bold uppercase mb-2">รายละเอียด</p><div class="bg-slate-50 p-3 rounded-lg text-sm text-slate-600 italic" id="modalDesc">-</div></div>
    </div>
  </div>
</div>
<script>
function openModal() { document.getElementById('eventModal').classList.remove('hidden'); setTimeout(() => document.getElementById('modalContent').classList.replace('scale-95', 'scale-100'), 10); }
function closeModal() { document.getElementById('modalContent').classList.replace('scale-100', 'scale-95'); setTimeout(() => document.getElementById('eventModal').classList.add('hidden'), 200); }
document.getElementById('checkAll')?.addEventListener('change', function(e) { document.querySelectorAll('.room-filter').forEach(cb => { cb.checked = e.target.checked; cb.dispatchEvent(new Event('change')); }); });
</script>
</body>
</html>