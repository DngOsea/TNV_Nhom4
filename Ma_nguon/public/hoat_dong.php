<?php
require __DIR__ . '/../src/Input.php';
require __DIR__ . '/../src/EventFilter.php';
require_once __DIR__ . '/../src/LocationManager.php';
require_once __DIR__ . '/../src/ActivityManager.php';

$volunteerData = require __DIR__ . '/../data/volunteer_data.php';
$categories = $volunteerData['categories'];
// Đồng bộ trực tiếp từ CSDL và hệ thống hoạt động
$allActivities = ActivityManager::getAllActivities();

$selectedCat = field($_GET, 'cat');
$selectedShift = field($_GET, 'shift');
$selectedRegion = field($_GET, 'region');
$selectedDate = field($_GET, 'date');
$selectedSort = field($_GET, 'sort', 'nearest');
$q = field($_GET, 'q');

// Lọc theo điều kiện
$filtered = array_filter($allActivities, function($act) use ($selectedCat, $selectedShift, $selectedRegion, $selectedDate, $q) {
    if ($selectedCat !== '' && $act['category'] !== $selectedCat) {
        return false;
    }
    if ($selectedRegion !== '' && !LocationManager::matches($act['location'] ?? '', $selectedRegion)) {
        return false;
    }
    if ($selectedDate !== '' && !ActivityManager::matchesDate($act, $selectedDate)) {
        return false;
    }
    if ($q !== '' && stripos($act['title'], $q) === false && stripos($act['summary'], $q) === false && stripos($act['location'], $q) === false) {
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
    return true;
});
$filtered = array_values($filtered);

// Sắp xếp danh sách theo thời gian hoặc tiêu chí được chọn
usort($filtered, function($a, $b) use ($selectedSort) {
    if ($selectedSort === 'latest') {
        // Mới nhất: theo ID giảm dần (hoạt động mới tạo lên đầu)
        return ((int)($b['id'] ?? 0)) <=> ((int)($a['id'] ?? 0));
    } elseif ($selectedSort === 'farthest') {
        // Thời gian xa nhất
        $tsA = ActivityManager::getEarliestTimestamp($a);
        $tsB = ActivityManager::getEarliestTimestamp($b);
        return $tsB <=> $tsA;
    } elseif ($selectedSort === 'available') {
        // Còn nhiều suất trống nhất
        $remA = max(0, ($a['capacity'] ?? 0) - ($a['registered'] ?? 0));
        $remB = max(0, ($b['capacity'] ?? 0) - ($b['registered'] ?? 0));
        return $remB <=> $remA;
    } else { // 'nearest' (mặc định)
        // Thời gian sớm nhất / sắp diễn ra
        $tsA = ActivityManager::getEarliestTimestamp($a);
        $tsB = ActivityManager::getEarliestTimestamp($b);
        if ($tsA === $tsB) {
            return ((int)($a['id'] ?? 0)) <=> ((int)($b['id'] ?? 0));
        }
        return $tsA <=> $tsB;
    }
});

// Cấu hình phân trang
$perPage = (int)field($_GET, 'per_page');
if ($perPage <= 0 || !in_array($perPage, [6, 9, 12, 18, 24, 48], true)) {
    $perPage = 12; // Mặc định 12 hoạt động / trang (gọn gàng, dễ quét thông tin, không phải lật trang liên tục)
}

$totalItems = count($filtered);
$totalPages = max(1, (int)ceil($totalItems / $perPage));

$page = (int)field($_GET, 'page');
if ($page < 1) {
    $page = 1;
} elseif ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;
$pagedActivities = array_slice($filtered, $offset, $perPage);

$fromItem = $totalItems > 0 ? $offset + 1 : 0;
$toItem = min($offset + $perPage, $totalItems);

$buildPageUrl = function(int $targetPage, ?int $targetPerPage = null) use ($selectedCat, $selectedShift, $selectedRegion, $selectedDate, $selectedSort, $q, $perPage): string {
    $params = [];
    if ($selectedCat !== '') $params['cat'] = $selectedCat;
    if ($selectedShift !== '') $params['shift'] = $selectedShift;
    if ($selectedRegion !== '') $params['region'] = $selectedRegion;
    if ($selectedDate !== '') $params['date'] = $selectedDate;
    if ($selectedSort !== '' && $selectedSort !== 'nearest') $params['sort'] = $selectedSort;
    if ($q !== '') $params['q'] = $q;
    
    $effectivePerPage = $targetPerPage ?? $perPage;
    if ($effectivePerPage !== 12) $params['per_page'] = $effectivePerPage;
    if ($targetPage > 1) $params['page'] = $targetPage;
    
    $query = http_build_query($params);
    return '/hoat_dong.php' . ($query !== '' ? '?' . $query : '');
};

$buildCatUrl = function(string $catKey) use ($selectedShift, $selectedRegion, $selectedDate, $selectedSort, $q, $perPage): string {
    $params = [];
    if ($catKey !== '') $params['cat'] = $catKey;
    if ($selectedShift !== '') $params['shift'] = $selectedShift;
    if ($selectedRegion !== '') $params['region'] = $selectedRegion;
    if ($selectedDate !== '') $params['date'] = $selectedDate;
    if ($selectedSort !== '' && $selectedSort !== 'nearest') $params['sort'] = $selectedSort;
    if ($q !== '') $params['q'] = $q;
    if ($perPage !== 12) $params['per_page'] = $perPage;
    
    $query = http_build_query($params);
    return '/hoat_dong.php' . ($query !== '' ? '?' . $query : '');
};

require __DIR__ . '/../templates/header.php';
?>

<div class="page-heading-pro">
    <div class="heading-container">
        <span class="sub-badge">HOẠT ĐỘNG TÌNH NGUYỆN</span>
        <h1>Danh Sách Hoạt Động</h1>
        <p>Danh sách các hoạt động tình nguyện theo từng nhóm chủ đề.</p>
    </div>
</div>

<div class="page-content-wrapper">
    <?php if (!empty($_SESSION['flash_message'])): ?>
        <div class="alert-success-box" role="alert" style="background: #ecfdf5; border: 1.5px solid #10b981; border-radius: 10px; padding: 14px 18px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px; box-shadow: 0 2px 8px rgba(16,185,129,0.1);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="background: #059669; color: #ffffff; width: 22px; height: 22px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700;">✓</span>
                <span style="color: #065f46; font-size: 0.95rem; font-weight: 600;"><?= htmlspecialchars($_SESSION['flash_message']) ?></span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" style="background: none; border: none; color: #059669; font-size: 18px; cursor: pointer;">&times;</button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <?php if (Auth::isCoordinator()): ?>
        <div style="background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%); border: 1.5px solid #a7f3d0; border-radius: 12px; padding: 14px 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; box-shadow: 0 2px 8px rgba(16,185,129,0.08);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span style="font-size: 0.72rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; padding: 3px 8px; border-radius: 6px; background: #059669; color: #ffffff;">
                    BAN CHỈ HUY
                </span>
                <span style="font-size: 0.92rem; color: #065f46; font-weight: 600;">
                    Bạn đang đăng nhập với quyền Điều Phối Viên. Bạn có thể khởi tạo chiến dịch mới hoặc quản lý toàn diện danh mục hoạt động.
                </span>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <a href="/admin_import.php?tab=projects" class="btn-detail" style="padding: 7px 14px; font-size: 0.84rem; text-decoration: none; font-weight: 600;">
                    📁 Quản Lý Dự Án
                </a>
                <a href="/tao_su_kien.php?from=hoat_dong" class="btn-primary" style="padding: 7px 16px; font-size: 0.84rem; text-decoration: none; font-weight: 700; background: #059669; border-color: #059669; display: inline-flex; align-items: center; gap: 6px;">
                    <span>&#10010;</span> Tạo Hoạt Động Mới
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- KHUNG LỌC HOẠT ĐỘNG -->
    <div class="advanced-filter-card">
        <form method="get" class="filter-flex-form">
            <?php if ($perPage !== 12): ?>
                <input type="hidden" name="per_page" value="<?= $perPage ?>">
            <?php endif; ?>

            <div class="filter-item-input">
                <label for="q">Từ khóa tìm kiếm</label>
                <input type="text" id="q" name="q" placeholder="Nhập tên hoạt động, địa bàn..." value="<?= htmlspecialchars($q) ?>">
            </div>

            <div class="filter-item-select">
                <label for="cat">Nhóm chủ đề</label>
                <select id="cat" name="cat">
                    <option value="">— Tất cả 5 nhóm chủ đề —</option>
                    <?php foreach ($categories as $catKey => $cat): ?>
                        <option value="<?= $catKey ?>" <?= $selectedCat === $catKey ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['short_name'] ?? $cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-item-select">
                <label for="shift">Ca làm việc</label>
                <select id="shift" name="shift">
                    <option value="">— Mọi ca làm việc —</option>
                    <option value="Sáng" <?= $selectedShift === 'Sáng' ? 'selected' : '' ?>>Ca Sáng (06:30 – 11:30)</option>
                    <option value="Chiều" <?= $selectedShift === 'Chiều' ? 'selected' : '' ?>>Ca Chiều (13:30 – 17:30)</option>
                    <option value="Tối" <?= $selectedShift === 'Tối' ? 'selected' : '' ?>>Ca Tối (18:00 – 21:30)</option>
                    <option value="Đêm" <?= $selectedShift === 'Đêm' ? 'selected' : '' ?>>Ca Đêm (21:00 – 00:30)</option>
                    <option value="Cả ngày" <?= $selectedShift === 'Cả ngày' ? 'selected' : '' ?>>Cả ngày (Toàn thời gian)</option>
                </select>
            </div>

            <div class="filter-item-select">
                <label for="region">Khu vực / Địa bàn</label>
                <select id="region" name="region">
                    <option value="">— Mọi khu vực —</option>
                    <option value="hanoi" <?= $selectedRegion === 'hanoi' ? 'selected' : '' ?>>Hà Nội (Thủ đô)</option>
                    <option value="north" <?= $selectedRegion === 'north' ? 'selected' : '' ?>>Miền Bắc (Tây Bắc & Đông Bắc)</option>
                    <option value="central" <?= $selectedRegion === 'central' ? 'selected' : '' ?>>Miền Trung & Tây Nguyên</option>
                    <option value="south" <?= $selectedRegion === 'south' ? 'selected' : '' ?>>Miền Nam (TP.HCM & Miền Tây)</option>
                </select>
            </div>

            <div class="filter-item-input">
                <label for="date">Chọn ngày diễn ra</label>
                <div style="position: relative; display: flex; align-items: center;">
                    <input type="date" id="date" name="date" value="<?= htmlspecialchars($selectedDate) ?>" style="padding-right: <?= $selectedDate !== '' ? '30px' : '10px' ?>;">
                    <?php if ($selectedDate !== ''): ?>
                        <button type="button" onclick="document.getElementById('date').value=''; this.form.submit();" title="Bỏ lọc theo ngày" style="position: absolute; right: 8px; background: none; border: none; color: #94a3b8; font-size: 16px; cursor: pointer; padding: 0; line-height: 1;">&times;</button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="filter-item-select">
                <label for="sort">Sắp xếp theo</label>
                <select id="sort" name="sort" onchange="this.form.submit()">
                    <option value="nearest" <?= $selectedSort === 'nearest' ? 'selected' : '' ?>>⏱ Sắp diễn ra (Sớm nhất)</option>
                    <option value="latest" <?= $selectedSort === 'latest' ? 'selected' : '' ?>>🆕 Mới nhất (Mới tạo/đăng)</option>
                    <option value="farthest" <?= $selectedSort === 'farthest' ? 'selected' : '' ?>>📅 Diễn ra xa nhất</option>
                    <option value="available" <?= $selectedSort === 'available' ? 'selected' : '' ?>>🎯 Còn nhiều suất trống nhất</option>
                </select>
            </div>

            <div class="filter-item-btn">
                <button type="submit" class="btn-primary nut-loc-chinh">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px;">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    Tìm Kiếm
                </button>
                <a href="/hoat_dong.php" class="btn-cancel">Đặt lại</a>
            </div>
        </form>
    </div>

    <!-- TỔNG KẾT KẾT QUẢ VÀ TABS CHỦ ĐỀ -->
    <div class="result-summary-bar" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div class="result-summary-count">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <span>
                Tìm thấy <strong><?= $totalItems ?></strong> hoạt động tình nguyện <?= $totalPages > 1 ? '(Trang ' . $page . '/' . $totalPages . ')' : '' ?>
                <?php if ($selectedDate !== ''): ?>
                    <span style="background: #e0f2fe; color: #0284c7; padding: 2px 8px; border-radius: 6px; font-size: 0.8rem; font-weight: 700; margin-left: 6px;">
                        📅 Ngày: <?= date('d/m/Y', strtotime($selectedDate)) ?>
                    </span>
                <?php endif; ?>
            </span>
        </div>

        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <div class="category-quick-pills">
                <a href="<?= htmlspecialchars($buildCatUrl('')) ?>" class="cat-pill <?= $selectedCat === '' ? 'active' : '' ?>">Tất cả (<?= count($allActivities) ?>)</a>
                <?php foreach ($categories as $k => $c): ?>
                    <?php $cnt = count(array_filter($allActivities, fn($a) => $a['category'] === $k)); ?>
                    <a href="<?= htmlspecialchars($buildCatUrl($k)) ?>" class="cat-pill <?= $selectedCat === $k ? 'active' : '' ?>">
                        <?= htmlspecialchars($c['short_name']) ?> (<?= $cnt ?>)
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- NÚT CHUYỂN ĐỔI CHẾ ĐỘ XEM GRID / LIST -->
            <div class="view-mode-toggle" id="view_mode_toggle" role="group" aria-label="Chế độ hiển thị danh sách">
                <button type="button" class="btn-view-toggle active" id="btn_view_grid" onclick="setActViewMode('grid')" title="Hiển thị dạng thẻ lưới gọn gàng">
                    <span>⊞ Lưới Gọn</span>
                </button>
                <button type="button" class="btn-view-toggle" id="btn_view_list" onclick="setActViewMode('list')" title="Hiển thị dạng dòng tóm tắt dễ quét">
                    <span>☰ Dạng Dòng</span>
                </button>
            </div>
        </div>
    </div>

    <!-- DANH SÁCH THẺ HOẠT ĐỘNG (GỌN GÀNG, KHÔNG RỐI MẮT) -->
    <?php if (empty($pagedActivities)): ?>
        <div class="no-activity-found">
            <h3>Không tìm thấy hoạt động phù hợp</h3>
            <p>Vui lòng thử tìm kiếm lại với tiêu chí khác hoặc xem toàn bộ danh sách.</p>
            <a href="/hoat_dong.php" class="btn-primary">Xem toàn bộ hoạt động</a>
        </div>
    <?php else: ?>
        <div class="activities-grid" id="activities_container">
            <?php foreach ($pagedActivities as $act): ?>
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
                    $locInfo = LocationManager::parse($act['location'] ?? '');
                ?>
                <article class="activity-card-item compact" id="act_<?= (int)$act['id'] ?>" data-category="<?= htmlspecialchars($act['category']) ?>" data-region="<?= htmlspecialchars($locInfo['region_key']) ?>">
                    <div class="list-main-info">
                        <div class="act-card-header">
                            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap; min-width: 0;">
                                <span class="act-cat-badge" style="background-color: <?= $catInfo['bg_color'] ?>; color: <?= $catInfo['color'] ?>; border: 1px solid <?= $catInfo['color'] ?>40;">
                                    [<?= htmlspecialchars($catInfo['code'] ?? 'TNV') ?>] <?= htmlspecialchars($catInfo['short_name'] ?? $catInfo['name']) ?>
                                </span>
                                <span class="act-loc-badge" style="background-color: <?= $locInfo['region_bg'] ?>; color: <?= $locInfo['region_color'] ?>; border: 1px solid <?= $locInfo['region_color'] ?>35; font-size: 0.72rem; font-weight: 700; padding: 2px 7px; border-radius: 6px; white-space: nowrap;" title="Khu vực: <?= htmlspecialchars($locInfo['region_full_name']) ?> (<?= htmlspecialchars($locInfo['full_location']) ?>)">
                                    📍 <?= htmlspecialchars($locInfo['province_short']) ?>
                                </span>
                            </div>
                            <?php if ($isFull): ?>
                                <span class="badge-status-full">Đã đủ</span>
                            <?php else: ?>
                                <span class="badge-status-open">Còn <?= $remaining ?> suất</span>
                            <?php endif; ?>
                        </div>

                        <h3 class="act-title">
                            <a href="#" class="btn-view-activity-detail" data-activity='<?= $jsonData ?>' title="Bấm để xem đầy đủ chi tiết nhiệm vụ và quyền lợi">
                                <?= htmlspecialchars($act['title']) ?>
                            </a>
                        </h3>

                        <!-- THÔNG TIN TÓM TẮT ĐỊA BÀN & THỜI GIAN (KHÔNG BỊ TRÀN CHỮ) -->
                        <div class="act-meta-compact">
                            <div class="meta-row meta-row-loc" title="<?= htmlspecialchars($locInfo['full_location']) ?>">
                                <span style="color: #ef4444; flex-shrink: 0; font-size: 0.82rem;">📍</span>
                                <span class="meta-text" style="font-weight: 600; color: #1e293b;">
                                    <?= htmlspecialchars($locInfo['short_location']) ?>
                                </span>
                            </div>
                            <div class="meta-row meta-row-date" title="<?= htmlspecialchars($act['date_string']) ?>">
                                <span style="color: #0284c7; flex-shrink: 0; font-size: 0.82rem;">📅</span>
                                <span class="meta-text" style="color: #475569;">
                                    <?= htmlspecialchars($act['date_string']) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- TIẾN ĐỘ TUYỂN QUÂN SỐ -->
                    <div class="list-progress-col">
                        <div class="recruitment-progress-box">
                            <div class="progress-labels">
                                <span>Đã tuyển: <strong><?= (int)$act['registered'] ?>/<?= (int)$act['capacity'] ?></strong></span>
                                <span><strong><?= $pct ?>%</strong></span>
                            </div>
                            <div class="progress-bar-track">
                                <div class="progress-bar-fill <?= $isFull ? 'fill-full' : '' ?>" style="width: <?= $pct ?>%;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- CỤM NÚT HÀNH ĐỘNG GỌN GÀNG -->
                    <div class="act-card-actions">
                        <button type="button" class="btn-view-detail btn-view-activity-detail" data-activity='<?= $jsonData ?>'>
                            Chi Tiết
                        </button>

                        <?php if ($isFull): ?>
                            <button type="button" class="btn-act-join disabled" disabled title="Hoạt động này đã đủ số lượng TNV">
                                Đã Đủ
                            </button>
                        <?php else: ?>
                            <a href="/dang_ky_ca.php?event_id=<?= (int)$act['id'] ?>&from_page=<?= $page ?>" class="btn-act-join">
                                Đăng Ký Ca
                            </a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <!-- BỘ ĐIỀU KHIỂN PHÂN TRANG HOẠT ĐỘNG -->
        <?php if ($totalItems > 0): ?>
            <div class="app-pagination-wrapper">
                <div class="pagination-info">
                    Hiển thị từ <strong><?= $fromItem ?></strong> đến <strong><?= $toItem ?></strong> trong tổng số <strong><?= $totalItems ?></strong> hoạt động
                </div>

                <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <!-- CHỌN SỐ LƯỢNG MỖI TRANG -->
                    <div style="display: flex; align-items: center; gap: 6px; font-size: 0.86rem; color: #64748b;">
                        <span>Hiển thị:</span>
                        <select onchange="window.location.href=this.value" style="padding: 5px 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.85rem; background: #ffffff; cursor: pointer;">
                            <?php foreach ([6, 12, 18, 24, 48] as $opt): ?>
                                <option value="<?= htmlspecialchars($buildPageUrl(1, $opt)) ?>" <?= $perPage === $opt ? 'selected' : '' ?>>
                                    <?= $opt ?> / trang
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- CỤM NÚT CHUYỂN TRANG -->
                    <?php if ($totalPages > 1): ?>
                        <nav class="app-pagination" aria-label="Phân trang hoạt động">
                            <!-- NÚT TRANG TRƯỚC -->
                            <?php if ($page > 1): ?>
                                <a href="<?= htmlspecialchars($buildPageUrl($page - 1)) ?>" class="page-link" aria-label="Trang trước">
                                    &larr; Trước
                                </a>
                            <?php else: ?>
                                <span class="page-link disabled" aria-disabled="true">&larr; Trước</span>
                            <?php endif; ?>

                            <!-- CÁC SỐ TRANG THÔNG MINH -->
                            <?php 
                                $range = [];
                                if ($totalPages <= 7) {
                                    $range = range(1, $totalPages);
                                } else {
                                    if ($page <= 4) {
                                        $range = [1, 2, 3, 4, 5, '...', $totalPages];
                                    } elseif ($page >= $totalPages - 3) {
                                        $range = [1, '...', $totalPages - 4, $totalPages - 3, $totalPages - 2, $totalPages - 1, $totalPages];
                                    } else {
                                        $range = [1, '...', $page - 1, $page, $page + 1, '...', $totalPages];
                                    }
                                }
                            ?>

                            <?php foreach ($range as $p): ?>
                                <?php if ($p === '...'): ?>
                                    <span class="page-ellipsis">&hellip;</span>
                                <?php elseif ((int)$p === $page): ?>
                                    <span class="page-link active" aria-current="page"><?= $p ?></span>
                                <?php else: ?>
                                    <a href="<?= htmlspecialchars($buildPageUrl((int)$p)) ?>" class="page-link"><?= $p ?></a>
                                <?php endif; ?>
                            <?php endforeach; ?>

                            <!-- NÚT TRANG SAU -->
                            <?php if ($page < $totalPages): ?>
                                <a href="<?= htmlspecialchars($buildPageUrl($page + 1)) ?>" class="page-link" aria-label="Trang sau">
                                    Sau &rarr;
                                </a>
                            <?php else: ?>
                                <span class="page-link disabled" aria-disabled="true">Sau &rarr;</span>
                            <?php endif; ?>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function setActViewMode(mode) {
    const container = document.getElementById('activities_container');
    const btnGrid = document.getElementById('btn_view_grid');
    const btnList = document.getElementById('btn_view_list');
    if (!container) return;

    if (mode === 'list') {
        container.classList.add('view-list');
        if (btnList) btnList.classList.add('active');
        if (btnGrid) btnGrid.classList.remove('active');
        localStorage.setItem('d2l_act_view_mode', 'list');
    } else {
        container.classList.remove('view-list');
        if (btnGrid) btnGrid.classList.add('active');
        if (btnList) btnList.classList.remove('active');
        localStorage.setItem('d2l_act_view_mode', 'grid');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Khôi phục chế độ xem đã lưu từ LocalStorage
    const savedMode = localStorage.getItem('d2l_act_view_mode');
    if (savedMode === 'list') {
        setActViewMode('list');
    }

    // Tự động cuộn và làm nổi bật thẻ hoạt động nếu quay lại từ trang đăng ký (dựa trên #act_ID)
    if (window.location.hash && window.location.hash.startsWith('#act_')) {
        const targetId = window.location.hash.substring(1);
        const targetEl = document.getElementById(targetId);
        if (targetEl) {
            setTimeout(function() {
                targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                targetEl.classList.add('target-highlight');
            }, 250);
        }
    }
});
</script>

<?php require __DIR__ . '/../templates/footer.php'; ?>