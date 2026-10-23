<?php
session_start();
include 'config/database.php';

$error = '';
$success = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'login';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $form_type = $_POST['form_type'] ?? '';

    if ($form_type == 'login') {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // ตรวจสอบรหัสผ่านกับคอลัมน์ password_hash ในฐานข้อมูลจริงของคุณ
        $db_pass = isset($user['password_hash']) ? $user['password_hash'] : (isset($user['password']) ? $user['password'] : '');

        if ($user && password_verify($password, $db_pass)) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['room_id'] = $user['room_id'];

            if ($user['role'] == 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: tenant/dashboard.php");
            }
            exit;
        } else {
            $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
        }
    } elseif ($form_type == 'register') {
        $full_name = trim($_POST['full_name']);
        $username = trim($_POST['username']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $room_id = trim($_POST['room_id']);
        $role = 'tenant';

        $check = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $check->execute([$username]);
        if ($check->rowCount() > 0) {
            $error = "ชื่อผู้ใช้นี้ถูกใช้งานแล้ว";
        } else {
            // บันทึกลงคอลัมน์ full_name และ password_hash ตามโครงสร้างจริงในภาพ
            $stmt = $pdo->prepare("INSERT INTO users (full_name, username, password_hash, room_id, role) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$full_name, $username, $password, $room_id, $role])) {
                $success = "สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ";
                $action = 'login';
            } else {
                $error = "เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title><?= $action == 'register' ? 'สมัครสมาชิก' : 'เข้าสู่ระบบ' ?> - Lumina Dorm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Prompt', sans-serif; }
        body, html {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100vh;
            overflow: hidden;
            background: #111 !important;
        }
        .bg-video {
            position: fixed;
            top: 50%;
            left: 50%;
            min-width: 100%;
            min-height: 100%;
            width: auto;
            height: auto;
            z-index: 1;
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
            z-index: 2;
        }
        .login-wrapper {
            position: relative;
            z-index: 10;
            width: 100vw;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .card-custom {
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            background: rgba(240, 242, 245, 0.85);
            backdrop-filter: blur(12px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            width: 400px;
        }
        .btn-custom {
            background-color: #212529;
            color: #fff;
            border-radius: 50rem;
            padding: 10px;
            border: none;
            font-weight: 500;
        }
        .btn-custom:hover {
            background-color: #343a40;
            color: #fff;
        }
    </style>
</head>
<body>

    <video autoplay muted loop playsinline class="bg-video">
        <source src="video/background-video.mp4" type="video/mp4">
        Your browser does not support HTML5 video.
    </video>
    <div class="video-overlay"></div>

    <div class="login-wrapper">
        <div class="card card-custom p-4 shadow">
            <h3 class="text-center fw-bold text-dark mb-1">Lumina Dorm</h3>
            <p class="text-center text-muted small mb-3">ระบบค่าน้ำ ค่าไฟ และแจ้งซ่อมหอพัก</p>

            <?php if($error): ?><div class="alert alert-danger py-2 small text-center"><?= $error ?></div><?php endif; ?>
            <?php if($success): ?><div class="alert alert-success py-2 small text-center"><?= $success ?></div><?php endif; ?>

            <?php if ($action == 'register'): ?>
                <form method="POST">
                    <input type="hidden" name="form_type" value="register">
                    <div class="mb-2">
                        <label class="form-label small fw-medium text-secondary">ชื่อ-นามสกุล</label>
                        <input type="text" name="full_name" class="form-control form-control-sm rounded-3 bg-white bg-opacity-75" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-medium text-secondary">ชื่อผู้ใช้ (Username)</label>
                        <input type="text" name="username" class="form-control form-control-sm rounded-3 bg-white bg-opacity-75" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-medium text-secondary">รหัสผ่าน</label>
                        <input type="password" name="password" class="form-control form-control-sm rounded-3 bg-white bg-opacity-75" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium text-secondary">เลขห้องพัก</label>
                        <input type="text" name="room_id" class="form-control form-control-sm rounded-3 bg-white bg-opacity-75" required>
                    </div>
                    <button type="submit" class="btn btn-custom w-100 btn-sm">สมัครสมาชิก</button>
                </form>
                <div class="text-center mt-3 small">
                    <a href="login.php" class="text-decoration-none text-dark fw-medium">มีบัญชีอยู่แล้ว? เข้าสู่ระบบ</a>
                </div>
            <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="form_type" value="login">
                    <div class="mb-3">
                        <label class="form-label fw-medium text-secondary">ชื่อผู้ใช้ (Username)</label>
                        <input type="text" name="username" class="form-control rounded-3 bg-white bg-opacity-75" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium text-secondary">รหัสผ่าน (Password)</label>
                        <input type="password" name="password" class="form-control rounded-3 bg-white bg-opacity-75" required>
                    </div>
                    <button type="submit" class="btn btn-custom w-100">เข้าสู่ระบบ</button>
                </form>
                <div class="text-center mt-3 small">
                    <a href="login.php?action=register" class="text-decoration-none text-dark fw-medium">ยังไม่มีบัญชี? สมัครสมาชิกที่นี่</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>