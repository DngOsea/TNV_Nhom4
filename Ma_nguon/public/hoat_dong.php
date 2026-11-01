<?php
require __DIR__ . '/../src/Input.php';
require __DIR__ . '/../src/Auth.php';
require __DIR__ . '/../src/EventFilter.php';
require_once __DIR__ . '/../src/ActivityManager.php';
require_once __DIR__ . '/../src/Pagination.php';

$volunteerData = require __DIR__ . '/../data/volunteer_data.php';
$categories = $volunteerData['categories'];
$allActivities = ActivityManager::getAllActivities();

$selectedCat = field($_GET, 'cat');
$selectedShift = field($_GET, 'shift');
$selectedScale = field($_GET, 'scale');
$selectedRegion = field($_GET, 'region');
$selectedStatus = field($_GET, 'status');
$q = field($_GET, 'q');

// Xử lý nộp form đăng ký ca trực tuyến ngay trên popup modal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && field($_POST, 'action') === 'register_shifts') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $activityId = field($_POST, 'activity_id');
    $selectedShifts = isset($_POST['selected_shifts']) && is_array($_POST['selected_shifts']) ? $_POST['selected_shifts'] : [];
    $note = trim(field($_POST, 'volunteer_note'));

    $user = Auth::getUser();
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

    if (!$user) {
        $_SESSION['flash_error'] = 'Vui lòng cung cấp Họ tên và Số điện thoại liên hệ để xác nhận đăng ký.';
    } elseif (empty($selectedShifts)) {
        $_SESSION['flash_error'] = 'Vui lòng tích chọn ít nhất một ca làm việc từ bảng lịch trước khi xác nhận.';
    } else {
        $regResult = ActivityManager::registerVolunteerShifts($user, $activityId, $selectedShifts, $note);
        if ($regResult['success']) {
            $_SESSION['flash_success'] = 'Đăng ký thành công ' . $regResult['count'] . ' đợt hoạt động trong chương trình "' . ($regResult['activity']['title'] ?? '') . '"! Thông tin ca làm việc đã được cập nhật vào mục Hoạt Động Của Tôi.';
            header('Location: /hoat_dong.php?registered=1');
            exit;
        } else {
            $_SESSION['flash_error'] = $regResult['message'] ?? 'Đăng ký không thành công. Vui lòng thử lại.';
        }
    }
}

// Lọc theo điều kiện đa thuộc tính
$filtered = array_filter($allActivities, function($act) use ($selectedCat, $selectedShift, $selectedScale, $selectedRegion, $selectedStatus, $q) {
    if ($selectedCat !== '' && $act['category'] !== $selectedCat) {
        return false;
    }
    if ($q !== '' && stripos($act['title'], $q) === false && stripos($act['summary'], $q) === false && stripos($act['location'], $q) === false && stripos($act['leader_name'] ?? '', $q) === false) {
        return false;
    }
    if ($selectedShift !== '') {
        $hasShift = false;
        foreach ($act['shifts'] as $s) {
            if (stripos($s, $selectedShift) !== false) {
                $hasShift = true;
                break;
            }
        }
        if (!$hasShift) return false;
    }
    if ($selectedScale !== '') {
        $cap = (int)($act['capacity'] ?? 20);
        if ($selectedScale === 'micro' && $cap >= 15) return false;
        if ($selectedScale === 'medium' && ($cap < 15 || $cap > 35)) return false;
        if ($selectedScale === 'macro' && $cap <= 35) return false;
    }
    if ($selectedRegion !== '') {
        $loc = (string)($act['location'] ?? '');
        if ($selectedRegion === 'hanoi' && !str_contains($loc, 'Hà Nội')) return false;
        if ($selectedRegion === 'north' && !preg_match('/(Lào Cai|Yên Bái|Hà Giang|Sơn La|Hải Phòng|Ninh Bình|Bắc Ninh|Thái Bình)/i', $loc)) return false;
        if ($selectedRegion === 'central' && !preg_match('/(Đà Nẵng|Quảng Bình|Hà Tĩnh|Quảng Ngãi|Quảng Nam|Ninh Thuận)/i', $loc)) return false;
        if ($selectedRegion === 'south' && !preg_match('/(Hồ Chí Minh|TP\.HCM|Cần Thơ|Cần Giờ|Bến Tre)/i', $loc)) return false;
    }
    if ($selectedStatus !== '') {
        $st = $act['status'] ?? 'recruiting';
        $isFull = ($act['registered'] >= $act['capacity']);
        $isUrgent = !empty($act['is_urgent']);
        if ($selectedStatus === 'urgent' && !$isUrgent) return false;
        if ($selectedStatus === 'recruiting' && ($isFull || $st === 'completed')) return false;
        if ($selectedStatus === 'full' && !$isFull) return false;
        if ($selectedStatus === 'completed' && $st !== 'completed') return false;
    }
    return true;
});

// Phân trang danh sách hoạt động (6 hoạt động mỗi trang)
$page = max(1, (int)field($_GET, 'page', '1'));
$paginationData = Pagination::paginate($filtered, $page, 6, true);
$paginatedActivities = $paginationData['items'];

require __DIR__ . '/../templates/header.php';
?>

<div class="page-heading-pro">
    <div class="heading-container" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <span class="sub-badge">DANH MỤC CHIẾN DỊCH</span>
            <h1>Danh Sách Hoạt Động Tình Nguyện</h1>
            <p>Tổng hợp <?= count($allActivities) ?> hoạt động trọng điểm phân theo 5 nhóm chủ đề thực tế — Lựa chọn chương trình phù hợp để tham gia cống hiến.</p>
        </div>
        <?php if (Auth::isCoordinator()): ?>
            <a href="/tao_su_kien.php" class="btn-primary" style="padding: 12px 24px; font-weight: 700; background: #dc2626; border-radius: 8px;">
                + Thiết Lập Sự Kiện Mới
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="page-content-wrapper">
    <!-- THÔNG BÁO KẾT QUẢ ĐĂNG KÝ HOẠT ĐỘNG -->
    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert-success-banner" style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; box-shadow: 0 4px 12px rgba(5,150,105,0.08);">
            <div style="display: flex; align-items: center; gap: 14px;">
                <span style="display: inline-flex; width: 32px; height: 32px; background: #059669; color: #ffffff; border-radius: 50%; align-items: center; justify-content: center; font-size: 1.1rem; font-weight: 700; flex-shrink: 0;">&#10003;</span>
                <span style="color: #065f46; font-size: 0.96rem; font-weight: 600; line-height: 1.5;"><?= htmlspecialchars($_SESSION['flash_success']) ?></span>
            </div>
            <a href="/lich_truc.php?tab=my_shifts" class="btn-primary" style="padding: 10px 20px; font-size: 0.9rem; font-weight: 700; background: #059669; border-radius: 8px; text-decoration: none; white-space: nowrap;">
                Xem Hoạt Động Của Tôi &rarr;
            </a>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert-error-banner" style="background: #fff1f2; border: 1px solid #fecdd3; border-radius: 12px; padding: 14px 20px; margin-bottom: 24px; color: #be123c; font-weight: 500;">
            <strong style="color: #9f1239;">Thông báo:</strong> <?= htmlspecialchars($_SESSION['flash_error']) ?>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <!-- KHUNG LỌC HOẠT ĐỘNG THU GỌN 1 DÒNG DUY NHẤT -->
    <div class="advanced-filter-card" style="padding: 10px 14px; background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.04);">
        <form method="get" class="filter-single-row-form" style="display: flex; gap: 8px; align-items: center; flex-wrap: nowrap; overflow-x: auto; margin: 0; padding-bottom: 2px;">
            <!-- Ô tìm kiếm thu gọn -->
            <div style="flex: 2; min-width: 170px;">
                <input type="text" id="q" name="q" placeholder="🔍 Tìm tên, địa bàn..." value="<?= htmlspecialchars($q) ?>" style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; box-sizing: border-box; background: #f8fafc;">
            </div>

            <!-- Nhóm chủ đề -->
            <div style="flex: 1.3; min-width: 135px;">
                <select id="cat" name="cat" style="width: 100%; height: 38px; padding: 0 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; box-sizing: border-box; background: #ffffff;">
                    <option value="">— Chủ đề —</option>
                    <?php foreach ($categories as $catKey => $cat): ?>
                        <option value="<?= $catKey ?>" <?= $selectedCat === $catKey ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['short_name'] ?? $cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Ca làm việc -->
            <div style="flex: 1.1; min-width: 110px;">
                <select id="shift" name="shift" style="width: 100%; height: 38px; padding: 0 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; box-sizing: border-box; background: #ffffff;">
                    <option value="">— Ca trực —</option>
                    <option value="Sáng" <?= $selectedShift === 'Sáng' ? 'selected' : '' ?>>Ca Sáng</option>
                    <option value="Chiều" <?= $selectedShift === 'Chiều' ? 'selected' : '' ?>>Ca Chiều</option>
                    <option value="Tối" <?= $selectedShift === 'Tối' ? 'selected' : '' ?>>Ca Tối</option>
                    <option value="Đêm" <?= $selectedShift === 'Đêm' ? 'selected' : '' ?>>Ca Đêm</option>
                    <option value="Cả ngày" <?= $selectedShift === 'Cả ngày' ? 'selected' : '' ?>>Cả ngày</option>
                </select>
            </div>

            <!-- Quy mô -->
            <div style="flex: 1.1; min-width: 110px;">
                <select id="scale" name="scale" style="width: 100%; height: 38px; padding: 0 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; box-sizing: border-box; background: #ffffff;">
                    <option value="">— Quy mô —</option>
                    <option value="micro" <?= $selectedScale === 'micro' ? 'selected' : '' ?>>Vi mô (&lt; 15)</option>
                    <option value="medium" <?= $selectedScale === 'medium' ? 'selected' : '' ?>>Vừa (15 - 35)</option>
                    <option value="macro" <?= $selectedScale === 'macro' ? 'selected' : '' ?>>Lớn (&gt; 35)</option>
                </select>
            </div>

            <!-- Địa bàn -->
            <div style="flex: 1.1; min-width: 110px;">
                <select id="region" name="region" style="width: 100%; height: 38px; padding: 0 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; box-sizing: border-box; background: #ffffff;">
                    <option value="">— Địa bàn —</option>
                    <option value="hanoi" <?= $selectedRegion === 'hanoi' ? 'selected' : '' ?>>Hà Nội</option>
                    <option value="north" <?= $selectedRegion === 'north' ? 'selected' : '' ?>>Tây Bắc</option>
                    <option value="central" <?= $selectedRegion === 'central' ? 'selected' : '' ?>>Miền Trung</option>
                    <option value="south" <?= $selectedRegion === 'south' ? 'selected' : '' ?>>TP.HCM & Nam</option>
                </select>
            </div>

            <!-- Trạng thái -->
            <div style="flex: 1.1; min-width: 110px;">
                <select id="status" name="status" style="width: 100%; height: 38px; padding: 0 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; box-sizing: border-box; background: #ffffff;">
                    <option value="">— Trạng thái —</option>
                    <option value="urgent" <?= $selectedStatus === 'urgent' ? 'selected' : '' ?>>Tuyển Gấp</option>
                    <option value="recruiting" <?= $selectedStatus === 'recruiting' ? 'selected' : '' ?>>Đang Tuyển</option>
                    <option value="full" <?= $selectedStatus === 'full' ? 'selected' : '' ?>>Đã Đủ</option>
                    <option value="completed" <?= $selectedStatus === 'completed' ? 'selected' : '' ?>>Đã Xong</option>
                </select>
            </div>

            <!-- Nút hành động -->
            <div style="display: flex; gap: 6px; flex-shrink: 0;">
                <button type="submit" class="btn-primary" style="height: 38px; padding: 0 14px; font-size: 13px; font-weight: 700; border-radius: 6px; white-space: nowrap; display: inline-flex; align-items: center; gap: 5px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <span>Lọc</span>
                </button>
                <a href="/hoat_dong.php" class="btn-cancel" style="height: 38px; line-height: 38px; padding: 0 12px; font-size: 13px; border-radius: 6px; white-space: nowrap; text-decoration: none; display: inline-flex; align-items: center;">
                    Đặt lại
                </a>
            </div>
        </form>
    </div>

    <!-- TỔNG KẾT KẾT QUẢ VÀ TABS CHỦ ĐỀ -->
    <div class="result-summary-bar">
        <div class="result-summary-count">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <span>Tìm thấy <strong><?= count($filtered) ?></strong> hoạt động tình nguyện</span>
        </div>
        <div class="category-quick-pills">
            <a href="/hoat_dong.php" class="cat-pill <?= $selectedCat === '' ? 'active' : '' ?>">Tất cả (<?= count($allActivities) ?>)</a>
            <?php foreach ($categories as $k => $c): ?>
                <?php $cnt = count(array_filter($allActivities, fn($a) => $a['category'] === $k)); ?>
                <a href="/hoat_dong.php?cat=<?= $k ?>" class="cat-pill <?= $selectedCat === $k ? 'active' : '' ?>">
                    <?= htmlspecialchars($c['short_name']) ?> (<?= $cnt ?>)
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- DANH SÁCH THẺ HOẠT ĐỘNG -->
    <?php if (empty($filtered)): ?>
        <div class="no-activity-found">
            <h3>Không tìm thấy hoạt động phù hợp</h3>
            <p>Vui lòng thử tìm kiếm lại với tiêu chí khác hoặc xem toàn bộ danh sách.</p>
            <a href="/hoat_dong.php" class="btn-primary">Xem toàn bộ hoạt động</a>
        </div>
    <?php else: ?>
        <div class="activities-grid">
            <?php foreach ($paginatedActivities as $act): ?>
                <?php 
                    $catInfo = $categories[$act['category']] ?? [
                        'name' => $act['category'],
                        'short_name' => $act['category'],
                        'color' => '#059669',
                        'bg_color' => '#ecfdf5',
                        'code' => 'TNV'
                    ];
                    $isFull = $act['registered'] >= $act['capacity'];
                    $pct = min(100, round(($act['registered'] / max(1, $act['capacity'])) * 100));
                    $remaining = max(0, $act['capacity'] - $act['registered']);
                    $jsonData = htmlspecialchars(json_encode($act), ENT_QUOTES, 'UTF-8');
                ?>
                <article class="activity-card-item" data-category="<?= htmlspecialchars($act['category']) ?>">
                    <div class="act-card-header">
                        <span class="act-cat-badge" style="background-color: <?= $catInfo['bg_color'] ?>; color: <?= $catInfo['color'] ?>; border: 1px solid <?= $catInfo['color'] ?>40;">
                            [<?= htmlspecialchars($catInfo['code'] ?? 'TNV') ?>] <?= htmlspecialchars($catInfo['short_name'] ?? $catInfo['name']) ?>
                        </span>
                        <?php if ($isFull): ?>
                            <span class="badge-status-full">Đã đủ quân số</span>
                        <?php else: ?>
                            <span class="badge-status-open">Còn <?= $remaining ?> suất</span>
                        <?php endif; ?>
                    </div>

                    <h3 class="act-title">
                        <a href="#" class="btn-view-activity-detail" data-activity='<?= $jsonData ?>'>
                            <?= htmlspecialchars($act['title']) ?>
                        </a>
                    </h3>

                    <p class="act-summary-snippet">
                        <?= htmlspecialchars($act['summary']) ?>
                    </p>

                    <div class="act-info-list">
                        <div class="act-info-item">
                            <span class="info-label">Thời gian:</span>
                            <span class="info-text"><?= htmlspecialchars($act['date_string']) ?></span>
                        </div>
                        <div class="act-info-item">
                            <span class="info-label">Địa điểm:</span>
                            <span class="info-text"><?= htmlspecialchars($act['location']) ?></span>
                        </div>
                        <div class="act-info-item">
                            <span class="info-label">Ca trực:</span>
                            <span class="info-text"><?= htmlspecialchars(implode(', ', $act['shifts'])) ?></span>
                        </div>
                        <div class="act-info-item">
                            <span class="info-label">Chỉ huy:</span>
                            <span class="info-text"><?= htmlspecialchars($act['leader_name']) ?> (<?= htmlspecialchars($act['leader_phone']) ?>)</span>
                        </div>
                    </div>

                    <div class="skills-preview-row">
                        <span class="skills-label">Ưu tiên:</span>
                        <?php foreach (array_slice($act['skills_needed'], 0, 3) as $sk): ?>
                            <span class="pill-skill-sm"><?= htmlspecialchars($sk) ?></span>
                        <?php endforeach; ?>
                    </div>

                    <div class="recruitment-progress-box">
                        <div class="progress-labels">
                            <span>Đã đăng ký: <strong><?= (int)$act['registered'] ?>/<?= (int)$act['capacity'] ?> TNV</strong></span>
                            <span><strong><?= $pct ?>%</strong></span>
                        </div>
                        <div class="progress-bar-track">
                            <div class="progress-bar-fill <?= $isFull ? 'fill-full' : '' ?>" style="width: <?= $pct ?>%;"></div>
                        </div>
                    </div>

                    <div class="act-card-actions">
                        <button type="button" class="btn-view-detail btn-view-activity-detail" data-activity='<?= $jsonData ?>'>
                            Chi Tiết
                        </button>

                        <?php if (Auth::isCoordinator()): ?>
                            <a href="/chinh_sua_du_an.php?id=<?= $act['id'] ?>&from=hoat_dong" class="btn-action-pro" style="display: inline-flex; align-items: center; justify-content: center; gap: 4px; padding: 7px 12px; border-radius: 6px; background: #0284c7; color: #ffffff; text-decoration: none; font-size: 0.82rem; font-weight: 700; border: none; box-shadow: 0 1px 3px rgba(2,132,199,0.3);" title="Chỉnh sửa thông tin chiến dịch">
                                &#9998; Sửa Dự Án
                            </a>
                        <?php endif; ?>

                        <?php if ($isFull): ?>
                            <button type="button" class="btn-act-join disabled" disabled title="Hoạt động này đã đủ số lượng TNV">
                                Đã Đủ Quân Số
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn-act-join btn-open-register-modal" data-activity='<?= $jsonData ?>'>
                                Đăng Ký Tham Gia
                            </button>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <!-- THANH PHÂN TRANG HOẠT ĐỘNG -->
        <?= Pagination::render(
            $paginationData['page'], 
            $paginationData['total_pages'], 
            $paginationData['total'], 
            $paginationData['per_page'], 
            '/hoat_dong.php', 
            ['cat' => $selectedCat, 'shift' => $selectedShift, 'scale' => $selectedScale, 'region' => $selectedRegion, 'status' => $selectedStatus, 'q' => $q]
        ) ?>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
