<?php
require_once "db.php";
session_start();
if (!isset($_SESSION["user_id"]) || ($_SESSION['role']??'') !== 'admin') { header("Location: login.php"); exit; }

$t_logs = DB_PREFIX . 'booking_logs';

// Pagination
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Count
$count_res = $conn->query("SELECT COUNT(*) as cnt FROM $t_logs");
$total_rows = $count_res->fetch_assoc()['cnt'];
$total_pages = ceil($total_rows / $limit);

// Fetch
$res = $conn->query("SELECT * FROM $t_logs ORDER BY action_time DESC LIMIT $offset, $limit");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8"><title>System Logs</title>
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
    <h2 class="text-2xl font-bold text-gray-800 mb-6"><i class="fa-solid fa-clock-rotate-left mr-2 text-gray-500"></i>ประวัติการใช้งาน (Logs)</h2>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="bg-gray-50 text-gray-500 text-xs uppercase border-b border-gray-100">
                    <th class="p-3 w-40">เวลา</th>
                    <th class="p-3 w-40">ผู้ใช้งาน</th>
                    <th class="p-3 w-40">กิจกรรม</th>
                    <th class="p-3">รายละเอียด</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php while($row = $res->fetch_assoc()): ?>
                <tr class="hover:bg-gray-50">
                    <td class="p-3 text-gray-500 whitespace-nowrap"><?= date('d/m/Y H:i', strtotime($row['action_time'])) ?></td>
                    <td class="p-3 font-medium text-gray-800"><?= htmlspecialchars($row['user_name']) ?></td>
                    <td class="p-3"><span class="px-2 py-0.5 bg-gray-100 text-gray-600 rounded text-xs font-bold border border-gray-200"><?= htmlspecialchars($row['action_type']) ?></span></td>
                    <td class="p-3 text-gray-600 truncate max-w-xs" title="<?= htmlspecialchars($row['details']) ?>">
                        <?= htmlspecialchars($row['details']) ?>
                        <?php if($row['room_name']) echo " <span class='text-gray-400'>(" . htmlspecialchars($row['room_name']) . ")</span>"; ?>
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
                <a href="?page=<?= $i ?>" class="px-4 py-2 text-sm <?= $i==$page ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-50' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</main>
</body>
</html>