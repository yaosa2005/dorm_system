<?php
$host = 'localhost';
$dbname = 'dorm_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // สร้างฐานข้อมูลอัตโนมัติ
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE $dbname");

    // 1. ตารางผู้ใช้งาน (Users)
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        role ENUM('admin', 'tenant') DEFAULT 'tenant',
        room_id VARCHAR(20) DEFAULT NULL
    ) ENGINE=InnoDB;");

    // 2. ตารางห้องพัก (Rooms)
    $pdo->exec("CREATE TABLE IF NOT EXISTS rooms (
        room_id VARCHAR(20) PRIMARY KEY,
        room_type VARCHAR(50),
        status VARCHAR(50) DEFAULT 'ว่าง'
    ) ENGINE=InnoDB;");

    // 3. ตารางบิลค่าน้ำ-ค่าไฟ-ค่าห้อง (Billing)
    $pdo->exec("CREATE TABLE IF NOT EXISTS billing (
        billing_id INT AUTO_INCREMENT PRIMARY KEY,
        room_id VARCHAR(20) NOT NULL,
        billing_month VARCHAR(20) NOT NULL,
        room_rent DECIMAL(10,2) DEFAULT 0.00,
        water_prev INT DEFAULT 0,
        water_curr INT DEFAULT 0,
        water_amount DECIMAL(10,2) DEFAULT 0.00,
        elec_prev INT DEFAULT 0,
        elec_curr INT DEFAULT 0,
        elec_amount DECIMAL(10,2) DEFAULT 0.00,
        total_amount DECIMAL(10,2) DEFAULT 0.00,
        status VARCHAR(30) DEFAULT 'pending'
    ) ENGINE=InnoDB;");

    // 4. ตารางการชำระเงิน/แนบสลิป (Payment)
    $pdo->exec("CREATE TABLE IF NOT EXISTS payment (
        payment_id INT AUTO_INCREMENT PRIMARY KEY,
        billing_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        slip_image VARCHAR(255) NOT NULL,
        status VARCHAR(30) DEFAULT 'รอตรวจสอบ',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // 5. ตารางแจ้งซ่อม (Repair - รองรับการนับจำนวนหน้า Admin Dashboard)
    $pdo->exec("CREATE TABLE IF NOT EXISTS repair (
        repair_id INT AUTO_INCREMENT PRIMARY KEY,
        room_id VARCHAR(20) NOT NULL,
        title VARCHAR(150) NOT NULL,
        description TEXT,
        status VARCHAR(30) DEFAULT 'รอดำเนินการ',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>