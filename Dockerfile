FROM php:8.2-apache

# ติดตั้งส่วนเสริมสำหรับเชื่อมต่อฐานข้อมูล MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# เปิดใช้งาน mod_rewrite สำหรับ Apache
RUN a2enmod rewrite

# คัดลอกไฟล์โปรเจกต์ทั้งหมดไปไว้ในโฟลเดอร์ทำงานของเว็บเซิร์ฟเวอร์
COPY . /var/www/html/

# กำหนดสิทธิ์การเข้าถึงไฟล์
RUN chown -R www-data:www-data /var/www/html/

EXPOSE 80
