<?php
declare(strict_types=1);

/**
 * TRANG CHỈNH SỬA THÔNG TIN DỰ ÁN & HOẠT ĐỘNG TÌNH NGUYỆN (D2L PROJECT EDITOR)
 * Dành riêng cho Người Điều Phối (Ban Chỉ Huy)
 */

require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/SkillManager.php';
require_once __DIR__ . '/../src/ActivityManager.php';

Auth::requireCoordinator();

$db = Database::getInstance();
$currentUser = Auth::getUser();

$projectId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($projectId <= 0) {
    $_SESSION['flash_msg'] = [
        'type' => 'danger',
        'title' => 'Không Tìm Thấy Dự Án',
        'content' => 'Mã dự án không hợp lệ. Vui lòng chọn dự án từ danh mục hoặc danh sách quản trị.'
    ];
    header('Location: /admin_import.php?tab=projects');
    exit;
}

$project = $db->getProjectById($projectId);
if (!$project) {
    $_SESSION['flash_msg'] = [
        'type' => 'danger',
        'title' => 'Dự Án Không Tồn Tại',
        'content' => 'Không tìm thấy dự án mang mã #' . $projectId . ' trong hệ thống.'
    ];
    header('Location: /admin_import.php?tab=projects');
    exit;
}

$errors = [];
$successMessage = null;

// Xử lý lưu cập nhật
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_project') {
    $title = trim((string)($_POST['title'] ?? ''));
    $category = trim((string)($_POST['category'] ?? 'thuc_te_moi_truong'));
    $dateString = trim((string)($_POST['date_string'] ?? ''));
    $timeRange = trim((string)($_POST['time_range'] ?? ''));
    $location = trim((string)($_POST['location'] ?? ''));
    $meetingPoint = trim((string)($_POST['meeting_point'] ?? ''));
    $capacity = (int)($_POST['capacity'] ?? 20);
    $leaderName = trim((string)($_POST['leader_name'] ?? ''));
    $leaderPhone = trim((string)($_POST['leader_phone'] ?? ''));
    $status = trim((string)($_POST['status'] ?? 'recruiting'));
    $isUrgent = !empty($_POST['is_urgent']) ? 1 : 0;
    $summary = trim((string)($_POST['summary'] ?? ''));

    // Kỹ năng yêu cầu
    $skillsNeeded = $_POST['skills_needed'] ?? [];
    if (!is_array($skillsNeeded)) {
        $skillsNeeded = array_filter(array_map('trim', explode(',', (string)$skillsNeeded)));
    }

    if (empty($title)) {
        $errors[] = 'Tên hoạt động/dự án không được để trống.';
    }
    if ($capacity < 1) {
        $errors[] = 'Chỉ tiêu quân số phải lớn hơn 0.';
    }

    if (empty($errors)) {
        $categoryNames = [
            'thuc_te_moi_truong' => 'Bảo Vệ Môi Trường',
            'hien_mau_y_te' => 'Hiến Máu & Y Tế',
            'tiep_suc_giao_duc' => 'Tiếp Sức Giáo Dục',
            'cuu_tro_an_sinh' => 'Cứu Trợ & Bếp Ăn 0Đ',
            'cham_soc_yeu_thuong' => 'Chăm Sóc Xã Hội',
        ];

        $updatePayload = [
            'title' => $title,
            'category' => $category,
            'category_name' => $categoryNames[$category] ?? 'Hoạt Động Tình Nguyện',
            'date_string' => $dateString,
            'time_range' => $timeRange,
            'location' => $location,
            'meeting_point' => $meetingPoint,
            'capacity' => $capacity,
            'leader_name' => $leaderName,
            'leader_phone' => $leaderPhone,
            'skills_needed' => $skillsNeeded,
            'benefits' => $project['benefits'] ?? ['Áo TNV', 'Nước uống', 'Giấy chứng nhận'],
            'summary' => $summary,
            'status' => $status,
            'is_urgent' => $isUrgent
        ];

        if ($db->updateProject($projectId, $updatePayload)) {
            $_SESSION['flash_msg'] = [
                'type' => 'success',
                'title' => 'Cập Nhật Thành Công',
                'content' => 'Đã lưu thay đổi thông tin dự án "' . htmlspecialchars($title) . '" vào cơ sở dữ liệu và đồng bộ toàn hệ thống.'
            ];
            $redirectTarget = !empty($_GET['from']) && $_GET['from'] === 'hoat_dong' ? '/hoat_dong.php' : '/admin_import.php?tab=projects';
            header('Location: ' . $redirectTarget);
            exit;
        } else {
            $errors[] = 'Lỗi trong quá trình cập nhật dữ liệu. Vui lòng thử lại.';
        }
    }
}

$pageTitle = 'Chỉnh Sửa Thông Tin Dự Án #' . $projectId;
require_once __DIR__ . '/../templates/header.php';
?>

<div class="pro-page-wrapper" style="background: #f8fafc; min-height: 85vh; padding-bottom: 60px;">
    <!-- BREADCRUMB & HEADER -->
    <div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #ffffff; padding: 26px 0; border-bottom: 3px solid #059669;">
        <div style="max-width: 1100px; margin: 0 auto; padding: 0 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                <div>
                    <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #94a3b8; margin-bottom: 6px;">
                        <a href="/admin_import.php?tab=projects" style="color: #34d399; text-decoration: none; font-weight: 600;">&larr; Quản Trị Dự Án</a>
                        <span>/</span>
                        <span>Dự Án #<?= $projectId ?></span>
                        <span>/</span>
                        <span style="color: #ffffff;">Chỉnh Sửa Thông Tin</span>
                    </div>
                    <h1 style="font-size: 24px; font-weight: 800; margin: 0; color: #ffffff;">
                        Chỉnh Sửa Hoạt Động #<?= $projectId ?>
                    </h1>
                </div>
                <div style="display: flex; gap: 10px;">
                    <a href="/hoat_dong.php" class="btn-action-pro" style="background: rgba(255,255,255,0.1); color: #ffffff; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; border: 1px solid rgba(255,255,255,0.2);">
                        Xem Trang Khách
                    </a>
                    <a href="/admin_import.php?tab=projects" class="btn-action-pro" style="background: #ffffff; color: #0f172a; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 700;">
                        Quay Lại Quản Lý
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div style="max-width: 1100px; margin: 28px auto 0; padding: 0 20px;">
        <!-- THÔNG BÁO LỖI NẾU CÓ -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 14px 18px; border-radius: 10px; margin-bottom: 20px;">
                <strong>Không thể lưu thay đổi:</strong>
                <ul style="margin: 6px 0 0 20px; padding: 0;">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- FORM CHỈNH SỬA CHI TIẾT -->
        <form method="POST" action="/chinh_sua_du_an.php?id=<?= $projectId ?><?= !empty($_GET['from']) ? '&from=' . htmlspecialchars($_GET['from']) : '' ?>" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 4px 14px rgba(0,0,0,0.04); overflow: hidden;">
            <input type="hidden" name="action" value="save_project">
            <input type="hidden" name="id" value="<?= $projectId ?>">

            <!-- KHỐI 1: THÔNG TIN CỐT LÕI -->
            <div style="padding: 24px 28px; border-bottom: 1px solid #e2e8f0;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="background: #059669; color: #ffffff; width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">1</span>
                        <h2 style="font-size: 17px; font-weight: 700; color: #0f172a; margin: 0;">Thông Tin Cơ Bản</h2>
                    </div>
                    <span style="font-size: 12px; background: #ecfdf5; color: #047857; font-weight: 700; padding: 4px 10px; border-radius: 20px; border: 1px solid #a7f3d0;">
                        Mã Dự Án: #<?= $projectId ?>
                    </span>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 18px; margin-bottom: 16px;">
                    <div>
                        <label style="display: block; font-size: 13.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                            Tên Hoạt Động / Chiến Dịch <span style="color: #dc2626;">*</span>
                        </label>
                        <input type="text" name="title" value="<?= htmlspecialchars($project['title'] ?? '') ?>" required
                               style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; font-weight: 600; color: #0f172a;">
                    </div>

                    <div>
                        <label style="display: block; font-size: 13.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                            Nhóm Chủ Đề <span style="color: #dc2626;">*</span>
                        </label>
                        <select name="category" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; background: #ffffff;">
                            <option value="thuc_te_moi_truong" <?= ($project['category'] ?? '') === 'thuc_te_moi_truong' ? 'selected' : '' ?>>Bảo Vệ Môi Trường Xanh</option>
                            <option value="hien_mau_y_te" <?= ($project['category'] ?? '') === 'hien_mau_y_te' ? 'selected' : '' ?>>Hiến Máu & Chăm Sóc Y Tế</option>
                            <option value="tiep_suc_giao_duc" <?= ($project['category'] ?? '') === 'tiep_suc_giao_duc' ? 'selected' : '' ?>>Tiếp Sức Đến Trường / Giáo Dục</option>
                            <option value="cuu_tro_an_sinh" <?= ($project['category'] ?? '') === 'cuu_tro_an_sinh' ? 'selected' : '' ?>>Cứu Trợ & Bếp Ăn 0 Đồng</option>
                            <option value="cham_soc_yeu_thuong" <?= ($project['category'] ?? '') === 'cham_soc_yeu_thuong' ? 'selected' : '' ?>>Chăm Sóc Người Yếu Thế / Xã Hội</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label style="display: block; font-size: 13.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                        Tóm Tắt Mục Tiêu & Mô Tả Hoạt Động
                    </label>
                    <textarea name="summary" rows="3" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; color: #334155; line-height: 1.5;"><?= htmlspecialchars($project['summary'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- KHỐI 2: THỜI GIAN, ĐỊA BÀN & QUÂN SỐ -->
            <div style="padding: 24px 28px; border-bottom: 1px solid #e2e8f0; background: #fdfdfd;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 18px;">
                    <span style="background: #2563eb; color: #ffffff; width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">2</span>
                    <h2 style="font-size: 17px; font-weight: 700; color: #0f172a; margin: 0;">Lịch Trình, Địa Bàn & Quân Số</h2>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 16px;">
                    <div>
                        <label style="display: block; font-size: 13.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                            Thời Gian / Ngày Diễn Ra
                        </label>
                        <input type="text" name="date_string" value="<?= htmlspecialchars($project['date_string'] ?? '') ?>" placeholder="Ví dụ: Thứ Bảy, 08/11/2026 hoặc 24/10/2026 – 25/10/2026"
                               style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px;">
                    </div>

                    <div>
                        <label style="display: block; font-size: 13.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                            Khung Giờ Hoạt Động (Phiên)
                        </label>
                        <input type="text" name="time_range" value="<?= htmlspecialchars($project['time_range'] ?? '') ?>" placeholder="Ví dụ: 08:00 – 17:00 hoặc Theo ca làm việc"
                               style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 16px;">
                    <div>
                        <label style="display: block; font-size: 13.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                            Địa Bàn Thực Địa
                        </label>
                        <input type="text" name="location" value="<?= htmlspecialchars($project['location'] ?? '') ?>" placeholder="Ví dụ: Quận Hoàn Kiếm, Hà Nội hoặc Huyện Bát Xát, Lào Cai"
                               style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px;">
                    </div>

                    <div>
                        <label style="display: block; font-size: 13.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                            Điểm Tập Kết / Xuất Phát
                        </label>
                        <input type="text" name="meeting_point" value="<?= htmlspecialchars($project['meeting_point'] ?? '') ?>" placeholder="Ví dụ: Cổng chính Công viên Thống Nhất"
                               style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 18px;">
                    <div>
                        <label style="display: block; font-size: 13.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                            Chỉ Tiêu Quân Số Cần Tuyển <span style="color: #dc2626;">*</span>
                        </label>
                        <input type="number" name="capacity" min="1" max="500" value="<?= (int)($project['capacity'] ?? 20) ?>" required
                               style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; font-weight: 700; color: #0f172a;">
                    </div>

                    <div>
                        <label style="display: block; font-size: 13.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                            Người Phụ Trách (Chỉ Huy)
                        </label>
                        <input type="text" name="leader_name" value="<?= htmlspecialchars($project['leader_name'] ?? 'Ban Chỉ Huy') ?>"
                               style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px;">
                    </div>

                    <div>
                        <label style="display: block; font-size: 13.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                            Số Điện Thoại Khẩn Cấp
                        </label>
                        <input type="text" name="leader_phone" value="<?= htmlspecialchars($project['leader_phone'] ?? '0912.456.789') ?>"
                               style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px;">
                    </div>
                </div>
            </div>

            <!-- KHỐI 3: TRẠNG THÁI & KỸ NĂNG YÊU CẦU -->
            <div style="padding: 24px 28px; border-bottom: 1px solid #e2e8f0;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 18px;">
                    <span style="background: #7c3aed; color: #ffffff; width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">3</span>
                    <h2 style="font-size: 17px; font-weight: 700; color: #0f172a; margin: 0;">Trạng Thái & Kỹ Năng Tiếp Nhận</h2>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 16px;">
                    <div>
                        <label style="display: block; font-size: 13.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                            Trạng Thái Tuyển Quân
                        </label>
                        <select name="status" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; background: #ffffff;">
                            <option value="recruiting" <?= ($project['status'] ?? '') === 'recruiting' ? 'selected' : '' ?>>Đang Tuyển Quân (Mở Đăng Ký)</option>
                            <option value="full" <?= ($project['status'] ?? '') === 'full' ? 'selected' : '' ?>>Đã Đủ Quân Số (Khóa Tạm Thời)</option>
                            <option value="completed" <?= ($project['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Đã Hoàn Thành Hoạt Động</option>
                        </select>
                    </div>

                    <div style="display: flex; align-items: center; padding-top: 24px;">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; background: #fff1f2; border: 1px solid #fecdd3; padding: 10px 16px; border-radius: 8px; width: 100%;">
                            <input type="checkbox" name="is_urgent" value="1" <?= !empty($project['is_urgent']) ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: #dc2626;">
                            <span style="font-size: 13.5px; font-weight: 700; color: #be123c;">
                                Gắn Cờ: Cần Tuyển Gấp (Khẩn Cấp - Ưu Tiên Phân Ca)
                            </span>
                        </label>
                    </div>
                </div>

                <div>
                    <label style="display: block; font-size: 13.5px; font-weight: 700; color: #334155; margin-bottom: 8px;">
                        Kỹ Năng / Chuyên Môn Ưu Tiên Tuyển Chọn
                    </label>
                    <?php 
                    $currentSkills = (array)($project['skills_needed'] ?? []);
                    $standardSkills = [
                        'y_te' => 'Sơ cấp cứu & Y tế',
                        'hau_can' => 'Hậu cần & Thể lực tốt',
                        'dieu_phoi' => 'Điều phối & Quản lý nhóm',
                        'truyen_thong' => 'Nhiếp ảnh & Truyền thông',
                        'su_pham' => 'Sư phạm & Dạy học',
                        'lai_xe' => 'Lái xe tải / bán tải',
                        'tam_ly' => 'Tâm lý & Công tác xã hội',
                    ];
                    ?>
                    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                        <?php foreach ($standardSkills as $sKey => $sLabel): 
                            $isSelected = in_array($sLabel, $currentSkills, true) || in_array($sKey, $currentSkills, true);
                        ?>
                            <label style="display: inline-flex; align-items: center; gap: 6px; background: <?= $isSelected ? '#eff6ff' : '#f8fafc' ?>; border: 1px solid <?= $isSelected ? '#93c5fd' : '#cbd5e1' ?>; padding: 6px 12px; border-radius: 20px; font-size: 13px; cursor: pointer;">
                                <input type="checkbox" name="skills_needed[]" value="<?= htmlspecialchars($sLabel) ?>" <?= $isSelected ? 'checked' : '' ?> style="accent-color: #2563eb;">
                                <span style="font-weight: <?= $isSelected ? '700' : '500' ?>; color: <?= $isSelected ? '#1d4ed8' : '#334155' ?>;"><?= htmlspecialchars($sLabel) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- NÚT LƯU VÀ HÀNH ĐỘNG -->
            <div style="padding: 20px 28px; background: #f8fafc; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                <span style="font-size: 13px; color: #64748b;">
                    Mọi thay đổi sẽ được cập nhật trực tiếp vào hệ thống dữ liệu.
                </span>
                <div style="display: flex; gap: 12px;">
                    <a href="/admin_import.php?tab=projects" style="padding: 10px 20px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #475569; font-weight: 600; font-size: 14px; text-decoration: none;">
                        Hủy Bỏ
                    </a>
                    <button type="submit" style="padding: 10px 28px; border-radius: 8px; border: none; background: #059669; color: #ffffff; font-weight: 700; font-size: 14px; cursor: pointer; box-shadow: 0 2px 8px rgba(5,150,105,0.3);">
                        Lưu Thay Đổi
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>

