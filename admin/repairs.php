<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

// อัปเดตสถานะงานซ่อม
if (isset($_GET['status']) && isset($_GET['repair_id'])) {
    $status = $_GET['status'];
    $repair_id = $_GET['repair_id'];
    $pdo->prepare("UPDATE repair SET status = ? WHERE repair_id = ?")->execute([$status, $repair_id]);
    echo "<script>alert('อัปเดตสถานะเป็น \"$status\" เรียบร้อยแล้ว'); window.location='repairs.php';</script>";
}

$repairs = $pdo->query("SELECT * FROM repair ORDER BY repair_id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการงานแจ้งซ่อม - Admin Lumina Dorm</title>
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
    /* ปรับกล่องตารางรายงานการแจ้งซ่อมให้เป็นสีเทาโปร่งใส */
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
                    <li class="nav-item mb-2"><a href="meters.php" class="nav-link text-white">📝 จดมิเตอร์/ออกบิล</a></li>
                    <li class="nav-item mb-2"><a href="bill_list.php" class="nav-link text-white">💰 ตรวจสอบการชำระเงิน</a></li>
                    <li class="nav-item mb-2"><a href="repairs.php" class="nav-link text-white active">🛠️ จัดการงานแจ้งซ่อม</a></li>
                    <li class="nav-item mt-5"><a href="../logout.php" class="nav-link text-danger">🚪 ออกจากระบบ</a></li>
                </ul>
            </nav>

            <main class="col-md-10 ms-sm-auto px-4 py-4">
                <h2>รายการแจ้งซ่อมทั้งหมดจากผู้เช่า</h2>
                <div class="card shadow mt-4 p-4">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>ห้อง</th>
                                    <th>หัวข้อแจ้งซ่อม</th>
                                    <th>รายละเอียด</th>
                                    <th>สถานะปัจจุบัน</th>
                                    <th>เปลี่ยนสถานะ (จัดการ)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($repairs) > 0): ?>
                                    <?php foreach($repairs as $index => $rep): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td>ห้อง <?= $rep['room_id'] ?></td>
                                        <td><?= $rep['title'] ?></td>
                                        <td><?= $rep['description'] ?></td>
                                        <td>
                                            <?php 
                                                // เช็คว่าถ้าค่า status ว่าง ให้แสดงเป็น 'รอดำเนินการ'
                                                $status = !empty($rep['status']) ? $rep['status'] : 'รอดำเนินการ'; 
                                                
                                            ?>
                                            <?php if($status == 'รอดำเนินการ'): ?>
                                                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill"><?= $status ?></span>
                                            <?php elseif($status == 'ติดต่อช่างแล้ว'): ?>
                                                <span class="badge bg-info text-dark px-3 py-2 rounded-pill"><?= $status ?></span>
                                            <?php elseif($status == 'กำลังซ่อม'): ?>
                                                <span class="badge bg-primary px-3 py-2 rounded-pill"><?= $status ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-success px-3 py-2 rounded-pill"><?= $status ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group" aria-label="Basic example">
                                                <a href="repairs.php?status=รอดำเนินการ&repair_id=<?= $rep['repair_id'] ?>" class="btn btn-outline-warning btn-sm">รอดำเนินการ</a>
                                                <a href="repairs.php?status=ติดต่อช่างแล้ว&repair_id=<?= $rep['repair_id'] ?>" class="btn btn-outline-info btn-sm">ติดต่อช่างแล้ว</a>
                                                <a href="repairs.php?status=กำลังซ่อม&repair_id=<?= $rep['repair_id'] ?>" class="btn btn-outline-primary btn-sm">กำลังซ่อม</a>
                                                <a href="repairs.php?status=เสร็จสิ้น&repair_id=<?= $rep['repair_id'] ?>" class="btn btn-outline-success btn-sm">เสร็จสิ้น</a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">ยังไม่มีรายการแจ้งซ่อมในขณะนี้</td>
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