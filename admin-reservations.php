<?php
require_once "db.php";
session_start();
if (!isset($_SESSION["user_id"]) || ($_SESSION['role']??'') !== 'admin') { header("Location: login.php"); exit; }

$t_res = DB_PREFIX . 'reservation';
$t_rooms = DB_PREFIX . 'rooms';
$t_users = DB_PREFIX . 'auth_users';

// Actions
if(isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    $conn->query("UPDATE $t_res SET approve=1 WHERE id=$id");
    header("Location: admin-reservations.php"); exit;
}
if(isset($_GET['reject'])) {
    $id = (int)$_GET['reject'];
    $conn->query("UPDATE $t_res SET approve=2 WHERE id=$id");
    header("Location: admin-reservations.php"); exit;
}

// Pagination
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Count
$count_res = $conn->query("SELECT COUNT(*) as cnt FROM $t_res");
$total_rows = $count_res->fetch_assoc()['cnt'];
$total_pages = ceil($total_rows / $limit);

// Fetch
$sql = "SELECT r.*, rm.name as room_name, u.name as user_name 
        FROM $t_res r 
        LEFT JOIN $t_rooms rm ON r.room_id = rm.id 
        LEFT JOIN $t_users u ON r.member_id = u.id 
        ORDER BY r.begin DESC 
        LIMIT $offset, $limit";
$res = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8"><title>จัดการการจอง</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style> body { font-family: 'Kanit', sans-serif; background-color: #F8FAFC; } </style>
</head>
<body class="bg-gray-50 flex flex-col min-h-screen">
<?php include 'header.php'; ?>
<?php include 'navbar.php'; ?>

<main class="flex-grow max-w-7xl mx-auto w-full px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800"><i class="fa-solid fa-list-check mr-2 text-[var(--primary)]"></i>จัดการการจองทั้งหมด</h2>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
        <table class="w-full text-left border-collapse whitespace-nowrap">
            <thead>
                <tr class="bg-gray-50 text-gray-500 text-xs uppercase border-b border-gray-100">
                    <th class="p-4">ผู้จอง</th>
                    <th class="p-4">ห้อง/หัวข้อ</th>
                    <th class="p-4">วันเวลา</th>
                    <th class="p-4 text-center">สถานะ</th>
                    <th class="p-4 text-right">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                <?php while($row = $res->fetch_assoc()): ?>
                <tr class="hover:bg-gray-50">
                    <td class="p-4 font-bold text-gray-700"><?= htmlspecialchars($row['user_name']) ?></td>
                    <td class="p-4">
                        <div class="text-xs text-gray-400"><?= htmlspecialchars($row['room_name']) ?></div>
                        <div class="font-medium text-gray-800"><?= htmlspecialchars($row['topic']) ?></div>
                    </td>
                    <td class="p-4 text-gray-600">
                        <?= date('d/m/Y H:i', strtotime($row['begin'])) ?><br>
                        - <?= date('H:i', strtotime($row['end'])) ?>
                    </td>
                    <td class="p-4 text-center">
                        <?php 
                            if($row['status']==9) echo '<span class="px-2 py-1 bg-gray-100 text-gray-500 rounded text-xs">ยกเลิก</span>';
                            else if($row['approve']==0) echo '<span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs">รออนุมัติ</span>';
                            else if($row['approve']==1) echo '<span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">อนุมัติ</span>';
                            else echo '<span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs">ไม่อนุมัติ</span>';
                        ?>
                    </td>
                    <td class="p-4 text-right space-x-1">
                        <?php if($row['status']!=9 && $row['approve']==0): ?>
                            <a href="?approve=<?= $row['id'] ?>" class="px-2 py-1 bg-green-600 text-white rounded hover:bg-green-700 text-xs">อนุมัติ</a>
                            <a href="?reject=<?= $row['id'] ?>" class="px-2 py-1 bg-red-500 text-white rounded hover:bg-red-600 text-xs">ปฏิเสธ</a>
                        <?php endif; ?>
                        <a href="admin-edit-booking.php?id=<?= $row['id'] ?>" class="px-2 py-1 bg-blue-50 text-blue-600 rounded hover:bg-blue-100 text-xs"><i class="fa-solid fa-pen"></i></a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <?php if($total_pages > 1): ?>
    <div class="flex justify-center mt-6">
        <div class="flex bg-white rounded-lg shadow-sm border border-gray-200 divide-x divide-gray-200">
            <?php for($i=1; $i<=$total_pages; $i++): ?>
                <a href="?page=<?= $i ?>" class="px-4 py-2 text-sm <?= $i==$page ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</main>
</body>
</html>