<?php
if (!isset($_SESSION)) session_start();
// โหลดค่าสี
if (!isset($sys)) { if (file_exists('db.php')) require_once 'db.php'; }
$current = basename($_SERVER["PHP_SELF"]);
$navColor = $sys['system_color'] ?? '#4F46E5';
?>
<style>
    /* ซ่อน Scrollbar แต่ยังเลื่อนได้ */
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<nav class="bg-white border-b border-gray-200 sticky top-12 sm:top-20 z-40 shadow-sm">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <div class="flex items-center space-x-2 sm:space-x-4 overflow-x-auto no-scrollbar py-1 sm:py-0">
      
      <a href="index.php" 
         class="whitespace-nowrap px-3 sm:px-4 py-3 text-sm transition-all flex items-center gap-2 border-b-2 flex-shrink-0 <?= $current=='index.php'?'font-bold bg-gray-50':'text-gray-500 hover:bg-gray-50 border-transparent' ?>"
         style="<?= $current=='index.php' ? "color: $navColor; border-color: $navColor;" : "hover:color: $navColor;" ?>">
          <i class="fa-solid fa-house"></i> <span>หน้าแรก</span>
      </a>

      <a href="booking-select.php" 
         class="whitespace-nowrap px-3 sm:px-4 py-3 text-sm transition-all flex items-center gap-2 border-b-2 flex-shrink-0 <?= $current=='booking-select.php'?'font-bold bg-gray-50':'text-gray-500 hover:bg-gray-50 border-transparent' ?>"
         style="<?= $current=='booking-select.php' ? "color: $navColor; border-color: $navColor;" : "hover:color: $navColor;" ?>">
          <i class="fa-regular fa-calendar-plus"></i> <span>จองห้องประชุม</span>
      </a>

      <?php if (isset($_SESSION["user_id"])): ?>
          <a href="my-bookings.php" 
             class="whitespace-nowrap px-3 sm:px-4 py-3 text-sm transition-all flex items-center gap-2 border-b-2 flex-shrink-0 <?= $current=='my-bookings.php'?'font-bold bg-gray-50':'text-gray-500 hover:bg-gray-50 border-transparent' ?>"
             style="<?= $current=='my-bookings.php' ? "color: $navColor; border-color: $navColor;" : "hover:color: $navColor;" ?>">
              <i class="fa-solid fa-list-check"></i> <span>รายการของฉัน</span>
          </a>
      <?php endif; ?>

    </div>
  </div>
</nav>