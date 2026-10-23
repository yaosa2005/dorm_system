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

// ดึงประวัติบิลทั้งหมดของห้องนี้
$stmt = $pdo->prepare("SELECT * FROM billing WHERE room_id = ? ORDER BY billing_month DESC");
$stmt->execute([$room_id]);
$bills = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ประวัติการชำระเงิน - Lumina Dorm</title>
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
    /* ปรับหัวข้อหน้าจอ (เช่น "ประวัติการชำระเงินและบิลทั้งหมด") ให้เป็นสีขาวสว่างและมีเงาเด่นชัด */
    h2, h3, h4, .container h3, .container h4 {
        color: brown !important;
        font-weight: 700 !important;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.4);
    }
    /* ปรับกล่องตารางประวัติให้เป็นสีเทาโปร่งใสสไตล์ Glassmorphism */
    .card, .table-container, .table-responsive {
        border: 1px solid rgba(255, 255, 255, 0.2) !important;
        border-radius: 20px !important;
        background: rgba(240, 242, 245, 0.88) !important;
        backdrop-filter: blur(12px) !important;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3) !important;
        padding: 15px;
    }
    /* ปรับแต่งตารางให้ดูสะอาดตาและอ่านง่าย */
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
<body class="bg-light">
    <video autoplay muted loop playsinline class="bg-video">
    <source src="video/background-video.mp4" type="video/mp4">
    Your browser does not support HTML5 video.
</video>
<div class="video-overlay"></div>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Lumina Dorm | ผู้เช่า: <?= htmlspecialchars($tenant_name); ?> ห้อง <?= htmlspecialchars($room_id); ?></a>
            <a href="dashboard.php" class="btn btn-outline-light btn-sm">กลับหน้าหลัก</a>
        </div>
    </nav>

    <div class="container my-4">
        <h3>ประวัติการชำระเงินและบิลทั้งหมด</h3>
        <div class="card shadow mt-3 p-4">
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>งวดเดือน</th>
                            <th>ค่าห้อง</th>
                            <th>ค่าน้ำ</th>
                            <th>ค่าไฟ</th>
                            <th>ยอดรวม</th>
                            <th>สถานะบิล</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($bills) > 0): ?>
                            <?php foreach($bills as $b): ?>
                            <tr>
                                <td><?= $b['billing_month'] ?></td>
                                <td><?= number_format($b['room_rent'], 2) ?></td>
                                <td><?= number_format($b['water_amount'], 2) ?></td>
                                <td><?= number_format($b['elec_amount'], 2) ?></td>
                                <td class="fw-bold text-danger"><?= number_format($b['total_amount'], 2) ?> ฿</td>
                                <td>
                                    <?php 
                                        $b_id = isset($b['billing_id']) ? $b['billing_id'] : (isset($b['id']) ? $b['id'] : ''); 
                                        $status = trim($b['status']);
                                    ?>

                                    <?php if ($status == 'paid'): ?>
                                        <a href="dashboard.php?bill_id=<?= $b_id; ?>" class="badge bg-success px-3 py-2 rounded-pill text-decoration-none">ชำระแล้ว (ดูบิล)</a>
                                    <?php elseif ($status == 'waiting_verify'): ?>
                                        <a href="dashboard.php?bill_id=<?= $b_id; ?>" class="badge bg-warning text-dark px-3 py-2 rounded-pill text-decoration-none">รอตรวจสอบ (ดูบิล)</a>
                                    <?php else: ?>
                                        <a href="dashboard.php?bill_id=<?= $b_id; ?>" class="btn btn-danger btn-sm rounded-pill px-3">ค้างชำระ (ชำระเงิน)</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">ยังไม่มีประวัติบิลในขณะนี้</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>