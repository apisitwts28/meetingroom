<?php
// profile.php
require_once "db.php";
session_start();
if (!isset($_SESSION["user_id"])) { header("Location: login.php"); exit; }

$t_users = DB_PREFIX . 'auth_users';
$user_id = $_SESSION["user_id"];
$msg = "";
$error = "";

// ดึงสีธีม
$themeColor = $sys['system_color'] ?? '#4F46E5';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $new_pass = $_POST['password'];

    // Update Basic Info
    $stmt = $conn->prepare("UPDATE $t_users SET name=?, phone=? WHERE id=?");
    $stmt->bind_param("ssi", $name, $phone, $user_id);
    $stmt->execute();
    
    $_SESSION['user_name'] = $name; // Update Session
    
    // Update Password if provided
    if (!empty($new_pass)) {
        if (strlen($new_pass) < 4) {
            $error = "รหัสผ่านต้องยาวกว่า 4 ตัวอักษร";
        } else {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt_p = $conn->prepare("UPDATE $t_users SET password=? WHERE id=?");
            $stmt_p->bind_param("si", $hashed, $user_id);
            $stmt_p->execute();
        }
    }
    
    if (!$error) $msg = "✅ บันทึกข้อมูลสำเร็จ";
    
    // Log
    $t_logs = DB_PREFIX . 'booking_logs';
    if($conn->query("SHOW TABLES LIKE '$t_logs'")->num_rows > 0) {
        $conn->query("INSERT INTO $t_logs (user_name, action_type, details) VALUES ('$name', 'แก้ไขโปรไฟล์', 'User ID: $user_id')");
    }
}

$stmt = $conn->prepare("SELECT name, email, phone FROM $t_users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8"><title>ข้อมูลส่วนตัว</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
      body{font-family:'Kanit',sans-serif;}
      
      /* ✅ เพิ่ม CSS ให้รองรับ class bg-theme ที่มีในโค้ดเดิม */
      :root { --primary: <?= $themeColor ?>; }
      .bg-theme { background-color: var(--primary); }
      .text-theme { color: var(--primary); }
      .border-theme { border-color: var(--primary); }
      .focus-ring:focus { --tw-ring-color: var(--primary); }
  </style>
</head>
<body class="flex flex-col min-h-screen bg-gray-50">
<?php include 'header.php'; ?>
<?php include 'navbar.php'; ?>

<main class="flex-grow flex items-center justify-center p-6">
    <div class="bg-white w-full max-w-2xl rounded-2xl shadow-xl border-t-4 border-theme p-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
            <div class="w-10 h-10 rounded-full bg-theme text-white flex items-center justify-center text-lg"><i class="fa-solid fa-user"></i></div>
            จัดการข้อมูลส่วนตัว
        </h2>

        <?php if($msg): ?><div class="bg-green-100 text-green-700 p-3 rounded mb-4 text-center font-bold border border-green-200"><?= $msg ?></div><?php endif; ?>
        <?php if($error): ?><div class="bg-red-100 text-red-700 p-3 rounded mb-4 text-center font-bold border border-red-200"><?= $error ?></div><?php endif; ?>

        <form method="post" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm text-gray-500 font-bold mb-1">อีเมล (เปลี่ยนไม่ได้)</label>
                    <input type="text" value="<?= htmlspecialchars($user['email']) ?>" disabled class="w-full bg-gray-100 border border-gray-300 rounded-lg px-3 py-2 text-gray-500 cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-sm text-gray-500 font-bold mb-1">ชื่อ-นามสกุล</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus-ring">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm text-gray-500 font-bold mb-1">เบอร์โทรศัพท์</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus-ring">
                </div>
            </div>

            <div class="pt-6 border-t border-gray-100 bg-gray-50 p-4 rounded-lg">
                <h3 class="font-bold text-gray-700 mb-3"><i class="fa-solid fa-key text-gray-400 mr-2"></i>เปลี่ยนรหัสผ่าน</h3>
                <label class="block text-sm text-gray-500 mb-1">รหัสผ่านใหม่ (ว่างไว้ถ้าไม่ต้องการเปลี่ยน)</label>
                <input type="password" name="password" placeholder="********" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus-ring bg-white">
            </div>

            <button type="submit" class="w-full text-white font-bold py-3 rounded-lg shadow-md hover:opacity-90 transition mt-2 bg-theme">
                บันทึกการเปลี่ยนแปลง
            </button>
        </form>
    </div>
</main>
</body>
</html>