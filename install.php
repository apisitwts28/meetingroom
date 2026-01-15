<?php
function tableExists($conn, $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    return $result && $result->num_rows > 0;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $host = $_POST["db_host"];
    $user = $_POST["db_user"];
    $pass = $_POST["db_pass"];
    $name = $_POST["db_name"];

    $conn = @new mysqli($host, $user, $pass, $name);
    if ($conn->connect_error) {
        $error = "❌ ไม่สามารถเชื่อมต่อฐานข้อมูล: " . $conn->connect_error;
    } else {
        $required_tables = ['bfamcsc_reservation', 'bfamcsc_rooms', 'bfamcsc_auth_users', 'bfamcsc_category'];
        $missing = [];

        foreach ($required_tables as $table) {
            if (!tableExists($conn, $table)) $missing[] = $table;
        }

        if (!empty($missing)) {
            $error = "❌ ตารางต่อไปนี้ไม่มีอยู่ในฐานข้อมูล: " . implode(", ", $missing);
        } else {
            $config_content = "<?php\n";
            $config_content .= "define('DB_HOST', '$host');\n";
            $config_content .= "define('DB_USER', '$user');\n";
            $config_content .= "define('DB_PASS', '$pass');\n";
            $config_content .= "define('DB_NAME', '$name');\n";

            $config_path = __DIR__ . "/../config.php";
            if (@file_put_contents($config_path, $config_content)) {
                header("Location: ../index.php");
                exit();
            } else {
                $error = "❌ ไม่สามารถเขียนไฟล์ config.php ได้ โปรดตรวจสอบสิทธิ์ของโฟลเดอร์.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>ติดตั้งระบบจองห้อง</title>
</head>
<body>
  <h2>🛠 ติดตั้งระบบจองห้อง</h2>
  <?php if (!empty($error)) echo "<p style='color:red; font-weight:bold;'>$error</p>"; ?>
  <form method="post">
    <label>Host: <input type="text" name="db_host" value="localhost" required></label><br>
    <label>User: <input type="text" name="db_user" required></label><br>
    <label>Password: <input type="password" name="db_pass"></label><br>
    <label>Database Name: <input type="text" name="db_name" required></label><br>
    <button type="submit">ติดตั้ง</button>
  </form>
</body>
</html>
