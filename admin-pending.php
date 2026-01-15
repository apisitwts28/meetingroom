<?php
// admin-pending.php
require_once "db.php";
session_start();

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? '') !== "admin") { header("Location: login.php"); exit; }

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset("utf8");

$t_res = DB_PREFIX . 'reservation';
$t_users = DB_PREFIX . 'auth_users';
$t_rooms = DB_PREFIX . 'rooms';
$t_logs = DB_PREFIX . 'booking_logs';

if (isset($_GET['approve'])) {
  $id = (int)$_GET['approve'];
  $conn->query("UPDATE $t_res SET approve = 1 WHERE id = $id");
  // Log
  $conn->query("INSERT INTO $t_logs (user_name, action_type, details) VALUES ('{$_SESSION['user_name']}', 'อนุมัติการจอง', 'ID: $id')");
  header("Location: admin-pending.php?msg=approved"); exit;
}
if (isset($_GET['reject'])) {
  $id = (int)$_GET['reject'];
  $conn->query("UPDATE $t_res SET approve = 2, status = 9 WHERE id = $id"); // Reject + Cancel
  header("Location: admin-pending.php?msg=rejected"); exit;
}

$sql = "SELECT r.*, u.name AS user_name, u.phone, rm.name AS room_name, rm.color 
        FROM $t_res r
        JOIN $t_users u ON r.member_id = u.id
        JOIN $t_rooms rm ON r.room_id = rm.id
        WHERE r.status = 1 AND r.approve = 0 
        ORDER BY r.begin ASC";
$result = $conn->query($sql);

// ดึงข้อมูลเข้า Array เพื่อวนลูป 2 รอบ (Mobile/Desktop)
$pending_list = [];
while ($row = $result->fetch_assoc()) {
    $pending_list[] = $row;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8"><title>รายการรออนุมัติ</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>body{font-family:'Kanit',sans-serif;}</style>
</head>
<body class="flex flex-col min-h-screen bg-gray-50">
<?php include 'header.php'; ?>
<?php include 'navbar.php'; ?>

<main class="flex-grow max-w-7xl mx-auto w-full p-4 sm:p-6">
    <div class="flex items-center gap-3 mb-6">
        <div class="p-3 bg-orange-100 rounded-lg text-theme" style="color: <?= $sys['system_color'] ?? '#F59E0B' ?>"><i class="fa-solid fa-clock text-xl"></i></div>
        <h2 class="text-xl sm:text-2xl font-bold text-gray-800">รายการจอง <span class="text-theme" style="color: <?= $sys['system_color'] ?? '#F59E0B' ?>">รออนุมัติ</span></h2>
    </div>

    <div class="md:hidden space-y-4">
        <?php if (count($pending_list) > 0): ?>
            <?php foreach ($pending_list as $row): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <div class="flex justify-between items-start mb-3 border-b border-gray-50 pb-3">
                    <div>
                        <div class="text-sm font-bold text-gray-800">
                            <?= date('d/m/Y', strtotime($row['begin'])) ?>
                        </div>
                        <div class="text-xs text-gray-500">
                            <?= date('H:i', strtotime($row['begin'])) ?> - <?= date('H:i', strtotime($row['end'])) ?>
                        </div>
                    </div>
                    <span class="px-2 py-1 rounded text-[10px] font-bold text-white" style="background-color: <?= $row['color'] ?>">
                        <?= htmlspecialchars($row['room_name']) ?>
                    </span>
                </div>
                
                <div class="mb-4 space-y-1">
                    <p class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($row['topic']) ?></p>
                    <div class="flex items-center gap-2 text-xs text-gray-500">
                        <i class="fa-solid fa-user"></i> <?= htmlspecialchars($row['user_name']) ?>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-gray-500">
                        <i class="fa-solid fa-phone"></i> <?= htmlspecialchars($row['phone']) ?>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <button onclick="confirmApprove(<?= $row['id'] ?>)" class="flex items-center justify-center gap-2 bg-green-50 text-green-600 border border-green-200 py-2 rounded-lg font-bold text-sm hover:bg-green-100 transition">
                        <i class="fa-solid fa-check"></i> อนุมัติ
                    </button>
                    <button onclick="confirmReject(<?= $row['id'] ?>)" class="flex items-center justify-center gap-2 bg-red-50 text-red-600 border border-red-200 py-2 rounded-lg font-bold text-sm hover:bg-red-100 transition">
                        <i class="fa-solid fa-xmark"></i> ปฏิเสธ
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="bg-white p-10 text-center text-gray-400 rounded-xl border border-gray-200">
                <i class="fa-regular fa-folder-open text-4xl mb-2 opacity-30"></i>
                <p>✅ ไม่มีรายการรออนุมัติ</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="hidden md:block bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                <tr>
                    <th class="p-4">วันที่/เวลา</th>
                    <th class="p-4">ห้อง</th>
                    <th class="p-4">หัวข้อ</th>
                    <th class="p-4">ผู้จอง</th>
                    <th class="p-4 text-center">ดำเนินการ</th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($pending_list) > 0): ?>
                <?php foreach ($pending_list as $row): ?>
                <tr class="border-b hover:bg-gray-50">
                    <td class="p-4">
                        <div class="font-bold"><?= date('d/m/Y', strtotime($row['begin'])) ?></div>
                        <div class="text-xs"><?= date('H:i', strtotime($row['begin'])) ?> - <?= date('H:i', strtotime($row['end'])) ?></div>
                    </td>
                    <td class="p-4"><span class="font-bold" style="color: <?= $row['color'] ?>"><?= htmlspecialchars($row['room_name']) ?></span></td>
                    <td class="p-4"><?= htmlspecialchars($row['topic']) ?></td>
                    <td class="p-4">
                        <div class="font-bold text-gray-700"><?= htmlspecialchars($row['user_name']) ?></div>
                        <div class="text-xs text-gray-400"><i class="fa-solid fa-phone text-[10px] mr-1"></i><?= htmlspecialchars($row['phone']) ?></div>
                    </td>
                    <td class="p-4 text-center">
                        <div class="flex justify-center gap-2">
                            <button onclick="confirmApprove(<?= $row['id'] ?>)" class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600 shadow-sm transition transform hover:-translate-y-0.5"><i class="fa-solid fa-check mr-1"></i> อนุมัติ</button>
                            <button onclick="confirmReject(<?= $row['id'] ?>)" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 shadow-sm transition transform hover:-translate-y-0.5"><i class="fa-solid fa-xmark mr-1"></i> ปฏิเสธ</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" class="p-10 text-center text-gray-400">✅ ไม่มีรายการรออนุมัติ</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
<script>
function confirmApprove(id) { Swal.fire({ title: 'ยืนยันอนุมัติ?', icon:'question', showCancelButton:true, confirmButtonText:'ใช่', cancelButtonText:'ไม่', confirmButtonColor: '#10B981' }).then(r=>{if(r.isConfirmed) location.href=`?approve=${id}`}) }
function confirmReject(id) { Swal.fire({ title: 'ปฏิเสธคำขอ?', icon:'warning', showCancelButton:true, confirmButtonColor:'#EF4444', confirmButtonText:'ปฏิเสธ', cancelButtonText:'ยกเลิก' }).then(r=>{if(r.isConfirmed) location.href=`?reject=${id}`}) }
const u = new URLSearchParams(window.location.search);
if(u.get('msg')==='approved') Swal.fire('สำเร็จ','อนุมัติเรียบร้อย','success');
if(u.get('msg')==='rejected') Swal.fire('สำเร็จ','ปฏิเสธคำขอแล้ว','success');
</script>
</body>
</html>