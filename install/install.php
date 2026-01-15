<?php
// install/install.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$config_path = __DIR__ . "/../config.php";
$is_installed = file_exists($config_path);
$message = "";
$msg_type = "";

if (!$is_installed && $_SERVER["REQUEST_METHOD"] === "POST") {
    $db_host = $_POST["db_host"];
    $db_user = $_POST["db_user"];
    $db_pass = $_POST["db_pass"];
    $db_name = $_POST["db_name"];
    $db_prefix = trim($_POST["db_prefix"]); 

    // System Settings
    $sys_name = $_POST["sys_name"];
    $sys_desc = $_POST["sys_desc"];
    $sys_color = $_POST["sys_color"]; // Hex Code

    // Admin Info
    $admin_name = $_POST["admin_name"];
    $admin_email = $_POST["admin_email"];
    $admin_pass  = $_POST["admin_pass"];

    $conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);

    if ($conn->connect_error) {
        $message = "❌ เชื่อมต่อฐานข้อมูลไม่สำเร็จ: " . $conn->connect_error;
        $msg_type = "error";
    } else {
        $conn->set_charset("utf8");
        $sql_commands = [];

        // 1. Users
        $sql_commands[] = "CREATE TABLE IF NOT EXISTS {$db_prefix}auth_users (id INT(11) AUTO_INCREMENT PRIMARY KEY, username VARCHAR(150) NOT NULL, password VARCHAR(255) NOT NULL, name VARCHAR(200) NOT NULL, email VARCHAR(150) NOT NULL, phone VARCHAR(50) DEFAULT NULL, status INT(1) DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY unique_username (username)) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        
        // 2. Settings
        $sql_commands[] = "CREATE TABLE IF NOT EXISTS {$db_prefix}settings (name VARCHAR(100) PRIMARY KEY, value TEXT DEFAULT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        
        // 3. Rooms
        $sql_commands[] = "CREATE TABLE IF NOT EXISTS {$db_prefix}rooms (id INT(11) AUTO_INCREMENT PRIMARY KEY, name VARCHAR(200) NOT NULL, detail TEXT DEFAULT NULL, color VARCHAR(50) DEFAULT '#4F46E5', image VARCHAR(255) DEFAULT NULL, capacity INT(11) DEFAULT 0, is_active TINYINT(1) DEFAULT 1, is_student_bookable TINYINT(1) DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        
        // 4. Reservation (✅ เพิ่ม column comment)
        $sql_commands[] = "CREATE TABLE IF NOT EXISTS {$db_prefix}reservation (id INT(11) AUTO_INCREMENT PRIMARY KEY, room_id INT(11) NOT NULL, member_id INT(11) NOT NULL, topic VARCHAR(255) NOT NULL, comment TEXT DEFAULT NULL, attendees INT(11) DEFAULT 1, begin DATETIME NOT NULL, end DATETIME NOT NULL, status INT(1) DEFAULT 0, approve INT(1) DEFAULT 0, closed INT(1) DEFAULT 0, create_date DATETIME DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        
        // 5. Logs
        $sql_commands[] = "CREATE TABLE IF NOT EXISTS {$db_prefix}booking_logs (id INT(11) AUTO_INCREMENT PRIMARY KEY, reservation_id INT(11) DEFAULT NULL, user_name VARCHAR(200) DEFAULT NULL, action_type VARCHAR(50) DEFAULT NULL, room_name VARCHAR(200) DEFAULT NULL, details TEXT DEFAULT NULL, action_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

        $errors = [];
        foreach ($sql_commands as $sql) {
            if (!$conn->query($sql)) { $errors[] = $conn->error; }
        }

        if (empty($errors)) {
            // Insert Admin
            $hashed_password = password_hash($admin_pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO {$db_prefix}auth_users (username, password, name, email, status) VALUES (?, ?, ?, ?, 1)");
            $stmt->bind_param("ssss", $admin_email, $hashed_password, $admin_name, $admin_email);
            $stmt->execute();

            // Insert Default Settings
            $settings_sql = "INSERT INTO {$db_prefix}settings (name, value) VALUES 
            ('system_name', ?), 
            ('system_desc', ?), 
            ('system_color', ?),
            ('system_logo', 'uploads/logo.png'),
            ('default_profile', 'uploads/profiles/default.png'),
            ('footer_credit', '© " . date("Y") . " " . $sys_name . "'),
            ('manual_link', '#'),
            ('enable_register', '1'),
            ('auto_approve', '1')
            ON DUPLICATE KEY UPDATE value = VALUES(value)";
            
            $stmt_s = $conn->prepare($settings_sql);
            $stmt_s->bind_param("sss", $sys_name, $sys_desc, $sys_color);
            $stmt_s->execute();

            // Create Config File
            $config_content = "<?php\n";
            $config_content .= "define('DB_HOST', '" . addslashes($db_host) . "');\n";
            $config_content .= "define('DB_USER', '" . addslashes($db_user) . "');\n";
            $config_content .= "define('DB_PASS', '" . addslashes($db_pass) . "');\n";
            $config_content .= "define('DB_NAME', '" . addslashes($db_name) . "');\n";
            $config_content .= "define('DB_PREFIX', '" . addslashes($db_prefix) . "');\n";
            $config_content .= "?>";

            if (@file_put_contents($config_path, $config_content)) {
                $message = "✅ ติดตั้งสำเร็จ!";
                $msg_type = "success";
                $is_installed = true;
            } else {
                $message = "⚠️ สร้าง DB สำเร็จ แต่เขียน config.php ไม่ได้ (กรุณาตรวจสอบ Permission)";
                $msg_type = "error";
            }
        } else {
            $message = "❌ DB Error: " . implode(", ", $errors);
            $msg_type = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8"><title>ติดตั้งระบบจองห้องประชุม</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>body{font-family:'Kanit',sans-serif;}</style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-3xl rounded-xl shadow-lg overflow-hidden border-t-4 border-blue-600">
        <div class="bg-blue-50 p-6 text-center border-b border-blue-100">
            <h1 class="text-2xl font-bold text-blue-800">ติดตั้งระบบจองห้องประชุม</h1>
            <p class="text-blue-600 text-sm">ตั้งค่าครั้งแรกเพื่อเริ่มใช้งานระบบ</p>
        </div>
        <div class="p-8">
            <?php if($message): ?>
                <div class="p-4 mb-6 rounded-lg text-sm font-medium <?= $msg_type=='success'?'bg-green-100 text-green-700 border border-green-200':'bg-red-100 text-red-700 border border-red-200' ?>">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <?php if($is_installed): ?>
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">✓</div>
                    <p class="text-gray-800 font-bold text-xl mb-2">ระบบติดตั้งเรียบร้อยแล้ว</p>
                    <a href="../login.php" class="inline-block bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 shadow-md transition-all font-medium">เข้าสู่ระบบ</a>
                </div>
            <?php else: ?>
                <form method="post" class="space-y-8">
                    <div>
                        <h3 class="font-bold text-lg text-gray-800 border-b pb-2 mb-4">1. การเชื่อมต่อฐานข้อมูล</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label class="block text-sm font-bold mb-1 text-gray-700">DB Host</label><input type="text" name="db_host" value="localhost" class="w-full border p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"></div>
                            <div><label class="block text-sm font-bold mb-1 text-gray-700">DB Name</label><input type="text" name="db_name" required class="w-full border p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"></div>
                            <div><label class="block text-sm font-bold mb-1 text-gray-700">DB User</label><input type="text" name="db_user" value="root" class="w-full border p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"></div>
                            <div><label class="block text-sm font-bold mb-1 text-gray-700">DB Pass</label><input type="password" name="db_pass" class="w-full border p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"></div>
                            <div><label class="block text-sm font-bold mb-1 text-gray-700">Prefix</label><input type="text" name="db_prefix" value="bk_" class="w-full border p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"></div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="font-bold text-lg text-gray-800 border-b pb-2 mb-4">2. ตั้งค่าหน้าตาระบบ</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label class="block text-sm font-bold mb-1 text-gray-700">ชื่อระบบ</label><input type="text" name="sys_name" placeholder="ระบบจอง..." required class="w-full border p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"></div>
                            <div>
                                <label class="block text-sm font-bold mb-1 text-gray-700">สีธีม (Theme Color)</label>
                                <input type="color" name="sys_color" value="#4F46E5" class="w-full h-10 border p-1 rounded cursor-pointer">
                            </div>
                            <div class="col-span-2"><label class="block text-sm font-bold mb-1 text-gray-700">คำอธิบายสั้นๆ</label><input type="text" name="sys_desc" placeholder="เช่น บริการ 24 ชั่วโมง" class="w-full border p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"></div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="font-bold text-lg text-gray-800 border-b pb-2 mb-4">3. สร้าง Admin</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label class="block text-sm font-bold mb-1 text-gray-700">ชื่อผู้ดูแล</label><input type="text" name="admin_name" required class="w-full border p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"></div>
                            <div><label class="block text-sm font-bold mb-1 text-gray-700">อีเมล (Username)</label><input type="email" name="admin_email" required class="w-full border p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"></div>
                            <div class="col-span-2"><label class="block text-sm font-bold mb-1 text-gray-700">รหัสผ่าน</label><input type="password" name="admin_pass" required class="w-full border p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"></div>
                        </div>
                    </div>
                    
                    <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3.5 rounded-lg hover:bg-blue-700 shadow-lg transition-all mt-4">ติดตั้งระบบ</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>