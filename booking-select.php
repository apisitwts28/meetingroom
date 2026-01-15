<?php
require_once "db.php";
session_start();
if (!isset($_SESSION["user_id"])) { header("Location: login.php"); exit; }

$t_rooms = DB_PREFIX . 'rooms';
$rooms = [];
$res = $conn->query("SELECT * FROM $t_rooms ORDER BY is_active DESC, name ASC");
if ($res) { while ($row = $res->fetch_assoc()) { $rooms[] = $row; } }
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8"><title>เลือกห้องประชุม</title>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style> body { font-family: 'Kanit', sans-serif; background-color: #F8FAFC; } </style>
</head>
<body class="flex flex-col min-h-screen">
<?php include 'header.php'; ?>
<?php include 'navbar.php'; ?>

<main class="flex-grow max-w-7xl mx-auto w-full px-2 sm:px-6 lg:px-8 pt-0 pb-20 sm:py-6">
    
    <div class="hidden sm:block mb-6 border-b border-gray-200 pb-4 mt-4">
        <h2 class="text-2xl font-bold text-gray-800">จองห้องประชุม</h2>
        <p class="text-gray-500 text-sm">เลือกห้องที่ต้องการใช้งาน</p>
    </div>

    <div class="sm:hidden py-3 px-1">
        <h2 class="text-lg font-bold text-gray-800">เลือกห้องประชุม</h2>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach($rooms as $r): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow group relative <?= $r['is_active']?'':'opacity-75 grayscale' ?>">
            
            <?php if(!$r['is_active']): ?>
                <div class="absolute inset-0 bg-gray-100/50 z-10 flex items-center justify-center">
                    <span class="bg-gray-800 text-white px-3 py-1 rounded text-xs font-bold">ปิดปรับปรุง</span>
                </div>
            <?php endif; ?>

            <div class="h-32 sm:h-40 bg-gray-200 relative">
                <?php if($r['image']): ?>
                    <img src="<?= $r['image'] ?>" class="w-full h-full object-cover">
                <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center bg-gray-100 text-gray-300">
                        <i class="fa-solid fa-image text-3xl"></i>
                    </div>
                <?php endif; ?>
                <div class="absolute top-2 right-2 px-2 py-1 rounded-md bg-white/90 backdrop-blur text-xs font-bold shadow-sm flex items-center gap-1">
                    <i class="fa-solid fa-user-group text-gray-400"></i> <?= $r['capacity'] ?>
                </div>
            </div>

            <div class="p-4">
                <h3 class="font-bold text-gray-800 text-lg mb-1 group-hover:text-[var(--primary)] transition-colors"><?= htmlspecialchars($r['name']) ?></h3>
                <p class="text-xs text-gray-500 line-clamp-2 mb-4 h-8"><?= htmlspecialchars($r['detail'] ?? '-') ?></p>
                
                <?php if($r['is_active']): ?>
                    <a href="booking-form.php?room_id=<?= $r['id'] ?>" class="block w-full text-center bg-[var(--primary)] text-white py-2.5 rounded-lg font-bold shadow-sm hover:brightness-90 transition-all text-sm">
                        จองห้องนี้
                    </a>
                <?php else: ?>
                    <button disabled class="block w-full text-center bg-gray-100 text-gray-400 py-2.5 rounded-lg font-bold text-sm cursor-not-allowed">
                        ไม่ว่าง
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</main>

<div class="md:hidden h-16"></div>
</body>
</html>