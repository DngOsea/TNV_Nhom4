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
require_once __DIR__ . '/../src/LocationManager.php';
require_once __DIR__ . '/../src/Pagination.php';

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

// TỔNG HỢP DANH SÁCH QUÂN SỐ THỰC TẾ CHO TỪNG DỰ ÁN (ROSTER)
$allVolunteersMaster = $db->getVolunteers([]);

function buildProjectRoster(array $p, array $allVolunteers): array {
    $pId = (int)$p['id'];
    $roster = [];
    $usedVolIds = [];

    // 1. Kiểm tra trong CSDL volunteer_registrations
    try {
        $db = Database::getInstance();
        if ($db->isAvailable()) {
            $pdo = $db->getPdo();
            if ($pdo) {
                $stmt = $pdo->prepare("
                    SELECT vr.*, v.name as vol_name, v.phone as vol_phone, v.email as vol_email, 
                           v.organization as vol_org, v.skills as vol_skills, v.blood_group as vol_blood
                    FROM volunteer_registrations vr
                    JOIN volunteers v ON vr.volunteer_id = v.id
                    WHERE vr.project_id = :pid
                    ORDER BY vr.id ASC
                ");
                $stmt->execute([':pid' => $pId]);
                $dbRows = $stmt->fetchAll();
                foreach ($dbRows as $row) {
                    $vId = $row['volunteer_id'];
                    $usedVolIds[$vId] = true;
                    $roster[] = [
                        'id' => $vId,
                        'name' => $row['vol_name'],
                        'phone' => $row['vol_phone'] ?? '',
                        'email' => $row['vol_email'] ?? '',
                        'unit' => $row['vol_org'] ?? 'Đội TNV D2L',
                        'blood_group' => $row['vol_blood'] ?? 'O+',
                        'shift_name' => $row['shift_id'],
                        'role' => $row['role_in_shift'] ?? 'TNV Thực Địa',
                        'status' => $row['status'] ?? 'confirmed',
                        'skills' => json_decode((string)($row['vol_skills'] ?? '[]'), true) ?: []
                    ];
                }
            }
        }
    } catch (Throwable $e) {}

    // 2. Kiểm tra trong Session (đăng ký trực tuyến mới)
    if (!empty($_SESSION['volunteer_shift_registrations'])) {
        foreach ($_SESSION['volunteer_shift_registrations'] as $sr) {
            if ((int)($sr['activity_id'] ?? 0) === $pId) {
                $vId = $sr['user_id'] ?? 'TNV-ONLINE';
                if (!isset($usedVolIds[$vId])) {
                    $usedVolIds[$vId] = true;
                    $roster[] = [
                        'id' => $vId,
                        'name' => $sr['user_name'] ?? 'TNV Đăng Ký',
                        'phone' => $sr['user_phone'] ?? '',
                        'email' => $sr['user_email'] ?? '',
                        'unit' => 'TNV Trực Tuyến',
                        'blood_group' => 'Chưa rõ',
                        'shift_name' => $sr['shift_name'] ?? 'Ca Đã Đăng Ký',
                        'role' => 'TNV Đăng Ký Trực Tuyến',
                        'status' => $sr['status'] ?? 'confirmed',
                        'skills' => []
                    ];
                }
            }
        }
    }

    // 3. Phân bổ các ca trực của dự án
    $shifts = [];
    if (!empty($p['schedule_days'])) {
        foreach ($p['schedule_days'] as $day) {
            foreach ($day['shifts'] ?? [] as $sh) {
                $shifts[] = $sh;
            }
        }
    }
    if (empty($shifts) && !empty($p['shifts_list'])) {
        $shifts = $p['shifts_list'];
    }
    if (empty($shifts) && !empty($p['shifts'])) {
        foreach ($p['shifts'] as $sName) {
            $shifts[] = ['shift_name' => $sName, 'time_range' => $p['time_range'] ?? ''];
        }
    }

    $targetCount = max(count($roster), (int)($p['registered'] ?? 0));
    if ($targetCount <= 0) {
        $targetCount = min(15, (int)($p['capacity'] ?? 20));
    }

    // Ưu tiên TNV có lịch rảnh khớp với dự án này
    foreach ($allVolunteers as $v) {
        if (count($roster) >= $targetCount) break;
        $vId = $v['id'];
        if (isset($usedVolIds[$vId])) continue;

        $vAvail = $v['availability'] ?? [];
        $isMatched = false;
        foreach ($vAvail as $av) {
            if (str_starts_with((string)$av, (string)$pId) || $av === ('CA-' . sprintf('%02d', ($pId % 12) + 1))) {
                $isMatched = true;
                break;
            }
        }

        if ($isMatched) {
            $usedVolIds[$vId] = true;
            $sh = !empty($shifts) ? $shifts[count($roster) % count($shifts)] : null;
            $roster[] = [
                'id' => $vId,
                'name' => $v['name'],
                'phone' => $v['phone'] ?? '',
                'email' => $v['email'] ?? '',
                'unit' => $v['organization'] ?? 'Thanh Niên D2L',
                'blood_group' => $v['blood_group'] ?? 'O+',
                'shift_name' => $sh['shift_name'] ?? 'Ca Thực Địa',
                'role' => $v['role'] ?? 'TNV Nòng Cốt',
                'status' => 'confirmed',
                'skills' => $v['skills'] ?? []
            ];
        }
    }

    // Bổ sung các TNV phù hợp chuyên môn cho tới khi đủ sĩ số đã ghi nhận
    $cat = $p['category'] ?? '';
    $neededSkill = match($cat) {
        'cham_soc_suc_khoe' => 'y_te',
        'giao_duc_tre_em' => 'su_pham',
        'cuu_tro_an_sinh' => 'hau_can',
        'hoat_dong_dong_hanh' => 'truyen_thong',
        default => 'hau_can'
    };

    foreach ($allVolunteers as $v) {
        if (count($roster) >= $targetCount) break;
        $vId = $v['id'];
        if (isset($usedVolIds[$vId])) continue;

        $vSkills = (array)($v['skills'] ?? []);
        if (in_array($neededSkill, $vSkills, true) || count($roster) < 15) {
            $usedVolIds[$vId] = true;
            $sh = !empty($shifts) ? $shifts[count($roster) % count($shifts)] : null;
            $roster[] = [
                'id' => $vId,
                'name' => $v['name'],
                'phone' => $v['phone'] ?? '',
                'email' => $v['email'] ?? '',
                'unit' => $v['organization'] ?? 'Thanh Niên D2L',
                'blood_group' => $v['blood_group'] ?? 'A+',
                'shift_name' => $sh['shift_name'] ?? 'Ca Thực Địa',
                'role' => $v['role'] ?? 'TNV Thực Địa',
                'status' => (count($roster) === 3) ? 'absent' : 'confirmed',
                'skills' => $vSkills
            ];
        }
    }

    return $roster;
}

foreach ($projects as &$pItem) {
    $pItem['roster'] = buildProjectRoster($pItem, $allVolunteersMaster);
}
unset($pItem);

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

// ==========================================
// CẤU HÌNH PHÂN TRANG (PAGINATION)
// ==========================================
// 1. Phân trang Danh sách Dự Án (10 mục / trang)
$projPerPage = 10;
$projTotalItems = count($projects);
$projTotalPages = max(1, (int)ceil($projTotalItems / $projPerPage));
$projPage = max(1, (int)($_GET['p_page'] ?? ($_GET['tab'] !== 'volunteers' ? ($_GET['page'] ?? 1) : 1)));
if ($projPage > $projTotalPages) {
    $projPage = $projTotalPages;
}
$projOffset = ($projPage - 1) * $projPerPage;
$pagedProjects = array_slice($projects, $projOffset, $projPerPage);

// 2. Phân trang Danh sách Tình Nguyện Viên (10 mục / trang)
$volPerPage = 10;
$volTotalItems = count($volunteers);
$volTotalPages = max(1, (int)ceil($volTotalItems / $volPerPage));
$volPage = max(1, (int)($_GET['v_page'] ?? ($_GET['tab'] === 'volunteers' ? ($_GET['page'] ?? 1) : 1)));
if ($volPage > $volTotalPages) {
    $volPage = $volTotalPages;
}
$volOffset = ($volPage - 1) * $volPerPage;
$pagedVolunteers = array_slice($volunteers, $volOffset, $volPerPage);

// Hàm helper tạo URL phân trang giữ nguyên toàn bộ bộ lọc hiện tại
$buildPaginationUrl = function(string $paramName, int $targetPage, array $currentGet) {
    $params = $currentGet;
    $params[$paramName] = $targetPage;
    unset($params['page']); // bỏ page chung để tránh xung đột
    return '?' . http_build_query($params);
};

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
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e2e8f0; background: #f8fafc; padding: 0 16px; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex;">
                    <button type="button" class="admin-tab-btn <?= $activeTab === 'projects' ? 'active' : '' ?>" id="tab_btn_projects" onclick="switchAdminTab('projects')" style="padding: 14px 20px; font-size: 14.5px; font-weight: 700; color: <?= $activeTab === 'projects' ? '#059669' : '#64748b' ?>; border: none; background: transparent; border-bottom: 3px solid <?= $activeTab === 'projects' ? '#059669' : 'transparent' ?>; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                        <span>Dự Án / Hoạt Động</span>
                        <span id="badge_count_projects" style="background: #ecfdf5; color: #059669; padding: 2px 8px; border-radius: 12px; font-size: 12px;"><?= count($projects) ?> / <?= $stats['projects_count'] ?></span>
                    </button>
                    <button type="button" class="admin-tab-btn <?= $activeTab === 'volunteers' ? 'active' : '' ?>" id="tab_btn_volunteers" onclick="switchAdminTab('volunteers')" style="padding: 14px 20px; font-size: 14.5px; font-weight: 700; color: <?= $activeTab === 'volunteers' ? '#059669' : '#64748b' ?>; border: none; background: transparent; border-bottom: 3px solid <?= $activeTab === 'volunteers' ? '#059669' : 'transparent' ?>; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                        <span>Tình Nguyện Viên</span>
                        <span id="badge_count_volunteers" style="background: #eff6ff; color: #2563eb; padding: 2px 8px; border-radius: 12px; font-size: 12px;"><?= count($volunteers) ?> / <?= $stats['volunteers_count'] ?></span>
                    </button>
                </div>
                <div style="padding: 8px 0;">
                    <a href="/tao_su_kien.php?from=admin" class="btn-create-activity" style="background: #059669; color: #ffffff; text-decoration: none; padding: 8px 16px; border-radius: 8px; font-size: 13.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 6px rgba(5,150,105,0.25); transition: background 0.2s ease;">
                        <span>&#10010;</span> Thêm Hoạt Động Mới
                    </a>
                </div>
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
                            <?php if (empty($pagedProjects)): ?>
                                <tr>
                                    <td colspan="7" style="padding: 30px; text-align: center; color: #64748b;">
                                        Không tìm thấy dự án phù hợp với bộ lọc đã chọn.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pagedProjects as $idx => $p): 
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
                                        <?php $locAdmin = LocationManager::parse($p['location'] ?? ''); ?>
                                        <td style="padding: 10px 12px; font-size: 12.5px;">
                                            <div style="font-weight: 700; color: #0f172a; margin-bottom: 3px;">
                                                📍 <?= htmlspecialchars($locAdmin['short_location']) ?>
                                            </div>
                                            <div style="display: flex; align-items: center; gap: 4px;">
                                                <span style="background: <?= $locAdmin['region_bg'] ?>; color: <?= $locAdmin['region_color'] ?>; padding: 1px 6px; border-radius: 4px; font-size: 10.5px; font-weight: 700; border: 1px solid <?= $locAdmin['region_color'] ?>25;" title="<?= htmlspecialchars($locAdmin['full_location']) ?>">
                                                    <?= htmlspecialchars($locAdmin['region_name']) ?>
                                                </span>
                                            </div>
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
                                        <td style="padding: 10px 12px; text-align: center; white-space: nowrap;">
                                            <a href="/chinh_sua_du_an.php?id=<?= $p['id'] ?>" style="display: inline-flex; align-items: center; gap: 4px; background: #0284c7; color: #ffffff; text-decoration: none; padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; box-shadow: 0 1px 3px rgba(2,132,199,0.3);">
                                                &#9998; Sửa
                                            </a>
                                            <button type="button" onclick="openProjectDetailModal(<?= (int)$p['id'] ?>)" title="Xem chi tiết hoạt động tại đây" style="display: inline-flex; align-items: center; gap: 3px; background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; padding: 5px 8px; border-radius: 6px; font-size: 12px; font-weight: 700; margin-left: 4px; cursor: pointer;">
                                                👁 Xem
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- PHÂN TRANG DANH SÁCH DỰ ÁN -->
                <?php if ($projTotalPages > 1): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-top: 18px; padding-top: 14px; border-top: 1px solid #e2e8f0;">
                        <div style="font-size: 13px; color: #64748b;">
                            Hiển thị <strong><?= $projTotalItems > 0 ? $projOffset + 1 : 0 ?> – <?= min($projTotalItems, $projOffset + $projPerPage) ?></strong> trong tổng số <strong><?= $projTotalItems ?></strong> dự án
                        </div>
                        <div style="display: flex; gap: 4px; align-items: center;">
                            <?php if ($projPage > 1): ?>
                                <a href="<?= htmlspecialchars($buildPaginationUrl('p_page', $projPage - 1, array_merge($_GET, ['tab' => 'projects']))) ?>" style="padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; color: #334155; background: #ffffff;">&larr; Trước</a>
                            <?php else: ?>
                                <span style="padding: 6px 12px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px; color: #94a3b8; background: #f8fafc; cursor: not-allowed;">&larr; Trước</span>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $projTotalPages; $i++): ?>
                                <?php if ($i === 1 || $i === $projTotalPages || ($i >= $projPage - 2 && $i <= $projPage + 2)): ?>
                                    <a href="<?= htmlspecialchars($buildPaginationUrl('p_page', $i, array_merge($_GET, ['tab' => 'projects']))) ?>" style="padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 700; <?= $i === $projPage ? 'background: #059669; color: #ffffff; border: 1px solid #059669;' : 'background: #ffffff; color: #334155; border: 1px solid #cbd5e1;' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php elseif ($i === $projPage - 3 || $i === $projPage + 3): ?>
                                    <span style="padding: 0 4px; color: #94a3b8;">&hellip;</span>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($projPage < $projTotalPages): ?>
                                <a href="<?= htmlspecialchars($buildPaginationUrl('p_page', $projPage + 1, array_merge($_GET, ['tab' => 'projects']))) ?>" style="padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; color: #334155; background: #ffffff;">Sau &rarr;</a>
                            <?php else: ?>
                                <span style="padding: 6px 12px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px; color: #94a3b8; background: #f8fafc; cursor: not-allowed;">Sau &rarr;</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
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
                            <?php if (empty($pagedVolunteers)): ?>
                                <tr>
                                    <td colspan="7" style="padding: 30px; text-align: center; color: #64748b;">
                                        Không tìm thấy tình nguyện viên phù hợp với bộ lọc đã chọn.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pagedVolunteers as $idx => $v): 
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

                <!-- PHÂN TRANG DANH SÁCH TÌNH NGUYỆN VIÊN -->
                <?php if ($volTotalPages > 1): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-top: 18px; padding-top: 14px; border-top: 1px solid #e2e8f0;">
                        <div style="font-size: 13px; color: #64748b;">
                            Hiển thị <strong><?= $volTotalItems > 0 ? $volOffset + 1 : 0 ?> – <?= min($volTotalItems, $volOffset + $volPerPage) ?></strong> trong tổng số <strong><?= $volTotalItems ?></strong> tình nguyện viên
                        </div>
                        <div style="display: flex; gap: 4px; align-items: center;">
                            <?php if ($volPage > 1): ?>
                                <a href="<?= htmlspecialchars($buildPaginationUrl('v_page', $volPage - 1, array_merge($_GET, ['tab' => 'volunteers']))) ?>" style="padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; color: #334155; background: #ffffff;">&larr; Trước</a>
                            <?php else: ?>
                                <span style="padding: 6px 12px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px; color: #94a3b8; background: #f8fafc; cursor: not-allowed;">&larr; Trước</span>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $volTotalPages; $i++): ?>
                                <?php if ($i === 1 || $i === $volTotalPages || ($i >= $volPage - 2 && $i <= $volPage + 2)): ?>
                                    <a href="<?= htmlspecialchars($buildPaginationUrl('v_page', $i, array_merge($_GET, ['tab' => 'volunteers']))) ?>" style="padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 700; <?= $i === $volPage ? 'background: #2563eb; color: #ffffff; border: 1px solid #2563eb;' : 'background: #ffffff; color: #334155; border: 1px solid #cbd5e1;' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php elseif ($i === $volPage - 3 || $i === $volPage + 3): ?>
                                    <span style="padding: 0 4px; color: #94a3b8;">&hellip;</span>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($volPage < $volTotalPages): ?>
                                <a href="<?= htmlspecialchars($buildPaginationUrl('v_page', $volPage + 1, array_merge($_GET, ['tab' => 'volunteers']))) ?>" style="padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; color: #334155; background: #ffffff;">Sau &rarr;</a>
                            <?php else: ?>
                                <span style="padding: 6px 12px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px; color: #94a3b8; background: #f8fafc; cursor: not-allowed;">Sau &rarr;</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
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

// =========================================================================
// MODAL CHI TIẾT DỰ ÁN CHO NGƯỜI ĐIỀU PHỐI (BAN CHỈ HUY)
// =========================================================================
var adminProjectsData = <?= json_encode(array_column($projects, null, 'id'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
var currentModalProjectId = null;
var currentModalRoster = [];

function switchModalTab(tab) {
    var btnInfo = document.getElementById('modal_tab_btn_info');
    var btnRoster = document.getElementById('modal_tab_btn_roster');
    var contInfo = document.getElementById('modal_tab_content_info');
    var contRoster = document.getElementById('modal_tab_content_roster');

    if (tab === 'info') {
        btnInfo.style.color = '#059669';
        btnInfo.style.borderBottomColor = '#059669';
        btnRoster.style.color = '#64748b';
        btnRoster.style.borderBottomColor = 'transparent';
        contInfo.style.display = 'block';
        contRoster.style.display = 'none';
    } else {
        btnRoster.style.color = '#0284c7';
        btnRoster.style.borderBottomColor = '#0284c7';
        btnInfo.style.color = '#64748b';
        btnInfo.style.borderBottomColor = 'transparent';
        contRoster.style.display = 'block';
        contInfo.style.display = 'none';
    }
}

function openProjectDetailModal(projectId) {
    currentModalProjectId = projectId;
    var p = adminProjectsData[projectId];
    if (!p) {
        alert('Không tìm thấy thông tin chi tiết dự án #' + projectId);
        return;
    }

    document.getElementById('modal_p_id').textContent = '#' + p.id;
    document.getElementById('modal_p_title').textContent = p.title || '';
    document.getElementById('modal_p_cat').textContent = p.category_name || p.category || 'Chung';
    document.getElementById('modal_p_date').textContent = p.date_string || 'Đang cập nhật';
    document.getElementById('modal_p_time').textContent = p.time_range || 'Theo từng ca';
    document.getElementById('modal_p_location').textContent = p.location || 'Chưa xác định';
    document.getElementById('modal_p_meeting').textContent = p.meeting_point || 'Thông báo sau';
    document.getElementById('modal_p_leader').textContent = (p.leader_name || 'Ban Chỉ Huy') + (p.leader_phone ? ' (' + p.leader_phone + ')' : '');
    document.getElementById('modal_p_summary').textContent = p.summary || 'Chưa có mô tả chi tiết.';
    
    var reg = parseInt(p.registered, 10) || 0;
    var cap = parseInt(p.capacity, 10) || 0;
    var pct = cap > 0 ? Math.min(100, Math.round((reg / cap) * 100)) : 0;
    document.getElementById('modal_p_capacity').textContent = reg + ' / ' + cap + ' TNV (' + pct + '%)';
    document.getElementById('modal_p_progress').style.width = pct + '%';
    
    // Status Badge
    var statusEl = document.getElementById('modal_p_status');
    if (p.is_urgent) {
        statusEl.textContent = 'Tuyển Gấp';
        statusEl.style.background = '#fef2f2';
        statusEl.style.color = '#dc2626';
        statusEl.style.borderColor = '#fecaca';
    } else if (p.status === 'full' || reg >= cap) {
        statusEl.textContent = 'Đã Đủ Quân';
        statusEl.style.background = '#ecfdf5';
        statusEl.style.color = '#059669';
        statusEl.style.borderColor = '#a7f3d0';
    } else if (p.status === 'completed') {
        statusEl.textContent = 'Hoàn Thành';
        statusEl.style.background = '#f1f5f9';
        statusEl.style.color = '#64748b';
        statusEl.style.borderColor = '#cbd5e1';
    } else {
        statusEl.textContent = 'Đang Tuyển';
        statusEl.style.background = '#eff6ff';
        statusEl.style.color = '#2563eb';
        statusEl.style.borderColor = '#bfdbfe';
    }

    // Danh sách Ca trực chi tiết
    var shiftsBody = document.getElementById('modal_p_shifts_body');
    shiftsBody.innerHTML = '';
    var hasShifts = false;
    var shiftFilterSelect = document.getElementById('modal_roster_shift_filter');
    shiftFilterSelect.innerHTML = '<option value="all">-- Tất Cả Ca Trực --</option>';

    if (p.schedule_days && p.schedule_days.length > 0) {
        p.schedule_days.forEach(function(day) {
            if (day.shifts && day.shifts.length > 0) {
                day.shifts.forEach(function(s) {
                    hasShifts = true;
                    var tr = document.createElement('tr');
                    tr.style.borderBottom = '1px solid #f1f5f9';
                    var sCap = parseInt(s.capacity, 10) || 0;
                    var sReg = parseInt(s.registered, 10) || 0;
                    tr.innerHTML = 
                        '<td style="padding: 8px 10px; font-weight: 700; color: #0284c7;">' + (day.day_name || '') + '</td>' +
                        '<td style="padding: 8px 10px; font-weight: 600; color: #0f172a;">' + (s.shift_name || '') + '</td>' +
                        '<td style="padding: 8px 10px; font-size: 12px; color: #475569;">' + (s.time_range || 'Theo ca') + '</td>' +
                        '<td style="padding: 8px 10px; font-weight: 700; text-align: center;">' + sReg + ' / ' + sCap + '</td>' +
                        '<td style="padding: 8px 10px; font-size: 12px; color: #64748b;">' + (s.skill_req || 'Không yêu cầu cụ thể') + '</td>';
                    shiftsBody.appendChild(tr);

                    var opt = document.createElement('option');
                    opt.value = s.shift_name;
                    opt.textContent = (day.day_name ? day.day_name + ' - ' : '') + s.shift_name;
                    shiftFilterSelect.appendChild(opt);
                });
            }
        });
    }

    if (!hasShifts && p.shifts && p.shifts.length > 0) {
        p.shifts.forEach(function(sName, sIdx) {
            hasShifts = true;
            var tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid #f1f5f9';
            tr.innerHTML = 
                '<td style="padding: 8px 10px; color: #64748b;">Ca #' + (sIdx + 1) + '</td>' +
                '<td style="padding: 8px 10px; font-weight: 600;" colspan="2">' + sName + '</td>' +
                '<td style="padding: 8px 10px; text-align: center; color: #64748b;">—</td>' +
                '<td style="padding: 8px 10px; color: #64748b;">Đang cập nhật</td>';
            shiftsBody.appendChild(tr);

            var opt = document.createElement('option');
            opt.value = sName;
            opt.textContent = sName;
            shiftFilterSelect.appendChild(opt);
        });
    }

    if (!hasShifts) {
        shiftsBody.innerHTML = '<tr><td colspan="5" style="padding: 16px; text-align: center; color: #94a3b8;">Chưa có ca trực cụ thể</td></tr>';
    }

    // Kỹ năng & Quyền lợi
    var skillsBox = document.getElementById('modal_p_skills');
    skillsBox.innerHTML = '';
    var skillsArr = p.skills_needed || [];
    if (skillsArr.length > 0) {
        skillsArr.forEach(function(sk) {
            var span = document.createElement('span');
            span.style.cssText = 'display: inline-block; background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 4px; font-size: 11.5px; font-weight: 600; margin: 2px 4px 2px 0; border: 1px solid #bae6fd;';
            span.textContent = sk;
            skillsBox.appendChild(span);
        });
    } else {
        skillsBox.innerHTML = '<span style="color: #94a3b8; font-style: italic;">Không yêu cầu chứng chỉ đặc thù</span>';
    }

    var benefitsBox = document.getElementById('modal_p_benefits');
    benefitsBox.innerHTML = '';
    var benArr = p.benefits || [];
    if (benArr.length > 0) {
        benArr.forEach(function(bn) {
            var span = document.createElement('span');
            span.style.cssText = 'display: inline-block; background: #f0fdf4; color: #15803d; padding: 2px 8px; border-radius: 4px; font-size: 11.5px; font-weight: 600; margin: 2px 4px 2px 0; border: 1px solid #bbf7d0;';
            span.textContent = '✓ ' + bn;
            benefitsBox.appendChild(span);
        });
    } else {
        benefitsBox.innerHTML = '<span style="color: #94a3b8; font-style: italic;">Áo đồng phục, Nước uống, Giấy chứng nhận</span>';
    }

    // CẬP NHẬT DANH SÁCH QUÂN SỐ (ROSTER)
    currentModalRoster = p.roster || [];
    document.getElementById('modal_roster_count_badge').textContent = currentModalRoster.length;
    document.getElementById('modal_roster_search').value = '';
    filterModalRoster();

    // Nút hành động
    document.getElementById('modal_btn_edit').href = '/chinh_sua_du_an.php?id=' + p.id;
    document.getElementById('modal_btn_public').href = '/hoat_dong.php?find_id=' + p.id;

    // Mặc định mở Tab 1 (Thông tin)
    switchModalTab('info');

    // Hiển thị modal
    document.getElementById('project_detail_modal').style.display = 'flex';
}

function filterModalRoster() {
    var search = (document.getElementById('modal_roster_search').value || '').trim().toLowerCase();
    var shiftFilter = document.getElementById('modal_roster_shift_filter').value;
    var tbody = document.getElementById('modal_roster_tbody');
    tbody.innerHTML = '';

    var filtered = currentModalRoster.filter(function(v) {
        if (shiftFilter !== 'all' && (v.shift_name || '').indexOf(shiftFilter) === -1) {
            return false;
        }
        if (search !== '') {
            var matchId = (v.id || '').toLowerCase().indexOf(search) !== -1;
            var matchName = (v.name || '').toLowerCase().indexOf(search) !== -1;
            var matchPhone = (v.phone || '').toLowerCase().indexOf(search) !== -1;
            var matchUnit = (v.unit || '').toLowerCase().indexOf(search) !== -1;
            var matchRole = (v.role || '').toLowerCase().indexOf(search) !== -1;
            if (!matchId && !matchName && !matchPhone && !matchUnit && !matchRole) {
                return false;
            }
        }
        return true;
    });

    document.getElementById('modal_roster_shown_count').textContent = filtered.length;
    document.getElementById('modal_roster_total_count').textContent = currentModalRoster.length;

    if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="padding: 24px; text-align: center; color: #94a3b8;">Không tìm thấy tình nguyện viên nào khớp với điều kiện lọc.</td></tr>';
        return;
    }

    filtered.forEach(function(v, idx) {
        var tr = document.createElement('tr');
        tr.style.borderBottom = '1px solid #f1f5f9';
        tr.style.background = idx % 2 === 0 ? '#ffffff' : '#f8fafc';

        var skillsHtml = '';
        if (v.skills && v.skills.length > 0) {
            v.skills.forEach(function(sk) {
                skillsHtml += '<span style="display: inline-block; background: #f1f5f9; color: #475569; font-size: 11px; padding: 1px 6px; border-radius: 4px; margin: 1px 2px 1px 0; border: 1px solid #e2e8f0;">' + sk + '</span>';
            });
        } else {
            skillsHtml = '<span style="color: #94a3b8; font-size: 11.5px; font-style: italic;">Đa năng</span>';
        }

        var isAbsent = (v.status === 'absent');
        var statusBadge = isAbsent ? 
            '<span style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 2px 7px; border-radius: 6px; font-size: 11px; font-weight: 700;">BÁO VẮNG</span>' :
            '<span style="background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; padding: 2px 7px; border-radius: 6px; font-size: 11px; font-weight: 700;">ĐÃ GHI NHẬN</span>';

        tr.innerHTML = 
            '<td style="padding: 9px 10px; font-weight: 700; color: #64748b; text-align: center;">' + (idx + 1) + '</td>' +
            '<td style="padding: 9px 10px; font-family: monospace; font-size: 12px; font-weight: 700; color: #0284c7;">' + 
                (v.id || 'TNV') + 
                (v.blood_group ? '<span style="display: block; font-size: 10px; color: #dc2626;">Máu: ' + v.blood_group + '</span>' : '') +
            '</td>' +
            '<td style="padding: 9px 10px;">' +
                '<div style="font-weight: 700; color: #0f172a; font-size: 13.5px;">' + (v.name || 'Tình Nguyện Viên') + '</div>' +
                '<div style="font-size: 11.5px; color: #64748b;">' + (v.unit || 'Đội TNV D2L') + '</div>' +
            '</td>' +
            '<td style="padding: 9px 10px; font-size: 12px;">' +
                '<div style="font-weight: 600; color: #0284c7;">📞 ' + (v.phone || '0912.xxx.xxx') + '</div>' +
                '<div style="color: #64748b; font-size: 11px;">' + (v.email || '') + '</div>' +
            '</td>' +
            '<td style="padding: 9px 10px; font-size: 12.5px; font-weight: 600; color: #334155;">' +
                (v.shift_name || 'Ca trực thực địa') +
            '</td>' +
            '<td style="padding: 9px 10px;">' +
                '<div style="font-size: 12px; font-weight: 600; color: #0f172a; margin-bottom: 2px;">' + (v.role || 'TNV Thực Địa') + '</div>' +
                '<div>' + skillsHtml + '</div>' +
            '</td>' +
            '<td style="padding: 9px 10px; text-align: center;">' + statusBadge + '</td>';
        tbody.appendChild(tr);
    });
}

function exportModalRosterCSV() {
    if (!currentModalRoster || currentModalRoster.length === 0) {
        alert('Dự án này hiện chưa có danh sách quân số để xuất file.');
        return;
    }
    var p = adminProjectsData[currentModalProjectId] || {};
    var projTitle = (p.title || 'Du_an').replace(/[^a-zA-Z0-9_\u00C0-\u024F\u1EA0-\u1EF9]/g, '_');
    
    var csv = "\uFEFF"; // UTF-8 BOM để Excel hiển thị đúng tiếng Việt có dấu
    csv += "STT,Mã TNV,Họ và Tên,Số Điện Thoại,Email,Đơn Vị / Trường,Ca Trực,Vai Trò Nhiệm Vụ,Kỹ Năng,Nhóm Máu,Trạng Thái\n";

    currentModalRoster.forEach(function(v, idx) {
        var sk = (v.skills || []).join('; ');
        var st = (v.status === 'absent') ? 'BÁO VẮNG' : 'ĐÃ GHI NHẬN';
        csv += '"' + (idx + 1) + '",' +
               '"' + (v.id || '') + '",' +
               '"' + (v.name || '').replace(/"/g, '""') + '",' +
               '"' + (v.phone || '') + '",' +
               '"' + (v.email || '') + '",' +
               '"' + (v.unit || '').replace(/"/g, '""') + '",' +
               '"' + (v.shift_name || '').replace(/"/g, '""') + '",' +
               '"' + (v.role || '').replace(/"/g, '""') + '",' +
               '"' + sk.replace(/"/g, '""') + '",' +
               '"' + (v.blood_group || '') + '",' +
               '"' + st + '"\n';
    });

    var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    var link = document.createElement("a");
    var url = URL.createObjectURL(blob);
    link.setAttribute("href", url);
    link.setAttribute("download", "Quan_So_" + (p.id ? 'DA_' + p.id : 'D2L') + ".csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function closeProjectDetailModal() {
    document.getElementById('project_detail_modal').style.display = 'none';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeProjectDetailModal();
    }
});
</script>

<!-- ========================================================================= -->
<!-- MODAL XEM CHI TIẾT DỰ ÁN & QUÂN SỐ DÀNH CHO BAN CHỈ HUY ĐIỀU PHỐI -->
<!-- ========================================================================= -->
<div id="project_detail_modal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); z-index: 99999; align-items: center; justify-content: center; padding: 16px; backdrop-filter: blur(2px);" onclick="if(event.target === this) closeProjectDetailModal();">
    <div style="background: #ffffff; border-radius: 14px; max-width: 960px; width: 100%; max-height: 92vh; display: flex; flex-direction: column; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35); overflow: hidden; animation: adminModalFadeIn 0.2s ease-out;">
        <!-- MODAL HEADER -->
        <div style="padding: 16px 24px; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #ffffff; display: flex; justify-content: space-between; align-items: flex-start; gap: 16px;">
            <div>
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                    <span id="modal_p_id" style="background: rgba(255,255,255,0.15); padding: 2px 8px; border-radius: 6px; font-size: 12px; font-weight: 700; font-family: monospace;">#000</span>
                    <span id="modal_p_status" style="border: 1px solid transparent; padding: 2px 8px; border-radius: 6px; font-size: 11.5px; font-weight: 700;">Đang Tuyển</span>
                    <span id="modal_p_cat" style="background: #334155; color: #cbd5e1; padding: 2px 8px; border-radius: 6px; font-size: 11.5px;">Chủ đề</span>
                </div>
                <h3 id="modal_p_title" style="margin: 0; font-size: 17px; font-weight: 700; color: #ffffff; line-height: 1.35;">Tên Dự Án</h3>
            </div>
            <button type="button" onclick="closeProjectDetailModal()" style="background: rgba(255,255,255,0.1); border: none; color: #cbd5e1; width: 32px; height: 32px; border-radius: 8px; font-size: 20px; cursor: pointer; display: flex; align-items: center; justify-content: center; line-height: 1;" title="Đóng">&times;</button>
        </div>

        <!-- MODAL TABS NAVIGATION -->
        <div style="display: flex; gap: 4px; border-bottom: 2px solid #e2e8f0; padding: 0 24px; background: #f8fafc;">
            <button type="button" id="modal_tab_btn_info" onclick="switchModalTab('info')" style="padding: 11px 18px; border: none; background: transparent; font-size: 13.5px; font-weight: 700; color: #059669; border-bottom: 3px solid #059669; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                📋 Thông Tin Dự Án & Ca Trực
            </button>
            <button type="button" id="modal_tab_btn_roster" onclick="switchModalTab('roster')" style="padding: 11px 18px; border: none; background: transparent; font-size: 13.5px; font-weight: 700; color: #64748b; border-bottom: 3px solid transparent; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                👥 Danh Sách Quân Số Tham Gia (<span id="modal_roster_count_badge" style="background: #0284c7; color: #ffffff; padding: 1px 7px; border-radius: 10px; font-size: 11px; font-weight: 800;">0</span>)
            </button>
        </div>

        <!-- TAB 1: THÔNG TIN DỰ ÁN (SCROLLABLE) -->
        <div id="modal_tab_content_info" style="padding: 20px 24px; overflow-y: auto; flex: 1;">
            <!-- GRID THÔNG TIN CỐT LÕI -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 14px; margin-bottom: 18px;">
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px;">
                    <div style="font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">📅 Lịch Trình Hoạt Động</div>
                    <div id="modal_p_date" style="font-size: 13.5px; font-weight: 700; color: #0f172a; margin-bottom: 2px;">—</div>
                    <div id="modal_p_time" style="font-size: 12px; color: #64748b;">—</div>
                </div>

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px;">
                    <div style="font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">📍 Địa Bàn & Điểm Hẹn</div>
                    <div id="modal_p_location" style="font-size: 13px; font-weight: 600; color: #0f172a; margin-bottom: 2px;">—</div>
                    <div style="font-size: 12px; color: #0284c7;">Hẹn: <span id="modal_p_meeting">—</span></div>
                </div>

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px;">
                    <div style="font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">👤 Cán Bộ Phụ Trách</div>
                    <div id="modal_p_leader" style="font-size: 13.5px; font-weight: 700; color: #0f172a;">—</div>
                    <div style="font-size: 11.5px; color: #059669; margin-top: 2px;">Ban Chỉ Huy Trực Tiếp Điều Hành</div>
                </div>

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px;">
                    <div style="font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">👥 Quân Số Chỉ Tiêu</div>
                    <div id="modal_p_capacity" style="font-size: 13.5px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">—</div>
                    <div style="width: 100%; height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden;">
                        <div id="modal_p_progress" style="width: 0%; height: 100%; background: #059669; transition: width 0.3s ease;"></div>
                    </div>
                </div>
            </div>

            <!-- TÓM TẮT MỤC TIÊU -->
            <div style="margin-bottom: 18px;">
                <div style="font-size: 12px; font-weight: 700; color: #334155; text-transform: uppercase; margin-bottom: 6px;">📝 Nội Dung & Mục Tiêu Nhiệm Vụ</div>
                <div id="modal_p_summary" style="font-size: 13.5px; color: #334155; line-height: 1.5; background: #fffbeb; border: 1px solid #fef3c7; padding: 10px 14px; border-radius: 8px;">
                    —
                </div>
            </div>

            <!-- BẢNG CA TRỰC CHI TIẾT -->
            <div style="margin-bottom: 18px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <div style="font-size: 12px; font-weight: 700; color: #334155; text-transform: uppercase;">⏱ Chi Tiết Các Ca Trực Thuộc Hoạt Động</div>
                    <button type="button" onclick="switchModalTab('roster')" style="background: none; border: none; color: #0284c7; font-size: 12px; font-weight: 700; cursor: pointer;">
                        Xem danh sách người trực &rarr;
                    </button>
                </div>
                <div style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                        <thead>
                            <tr style="background: #f1f5f9; color: #334155; text-align: left; font-weight: 700;">
                                <th style="padding: 8px 10px; width: 90px;">Ngày</th>
                                <th style="padding: 8px 10px;">Tên Ca Trực</th>
                                <th style="padding: 8px 10px; width: 130px;">Khung Giờ</th>
                                <th style="padding: 8px 10px; width: 90px; text-align: center;">Quân Số</th>
                                <th style="padding: 8px 10px;">Yêu Cầu Nhiệm Vụ / Kỹ Năng</th>
                            </tr>
                        </thead>
                        <tbody id="modal_p_shifts_body">
                            <!-- Render bằng JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- KỸ NĂNG & QUYỀN LỢI -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px;">
                    <div style="font-size: 12px; font-weight: 700; color: #0369a1; text-transform: uppercase; margin-bottom: 6px;">🎯 Kỹ Năng Yêu Cầu</div>
                    <div id="modal_p_skills"></div>
                </div>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px;">
                    <div style="font-size: 12px; font-weight: 700; color: #15803d; text-transform: uppercase; margin-bottom: 6px;">🎁 Quyền Lợi & Hỗ Trợ</div>
                    <div id="modal_p_benefits"></div>
                </div>
            </div>
        </div>

        <!-- TAB 2: DANH SÁCH QUÂN SỐ THAM GIA HOẠT ĐỘNG (ROSTER) -->
        <div id="modal_tab_content_roster" style="display: none; padding: 18px 24px; overflow-y: auto; flex: 1;">
            <!-- THANH TÌM KIẾM & BỘ LỌC QUÂN SỐ -->
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 14px; background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <div style="flex: 1; min-width: 220px;">
                    <input type="text" id="modal_roster_search" oninput="filterModalRoster()" placeholder="🔍 Tìm theo Tên TNV, Mã số, SĐT, Trường..." style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; background: #ffffff;">
                </div>
                <div style="min-width: 200px;">
                    <select id="modal_roster_shift_filter" onchange="filterModalRoster()" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; background: #ffffff;">
                        <option value="all">-- Tất Cả Ca Trực --</option>
                    </select>
                </div>
                <button type="button" onclick="exportModalRosterCSV()" style="background: #10b981; color: #ffffff; border: none; border-radius: 6px; padding: 8px 14px; font-size: 13px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; box-shadow: 0 1px 3px rgba(16,185,129,0.3);">
                    📥 Xuất Điểm Danh (CSV)
                </button>
            </div>

            <!-- BẢNG DANH SÁCH QUÂN SỐ -->
            <div style="border: 1px solid #e2e8f0; border-radius: 8px; overflow-x: auto; margin-bottom: 10px;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
                    <thead>
                        <tr style="background: #f1f5f9; color: #334155; font-weight: 700; border-bottom: 2px solid #cbd5e1;">
                            <th style="padding: 9px 10px; width: 45px; text-align: center;">STT</th>
                            <th style="padding: 9px 10px; width: 85px;">Mã TNV</th>
                            <th style="padding: 9px 10px; min-width: 170px;">Họ và Tên / Trường</th>
                            <th style="padding: 9px 10px; width: 140px;">Liên Hệ</th>
                            <th style="padding: 9px 10px; min-width: 150px;">Ca Trực Ghi Nhận</th>
                            <th style="padding: 9px 10px; min-width: 160px;">Vai Trò & Chuyên Môn</th>
                            <th style="padding: 9px 10px; width: 110px; text-align: center;">Trạng Thái</th>
                        </tr>
                    </thead>
                    <tbody id="modal_roster_tbody">
                        <!-- Render bằng JS filterModalRoster() -->
                    </tbody>
                </table>
            </div>

            <!-- THÔNG TIN PHỤ DƯỚI BẢNG -->
            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #64748b; padding: 4px 2px;">
                <div>
                    Đang hiển thị <strong><span id="modal_roster_shown_count">0</span></strong> / <strong><span id="modal_roster_total_count">0</span></strong> tình nguyện viên trong quân số
                </div>
                <div style="color: #059669; font-weight: 600;">
                    ✓ Dữ liệu được đồng bộ từ Cơ sở dữ liệu và Cổng đăng ký trực tuyến
                </div>
            </div>
        </div>

        <!-- MODAL FOOTER -->
        <div style="padding: 12px 24px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div style="display: flex; gap: 8px;">
                <a id="modal_btn_edit" href="#" style="background: #0284c7; color: #ffffff; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                    &#9998; Chỉnh Sửa Dự Án Này
                </a>
                <a id="modal_btn_public" href="#" target="_blank" style="background: #ffffff; color: #0f172a; border: 1px solid #cbd5e1; text-decoration: none; padding: 8px 14px; border-radius: 6px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                    🔗 Mở Trang Công Khai &#8599;
                </a>
            </div>
            <button type="button" onclick="closeProjectDetailModal()" style="background: #e2e8f0; color: #334155; border: none; padding: 8px 18px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">
                Đóng
            </button>
        </div>
    </div>
</div>

<style>
@keyframes adminModalFadeIn {
    from { opacity: 0; transform: scale(0.96); }
    to { opacity: 1; transform: scale(1); }
}
</style>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
