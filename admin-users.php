<?php
// admin-users.php
require_once "db.php";
session_start();

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? '') !== "admin") {
  header("Location: login.php"); exit;
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset("utf8");

$t_users = DB_PREFIX . 'auth_users';

// --- Actions ---
// 1. เพิ่มผู้ใช้
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
  $email = $conn->real_escape_string($_POST['email']);
  $name = $conn->real_escape_string($_POST['name']);
  $phone = $conn->real_escape_string($_POST['phone']);
  $status = (int) $_POST['status']; // 1=Admin, 0=User
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

  // เช็คซ้ำ
  $chk = $conn->query("SELECT id FROM $t_users WHERE username = '$email'");
  if ($chk->num_rows > 0) {
      header("Location: admin-users.php?msg=dup"); exit;
  }

  $sql = "INSERT INTO $t_users (username, name, email, phone, password, status) VALUES ('$email', '$name', '$email', '$phone', '$password', $status)";
  $conn->query($sql);
  header("Location: admin-users.php?msg=added"); exit;
}

// 2. แก้ไข
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
  $id = (int) $_POST['id'];
  $name = $conn->real_escape_string($_POST['name']);
  $phone = $conn->real_escape_string($_POST['phone']);
  $status = (int) $_POST['status'];
  
  $sql = "UPDATE $t_users SET name='$name', phone='$phone', status=$status WHERE id=$id";
  $conn->query($sql);

  // ถ้าเปลี่ยนรหัสผ่าน
  if (!empty($_POST['password'])) {
      $pwd = password_hash($_POST['password'], PASSWORD_DEFAULT);
      $conn->query("UPDATE $t_users SET password='$pwd' WHERE id=$id");
  }
  header("Location: admin-users.php?msg=updated"); exit;
}

// 3. ลบ
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    if ($id != $_SESSION['user_id']) { // ห้ามลบตัวเอง
        $conn->query("DELETE FROM $t_users WHERE id=$id");
    }
    header("Location: admin-users.php?msg=deleted"); exit;
}

$users = $conn->query("SELECT * FROM $t_users ORDER BY status DESC, id DESC");
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8"><title>จัดการผู้ใช้งาน</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>body{font-family:'Kanit',sans-serif;}</style>
</head>
<body class="flex flex-col min-h-screen bg-gray-50">
<?php include 'header.php'; ?>
<?php include 'navbar.php'; ?>

<main class="flex-grow max-w-7xl mx-auto w-full p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800 border-l-4 border-theme pl-3" style="border-color: <?= $sys['system_color'] ?>">จัดการสมาชิก</h2>
        <button onclick="openAddModal()" class="bg-gray-800 text-white px-4 py-2 rounded-lg shadow hover:bg-black transition text-sm">
            <i class="fa-solid fa-user-plus mr-1"></i> เพิ่มสมาชิก
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                    <tr>
                        <th class="p-4 w-16">ID</th>
                        <th class="p-4">ชื่อ-นามสกุล</th>
                        <th class="p-4">อีเมล (Username)</th>
                        <th class="p-4">เบอร์โทร</th>
                        <th class="p-4 text-center">สถานะ</th>
                        <th class="p-4 text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($u = $users->fetch_assoc()): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="p-4 text-center"><?= $u['id'] ?></td>
                        <td class="p-4 font-bold text-gray-800"><?= htmlspecialchars($u['name']) ?></td>
                        <td class="p-4"><?= htmlspecialchars($u['email']) ?></td>
                        <td class="p-4"><?= htmlspecialchars($u['phone']) ?></td>
                        <td class="p-4 text-center">
                            <?php if ($u['status'] == 1): ?>
                                <span class="bg-purple-100 text-purple-700 px-2 py-1 rounded text-xs font-bold">Admin</span>
                            <?php else: ?>
                                <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs font-bold">User</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-4 text-center space-x-2">
                            <button onclick='openEditModal(<?= json_encode($u) ?>)' class="text-blue-600 hover:text-blue-800"><i class="fa-solid fa-pen-to-square"></i></button>
                            <?php if($u['id'] != $_SESSION['user_id']): ?>
                                <button onclick="confirmDelete(<?= $u['id'] ?>)" class="text-red-600 hover:text-red-800"><i class="fa-solid fa-trash"></i></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<div id="addModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white w-full max-w-md rounded-xl shadow-2xl p-6">
        <h3 class="text-lg font-bold mb-4">เพิ่มสมาชิกใหม่</h3>
        <form method="post" class="space-y-3">
            <input type="hidden" name="add" value="1">
            <div><label class="block text-xs font-bold">ชื่อ-นามสกุล</label><input type="text" name="name" required class="w-full border rounded p-2"></div>
            <div><label class="block text-xs font-bold">อีเมล (ใช้ Login)</label><input type="email" name="email" required class="w-full border rounded p-2"></div>
            <div><label class="block text-xs font-bold">รหัสผ่าน</label><input type="password" name="password" required class="w-full border rounded p-2"></div>
            <div><label class="block text-xs font-bold">เบอร์โทร</label><input type="text" name="phone" class="w-full border rounded p-2"></div>
            <div><label class="block text-xs font-bold">สถานะ</label>
                <select name="status" class="w-full border rounded p-2">
                    <option value="0">User (ผู้ใช้ทั่วไป)</option>
                    <option value="1">Admin (ผู้ดูแลระบบ)</option>
                </select>
            </div>
            <div class="flex justify-end gap-2 pt-4">
                <button type="button" onclick="closeAddModal()" class="px-3 py-2 bg-gray-200 rounded">ยกเลิก</button>
                <button type="submit" class="px-3 py-2 bg-blue-600 text-white rounded">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<div id="editModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white w-full max-w-md rounded-xl shadow-2xl p-6">
        <h3 class="text-lg font-bold mb-4">แก้ไขสมาชิก</h3>
        <form method="post" class="space-y-3">
            <input type="hidden" name="edit" value="1">
            <input type="hidden" name="id" id="edit_id">
            <div><label class="block text-xs font-bold">ชื่อ-นามสกุล</label><input type="text" name="name" id="edit_name" required class="w-full border rounded p-2"></div>
            <div><label class="block text-xs font-bold">อีเมล (ห้ามแก้)</label><input type="email" id="edit_email" disabled class="w-full border rounded p-2 bg-gray-100 text-gray-500"></div>
            <div><label class="block text-xs font-bold">เปลี่ยนรหัสผ่าน (ถ้าไม่เปลี่ยนให้เว้นว่าง)</label><input type="password" name="password" class="w-full border rounded p-2"></div>
            <div><label class="block text-xs font-bold">เบอร์โทร</label><input type="text" name="phone" id="edit_phone" class="w-full border rounded p-2"></div>
            <div><label class="block text-xs font-bold">สถานะ</label>
                <select name="status" id="edit_status" class="w-full border rounded p-2">
                    <option value="0">User</option>
                    <option value="1">Admin</option>
                </select>
            </div>
            <div class="flex justify-end gap-2 pt-4">
                <button type="button" onclick="closeEditModal()" class="px-3 py-2 bg-gray-200 rounded">ยกเลิก</button>
                <button type="submit" class="px-3 py-2 bg-blue-600 text-white rounded">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddModal() { document.getElementById('addModal').classList.remove('hidden'); }
    function closeAddModal() { document.getElementById('addModal').classList.add('hidden'); }
    function openEditModal(u) {
        document.getElementById('edit_id').value = u.id;
        document.getElementById('edit_name').value = u.name;
        document.getElementById('edit_email').value = u.email;
        document.getElementById('edit_phone').value = u.phone;
        document.getElementById('edit_status').value = u.status;
        document.getElementById('editModal').classList.remove('hidden');
    }
    function closeEditModal() { document.getElementById('editModal').classList.add('hidden'); }
    function confirmDelete(id) {
        Swal.fire({ title: 'ยืนยันลบ?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'ลบ', cancelButtonText: 'ไม่' })
        .then((r) => { if(r.isConfirmed) window.location.href='?delete='+id; });
    }
    const u = new URLSearchParams(window.location.search);
    if(u.get('msg')==='added') Swal.fire('สำเร็จ','เพิ่มแล้ว','success');
    if(u.get('msg')==='updated') Swal.fire('สำเร็จ','แก้ไขแล้ว','success');
    if(u.get('msg')==='dup') Swal.fire('ผิดพลาด','อีเมลนี้มีในระบบแล้ว','error');
</script>
</body>
</html>