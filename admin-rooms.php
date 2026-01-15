<?php
// admin-rooms.php
require_once "db.php";
session_start();

// ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? '') !== "admin") {
  header("Location: login.php");
  exit;
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset("utf8");

// Table Name
$t_rooms = DB_PREFIX . 'rooms';
$t_logs = DB_PREFIX . 'booking_logs';

// --- Handle Actions ---

// 1. ลบห้อง
if (isset($_GET['delete'])) {
  $id = (int) $_GET['delete'];
  
  // ลบรูปภาพเก่า (ถ้ามี)
  $res = $conn->query("SELECT image, name FROM $t_rooms WHERE id=$id");
  if ($row = $res->fetch_assoc()) {
      if (!empty($row['image']) && file_exists($row['image'])) {
          @unlink($row['image']);
      }
      $room_name = $row['name'];
  }
  
  $conn->query("DELETE FROM $t_rooms WHERE id = $id");
  
  // Log
  $conn->query("INSERT INTO $t_logs (user_name, action_type, details) VALUES ('{$_SESSION['user_name']}', 'ลบห้องประชุม', 'Room: $room_name')");

  header("Location: admin-rooms.php?msg=deleted");
  exit;
}

// 2. เพิ่มห้อง
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
  $name = $conn->real_escape_string($_POST['name'] ?? '');
  $detail = $conn->real_escape_string($_POST['detail'] ?? '');
  $capacity = (int) $_POST['capacity'];
  $color = $conn->real_escape_string($_POST['color'] ?? '#000000');
  $is_active = isset($_POST['is_active']) ? 1 : 0;

  // Upload Image
  $image_path = "";
  if (!empty($_FILES['image']['name'])) {
      $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
      $new_name = "uploads/rooms/room_" . time() . "." . $ext;
      if (!is_dir('uploads/rooms')) mkdir('uploads/rooms', 0777, true);
      if (move_uploaded_file($_FILES['image']['tmp_name'], $new_name)) {
          $image_path = $new_name;
      }
  }

  $sql = "INSERT INTO $t_rooms (name, detail, color, image, capacity, is_active) VALUES ('$name', '$detail', '$color', '$image_path', $capacity, $is_active)";
  $conn->query($sql);
  
  header("Location: admin-rooms.php?msg=added"); exit;
}

// 3. แก้ไขห้อง
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
  $id = (int) $_POST['id'];
  $name = $conn->real_escape_string($_POST['name']);
  $detail = $conn->real_escape_string($_POST['detail']);
  $capacity = (int) $_POST['capacity'];
  $color = $conn->real_escape_string($_POST['color']);
  $is_active = isset($_POST['is_active']) ? 1 : 0;

  // Handle Image Update
  $image_sql = "";
  if (!empty($_FILES['image']['name'])) {
      $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
      $new_name = "uploads/rooms/room_" . time() . "." . $ext;
      if (move_uploaded_file($_FILES['image']['tmp_name'], $new_name)) {
          $image_sql = ", image = '$new_name'";
      }
  }

  $sql = "UPDATE $t_rooms SET name = '$name', detail = '$detail', color = '$color', capacity = $capacity $image_sql, is_active = $is_active WHERE id = $id";
  $conn->query($sql);

  header("Location: admin-rooms.php?msg=updated"); exit;
}

$rooms = $conn->query("SELECT * FROM $t_rooms ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8"><title>จัดการห้องประชุม</title>
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
        <h2 class="text-2xl font-bold text-gray-800 border-l-4 border-theme pl-3" style="border-color: <?= $sys['system_color'] ?>">จัดการห้องประชุม</h2>
        <button onclick="openAddModal()" class="bg-gray-800 text-white px-4 py-2 rounded-lg shadow hover:bg-black transition text-sm">
            <i class="fa-solid fa-plus mr-1"></i> เพิ่มห้องใหม่
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                    <tr>
                        <th class="p-4 w-16">ID</th>
                        <th class="p-4 w-20">รูปภาพ</th>
                        <th class="p-4">ชื่อห้อง / รายละเอียด</th>
                        <th class="p-4 text-center">ความจุ</th>
                        <th class="p-4 text-center">สีปฏิทิน</th>
                        <th class="p-4 text-center">สถานะ</th>
                        <th class="p-4 text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $rooms->fetch_assoc()): ?>
                    <tr class="border-b hover:bg-gray-50 transition">
                        <td class="p-4 text-center"><?= $row['id'] ?></td>
                        <td class="p-4">
                            <?php if($row['image']): ?>
                                <img src="<?= $row['image'] ?>" class="h-10 w-16 object-cover rounded border">
                            <?php else: ?>
                                <div class="h-10 w-16 bg-gray-100 rounded flex items-center justify-center text-xs">No Pic</div>
                            <?php endif; ?>
                        </td>
                        <td class="p-4">
                            <div class="font-bold text-gray-800 text-base"><?= htmlspecialchars($row['name']) ?></div>
                            <div class="text-xs text-gray-500 truncate max-w-xs"><?= htmlspecialchars($row['detail']) ?></div>
                        </td>
                        <td class="p-4 text-center"><?= $row['capacity'] ?> คน</td>
                        <td class="p-4 text-center">
                            <span class="inline-block w-6 h-6 rounded-full shadow-sm" style="background-color: <?= $row['color'] ?>"></span>
                        </td>
                        <td class="p-4 text-center">
                            <?php if ($row['is_active']): ?>
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-bold">เปิดใช้งาน</span>
                            <?php else: ?>
                                <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs font-bold">ปิดปรับปรุง</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-4 text-center space-x-2">
                            <button onclick='openEditModal(<?= json_encode($row) ?>)' class="text-blue-600 hover:text-blue-800"><i class="fa-solid fa-pen-to-square"></i></button>
                            <button onclick="confirmDelete(<?= $row['id'] ?>)" class="text-red-600 hover:text-red-800"><i class="fa-solid fa-trash"></i></button>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<div id="addModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white w-full max-w-lg rounded-xl shadow-2xl p-6">
        <h3 class="text-xl font-bold mb-4 text-gray-800">เพิ่มห้องประชุมใหม่</h3>
        <form method="post" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="add" value="1">
            <div><label class="block text-sm font-bold mb-1">ชื่อห้อง</label><input type="text" name="name" required class="w-full border rounded px-3 py-2"></div>
            <div><label class="block text-sm font-bold mb-1">รายละเอียด</label><textarea name="detail" rows="2" class="w-full border rounded px-3 py-2"></textarea></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-bold mb-1">ความจุ (คน)</label><input type="number" name="capacity" value="0" class="w-full border rounded px-3 py-2"></div>
                <div><label class="block text-sm font-bold mb-1">สีในปฏิทิน</label><input type="color" name="color" value="#3B82F6" class="w-full h-10 border rounded cursor-pointer"></div>
            </div>
            <div><label class="block text-sm font-bold mb-1">รูปภาพ</label><input type="file" name="image" accept="image/*" class="w-full text-sm"></div>
            <div class="flex items-center gap-2 pt-2">
                <input type="checkbox" name="is_active" checked id="add_active" class="w-4 h-4">
                <label for="add_active" class="text-sm">เปิดใช้งานทันที</label>
            </div>
            <div class="flex justify-end gap-3 mt-4 pt-4 border-t">
                <button type="button" onclick="closeAddModal()" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">ยกเลิก</button>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<div id="editModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white w-full max-w-lg rounded-xl shadow-2xl p-6">
        <h3 class="text-xl font-bold mb-4 text-gray-800">แก้ไขห้องประชุม</h3>
        <form method="post" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="edit" value="1">
            <input type="hidden" name="id" id="edit_id">
            <div><label class="block text-sm font-bold mb-1">ชื่อห้อง</label><input type="text" name="name" id="edit_name" required class="w-full border rounded px-3 py-2"></div>
            <div><label class="block text-sm font-bold mb-1">รายละเอียด</label><textarea name="detail" id="edit_detail" rows="2" class="w-full border rounded px-3 py-2"></textarea></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-bold mb-1">ความจุ (คน)</label><input type="number" name="capacity" id="edit_capacity" class="w-full border rounded px-3 py-2"></div>
                <div><label class="block text-sm font-bold mb-1">สีในปฏิทิน</label><input type="color" name="color" id="edit_color" class="w-full h-10 border rounded cursor-pointer"></div>
            </div>
            <div><label class="block text-sm font-bold mb-1">เปลี่ยนรูปภาพ (ถ้ามี)</label><input type="file" name="image" accept="image/*" class="w-full text-sm"></div>
            <div class="flex items-center gap-2 pt-2">
                <input type="checkbox" name="is_active" id="edit_is_active" class="w-4 h-4">
                <label for="edit_is_active" class="text-sm">เปิดใช้งาน</label>
            </div>
            <div class="flex justify-end gap-3 mt-4 pt-4 border-t">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">ยกเลิก</button>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddModal() { document.getElementById('addModal').classList.remove('hidden'); }
    function closeAddModal() { document.getElementById('addModal').classList.add('hidden'); }
    
    function openEditModal(room) {
      document.getElementById('edit_id').value = room.id;
      document.getElementById('edit_name').value = room.name;
      document.getElementById('edit_detail').value = room.detail;
      document.getElementById('edit_color').value = room.color;
      document.getElementById('edit_capacity').value = room.capacity ?? 0;
      document.getElementById('edit_is_active').checked = room.is_active == 1;
      document.getElementById('editModal').classList.remove('hidden');
    }
    function closeEditModal() { document.getElementById('editModal').classList.add('hidden'); }
    
    function confirmDelete(id) { 
        Swal.fire({
            title: 'ยืนยันลบห้อง?', text: "ข้อมูลห้องและรูปภาพจะถูกลบถาวร!", icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'ลบห้อง', cancelButtonText: 'ยกเลิก'
        }).then((r) => { if(r.isConfirmed) window.location = 'admin-rooms.php?delete=' + id; });
    }

    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.get('msg') === 'added') Swal.fire('สำเร็จ', 'เพิ่มห้องประชุมเรียบร้อยแล้ว', 'success');
    if(urlParams.get('msg') === 'updated') Swal.fire('สำเร็จ', 'แก้ไขข้อมูลเรียบร้อยแล้ว', 'success');
    if(urlParams.get('msg') === 'deleted') Swal.fire('สำเร็จ', 'ลบห้องประชุมเรียบร้อยแล้ว', 'success');
</script>
</body>
</html>