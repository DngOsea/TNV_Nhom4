<?php
declare(strict_types=1);

/**
 * TRANG QUẢN TRỊ CƠ SỞ DỮ LIỆU & IMPORT 50 DỰ ÁN, 50 TÌNH NGUYỆN VIÊN (D2L ADMIN)
 * Hỗ trợ bộ lọc đa thuộc tính (thời gian, quy mô, địa điểm, kỹ năng, lịch rảnh, nhóm máu)
 */

require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/SkillManager.php';
require_once __DIR__ . '/../src/ActivityManager.php';

Auth::requireCoordinator();

$db = Database::getInstance();
$actionResult = null;

// Xử lý POST request: Import hoặc Reset Database
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'run_import') {
    try {
        $importResult = $db->importSeedSql();
        if ($importResult['success']) {
            $_SESSION['flash_message'] = "Đã import và đồng bộ thành công 50 Dự Án & 50 Tình Nguyện Viên vào Database!";
            header("Location: /admin_import.php?imported=1");
            exit;
        } else {
            $actionResult = ['type' => 'danger', 'msg' => 'Import thất bại: ' . ($importResult['error'] ?? 'Lỗi không xác định')];
        }
    } catch (Throwable $e) {
        $actionResult = ['type' => 'danger', 'msg' => 'Lỗi thực thi SQL: ' . $e->getMessage()];
    }
}

// Lấy thông tin thống kê hiện tại (luôn đảm bảo có dữ liệu)
$stats = $db->getStats();

// ==========================================
// THAM SỐ BỘ LỌC ĐA THUỘC TÍNH CHO DỰ ÁN
// ==========================================
$projSearch = trim($_GET['search_proj'] ?? '');
$projCats = isset($_GET['proj_cats']) && is_array($_GET['proj_cats']) ? $_GET['proj_cats'] : (!empty($_GET['proj_cat']) && $_GET['proj_cat'] !== 'all' ? [$_GET['proj_cat']] : []);
$projStatuses = isset($_GET['proj_statuses']) && is_array($_GET['proj_statuses']) ? $_GET['proj_statuses'] : (!empty($_GET['proj_status']) && $_GET['proj_status'] !== 'all' ? [$_GET['proj_status']] : []);
$projTimes = isset($_GET['proj_times']) && is_array($_GET['proj_times']) ? $_GET['proj_times'] : [];
$projScales = isset($_GET['proj_scales']) && is_array($_GET['proj_scales']) ? $_GET['proj_scales'] : [];
$projRegions = isset($_GET['proj_regions']) && is_array($_GET['proj_regions']) ? $_GET['proj_regions'] : [];

$projFilters = [
    'search' => $projSearch,
    'category' => $projCats,
    'status' => $projStatuses,
    'time_type' => $projTimes,
    'scale' => $projScales,
    'region' => $projRegions
];
$projects = $db->getProjects($projFilters);

// ==========================================
// THAM SỐ BỘ LỌC ĐA THUỘC TÍNH CHO TÌNH NGUYỆN VIÊN
// ==========================================
$volSearch = trim($_GET['search_vol'] ?? '');
$volSkills = isset($_GET['vol_skills']) && is_array($_GET['vol_skills']) ? $_GET['vol_skills'] : (!empty($_GET['vol_skill']) && $_GET['vol_skill'] !== 'all' ? [$_GET['vol_skill']] : []);
$volRegions = isset($_GET['vol_regions']) && is_array($_GET['vol_regions']) ? $_GET['vol_regions'] : [];
$volAvails = isset($_GET['vol_avails']) && is_array($_GET['vol_avails']) ? $_GET['vol_avails'] : [];
$volBloods = isset($_GET['vol_bloods']) && is_array($_GET['vol_bloods']) ? $_GET['vol_bloods'] : [];
$volHours = isset($_GET['vol_hours']) && is_array($_GET['vol_hours']) ? $_GET['vol_hours'] : [];
$volCertified = !empty($_GET['vol_certified']);

$volFilters = [
    'search' => $volSearch,
    'skill' => $volSkills,
    'region' => $volRegions,
    'availability' => $volAvails,
    'blood_group' => $volBloods,
    'hours_level' => $volHours,
    'certified_only' => $volCertified
];
$volunteers = $db->getVolunteers($volFilters);

$activeTab = $_GET['tab'] ?? 'projects';
$pageTitle = 'Quản Lý Dự Án & Tình Nguyện Viên';
require_once __DIR__ . '/../templates/header.php';
?>

<div class="pro-page-wrapper">
    <!-- BANNER TIÊU ĐỀ TRANG -->
    <div class="pro-page-header" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #ffffff; padding: 32px 0; border-bottom: 3px solid #059669;">
        <div class="header-container" style="max-width: 1240px; margin: 0 auto; padding: 0 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                <div>
                    <div style="display: inline-flex; align-items: center; gap: 8px; background: rgba(5,150,105,0.25); border: 1px solid #059669; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; color: #34d399; margin-bottom: 10px;">
                        <span>Cơ Sở Dữ Liệu Hệ Thống</span> &bull; <span><?= htmlspecialchars($stats['driver']) ?></span>
                    </div>
                    <h1 style="font-size: 26px; font-weight: 800; margin: 0 0 6px 0; color: #ffffff; letter-spacing: -0.5px;">
                        Quản Lý Dự Án & Tình Nguyện Viên
                    </h1>
                    <p style="font-size: 14px; color: #cbd5e1; margin: 0; max-width: 780px; line-height: 1.5;">
                        Tra cứu, lọc đa tiêu chí và quản lý danh mục hoạt động cùng lực lượng tình nguyện viên.
                    </p>
                </div>
                <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <form method="POST" style="margin: 0;" onsubmit="return confirm('Bạn có chắc chắn muốn đặt lại dữ liệu gốc của hệ thống?');">
                        <input type="hidden" name="action" value="run_import">
                        <button type="submit" class="btn-import-run" style="background: #059669; color: #ffffff; border: none; padding: 11px 20px; font-size: 13.5px; font-weight: 700; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(5,150,105,0.4);">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                            <span>Đặt Lại Dữ Liệu</span>
                        </button>
                    </form>
                    <a href="/hoat_dong.php" style="background: #ffffff; color: #0f172a; text-decoration: none; padding: 11px 16px; font-size: 13.5px; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; border: 1px solid #cbd5e1;">
                        <span>Xem Hoạt Động</span> &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="pro-content-body" style="max-width: 1240px; margin: 26px auto; padding: 0 20px;">
        <?php if ($actionResult): ?>
            <div style="padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; background: <?= $actionResult['type'] === 'danger' ? '#fef2f2' : '#ecfdf5' ?>; color: <?= $actionResult['type'] === 'danger' ? '#dc2626' : '#059669' ?>; border: 1px solid <?= $actionResult['type'] === 'danger' ? '#fecaca' : '#a7f3d0' ?>;">
                <?= htmlspecialchars($actionResult['msg']) ?>
            </div>
        <?php endif; ?>

        <!-- 4 CARDS TỔNG QUAN HỆ THỐNG -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 26px;">
            <div class="metric-card-interactive" style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 20px; border-top: 4px solid #059669; box-shadow: 0 4px 16px rgba(5,150,105,0.08);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <span style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Dự Án / Hoạt Động</span>
                    <span style="background: #ecfdf5; color: #059669; padding: 3px 9px; border-radius: 12px; font-size: 11.5px; font-weight: 700; border: 1px solid #a7f3d0;">5 Nhóm</span>
                </div>
                <div style="font-size: 32px; font-weight: 800; color: #0f172a; margin-bottom: 4px; letter-spacing: -0.5px;">
                    <?= $stats['projects_count'] ?> <span style="font-size: 14px; font-weight: 600; color: #64748b;">chiến dịch</span>
                </div>
                <div style="font-size: 12.5px; color: #475569;">10 dự án/nhóm (Môi trường, Y tế, GD, Cứu trợ, Chăm sóc)</div>
            </div>

            <div class="metric-card-interactive" style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 20px; border-top: 4px solid #2563eb; box-shadow: 0 4px 16px rgba(37,99,235,0.08);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <span style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Tình Nguyện Viên</span>
                    <span style="background: #eff6ff; color: #2563eb; padding: 3px 9px; border-radius: 12px; font-size: 11.5px; font-weight: 700; border: 1px solid #bfdbfe;">TNV-001 - 050</span>
                </div>
                <div style="font-size: 32px; font-weight: 800; color: #0f172a; margin-bottom: 4px; letter-spacing: -0.5px;">
                    <?= $stats['volunteers_count'] ?> <span style="font-size: 14px; font-weight: 600; color: #64748b;">nhân sự</span>
                </div>
                <div style="font-size: 12.5px; color: #475569;">Kỹ năng tự nhập, nhóm máu, lịch rảnh & sức khỏe</div>
            </div>

            <div class="metric-card-interactive" style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 20px; border-top: 4px solid #d97706; box-shadow: 0 4px 16px rgba(217,119,6,0.08);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <span style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Ca Trực & Lượt Gán</span>
                    <span style="background: #fffbeb; color: #d97706; padding: 3px 9px; border-radius: 12px; font-size: 11.5px; font-weight: 700; border: 1px solid #fde68a;">Đa ca/Đa ngày</span>
                </div>
                <div style="font-size: 32px; font-weight: 800; color: #0f172a; margin-bottom: 4px; letter-spacing: -0.5px;">
                    <?= $stats['shifts_count'] ?> <span style="font-size: 14px; font-weight: 600; color: #64748b;">ca trực</span>
                </div>
                <div style="font-size: 12.5px; color: #475569;"><?= $stats['registrations_count'] ?> lượt phân công mẫu đã liên kết vào ca</div>
            </div>

            <div class="metric-card-interactive" style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 20px; border-top: 4px solid #7c3aed; box-shadow: 0 4px 16px rgba(124,58,237,0.08);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <span style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Cơ Sở Dữ Liệu</span>
                    <span style="background: #f5f3ff; color: #7c3aed; padding: 3px 9px; border-radius: 12px; font-size: 11.5px; font-weight: 700; border: 1px solid #ddd6fe;"><?= $stats['driver'] ?></span>
                </div>
                <div style="font-size: 32px; font-weight: 800; color: #0f172a; margin-bottom: 4px; letter-spacing: -0.5px;">
                    <?= $stats['file_size'] ?>
                </div>
                <div style="font-size: 12.5px; color: #475569;">volunteer_platform.sqlite</div>
            </div>
        </div>

        <!-- TABS XEM CHI TIẾT DỮ LIỆU: DỰ ÁN & TÌNH NGUYỆN VIÊN -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); overflow: hidden; margin-bottom: 30px;">
            <div style="display: flex; border-bottom: 2px solid #e2e8f0; background: #f8fafc; padding: 0 16px;">
                <button type="button" class="admin-tab-btn <?= $activeTab === 'projects' ? 'active' : '' ?>" id="tab_btn_projects" onclick="switchAdminTab('projects')" style="padding: 14px 20px; font-size: 14.5px; font-weight: 700; color: <?= $activeTab === 'projects' ? '#059669' : '#64748b' ?>; border: none; background: transparent; border-bottom: 3px solid <?= $activeTab === 'projects' ? '#059669' : 'transparent' ?>; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                    <span>Dự Án / Hoạt Động</span>
                    <span id="badge_count_projects" style="background: #ecfdf5; color: #059669; padding: 2px 8px; border-radius: 12px; font-size: 12px;"><?= count($projects) ?> / <?= $stats['projects_count'] ?></span>
                </button>
                <button type="button" class="admin-tab-btn <?= $activeTab === 'volunteers' ? 'active' : '' ?>" id="tab_btn_volunteers" onclick="switchAdminTab('volunteers')" style="padding: 14px 20px; font-size: 14.5px; font-weight: 700; color: <?= $activeTab === 'volunteers' ? '#059669' : '#64748b' ?>; border: none; background: transparent; border-bottom: 3px solid <?= $activeTab === 'volunteers' ? '#059669' : 'transparent' ?>; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                    <span>Tình Nguyện Viên</span>
                    <span id="badge_count_volunteers" style="background: #eff6ff; color: #2563eb; padding: 2px 8px; border-radius: 12px; font-size: 12px;"><?= count($volunteers) ?> / <?= $stats['volunteers_count'] ?></span>
                </button>
            </div>

            <!-- ======================================================== -->
            <!-- TAB 1: DANH SÁCH DỰ ÁN VỚI BỘ LỌC ĐA THUỘC TÍNH (COMPACT SEARCH) -->
            <!-- ======================================================== -->
            <div id="admin_tab_content_projects" style="padding: 20px; display: <?= $activeTab === 'projects' ? 'block' : 'none' ?>;">
                <!-- KHUNG LỌC ĐA THUỘC TÍNH CHO DỰ ÁN -->
                <form method="GET" action="/admin_import.php" id="form_filter_projects" style="background: #f8fafc; padding: 16px; border-radius: 10px; border: 1px solid #e2e8f0; margin-bottom: 20px;">
                    <input type="hidden" name="tab" value="projects">

                    <!-- HÀNG 1: TÌM KIẾM NGẮN GỌN + CÁC Ô CHỌN THUỘC TÍNH -->
                    <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 12px;">
                        <!-- Ô tìm kiếm thu gọn -->
                        <div style="width: 220px; flex-shrink: 0;">
                            <input type="text" name="search_proj" value="<?= htmlspecialchars($projSearch) ?>" placeholder="Tìm tên, địa bàn..." style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13.5px; background: #ffffff;">
                        </div>

                        <!-- Chọn nhóm chủ đề -->
                        <div style="min-width: 170px;">
                            <select name="proj_cats[]" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; background: #ffffff;">
                                <option value="">-- Nhóm Chủ Đề (Tất cả) --</option>
                                <option value="thuc_te_moi_truong" <?= in_array('thuc_te_moi_truong', $projCats, true) ? 'selected' : '' ?>>Môi Trường Xanh</option>
                                <option value="hien_mau_y_te" <?= in_array('hien_mau_y_te', $projCats, true) ? 'selected' : '' ?>>Hiến Máu & Y Tế</option>
                                <option value="tiep_suc_giao_duc" <?= in_array('tiep_suc_giao_duc', $projCats, true) ? 'selected' : '' ?>>Tiếp Sức Giáo Dục</option>
                                <option value="cuu_tro_an_sinh" <?= in_array('cuu_tro_an_sinh', $projCats, true) ? 'selected' : '' ?>>Cứu Trợ & Bếp Ăn 0Đ</option>
                                <option value="cham_soc_yeu_thuong" <?= in_array('cham_soc_yeu_thuong', $projCats, true) ? 'selected' : '' ?>>Chăm Sóc Xã Hội</option>
                            </select>
                        </div>

                        <!-- Chọn Thời Gian -->
                        <div style="min-width: 150px;">
                            <select name="proj_times[]" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; background: #ffffff;">
                                <option value="">-- Thời Gian (Tất cả) --</option>
                                <option value="weekend" <?= in_array('weekend', $projTimes, true) ? 'selected' : '' ?>>Cuối tuần (T7, CN)</option>
                                <option value="weekday" <?= in_array('weekday', $projTimes, true) ? 'selected' : '' ?>>Trong tuần (T2-T6)</option>
                                <option value="early_morning" <?= in_array('early_morning', $projTimes, true) ? 'selected' : '' ?>>Ca sáng sớm (&lt; 07h)</option>
                                <option value="night" <?= in_array('night', $projTimes, true) ? 'selected' : '' ?>>Ca đêm (&gt; 21h)</option>
                            </select>
                        </div>

                        <!-- Chọn Quy Mô -->
                        <div style="min-width: 140px;">
                            <select name="proj_scales[]" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; background: #ffffff;">
                                <option value="">-- Quy Mô (Tất cả) --</option>
                                <option value="micro" <?= in_array('micro', $projScales, true) ? 'selected' : '' ?>>Vi mô (&lt; 15 TNV)</option>
                                <option value="medium" <?= in_array('medium', $projScales, true) ? 'selected' : '' ?>>Quy mô vừa (15-35)</option>
                                <option value="macro" <?= in_array('macro', $projScales, true) ? 'selected' : '' ?>>Đại quy mô (&gt; 35)</option>
                            </select>
                        </div>

                        <!-- Chọn Khu Vực / Địa Điểm -->
                        <div style="min-width: 150px;">
                            <select name="proj_regions[]" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; background: #ffffff;">
                                <option value="">-- Địa Điểm (Tất cả) --</option>
                                <option value="hanoi" <?= in_array('hanoi', $projRegions, true) ? 'selected' : '' ?>>Hà Nội (Thủ đô)</option>
                                <option value="north" <?= in_array('north', $projRegions, true) ? 'selected' : '' ?>>Vùng Cao / Tây Bắc</option>
                                <option value="central" <?= in_array('central', $projRegions, true) ? 'selected' : '' ?>>Miền Trung</option>
                                <option value="south" <?= in_array('south', $projRegions, true) ? 'selected' : '' ?>>TP.HCM & Miền Tây</option>
                            </select>
                        </div>

                        <!-- Chọn Trạng Thái -->
                        <div style="min-width: 140px;">
                            <select name="proj_statuses[]" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; background: #ffffff;">
                                <option value="">-- Trạng Thái --</option>
                                <option value="urgent" <?= in_array('urgent', $projStatuses, true) ? 'selected' : '' ?>>Tuyển Gấp (Khẩn cấp)</option>
                                <option value="recruiting" <?= in_array('recruiting', $projStatuses, true) ? 'selected' : '' ?>>Đang Tuyển Quân</option>
                                <option value="full" <?= in_array('full', $projStatuses, true) ? 'selected' : '' ?>>Đã Đủ Quân Số</option>
                                <option value="completed" <?= in_array('completed', $projStatuses, true) ? 'selected' : '' ?>>Đã Hoàn Thành</option>
                            </select>
                        </div>

                        <button type="submit" style="background: #0f172a; color: #ffffff; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 700; font-size: 13.5px; cursor: pointer;">
                            Áp Dụng Lọc
                        </button>
                        <a href="/admin_import.php?tab=projects" style="color: #64748b; font-size: 13px; text-decoration: underline;">Xóa Lọc</a>
                    </div>

                    <!-- HÀNG 2: CÁC Ô CHỌN NHANH ĐA THUỘC TÍNH DẠNG BADGE PILL (CLICK ĐỂ BẬT/TẮT NHIỀU THUỘC TÍNH) -->
                    <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center; border-top: 1px dashed #cbd5e1; padding-top: 10px; font-size: 12.5px;">
                        <span style="font-weight: 700; color: #475569;">Gợi ý chọn nhanh:</span>
                        <a href="/admin_import.php?tab=projects&proj_statuses[]=urgent" style="text-decoration: none; padding: 3px 10px; border-radius: 12px; font-weight: 600; background: <?= in_array('urgent', $projStatuses, true) ? '#dc2626' : '#fee2e2' ?>; color: <?= in_array('urgent', $projStatuses, true) ? '#ffffff' : '#991b1b' ?>;">
                            + Tuyển Gấp
                        </a>
                        <a href="/admin_import.php?tab=projects&proj_times[]=weekend" style="text-decoration: none; padding: 3px 10px; border-radius: 12px; font-weight: 600; background: <?= in_array('weekend', $projTimes, true) ? '#2563eb' : '#dbeafe' ?>; color: <?= in_array('weekend', $projTimes, true) ? '#ffffff' : '#1e40af' ?>;">
                            + Ca Cuối Tuần
                        </a>
                        <a href="/admin_import.php?tab=projects&proj_times[]=night" style="text-decoration: none; padding: 3px 10px; border-radius: 12px; font-weight: 600; background: <?= in_array('night', $projTimes, true) ? '#7c3aed' : '#ede9fe' ?>; color: <?= in_array('night', $projTimes, true) ? '#ffffff' : '#5b21b6' ?>;">
                            + Ca Đêm Tuần Tra
                        </a>
                        <a href="/admin_import.php?tab=projects&proj_scales[]=macro" style="text-decoration: none; padding: 3px 10px; border-radius: 12px; font-weight: 600; background: <?= in_array('macro', $projScales, true) ? '#059669' : '#d1fae5' ?>; color: <?= in_array('macro', $projScales, true) ? '#ffffff' : '#065f46' ?>;">
                            + Đại Quy Mô (&gt;35 TNV)
                        </a>
                        <a href="/admin_import.php?tab=projects&proj_scales[]=micro" style="text-decoration: none; padding: 3px 10px; border-radius: 12px; font-weight: 600; background: <?= in_array('micro', $projScales, true) ? '#d97706' : '#fef3c7' ?>; color: <?= in_array('micro', $projScales, true) ? '#ffffff' : '#92400e' ?>;">
                            + Vi Mô (&lt;15 TNV)
                        </a>
                        <a href="/admin_import.php?tab=projects&proj_regions[]=north" style="text-decoration: none; padding: 3px 10px; border-radius: 12px; font-weight: 600; background: <?= in_array('north', $projRegions, true) ? '#0891b2' : '#cffafe' ?>; color: <?= in_array('north', $projRegions, true) ? '#ffffff' : '#155e75' ?>;">
                            + Tây Bắc / Vùng Cao
                        </a>
                        <a href="/admin_import.php?tab=projects&proj_regions[]=south" style="text-decoration: none; padding: 3px 10px; border-radius: 12px; font-weight: 600; background: <?= in_array('south', $projRegions, true) ? '#ea580c' : '#ffedd5' ?>; color: <?= in_array('south', $projRegions, true) ? '#ffffff' : '#9a3412' ?>;">
                            + Miền Nam / TP.HCM
                        </a>
                    </div>
                </form>

                <!-- BẢNG DANH SÁCH DỰ ÁN -->
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 13.5px; text-align: left;">
                        <thead>
                            <tr style="background: #f1f5f9; border-bottom: 2px solid #cbd5e1; color: #334155; font-weight: 700;">
                                <th style="padding: 10px 12px; width: 55px;">ID</th>
                                <th style="padding: 10px 12px; min-width: 270px;">Tên Hoạt Động / Dự Án</th>
                                <th style="padding: 10px 12px; min-width: 150px;">Chủ Đề & Lịch Trực</th>
                                <th style="padding: 10px 12px; min-width: 140px;">Địa Bàn</th>
                                <th style="padding: 10px 12px; width: 110px;">Quân Số</th>
                                <th style="padding: 10px 12px; width: 120px;">Trạng Thái</th>
                                <th style="padding: 10px 12px; width: 85px; text-align: center;">Số Ca</th>
                                <th style="padding: 10px 12px; width: 95px; text-align: center;">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($projects)): ?>
                                <tr>
                                    <td colspan="7" style="padding: 30px; text-align: center; color: #64748b;">
                                        Không tìm thấy dự án phù hợp với bộ lọc đã chọn.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($projects as $idx => $p): 
                                    $isUrgent = !empty($p['is_urgent']);
                                    $isFull = ($p['registered'] >= $p['capacity']);
                                    $pct = $p['capacity'] > 0 ? round(($p['registered'] / $p['capacity']) * 100) : 0;
                                ?>
                                    <tr style="border-bottom: 1px solid #e2e8f0; background: <?= $idx % 2 === 0 ? '#ffffff' : '#f8fafc' ?>;">
                                        <td style="padding: 10px 12px; font-weight: 700; color: #64748b;">
                                            #<?= $p['id'] ?>
                                        </td>
                                        <td style="padding: 10px 12px;">
                                            <div style="font-weight: 700; color: #0f172a; margin-bottom: 3px;">
                                                <?= htmlspecialchars($p['title']) ?>
                                            </div>
                                            <div style="font-size: 12px; color: #64748b;">
                                                <span>Lãnh đạo: <strong><?= htmlspecialchars($p['leader_name'] ?? 'Ban Chỉ Huy') ?></strong> (<?= htmlspecialchars($p['leader_phone'] ?? '') ?>)</span>
                                            </div>
                                        </td>
                                        <td style="padding: 10px 12px;">
                                            <span style="display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11.5px; font-weight: 600; background: #f1f5f9; color: #334155; margin-bottom: 3px;">
                                                <?= htmlspecialchars($p['category_name'] ?? $p['category']) ?>
                                            </span>
                                            <div style="font-size: 11.5px; color: #64748b;"><?= htmlspecialchars($p['date_string'] ?? '') ?></div>
                                        </td>
                                        <td style="padding: 10px 12px; color: #475569; font-size: 12.5px;">
                                            <?= htmlspecialchars($p['location'] ?? '') ?>
                                        </td>
                                        <td style="padding: 10px 12px;">
                                            <div style="font-weight: 700; font-size: 13px; color: <?= $isUrgent ? '#dc2626' : ($isFull ? '#059669' : '#0f172a') ?>;">
                                                <?= $p['registered'] ?> / <?= $p['capacity'] ?>
                                            </div>
                                            <div style="width: 100%; height: 4px; background: #e2e8f0; border-radius: 2px; margin-top: 3px; overflow: hidden;">
                                                <div style="width: <?= min(100, $pct) ?>%; height: 100%; background: <?= $isUrgent ? '#dc2626' : ($isFull ? '#059669' : '#2563eb') ?>;"></div>
                                            </div>
                                        </td>
                                        <td style="padding: 10px 12px;">
                                            <?php if ($isUrgent): ?>
                                                <span style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 2px 7px; border-radius: 6px; font-size: 11px; font-weight: 700;">
                                                    Tuyển Gấp
                                                </span>
                                            <?php elseif ($p['status'] === 'full' || $isFull): ?>
                                                <span style="background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; padding: 2px 7px; border-radius: 6px; font-size: 11px; font-weight: 700;">
                                                    Đã Đủ Quân
                                                </span>
                                            <?php elseif ($p['status'] === 'completed'): ?>
                                                <span style="background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; padding: 2px 7px; border-radius: 6px; font-size: 11px; font-weight: 700;">
                                                    Hoàn Thành
                                                </span>
                                            <?php else: ?>
                                                <span style="background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; padding: 2px 7px; border-radius: 6px; font-size: 11px; font-weight: 700;">
                                                    Đang Tuyển
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 10px 12px; text-align: center; font-weight: 700;">
                                            <span style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 2px 6px; border-radius: 10px; font-size: 11.5px;">
                                                <?= count($p['shifts_list'] ?? []) ?> ca
                                            </span>
                                        </td>
                                        <td style="padding: 10px 12px; text-align: center;">
                                            <a href="/chinh_sua_du_an.php?id=<?= $p['id'] ?>" style="display: inline-flex; align-items: center; gap: 4px; background: #0284c7; color: #ffffff; text-decoration: none; padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; box-shadow: 0 1px 3px rgba(2,132,199,0.3);">
                                                &#9998; Sửa
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- TAB 2: DANH SÁCH TÌNH NGUYỆN VIÊN VỚI BỘ LỌC ĐA THUỘC TÍNH -->
            <!-- ======================================================== -->
            <div id="admin_tab_content_volunteers" style="padding: 20px; display: <?= $activeTab === 'volunteers' ? 'block' : 'none' ?>;">
                <!-- KHUNG LỌC ĐA THUỘC TÍNH CHO TÌNH NGUYỆN VIÊN -->
                <form method="GET" action="/admin_import.php" id="form_filter_volunteers" style="background: #f8fafc; padding: 16px; border-radius: 10px; border: 1px solid #e2e8f0; margin-bottom: 20px;">
                    <input type="hidden" name="tab" value="volunteers">

                    <!-- HÀNG 1: TÌM KIẾM NGẮN GỌN + CÁC Ô CHỌN THUỘC TÍNH -->
                    <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 12px;">
                        <!-- Ô tìm kiếm thu gọn -->
                        <div style="width: 220px; flex-shrink: 0;">
                            <input type="text" name="search_vol" value="<?= htmlspecialchars($volSearch) ?>" placeholder="Tìm tên, SĐT, trường, kỹ năng..." style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13.5px; background: #ffffff;">
                        </div>

                        <!-- Chọn Nhóm Kỹ Năng -->
                        <div style="min-width: 160px;">
                            <select name="vol_skills[]" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; background: #ffffff;">
                                <option value="">-- Kỹ Năng (Tất cả) --</option>
                                <option value="y_te" <?= in_array('y_te', $volSkills, true) ? 'selected' : '' ?>>Sơ cấp cứu & Y tế</option>
                                <option value="dieu_phoi" <?= in_array('dieu_phoi', $volSkills, true) ? 'selected' : '' ?>>Điều phối & Giao thông</option>
                                <option value="hau_can" <?= in_array('hau_can', $volSkills, true) ? 'selected' : '' ?>>Hậu cần & Bếp ăn</option>
                                <option value="truyen_thong" <?= in_array('truyen_thong', $volSkills, true) ? 'selected' : '' ?>>Truyền thông & Ảnh</option>
                                <option value="su_pham" <?= in_array('su_pham', $volSkills, true) ? 'selected' : '' ?>>Sư phạm & Dạy học</option>
                            </select>
                        </div>

                        <!-- Chọn Khu Vực / Trường Học -->
                        <div style="min-width: 150px;">
                            <select name="vol_regions[]" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; background: #ffffff;">
                                <option value="">-- Khu Vực (Tất cả) --</option>
                                <option value="hanoi" <?= in_array('hanoi', $volRegions, true) ? 'selected' : '' ?>>Hà Nội (ĐH, Viện)</option>
                                <option value="north" <?= in_array('north', $volRegions, true) ? 'selected' : '' ?>>Vùng Cao / Phía Bắc</option>
                                <option value="central" <?= in_array('central', $volRegions, true) ? 'selected' : '' ?>>Đà Nẵng & Miền Trung</option>
                                <option value="south" <?= in_array('south', $volRegions, true) ? 'selected' : '' ?>>TP.HCM & Cần Thơ</option>
                            </select>
                        </div>

                        <!-- Chọn Lịch Rảnh -->
                        <div style="min-width: 150px;">
                            <select name="vol_avails[]" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; background: #ffffff;">
                                <option value="">-- Lịch Rảnh (Tất cả) --</option>
                                <option value="weekend" <?= in_array('weekend', $volAvails, true) ? 'selected' : '' ?>>Rảnh Cuối Tuần (T7, CN)</option>
                                <option value="evening" <?= in_array('evening', $volAvails, true) ? 'selected' : '' ?>>Rảnh Buổi Tối</option>
                                <option value="morning" <?= in_array('morning', $volAvails, true) ? 'selected' : '' ?>>Rảnh Buổi Sáng</option>
                                <option value="afternoon" <?= in_array('afternoon', $volAvails, true) ? 'selected' : '' ?>>Rảnh Buổi Chiều</option>
                                <option value="flexible" <?= in_array('flexible', $volAvails, true) ? 'selected' : '' ?>>Linh hoạt nhiều ca</option>
                            </select>
                        </div>

                        <!-- Chọn Nhóm Máu -->
                        <div style="min-width: 140px;">
                            <select name="vol_bloods[]" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; background: #ffffff;">
                                <option value="">-- Nhóm Máu --</option>
                                <option value="rare" <?= in_array('rare', $volBloods, true) ? 'selected' : '' ?>>Máu Hiếm (O-, B-, AB-)</option>
                                <option value="O+" <?= in_array('O+', $volBloods, true) ? 'selected' : '' ?>>Nhóm O+</option>
                                <option value="A+" <?= in_array('A+', $volBloods, true) ? 'selected' : '' ?>>Nhóm A+</option>
                                <option value="B+" <?= in_array('B+', $volBloods, true) ? 'selected' : '' ?>>Nhóm B+</option>
                                <option value="AB+" <?= in_array('AB+', $volBloods, true) ? 'selected' : '' ?>>Nhóm AB+</option>
                            </select>
                        </div>

                        <!-- Chọn Mức Kinh Nghiệm -->
                        <div style="min-width: 140px;">
                            <select name="vol_hours[]" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; background: #ffffff;">
                                <option value="">-- Giờ Tích Lũy --</option>
                                <option value="veteran" <?= in_array('veteran', $volHours, true) ? 'selected' : '' ?>>Cán Bộ (&gt; 100h)</option>
                                <option value="medium" <?= in_array('medium', $volHours, true) ? 'selected' : '' ?>>Tích lũy vừa (30-100h)</option>
                                <option value="new" <?= in_array('new', $volHours, true) ? 'selected' : '' ?>>Tân Binh (&lt; 30h / 0h)</option>
                            </select>
                        </div>

                        <button type="submit" style="background: #0f172a; color: #ffffff; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 700; font-size: 13.5px; cursor: pointer;">
                            Áp Dụng Lọc
                        </button>
                        <a href="/admin_import.php?tab=volunteers" style="color: #64748b; font-size: 13px; text-decoration: underline;">Xóa Lọc</a>
                    </div>

                    <!-- HÀNG 2: CÁC Ô CHỌN NHANH ĐA THUỘC TÍNH DẠNG BADGE PILL -->
                    <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center; border-top: 1px dashed #cbd5e1; padding-top: 10px; font-size: 12.5px;">
                        <span style="font-weight: 700; color: #475569;">Gợi ý chọn nhanh:</span>
                        <a href="/admin_import.php?tab=volunteers&vol_bloods[]=rare" style="text-decoration: none; padding: 3px 10px; border-radius: 12px; font-weight: 600; background: <?= in_array('rare', $volBloods, true) ? '#dc2626' : '#fee2e2' ?>; color: <?= in_array('rare', $volBloods, true) ? '#ffffff' : '#991b1b' ?>;">
                            + Nhóm Máu Hiếm (O-, B-, AB-)
                        </a>
                        <a href="/admin_import.php?tab=volunteers&vol_skills[]=y_te" style="text-decoration: none; padding: 3px 10px; border-radius: 12px; font-weight: 600; background: <?= in_array('y_te', $volSkills, true) ? '#dc2626' : '#fee2e2' ?>; color: <?= in_array('y_te', $volSkills, true) ? '#ffffff' : '#991b1b' ?>;">
                            + Y Tế / Bác Sĩ
                        </a>
                        <a href="/admin_import.php?tab=volunteers&vol_skills[]=hau_can" style="text-decoration: none; padding: 3px 10px; border-radius: 12px; font-weight: 600; background: <?= in_array('hau_can', $volSkills, true) ? '#d97706' : '#fef3c7' ?>; color: <?= in_array('hau_can', $volSkills, true) ? '#ffffff' : '#92400e' ?>;">
                            + Hậu Cần / Xe Tải
                        </a>
                        <a href="/admin_import.php?tab=volunteers&vol_avails[]=weekend" style="text-decoration: none; padding: 3px 10px; border-radius: 12px; font-weight: 600; background: <?= in_array('weekend', $volAvails, true) ? '#2563eb' : '#dbeafe' ?>; color: <?= in_array('weekend', $volAvails, true) ? '#ffffff' : '#1e40af' ?>;">
                            + Rảnh Cuối Tuần
                        </a>
                        <a href="/admin_import.php?tab=volunteers&vol_hours[]=veteran" style="text-decoration: none; padding: 3px 10px; border-radius: 12px; font-weight: 600; background: <?= in_array('veteran', $volHours, true) ? '#059669' : '#d1fae5' ?>; color: <?= in_array('veteran', $volHours, true) ? '#ffffff' : '#065f46' ?>;">
                            + Cán Bộ Kỳ Cựu (&gt;100h)
                        </a>
                        <a href="/admin_import.php?tab=volunteers&vol_hours[]=new" style="text-decoration: none; padding: 3px 10px; border-radius: 12px; font-weight: 600; background: <?= in_array('new', $volHours, true) ? '#64748b' : '#f1f5f9' ?>; color: <?= in_array('new', $volHours, true) ? '#ffffff' : '#334155' ?>;">
                            + Tân Binh Mới (0h)
                        </a>
                    </div>
                </form>

                <!-- BẢNG DANH SÁCH TÌNH NGUYỆN VIÊN -->
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 13.5px; text-align: left;">
                        <thead>
                            <tr style="background: #f1f5f9; border-bottom: 2px solid #cbd5e1; color: #334155; font-weight: 700;">
                                <th style="padding: 10px 12px; width: 80px;">Mã TNV</th>
                                <th style="padding: 10px 12px; min-width: 180px;">Họ & Tên</th>
                                <th style="padding: 10px 12px; width: 70px; text-align: center;">Máu</th>
                                <th style="padding: 10px 12px; min-width: 200px;">Kỹ Năng Chuẩn & Chứng Chỉ</th>
                                <th style="padding: 10px 12px; min-width: 200px;">Kỹ Năng Tự Nhập (Custom)</th>
                                <th style="padding: 10px 12px; width: 100px;">Tích Lũy</th>
                                <th style="padding: 10px 12px; min-width: 170px;">Ghi Chú Sức Khỏe</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($volunteers)): ?>
                                <tr>
                                    <td colspan="7" style="padding: 30px; text-align: center; color: #64748b;">
                                        Không tìm thấy tình nguyện viên phù hợp với bộ lọc đã chọn.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($volunteers as $idx => $v): 
                                    $isRareBlood = in_array($v['blood_group'] ?? 'O+', ['O-', 'B-', 'AB-'], true);
                                    $hasCert = !empty($v['certified_skills']);
                                ?>
                                    <tr style="border-bottom: 1px solid #e2e8f0; background: <?= $idx % 2 === 0 ? '#ffffff' : '#f8fafc' ?>;">
                                        <td style="padding: 10px 12px; font-weight: 700; color: #059669; font-family: monospace;">
                                            <?= htmlspecialchars($v['id'] ?? '') ?>
                                        </td>
                                        <td style="padding: 10px 12px;">
                                            <div style="font-weight: 700; color: #0f172a; margin-bottom: 2px;">
                                                <?= htmlspecialchars($v['name'] ?? '') ?>
                                            </div>
                                            <div style="font-size: 11.5px; color: #64748b;">
                                                <span><?= htmlspecialchars($v['gender'] ?? 'Nam') ?> &bull; <?= htmlspecialchars($v['organization'] ?? '') ?></span>
                                            </div>
                                            <div style="font-size: 11px; color: #94a3b8; font-family: monospace;">
                                                <?= htmlspecialchars($v['phone'] ?? '') ?>
                                            </div>
                                        </td>
                                        <td style="padding: 10px 12px; text-align: center;">
                                            <span style="display: inline-block; padding: 2px 7px; border-radius: 5px; font-size: 11.5px; font-weight: 800; background: <?= $isRareBlood ? '#fef2f2' : '#f1f5f9' ?>; color: <?= $isRareBlood ? '#dc2626' : '#334155' ?>; border: 1px solid <?= $isRareBlood ? '#fecaca' : '#cbd5e1' ?>;" title="<?= $isRareBlood ? 'Nhóm máu hiếm cấp cứu' : '' ?>">
                                                <?= htmlspecialchars($v['blood_group'] ?? 'O+') ?>
                                            </span>
                                        </td>
                                        <td style="padding: 10px 12px;">
                                            <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                                <?php foreach ((array)($v['skills'] ?? []) as $sk): 
                                                    $isCert = in_array($sk, (array)($v['certified_skills'] ?? []), true);
                                                ?>
                                                    <span style="display: inline-flex; align-items: center; gap: 3px; padding: 2px 6px; border-radius: 8px; font-size: 11px; font-weight: 600; background: <?= $isCert ? '#ecfdf5' : '#eff6ff' ?>; color: <?= $isCert ? '#065f46' : '#1e40af' ?>; border: 1px solid <?= $isCert ? '#a7f3d0' : '#bfdbfe' ?>;">
                                                        <?= htmlspecialchars($sk) ?>
                                                        <?= $isCert ? '&#10003;' : '' ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php if ($hasCert): ?>
                                                <div style="font-size: 10.5px; color: #059669; font-weight: 600; margin-top: 3px;">
                                                    &#10003; Đã thẩm định chứng chỉ
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 10px 12px; font-size: 12px; color: #475569;">
                                            <?php if (!empty($v['custom_skills'])): ?>
                                                <ul style="margin: 0; padding-left: 14px; line-height: 1.35;">
                                                    <?php foreach ((array)$v['custom_skills'] as $csk): ?>
                                                        <li><?= htmlspecialchars($csk) ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php else: ?>
                                                <span style="color: #94a3b8; font-style: italic;">Chưa bổ sung</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 10px 12px;">
                                            <div style="font-weight: 700; color: #0f172a;">
                                                <?= (int)($v['volunteer_hours'] ?? 0) ?> giờ
                                            </div>
                                            <div style="font-size: 11px; color: #64748b;">
                                                Uy tín: <strong><?= number_format((float)($v['reliability_score'] ?? 10.0), 1) ?></strong>
                                            </div>
                                        </td>
                                        <td style="padding: 10px 12px; font-size: 11.5px; color: #64748b; line-height: 1.35;">
                                            <?= htmlspecialchars($v['health_note'] ?? 'Sức khỏe tốt') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function switchAdminTab(tab) {
    var btnProj = document.getElementById('tab_btn_projects');
    var btnVol = document.getElementById('tab_btn_volunteers');
    var contProj = document.getElementById('admin_tab_content_projects');
    var contVol = document.getElementById('admin_tab_content_volunteers');

    if (tab === 'projects') {
        btnProj.style.color = '#059669';
        btnProj.style.borderBottomColor = '#059669';
        btnVol.style.color = '#64748b';
        btnVol.style.borderBottomColor = 'transparent';
        contProj.style.display = 'block';
        contVol.style.display = 'none';
    } else {
        btnVol.style.color = '#2563eb';
        btnVol.style.borderBottomColor = '#2563eb';
        btnProj.style.color = '#64748b';
        btnProj.style.borderBottomColor = 'transparent';
        contVol.style.display = 'block';
        contProj.style.display = 'none';
    }
}
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
