<?php
// admin-settings.php
require_once "db.php";
session_start();
if (($_SESSION["role"] ?? '') !== "admin") { header("Location: login.php"); exit; }

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset("utf8");

$t_settings = DB_PREFIX . 'settings';
$msg = "";

// Save Logic
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 1. Text Fields
    $fields = ['system_name', 'system_desc', 'system_color', 'footer_credit', 'manual_link'];
    foreach ($fields as $f) {
        if (isset($_POST[$f])) {
            $val = $conn->real_escape_string($_POST[$f]);
            $conn->query("INSERT INTO $t_settings (name, value) VALUES ('$f', '$val') ON DUPLICATE KEY UPDATE value='$val'");
        }
    }
    
    // 2. Toggles
    $reg = isset($_POST['enable_register']) ? '1' : '0';
    $conn->query("INSERT INTO $t_settings (name, value) VALUES ('enable_register', '$reg') ON DUPLICATE KEY UPDATE value='$reg'");
    
    $auto = isset($_POST['auto_approve']) ? '1' : '0';
    $conn->query("INSERT INTO $t_settings (name, value) VALUES ('auto_approve', '$auto') ON DUPLICATE KEY UPDATE value='$auto'");

    // 3. File Upload (Logo)
    if (!empty($_FILES['logo_file']['name'])) {
        $ext = pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION);
        $new_name = "uploads/logo_" . time() . "." . $ext;
        
        // สร้างโฟลเดอร์ถ้ายังไม่มี
        if (!is_dir('uploads')) mkdir('uploads');

        if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $new_name)) {
             $conn->query("INSERT INTO $t_settings (name, value) VALUES ('system_logo', '$new_name') ON DUPLICATE KEY UPDATE value='$new_name'");
        }
    }

    $msg = "✅ บันทึกการตั้งค่าเรียบร้อยแล้ว";
    
    // Log Admin Action
    $t_logs = DB_PREFIX . 'booking_logs';
    if($conn->query("SHOW TABLES LIKE '$t_logs'")->num_rows > 0) {
        $conn->query("INSERT INTO $t_logs (user_name, action_type, details) VALUES ('{$_SESSION['user_name']}', 'ตั้งค่าระบบ', 'Updated System Settings')");
    }
    
    header("Location: admin-settings.php?msg=saved"); exit;
}

// Reload Settings locally
$res = $conn->query("SELECT * FROM $t_settings");
$local_sys = [];
while ($r = $res->fetch_assoc()) $local_sys[$r['name']] = $r['value'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8"><title>ตั้งค่าระบบ</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
      body{font-family:'Kanit',sans-serif;}
      :root { --primary: <?= $local_sys['system_color'] ?? '#4F46E5' ?>; }
      .bg-theme { background-color: var(--primary); }
  </style>
</head>
<body class="flex flex-col min-h-screen bg-gray-50">
<?php include 'header.php'; ?>
<?php include 'navbar.php'; ?>

<main class="flex-grow max-w-5xl mx-auto w-full p-6">
    <div class="flex items-center gap-3 mb-6">
        <div class="p-3 rounded-lg text-white bg-theme shadow-sm">
            <i class="fa-solid fa-sliders text-xl"></i>
        </div>
        <h2 class="text-2xl font-bold text-gray-800">ตั้งค่าระบบ (General Settings)</h2>
    </div>

    <form method="post" enctype="multipart/form-data" class="space-y-6">
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                    <h3 class="font-bold text-gray-700 mb-4 border-b pb-2"><i class="fa-solid fa-circle-info mr-2"></i>ข้อมูลทั่วไป</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-sm font-bold text-gray-600 mb-1">ชื่อระบบ (Title)</label>
                            <input type="text" name="system_name" value="<?= htmlspecialchars($local_sys['system_name'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus-ring outline-none">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-bold text-gray-600 mb-1">คำอธิบายใต้โลโก้ (Subtitle)</label>
                            <input type="text" name="system_desc" value="<?= htmlspecialchars($local_sys['system_desc'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus-ring outline-none">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-bold text-gray-600 mb-1">ลิงก์คู่มือ (Link)</label>
                            <input type="text" name="manual_link" value="<?= htmlspecialchars($local_sys['manual_link'] ?? '#') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus-ring outline-none">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-bold text-gray-600 mb-1">ข้อความ Footer</label>
                            <input type="text" name="footer_credit" value="<?= htmlspecialchars($local_sys['footer_credit'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus-ring outline-none">
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                    <h3 class="font-bold text-gray-700 mb-4 border-b pb-2"><i class="fa-solid fa-palette mr-2"></i>การแสดงผล</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-600 mb-1">สีธีมหลัก</label>
                            <div class="flex gap-2 items-center">
                                <input type="color" name="system_color" value="<?= htmlspecialchars($local_sys['system_color'] ?? '#D35400') ?>" class="h-10 w-full border rounded cursor-pointer">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-600 mb-1">เปลี่ยนโลโก้</label>
                            <input type="file" name="logo_file" accept="image/*" class="w-full text-xs text-gray-500 file:mr-2 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:bg-gray-100 hover:file:bg-gray-200">
                            
                            <?php 
                                $logo_path = $local_sys['system_logo'] ?? '';
                                if(!empty($logo_path) && file_exists($logo_path)): 
                            ?>
                                <div class="mt-2 bg-gray-200 p-2 rounded text-center">
                                    <img src="<?= $logo_path ?>" class="h-12 mx-auto object-contain">
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                    <h3 class="font-bold text-gray-700 mb-4 border-b pb-2"><i class="fa-solid fa-toggle-on mr-2"></i>ตัวเลือกระบบ</h3>
                    <div class="space-y-4">
                        <label class="flex items-center justify-between cursor-pointer p-2 hover:bg-gray-50 rounded">
                            <span class="text-sm font-medium text-gray-700">เปิดรับสมัครสมาชิก</span>
                            <div class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="enable_register" value="1" class="sr-only peer" <?= ($local_sys['enable_register']??'1')=='1' ? 'checked':'' ?>>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500"></div>
                            </div>
                        </label>
                        <label class="flex items-center justify-between cursor-pointer p-2 hover:bg-gray-50 rounded">
                            <span class="text-sm font-medium text-gray-700">อนุมัติอัตโนมัติ</span>
                            <div class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="auto_approve" value="1" class="sr-only peer" <?= ($local_sys['auto_approve']??'1')=='1' ? 'checked':'' ?>>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500"></div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="w-full bg-gray-900 text-white font-bold py-4 rounded-xl shadow-lg hover:bg-black transition-all flex items-center justify-center gap-2">
            <i class="fa-solid fa-save"></i> บันทึกการเปลี่ยนแปลงทั้งหมด
        </button>

    </form>
</main>

<script>
const u = new URLSearchParams(window.location.search);
if(u.get('msg')==='saved') Swal.fire('สำเร็จ','บันทึกการตั้งค่าแล้ว','success');
</script>
</body>
</html>