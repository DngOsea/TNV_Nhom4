<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/Auth.php';
$volunteerData = require __DIR__ . '/../data/volunteer_data.php';

// Xử lý đăng xuất tài khoản
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['user']);
    header("Location: /");
    exit;
}

$currentUser = $_SESSION['user'] ?? null;
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

// TỔNG HỢP & ƯU TIÊN THÔNG BÁO HOẠT ĐỘNG TUYỂN NHÂN SỰ GẤP
$topAnnouncements = [];
$activitiesList = $volunteerData['activities'] ?? [];

$urgentNotes = [
    202 => [
        'is_urgent' => true,
        'badge' => 'TUYỂN GẤP',
        'badge_class' => 'badge-urgent',
        'prefix' => '[Ca Sáng Sớm 05:45]',
        'focus' => 'phát cháo dinh dưỡng & hỗ trợ bệnh nhân nặng tại Bệnh Viện K Tân Triều',
        'cta' => 'Ứng tuyển gấp'
    ],
    301 => [
        'is_urgent' => true,
        'badge' => 'TUYỂN GẤP',
        'badge_class' => 'badge-urgent',
        'prefix' => '[Đội Xe Máy 06:00]',
        'focus' => 'đưa đón thí sinh & điều tiết giao thông tại 15 điểm thi THPT',
        'cta' => 'Ứng tuyển gấp'
    ],
    101 => [
        'is_urgent' => true,
        'badge' => 'TUYỂN GẤP',
        'badge_class' => 'badge-urgent',
        'prefix' => '[Thực Địa]',
        'focus' => 'dọn rác và cải tạo mảng xanh ven sông Tô Lịch sáng Chủ Nhật',
        'cta' => 'Ứng tuyển gấp'
    ],
    201 => [
        'is_urgent' => true,
        'badge' => 'TUYỂN GẤP',
        'badge_class' => 'badge-urgent',
        'prefix' => '[Y Tế Sơ Cứu]',
        'focus' => 'đo huyết áp và hỗ trợ người hiến máu tại Viện Huyết Học Trung Ương',
        'cta' => 'Ứng tuyển gấp'
    ]
];

$urgentList = [];
$normalList = [];

foreach ($activitiesList as $act) {
    $actId = (int)$act['id'];
    $capacity = (int)($act['capacity'] ?? 0);
    $registered = (int)($act['registered'] ?? 0);
    $deficit = max(0, $capacity - $registered);

    if ($deficit <= 0) {
        continue;
    }

    if (isset($urgentNotes[$actId])) {
        $meta = $urgentNotes[$actId];
        $urgentList[] = [
            'id' => $actId,
            'is_urgent' => true,
            'badge' => $meta['badge'],
            'badge_class' => $meta['badge_class'],
            'text' => $meta['prefix'] . ' Đang cần gấp <strong>' . $deficit . ' tình nguyện viên</strong> ' . $meta['focus'] . '.',
            'link' => '/register.php?event_id=' . $actId,
            'cta' => $meta['cta'] . ' &rarr;'
        ];
    } else {
        $shortTitle = explode('—', $act['title'])[0] ?? $act['title'];
        $normalList[] = [
            'id' => $actId,
            'is_urgent' => false,
            'badge' => 'ĐANG MỞ ĐĂNG KÝ',
            'badge_class' => 'badge-open',
            'text' => 'Chiến dịch <strong>' . htmlspecialchars(trim($shortTitle)) . '</strong> còn tiếp nhận <strong>' . $deficit . ' chỉ tiêu</strong>.',
            'link' => '/register.php?event_id=' . $actId,
            'cta' => 'Đăng ký tham gia &rarr;'
        ];
    }
}

// Ưu tiên toàn bộ hoạt động tuyển gấp lên đầu tiên
$topAnnouncements = array_merge($urgentList, $normalList);

// Thêm thông báo chung toàn hệ thống
$topAnnouncements[] = [
    'id' => 0,
    'is_urgent' => false,
    'badge' => 'THÔNG BÁO',
    'badge_class' => 'badge-system',
    'text' => 'Hệ thống đang tiếp nhận đăng ký điều phối trực tuyến cho toàn bộ 8 chiến dịch trọng điểm 2026.',
    'link' => '/register.php',
    'cta' => 'Xem chi tiết đăng ký &rarr;'
];
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Hệ Thống Quản Lý & Phân Công Hoạt Động Tình Nguyện • D2L Volunteer</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/tokens.css">
<link rel="stylesheet" href="/assets/css/app.css">
<link rel="stylesheet" href="/assets/css/ql_tinh_nguyen.css?v=<?= time() ?>">
<script src="/assets/js/volunteer_portal.js?v=<?= time() ?>" defer></script>
<script src="/assets/js/banner_chuyen_dong.js?v=<?= time() ?>" defer></script>
</head>
<body class="volunteer-theme-body">
<a class="skip-link" href="#main">Đến nội dung chính</a>

<!-- THANH THÔNG BÁO ĐIỀU PHỐI (CẬP NHẬT LIÊN TỤC & ƯU TIÊN TUYỂN NHÂN SỰ GẤP) -->
<div class="top-announcement-bar" id="top_announcement_bar" role="region" aria-label="Bảng tin điều phối tình nguyện viên">
    <div class="announcement-container">
        <!-- Huy hiệu trạng thái trực tiếp -->
        <div class="announcement-live-indicator">
            <span class="live-pulse-dot" aria-hidden="true"></span>
            <span class="live-label">ĐIỀU PHỐI:</span>
        </div>

        <!-- Khung quay vòng thông báo cập nhật liên tục -->
        <div class="announcement-slider-wrap" id="announcement_slider_wrap" title="Dừng chuyển động khi rê chuột để xem chi tiết">
            <div class="announcement-slider-track" id="announcement_slider_track">
                <?php foreach ($topAnnouncements as $index => $item): ?>
                    <div class="announcement-slide-item <?= $index === 0 ? 'active' : '' ?>" data-index="<?= $index ?>" role="article">
                        <span class="announcement-badge <?= htmlspecialchars($item['badge_class']) ?>">
                            <?php if (!empty($item['is_urgent'])): ?>
                                <span class="badge-fire-dot" aria-hidden="true"></span>
                            <?php endif; ?>
                            <?= htmlspecialchars($item['badge']) ?>
                        </span>
                        <span class="announcement-message">
                            <?= $item['text'] ?>
                        </span>
                        <a href="<?= htmlspecialchars($item['link']) ?>" class="announcement-cta-link">
                            <?= $item['cta'] ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Cụm điều khiển nhanh & chỉ số đếm -->
        <div class="announcement-controls">
            <span class="announcement-counter" id="announcement_counter" aria-live="polite">1 / <?= count($topAnnouncements) ?></span>
            <button type="button" class="announcement-arrow-btn" id="announcement_prev_btn" title="Xem tin trước" aria-label="Thông báo trước">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
            </button>
            <button type="button" class="announcement-arrow-btn" id="announcement_next_btn" title="Xem tin kế tiếp" aria-label="Thông báo kế tiếp">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </button>
        </div>
    </div>
</div>

<!-- SITE HEADER CHÍNH -->
<header class="site-header-pro">
    <div class="header-container">
        <!-- Logo & Thương hiệu bên trái -->
        <a href="/" class="brand-link" title="Đội Tình Nguyện D2L">
            <div class="brand-badge-box">
                <span class="brand-acronym">D2L</span>
            </div>
            <div class="brand-text">
                <strong class="brand-title">Đội Tình Nguyện</strong>
            </div>
        </a>

        <!-- Menu Điều Hướng Chính (Tách biệt hoàn toàn theo vai trò thực tế) -->
        <nav class="main-nav-pro" aria-label="Điều hướng chính">
            <a href="/" class="nav-item <?= $currentPath === '/' ? 'active' : '' ?>">
                Trang Chủ
            </a>
            <a href="/hoat_dong.php" class="nav-item <?= $currentPath === '/hoat_dong.php' ? 'active' : '' ?>">
                Hoạt Động
            </a>

            <?php if (Auth::isCoordinator()): ?>
                <!-- MENU DÀNH RIÊNG CHO BAN CHỈ HUY / ĐIỀU PHỐI VIÊN -->
                <a href="/lich_truc.php" class="nav-item <?= $currentPath === '/lich_truc.php' ? 'active' : '' ?>">
                    Lịch Trực
                </a>
                <a href="/phan_cong.php" class="nav-item <?= $currentPath === '/phan_cong.php' ? 'active' : '' ?>" style="color: #dc2626; font-weight: 600;">
                    Điều Phối Ca Trực
                </a>
                <a href="/quan_ly_tnv.php" class="nav-item <?= $currentPath === '/quan_ly_tnv.php' ? 'active' : '' ?>">
                    Quản Lý TNV
                </a>
                <a href="/doi_soat_thuat_toan.php" class="nav-item <?= $currentPath === '/doi_soat_thuat_toan.php' ? 'active' : '' ?>">
                    Đối Soát Phân Công
                </a>
            <?php else: ?>
                <!-- MENU DÀNH CHO TÌNH NGUYỆN VIÊN & KHÁCH -->
                <a href="/lich_truc.php" class="nav-item <?= $currentPath === '/lich_truc.php' ? 'active' : '' ?>">
                    <?= Auth::isVolunteer() ? 'Ca Trực Của Tôi' : 'Lịch Trực' ?>
                </a>
            <?php endif; ?>

            <!-- TRANG ĐIỀN HỒ SƠ CÁ NHÂN & ĐĂNG KÝ CHIẾN DỊCH ONLINE -->
            <a href="/register.php" class="nav-item nav-highlight <?= $currentPath === '/register.php' ? 'active' : '' ?>">
                Hồ Sơ & Tham Gia Hoạt Động
            </a>
        </nav>

        <!-- KHU VỰC HIỂN THỊ TÀI KHOẢN NGƯỜI DÙNG GÓC TRÊN BÊN PHẢI -->
        <div class="header-user-zone">
            <?php if ($currentUser): ?>
                <!-- ĐÃ ĐĂNG NHẬP -->
                <div class="user-profile-badge" tabindex="0" role="button" aria-haspopup="true" aria-expanded="false" title="Nhấn để mở menu tài khoản">
                    <div class="avatar-circle" style="background-color: <?= htmlspecialchars($currentUser['avatar_color'] ?? '#059669') ?>;">
                        <span><?= htmlspecialchars($currentUser['avatar_letter'] ?? mb_substr($currentUser['name'] ?? 'U', 0, 1)) ?></span>
                        <span class="online-dot" title="Đang trực tuyến"></span>
                    </div>
                    <div class="user-meta">
                        <strong class="user-name"><?= htmlspecialchars($currentUser['name']) ?></strong>
                        <span class="user-position-tag" style="<?= Auth::isCoordinator() ? 'background:#dc2626; color:#ffffff;' : '' ?>">
                            <?= Auth::isCoordinator() ? 'Ban Chỉ Huy' : 'Tình Nguyện Viên' ?>
                        </span>
                    </div>
                    <span class="caret-down">&#9662;</span>

                    <!-- MENU THẢ XUỐNG CỦA TÀI KHOẢN -->
                    <div class="user-profile-dropdown" role="menu">
                        <div class="dropdown-header-card">
                            <div class="avatar-large" style="background-color: <?= htmlspecialchars($currentUser['avatar_color'] ?? '#059669') ?>;">
                                <?= htmlspecialchars($currentUser['avatar_letter'] ?? mb_substr($currentUser['name'] ?? 'U', 0, 1)) ?>
                            </div>
                            <div class="header-info">
                                <strong><?= htmlspecialchars($currentUser['name']) ?></strong>
                                <span class="role-highlight" style="<?= Auth::isCoordinator() ? 'color:#dc2626; font-weight:700;' : '' ?>">
                                    <?= Auth::getRoleDisplayBadge() ?>
                                </span>
                                <span class="email-sub"><?= htmlspecialchars($currentUser['email'] ?? '') ?></span>
                            </div>
                        </div>

                        <div class="dropdown-stats-row">
                            <div class="stat-pill">
                                <strong><?= (int)($currentUser['volunteer_hours'] ?? 8) ?> giờ</strong>
                                <span>Tích lũy</span>
                            </div>
                            <div class="stat-pill">
                                <strong><?= (int)($currentUser['events_joined'] ?? 2) ?></strong>
                                <span>Chiến dịch</span>
                            </div>
                            <div class="stat-pill">
                                <strong>Hạng</strong>
                                <span><?= htmlspecialchars($currentUser['badge'] ?? 'Thành viên') ?></span>
                            </div>
                        </div>

                        <div class="dropdown-divider"></div>

                        <div class="dropdown-links">
                            <?php if (Auth::isCoordinator()): ?>
                                <a href="/phan_cong.php" class="dropdown-link-item" style="color: #dc2626; font-weight: 600;">
                                    Bảng Điều Phối Lực Lượng
                                </a>
                                <a href="/doi_soat_thuat_toan.php" class="dropdown-link-item">
                                    Báo Cáo Đối Soát Phân Công
                                </a>
                                <a href="/lich_truc.php" class="dropdown-link-item">
                                    Lịch Trực Chung Cả Đội
                                </a>
                            <?php else: ?>
                                <a href="/lich_truc.php" class="dropdown-link-item">
                                    Ca Trực Cá Nhân Của Tôi
                                </a>
                                <a href="/register.php" class="dropdown-link-item">
                                    Thẻ Tình Nguyện Viên Điện Tử
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="dropdown-divider"></div>

                        <a href="?action=logout" class="dropdown-logout-item">
                            Đăng xuất tài khoản
                        </a>
                    </div>
                </div>

            <?php else: ?>
                <!-- CHƯA ĐĂNG NHẬP -->
                <div class="auth-buttons-group">
                    <a href="/dang_nhap.php" class="btn-login-header">Đăng Nhập</a>
                    <a href="/dang_ky_tk.php" class="btn-register-header">Đăng Ký Tài Khoản</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>

<main id="main" tabindex="-1">
<?php if (!empty($_SESSION['flash_message'])): ?>
    <div id="flash_banner_toast" class="app-toast-container" style="position: fixed; top: 80px; right: 24px; z-index: 9999;">
        <div class="app-toast-item show" style="background: #059669; color: #ffffff; font-weight: 600; box-shadow: 0 8px 24px rgba(5,150,105,0.35); padding: 14px 20px; border-radius: 8px;">
            <span class="toast-msg"><?= htmlspecialchars($_SESSION['flash_message']) ?></span>
        </div>
    </div>
    <script>
        setTimeout(function() {
            var el = document.getElementById('flash_banner_toast');
            if (el) {
                el.style.opacity = '0';
                el.style.transition = 'opacity 0.5s ease';
                setTimeout(function() { el.remove(); }, 500);
            }
        }, 4000);
    </script>
    <?php unset($_SESSION['flash_message']); ?>
<?php endif; ?>
