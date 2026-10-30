<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/Input.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/ActivityManager.php';

/**
 * Tính năng đăng ký hoạt động đã được tích hợp thành Popup Modal ngay trên Danh mục hoạt động (hoat_dong.php)
 * để tối ưu trải nghiệm và luồng vận hành, không cần mở trang riêng.
 * Chuyển hướng người dùng sang hoat_dong.php kèm ID hoạt động để tự động bật modal đăng ký.
 */
$selectedEventId = field($_POST, 'activity_id') ?: field($_GET, 'event_id') ?: field($_GET, 'activity_id');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && field($_POST, 'action') === 'register_shifts') {
    $user = Auth::getUser();
    $selectedShifts = isset($_POST['selected_shifts']) && is_array($_POST['selected_shifts']) ? $_POST['selected_shifts'] : [];
    $note = trim(field($_POST, 'volunteer_note'));

    if (!$user) {
        $guestName = trim(field($_POST, 'guest_name'));
        $guestPhone = trim(field($_POST, 'guest_phone'));
        $guestEmail = trim(field($_POST, 'guest_email'));
        if ($guestName !== '' || $guestPhone !== '') {
            $user = [
                'id' => 'GUEST-' . strtoupper(substr(md5(uniqid('', true)), 0, 6)),
                'name' => $guestName ?: 'Tình Nguyện Viên Khách',
                'phone' => $guestPhone,
                'email' => $guestEmail,
                'role' => 'volunteer'
            ];
            $_SESSION['guest_user'] = $user;
        }
    }

    if ($user && !empty($selectedShifts)) {
        $regResult = ActivityManager::registerVolunteerShifts($user, $selectedEventId, $selectedShifts, $note);
        if ($regResult['success']) {
            $_SESSION['flash_success'] = 'Đăng ký thành công ' . $regResult['count'] . ' đợt hoạt động trong chương trình "' . ($regResult['activity']['title'] ?? '') . '"! Thông tin đã chuyển sang Hoạt Động Của Tôi.';
            header('Location: /hoat_dong.php?registered=1');
            exit;
        }
    }
}

$redirectUrl = '/hoat_dong.php' . ($selectedEventId ? '?open_reg=' . urlencode((string)$selectedEventId) : '');
header('Location: ' . $redirectUrl);
exit;
