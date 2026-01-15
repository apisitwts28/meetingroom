<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($sys)) { if (file_exists('db.php')) require_once 'db.php'; }

$themeColor = $sys['system_color'] ?? '#4F46E5'; 
$logo_path = !empty($sys['system_logo']) ? $sys['system_logo'] : 'uploads/logo.png';
?>
<style>
    :root {
        --primary: <?= $themeColor ?>;
        --primary-dark: color-mix(in srgb, var(--primary), black 20%);
    }
    .bg-brand-gradient { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); }
</style>

<header class="bg-brand-gradient shadow-md sticky top-0 z-50 text-white transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-12 sm:h-20">
            
            <a href="index.php" class="flex items-center gap-2 sm:gap-3 group">
                <div class="w-8 h-8 sm:w-11 sm:h-11 bg-white rounded-lg sm:rounded-xl flex items-center justify-center shadow-sm p-1 sm:p-1.5 flex-shrink-0">
                    <img src="<?= $logo_path ?>" class="w-full h-full object-contain" onerror="this.style.display='none'; document.getElementById('fallback-icon').style.display='flex'">
                    <div id="fallback-icon" class="hidden w-full h-full items-center justify-center" style="color: var(--primary)">
                        <i class="fa-solid fa-building text-sm sm:text-lg"></i>
                    </div>
                </div>
                <div class="flex flex-col min-w-0 justify-center">
                    <h1 class="font-bold text-sm sm:text-xl leading-tight tracking-wide drop-shadow-sm truncate">
                        <?= htmlspecialchars($sys['system_name'] ?? 'Booking System') ?>
                    </h1>
                    <?php if(!empty($sys['system_desc'])): ?>
                        <p class="text-[10px] sm:text-sm text-white/80 font-light truncate max-w-[200px] sm:max-w-md">
                            <?= htmlspecialchars($sys['system_desc']) ?>
                        </p>
                    <?php endif; ?>
                </div>
            </a>

            <div class="flex items-center gap-2 sm:gap-3">
                
                <?php if (isset($_SESSION["user_id"])): ?>
                    <div class="relative group">
                        <button class="flex items-center gap-2 sm:gap-3 pl-2 sm:pl-4 pr-1 py-0.5 sm:py-1 bg-white/10 hover:bg-white/20 border border-white/20 rounded-full backdrop-blur-sm transition-all">
                            <div class="text-right hidden sm:block">
                                <p class="text-xs font-bold text-white leading-none"><?= htmlspecialchars($_SESSION["user_name"]) ?></p>
                                <p class="text-[10px] text-white/70 uppercase mt-0.5">
                                    <?= ($_SESSION['role']??'') === 'admin' ? 'Admin' : 'User' ?>
                                </p>
                            </div>
                            <?php $img = !empty($_SESSION['profile_image']) ? $_SESSION['profile_image'] : ($sys['default_profile'] ?? 'uploads/profiles/default.png'); ?>
                            <img src="<?= $img ?>" class="w-7 h-7 sm:w-9 sm:h-9 rounded-full object-cover border-2 border-white/50 shadow-sm bg-white">
                        </button>
                        
                        <div class="absolute right-0 top-full mt-2 w-56 bg-white rounded-xl shadow-xl border border-gray-100 p-2 invisible opacity-0 translate-y-2 group-hover:visible group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-200 z-50">
                            <div class="px-3 py-2 border-b border-gray-100 sm:hidden">
                                <p class="text-xs text-gray-400">บัญชีผู้ใช้</p>
                                <p class="text-sm font-bold text-gray-800 truncate"><?= htmlspecialchars($_SESSION["user_name"]) ?></p>
                            </div>
                            
                            <a href="my-bookings.php" class="flex items-center gap-3 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 rounded-lg">
                                <i class="fa-solid fa-clock-rotate-left w-5 text-center"></i> ประวัติการจอง
                            </a>
                            
                            <a href="profile.php" class="flex items-center gap-3 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 rounded-lg">
                                <i class="fa-solid fa-user-pen w-5 text-center"></i> แก้ไขข้อมูลส่วนตัว
                            </a>

                            <?php if(($_SESSION['role']??'')==='admin'): ?>
                                <a href="admin-dashboard.php" class="flex items-center gap-3 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 rounded-lg">
                                    <i class="fa-solid fa-gauge w-5 text-center"></i> แดชบอร์ด
                                </a>
                            <?php endif; ?>
                            
                            <div class="border-t border-gray-100 my-1"></div>
                            
                            <a href="logout.php" class="flex items-center gap-3 px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg">
                                <i class="fa-solid fa-right-from-bracket w-5 text-center"></i> ออกจากระบบ
                            </a>
                        </div>
                    </div>

                <?php else: ?>
                    <a href="login.php" class="flex items-center gap-2 px-4 py-1.5 sm:px-6 sm:py-2 bg-white text-[var(--primary)] rounded-full text-xs sm:text-sm font-bold shadow-lg hover:shadow-xl hover:bg-gray-50 transition-all">
                        <i class="fa-solid fa-right-to-bracket"></i> <span>เข้าสู่ระบบ</span>
                    </a>
                <?php endif; ?>

            </div>
        </div>
    </div>
</header>