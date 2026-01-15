<?php
require_once "db.php";
session_start();

if (!isset($_SESSION["user_id"])) { header("Location: login.php"); exit; }

$user_id = $_SESSION["user_id"];
$t_res = DB_PREFIX . 'reservation';
$t_rooms = DB_PREFIX . 'rooms';

// --- Handle Cancel ---
if (isset($_GET['cancel'])) {
    $id = (int) $_GET['cancel'];
    $stmt = $conn->prepare("UPDATE $t_res SET status = 9 WHERE id = ? AND member_id = ? AND status != 9");
    $stmt->bind_param("ii", $id, $user_id);
    if ($stmt->execute()) { header("Location: my-bookings.php?msg=cancelled"); exit; }
}

// --- Pagination Setup ---
$limit = 20; // จำนวนรายการต่อหน้า
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Count Total
$sql_count = "SELECT COUNT(*) as total FROM $t_res WHERE member_id = ?";
$stmt_c = $conn->prepare($sql_count);
$stmt_c->bind_param("i", $user_id);
$stmt_c->execute();
$total_rows = $stmt_c->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Fetch Data with Limit
$sql = "SELECT r.*, rm.name as room_name, rm.color as room_color 
        FROM $t_res r 
        LEFT JOIN $t_rooms rm ON r.room_id = rm.id 
        WHERE r.member_id = ? 
        ORDER BY r.begin DESC 
        LIMIT ?, ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $offset, $limit);
$stmt->execute();
$result = $stmt->get_result();

function getStatusBadge($status, $approve) {
    if ($status == 9) return '<span class="bg-gray-100 text-gray-500 px-2 py-1 rounded-md text-xs font-bold">ยกเลิกแล้ว</span>';
    if ($approve == 0) return '<span class="bg-yellow-50 text-yellow-600 border border-yellow-100 px-2 py-1 rounded-md text-xs font-bold"><i class="fa-regular fa-clock mr-1"></i>รออนุมัติ</span>';
    if ($approve == 1) return '<span class="bg-green-50 text-green-600 border border-green-100 px-2 py-1 rounded-md text-xs font-bold"><i class="fa-solid fa-check mr-1"></i>อนุมัติ</span>';
    return '<span class="bg-red-50 text-red-600 border border-red-100 px-2 py-1 rounded-md text-xs font-bold">ไม่อนุมัติ</span>';
}
function ThaiDate($strDate) {
    $strYear = date("Y",strtotime($strDate))+543;
    $strDay= date("j",strtotime($strDate));
    $strMonthCut = Array("","ม.ค.","ก.พ.","มี.ค.","เม.ย.","พ.ค.","มิ.ย.","ก.ค.","ส.ค.","ก.ย.","ต.ค.","พ.ย.","ธ.ค.");
    $strMonthThai=$strMonthCut[date("n",strtotime($strDate))];
    $strTime = date("H:i",strtotime($strDate));
    return "$strDay $strMonthThai $strYear ($strTime น.)";
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8"><title>รายการของฉัน</title>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style> body { font-family: 'Kanit', sans-serif; background-color: #F8FAFC; } </style>
</head>
<body class="flex flex-col min-h-screen">
<?php include 'header.php'; ?>
<?php include 'navbar.php'; ?>

<main class="flex-grow max-w-7xl mx-auto w-full px-2 sm:px-6 lg:px-8 pt-4 pb-12 sm:py-6">
    <div class="flex items-center justify-between mb-4 px-1">
        <h2 class="text-xl sm:text-2xl font-bold text-gray-800 flex items-center gap-2">
            <span class="bg-[var(--primary)] text-white w-8 h-8 rounded-lg flex items-center justify-center text-sm shadow-sm"><i class="fa-solid fa-clock-rotate-left"></i></span>
            <span class="text-base sm:text-2xl">ประวัติการจอง</span>
        </h2>
        <div class="text-right">
             <span class="text-xs font-bold text-gray-400 bg-gray-100 px-2 py-1 rounded-md">ทั้งหมด <?= $total_rows ?> รายการ</span>
        </div>
    </div>

    <?php if($result->num_rows == 0): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
            <p class="text-gray-500">ยังไม่มีประวัติการจอง</p>
        </div>
    <?php else: ?>
        <div class="hidden sm:block bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-4">
            <table class="w-full text-left border-collapse">
                <thead><tr class="bg-gray-50 text-gray-500 text-xs uppercase border-b border-gray-100"><th class="p-4">ห้อง</th><th class="p-4">หัวข้อ</th><th class="p-4">เวลา</th><th class="p-4 text-center">สถานะ</th><th class="p-4 text-right">จัดการ</th></tr></thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="p-4"><div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full" style="background-color: <?= $row['room_color'] ?>"></span><span class="font-medium text-gray-700"><?= htmlspecialchars($row['room_name']) ?></span></div></td>
                        <td class="p-4 font-medium text-gray-800"><?= htmlspecialchars($row['topic']) ?></td>
                        <td class="p-4 text-gray-600"><?= ThaiDate($row['begin']) ?><br><span class="text-xs text-gray-400">ถึง <?= ThaiDate($row['end']) ?></span></td>
                        <td class="p-4 text-center"><?= getStatusBadge($row['status'], $row['approve']) ?></td>
                        <td class="p-4 text-right">
                            <?php if($row['status'] != 9 && strtotime($row['begin']) > time()): ?>
                                <a href="edit-booking.php?id=<?= $row['id'] ?>" class="text-blue-600 hover:bg-blue-50 px-2 py-1 rounded mr-1"><i class="fa-solid fa-pen"></i></a>
                                <button onclick="confirmCancel(<?= $row['id'] ?>)" class="text-red-500 hover:bg-red-50 px-2 py-1 rounded"><i class="fa-solid fa-trash"></i></button>
                            <?php else: ?><span class="text-gray-300">-</span><?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="sm:hidden space-y-3 mb-4">
            <?php $result->data_seek(0); while($row = $result->fetch_assoc()): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 relative overflow-hidden">
                <div class="absolute left-0 top-0 bottom-0 w-1" style="background-color: <?= $row['room_color'] ?>"></div>
                <div class="flex justify-between items-start mb-2 pl-2"><span class="text-xs font-bold text-gray-400 uppercase"><?= htmlspecialchars($row['room_name']) ?></span><?= getStatusBadge($row['status'], $row['approve']) ?></div>
                <h3 class="font-bold text-gray-800 text-base mb-1 pl-2"><?= htmlspecialchars($row['topic']) ?></h3>
                <div class="pl-2 mt-2 text-sm text-gray-600 space-y-1">
                    <div class="flex items-center gap-2"><i class="fa-regular fa-calendar text-gray-400 w-4"></i><span><?= ThaiDate($row['begin']) ?></span></div>
                </div>
                <?php if($row['status'] != 9 && strtotime($row['begin']) > time()): ?>
                <div class="mt-3 pt-3 border-t border-gray-100 flex justify-end gap-3 pl-2">
                    <a href="edit-booking.php?id=<?= $row['id'] ?>" class="text-sm font-bold text-blue-600"><i class="fa-solid fa-pen"></i> แก้ไข</a>
                    <button onclick="confirmCancel(<?= $row['id'] ?>)" class="text-sm font-bold text-red-500"><i class="fa-solid fa-trash"></i> ยกเลิก</button>
                </div>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        </div>

        <?php if($total_pages > 1): ?>
        <div class="flex justify-center mt-6">
            <div class="flex bg-white rounded-lg shadow-sm border border-gray-200 divide-x divide-gray-200">
                <?php if($page > 1): ?><a href="?page=<?= $page-1 ?>" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">ก่อนหน้า</a><?php endif; ?>
                <?php for($i=1; $i<=$total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>" class="px-4 py-2 text-sm <?= $i==$page ? 'bg-[var(--primary)] text-white font-bold' : 'text-gray-600 hover:bg-gray-50' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if($page < $total_pages): ?><a href="?page=<?= $page+1 ?>" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">ถัดไป</a><?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    <?php endif; ?>
</main>

<script>
function confirmCancel(id) {
    Swal.fire({ title: 'ยืนยันการยกเลิก?', text: "คุณต้องการยกเลิกการจองนี้ใช่หรือไม่", icon: 'warning', showCancelButton: true, confirmButtonColor: '#EF4444', confirmButtonText: 'ใช่, ยกเลิกเลย', cancelButtonText: 'ไม่ยกเลิก' }).then((result) => { if (result.isConfirmed) { window.location.href = 'my-bookings.php?cancel=' + id; } })
}
<?php if (isset($_GET['msg']) && $_GET['msg'] == 'cancelled'): ?> Swal.fire({ icon: 'success', title: 'ยกเลิกสำเร็จ', timer: 2000, showConfirmButton: false }); <?php endif; ?>
</script>
</body>
</html>