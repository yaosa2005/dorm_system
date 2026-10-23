<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

if (isset($_GET['action']) && isset($_GET['payment_id'])) {
    $payment_id = $_GET['payment_id'];
    $billing_id = $_GET['billing_id'];
    $action = $_GET['action'];

    if ($action == 'approve') {
        $pdo->prepare("UPDATE payment SET status = 'อนุมัติ' WHERE payment_id = ?")->execute([$payment_id]);
        $pdo->prepare("UPDATE billing SET status = 'paid' WHERE billing_id = ?")->execute([$billing_id]);
        echo "<script>alert('อนุมัติการชำระเงินเรียบร้อยแล้ว'); window.location='bill_list.php';</script>";
    } elseif ($action == 'reject') {
        $pdo->prepare("UPDATE payment SET status = 'ไม่อนุมัติ' WHERE payment_id = ?")->execute([$payment_id]);
        $pdo->prepare("UPDATE billing SET status = 'pending' WHERE billing_id = ?")->execute([$billing_id]);
        echo "<script>alert('ปฏิเสธการชำระเงินแล้ว'); window.location='bill_list.php';</script>";
    }
}

// ดึงข้อมูลการชำระเงินและเชื่อมโยงกับตาราง billing เพื่อให้ได้ billing_id
$stmt = $pdo->query("SELECT p.*, b.room_id, b.billing_month, b.total_amount, b.billing_id 
                     FROM payment p 
                     JOIN billing b ON p.billing_id = b.billing_id 
                     ORDER BY p.payment_id DESC");
$payments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ตรวจสอบการชำระเงิน - Lumina Dorm</title>
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
    .main-content, .sidebar, nav, .container-fluid {
        position: relative;
        z-index: 10;
    }
    /* ปรับหัวข้อหน้าจอให้เข้มและชัดเจน */
    h2, .container-fluid h2 {
        color: brown !important;
        font-weight: 700 !important;
    }
    /* ปรับกล่องตารางรายชื่อให้เป็นสีเทาโปร่งใส */
    .card, .table-container, .table-responsive {
        border: 1px solid rgba(255, 255, 255, 0.2) !important;
        border-radius: 20px !important;
        background: rgba(240, 242, 245, 0.88) !important;
        backdrop-filter: blur(12px) !important;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3) !important;
        padding: 15px;
    }
    /* ปรับแต่งตารางให้ดูสะอาดตา */
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
    <source src="../video/background-video.mp4" type="video/mp4">
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
                    <li class="nav-item mb-2"><a href="meters.php" class="nav-link text-white">📝 จดมิเตอร์/ออกบิล</a></li>
                    <li class="nav-item mb-2"><a href="bill_list.php" class="nav-link text-white active">💰 ตรวจสอบการชำระเงิน</a></li>
                    <li class="nav-item mb-2"><a href="repairs.php" class="nav-link text-white">🛠️ จัดการงานแจ้งซ่อม</a></li>
                    <li class="nav-item mt-5"><a href="../logout.php" class="nav-link text-danger">🚪 ออกจากระบบ</a></li>
                </ul>
            </nav>

            <main class="col-md-10 ms-sm-auto px-4 py-4">
                <h2>ตรวจสอบรายการชำระเงิน (สลิปโอนเงิน)</h2>
                <div class="card shadow mt-4 p-4">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>ห้อง</th>
                                    <th>งวดเดือน</th>
                                    <th>ยอดรวมบิล</th>
                                    <th>ยอดที่โอน</th>
                                    <th>หลักฐานสลิป</th>
                                    <th>สถานะ</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($payments) > 0): ?>
                                    <?php foreach($payments as $index => $pay): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td>ห้อง <?= $pay['room_id'] ?></td>
                                        <td><?= $pay['billing_month'] ?></td>
                                        <td><?= number_format($pay['total_amount'], 2) ?> ฿</td>
                                        <td><?= number_format($pay['amount'], 2) ?> ฿</td>
                                        <td>
                                            <a href="../assets/uploads/<?= $pay['slip_image'] ?>" target="_blank">
                                                <img src="../assets/uploads/<?= $pay['slip_image'] ?>" alt="Slip" style="width: 50px; height: 50px; object-fit: cover;" class="rounded border">
                                            </a>
                                        </td>
                                        <td>
                                            <?php if($pay['status'] == 'รอตรวจสอบ'): ?>
                                                <span class="badge bg-warning text-dark"><?= $pay['status'] ?></span>
                                            <?php elseif($pay['status'] == 'อนุมัติ'): ?>
                                                <span class="badge bg-success"><?= $pay['status'] ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-danger"><?= $pay['status'] ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <!-- เพิ่มปุ่มดูบิลสำหรับแอดมิน (เปิดไปหน้า dashboard ของผู้เช่าแบบระบุ bill_id) -->
                                            <a href="../tenant/dashboard.php?bill_id=<?= $pay['billing_id'] ?>" target="_blank" class="btn btn-info btn-sm mb-1">ดูบิล</a>
                                            
                                            <?php if($pay['status'] == 'รอตรวจสอบ'): ?>
                                                <div class="btn-group-vertical btn-group-sm w-100" role="group">
                                                    <a href="bill_list.php?action=approve&payment_id=<?= $pay['payment_id'] ?>&billing_id=<?= $pay['billing_id'] ?>" class="btn btn-success btn-sm mb-1" onclick="return confirm('ยืนยันอนุมัติ?')">อนุมัติ</a>
                                                    <a href="bill_list.php?action=reject&payment_id=<?= $pay['payment_id'] ?>&billing_id=<?= $pay['billing_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('ปฏิเสธ?')">ปฏิเสธ</a>
                                                </div>
                                            <?php else: ?>
                                                <div><span class="text-muted small">ดำเนินการแล้ว</span></div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">ยังไม่มีรายการแจ้งชำระเงินเข้ามา</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>