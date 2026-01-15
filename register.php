<?php
require_once "db.php";
session_start();

// ตรวจสอบการตั้งค่าว่าเปิดรับสมัครหรือไม่
if (($sys['enable_register'] ?? '1') !== '1') {
    header("Location: login.php"); exit;
}

// Theme Color
$themeColor = $sys['system_color'] ?? '#4F46E5';
$logo_path = !empty($sys['system_logo']) ? $sys['system_logo'] : 'uploads/logo.png';
$has_logo = (!empty($logo_path) && file_exists($logo_path));

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $pass = $_POST['password'];
    $pass_confirm = $_POST['password_confirm'];

    if ($pass !== $pass_confirm) {
        $error = "รหัสผ่านไม่ตรงกัน";
    } elseif (strlen($pass) < 6) {
        $error = "รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร";
    } else {
        $t_users = DB_PREFIX . 'auth_users';
        $t_logs = DB_PREFIX . 'booking_logs';

        // เช็คอีเมลซ้ำ
        $stmt = $conn->prepare("SELECT id FROM $t_users WHERE username = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = "อีเมลนี้มีผู้ใช้งานแล้ว";
        } else {
            // สมัครสมาชิก (Status 0 = User)
            $hashed = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO $t_users (username, password, name, email, phone, status) VALUES (?, ?, ?, ?, ?, 0)");
            $stmt->bind_param("sssss", $email, $hashed, $name, $email, $phone);
            
            if ($stmt->execute()) {
                $success = true;
                // Log
                if($conn->query("SHOW TABLES LIKE '$t_logs'")->num_rows > 0) {
                    $conn->query("INSERT INTO $t_logs (user_name, action_type, details) VALUES ('$name', 'Register', 'New User')");
                }
            } else {
                $error = "เกิดข้อผิดพลาด: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8"><title>สมัครสมาชิก - <?= htmlspecialchars($sys['system_name']) ?></title>
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
<body class="min-h-screen flex items-center justify-center p-4 bg-pattern relative">

  <div class="absolute top-0 left-0 w-64 h-64 bg-primary opacity-5 rounded-full blur-3xl -translate-x-1/2 -translate-y-1/2"></div>
  <div class="absolute bottom-0 right-0 w-96 h-96 bg-blue-300 opacity-10 rounded-full blur-3xl translate-x-1/3 translate-y-1/3"></div>

  <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden relative z-10 border border-gray-100">
    
    <div class="px-8 pt-8 pb-4 text-center">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gray-50 mb-3 shadow-sm border border-gray-100 overflow-hidden">
             <?php if ($has_logo): ?>
                <img src="<?= $logo_path ?>" class="w-12 h-12 object-contain">
            <?php else: ?>
                <i class="fa-solid fa-user-plus text-3xl text-primary"></i>
            <?php endif; ?>
        </div>
        <h2 class="text-2xl font-bold text-gray-800">สร้างบัญชีใหม่</h2>
        <p class="text-gray-500 text-sm mt-1">กรอกข้อมูลเพื่อเริ่มต้นใช้งานระบบ</p>
    </div>

    <div class="px-8 pb-8">
        <?php if ($success): ?>
            <div class="text-center py-8">
                <div class="w-16 h-16 bg-green-100 text-green-500 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl animate-bounce">
                    <i class="fa-solid fa-check"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800">สมัครสมาชิกสำเร็จ!</h3>
                <p class="text-gray-500 text-sm mt-2 mb-6">บัญชีของคุณพร้อมใช้งานแล้ว</p>
                <a href="login.php" class="btn-primary w-full block text-white font-bold py-3 rounded-xl shadow-lg text-center">
                    เข้าสู่ระบบทันที
                </a>
            </div>
        <?php else: ?>
        
        <?php if ($error): ?>
            <div class="flex items-center gap-2 bg-red-50 text-red-600 px-4 py-3 rounded-xl text-sm mb-5 border border-red-100">
                <i class="fa-solid fa-circle-exclamation"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-1">ชื่อ-นามสกุล</label>
                <input type="text" name="name" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:bg-white focus:outline-none focus:ring-2 focus-ring transition-all">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1">อีเมล</label>
                    <input type="email" name="email" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:bg-white focus:outline-none focus:ring-2 focus-ring transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1">เบอร์โทรศัพท์</label>
                    <input type="tel" name="phone" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:bg-white focus:outline-none focus:ring-2 focus-ring transition-all">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1">รหัสผ่าน</label>
                    <input type="password" name="password" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:bg-white focus:outline-none focus:ring-2 focus-ring transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1">ยืนยันรหัสผ่าน</label>
                    <input type="password" name="password_confirm" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:bg-white focus:outline-none focus:ring-2 focus-ring transition-all">
                </div>
            </div>

            <button type="submit" class="btn-primary w-full text-white font-bold py-3.5 rounded-xl shadow-lg text-sm tracking-wide mt-2">
                สมัครสมาชิก
            </button>
        </form>

        <div class="mt-6 text-center">
            <p class="text-sm text-gray-400">มีบัญชีอยู่แล้ว?</p>
            <a href="login.php" class="inline-block mt-1 text-sm font-bold text-primary hover:underline decoration-2 underline-offset-4">
                เข้าสู่ระบบที่นี่
            </a>
        </div>
        
        <div class="mt-4 text-center border-t border-gray-100 pt-4">
             <a href="index.php" class="text-xs text-gray-400 hover:text-gray-600 transition-colors">
                กลับหน้าหลัก
             </a>
        </div>
        <?php endif; ?>
    </div>
  </div>
</body>
</html>