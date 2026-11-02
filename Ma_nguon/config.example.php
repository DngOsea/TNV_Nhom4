<?php
/**
 * CẤU HÌNH KẾT NỐI CƠ SỞ DỮ LIỆU MYSQL (TÙY CHỌN)
 * Đổi tên file này thành config.php và chỉnh sửa thông tin theo máy của bạn nếu muốn dùng MySQL.
 * Nếu không có file này, hệ thống sẽ tự động dùng SQLite (data/volunteer_platform.sqlite) hoặc biến môi trường.
 */
return [
    'driver'   => 'mysql',          // 'mysql' hoặc 'auto' hoặc 'sqlite'
    'host'     => '127.0.0.1',      // Địa chỉ MySQL server
    'port'     => 3306,             // Cổng MySQL (mặc định 3306 hoặc 3307)
    'dbname'   => 'd2l_volunteers', // Tên Database
    'username' => 'root',           // Tên tài khoản MySQL
    'password' => '',               // Mật khẩu MySQL của bạn
    'charset'  => 'utf8mb4'
];

