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

require __DIR__ . '/../templates/header.php';
?>

<div class="page-heading-pro">
    <div class="heading-container">
        <span class="sub-badge">DANH MỤC CHIẾN DỊCH</span>
        <h1>Danh Sách Hoạt Động Tình Nguyện</h1>
        <p>Tổng hợp <?= count($allActivities) ?> hoạt động trọng điểm phân theo 5 nhóm chủ đề thực tế — Lựa chọn chương trình phù hợp để tham gia cống hiến.</p>
    </div>
</div>

<div class="page-content-wrapper">
    <!-- KHUNG LỌC HOẠT ĐỘNG -->
    <div class="advanced-filter-card">
        <form method="get" class="filter-flex-form">
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
            <?php foreach ($filtered as $act): ?>
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

                        <?php if ($isFull): ?>
                            <button type="button" class="btn-act-join disabled" disabled title="Hoạt động này đã đủ số lượng TNV">
                                Đã Đủ Quân Số
                            </button>
                        <?php else: ?>
                            <a href="/register.php?event_id=<?= (int)$act['id'] ?>" class="btn-act-join">
                                Đăng Ký Ca
                            </a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
