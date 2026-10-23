<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $room_id = trim($_POST['room_id']);
    $billing_month = trim($_POST['billing_month']);
    $room_rent = floatval($_POST['room_rent']);
    
    // กำหนดค่าน้ำแบบเหมาจ่ายรายเดือน (ตัวอย่าง fix ไว้ที่ 150 บาท หรือจะปรับเปลี่ยนตัวเลขได้เลยครับ)
    $water_amount = 150.00;
    
    // เลขมิเตอร์น้ำไม่ต้องใช้คำนวณแล้ว เซ็ตเป็น 0 หรือปล่อยว่างไว้ได้
    $water_prev = 0;
    $water_curr = 0;

    // รับค่าเลขมิเตอร์ไฟฟ้าปัจจุบัน
    $elec_curr = intval($_POST['elec_curr']);
    
    // ดึงมิเตอร์ไฟฟ้าครั้งก่อนของห้องนี้มาคำนวณ
    $stmt_prev = $pdo->prepare("SELECT elec_curr FROM billing WHERE room_id = ? ORDER BY billing_id DESC LIMIT 1");
    $stmt_prev->execute([$room_id]);
    $prev_bill = $stmt_prev->fetch();

    $elec_prev = $prev_bill ? $prev_bill['elec_curr'] : 0;

    // เรทราคาค่าไฟ (หน่วยละ 8 บาท สามารถปรับเปลี่ยนได้ตามจริง)
    $elec_unit_price = 8;
    $elec_used = max(0, $elec_curr - $elec_prev);
    $elec_amount = $elec_used * $elec_unit_price;

    // ยอดรวมทั้งหมด = ค่าห้อง + ค่าน้ำเหมาจ่าย + ค่าไฟตามหน่วย
    $total_amount = $room_rent + $water_amount + $elec_amount;

    // บันทึกลงตาราง billing
    $insert = $pdo->prepare("INSERT INTO billing (room_id, billing_month, room_rent, water_prev, water_curr, water_amount, elec_prev, elec_curr, elec_amount, total_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    
        if ($insert->execute([$room_id, $billing_month, $room_rent, $water_prev, $water_curr, $water_amount, $elec_prev, $elec_curr, $elec_amount, $total_amount])) {
            $success = "ออกบิลสำหรับห้อง $room_id สำเร็จ!";
        } else {
            $error = "เกิดข้อผิดพลาดในการออกบิล";
        }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จดมิเตอร์และออกบิล - Admin Lumina Dorm</title>
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
    /* ให้เนื้อหาทุกอย่างอยู่เหนือวิดีโอ */
    .main-content, .sidebar, nav, .container-fluid {
        position: relative;
        z-index: 10;
    }
    /* ปรับหัวข้อหน้าจอให้เข้มและชัดเจน */
    h2, .container-fluid h2 {
        color: brown !important;
        font-weight: 700 !important;
    }
    /* ปรับกล่องฟอร์มกรอกข้อมูลให้เป็นสีเทาโปร่งใสเข้าธีม */
    .card, .form-container {
        border: 1px solid rgba(255, 255, 255, 0.2) !important;
        border-radius: 20px !important;
        background: rgba(240, 242, 245, 0.88) !important;
        backdrop-filter: blur(12px) !important;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3) !important;
    }
    /* ปรับตัวหนังสือ Label ในฟอร์มให้เข้มชัดเจน */
    label, .form-label {
        color: #333 !important;
        font-weight: 600 !important;
    }
</style>
</head>
<body class="bg-light">
    <video autoplay muted loop playsinline class="bg-video">
    <source src="video/background-video.mp4" type="video/mp4">
    Your browser does not support HTML5 video.
</video>
<div class="video-overlay"></div>
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-2 d-md-block bg-dark sidebar text-white vh-100 p-3">
                <h4 class="fw-bold text-center py-3 text-pink">✨ Lumina Dorm Admin</h4>
                <hr class="border-secondary">
                <ul class="nav flex-column">
                    <li class="nav-item mb-2"><a href="dashboard.php" class="nav-link text-white">📊 Dashboard</a></li>
                    <li class="nav-item mb-2"><a href="meters.php" class="nav-link text-white active">📝 จดมิเตอร์/ออกบิล</a></li>
                    <li class="nav-item mb-2"><a href="bill_list.php" class="nav-link text-white">💰 ตรวจสอบการชำระเงิน</a></li>
                    <li class="nav-item mb-2"><a href="repairs.php" class="nav-link text-white">🛠️ จัดการงานแจ้งซ่อม</a></li>
                    <li class="nav-item mt-5"><a href="../logout.php" class="nav-link text-danger">🚪 ออกจากระบบ</a></li>
                </ul>
            </nav>

            <main class="col-md-10 ms-sm-auto px-4 py-4">
                <h2>จดมิเตอร์และออกใบแจ้งหนี้</h2>
                <?php if($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
                <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

                <div class="card shadow p-4 mt-3" style="max-width: 600px;">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">เลขห้องพัก (Room ID)</label>
                            <input type="text" name="room_id" class="form-control" placeholder="เช่น 101" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">งวดประจำเดือน</label>
                            <input type="text" name="billing_month" class="form-control" placeholder="เช่น 2026-06" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ค่าห้องพัก (บาท)</label>
                            <input type="number" step="0.01" name="room_rent" class="form-control" value="3500" required>
                        </div>
                        <div class="mb-3 p-3 bg-white border rounded">
                            <label class="form-label text-muted">ค่าน้ำประปา</label>
                            <p class="mb-0 fw-bold text-success">เหมาจ่ายรายเดือน: 150.00 บาท</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">เลขมิเตอร์ไฟฟ้า (ครั้งปัจจุบัน)</label>
                            <input type="number" name="elec_curr" class="form-control" placeholder="กรอกเลขมิเตอร์ไฟล่าสุด" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">คำนวณและออกบิล</button>
                    </form>
                </div>
            </main>
        </div>
    </div>
</body>
</html>