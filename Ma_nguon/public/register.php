<?php
declare(strict_types=1);

/**
 * TRANG ĐIỀU HƯỚNG ĐĂNG KÝ HOẠT ĐỘNG HOẶC HỒ SƠ TÌNH NGUYỆN VIÊN
 */
if (!empty($_GET['event_id'])) {
    header('Location: /hoat_dong.php?event_id=' . urlencode((string)$_GET['event_id']));
    exit;
}

require __DIR__ . '/profile.php';
