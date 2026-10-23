<?php
session_start();
include '../config/database.php';

// อนุญาตให้เข้าถึงได้เฉพาะผู้เช่า (tenant) หรือแอดมิน (admin) เท่านั้น
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'tenant' && $_SESSION['role'] != 'admin')) {
    header("Location: ../login.php");
    exit;
}

// กำหนดค่าห้องและชื่อผู้เช่า โดยแยกตามสิทธิ์ของผู้ใช้งานที่กำลังเปิดดู
if ($_SESSION['role'] == 'admin') {
    // กรณีที่เป็นแอดมินกดเข้ามาดู ต้องรับค่า room_id และ bill_id มาจาก URL
    $room_id = isset($_GET['room_id']) ? $_GET['room_id'] : '';
    $tenant_name = "แอดมินตรวจสอบ";
} else {
    // กรณีที่เป็นผู้เช่าปกติ
    $user_id = $_SESSION['user_id'];
    $room_id = $_SESSION['room_id'];

    // ดึงข้อมูลชื่อผู้เช่าจากตาราง users
    $stmt_user = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt_user->execute([$user_id]);
    $user = $stmt_user->fetch();
    $tenant_name = isset($user['name']) ? $user['name'] : (isset($user['fullname']) ? $user['fullname'] : $user['username']);
}

// เช็คว่ามีการส่ง billing_id (หรือ bill_id) มาหรือไม่
if (isset($_GET['bill_id']) && !empty($_GET['bill_id'])) {
    $bill_id = $_GET['bill_id'];
    $stmt = $pdo->prepare("SELECT * FROM billing WHERE billing_id = ?");
    $stmt->execute([$bill_id]);
    $bill = $stmt->fetch();
    // อัปเดต room_id ให้ตรงกับบิลนั้นๆ กรณีแอดมินกดเข้ามา
    if ($bill) {
        $room_id = $bill['room_id'];
    }
} else {
    // ถ้าไม่ได้ระบุ bill_id ให้แสดงบิลล่าสุดของห้องนั้น
    $stmt = $pdo->prepare("SELECT * FROM billing WHERE room_id = ? ORDER BY billing_month DESC LIMIT 1");
    $stmt->execute([$room_id]);
    $bill = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ใบแจ้งหนี้ - Lumina Dorm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
    /* บังคับล้างพื้นหลังทุกระดับชั้นเพื่อให้วิดีโอด้านหลังแสดงผล */
    html, body {
        background: transparent !important;
        background-color: transparent !important;
        margin: 0;
        padding: 0;
        min-height: 100vh;
    }

    /* วิดีโอพื้นหลังเต็มจอและซ่อนลายน้ำ */
    .bg-video {
        position: fixed;
        top: 50%;
        left: 50%;
        min-width: 100%;
        min-height: 100%;
        width: auto;
        height: auto;
        z-index: -999;
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
        z-index: -998;
        pointer-events: none;
    }
    /* ให้เนื้อหาทั้งหมดอยู่เหนือวิดีโอ */
    .container, .container-fluid, nav, .navbar {
        position: relative;
        z-index: 10;
    }
    /* ปรับกล่องใบแจ้งหนี้ให้เป็นสีเทาโปร่งใสสไตล์ Glassmorphism */
    .card, .card-custom {
        border: 1px solid rgba(255, 255, 255, 0.2) !important;
        border-radius: 20px !important;
        background: rgba(240, 242, 245, 0.88) !important;
        backdrop-filter: blur(12px) !important;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3) !important;
    }
    /* ปรับตัวหนังสือภายในบิลให้คมชัด */
    .card-body, span, p {
        color: #333 !important;
    }
    /* ปรับตัวหนังสือบน Navbar แถบสีดำด้านบน ให้เป็นสีขาวสว่างเด่นชัด */
    .navbar, .navbar span, .navbar a, .navbar-brand {
        color: #ffffff !important;
        opacity: 1 !important;
        font-weight: 500;
    }
    /* ปรับหัวข้อด้านนอกการ์ด ให้เป็นสีน้ำตาลเข้มพร้อมเงา */
    .container h3, .container h4, .container h2 {
        color: brown !important;
        font-weight: 700 !important;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.4);
    }
    </style>
</head>
<body>
    <video autoplay muted loop playsinline class="bg-video">
        <source src="../video/background-video.mp4" type="video/mp4">
        Your browser does not support HTML5 video.
    </video>
    <div class="video-overlay"></div>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container">
            <a class="navbar-brand fw-semibold" href="dashboard.php">🏠 Lumina Dorm 
                <span class="text-pink">| <?= ($_SESSION['role'] == 'admin') ? '🔍 ผู้ดูแลระบบกำลังตรวจสอบบิลห้อง: ' . htmlspecialchars($room_id) : 'ผู้เช่า: ' . htmlspecialchars($tenant_name) . ' ห้อง ' . htmlspecialchars($room_id) ?></span>
            </a>
            <div>
                <?php if($_SESSION['role'] == 'admin'): ?>
                    <a href="javascript:window.close();" class="btn btn-secondary btn-sm rounded-pill px-3">ปิดหน้าต่างนี้</a>
                <?php else: ?>
                    <a href="history.php" class="btn btn-outline-light btn-sm me-2 rounded-pill px-3">ประวัติการชำระเงิน</a>
                    <a href="repair.php" class="btn btn-outline-light btn-sm me-2 rounded-pill px-3">แจ้งซ่อมแซม</a>
                    <a href="../logout.php" class="btn btn-danger btn-sm rounded-pill px-3">ออกจากระบบ</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container my-5" style="max-width: 700px;">
        <h3 class="fw-bold mb-4">ใบแจ้งค่าน้ำ-ค่าไฟ ประจำเดือน</h3>
        <?php if ($bill): ?>
            <div class="card card-custom p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark m-0">งวดประจำเดือน: <span class="text-danger"><?= $bill['billing_month'] ?></span></h5>
                    <?php if($bill['status'] == 'paid'): ?>
                        <span class="badge bg-success px-3 py-2 rounded-pill">ชำระแล้ว</span>
                    <?php elseif($bill['status'] == 'waiting_verify'): ?>
                        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">รอแอดมินตรวจสอบสลิป</span>
                    <?php else: ?>
                        <span class="badge bg-danger px-3 py-2 rounded-pill">ค้างชำระ</span>
                    <?php endif; ?>
                </div>
                <hr>
                <div class="mb-2 d-flex justify-content-between">
                    <span class="text-muted">ค่าห้องพัก:</span>
                    <strong class="text-dark"><?= number_format($bill['room_rent'], 2) ?> บาท</strong>
                </div>
                <div class="mb-2 d-flex justify-content-between">
                    <span class="text-muted">ค่าน้ำ (เหมาจ่ายรายเดือน):</span>
                    <strong class="text-dark"><?= number_format($bill['water_amount'], 2) ?> บาท</strong>
                </div>
                <div class="mb-2 d-flex justify-content-between">
                    <span class="text-muted">ค่าไฟ (ใช้ไป <?= $bill['elec_curr'] - $bill['elec_prev'] ?> หน่วย):</span>
                    <strong class="text-dark"><?= number_format($bill['elec_amount'], 2) ?> บาท</strong>
                </div>
                <hr>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold m-0 text-secondary">ยอดรวมทั้งสิ้น:</h4>
                    <h3 class="fw-bold text-danger m-0"><?= number_format($bill['total_amount'], 2) ?> บาท</h3>
                </div>

                <?php if($_SESSION['role'] == 'tenant' && $bill['status'] != 'paid' && $bill['status'] != 'waiting_verify'): ?>
                    <a href="pay_bill.php?billing_id=<?= $bill['billing_id'] ?>" class="btn btn-custom-pink text-center text-white py-2 fw-semibold rounded-pill">📸 แจ้งชำระเงิน / แนบสลิป</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-light shadow-sm text-center py-4 rounded-4 text-muted">ยังไม่มีรายการบิลในขณะนี้</div>
        <?php endif; ?>
    </div>
</body>
</html>