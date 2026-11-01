<?php
require __DIR__ . '/../src/Input.php';
require __DIR__ . '/../src/EventFilter.php';

$volunteerData = require __DIR__ . '/../data/volunteer_data.php';
$categories = $volunteerData['categories'];
$allActivities = $volunteerData['activities'];

$selectedCat = field($_GET, 'cat');
$selectedShift = field($_GET, 'shift');
$q = field($_GET, 'q');

// Lọc theo điều kiện
$filtered = array_filter($allActivities, function($act) use ($selectedCat, $selectedShift, $q) {
    if ($selectedCat !== '' && $act['category'] !== $selectedCat) {
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

// Cấu hình phân trang
$perPage = (int)field($_GET, 'per_page');
if ($perPage <= 0 || !in_array($perPage, [6, 9, 12, 18, 24], true)) {
    $perPage = 6; // Mặc định 6 hoạt động / trang (2 hàng x 3 cột)
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

$buildPageUrl = function(int $targetPage, ?int $targetPerPage = null) use ($selectedCat, $selectedShift, $q, $perPage): string {
    $params = [];
    if ($selectedCat !== '') $params['cat'] = $selectedCat;
    if ($selectedShift !== '') $params['shift'] = $selectedShift;
    if ($q !== '') $params['q'] = $q;
    
    $effectivePerPage = $targetPerPage ?? $perPage;
    if ($effectivePerPage !== 6) $params['per_page'] = $effectivePerPage;
    if ($targetPage > 1) $params['page'] = $targetPage;
    
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
    <!-- KHUNG LỌC HOẠT ĐỘNG -->
    <div class="advanced-filter-card">
        <form method="get" class="filter-flex-form">
            <?php if ($perPage !== 6): ?>
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
    <div class="result-summary-bar">
        <div class="result-summary-count">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <span>Tìm thấy <strong><?= $totalItems ?></strong> hoạt động tình nguyện <?= $totalPages > 1 ? '(Trang ' . $page . '/' . $totalPages . ')' : '' ?></span>
        </div>
        <div class="category-quick-pills">
            <a href="/hoat_dong.php<?= $perPage !== 6 ? '?per_page=' . $perPage : '' ?>" class="cat-pill <?= $selectedCat === '' ? 'active' : '' ?>">Tất cả (<?= count($allActivities) ?>)</a>
            <?php foreach ($categories as $k => $c): ?>
                <?php $cnt = count(array_filter($allActivities, fn($a) => $a['category'] === $k)); ?>
                <a href="/hoat_dong.php?cat=<?= $k ?><?= $perPage !== 6 ? '&per_page=' . $perPage : '' ?>" class="cat-pill <?= $selectedCat === $k ? 'active' : '' ?>">
                    <?= htmlspecialchars($c['short_name']) ?> (<?= $cnt ?>)
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- DANH SÁCH THẺ HOẠT ĐỘNG -->
    <?php if (empty($pagedActivities)): ?>
        <div class="no-activity-found">
            <h3>Không tìm thấy hoạt động phù hợp</h3>
            <p>Vui lòng thử tìm kiếm lại với tiêu chí khác hoặc xem toàn bộ danh sách.</p>
            <a href="/hoat_dong.php" class="btn-primary">Xem toàn bộ hoạt động</a>
        </div>
    <?php else: ?>
        <div class="activities-grid">
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
                        <div class="act-info-item" title="Thời gian diễn ra">
                            <span class="info-label">&#128197; Lịch:</span>
                            <span class="info-text"><?= htmlspecialchars($act['date_string']) ?></span>
                        </div>
                        <div class="act-info-item" title="Địa bàn thực hiện">
                            <span class="info-label">&#128205; Địa điểm:</span>
                            <span class="info-text"><?= htmlspecialchars($act['location']) ?></span>
                        </div>
                        <div class="act-info-item" title="Các ca trực">
                            <span class="info-label">&#9200; Ca:</span>
                            <span class="info-text"><?= htmlspecialchars(implode(', ', $act['shifts'])) ?></span>
                        </div>
                        <div class="act-info-item" title="Chỉ huy phụ trách">
                            <span class="info-label">&#128100; Chỉ huy:</span>
                            <span class="info-text"><?= htmlspecialchars($act['leader_name']) ?></span>
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

                        <?php if ($isFull): ?>
                            <button type="button" class="btn-act-join disabled" disabled title="Hoạt động này đã đủ số lượng TNV">
                                Đã Đủ Quân Số
                            </button>
                        <?php else: ?>
                            <a href="/dang_ky_ca.php?event_id=<?= (int)$act['id'] ?>" class="btn-act-join">
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
                            <?php foreach ([6, 9, 12, 18, 24] as $opt): ?>
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

<?php require __DIR__ . '/../templates/footer.php'; ?>