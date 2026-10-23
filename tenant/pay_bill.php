<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'tenant') {
    header("Location: ../login.php");
    exit;
}

$billing_id = $_GET['billing_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = $_POST['amount'];
    
    // อัปโหลดไฟล์สลิป
    $target_dir = "../assets/uploads/";
    if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
    
    $file_extension = pathinfo($_FILES["slip"]["name"], PATHINFO_EXTENSION);
    $file_name = "slip_" . time() . "." . $file_extension;
    $target_file = $target_dir . $file_name;

    if (move_uploaded_file($_FILES["slip"]["tmp_name"], $target_file)) {
        // บันทึกข้อมูลลงตาราง payment
        $stmt = $pdo->prepare("INSERT INTO payment (billing_id, amount, slip_image, status) VALUES (?, ?, ?, 'รอตรวจสอบ')");
        $stmt->execute([$billing_id, $amount, $file_name]);

        // อัปเดตสถานะบิลเป็นรอตรวจสอบ
        $pdo->prepare("UPDATE billing SET status = 'waiting_verify' WHERE billing_id = ?")->execute([$billing_id]);

        echo "<script>alert('ส่งหลักฐานการชำระเงินเรียบร้อยแล้ว'); window.location='dashboard.php';</script>";
    } else {
        echo "<script>alert('เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ชำระเงิน - Lumina Dorm</title>
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
    /* ปรับหัวข้อหน้าจอให้เป็นสีขาวสว่างและมีเงาเด่นชัด */
    h2, h3, h4, .container h3 {
        color: brown !important;
        font-weight: 700 !important;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.4);
    }
    /* ปรับกล่องการ์ดฟอร์มแนบสลิปให้เป็นสีเทาโปร่งใสสไตล์ Glassmorphism */
    .card, .form-container {
        border: 1px solid rgba(255, 255, 255, 0.2) !important;
        border-radius: 24px !important;
        background: rgba(240, 242, 245, 0.88) !important;
        backdrop-filter: blur(12px) !important;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4) !important;
    }
    /* ปรับ Label ในฟอร์มให้เข้มชัดเจน */
    label, .form-label, p.text-muted {
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
    <div class="container my-5" style="max-width: 500px;">
        <div class="card shadow p-4">
            <h4 class="mb-3 text-center">แนบสลิปการโอนเงิน</h4>
            <div class="text-center mb-3">
                <!-- จำลอง QR PromptPay -->
                <img src="https://promptpay.io/0812345678/1000.png" alt="QR Code" class="img-fluid border p-2" style="max-width: 200px;">
                <p class="text-muted small mt-2">สแกนเพื่อโอนเงินผ่าน PromptPay</p>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">จำนวนเงินที่โอน (บาท)</label>
                    <input type="number" step="0.01" name="amount" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">อัปโหลดสลิปโอนเงิน</label>
                    <input type="file" name="slip" class="form-control" accept="image/*" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">ยืนยันการชำระเงิน</button>
            </form>
        </div>
    </div>
</body>
</html>