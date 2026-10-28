<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/../src/Input.php';
require __DIR__ . '/../src/EventFilter.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/ActivityManager.php';

$volunteerData = require __DIR__ . '/../data/volunteer_data.php';
$categories = $volunteerData['categories'];

// XỬ LÝ CÁC TÁC VỤ CRUD HOẠT ĐỘNG
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = field($_POST, 'action');

    // 1. THÊM MỚI HOẠT ĐỘNG
    if ($action === 'create_activity') {
        $shiftsRaw = field($_POST, 'shifts');
        $shiftsArr = array_filter(array_map('trim', explode("\n", $shiftsRaw)));
        if (empty($shiftsArr)) {
            $shiftsArr = ['Ca Sáng (07:30 – 11:30)'];
        }

        $skillsRaw = field($_POST, 'skills_needed');
        $skillsArr = array_filter(array_map('trim', explode(',', $skillsRaw)));
        if (empty($skillsArr)) {
            $skillsArr = ['Thể lực tốt', 'Làm việc nhóm'];
        }

        $catKey = field($_POST, 'category') ?: 'thuc_te_moi_truong';
        $catName = $categories[$catKey]['name'] ?? 'Hoạt Động Tình Nguyện';

        $payload = [
            'title' => field($_POST, 'title'),
            'category' => $catKey,
            'category_name' => $catName,
            'date_string' => field($_POST, 'date_string'),
            'time_range' => field($_POST, 'time_range'),
            'location' => field($_POST, 'location'),
            'meeting_point' => field($_POST, 'meeting_point'),
            'capacity' => (int)field($_POST, 'capacity'),
            'registered' => 0,
            'leader_name' => field($_POST, 'leader_name'),
            'leader_phone' => field($_POST, 'leader_phone'),
            'shifts' => $shiftsArr,
            'skills_needed' => $skillsArr,
            'summary' => field($_POST, 'summary'),
            'status' => 'recruiting'
        ];

        ActivityManager::create($payload);
        $_SESSION['flash_message'] = 'Đã thêm thành công chiến dịch tình nguyện mới vào hệ thống!';
        header('Location: /hoat_dong.php');
        exit;
    }

    // 2. CHỈNH SỬA HOẠT ĐỘNG
    if ($action === 'update_activity') {
        $id = (int)field($_POST, 'id');
        $shiftsRaw = field($_POST, 'shifts');
        $shiftsArr = array_filter(array_map('trim', explode("\n", $shiftsRaw)));
        $skillsRaw = field($_POST, 'skills_needed');
        $skillsArr = array_filter(array_map('trim', explode(',', $skillsRaw)));

        $catKey = field($_POST, 'category') ?: 'thuc_te_moi_truong';
        $catName = $categories[$catKey]['name'] ?? 'Hoạt Động Tình Nguyện';

        $payload = [
            'title' => field($_POST, 'title'),
            'category' => $catKey,
            'category_name' => $catName,
            'date_string' => field($_POST, 'date_string'),
            'time_range' => field($_POST, 'time_range'),
            'location' => field($_POST, 'location'),
            'meeting_point' => field($_POST, 'meeting_point'),
            'capacity' => (int)field($_POST, 'capacity'),
            'registered' => (int)field($_POST, 'registered'),
            'leader_name' => field($_POST, 'leader_name'),
            'leader_phone' => field($_POST, 'leader_phone'),
            'shifts' => $shiftsArr,
            'skills_needed' => $skillsArr,
            'summary' => field($_POST, 'summary'),
            'status' => field($_POST, 'status') ?: 'recruiting'
        ];

        ActivityManager::update($id, $payload);
        $_SESSION['flash_message'] = 'Đã cập nhật thông tin chiến dịch #' . $id . ' thành công!';
        header('Location: /hoat_dong.php');
        exit;
    }

    // 3. XÓA HOẠT ĐỘNG
    if ($action === 'delete_activity') {
        $id = (int)field($_POST, 'id');
        ActivityManager::delete($id);
        $_SESSION['flash_message'] = 'Đã xóa chiến dịch #' . $id . ' khỏi hệ thống!';
        header('Location: /hoat_dong.php');
        exit;
    }
}

// Đọc toàn bộ hoạt động từ kho lưu trữ ActivityManager
$allActivities = ActivityManager::getAll();

$selectedCat = field($_GET, 'cat');
$selectedShift = field($_GET, 'shift');
$q = field($_GET, 'q');
$page = max(1, (int)(field($_GET, 'page') ?: 1));
$perPage = max(3, min(24, (int)(field($_GET, 'per_page') ?: 6)));

// Lọc dữ liệu theo từ khóa, nhóm chủ đề và ca làm việc
$filtered = ActivityManager::filter($allActivities, $selectedCat, $selectedShift, $q);

// Phân trang
$pagination = ActivityManager::paginate($filtered, $page, $perPage);
$displayActivities = $pagination['items'];

require __DIR__ . '/../templates/header.php';
?>

<div class="page-heading-pro">
    <div class="heading-container">
        <span class="sub-badge">DANH MỤC CHIẾN DỊCH</span>
        <h1>Danh Sách Hoạt Động Tình Nguyện</h1>
        <div class="neon-divider-glow"></div>
        <p>Tổng hợp <?= count($allActivities) ?> hoạt động trọng điểm phân theo 5 nhóm chủ đề thực tế — Lựa chọn chương trình phù hợp để tham gia cống hiến.</p>
    </div>
</div>

<div class="page-content-wrapper">
    <!-- THANH TÁC VỤ QUẢN TRỊ VIÊN & ĐIỀU PHỐI (NẾU LÀ ĐIỀU PHỐI HOẶC TÙY CHỌN QUẢN LÝ) -->
    <div class="activity-management-bar">
        <div class="mgmt-bar-left">
            <span class="neon-tag-indicator">HỆ THỐNG DỮ LIỆU THỰC ĐỊA</span>
            <span class="mgmt-counter">Đang hiển thị: <strong><?= $pagination['start_index'] ?> - <?= $pagination['end_index'] ?></strong> / Tổng số <strong><?= $pagination['total'] ?></strong> hoạt động</span>
        </div>
        <div class="mgmt-bar-right">
            <button type="button" class="btn-neon-action" onclick="openCreateActivityModal()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Thêm Hoạt Động Mới
            </button>
            <a href="/quan_ly_tnv.php" class="btn-mgmt-link" title="Chuyển sang trang Quản lý Lực lượng Tình nguyện viên">
                Quản Lý TNV &rarr;
            </a>
        </div>
    </div>

    <!-- KHUNG LỌC HOẠT ĐỘNG -->
    <div class="advanced-filter-card">
        <form method="get" class="filter-flex-form">
            <div class="filter-item-input">
                <label for="q">Từ khóa tìm kiếm</label>
                <input type="text" id="q" name="q" placeholder="Nhập tên hoạt động, địa bàn, người phụ trách..." value="<?= htmlspecialchars($q) ?>">
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
            <span>Tìm thấy <strong><?= count($filtered) ?></strong> hoạt động &bull; Trang <strong><?= $pagination['page'] ?></strong>/<strong><?= $pagination['total_pages'] ?></strong></span>
        </div>
        <div class="category-quick-pills">
            <a href="/hoat_dong.php" class="cat-pill <?= $selectedCat === '' ? 'active' : '' ?>">Tất cả (<?= count($allActivities) ?>)</a>
            <?php foreach ($categories as $k => $c): ?>
                <?php $cnt = count(array_filter($allActivities, fn($a) => ($a['category'] ?? '') === $k)); ?>
                <a href="/hoat_dong.php?cat=<?= $k ?>" class="cat-pill <?= $selectedCat === $k ? 'active' : '' ?>">
                    <?= htmlspecialchars($c['short_name']) ?> (<?= $cnt ?>)
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- DANH SÁCH THẺ HOẠT ĐỘNG -->
    <?php if (empty($displayActivities)): ?>
        <div class="no-activity-found">
            <h3>Không tìm thấy hoạt động phù hợp</h3>
            <p>Vui lòng thử tìm kiếm lại với tiêu chí khác hoặc xem toàn bộ danh sách.</p>
            <a href="/hoat_dong.php" class="btn-primary">Xem toàn bộ hoạt động</a>
        </div>
    <?php else: ?>
        <div class="activities-grid">
            <?php foreach ($displayActivities as $act): ?>
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
                        
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <?php if ($isFull): ?>
                                <span class="badge-status-full">Đã đủ quân số</span>
                            <?php else: ?>
                                <span class="badge-status-open">Còn <?= $remaining ?> suất</span>
                            <?php endif; ?>

                            <!-- Nút Thao Tác Nhanh (Sửa / Xóa) -->
                            <div class="act-crud-actions">
                                <button type="button" class="btn-crud-icon" title="Chỉnh sửa hoạt động" onclick='openEditActivityModal(<?= $jsonData ?>)'>
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                </button>
                                <button type="button" class="btn-crud-icon btn-crud-del" title="Xóa hoạt động" onclick="confirmDeleteActivity(<?= (int)$act['id'] ?>, '<?= htmlspecialchars(addslashes($act['title'])) ?>')">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                </button>
                            </div>
                        </div>
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
                            <span class="info-text"><?= htmlspecialchars(implode(', ', $act['shifts'] ?? [])) ?></span>
                        </div>
                        <div class="act-info-item">
                            <span class="info-label">Chỉ huy:</span>
                            <span class="info-text"><?= htmlspecialchars($act['leader_name']) ?> (<?= htmlspecialchars($act['leader_phone']) ?>)</span>
                        </div>
                    </div>

                    <div class="skills-preview-row">
                        <span class="skills-label">Ưu tiên:</span>
                        <?php foreach (array_slice($act['skills_needed'] ?? [], 0, 3) as $sk): ?>
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

        <!-- KHUNG PHÂN TRANG HIỆN ĐẠI (PAGINATION CONTROLS) -->
        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="pagination-container">
                <div class="pagination-info">
                    Hiển thị <strong><?= $pagination['start_index'] ?> - <?= $pagination['end_index'] ?></strong> của <strong><?= $pagination['total'] ?></strong> hoạt động
                </div>

                <div class="pagination-nav">
                    <!-- Nút Trang Trước -->
                    <?php if ($pagination['has_prev']): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['prev_page']])) ?>" class="page-nav-btn" title="Trang trước">&larr; Trước</a>
                    <?php else: ?>
                        <span class="page-nav-btn disabled">&larr; Trước</span>
                    <?php endif; ?>

                    <!-- Các nút số trang -->
                    <?php for ($p = 1; $p <= $pagination['total_pages']; $p++): ?>
                        <?php if ($p === $pagination['page']): ?>
                            <span class="page-number-btn active"><?= $p ?></span>
                        <?php else: ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>" class="page-number-btn"><?= $p ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <!-- Nút Trang Kế Tiếp -->
                    <?php if ($pagination['has_next']): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['next_page']])) ?>" class="page-nav-btn" title="Trang sau">Sau &rarr;</a>
                    <?php else: ?>
                        <span class="page-nav-btn disabled">Sau &rarr;</span>
                    <?php endif; ?>
                </div>

                <div class="pagination-per-page">
                    <label for="per_page_select">Hiển thị:</label>
                    <select id="per_page_select" onchange="changePerPage(this.value)">
                        <option value="6" <?= $perPage === 6 ? 'selected' : '' ?>>6 / trang</option>
                        <option value="12" <?= $perPage === 12 ? 'selected' : '' ?>>12 / trang</option>
                        <option value="24" <?= $perPage === 24 ? 'selected' : '' ?>>24 / trang</option>
                    </select>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- ========================================================
     MODAL 1: THÊM MỚI HOẠT ĐỘNG TÌNH NGUYỆN
     ======================================================== -->
<div id="modal_create_activity" class="app-modal" role="dialog" aria-modal="true" style="display: none;">
    <div class="modal-backdrop" onclick="closeCreateActivityModal()"></div>
    <div class="modal-window" style="max-width: 680px;">
        <div class="modal-header">
            <div>
                <span class="modal-category-badge cat-thuc_te_moi_truong">THÊM CHIẾN DỊCH</span>
                <h3 class="modal-title">Tạo Hoạt Động Tình Nguyện Mới</h3>
            </div>
            <button type="button" class="modal-close-btn" onclick="closeCreateActivityModal()">&times;</button>
        </div>

        <form method="POST" action="/hoat_dong.php">
            <input type="hidden" name="action" value="create_activity">
            <div class="modal-body" style="display: flex; flex-direction: column; gap: 14px;">
                <div class="auth-input-group">
                    <label for="c_title">Tên chiến dịch / Hoạt động <span style="color:#dc2626;">*</span></label>
                    <input type="text" id="c_title" name="title" required placeholder="Ví dụ: Chiến dịch Đông Ấm Y Tý 2026...">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="auth-input-group">
                        <label for="c_cat">Nhóm chủ đề <span style="color:#dc2626;">*</span></label>
                        <select id="c_cat" name="category" required>
                            <?php foreach ($categories as $k => $c): ?>
                                <option value="<?= $k ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="auth-input-group">
                        <label for="c_capacity">Chỉ tiêu quân số (TNV) <span style="color:#dc2626;">*</span></label>
                        <input type="number" id="c_capacity" name="capacity" value="30" min="1" max="500" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="auth-input-group">
                        <label for="c_date">Thời gian diễn ra</label>
                        <input type="text" id="c_date" name="date_string" placeholder="Chủ Nhật hàng tuần...">
                    </div>
                    <div class="auth-input-group">
                        <label for="c_time">Khung giờ</label>
                        <input type="text" id="c_time" name="time_range" placeholder="07:30 – 11:30">
                    </div>
                </div>

                <div class="auth-input-group">
                    <label for="c_location">Địa bàn hoạt động <span style="color:#dc2626;">*</span></label>
                    <input type="text" id="c_location" name="location" required placeholder="Địa chỉ cụ thể nơi diễn ra hoạt động...">
                </div>

                <div class="auth-input-group">
                    <label for="c_meeting">Điểm tập kết đón xe</label>
                    <input type="text" id="c_meeting" name="meeting_point" placeholder="Cổng Công viên Thống Nhất...">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="auth-input-group">
                        <label for="c_leader">Chỉ huy phụ trách</label>
                        <input type="text" id="c_leader" name="leader_name" value="Trần Hoàng Nam">
                    </div>
                    <div class="auth-input-group">
                        <label for="c_phone">SĐT Chỉ huy</label>
                        <input type="text" id="c_phone" name="leader_phone" value="0912.456.789">
                    </div>
                </div>

                <div class="auth-input-group">
                    <label for="c_shifts">Các ca làm việc (mỗi ca 1 dòng)</label>
                    <textarea id="c_shifts" name="shifts" rows="2" placeholder="Ca Sáng (07:30 – 11:30)&#10;Ca Chiều (13:30 – 17:00)">Ca Sáng (07:30 – 11:30)</textarea>
                </div>

                <div class="auth-input-group">
                    <label for="c_skills">Kỹ năng ưu tiên (ngăn cách bằng dấu phẩy)</label>
                    <input type="text" id="c_skills" name="skills_needed" value="Thể lực tốt, Làm việc nhóm, Sơ cấp cứu">
                </div>

                <div class="auth-input-group">
                    <label for="c_summary">Mô tả tóm tắt</label>
                    <textarea id="c_summary" name="summary" rows="2" placeholder="Nội dung tóm tắt mục tiêu và ý nghĩa chiến dịch..."></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeCreateActivityModal()">Hủy</button>
                <button type="submit" class="btn-primary">Tạo Hoạt Động Mới</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================
     MODAL 2: CHỈNH SỬA HOẠT ĐỘNG TÌNH NGUYỆN
     ======================================================== -->
<div id="modal_edit_activity" class="app-modal" role="dialog" aria-modal="true" style="display: none;">
    <div class="modal-backdrop" onclick="closeEditActivityModal()"></div>
    <div class="modal-window" style="max-width: 680px;">
        <div class="modal-header">
            <div>
                <span class="modal-category-badge cat-dieu_phoi">CHỈNH SỬA CHIẾN DỊCH</span>
                <h3 class="modal-title" id="edit_modal_heading">Chỉnh Sửa Hoạt Động</h3>
            </div>
            <button type="button" class="modal-close-btn" onclick="closeEditActivityModal()">&times;</button>
        </div>

        <form method="POST" action="/hoat_dong.php">
            <input type="hidden" name="action" value="update_activity">
            <input type="hidden" name="id" id="edit_act_id">

            <div class="modal-body" style="display: flex; flex-direction: column; gap: 14px;">
                <div class="auth-input-group">
                    <label for="edit_title">Tên chiến dịch / Hoạt động <span style="color:#dc2626;">*</span></label>
                    <input type="text" id="edit_title" name="title" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="auth-input-group">
                        <label for="edit_cat">Nhóm chủ đề</label>
                        <select id="edit_cat" name="category" required>
                            <?php foreach ($categories as $k => $c): ?>
                                <option value="<?= $k ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="auth-input-group">
                        <label for="edit_capacity">Chỉ tiêu tiếp nhận</label>
                        <input type="number" id="edit_capacity" name="capacity" min="1" max="500" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="auth-input-group">
                        <label for="edit_registered">Số lượng đã đăng ký</label>
                        <input type="number" id="edit_registered" name="registered" min="0">
                    </div>
                    <div class="auth-input-group">
                        <label for="edit_status">Trạng thái tiếp nhận</label>
                        <select id="edit_status" name="status">
                            <option value="recruiting">Đang tuyển quân</option>
                            <option value="full">Đã đủ quân số</option>
                            <option value="completed">Đã hoàn thành</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="auth-input-group">
                        <label for="edit_date">Thời gian</label>
                        <input type="text" id="edit_date" name="date_string">
                    </div>
                    <div class="auth-input-group">
                        <label for="edit_time">Khung giờ</label>
                        <input type="text" id="edit_time" name="time_range">
                    </div>
                </div>

                <div class="auth-input-group">
                    <label for="edit_location">Địa bàn hoạt động</label>
                    <input type="text" id="edit_location" name="location" required>
                </div>

                <div class="auth-input-group">
                    <label for="edit_meeting">Điểm tập kết đón xe</label>
                    <input type="text" id="edit_meeting" name="meeting_point">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="auth-input-group">
                        <label for="edit_leader">Chỉ huy phụ trách</label>
                        <input type="text" id="edit_leader" name="leader_name">
                    </div>
                    <div class="auth-input-group">
                        <label for="edit_phone">SĐT Chỉ huy</label>
                        <input type="text" id="edit_phone" name="leader_phone">
                    </div>
                </div>

                <div class="auth-input-group">
                    <label for="edit_shifts">Các ca làm việc (mỗi ca 1 dòng)</label>
                    <textarea id="edit_shifts" name="shifts" rows="2"></textarea>
                </div>

                <div class="auth-input-group">
                    <label for="edit_skills">Kỹ năng ưu tiên (ngăn cách bằng dấu phẩy)</label>
                    <input type="text" id="edit_skills" name="skills_needed">
                </div>

                <div class="auth-input-group">
                    <label for="edit_summary">Mô tả tóm tắt</label>
                    <textarea id="edit_summary" name="summary" rows="2"></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeEditActivityModal()">Hủy</button>
                <button type="submit" class="btn-primary">Lưu Thay Đổi</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================
     MODAL 3: XÁC NHẬN XÓA HOẠT ĐỘNG
     ======================================================== -->
<form id="form_delete_activity" method="POST" action="/hoat_dong.php" style="display: none;">
    <input type="hidden" name="action" value="delete_activity">
    <input type="hidden" name="id" id="delete_act_id">
</form>

<script>
function changePerPage(val) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', val);
    url.searchParams.set('page', 1);
    window.location.href = url.toString();
}

function openCreateActivityModal() {
    document.getElementById('modal_create_activity').style.display = 'flex';
}
function closeCreateActivityModal() {
    document.getElementById('modal_create_activity').style.display = 'none';
}

function openEditActivityModal(data) {
    document.getElementById('edit_act_id').value = data.id || '';
    document.getElementById('edit_modal_heading').textContent = 'Chỉnh Sửa Chiến Dịch #' + data.id;
    document.getElementById('edit_title').value = data.title || '';
    document.getElementById('edit_cat').value = data.category || 'thuc_te_moi_truong';
    document.getElementById('edit_capacity').value = data.capacity || 20;
    document.getElementById('edit_registered').value = data.registered || 0;
    document.getElementById('edit_status').value = data.status || 'recruiting';
    document.getElementById('edit_date').value = data.date_string || '';
    document.getElementById('edit_time').value = data.time_range || '';
    document.getElementById('edit_location').value = data.location || '';
    document.getElementById('edit_meeting').value = data.meeting_point || '';
    document.getElementById('edit_leader').value = data.leader_name || '';
    document.getElementById('edit_phone').value = data.leader_phone || '';
    document.getElementById('edit_shifts').value = (data.shifts || []).join("\n");
    document.getElementById('edit_skills').value = (data.skills_needed || []).join(", ");
    document.getElementById('edit_summary').value = data.summary || '';

    document.getElementById('modal_edit_activity').style.display = 'flex';
}
function closeEditActivityModal() {
    document.getElementById('modal_edit_activity').style.display = 'none';
}

function confirmDeleteActivity(id, title) {
    if (confirm('Xác nhận xóa hoạt động:\n"' + title + '"\nThao tác này sẽ loại bỏ hoạt động khỏi danh mục chiến dịch.')) {
        document.getElementById('delete_act_id').value = id;
        document.getElementById('form_delete_activity').submit();
    }
}
</script>

<?php require __DIR__ . '/../templates/footer.php'; ?>
