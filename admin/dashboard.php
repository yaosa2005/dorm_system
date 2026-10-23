<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

$rooms_count = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$pending_bills = $pdo->query("SELECT COUNT(*) FROM billing WHERE status = 'pending'")->fetchColumn();
$pending_repairs = $pdo->query("SELECT COUNT(*) FROM repair WHERE status = 'รอดำเนินการ'")->fetchColumn();
$total_income = $pdo->query("SELECT SUM(total_amount) FROM billing WHERE status = 'paid'")->fetchColumn() ?: 0;
// ตัวอย่างการคิวรีนับจำนวนห้องทั้งหมด
$stmt_rooms = $pdo->query("SELECT COUNT(*) FROM rooms");
$total_rooms = $stmt_rooms->fetchColumn();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Lumina Dorm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- เรียกใช้งานไฟล์ CSS ที่แยกออกมา (ถอยหลัง 1 โฟลเดอร์) -->
    <link rel="stylesheet" href="../assets/css/style.css">
   <style>
    /* ควบคุมวิดีโอพื้นหลังเต็มจอและซ่อนลายน้ำ */
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

    /* เลเยอร์มืดโปร่งแสงทับวิดีโอนิดหน่อย */
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
    .main-content, .sidebar, nav, .container-fluid {
        position: relative;
        z-index: 10;
    }

    /* บังคับการ์ดสรุปผลทั้ง 4 ช่องให้เป็นสีเทาโปร่งใสสไตล์เดียวกับหน้า Login */
    .card, .stat-card, .dashboard-card {
        border: 1px solid rgba(255, 255, 255, 0.2) !important;
        border-radius: 16px !important;
        background: rgba(240, 242, 245, 0.85) !important;
        backdrop-filter: blur(12px) !important;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3) !important;
    }

    /* บังคับสีตัวหนังสือหัวข้อและการแสดงผลในการ์ดให้เข้มและชัดเจน */
    .card h6, .card p, .card span, .card small, .stat-card h6, .stat-card p {
        color: #495057 !important;
        font-weight: 500 !important;
    }
    .card h3, .card h2, .card h4, .stat-card h3 {
        color: #212529 !important;
        font-weight: 700 !important;
    }
    /* ปรับหัวข้อหน้าจอหลักให้สีเข้มและเด่นชัดขึ้น */
h2, .container-fluid h2, .main-content h2 {
    color: brown !important;
    font-weight: 700 !important;
}
</style>
</head>
<body>
    <!-- วิดีโอพื้นหลังและเลเยอร์โปร่งแสง -->
<video autoplay muted loop playsinline class="bg-video">
    <source src="video/background-video.mp4" type="video/mp4">
    Your browser does not support HTML5 video.
</video>
<div class="video-overlay"></div>
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-2 d-md-block sidebar text-white p-3">
                <h4 class="fw-bold text-center py-3 text-pink">✨ Lumina Dorm Admin</h4>
                <hr class="border-secondary">
                <ul class="nav flex-column">
                    <li class="nav-item"><a href="dashboard.php" class="nav-link active">📊 Dashboard</a></li>
                    <li class="nav-item"><a href="meters.php" class="nav-link">📝 จดมิเตอร์/ออกบิล</a></li>
                    <li class="nav-item"><a href="bill_list.php" class="nav-link">💰 ตรวจสอบการชำระเงิน</a></li>
                    <li class="nav-item"><a href="repairs.php" class="nav-link">🛠️ จัดการงานแจ้งซ่อม</a></li>
                    <li class="nav-item mt-5"><a href="../logout.php" class="nav-link text-danger fw-semibold">🚪 ออกจากระบบ</a></li>
                </ul>
            </nav>

            <main class="col-md-10 ms-sm-auto px-5 py-4">
                <h2 class="fw-bold text-secondary mb-4">Dashboard สรุปภาพรวมระบบ</h2>
                
                <div class="row g-4">
                    <div class="col-md-3">
                        <div class="card card-stat bg-primary text-white p-4">
                            <h6 class="text-white-50">ห้องทั้งหมด</h6>
                            <h2 class="fw-bold"><?= $total_rooms ?> ห้อง</h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-stat bg-danger text-white p-4">
                            <h6 class="text-white-50">บิลค้างชำระ</h6>
                            <h2 class="fw-bold mb-0"><?= $pending_bills ?> บิล</h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-stat bg-warning text-dark p-4">
                            <h6 class="text-dark-50">งานซ่อมรอดำเนินการ</h6>
                            <h2 class="fw-bold mb-0"><?= $pending_repairs ?> งาน</h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-stat bg-success text-white p-4">
                            <h6 class="text-white-50">รายได้รวมทั้งหมด</h6>
                            <h3 class="fw-bold mb-0">฿<?= number_format($total_income, 2) ?></h3>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>