<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'tenant') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$room_id = $_SESSION['room_id'];

// ดึงข้อมูลชื่อผู้เช่าจากตาราง users
$stmt_user = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt_user->execute([$user_id]);
$user_info = $stmt_user->fetch();
$tenant_name = isset($user_info['name']) ? $user_info['name'] : (isset($user_info['fullname']) ? $user_info['fullname'] : $user_info['username']);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);

    if (!empty($title)) {
        $stmt = $pdo->prepare("INSERT INTO repair (room_id, user_id, title, description, status) VALUES (?, ?, ?, ?, 'รอดำเนินการ')");
        if ($stmt->execute([$room_id, $user_id, $title, $description])) {
            $success = "แจ้งซ่อมสำเร็จ! แอดมินรับเรื่องเรียบร้อยแล้ว";
        } else {
            $error = "เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง";
        }
    } else {
        $error = "กรุณากรอกหัวข้อปัญหาที่ต้องการแจ้งซ่อม";
    }
}

$repairs = $pdo->prepare("SELECT * FROM repair WHERE room_id = ? ORDER BY repair_id DESC");
$repairs->execute([$room_id]);
$my_repairs = $repairs->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แจ้งซ่อมหอพัก - Lumina Dorm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
    /* วิดีโอพื้นหลังเต็มจอและซ่อนลายน้ำ */
    .bg-video {
        position: fixed;
        top: 50%;
        left: 50%;
        min-width: 100%;
        min-height: 100%;
        width: auto;
        height: auto;
        z-index: 0;
        transform: translateX(-50%) translateY(-50%) scale(1.18);
        object-fit: cover;
    }
    .video-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.35);
        z-index: 1;
    }
    /* ให้เนื้อหาทั้งหมดอยู่เหนือวิดีโอ */
    .container, .container-fluid, nav, .navbar {
        position: relative;
        z-index: 10;
    }
    /* ปรับ Navbar ด้านบนให้ตัวหนังสือเป็นสีขาวคมชัด */
    .navbar, .navbar span, .navbar a, .navbar-brand {
        color: #ffffff !important;
        opacity: 1 !important;
        font-weight: 500;
    }
    /* ปรับหัวข้อหน้าจอ (เช่น "แจ้งซ่อมแซมอุปกรณ์ภายในห้อง") ให้เป็นสีขาวสว่างและมีเงาเด่นชัด */
    h2, h4, .container h4, .container h3 {
        color: #ffffff !important;
        font-weight: 700 !important;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.4);
    }
    /* ปรับกล่องฟอร์มและตารางประวัติให้เป็นสีเทาโปร่งใสสไตล์ Glassmorphism */
    .card, .form-container, .table-container {
        border: 1px solid rgba(255, 255, 255, 0.2) !important;
        border-radius: 20px !important;
        background: rgba(240, 242, 245, 0.88) !important;
        backdrop-filter: blur(12px) !important;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3) !important;
    }
    /* ปรับ Label ในฟอร์มให้เข้มชัดเจน */
    label, .form-label {
        color: #333 !important;
        font-weight: 600 !important;
    }
    /* แต่งตารางประวัติการแจ้งซ่อม */
    table {
        background: transparent !important;
    }
    th {
        background-color: #212529 !important;
        color: #fff !important;
    }
    td {
        color: #333 !important;
        vertical-align: middle !important;
        font-weight: 500;
    }
</style>
</head>
<body>
    <video autoplay muted loop playsinline class="bg-video">
    <source src="video/background-video.mp4" type="video/mp4">
    Your browser does not support HTML5 video.
</video>
<div class="video-overlay"></div>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container">
            <a class="navbar-brand fw-semibold" href="dashboard.php">🏠 Lumina Dorm <span class="text-pink">| ผู้เช่า: <?= htmlspecialchars($tenant_name) ?> ห้อง <?= htmlspecialchars($room_id) ?></span></a>
            <a href="dashboard.php" class="btn btn-outline-light btn-sm rounded-pill px-3">กลับหน้าหลัก</a>
        </div>
    </nav>

    <div class="container my-5" style="max-width: 750px;">
        <h3 class="fw-bold text-secondary mb-4">🔧 แจ้งซ่อมแซมอุปกรณ์ภายในห้อง</h3>
        
        <?php if($success): ?><div class="alert alert-success rounded-4 shadow-sm"><?= $success ?></div><?php endif; ?>
        <?php if($error): ?><div class="alert alert-danger rounded-4 shadow-sm"><?= $error ?></div><?php endif; ?>

        <div class="card card-custom p-4 mb-4">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-medium">หัวข้อปัญหาที่ต้องการแจ้ง</label>
                    <input type="text" name="title" class="form-control" placeholder="เช่น แอร์ไม่เย็น, หลอดไฟห้องน้ำเสีย" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-medium">รายละเอียดเพิ่มเติม</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="ระบุรายละเอียดอาการเพิ่มเติม..."></textarea>
                </div>
                <button type="submit" class="btn btn-custom-pink w-100">ส่งเรื่องแจ้งซ่อม</button>
            </form>
        </div>

        <h4 class="fw-bold text-secondary mb-3">📋 ประวัติและสถานะการแจ้งซ่อม</h4>
        <div class="card card-custom p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>หัวข้อ</th>
                            <th>รายละเอียด</th>
                            <th>วันที่แจ้ง</th>
                            <th>สถานะการดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($my_repairs) > 0): ?>
                            <?php foreach($my_repairs as $index => $r): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td class="fw-medium"><?= htmlspecialchars($r['title']) ?></td>
                                <td class="text-muted"><?= htmlspecialchars($r['description']) ?></td>
                                <td><?= htmlspecialchars($r['created_at']) ?></td>
                                <td>
                                    <?php $status = !empty($r['status']) ? $r['status'] : 'รอดำเนินการ'; ?>
                                    <?php if($status == 'รอดำเนินการ'): ?>
                                        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill"><?= $status ?></span>
                                    <?php elseif($status == 'ติดต่อช่างแล้ว'): ?>
                                        <span class="badge bg-info text-dark px-3 py-2 rounded-pill"><?= $status ?></span>
                                    <?php elseif($status == 'กำลังซ่อม'): ?>
                                        <span class="badge bg-primary px-3 py-2 rounded-pill"><?= $status ?></span>
                                    <?php elseif($status == 'เสร็จสิ้น'): ?>
                                        <span class="badge bg-success px-3 py-2 rounded-pill"><?= $status ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary px-3 py-2 rounded-pill"><?= $status ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">ยังไม่มีรายการแจ้งซ่อม</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>