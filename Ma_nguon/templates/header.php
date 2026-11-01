<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Database.php';
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
            'link' => '/hoat_dong.php?event_id=' . $actId,
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
            'link' => '/hoat_dong.php?event_id=' . $actId,
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
    'link' => '/hoat_dong.php',
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
<link rel="stylesheet" href="/assets/css/pro_design_theme.css?v=<?= time() ?>">
<script src="/assets/js/volunteer_portal.js?v=<?= time() ?>" defer></script>
<script src="/assets/js/banner_chuyen_dong.js?v=<?= time() ?>" defer></script>
<style>
/* TỐI ƯU GIAO DIỆN NAVBAR KHÔNG BỊ TRÀN VIEWPORT */
body { overflow-x: hidden !important; }
.site-header-pro { width: 100% !important; overflow-x: clip !important; }
.site-header-pro .header-container { padding: 6px 14px !important; gap: 8px !important; max-width: 100% !important; box-sizing: border-box !important; overflow-x: clip !important; }
.site-header-pro .brand-link { flex-shrink: 0 !important; gap: 8px !important; }
.site-header-pro .brand-title { font-size: 1.02rem !important; }
.site-header-pro .main-nav-pro { margin: 0 4px !important; gap: 4px !important; justify-content: center !important; flex: 1 !important; min-width: 0 !important; flex-wrap: nowrap !important; }
.site-header-pro .nav-item { padding: 6px 8px !important; font-size: 0.84rem !important; flex-shrink: 0 !important; white-space: nowrap !important; }
.site-header-pro .header-user-zone { flex-shrink: 0 !important; margin-left: auto !important; }
.site-header-pro .user-profile-badge { padding: 4px 10px !important; gap: 6px !important; }
.site-header-pro .user-name { max-width: 130px !important; font-size: 0.84rem !important; }
</style>
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
                <div class="nav-item-dropdown-wrap">
                    <a href="/lich_truc.php" class="nav-item <?= $currentPath === '/lich_truc.php' ? 'active' : '' ?>">
                        <span>Lịch Trực</span>
                        <span class="nav-caret">&#9662;</span>
                    </a>
                    <div class="nav-sub-dropdown" role="menu">
                        <a href="/lich_truc.php?tab=all_shifts" class="nav-sub-link">
                            <span class="sub-icon">&#128197;</span>
                            <div class="sub-text">
                                <strong>Lịch Toàn Đội</strong>
                                <small>Lịch trực chung toàn đội</small>
                            </div>
                        </a>
                        <a href="/lich_truc.php?tab=my_shifts" class="nav-sub-link">
                            <span class="sub-icon">&#127919;</span>
                            <div class="sub-text">
                                <strong>Ca Của Tôi</strong>
                                <small>Ca trực phụ trách chỉ huy</small>
                            </div>
                        </a>
                    </div>
                </div>

                <div class="nav-item-dropdown-wrap">
                    <a href="/phan_cong.php" class="nav-item nav-item-bch <?= in_array($currentPath, ['/phan_cong.php', '/phe_duyet_vang.php', '/admin_import.php', '/doi_soat_thuat_toan.php'], true) ? 'active' : '' ?>">
                        <span>Điều Phối</span>
                        <span class="nav-bch-pill">BCH</span>
                        <span class="nav-caret">&#9662;</span>
                    </a>
                    <div class="nav-sub-dropdown" role="menu">
                        <a href="/phan_cong.php" class="nav-sub-link">
                            <span class="sub-icon chip-ruby">&#9881;</span>
                            <div class="sub-text">
                                <strong>Phân Công Ca</strong>
                                <small>Xếp lịch và điều động nhân sự</small>
                            </div>
                        </a>
                        <a href="/phe_duyet_vang.php" class="nav-sub-link">
                            <span class="sub-icon chip-amber">&#9203;</span>
                            <div class="sub-text">
                                <strong>Duyệt Báo Vắng</strong>
                                <small>Xét duyệt đơn xin vắng ca</small>
                            </div>
                        </a>
                        <a href="/admin_import.php" class="nav-sub-link">
                            <span class="sub-icon chip-emerald">&#128194;</span>
                            <div class="sub-text">
                                <strong>Quản Lý Dự Án & TNV</strong>
                                <small>Danh mục hoạt động và nhân sự</small>
                            </div>
                        </a>
                        <a href="/doi_soat_thuat_toan.php" class="nav-sub-link">
                            <span class="sub-icon chip-sapphire">&#128202;</span>
                            <div class="sub-text">
                                <strong>Báo Cáo Hiệu Quả</strong>
                                <small>Đánh giá phương án phân ca</small>
                            </div>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- MENU DÀNH CHO TÌNH NGUYỆN VIÊN & KHÁCH -->
                <div class="nav-item-dropdown-wrap">
                    <a href="/lich_truc.php" class="nav-item <?= $currentPath === '/lich_truc.php' ? 'active' : '' ?>">
                        <span>Lịch Trực</span>
                        <span class="nav-caret">&#9662;</span>
                    </a>
                    <div class="nav-sub-dropdown" role="menu">
                        <a href="/lich_truc.php?tab=my_shifts" class="nav-sub-link">
                            <span class="sub-icon">&#127919;</span>
                            <div class="sub-text">
                                <strong>Ca Của Tôi</strong>
                                <small>Lịch trực cá nhân và báo vắng</small>
                            </div>
                        </a>
                        <a href="/lich_truc.php?tab=all_shifts" class="nav-sub-link">
                            <span class="sub-icon">&#128197;</span>
                            <div class="sub-text">
                                <strong>Lịch Toàn Đội</strong>
                                <small>Lịch trực chung toàn đơn vị</small>
                            </div>
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- TRANG HỒ SƠ TÌNH NGUYỆN VIÊN -->
            <div class="nav-item-dropdown-wrap">
                <a href="/profile.php" class="nav-item nav-highlight nav-dropdown-trigger <?= in_array($currentPath, ['/profile.php', '/register.php'], true) ? 'active' : '' ?>">
                    <span>Hồ Sơ</span>
                    <span class="nav-caret">&#9662;</span>
                </a>
                <div class="nav-sub-dropdown" role="menu">
                    <a href="/profile.php?section=info" class="nav-sub-link">
                        <span class="sub-icon">&#128100;</span>
                        <div class="sub-text">
                            <strong>Thông Tin Cá Nhân</strong>
                            <small>Họ tên, SĐT và liên hệ</small>
                        </div>
                    </a>
                    <a href="/profile.php?section=skills" class="nav-sub-link">
                        <span class="sub-icon">&#9874;</span>
                        <div class="sub-text">
                            <strong>Kỹ Năng</strong>
                            <small>Chuyên môn và năng lực</small>
                        </div>
                    </a>
                    <a href="/profile.php?section=schedule" class="nav-sub-link">
                        <span class="sub-icon">&#128197;</span>
                        <div class="sub-text">
                            <strong>Lịch Rảnh</strong>
                            <small>Thời gian rảnh và báo bận</small>
                        </div>
                    </a>
                    <a href="/profile.php?section=card" class="nav-sub-link">
                        <span class="sub-icon">&#127379;</span>
                        <div class="sub-text">
                            <strong>Thẻ Điện Tử</strong>
                            <small>Mã định danh và mã QR</small>
                        </div>
                    </a>
                    <a href="/profile.php?section=emergency" class="nav-sub-link">
                        <span class="sub-icon">&#9742;</span>
                        <div class="sub-text">
                            <strong>Liên Hệ Khẩn Cấp</strong>
                            <small>Thông tin liên hệ người thân</small>
                        </div>
                    </a>
                </div>
            </div>
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
                                <a href="/phan_cong.php" class="dropdown-link-item">
                                    <span class="dropdown-icon-chip chip-ruby">&#9881;</span>
                                    <span class="dropdown-link-label">Phân Công Ca Trực</span>
                                </a>
                                <a href="/phe_duyet_vang.php" class="dropdown-link-item" style="display: flex; justify-content: space-between; align-items: center;">
                                    <div style="display: inline-flex; align-items: center; gap: 10px;">
                                        <span class="dropdown-icon-chip chip-amber">&#9203;</span>
                                        <span class="dropdown-link-label">Duyệt Báo Vắng</span>
                                    </div>
                                    <?php 
                                    $pendingAbsences = Database::getInstance()->getPendingAbsenceCount();
                                    if ($pendingAbsences > 0): ?>
                                        <span style="background: linear-gradient(135deg, #e11d48, #be123c); color: #ffffff; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 800; box-shadow: 0 2px 6px rgba(225,29,72,0.35);"><?= $pendingAbsences ?></span>
                                    <?php endif; ?>
                                </a>
                                <a href="/admin_import.php" class="dropdown-link-item">
                                    <span class="dropdown-icon-chip chip-emerald">&#128194;</span>
                                    <span class="dropdown-link-label">Quản Lý Dự Án & TNV</span>
                                </a>
                                <a href="/tao_su_kien.php" class="dropdown-link-item">
                                    <span class="dropdown-icon-chip chip-cyan">&#10010;</span>
                                    <span class="dropdown-link-label">Tạo Hoạt Động Mới</span>
                                </a>
                                <a href="/doi_soat_thuat_toan.php" class="dropdown-link-item">
                                    <span class="dropdown-icon-chip chip-sapphire">&#128202;</span>
                                    <span class="dropdown-link-label">Báo Cáo Hiệu Quả</span>
                                </a>
                                <a href="/lich_truc.php" class="dropdown-link-item">
                                    <span class="dropdown-icon-chip chip-violet">&#128197;</span>
                                    <span class="dropdown-link-label">Lịch Toàn Đội</span>
                                </a>
                                <a href="/profile.php" class="dropdown-link-item">
                                    <span class="dropdown-icon-chip chip-slate">&#128100;</span>
                                    <span class="dropdown-link-label">Hồ Sơ Cá Nhân</span>
                                </a>
                            <?php else: ?>
                                <a href="/profile.php" class="dropdown-link-item">
                                    <span class="dropdown-icon-chip chip-emerald">&#128100;</span>
                                    <span class="dropdown-link-label">Hồ Sơ Cá Nhân</span>
                                </a>
                                <a href="/hoat_dong.php" class="dropdown-link-item">
                                    <span class="dropdown-icon-chip chip-sapphire">&#128640;</span>
                                    <span class="dropdown-link-label">Đăng Ký Ca Mới</span>
                                </a>
                                <a href="/lich_truc.php?tab=my_shifts" class="dropdown-link-item">
                                    <span class="dropdown-icon-chip chip-amber">&#128197;</span>
                                    <span class="dropdown-link-label">Ca Của Tôi</span>
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
