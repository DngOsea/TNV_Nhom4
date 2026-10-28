<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/../src/Input.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/VolunteerManager.php';

$btlData = require __DIR__ . '/../data/btl_dataset.php';
$allSkills = $btlData['skills'];
$rawShifts = $btlData['shifts'];

// XỬ LÝ CÁC TÁC VỤ CRUD TÌNH NGUYỆN VIÊN (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = field($_POST, 'action');

    // 1. THÊM TÌNH NGUYỆN VIÊN MỚI
    if ($action === 'create_volunteer') {
        $skills = isset($_POST['skills']) && is_array($_POST['skills']) ? $_POST['skills'] : [];
        $shifts = isset($_POST['shifts']) && is_array($_POST['shifts']) ? $_POST['shifts'] : [];

        $payload = [
            'name' => field($_POST, 'name'),
            'phone' => field($_POST, 'phone'),
            'email' => field($_POST, 'email'),
            'unit' => field($_POST, 'unit'),
            'skills' => $skills,
            'available_shifts' => $shifts,
            'max_shifts' => (int)field($_POST, 'max_shifts') ?: 2,
            'status' => field($_POST, 'status') ?: 'active',
            'hours' => (int)field($_POST, 'hours') ?: 8,
        ];

        $newId = VolunteerManager::create($payload);
        $_SESSION['flash_message'] = "Đã thêm thành công tình nguyện viên mới: {$payload['name']} (Mã {$newId})!";
        header('Location: /quan_ly_tnv.php');
        exit;
    }

    // 2. CẬP NHẬT TÌNH NGUYỆN VIÊN
    if ($action === 'update_volunteer') {
        $id = field($_POST, 'id');
        $skills = isset($_POST['skills']) && is_array($_POST['skills']) ? $_POST['skills'] : [];
        $shifts = isset($_POST['shifts']) && is_array($_POST['shifts']) ? $_POST['shifts'] : [];

        $payload = [
            'name' => field($_POST, 'name'),
            'phone' => field($_POST, 'phone'),
            'email' => field($_POST, 'email'),
            'unit' => field($_POST, 'unit'),
            'skills' => $skills,
            'available_shifts' => $shifts,
            'max_shifts' => (int)field($_POST, 'max_shifts') ?: 2,
            'status' => field($_POST, 'status') ?: 'active',
            'hours' => (int)field($_POST, 'hours') ?: 8,
        ];

        VolunteerManager::update($id, $payload);
        $_SESSION['flash_message'] = "Đã cập nhật thông tin tình nguyện viên {$id} thành công!";
        header('Location: /quan_ly_tnv.php');
        exit;
    }

    // 3. XÓA TÌNH NGUYỆN VIÊN
    if ($action === 'delete_volunteer') {
        $id = field($_POST, 'id');
        VolunteerManager::delete($id);
        $_SESSION['flash_message'] = "Đã xóa tình nguyện viên {$id} khỏi danh sách nhân sự!";
        header('Location: /quan_ly_tnv.php');
        exit;
    }
}

// Lấy toàn bộ danh sách TNV
$allVolunteers = VolunteerManager::getAll();

$q = field($_GET, 'q');
$selectedSkill = field($_GET, 'skill');
$selectedStatus = field($_GET, 'status');
$page = max(1, (int)(field($_GET, 'page') ?: 1));
$perPage = max(5, min(100, (int)(field($_GET, 'per_page') ?: 10)));

// Tìm kiếm & lọc
$filtered = VolunteerManager::search($allVolunteers, $q, $selectedSkill, $selectedStatus);

// Phân trang
$pagination = VolunteerManager::paginate($filtered, $page, $perPage);
$displayVolunteers = $pagination['items'];

// Thống kê nhanh
$totalCount = count($allVolunteers);
$activeCount = count(array_filter($allVolunteers, fn($v) => ($v['status'] ?? 'active') === 'active'));
$busyCount = count(array_filter($allVolunteers, fn($v) => ($v['status'] ?? '') === 'busy'));
$absentCount = count(array_filter($allVolunteers, fn($v) => ($v['status'] ?? '') === 'absent'));

require __DIR__ . '/../templates/header.php';
?>

<div class="page-heading-pro">
    <div class="heading-container">
        <span class="sub-badge">QUẢN TRỊ NHÂN SỰ TÌNH NGUYỆN</span>
        <h1>Quản Lý Lực Lượng Tình Nguyện Viên</h1>
        <div class="neon-divider-glow"></div>
        <p>Hệ thống hồ sơ, phân loại kỹ năng chuyên môn và giám sát điều động lực lượng tình nguyện viên nòng cốt toàn hệ thống.</p>
    </div>
</div>

<div class="page-content-wrapper">
    <!-- KHUNG THỐNG KÊ QUÂN SỐ TỔNG HỢP VỚI ACCENT NEON -->
    <div class="shift-stats-overview" style="margin-bottom: 24px;">
        <div class="shift-stat-box">
            <strong style="color: #0f172a;"><?= $totalCount ?> TNV</strong>
            <span>Tổng nhân sự trong cơ sở dữ liệu</span>
        </div>
        <div class="shift-stat-box">
            <strong class="text-success"><?= $activeCount ?> Sẵn Sàng</strong>
            <span>Đang hoạt động & trực tuyến</span>
        </div>
        <div class="shift-stat-box">
            <strong style="color: #2563eb;"><?= $busyCount ?> Đang Trực</strong>
            <span>Đã nhận lịch làm nhiệm vụ</span>
        </div>
        <div class="shift-stat-box">
            <strong class="<?= $absentCount > 0 ? 'text-danger' : '' ?>"><?= $absentCount ?> Đang Vắng</strong>
            <span>Tạm nghỉ hoặc xin vắng</span>
        </div>
    </div>

    <!-- THANH TÁC VỤ & BỘ LỌC TÌM KIẾM -->
    <div class="coordinator-command-bar" style="margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span class="neon-tag-indicator">QUÂN SỐ THỰC TẾ</span>
            <span style="font-size: 0.88rem; color: #475569;">
                Hiển thị: <strong><?= $pagination['start_index'] ?> - <?= $pagination['end_index'] ?></strong> / Tổng số <strong><?= $pagination['total'] ?></strong> hồ sơ
            </span>
        </div>

        <div style="display: flex; gap: 10px; align-items: center;">
            <button type="button" class="btn-neon-action" onclick="openCreateVolunteerModal()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Thêm Tình Nguyện Viên
            </button>
            <a href="/phan_cong.php" class="btn-cmd btn-cmd-outline" title="Chuyển sang Bảng Phân Công Ca Trực">
                Bảng Điều Phối &rarr;
            </a>
        </div>
    </div>

    <!-- KHUNG LỌC TÌM KIẾM TNV -->
    <div class="advanced-filter-card" style="margin-bottom: 20px;">
        <form method="get" class="filter-flex-form">
            <div class="filter-item-input">
                <label for="q">Tìm kiếm TNV</label>
                <input type="text" id="q" name="q" placeholder="Nhập tên, mã TNV, SĐT, email, trường/viện..." value="<?= htmlspecialchars($q) ?>">
            </div>

            <div class="filter-item-select">
                <label for="skill">Kỹ năng chuyên môn</label>
                <select id="skill" name="skill">
                    <option value="">— Mọi kỹ năng —</option>
                    <?php foreach ($allSkills as $skId => $sk): ?>
                        <option value="<?= $skId ?>" <?= $selectedSkill === $skId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sk['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-item-select">
                <label for="status">Trạng thái làm việc</label>
                <select id="status" name="status">
                    <option value="">— Mọi trạng thái —</option>
                    <option value="active" <?= $selectedStatus === 'active' ? 'selected' : '' ?>>Sẵn sàng nhận ca (Active)</option>
                    <option value="busy" <?= $selectedStatus === 'busy' ? 'selected' : '' ?>>Đang thực hiện ca (Busy)</option>
                    <option value="absent" <?= $selectedStatus === 'absent' ? 'selected' : '' ?>>Báo vắng (Absent)</option>
                </select>
            </div>

            <div class="filter-item-btn">
                <button type="submit" class="btn-primary nut-loc-chinh">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    Lọc Hồ Sơ
                </button>
                <a href="/quan_ly_tnv.php" class="btn-cancel">Đặt lại</a>
            </div>
        </form>
    </div>

    <!-- BẢNG DANH SÁCH TÌNH NGUYỆN VIÊN (TABLE VIEW) -->
    <div class="duty-table-container">
        <div class="duty-table-responsive">
            <table class="duty-table-modern">
                <thead>
                    <tr>
                        <th style="width: 12%;">Mã & Ảnh</th>
                        <th style="width: 20%;">Họ & Tên / Trường</th>
                        <th style="width: 18%;">Liên Hệ</th>
                        <th style="width: 22%;">Kỹ Năng Đóng Góp</th>
                        <th style="width: 14%;">Trạng Thái</th>
                        <th style="width: 14%; text-align: center;">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($displayVolunteers)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: #64748b;">
                                Không tìm thấy tình nguyện viên nào phù hợp với điều kiện tìm kiếm.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($displayVolunteers as $v): 
                            $vId = $v['id'] ?? 'TNV-000';
                            $vStatus = $v['status'] ?? 'active';
                            $jsonData = htmlspecialchars(json_encode($v), ENT_QUOTES, 'UTF-8');
                        ?>
                            <tr class="dispatch-data-row">
                                <td>
                                    <span class="t-shift-badge" style="background:#f1f5f9; color:#0f172a; border-color:#cbd5e1;"><?= htmlspecialchars($vId) ?></span>
                                    <div style="font-size: 0.74rem; color: #64748b; margin-top: 4px;">
                                        <?= (int)($v['hours'] ?? 8) ?> giờ tích lũy
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 9px;">
                                        <span class="t-v-avatar" style="background: #059669; font-weight:700;">
                                            <?= mb_substr($v['name'] ?? 'T', 0, 1) ?>
                                        </span>
                                        <div>
                                            <strong style="color: #0f172a; font-size: 0.92rem;"><?= htmlspecialchars($v['name'] ?? '') ?></strong>
                                            <div style="font-size: 0.75rem; color: #475569; margin-top: 2px;">
                                                <?= htmlspecialchars($v['unit'] ?? 'Thanh niên tình nguyện') ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size: 0.84rem; font-weight: 600; color: #334155;">
                                        <?= htmlspecialchars($v['phone'] ?? 'Chưa cập nhật') ?>
                                    </div>
                                    <div style="font-size: 0.74rem; color: #64748b; margin-top: 2px;">
                                        <?= htmlspecialchars($v['email'] ?? '') ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                        <?php foreach ($v['skills'] ?? [] as $sk): ?>
                                            <span class="req-skill-pill" style="font-size: 0.72rem; padding: 2px 7px;">
                                                <?= htmlspecialchars($allSkills[$sk]['name'] ?? $sk) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                    <div style="font-size: 0.72rem; color: #64748b; margin-top: 4px;">
                                        Ca khả dụng: <strong><?= count($v['available_shifts'] ?? []) ?></strong> ca &bull; Tối đa: <?= $v['max_shifts'] ?? 2 ?> ca
                                    </div>
                                </td>
                                <td>
                                    <?php if ($vStatus === 'active'): ?>
                                        <span style="display: inline-flex; align-items: center; gap: 4px; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; font-size: 0.75rem; font-weight: 700; padding: 3px 8px; border-radius: 6px;">
                                            <span style="width: 6px; height: 6px; border-radius: 50%; background: #10b981;"></span>
                                            Sẵn Sàng
                                        </span>
                                    <?php elseif ($vStatus === 'busy'): ?>
                                        <span style="display: inline-flex; align-items: center; gap: 4px; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 0.75rem; font-weight: 700; padding: 3px 8px; border-radius: 6px;">
                                            <span style="width: 6px; height: 6px; border-radius: 50%; background: #3b82f6;"></span>
                                            Đang Trực
                                        </span>
                                    <?php else: ?>
                                        <span style="display: inline-flex; align-items: center; gap: 4px; background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; font-size: 0.75rem; font-weight: 700; padding: 3px 8px; border-radius: 6px;">
                                            <span style="width: 6px; height: 6px; border-radius: 50%; background: #ef4444;"></span>
                                            Báo Vắng
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <div style="display: inline-flex; gap: 6px;">
                                        <button type="button" class="btn-crud-icon" title="Chỉnh sửa thông tin" onclick='openEditVolunteerModal(<?= $jsonData ?>)'>
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                        </button>
                                        <button type="button" class="btn-crud-icon btn-crud-del" title="Xóa tình nguyện viên" onclick="confirmDeleteVolunteer('<?= htmlspecialchars($vId) ?>', '<?= htmlspecialchars(addslashes($v['name'])) ?>')">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- KHUNG PHÂN TRANG HIỆN ĐẠI CHO TNV -->
        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="pagination-container">
                <div class="pagination-info">
                    Hiển thị <strong><?= $pagination['start_index'] ?> - <?= $pagination['end_index'] ?></strong> của <strong><?= $pagination['total'] ?></strong> tình nguyện viên
                </div>

                <div class="pagination-nav">
                    <?php if ($pagination['has_prev']): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['prev_page']])) ?>" class="page-nav-btn">&larr; Trước</a>
                    <?php else: ?>
                        <span class="page-nav-btn disabled">&larr; Trước</span>
                    <?php endif; ?>

                    <?php for ($p = 1; $p <= $pagination['total_pages']; $p++): ?>
                        <?php if ($p === $pagination['page']): ?>
                            <span class="page-number-btn active"><?= $p ?></span>
                        <?php else: ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>" class="page-number-btn"><?= $p ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($pagination['has_next']): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['next_page']])) ?>" class="page-nav-btn">Sau &rarr;</a>
                    <?php else: ?>
                        <span class="page-nav-btn disabled">Sau &rarr;</span>
                    <?php endif; ?>
                </div>

                <div class="pagination-per-page">
                    <label for="per_page_select">Hiển thị:</label>
                    <select id="per_page_select" onchange="changePerPage(this.value)">
                        <option value="10" <?= $perPage === 10 ? 'selected' : '' ?>>10 / trang</option>
                        <option value="20" <?= $perPage === 20 ? 'selected' : '' ?>>20 / trang</option>
                        <option value="50" <?= $perPage === 50 ? 'selected' : '' ?>>50 / trang</option>
                    </select>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ========================================================
     MODAL THÊM MỚI TÌNH NGUYỆN VIÊN
     ======================================================== -->
<div id="modal_create_volunteer" class="app-modal" role="dialog" aria-modal="true" style="display: none;">
    <div class="modal-backdrop" onclick="closeCreateVolunteerModal()"></div>
    <div class="modal-window" style="max-width: 640px;">
        <div class="modal-header">
            <div>
                <span class="modal-category-badge cat-thuc_te_moi_truong">THÊM HỒ SƠ</span>
                <h3 class="modal-title">Thêm Tình Nguyện Viên Mới</h3>
            </div>
            <button type="button" class="modal-close-btn" onclick="closeCreateVolunteerModal()">&times;</button>
        </div>

        <form method="POST" action="/quan_ly_tnv.php">
            <input type="hidden" name="action" value="create_volunteer">
            <div class="modal-body" style="display: flex; flex-direction: column; gap: 14px;">
                <div class="auth-input-group">
                    <label for="v_name">Họ và tên tình nguyện viên <span style="color:#dc2626;">*</span></label>
                    <input type="text" id="v_name" name="name" required placeholder="Ví dụ: Nguyễn Văn Hùng">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="auth-input-group">
                        <label for="v_phone">Số điện thoại <span style="color:#dc2626;">*</span></label>
                        <input type="text" id="v_phone" name="phone" required placeholder="0988.xxx.xxx">
                    </div>
                    <div class="auth-input-group">
                        <label for="v_email">Địa chỉ Email</label>
                        <input type="email" id="v_email" name="email" placeholder="hung.nguyen@example.com">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px;">
                    <div class="auth-input-group">
                        <label for="v_unit">Trường Đại học / Đơn vị công tác</label>
                        <input type="text" id="v_unit" name="unit" placeholder="ĐH Bách Khoa Hà Nội...">
                    </div>
                    <div class="auth-input-group">
                        <label for="v_max_shifts">Số ca tối đa</label>
                        <input type="number" id="v_max_shifts" name="max_shifts" value="2" min="1" max="5">
                    </div>
                </div>

                <div class="auth-input-group">
                    <label>Kỹ năng chuyên môn đóng góp:</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 6px;">
                        <?php foreach ($allSkills as $skId => $sk): ?>
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 0.84rem; cursor: pointer;">
                                <input type="checkbox" name="skills[]" value="<?= $skId ?>">
                                <span><?= htmlspecialchars($sk['name']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="auth-input-group">
                    <label>Ca trực có thể tham gia:</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px; margin-top: 6px; max-height: 120px; overflow-y: auto; background: #f8fafc; padding: 8px; border-radius: 6px; border: 1px solid #e2e8f0;">
                        <?php foreach ($rawShifts as $s): ?>
                            <label style="display: flex; align-items: center; gap: 5px; font-size: 0.8rem; cursor: pointer;">
                                <input type="checkbox" name="shifts[]" value="<?= $s['id'] ?>">
                                <span><?= $s['id'] ?> (<?= explode(' (', $s['shift_name'])[0] ?>)</span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="auth-input-group">
                        <label for="v_status">Trạng thái làm việc</label>
                        <select id="v_status" name="status">
                            <option value="active">Sẵn sàng nhận ca (Active)</option>
                            <option value="busy">Đang trực nhiệm vụ (Busy)</option>
                            <option value="absent">Báo vắng / Nghỉ phép (Absent)</option>
                        </select>
                    </div>
                    <div class="auth-input-group">
                        <label for="v_hours">Giờ tình nguyện ban đầu</label>
                        <input type="number" id="v_hours" name="hours" value="8" min="0">
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeCreateVolunteerModal()">Hủy</button>
                <button type="submit" class="btn-primary">Thêm Tình Nguyện Viên</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================
     MODAL CHỈNH SỬA TÌNH NGUYỆN VIÊN
     ======================================================== -->
<div id="modal_edit_volunteer" class="app-modal" role="dialog" aria-modal="true" style="display: none;">
    <div class="modal-backdrop" onclick="closeEditVolunteerModal()"></div>
    <div class="modal-window" style="max-width: 640px;">
        <div class="modal-header">
            <div>
                <span class="modal-category-badge cat-dieu_phoi">CẬP NHẬT HỒ SƠ</span>
                <h3 class="modal-title" id="edit_v_heading">Chỉnh Sửa Tình Nguyện Viên</h3>
            </div>
            <button type="button" class="modal-close-btn" onclick="closeEditVolunteerModal()">&times;</button>
        </div>

        <form method="POST" action="/quan_ly_tnv.php">
            <input type="hidden" name="action" value="update_volunteer">
            <input type="hidden" name="id" id="edit_v_id">

            <div class="modal-body" style="display: flex; flex-direction: column; gap: 14px;">
                <div class="auth-input-group">
                    <label for="edit_v_name">Họ và tên <span style="color:#dc2626;">*</span></label>
                    <input type="text" id="edit_v_name" name="name" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="auth-input-group">
                        <label for="edit_v_phone">Số điện thoại</label>
                        <input type="text" id="edit_v_phone" name="phone" required>
                    </div>
                    <div class="auth-input-group">
                        <label for="edit_v_email">Địa chỉ Email</label>
                        <input type="email" id="edit_v_email" name="email">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px;">
                    <div class="auth-input-group">
                        <label for="edit_v_unit">Đơn vị / Trường</label>
                        <input type="text" id="edit_v_unit" name="unit">
                    </div>
                    <div class="auth-input-group">
                        <label for="edit_v_max_shifts">Số ca tối đa</label>
                        <input type="number" id="edit_v_max_shifts" name="max_shifts" min="1" max="5">
                    </div>
                </div>

                <div class="auth-input-group">
                    <label>Kỹ năng chuyên môn:</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 6px;">
                        <?php foreach ($allSkills as $skId => $sk): ?>
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 0.84rem; cursor: pointer;">
                                <input type="checkbox" name="skills[]" value="<?= $skId ?>" class="edit-chk-skill" id="edit_sk_<?= $skId ?>">
                                <span><?= htmlspecialchars($sk['name']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="auth-input-group">
                        <label for="edit_v_status">Trạng thái làm việc</label>
                        <select id="edit_v_status" name="status">
                            <option value="active">Sẵn sàng nhận ca (Active)</option>
                            <option value="busy">Đang trực nhiệm vụ (Busy)</option>
                            <option value="absent">Báo vắng / Nghỉ phép (Absent)</option>
                        </select>
                    </div>
                    <div class="auth-input-group">
                        <label for="edit_v_hours">Giờ tình nguyện</label>
                        <input type="number" id="edit_v_hours" name="hours" min="0">
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeEditVolunteerModal()">Hủy</button>
                <button type="submit" class="btn-primary">Lưu Thay Đổi</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================
     FORM XÓA TÌNH NGUYỆN VIÊN (CONFIRM ACTION)
     ======================================================== -->
<form id="form_delete_volunteer" method="POST" action="/quan_ly_tnv.php" style="display: none;">
    <input type="hidden" name="action" value="delete_volunteer">
    <input type="hidden" name="id" id="delete_v_id">
</form>

<script>
function changePerPage(val) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', val);
    url.searchParams.set('page', 1);
    window.location.href = url.toString();
}

function openCreateVolunteerModal() {
    document.getElementById('modal_create_volunteer').style.display = 'flex';
}
function closeCreateVolunteerModal() {
    document.getElementById('modal_create_volunteer').style.display = 'none';
}

function openEditVolunteerModal(data) {
    document.getElementById('edit_v_id').value = data.id || '';
    document.getElementById('edit_v_heading').textContent = 'Chỉnh Sửa Hồ Sơ ' + (data.id || '');
    document.getElementById('edit_v_name').value = data.name || '';
    document.getElementById('edit_v_phone').value = data.phone || '';
    document.getElementById('edit_v_email').value = data.email || '';
    document.getElementById('edit_v_unit').value = data.unit || '';
    document.getElementById('edit_v_max_shifts').value = data.max_shifts || 2;
    document.getElementById('edit_v_status').value = data.status || 'active';
    document.getElementById('edit_v_hours').value = data.hours || 8;

    // Reset checkboxes
    document.querySelectorAll('.edit-chk-skill').forEach(chk => chk.checked = false);
    (data.skills || []).forEach(sk => {
        const el = document.getElementById('edit_sk_' + sk);
        if (el) el.checked = true;
    });

    document.getElementById('modal_edit_volunteer').style.display = 'flex';
}
function closeEditVolunteerModal() {
    document.getElementById('modal_edit_volunteer').style.display = 'none';
}

function confirmDeleteVolunteer(id, name) {
    if (confirm('Xác nhận xóa tình nguyện viên:\n' + name + ' (' + id + ')\nThao tác này sẽ loại bỏ tình nguyện viên khỏi cơ sở dữ liệu.')) {
        document.getElementById('delete_v_id').value = id;
        document.getElementById('form_delete_volunteer').submit();
    }
}
</script>

<?php require __DIR__ . '/../templates/footer.php'; ?>
