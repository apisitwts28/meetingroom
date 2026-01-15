<?php
require_once "db.php";
session_start();

// ถ้าล็อกอินอยู่แล้ว ให้เด้งไปตาม Role
if (isset($_SESSION['user_id'])) {
    // ✅ แก้กลับเป็น admin-dashboard.php
    header("Location: " . (($_SESSION['role'] ?? '') === 'admin' ? "admin-dashboard.php" : "index.php"));
    exit;
}

$manual_link = $sys['manual_link'] ?? '#';
$has_manual = (!empty($manual_link) && $manual_link !== '#');
$themeColor = $sys['system_color'] ?? '#4F46E5';
$logo_path = !empty($sys['system_logo']) ? $sys['system_logo'] : 'uploads/logo.png';
$has_logo = (!empty($logo_path) && file_exists($logo_path));

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    
    $t_users = DB_PREFIX . 'auth_users';
    $t_logs = DB_PREFIX . 'booking_logs';

    $stmt = $conn->prepare("SELECT id, username, password, name, status, email FROM $t_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row["password"])) {
            $_SESSION["user_id"] = $row["id"];
            $_SESSION["user_name"] = $row["name"]; 
            $_SESSION["user_email"] = $row["email"];
            $_SESSION["role"] = ($row["status"] == 1) ? "admin" : "user";
            
            // Log
            if($conn->query("SHOW TABLES LIKE '$t_logs'")->num_rows > 0) {
                $conn->query("INSERT INTO $t_logs (user_name, action_type) VALUES ('{$row['name']}', 'Login')");
            }
            
            // ✅ แก้กลับเป็น admin-dashboard.php
            header("Location: " . ($_SESSION["role"] === "admin" ? "admin-dashboard.php" : "index.php"));
            exit;
        } else { $error = "รหัสผ่านไม่ถูกต้อง"; }
    } else { $error = "ไม่พบอีเมลผู้ใช้งานนี้ในระบบ"; }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8"><title>เข้าสู่ระบบ - <?= htmlspecialchars($sys['system_name']) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
      body { font-family: 'Kanit', sans-serif; background-color: #F8FAFC; }
      :root { --primary: <?= $themeColor ?>; }
      .text-primary { color: var(--primary); }
      .bg-primary { background-color: var(--primary); }
      .btn-primary {
          background: linear-gradient(135deg, var(--primary), color-mix(in srgb, var(--primary), black 10%));
          transition: all 0.3s ease;
      }
      .btn-primary:hover {
          box-shadow: 0 10px 20px -5px color-mix(in srgb, var(--primary), white 50%);
          transform: translateY(-2px);
      }
      .bg-pattern {
          background-image: radial-gradient(#cbd5e1 1px, transparent 1px);
          background-size: 24px 24px;
      }
      .focus-ring:focus { --tw-ring-color: var(--primary); }
  </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-pattern relative overflow-hidden">
  
  <?php if ($has_manual): ?>
  <a href="<?= $manual_link ?>" target="_blank" class="absolute top-4 right-4 bg-white/80 backdrop-blur border border-gray-200 text-gray-600 px-4 py-2 rounded-full text-sm font-medium shadow-sm hover:bg-white hover:text-primary transition-all z-20 flex items-center gap-2">
      <i class="fa-regular fa-circle-question"></i> คู่มือ
  </a>
  <?php endif; ?>

  <div class="absolute top-0 left-0 w-64 h-64 bg-primary opacity-5 rounded-full blur-3xl -translate-x-1/2 -translate-y-1/2"></div>
  <div class="absolute bottom-0 right-0 w-96 h-96 bg-blue-300 opacity-10 rounded-full blur-3xl translate-x-1/3 translate-y-1/3"></div>

  <div class="bg-white w-full max-w-[400px] rounded-2xl shadow-2xl overflow-hidden relative z-10 border border-gray-100">
    <div class="p-8 pb-0 text-center">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gray-50 mb-4 shadow-sm border border-gray-100 overflow-hidden">
            <?php if ($has_logo): ?>
                <img src="<?= $logo_path ?>" class="w-12 h-12 object-contain">
            <?php else: ?>
                <i class="fa-solid fa-building-columns text-3xl text-primary"></i>
            <?php endif; ?>
        </div>
        <h2 class="text-2xl font-bold text-gray-800">เข้าสู่ระบบ</h2>
        <p class="text-gray-500 text-sm mt-1">จัดการการจองห้องประชุมของคุณ</p>
    </div>

    <div class="p-8 pt-6">
        <?php if ($error): ?>
            <div class="flex items-center gap-2 bg-red-50 text-red-600 px-4 py-3 rounded-lg text-sm mb-5 border border-red-100">
                <i class="fa-solid fa-circle-exclamation"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-5">
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-1 tracking-wider">บัญชีผู้ใช้</label>
                <div class="relative">
                     <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i class="fa-regular fa-envelope"></i></span>
                     <input type="text" name="username" required placeholder="อีเมลของคุณ" 
                            class="w-full pl-10 border border-gray-200 bg-gray-50 rounded-lg px-4 py-3 text-sm focus:outline-none focus:bg-white focus:ring-2 focus-ring transition-all">
                </div>
            </div>
            
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-1 tracking-wider">รหัสผ่าน</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="password" required placeholder="••••••••" 
                           class="w-full pl-10 border border-gray-200 bg-gray-50 rounded-lg px-4 py-3 text-sm focus:outline-none focus:bg-white focus:ring-2 focus-ring transition-all">
                </div>
            </div>

            <button type="submit" class="btn-primary w-full text-white font-bold py-3.5 rounded-xl shadow-lg text-sm tracking-wide mt-2">
                เข้าสู่ระบบ <i class="fa-solid fa-arrow-right ml-2 opacity-70"></i>
            </button>
        </form>

        <?php if ($has_manual): ?>
        <div class="mt-4 text-center">
            <a href="<?= $manual_link ?>" target="_blank" class="text-xs text-gray-400 hover:text-primary transition-colors flex items-center justify-center gap-1">
                <i class="fa-solid fa-book-open"></i> อ่านคู่มือการใช้งาน
            </a>
        </div>
        <?php endif; ?>

        <?php if (($sys['enable_register'] ?? '1') === '1'): ?>
            <div class="mt-6 pt-6 border-t border-gray-100 text-center">
                <p class="text-sm text-gray-400">ยังไม่มีบัญชี?</p>
                <a href="register.php" class="inline-block mt-1 text-sm font-bold text-primary hover:underline decoration-2 underline-offset-4">
                    สมัครสมาชิกใหม่
                </a>
            </div>
        <?php endif; ?>
        
        <div class="mt-4 text-center">
             <a href="index.php" class="text-xs text-gray-400 hover:text-gray-600 transition-colors">
                กลับหน้าหลัก
             </a>
        </div>
    </div>
  </div>
</body>
</html>