<?php
declare(strict_types=1);

/**
 * CẤU HÌNH KẾT NỐI CƠ SỞ DỮ LIỆU MYSQL / MYSQL WORKBENCH
 * Hệ Thống Quản Lý Tình Nguyện Viên & Hoạt Động Xã Hội D2L
 * Tương thích kết nối MySQL Workbench qua cổng 3307 (XAMPP MariaDB) và 3306 (MySQL 8.0)
 */
return [
    'driver'   => 'mysql',
    'host'     => '127.0.0.1',
    'port'     => 3307,
    'dbname'   => 'd2l_volunteers',
    'username' => 'root',
    'password' => '',
    'charset'  => 'utf8mb4'
];
