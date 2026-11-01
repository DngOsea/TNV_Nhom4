<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/../src/Input.php';
require __DIR__ . '/../src/Auth.php';
require __DIR__ . '/../src/AssignmentEngine.php';
require_once __DIR__ . '/../src/SkillManager.php';
require_once __DIR__ . '/../src/Pagination.php';

// BẮT BUỘC QUYỀN ĐIỀU PHỐI VIÊN (BAN CHỈ HUY)
// Tình nguyện viên và khách sẽ được chuyển hướng về trang Lịch Trực Thành Viên
$currentUser = Auth::requireCoordinator('/lich_truc.php');

$btlData = require __DIR__ . '/../data/btl_dataset.php';
$allSkills = $btlData['skills'];
$rawShifts = $btlData['shifts'];
$allVolunteers = $btlData['volunteers'];

// Khởi tạo trạng thái lịch trực trong Session nếu chưa có
function initDefaultSchedule(array $rawShifts, array $allVolunteers): array {
    $initialResult = AssignmentEngine::assignSmartGreedy($allVolunteers, $rawShifts);
    $schedule = [];

    $leaders = [
        'Trần Hoàng Nam (Chỉ Huy Trưởng)',
        'Lê Nguyễn Phương Thảo (Trưởng Ban Y Tế)',
        'Phạm Minh Quân (Trưởng Ban Hậu Cần)',
        'Nguyễn Hà My (Trưởng Ban Truyền Thông)',
    ];

    $i = 0;
    foreach ($rawShifts as $s) {
        $sId = $s['id'];
        $assigned = $initialResult['assignments'][$sId]['assigned'] ?? [];

        $assignedList = [];
        foreach ($assigned as $v) {
            $assignedList[] = [
                'id' => $v['id'],
                'name' => $v['name'],
                'skills' => $v['skills'] ?? [],
                'status' => 'confirmed', // confirmed, absent, replaced
                'role' => 'TNV Thực Địa',
                'replacement_for' => null,
            ];
        }

        $leaderInfo = $leaders[$i % count($leaders)];
        $leaderParts = explode(' (', rtrim($leaderInfo, ')'));

        $schedule[$sId] = [
            'id' => $sId,
            'shift_code' => $sId,
            'shift_name' => $s['shift_name'],
            'time_slot' => $s['time_slot'],
            'activity_title' => $s['activity_title'],
            'location' => $s['location'],
            'leader' => $leaderParts[0],
            'leader_role' => $leaderParts[1] ?? 'Chỉ Huy Ca Trực',
            'needed_count' => (int)$s['needed_count'],
            'required_skills' => $s['required_skills'] ?? [],
            'assigned_volunteers' => $assignedList,
            'is_confirmed' => true,
        ];
        $i++;
    }
    return $schedule;
}

if (!isset($_SESSION['btl_duty_schedule']) || !is_array($_SESSION['btl_duty_schedule'])) {
    $_SESSION['btl_duty_schedule'] = initDefaultSchedule($rawShifts, $allVolunteers);
}

$dutySchedules = &$_SESSION['btl_duty_schedule'];
$flashMessage = $_SESSION['flash_msg'] ?? null;
unset($_SESSION['flash_msg']);

// =========================================================================
// XỬ LÝ CÁC HÀNH ĐỘNG ĐIỀU PHỐI (POST REQUESTS)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = field($_POST, 'action');

    // 1. TỰ ĐỘNG PHÂN BỔ CA TRỰC THEO THUẬT TOÁN (CHỈ DÀNH CHO BAN CHỈ HUY)
    if ($action === 'auto_assign') {
        if (!Auth::isCoordinator()) {
            $_SESSION['flash_msg'] = [
                'type' => 'danger',
                'title' => 'Từ Chối Quyền Truy Cập',
                'content' => 'Thao tác tự động phân bổ ca trực yêu cầu quyền Người Điều Phối (Ban Chỉ Huy).',
            ];
            header('Location: /phan_cong.php');
            exit;
        }

        $result = AssignmentEngine::assignSmartGreedy($allVolunteers, $rawShifts);
        foreach ($rawShifts as $s) {
            $sId = $s['id'];
            $newAssigned = $result['assignments'][$sId]['assigned'] ?? [];
            $assignedList = [];
            foreach ($newAssigned as $v) {
                $assignedList[] = [
                    'id' => $v['id'],
                    'name' => $v['name'],
                    'skills' => $v['skills'] ?? [],
                    'status' => 'confirmed',
                    'role' => 'TNV Điều Phối Tự Động',
                    'replacement_for' => null,
                ];
            }
            if (isset($dutySchedules[$sId])) {
                $dutySchedules[$sId]['assigned_volunteers'] = $assignedList;
                $dutySchedules[$sId]['is_confirmed'] = false;
            }
        }
        $_SESSION['flash_msg'] = [
            'type' => 'success',
            'title' => 'Đã Hoàn Tất Phân Bổ Tự Động',
            'content' => 'Hệ thống đã tự động sắp xếp nhân sự cho toàn bộ các ca trực theo năng lực chuyên môn và thời gian.',
        ];
        header('Location: /phan_cong.php');
        exit;
    }

    // 2. XÁC NHẬN PHÊ DUYỆT LỊCH TRỰC CHÍNH THỨC
    if ($action === 'confirm_all') {
        if (!Auth::isCoordinator()) {
            $_SESSION['flash_msg'] = [
                'type' => 'danger',
                'title' => 'Từ Chối Quyền Truy Cập',
                'content' => 'Thao tác phê duyệt lịch trực yêu cầu quyền Người Điều Phối (Ban Chỉ Huy).',
            ];
            header('Location: /phan_cong.php');
            exit;
        }

        foreach ($dutySchedules as &$d) {
            $d['is_confirmed'] = true;
        }
        unset($d);
        $_SESSION['flash_msg'] = [
            'type' => 'success',
            'title' => 'Phê Duyệt Lịch Trực Thành Công',
            'content' => 'Lịch phân công nhân sự đã được phê duyệt và lưu trữ trên hệ thống điều hành.',
        ];
        header('Location: /phan_cong.php');
        exit;
    }

    // 3. XỬ LÝ YÊU CẦU BÁO VẮNG
    if ($action === 'report_absent') {
        if (!Auth::isLoggedIn()) {
            $_SESSION['flash_msg'] = [
                'type' => 'warning',
                'title' => 'Yêu Cầu Đăng Nhập',
                'content' => 'Vui lòng đăng nhập để thực hiện báo vắng ca trực.',
            ];
            header('Location: /dang_nhap.php?redirect=' . urlencode('/phan_cong.php'));
            exit;
        }

        $shiftId = field($_POST, 'shift_id');
        $volunteerId = field($_POST, 'volunteer_id');
        $currentUser = Auth::getUser();

        if (Auth::isVolunteer()) {
            $foundVolunteer = null;
            if (isset($dutySchedules[$shiftId])) {
                foreach ($dutySchedules[$shiftId]['assigned_volunteers'] as $chk) {
                    if ($chk['id'] === $volunteerId) {
                        $foundVolunteer = $chk;
                        break;
                    }
                }
            }

            $currentUserName = Auth::lower(trim($currentUser['name'] ?? ''));
            $targetName = Auth::lower(trim($foundVolunteer['name'] ?? ''));
            $isOwner = ($targetName !== '' && $currentUserName === $targetName) || 
                       (($currentUser['id'] ?? '') === $volunteerId);

            if (!$isOwner) {
                $_SESSION['flash_msg'] = [
                    'type' => 'danger',
                    'title' => 'Từ Chối Thao Tác Báo Vắng',
                    'content' => 'Tài khoản Tình Nguyện Viên chỉ có quyền báo vắng cho ca trực của chính bản thân mình.',
                ];
                header('Location: /phan_cong.php#shift-row-' . urlencode($shiftId));
                exit;
            }
        }

        if (isset($dutySchedules[$shiftId])) {
            foreach ($dutySchedules[$shiftId]['assigned_volunteers'] as &$v) {
                if ($v['id'] === $volunteerId) {
                    $v['status'] = 'absent';
                    $_SESSION['flash_msg'] = [
                        'type' => 'warning',
                        'title' => 'Ghi Nhận Nhân Sự Báo Vắng',
                        'content' => 'Tình nguyện viên ' . htmlspecialchars($v['name']) . ' đã thông báo vắng mặt tại ca ' . $shiftId . '. Thông tin đã chuyển tới Ban Chỉ Huy để điều động thay thế.',
                    ];
                    break;
                }
            }
            unset($v);
        }
        header('Location: /phan_cong.php#shift-row-' . urlencode($shiftId));
        exit;
    }

    // 4. THAY THẾ NHÂN SỰ VẮNG MẶT
    if ($action === 'replace_volunteer') {
        if (!Auth::isCoordinator()) {
            $_SESSION['flash_msg'] = [
                'type' => 'danger',
                'title' => 'Từ Chối Quyền Truy Cập',
                'content' => 'Chỉ Người Điều Phối mới có quyền chỉ định nhân sự bổ sung thay thế.',
            ];
            header('Location: /phan_cong.php');
            exit;
        }

        $shiftId = field($_POST, 'shift_id');
        $absentVId = field($_POST, 'absent_volunteer_id');
        $newVId = field($_POST, 'replacement_volunteer_id');

        $targetShift = null;
        foreach ($rawShifts as $s) {
            if ($s['id'] === $shiftId) {
                $targetShift = $s;
                break;
            }
        }

        $newVolunteer = null;
        foreach ($allVolunteers as $v) {
            if ($v['id'] === $newVId) {
                $newVolunteer = $v;
                break;
            }
        }

        if (!$targetShift || !$newVolunteer || !isset($dutySchedules[$shiftId])) {
            $_SESSION['flash_msg'] = [
                'type' => 'danger',
                'title' => 'Lỗi Xử Lý Dữ Liệu',
                'content' => 'Không tìm thấy thông tin ca trực hoặc nhân sự được chọn.',
            ];
            header('Location: /phan_cong.php');
            exit;
        }

        // Tìm các ca khác mà người mới này đã được gán
        $volunteerOtherShifts = [];
        foreach ($dutySchedules as $sItem) {
            if ($sItem['id'] === $shiftId) continue;
            foreach ($sItem['assigned_volunteers'] as $v) {
                if ($v['id'] === $newVId && $v['status'] !== 'absent') {
                    $volunteerOtherShifts[] = $sItem;
                }
            }
        }

        $activeAssigned = array_filter(
            $dutySchedules[$shiftId]['assigned_volunteers'],
            fn($v) => $v['id'] !== $absentVId && $v['status'] !== 'absent'
        );

        // Thẩm định ràng buộc theo chuẩn rubric thuật toán
        $validation = AssignmentEngine::validateReassignment(
            $newVolunteer,
            $targetShift,
            $activeAssigned,
            $volunteerOtherShifts
        );

        if (!$validation['valid']) {
            $_SESSION['flash_msg'] = [
                'type' => 'danger',
                'title' => 'Yêu Cầu Thay Thế Bị Từ Chối',
                'content' => $validation['message'],
            ];
            header('Location: /phan_cong.php#shift-row-' . urlencode($shiftId));
            exit;
        }

        // Hợp lệ -> Thay thế thành công
        $absentName = '';
        foreach ($dutySchedules[$shiftId]['assigned_volunteers'] as $idx => $v) {
            if ($v['id'] === $absentVId) {
                $absentName = $v['name'];
                $dutySchedules[$shiftId]['assigned_volunteers'][$idx] = [
                    'id' => $newVolunteer['id'],
                    'name' => $newVolunteer['name'],
                    'skills' => $newVolunteer['skills'] ?? [],
                    'status' => 'confirmed',
                    'role' => 'TNV Bổ Sung Thay Thế',
                    'replacement_for' => $absentName,
                ];
                break;
            }
        }

        $_SESSION['flash_msg'] = [
            'type' => 'success',
            'title' => 'Điều Phối Nhân Sự Thành Công',
            'content' => 'Đã phân công đồng chí ' . htmlspecialchars($newVolunteer['name']) . ' tiếp nhận nhiệm vụ thay cho ' . htmlspecialchars($absentName) . ' tại ca ' . $shiftId . '.',
        ];
        header('Location: /phan_cong.php#shift-row-' . urlencode($shiftId));
        exit;
    }

    // 5. THIẾT LẬP LẠI TRẠNG THÁI BAN ĐẦU
    if ($action === 'reset_schedule') {
        if (!Auth::isCoordinator()) {
            $_SESSION['flash_msg'] = [
                'type' => 'danger',
                'title' => 'Từ Chối Quyền Truy Cập',
                'content' => 'Chỉ Người Điều Phối mới có quyền thiết lập lại dữ liệu ca trực.',
            ];
            header('Location: /phan_cong.php');
            exit;
        }

        $_SESSION['btl_duty_schedule'] = initDefaultSchedule($rawShifts, $allVolunteers);
        $_SESSION['flash_msg'] = [
            'type' => 'info',
            'title' => 'Đã Thiết Lập Lại Bảng Phân Công',
            'content' => 'Dữ liệu các ca trực đã được đưa về trạng thái phân bổ tiêu chuẩn.',
        ];
        header('Location: /phan_cong.php');
        exit;
    }

    // 6. THÊM TÌNH NGUYỆN VIÊN VÀO CA TRỰC (TÍNH NĂNG MỚI THEO YÊU CẦU BAN CHỈ HUY)
    if ($action === 'add_volunteer_to_shift') {
        if (!Auth::isCoordinator()) {
            $_SESSION['flash_msg'] = [
                'type' => 'danger',
                'title' => 'Từ Chối Quyền Truy Cập',
                'content' => 'Chỉ Người Điều Phối mới có quyền bổ sung nhân sự vào ca trực.',
            ];
            header('Location: /phan_cong.php');
            exit;
        }

        $shiftId = field($_POST, 'shift_id');
        $newVId = field($_POST, 'volunteer_id');
        $role = trim(field($_POST, 'volunteer_role')) ?: 'TNV Thực Địa';

        $targetShift = null;
        foreach ($rawShifts as $s) {
            if ($s['id'] === $shiftId) {
                $targetShift = $s;
                break;
            }
        }

        $newVolunteer = null;
        foreach ($allVolunteers as $v) {
            if ($v['id'] === $newVId) {
                $newVolunteer = $v;
                break;
            }
        }

        if (!$targetShift || !$newVolunteer || !isset($dutySchedules[$shiftId])) {
            $_SESSION['flash_msg'] = [
                'type' => 'danger',
                'title' => 'Lỗi Xử Lý Dữ Liệu',
                'content' => 'Không tìm thấy ca trực hoặc nhân sự được chọn.',
            ];
            header('Location: /phan_cong.php');
            exit;
        }

        // Kiểm tra xem đã có trong ca chưa
        foreach ($dutySchedules[$shiftId]['assigned_volunteers'] as $existing) {
            if ($existing['id'] === $newVId && $existing['status'] !== 'absent') {
                $_SESSION['flash_msg'] = [
                    'type' => 'warning',
                    'title' => 'Nhân Sự Đã Có Mặt',
                    'content' => 'Tình nguyện viên ' . htmlspecialchars($newVolunteer['name']) . ' hiện đã được phân công trong ca trực này.',
                ];
                header('Location: /phan_cong.php#shift-row-' . urlencode($shiftId));
                exit;
            }
        }

        // Kiểm tra trùng lịch các ca khác
        $volunteerOtherShifts = [];
        foreach ($dutySchedules as $sItem) {
            if ($sItem['id'] === $shiftId) continue;
            foreach ($sItem['assigned_volunteers'] as $v) {
                if ($v['id'] === $newVId && $v['status'] !== 'absent') {
                    $volunteerOtherShifts[] = $sItem;
                }
            }
        }

        $activeAssigned = array_filter(
            $dutySchedules[$shiftId]['assigned_volunteers'],
            fn($v) => $v['status'] !== 'absent'
        );

        // Thẩm định nghiêm ngặt theo thuật toán
        $validation = AssignmentEngine::validateReassignment(
            $newVolunteer,
            $targetShift,
            $activeAssigned,
            $volunteerOtherShifts
        );

        if (!$validation['valid']) {
            $_SESSION['flash_msg'] = [
                'type' => 'danger',
                'title' => 'Yêu Cầu Bổ Sung Bị Từ Chối',
                'content' => $validation['message'],
            ];
            header('Location: /phan_cong.php#shift-row-' . urlencode($shiftId));
            exit;
        }

        // Hợp lệ -> Thêm vào ca
        $dutySchedules[$shiftId]['assigned_volunteers'][] = [
            'id' => $newVolunteer['id'],
            'name' => $newVolunteer['name'],
            'skills' => $newVolunteer['skills'] ?? [],
            'status' => 'confirmed',
            'role' => $role,
            'replacement_for' => null,
        ];

        $_SESSION['flash_msg'] = [
            'type' => 'success',
            'title' => 'Bổ Sung Nhân Sự Thành Công',
            'content' => 'Đã thêm đồng chí ' . htmlspecialchars($newVolunteer['name']) . ' vào ca ' . htmlspecialchars($shiftId) . ' với vai trò: ' . htmlspecialchars($role) . '.',
        ];
        header('Location: /phan_cong.php#shift-row-' . urlencode($shiftId));
        exit;
    }

    // 7. SỬA VAI TRÒ NHÂN SỰ TRONG CA
    if ($action === 'update_volunteer_role') {
        if (!Auth::isCoordinator()) {
            $_SESSION['flash_msg'] = [
                'type' => 'danger',
                'title' => 'Từ Chối Quyền',
                'content' => 'Chỉ Người Điều Phối mới có quyền cập nhật vai trò nhân sự.',
            ];
            header('Location: /phan_cong.php');
            exit;
        }
        $shiftId = field($_POST, 'shift_id');
        $vId = field($_POST, 'volunteer_id');
        $newRole = trim(field($_POST, 'new_role'));

        if (isset($dutySchedules[$shiftId])) {
            foreach ($dutySchedules[$shiftId]['assigned_volunteers'] as &$v) {
                if ($v['id'] === $vId) {
                    $v['role'] = $newRole ?: 'TNV Thực Địa';
                    $_SESSION['flash_msg'] = [
                        'type' => 'success',
                        'title' => 'Cập Nhật Vai Trò Thành Công',
                        'content' => 'Đã cập nhật vai trò của đồng chí ' . htmlspecialchars($v['name']) . ' thành: ' . htmlspecialchars($v['role']) . '.',
                    ];
                    break;
                }
            }
            unset($v);
        }
        header('Location: /phan_cong.php#shift-row-' . urlencode($shiftId));
        exit;
    }

    // 8. RÚT / XÓA NHÂN SỰ KHỎI CA
    if ($action === 'remove_volunteer_from_shift') {
        if (!Auth::isCoordinator()) {
            $_SESSION['flash_msg'] = [
                'type' => 'danger',
                'title' => 'Từ Chối Quyền',
                'content' => 'Chỉ Người Điều Phối mới có quyền rút nhân sự khỏi ca.',
            ];
            header('Location: /phan_cong.php');
            exit;
        }
        $shiftId = field($_POST, 'shift_id');
        $vId = field($_POST, 'volunteer_id');

        if (isset($dutySchedules[$shiftId])) {
            $removedName = '';
            $newList = [];
            foreach ($dutySchedules[$shiftId]['assigned_volunteers'] as $v) {
                if ($v['id'] === $vId) {
                    $removedName = $v['name'];
                } else {
                    $newList[] = $v;
                }
            }
            $dutySchedules[$shiftId]['assigned_volunteers'] = $newList;
            $_SESSION['flash_msg'] = [
                'type' => 'info',
                'title' => 'Đã Rút Tên Nhân Sự Khỏi Ca',
                'content' => 'Đã rút tên đồng chí ' . htmlspecialchars($removedName) . ' khỏi ca ' . htmlspecialchars($shiftId) . '.',
            ];
        }
        header('Location: /phan_cong.php#shift-row-' . urlencode($shiftId));
        exit;
    }
}

// =========================================================================
// LỌC CA TRỰC THEO THỜI GIAN & TRẠNG THÁI QUÂN SỐ
// =========================================================================
$selectedFilter = field($_GET, 'shift_type');
$selectedStatus = field($_GET, 'status'); // all, need, absent, full

$filteredDuties = array_filter($dutySchedules, function($d) use ($selectedFilter, $selectedStatus) {
    if ($selectedFilter !== '' && stripos($d['shift_name'], $selectedFilter) === false) {
        return false;
    }
    $needed = (int)$d['needed_count'];
    $activeAssignedCount = count(array_filter($d['assigned_volunteers'], fn($v) => $v['status'] !== 'absent'));
    $hasAbsent = count(array_filter($d['assigned_volunteers'], fn($v) => $v['status'] === 'absent')) > 0;

    if ($selectedStatus === 'need' && $activeAssignedCount >= $needed) {
        return false;
    }
    if ($selectedStatus === 'absent' && !$hasAbsent) {
        return false;
    }
    if ($selectedStatus === 'full' && $activeAssignedCount < $needed) {
        return false;
    }
    return true;
});

// Thống kê tổng hợp toàn đội
$totalShifts = count($dutySchedules);
$totalNeeded = array_sum(array_column($dutySchedules, 'needed_count'));
$totalConfirmed = 0;
$totalAbsent = 0;

foreach ($dutySchedules as $d) {
    foreach ($d['assigned_volunteers'] as $v) {
        if ($v['status'] === 'absent') {
            $totalAbsent++;
        } else {
            $totalConfirmed++;
        }
    }
}
$fillRate = $totalNeeded > 0 ? round(($totalConfirmed / $totalNeeded) * 100, 1) : 0.0;

// Phân trang danh sách ca trực (5 ca mỗi trang cho bảng siêu gọn gàng)
$page = max(1, (int)field($_GET, 'page', '1'));
$paginationData = Pagination::paginate($filteredDuties, $page, 5, true);
$paginatedDuties = $paginationData['items'];

require __DIR__ . '/../templates/header.php';
?>

<div class="page-heading-pro">
    <div class="heading-container">
        <span class="sub-badge">ĐIỀU PHỐI LỰC LƯỢNG THỰC ĐỊA</span>
        <h1>Bảng Phân Công & Điều Phối Tình Nguyện Viên</h1>
        <p>Hệ thống giám sát điều phối nhân sự, tự động phân bổ ca trực theo chuyên môn và hỗ trợ xử lý thay thế nhân sự kịp thời.</p>
    </div>
</div>

<div class="page-content-wrapper">
    <!-- KHUNG CHỈ BÁO VAI TRÒ BAN CHỈ HUY -->
    <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 12px; padding: 14px 20px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div>
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 2px;">
                <span style="font-size: 0.72rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; padding: 2px 8px; border-radius: 4px; background: #dc2626; color: #ffffff;">
                    KHU VỰC ĐIỀU HÀNH BAN CHỈ HUY
                </span>
                <strong style="color: #065f46; font-size: 0.95rem;">
                    <?= htmlspecialchars($currentUser['name'] ?? '') ?> (<?= htmlspecialchars($currentUser['role'] ?? 'Cán Bộ Chỉ Huy') ?>)
                </strong>
            </div>
            <p style="margin: 0; font-size: 0.84rem; color: #047857;">
                Đồng chí đang tác nghiệp tại Bảng Điều Phối Lực Lượng. Bấm vào biểu tượng mắt để xem chi tiết và thêm, sửa, xóa, thay thế nhân sự ca trực.
            </p>
        </div>
        <div>
            <a href="/lich_truc.php" class="btn-action-pro" style="background: #ffffff; border: 1px solid #cbd5e1; color: #334155; text-decoration: none; padding: 7px 14px; border-radius: 6px; font-size: 0.82rem; font-weight: 600;">
                Xem Giao Diện Lịch Trực TNV &rarr;
            </a>
        </div>
    </div>

    <!-- THÔNG BÁO TƯƠNG TÁC -->
    <?php if ($flashMessage): ?>
        <div class="alert alert-<?= htmlspecialchars($flashMessage['type']) ?>" role="alert" style="margin-bottom: 20px; border-radius: 10px; padding: 14px 18px;">
            <strong><?= htmlspecialchars($flashMessage['title']) ?>:</strong>
            <span style="margin-left: 6px;"><?= htmlspecialchars($flashMessage['content']) ?></span>
        </div>
    <?php endif; ?>

    <!-- CẢNH BÁO YÊU CẦU BÁO VẮNG CHỜ DUYỆT TỪ TNV -->
    <?php 
    require_once __DIR__ . '/../src/Database.php';
    $pendingAbsenceCount = Database::getInstance()->getPendingAbsenceCount();
    if ($pendingAbsenceCount > 0): ?>
        <div style="background: #fffbeb; border: 2px solid #fde68a; border-radius: 12px; padding: 14px 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; box-shadow: 0 4px 12px rgba(217,119,6,0.08);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span style="font-size: 24px;">🔔</span>
                <div>
                    <strong style="color: #b45309; font-size: 0.95rem;">Đơn Báo Vắng Chờ Ban Chỉ Huy Duyệt:</strong>
                    <span style="color: #92400e; font-size: 0.88rem; margin-left: 4px;">
                        Có <strong><?= $pendingAbsenceCount ?></strong> tình nguyện viên đã gửi đơn xin vắng mặt hợp lệ (&ge; 48 giờ) cần thẩm định và điều động nhân sự thay thế.
                    </span>
                </div>
            </div>
            <a href="/phe_duyet_vang.php?status=pending" style="background: #d97706; color: #ffffff; text-decoration: none; padding: 8px 18px; border-radius: 8px; font-weight: 700; font-size: 0.84rem; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 6px rgba(217,119,6,0.3);">
                Phê Duyệt & Điều Động Ngay &rarr;
            </a>
        </div>
    <?php endif; ?>

    <!-- THANH TÁC VỤ ĐIỀU PHỐI NHÂN SỰ -->
    <div class="coordinator-action-toolbar" style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px 20px;">
        <div>
            <h3 style="margin: 0 0 4px; font-size: 1.1rem; color: #1e293b;">Bảng Điều Hành Nhân Sự Trực Tuyến</h3>
            <span style="font-size: 0.84rem; color: #64748b;">
                Hệ thống hỗ trợ tự động gán nhân sự thông minh theo thuật toán Greedy kết hợp kiểm tra 5 phép thử ràng buộc
            </span>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
            <a href="/phe_duyet_vang.php" class="btn-action-pro" style="background: #fffbeb; border: 1.5px solid #fde68a; color: #b45309; text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                <span>&#9203; Duyệt Báo Vắng</span>
                <?php if ($pendingAbsenceCount > 0): ?>
                    <span style="background: #dc2626; color: #ffffff; padding: 1px 7px; border-radius: 10px; font-size: 11px; font-weight: 800;"><?= $pendingAbsenceCount ?></span>
                <?php endif; ?>
            </a>
            <?php if (Auth::isCoordinator()): ?>
                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="auto_assign">
                    <button type="submit" class="btn-action-pro btn-auto" onclick="return confirm('Chạy thuật toán Smart Greedy để tự động phân bổ quân số?')">
                        Tự Động Phân Ca
                    </button>
                </form>

                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="confirm_all">
                    <button type="submit" class="btn-action-pro btn-confirm">
                        Phê Duyệt Lịch Trực
                    </button>
                </form>

                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="reset_schedule">
                    <button type="submit" class="btn-action-pro btn-reset" onclick="return confirm('Bạn có chắc muốn thiết lập lại bảng phân công?')">
                        Thiết Lập Lại
                    </button>
                </form>
            <?php endif; ?>

            <a href="/doi_soat_thuat_toan.php" class="btn-action-pro btn-benchmark">
                Đối Soát Hiệu Suất
            </a>
        </div>
    </div>

    <!-- KHUNG TỔNG QUAN QUÂN SỐ -->
    <div class="shift-stats-overview">
        <div class="shift-stat-box">
            <strong><?= $totalShifts ?> Ca Trực</strong>
            <span>Đang điều phối trên hệ thống</span>
        </div>
        <div class="shift-stat-box">
            <strong><?= $totalNeeded ?> Chỉ Tiêu</strong>
            <span>Tổng số suất cần tiếp nhận</span>
        </div>
        <div class="shift-stat-box">
            <strong class="text-success"><?= $totalConfirmed ?> Đã Nhận Ca</strong>
            <span>Tỷ lệ lấp đầy: <?= $fillRate ?>%</span>
        </div>
        <div class="shift-stat-box">
            <strong class="<?= $totalAbsent > 0 ? 'text-danger' : '' ?>"><?= $totalAbsent ?> Báo Vắng</strong>
            <span>Đang cần thay thế người</span>
        </div>
    </div>

    <!-- BỘ LỌC VÀ ĐIỀU KHIỂN CHẾ ĐỘ XEM TRỰC QUAN -->
    <div class="table-control-bar" style="margin-bottom: 20px;">
        <div style="display: flex; flex-direction: column; gap: 12px; width: 100%;">
            <!-- Dòng 1: Bộ lọc thời gian ca trực -->
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <a href="/phan_cong.php<?= $selectedStatus ? '?status=' . urlencode($selectedStatus) : '' ?>" class="tab-duty <?= $selectedFilter === '' ? 'active' : '' ?>">Tất Cả (<?= $totalShifts ?>)</a>
                    <a href="/phan_cong.php?shift_type=Sáng<?= $selectedStatus ? '&status=' . urlencode($selectedStatus) : '' ?>" class="tab-duty <?= $selectedFilter === 'Sáng' ? 'active' : '' ?>">Ca Sáng</a>
                    <a href="/phan_cong.php?shift_type=Chiều<?= $selectedStatus ? '&status=' . urlencode($selectedStatus) : '' ?>" class="tab-duty <?= $selectedFilter === 'Chiều' ? 'active' : '' ?>">Ca Chiều</a>
                    <a href="/phan_cong.php?shift_type=Tối<?= $selectedStatus ? '&status=' . urlencode($selectedStatus) : '' ?>" class="tab-duty <?= $selectedFilter === 'Tối' ? 'active' : '' ?>">Ca Tối</a>
                </div>

                <!-- Ô tìm kiếm tức thời & Nút chuyển đổi View -->
                <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                    <div class="table-search-box">
                        <input type="text" id="dispatch_search_input" class="table-search-input" placeholder="Tìm kiếm theo mã ca, tên hoạt động, địa bàn, TNV..." autocomplete="off">
                    </div>

                    <div class="view-toggle-group">
                        <button type="button" class="btn-view-toggle active" id="btn_toggle_dispatch_table" title="Xem dạng bảng điều phối">
                            Bảng (Table)
                        </button>
                        <button type="button" class="btn-view-toggle" id="btn_toggle_dispatch_list" title="Xem dạng danh sách thẻ gọn">
                            Danh Sách (List)
                        </button>
                    </div>
                </div>
            </div>

            <!-- Dòng 2: Bộ lọc nhanh trạng thái quân số (Mới theo phản hồi) -->
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; padding-top: 8px; border-top: 1px dashed #e2e8f0;">
                <span style="font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Lọc theo quân số:</span>
                <a href="/phan_cong.php<?= $selectedFilter ? '?shift_type=' . urlencode($selectedFilter) : '' ?>" class="status-pill-filter <?= empty($selectedStatus) ? 'active' : '' ?>">
                    Tất cả quân số
                </a>
                <a href="/phan_cong.php?status=need<?= $selectedFilter ? '&shift_type=' . urlencode($selectedFilter) : '' ?>" class="status-pill-filter <?= $selectedStatus === 'need' ? 'active' : '' ?>">
                    Ca còn thiếu người
                </a>
                <a href="/phan_cong.php?status=absent<?= $selectedFilter ? '&shift_type=' . urlencode($selectedFilter) : '' ?>" class="status-pill-filter pill-warn <?= $selectedStatus === 'absent' ? 'active' : '' ?>">
                    Ca có người báo vắng (<?= $totalAbsent ?>)
                </a>
                <a href="/phan_cong.php?status=full<?= $selectedFilter ? '&shift_type=' . urlencode($selectedFilter) : '' ?>" class="status-pill-filter <?= $selectedStatus === 'full' ? 'active' : '' ?>">
                    Ca đã đủ 100% quân số
                </a>
            </div>
        </div>
    </div>

    <!-- 1. GIAO DIỆN DẠNG BẢNG ĐIỀU PHỐI (TABLE VIEW - MẶC ĐỊNH) -->
    <div id="dispatch_table_container" class="duty-table-container">
        <div class="duty-table-responsive">
            <table class="duty-table-modern" id="main_dispatch_table">
                <thead>
                    <tr>
                        <th style="width: 22%;">Mã & Hoạt Động</th>
                        <th style="width: 14%;">Khung Giờ</th>
                        <th style="width: 15%;">Kỹ Năng Yêu Cầu</th>
                        <th style="width: 14%;">Quân Số</th>
                        <th style="width: 35%;">Lực Lượng & Thao Tác Điều Động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($paginatedDuties)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 36px; color: #64748b;">
                                Không có ca trực nào phù hợp với bộ lọc hiện tại.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($paginatedDuties as $duty): 
                            $sId = $duty['id'];
                            $needed = (int)$duty['needed_count'];
                            $activeAssignedCount = count(array_filter($duty['assigned_volunteers'], fn($v) => $v['status'] !== 'absent'));
                            $isFull = $activeAssignedCount >= $needed;
                            $percent = min(100, (int)round(($activeAssignedCount / max(1, $needed)) * 100));
                            $hasAbsent = count(array_filter($duty['assigned_volunteers'], fn($v) => $v['status'] === 'absent')) > 0;
                            $dutyJson = htmlspecialchars(json_encode($duty, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                        ?>
                            <tr class="dispatch-data-row <?= $hasAbsent ? 'row-has-absent' : '' ?>" id="shift-row-<?= htmlspecialchars($sId) ?>"
                                data-search="<?= htmlspecialchars(strtolower($duty['shift_code'] . ' ' . $duty['activity_title'] . ' ' . $duty['location'] . ' ' . $duty['leader'] . ' ' . implode(' ', array_column($duty['assigned_volunteers'], 'name')))) ?>">
                                <td>
                                    <span class="t-shift-badge"><?= htmlspecialchars($duty['shift_code']) ?></span>
                                    <div class="t-act-title"><?= htmlspecialchars($duty['activity_title']) ?></div>
                                    <span class="t-act-location"><?= htmlspecialchars($duty['location']) ?></span>
                                    <span style="font-size: 0.76rem; color: #475569; display: block; margin-top: 3px;">
                                        Chỉ huy: <strong><?= htmlspecialchars($duty['leader']) ?></strong>
                                    </span>
                                </td>
                                <td>
                                    <span class="t-time-pill"><?= htmlspecialchars($duty['shift_name']) ?></span>
                                </td>
                                <td>
                                    <div class="skill-req-pills" style="gap: 4px;">
                                        <?php foreach ($duty['required_skills'] as $sk): ?>
                                            <span class="req-skill-pill" style="font-size: 0.72rem; padding: 2px 7px;">
                                                <?= htmlspecialchars($allSkills[$sk]['name'] ?? $sk) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="t-capacity-box">
                                        <div class="t-capacity-label">
                                            <span><?= $activeAssignedCount ?> / <?= $needed ?></span>
                                            <span class="t-status-tag <?= $isFull ? 'tag-full' : 'tag-need' ?>">
                                                <?= $isFull ? 'Đủ' : 'Thiếu ' . ($needed - $activeAssignedCount) ?>
                                            </span>
                                        </div>
                                        <div class="t-capacity-progress">
                                            <div class="t-progress-bar <?= $isFull ? 't-progress-full' : 't-progress-warning' ?>" style="width: <?= $percent ?>%;"></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <!-- CỘT LỰC LƯỢNG GỌN GÀNG VỚI NÚT HÌNH MẮT (THEO ĐÚNG YÊU CẦU NGƯỜI DÙNG) -->
                                    <div class="roster-compact-summary">
                                        <div class="avatar-preview-stack">
                                            <?php 
                                            $confirmedList = array_filter($duty['assigned_volunteers'], fn($v) => $v['status'] !== 'absent');
                                            $previewVolunteers = array_slice($confirmedList, 0, 3);
                                            foreach ($previewVolunteers as $pv): ?>
                                                <span class="mini-avatar" title="<?= htmlspecialchars($pv['name']) ?> (<?= htmlspecialchars($pv['role'] ?? 'TNV') ?>)">
                                                    <?= mb_substr($pv['name'], 0, 1) ?>
                                                </span>
                                            <?php endforeach; ?>
                                            <?php if (count($confirmedList) > 3): ?>
                                                <span class="mini-avatar mini-avatar-more">+<?= count($confirmedList) - 3 ?></span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="roster-badge-group">
                                            <span class="roster-count-label"><strong><?= count($confirmedList) ?>/<?= $needed ?></strong> TNV</span>
                                            <?php if ($hasAbsent): 
                                                $absentCount = count(array_filter($duty['assigned_volunteers'], fn($v) => $v['status'] === 'absent'));
                                            ?>
                                                <span class="badge-absent-alert" title="Có <?= $absentCount ?> nhân sự báo vắng!">
                                                    &#9888; <?= $absentCount ?> vắng
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <button type="button" class="btn-eye-roster" onclick='openShiftRosterModal(<?= $dutyJson ?>)' title="Bấm vào để xem danh sách chi tiết và thêm, sửa, xóa, thay thế nhân sự">
                                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                            <span>Quân Số (<?= count($duty['assigned_volunteers']) ?>)</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 2. GIAO DIỆN DẠNG DANH SÁCH THẺ THU GỌN (COMPACT LIST VIEW) -->
    <div id="dispatch_list_container" class="duty-compact-list" style="display: none;">
        <?php foreach ($paginatedDuties as $duty): 
            $sId = $duty['id'];
            $needed = (int)$duty['needed_count'];
            $activeAssignedCount = count(array_filter($duty['assigned_volunteers'], fn($v) => $v['status'] !== 'absent'));
            $isFull = $activeAssignedCount >= $needed;
            $hasAbsent = count(array_filter($duty['assigned_volunteers'], fn($v) => $v['status'] === 'absent')) > 0;
            $dutyJson = htmlspecialchars(json_encode($duty, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
        ?>
            <div class="compact-shift-card <?= $hasAbsent ? 'card-has-absent' : '' ?>" id="shift-card-<?= htmlspecialchars($sId) ?>"
                 data-search="<?= htmlspecialchars(strtolower($duty['shift_code'] . ' ' . $duty['activity_title'] . ' ' . $duty['location'] . ' ' . $duty['leader'] . ' ' . implode(' ', array_column($duty['assigned_volunteers'], 'name')))) ?>">
                <div class="compact-main-info">
                    <span class="t-shift-badge"><?= htmlspecialchars($duty['shift_code']) ?></span>
                    <h4 class="t-act-title" style="margin: 4px 0;"><?= htmlspecialchars($duty['activity_title']) ?></h4>
                    <span class="t-act-location"><?= htmlspecialchars($duty['location']) ?></span>
                    <div style="margin-top: 6px;">
                        <span class="t-time-pill"><?= htmlspecialchars($duty['shift_name']) ?></span>
                    </div>
                </div>

                <div class="compact-meta-info">
                    <div><strong>Chỉ huy:</strong> <?= htmlspecialchars($duty['leader']) ?></div>
                    <div><strong>Quân số:</strong> <span class="t-status-tag <?= $isFull ? 'tag-full' : 'tag-need' ?>"><?= $activeAssignedCount ?>/<?= $needed ?> TNV</span></div>
                    <div class="skill-req-pills" style="margin-top: 4px;">
                        <?php foreach ($duty['required_skills'] as $sk): ?>
                            <span class="req-skill-pill" style="font-size: 0.7rem; padding: 2px 6px;">
                                <?= htmlspecialchars($allSkills[$sk]['name'] ?? $sk) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="compact-volunteers-info" style="flex: 2;">
                    <div style="font-size: 0.78rem; font-weight: 700; color: #475569; margin-bottom: 6px;">Lực lượng phân công & Thao tác điều động:</div>
                    <div class="roster-compact-summary">
                        <div class="avatar-preview-stack">
                            <?php 
                            $confirmedList = array_filter($duty['assigned_volunteers'], fn($v) => $v['status'] !== 'absent');
                            $previewVolunteers = array_slice($confirmedList, 0, 3);
                            foreach ($previewVolunteers as $pv): ?>
                                <span class="mini-avatar" title="<?= htmlspecialchars($pv['name']) ?>">
                                    <?= mb_substr($pv['name'], 0, 1) ?>
                                </span>
                            <?php endforeach; ?>
                            <?php if (count($confirmedList) > 3): ?>
                                <span class="mini-avatar mini-avatar-more">+<?= count($confirmedList) - 3 ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="roster-badge-group">
                            <span class="roster-count-label"><strong><?= count($confirmedList) ?>/<?= $needed ?></strong> TNV</span>
                            <?php if ($hasAbsent): ?>
                                <span class="badge-absent-alert">&#9888; Có báo vắng</span>
                            <?php endif; ?>
                        </div>

                        <button type="button" class="btn-eye-roster" onclick='openShiftRosterModal(<?= $dutyJson ?>)' title="Xem chi tiết lực lượng ca này">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <span>Xem Lực Lượng (<?= count($duty['assigned_volunteers']) ?>)</span>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- THANH PHÂN TRANG DANH SÁCH CA TRỰC -->
    <?= Pagination::render(
        $paginationData['page'], 
        $paginationData['total_pages'], 
        $paginationData['total'], 
        $paginationData['per_page'], 
        '/phan_cong.php', 
        ['shift_type' => $selectedFilter, 'status' => $selectedStatus]
    ) ?>

    <!-- THÔNG BÁO TÌM KIẾM KHÔNG THẤY -->
    <div id="no_dispatch_result" style="display: none; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; padding: 36px; text-align: center; margin-bottom: 24px;">
        <p style="color: #64748b; font-size: 0.95rem; margin: 0;">Không tìm thấy ca trực nào phù hợp với từ khóa tìm kiếm.</p>
    </div>

    <!-- JAVASCRIPT ĐIỀU KHIỂN CHẾ ĐỘ XEM VÀ TÌM KIẾM TỨC THỜI -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var btnTable = document.getElementById('btn_toggle_dispatch_table');
        var btnList = document.getElementById('btn_toggle_dispatch_list');
        var viewTable = document.getElementById('dispatch_table_container');
        var viewList = document.getElementById('dispatch_list_container');
        var searchInput = document.getElementById('dispatch_search_input');
        var noResult = document.getElementById('no_dispatch_result');

        // 1. Chuyển đổi View Mode
        if (btnTable && btnList && viewTable && viewList) {
            btnTable.addEventListener('click', function() {
                btnTable.classList.add('active');
                btnList.classList.remove('active');
                viewTable.style.display = '';
                viewList.style.display = 'none';
                try { localStorage.setItem('d2l_dispatch_view', 'table'); } catch(e){}
            });

            btnList.addEventListener('click', function() {
                btnList.classList.add('active');
                btnTable.classList.remove('active');
                viewTable.style.display = 'none';
                viewList.style.display = 'flex';
                try { localStorage.setItem('d2l_dispatch_view', 'list'); } catch(e){}
            });

            try {
                var savedView = localStorage.getItem('d2l_dispatch_view');
                if (savedView === 'list') {
                    btnList.click();
                }
            } catch(e){}
        }

        // 2. Tìm kiếm tức thời (Real-time Live Search)
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                var query = this.value.trim().toLowerCase();
                var rows = document.querySelectorAll('.dispatch-data-row');
                var cards = document.querySelectorAll('.compact-shift-card');
                var visibleCount = 0;

                rows.forEach(function(row) {
                    var searchData = row.getAttribute('data-search') || '';
                    if (query === '' || searchData.indexOf(query) !== -1) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                cards.forEach(function(card) {
                    var searchData = card.getAttribute('data-search') || '';
                    if (query === '' || searchData.indexOf(query) !== -1) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });

                if (noResult) {
                    noResult.style.display = (visibleCount === 0 && query !== '') ? 'block' : 'none';
                }
            });
        }
    });
    </script>
</div>

<?php if (Auth::isCoordinator()): ?>
<!-- =========================================================================
     MODAL POPUP 1: QUẢN LÝ LỰC LƯỢNG CA TRỰC CHI TIẾT (THEO HÌNH CON MẮT)
     Bao gồm: Tìm kiếm, Thêm, Sửa vai trò, Báo vắng, Thay thế, Xóa người, Xuất CSV
     ========================================================================= -->
<div id="shift_roster_modal" class="modal-overlay" style="display: none;">
    <div class="modal-content-card modal-roster-dialog">
        <div class="modal-header">
            <div>
                <span class="modal-category-badge" style="background:#e0f2fe; color:#0369a1;">ĐIỀU HÀNH QUÂN SỐ CA TRỰC</span>
                <h3 id="roster_modal_shift_title" style="margin: 4px 0 0; font-size: 1.28rem; color:#0f172a;">Quản Lý Lực Lượng Ca</h3>
            </div>
            <button type="button" class="btn-modal-close" onclick="closeShiftRosterModal()">&times;</button>
        </div>

        <div class="modal-body" style="padding: 20px 24px; max-height: 75vh; overflow-y: auto;">
            <!-- Thông tin tóm tắt ca trực -->
            <div class="roster-modal-meta-grid">
                <div class="roster-meta-item">
                    <span class="roster-meta-label">Mã Ca Trực:</span>
                    <strong id="roster_modal_shift_code" class="roster-meta-val" style="color:#0284c7;">—</strong>
                </div>
                <div class="roster-meta-item">
                    <span class="roster-meta-label">Hoạt Động / Chiến Dịch:</span>
                    <strong id="roster_modal_act_title" class="roster-meta-val">—</strong>
                </div>
                <div class="roster-meta-item">
                    <span class="roster-meta-label">Địa Bàn Thực Địa:</span>
                    <span id="roster_modal_location" class="roster-meta-val">—</span>
                </div>
                <div class="roster-meta-item">
                    <span class="roster-meta-label">Chỉ Huy Ca Trực:</span>
                    <strong id="roster_modal_leader" class="roster-meta-val">—</strong>
                </div>
                <div class="roster-meta-item">
                    <span class="roster-meta-label">Quân Số Tiếp Nhận:</span>
                    <span id="roster_modal_capacity_text" class="roster-meta-val">—</span>
                </div>
            </div>

            <!-- Thanh công cụ điều hành (Toolbar) -->
            <div class="roster-toolbar">
                <div class="roster-search-wrap">
                    <svg class="roster-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" id="roster_quick_search_input" class="roster-search-input" placeholder="Lọc nhanh TNV theo tên, mã số, vai trò, chuyên môn...">
                </div>

                <div class="roster-actions-wrap">
                    <button type="button" class="btn-roster-action btn-add-vol" id="btn_toggle_add_volunteer" onclick="toggleAddVolunteerForm()">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        + Thêm TNV Vào Ca
                    </button>

                    <button type="button" class="btn-roster-action btn-export-csv" onclick="exportRosterToCSV()" title="Tải danh sách điểm danh dạng bảng CSV">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        Xuất Danh Sách Điểm Danh
                    </button>
                </div>
            </div>

            <!-- Khung Form Thêm TNV mới vào ca (Ẩn/Hiện) -->
            <div id="add_volunteer_form_box" class="add-volunteer-box" style="display: none;">
                <form method="POST" action="/phan_cong.php" id="form_add_volunteer_to_shift">
                    <input type="hidden" name="action" value="add_volunteer_to_shift">
                    <input type="hidden" name="shift_id" id="add_form_shift_id" value="">

                    <div style="font-weight: 700; color: #065f46; font-size: 0.92rem; margin-bottom: 10px;">
                        Bổ Sung Tình Nguyện Viên Vào Ca Này (Thẩm định không trùng lịch & đáp ứng kỹ năng):
                    </div>

                    <div class="add-vol-grid">
                        <div class="add-vol-col">
                            <label for="add_vol_select">Chọn Tình Nguyện Viên: <span class="req">*</span></label>
                            <select id="add_vol_select" name="volunteer_id" required>
                                <option value="">-- Lựa chọn từ toàn bộ lực lượng --</option>
                                <?php foreach ($allVolunteers as $av): 
                                    $skStr = implode(', ', $av['skills'] ?? []);
                                ?>
                                    <option value="<?= htmlspecialchars($av['id']) ?>">
                                        <?= htmlspecialchars($av['name']) ?> [<?= htmlspecialchars($av['id']) ?>] (<?= htmlspecialchars($skStr) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="add-vol-col">
                            <label for="add_vol_role">Vai Trò Nhiệm Vụ:</label>
                            <select id="add_vol_role" name="volunteer_role">
                                <option value="TNV Thực Địa">TNV Thực Địa</option>
                                <option value="Đội Trưởng Nhóm">Đội Trưởng Nhóm</option>
                                <option value="Phụ Trách Y Tế Sơ Cứu">Phụ Trách Y Tế Sơ Cứu</option>
                                <option value="Hậu Cần & Bếp Ăn">Hậu Cần & Bếp Ăn</option>
                                <option value="Điều Tiết Giao Thông">Điều Tiết Giao Thông</option>
                                <option value="Truyền Thông & Ghi Hình">Truyền Thông & Ghi Hình</option>
                            </select>
                        </div>

                        <div class="add-vol-col" style="display: flex; gap: 8px;">
                            <button type="submit" class="btn-action-pro" style="background:#059669; color:#fff; border:none; padding:9px 16px; font-weight:700; border-radius:6px; cursor:pointer;">
                                Lưu Thêm
                            </button>
                            <button type="button" class="btn-cancel" onclick="toggleAddVolunteerForm(false)" style="padding:9px 12px; border-radius:6px;">
                                Hủy
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Khung Form Sửa Vai Trò (Ẩn/Hiện động) -->
            <div id="edit_role_form_box" class="add-volunteer-box" style="display: none; background: #eff6ff; border-color: #bfdbfe;">
                <form method="POST" action="/phan_cong.php" id="form_update_role">
                    <input type="hidden" name="action" value="update_volunteer_role">
                    <input type="hidden" name="shift_id" id="edit_role_shift_id" value="">
                    <input type="hidden" name="volunteer_id" id="edit_role_volunteer_id" value="">

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-weight: 700; color: #1e40af; font-size: 0.92rem;">
                            Cập Nhật Vai Trò Cho: <strong id="edit_role_vol_name" style="color: #1e3a8a;">...</strong>
                        </span>
                        <button type="button" onclick="closeEditRoleForm()" style="background:none; border:none; color:#64748b; cursor:pointer; font-size:1.2rem;">&times;</button>
                    </div>

                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 200px;">
                            <select id="edit_role_select" name="new_role" style="width: 100%; padding: 8px 10px; font-size: 0.88rem; border: 1px solid #93c5fd; border-radius: 6px; background:#fff;">
                                <option value="TNV Thực Địa">TNV Thực Địa</option>
                                <option value="Đội Trưởng Nhóm">Đội Trưởng Nhóm</option>
                                <option value="Phụ Trách Y Tế Sơ Cứu">Phụ Trách Y Tế Sơ Cứu</option>
                                <option value="Hậu Cần & Bếp Ăn">Hậu Cần & Bếp Ăn</option>
                                <option value="Điều Tiết Giao Thông">Điều Tiết Giao Thông</option>
                                <option value="Truyền Thông & Ghi Hình">Truyền Thông & Ghi Hình</option>
                                <option value="TNV Hướng Dẫn & Tiếp Sức">TNV Hướng Dẫn & Tiếp Sức</option>
                            </select>
                        </div>
                        <button type="submit" class="btn-action-pro" style="background:#2563eb; color:#fff; border:none; padding:9px 18px; font-weight:700; border-radius:6px; cursor:pointer;">
                            Cập Nhật
                        </button>
                    </div>
                </form>
            </div>

            <!-- Bảng danh sách lực lượng trong ca -->
            <div class="roster-table-wrap">
                <table class="roster-modal-table" id="roster_volunteers_table">
                    <thead>
                        <tr>
                            <th style="width: 6%;">STT</th>
                            <th style="width: 32%;">Tình Nguyện Viên</th>
                            <th style="width: 22%;">Vai Trò / Nhiệm Vụ</th>
                            <th style="width: 18%;">Kỹ Năng</th>
                            <th style="width: 22%; text-align: right;">Thao Tác Điều Động</th>
                        </tr>
                    </thead>
                    <tbody id="roster_modal_tbody">
                        <!-- JS đổ dữ liệu tình nguyện viên -->
                    </tbody>
                </table>
            </div>

            <div id="roster_no_search_result" style="display: none; padding: 20px; text-align: center; color: #64748b; font-size: 0.88rem;">
                Không tìm thấy tình nguyện viên nào khớp với từ khóa tìm kiếm.
            </div>
        </div>

        <div class="modal-footer" style="padding: 14px 24px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <div style="font-size: 0.84rem; color: #64748b;">
                Mọi thao tác thêm, sửa, xóa, báo vắng sẽ tự động lưu và đồng bộ tới lịch của tình nguyện viên.
            </div>
            <button type="button" class="btn-cancel" onclick="closeShiftRosterModal()">Đóng Lại</button>
        </div>
    </div>
</div>

<!-- =========================================================================
     MODAL POPUP 2: ĐIỀU PHỐI NHÂN SỰ THAY THẾ & BỘ LỌC KỸ NĂNG THÔNG MINH
     ========================================================================= -->
<div id="replaceModal" class="modal-overlay" style="display: none;">
    <div class="modal-content-card" style="max-width: 780px; width: 95%;">
        <div class="modal-header">
            <div>
                <h3 style="margin: 0; font-size: 1.25rem;">Điều Phối & Thay Thế Nhân Sự Ca Trực</h3>
                <span style="font-size: 0.84rem; color: #64748b;">Hỗ trợ lọc theo nhóm chuyên môn, kỹ năng có sẵn & kỹ năng do tình nguyện viên tự nhập</span>
            </div>
            <button type="button" class="btn-modal-close" onclick="closeReplaceModal()">&times;</button>
        </div>

        <form method="POST" action="/phan_cong.php" id="replace_volunteer_form">
            <input type="hidden" name="action" value="replace_volunteer">
            <input type="hidden" id="modalShiftId" name="shift_id" value="">
            <input type="hidden" id="modalAbsentVId" name="absent_volunteer_id" value="">

            <div class="modal-body" style="padding: 20px 24px; max-height: 72vh; overflow-y: auto;">
                <div class="modal-meta-box" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; margin-bottom: 18px; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                    <div>Ca trực: <strong id="modalShiftName" style="color: #0369a1;">...</strong></div>
                    <div>Nhân sự báo vắng: <strong id="modalAbsentVName" class="text-danger" style="color: #dc2626;">...</strong></div>
                </div>

                <!-- KHU VỰC BỘ LỌC KỸ NĂNG THÔNG MINH DÀNH CHO NGƯỜI ĐIỀU PHỐI -->
                <div class="coordinator-skill-filter-card" style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 8px; padding: 14px 16px; margin-bottom: 18px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span style="font-weight: 700; color: #1e293b; font-size: 0.92rem;">
                            Bộ Lọc Kỹ Năng Thông Minh (Gom Nhóm & Nhận Diện Tự Động)
                        </span>
                        <span id="candidate_count_badge" style="font-size: 0.8rem; background: #059669; color: #ffffff; padding: 2px 8px; border-radius: 12px; font-weight: 600;">
                            30 Ứng viên
                        </span>
                    </div>

                    <div style="grid-template-columns: 1fr 1fr; gap: 10px; display: grid;">
                        <div>
                            <label for="candidateGroupFilter" style="display: block; font-size: 0.8rem; color: #475569; font-weight: 600; margin-bottom: 4px;">Lọc theo Nhóm Kỹ Năng:</label>
                            <select id="candidateGroupFilter" class="select-pro" style="width: 100%; padding: 7px 10px; font-size: 0.88rem;">
                                <option value="all">-- Tất Cả Nhóm Kỹ Năng --</option>
                                <option value="y_te">[Y Tế] Sơ cấp cứu & Hỗ trợ y tế</option>
                                <option value="dieu_phoi">[Điều Phối] Giao thông, Dẫn đường & Lái xe</option>
                                <option value="hau_can">[Hậu Cần] Bếp ăn, Bốc dỡ & Thể lực</option>
                                <option value="truyen_thong">[Truyền Thông] Chụp ảnh, Ghi hình & Thiết kế</option>
                                <option value="su_pham">[Sư Phạm] Dạy học, Hoạt náo & Ngoại ngữ</option>
                                <option value="ky_thuat">[Kỹ Thuật] Thiết bị, Điện & Tin học</option>
                            </select>
                        </div>

                        <div>
                            <label for="candidateKeywordSearch" style="display: block; font-size: 0.8rem; color: #475569; font-weight: 600; margin-bottom: 4px;">Tìm từ khóa kỹ năng (kể cả kỹ năng tự do):</label>
                            <input type="text" id="candidateKeywordSearch" placeholder="Ví dụ: sơ cứu, bán tải, canva, nấu ăn, dạy học..." style="width: 100%; padding: 7px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem;">
                        </div>
                    </div>

                    <div style="margin-top: 10px; display: flex; align-items: center; justify-content: space-between;">
                        <label style="font-size: 0.82rem; color: #334155; display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input type="checkbox" id="candidateCertifiedFilter" style="cursor: pointer;">
                            <strong>Chỉ hiển thị tình nguyện viên có kỹ năng đã chứng nhận (Verified)</strong>
                        </label>
                        <button type="button" onclick="resetCandidateFilter()" style="background: none; border: none; color: #2563eb; font-size: 0.82rem; cursor: pointer; text-decoration: underline;">
                            Đặt lại bộ lọc
                        </button>
                    </div>
                </div>

                <!-- DANH SÁCH THẺ ỨNG VIÊN DỰ BỊ VỚI KỸ NĂNG CỐ ĐỊNH & KỸ NĂNG TỰ NHẬP -->
                <div style="margin-bottom: 14px;">
                    <label style="font-size: 0.88rem; font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px;">
                        Lựa Chọn Nhân Sự Phù Hợp Từ Lực Lượng (Nhấn Để Chọn Nhanh):
                    </label>

                    <div id="candidate_cards_container" style="max-height: 240px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px; background: #fafafa;">
                        <?php 
                        $allSkillGroups = SkillManager::getGroups();
                        foreach ($allVolunteers as $cand): 
                            $skillsProfile = SkillManager::getVolunteerSkillsProfile($cand);
                            $groupsList = array_unique(array_column($skillsProfile, 'group_id'));
                            $allSkillNames = implode(' ', array_column($skillsProfile, 'name'));
                            $hasCertified = !empty($cand['certified_skills']);
                        ?>
                            <div class="candidate-row-card" 
                                 data-id="<?= htmlspecialchars($cand['id']) ?>"
                                 data-name="<?= htmlspecialchars($cand['name']) ?>"
                                 data-groups="<?= htmlspecialchars(implode(',', $groupsList)) ?>"
                                 data-skills-text="<?= htmlspecialchars(strtolower($cand['name'] . ' ' . $allSkillNames . ' ' . implode(' ', $cand['skills'] ?? []))) ?>"
                                 data-certified="<?= $hasCertified ? '1' : '0' ?>"
                                 onclick="chooseCandidateDirectly('<?= htmlspecialchars($cand['id']) ?>')"
                                 style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; display: flex; justify-content: space-between; align-items: center; cursor: pointer; transition: all 0.2s ease;">
                                
                                <div style="display: flex; align-items: center; gap: 10px; flex: 1;">
                                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%; background: #059669; color: #ffffff; font-weight: 700; font-size: 0.85rem;">
                                        <?= mb_substr($cand['name'], 0, 1) ?>
                                    </span>
                                    <div>
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <strong style="color: #1e293b; font-size: 0.92rem;"><?= htmlspecialchars($cand['name']) ?></strong>
                                            <span style="font-size: 0.75rem; color: #64748b;">[<?= htmlspecialchars($cand['id']) ?>]</span>
                                            <?php if ($hasCertified): ?>
                                                <span style="font-size: 0.7rem; background: #ecfdf5; color: #059669; border: 1px solid #10b981; padding: 1px 6px; border-radius: 4px; font-weight: 700;">&#10003; Đã chứng minh</span>
                                            <?php endif; ?>
                                        </div>

                                        <div style="display: flex; gap: 4px; flex-wrap: wrap; margin-top: 4px;">
                                            <?php foreach ($cand['skills'] ?? [] as $sk): 
                                                $gMeta = $allSkillGroups[$sk] ?? [];
                                            ?>
                                                <span style="font-size: 0.72rem; padding: 2px 7px; border-radius: 4px; background: <?= htmlspecialchars($gMeta['bg_color'] ?? '#f1f5f9') ?>; color: <?= htmlspecialchars($gMeta['color'] ?? '#334155') ?>; font-weight: 600; border: 1px solid <?= htmlspecialchars($gMeta['color'] ?? '#cbd5e1') ?>30;">
                                                    <?= htmlspecialchars($gMeta['badge'] ?? $sk) ?>
                                                </span>
                                            <?php endforeach; ?>

                                            <?php foreach ($cand['custom_skills'] ?? [] as $cSk): ?>
                                                <span style="font-size: 0.72rem; padding: 2px 7px; border-radius: 4px; background: #f5f3ff; color: #7c3aed; font-weight: 600; border: 1px dashed #a78bfa;">
                                                    * Tự nhập: <?= htmlspecialchars($cSk) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>

                                <button type="button" class="btn-detail" style="padding: 5px 12px; font-size: 0.8rem; pointer-events: none;">
                                    Chọn
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div id="no_candidate_alert" style="display: none; padding: 14px; text-align: center; color: #64748b; font-size: 0.88rem; background: #ffffff; border: 1px dashed #cbd5e1; border-radius: 8px;">
                        Không tìm thấy ứng viên nào phù hợp với nhóm kỹ năng hoặc từ khóa này.
                    </div>
                </div>

                <div class="form-field-group" style="margin-top: 14px;">
                    <label for="replacementSelect"><strong>Nhân Sự Dự Bị Đang Được Chọn: <span class="req">*</span></strong></label>
                    <select id="replacementSelect" name="replacement_volunteer_id" required class="select-pro" onchange="highlightSelectedCandidateCard(this.value)">
                        <option value="">-- Lựa chọn nhân sự từ danh sách lực lượng --</option>
                        <?php foreach ($allVolunteers as $cand): 
                            $skillsStr = implode(', ', $cand['skills'] ?? []);
                            $customStr = !empty($cand['custom_skills']) ? (' | Tự nhập: ' . implode(', ', $cand['custom_skills'])) : '';
                        ?>
                            <option value="<?= htmlspecialchars($cand['id']) ?>">
                                <?= htmlspecialchars($cand['name']) ?> [<?= htmlspecialchars($cand['id']) ?>] — Chuyên môn: [<?= htmlspecialchars($skillsStr) ?>]<?= htmlspecialchars($customStr) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="modal-test-cases-box" style="margin-top: 14px;">
                    <small><strong>Gợi ý phép thử chuyên môn:</strong></small>
                    <div class="quick-test-buttons" style="margin-top: 6px;">
                        <button type="button" class="btn-quick-fill" onclick="selectQuickTest('TNV-001')">
                            Đ/c Lê Nguyễn Phương Thảo (Chuyên môn Y tế)
                        </button>
                        <button type="button" class="btn-quick-fill danger" onclick="selectQuickTest('TNV-013')">
                            Đ/c Phạm Minh Quân (Hậu cần - Không có chuyên môn Y tế)
                        </button>
                    </div>
                </div>
            </div>

            <div class="modal-footer" style="padding: 14px 24px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn-cancel" onclick="closeReplaceModal()">Hủy Bỏ</button>
                <button type="submit" class="btn-submit-replace" style="padding: 10px 24px; font-weight: 700;">
                    Xác Nhận Điều Phối
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// =========================================================================
// JAVASCRIPT ĐIỀU HÀNH LỰC LƯỢNG CA TRỰC (HÌNH CON MẮT & MODAL ROSTER)
// =========================================================================
var currentRosterShiftData = null;

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function openShiftRosterModal(dutyData) {
    if (!dutyData) return;
    currentRosterShiftData = dutyData;

    document.getElementById('roster_modal_shift_title').textContent = dutyData.shift_code + ' — ' + dutyData.shift_name;
    document.getElementById('roster_modal_shift_code').textContent = dutyData.shift_code;
    document.getElementById('roster_modal_act_title').textContent = dutyData.activity_title;
    document.getElementById('roster_modal_location').textContent = dutyData.location;
    document.getElementById('roster_modal_leader').textContent = dutyData.leader + ' (' + (dutyData.leader_role || 'Chỉ Huy') + ')';
    
    var activeCount = (dutyData.assigned_volunteers || []).filter(function(v){ return v.status !== 'absent'; }).length;
    document.getElementById('roster_modal_capacity_text').textContent = activeCount + ' / ' + dutyData.needed_count + ' TNV (Chỉ tiêu: ' + dutyData.needed_count + ')';

    document.getElementById('add_form_shift_id').value = dutyData.id;
    document.getElementById('edit_role_shift_id').value = dutyData.id;

    // Đặt lại ô tìm kiếm và các form phụ
    var searchInput = document.getElementById('roster_quick_search_input');
    if (searchInput) searchInput.value = '';
    toggleAddVolunteerForm(false);
    closeEditRoleForm();

    renderRosterTable(dutyData.assigned_volunteers || []);

    document.getElementById('shift_roster_modal').style.display = 'flex';
}

function closeShiftRosterModal() {
    document.getElementById('shift_roster_modal').style.display = 'none';
}

function renderRosterTable(volunteers) {
    var tbody = document.getElementById('roster_modal_tbody');
    tbody.innerHTML = '';

    if (!volunteers || volunteers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:24px; color:#64748b;">Chưa có tình nguyện viên nào trong ca này. Hãy bấm <strong>+ Thêm TNV Vào Ca</strong> để phân công.</td></tr>';
        return;
    }

    volunteers.forEach(function(v, index) {
        var tr = document.createElement('tr');
        tr.className = 'roster-item-row';
        tr.setAttribute('data-search', (v.name + ' ' + v.id + ' ' + (v.role || '') + ' ' + (v.skills || []).join(' ')).toLowerCase());

        var isAbsent = (v.status === 'absent');

        // Cột 1: STT
        var tdIdx = document.createElement('td');
        tdIdx.style.fontWeight = '700';
        tdIdx.style.color = '#64748b';
        tdIdx.textContent = index + 1;

        // Cột 2: Tình Nguyện Viên (Avatar + Tên + ID)
        var tdVol = document.createElement('td');
        tdVol.innerHTML = `
            <div class="vol-user-cell">
                <span class="vol-avatar-lg ${isAbsent ? 'avatar-absent' : ''}">
                    ${escapeHtml(v.name.charAt(0))}
                </span>
                <div>
                    <strong style="color:#1e293b; font-size:0.92rem; ${isAbsent ? 'text-decoration:line-through; color:#991b1b;' : ''}">
                        ${escapeHtml(v.name)}
                    </strong>
                    <div class="vol-id-tag">[${escapeHtml(v.id)}]</div>
                </div>
            </div>
        `;

        // Cột 3: Vai Trò Trong Ca & Nút Sửa
        var tdRole = document.createElement('td');
        var roleText = escapeHtml(v.role || 'TNV Thực Địa');
        tdRole.innerHTML = `
            <div style="display:flex; align-items:center; gap:6px;">
                <span class="vol-role-badge">
                    ${roleText}
                </span>
                <button type="button" class="btn-edit-role" title="Đổi vai trò" onclick="openEditRoleForm('${escapeHtml(v.id)}', '${escapeHtml(v.name)}', '${escapeHtml(v.role || 'TNV Thực Địa')}')">
                    &#9998;
                </button>
            </div>
            ${v.replacement_for ? `<small style="color:#0284c7; display:block; margin-top:2px;">(Thay cho: ${escapeHtml(v.replacement_for)})</small>` : ''}
        `;

        // Cột 4: Kỹ Năng
        var tdSkills = document.createElement('td');
        var skillsHtml = '';
        (v.skills || []).forEach(function(sk) {
            skillsHtml += `<span class="req-skill-pill" style="font-size:0.7rem; padding:1px 6px; margin-right:3px;">${escapeHtml(sk)}</span>`;
        });
        tdSkills.innerHTML = skillsHtml || '<span style="color:#94a3b8; font-size:0.75rem;">Cơ bản</span>';

        // Cột 5: Hành Động Điều Động
        var tdActions = document.createElement('td');
        tdActions.style.textAlign = 'right';

        var actionsHtml = '<div class="roster-action-btns" style="justify-content:flex-end;">';

        if (!isAbsent) {
            actionsHtml += `
                <form method="POST" action="/phan_cong.php" style="margin:0; display:inline-block;" onsubmit="return confirm('Xác nhận báo vắng cho đồng chí ${escapeHtml(v.name)}?')">
                    <input type="hidden" name="action" value="report_absent">
                    <input type="hidden" name="shift_id" value="${escapeHtml(currentRosterShiftData.id)}">
                    <input type="hidden" name="volunteer_id" value="${escapeHtml(v.id)}">
                    <button type="submit" class="btn-action-sm btn-action-warn" title="Ghi nhận báo vắng">
                        Báo Vắng
                    </button>
                </form>
            `;
        } else {
            actionsHtml += `
                <button type="button" class="btn-action-sm btn-action-replace" onclick="closeShiftRosterModal(); openReplaceModal('${escapeHtml(currentRosterShiftData.id)}', '${escapeHtml(currentRosterShiftData.shift_name)}', '${escapeHtml(v.id)}', '${escapeHtml(v.name)}')">
                    Thay Người
                </button>
            `;
        }

        // Nút Xóa khỏi ca
        actionsHtml += `
            <form method="POST" action="/phan_cong.php" style="margin:0; display:inline-block;" onsubmit="return confirm('Bạn có chắc chắn muốn rút đồng chí ${escapeHtml(v.name)} ra khỏi ca trực này?')">
                <input type="hidden" name="action" value="remove_volunteer_from_shift">
                <input type="hidden" name="shift_id" value="${escapeHtml(currentRosterShiftData.id)}">
                <input type="hidden" name="volunteer_id" value="${escapeHtml(v.id)}">
                <button type="submit" class="btn-action-sm btn-action-del" title="Xóa khỏi ca">
                    Xóa
                </button>
            </form>
        `;

        actionsHtml += '</div>';
        tdActions.innerHTML = actionsHtml;

        tr.appendChild(tdIdx);
        tr.appendChild(tdVol);
        tr.appendChild(tdRole);
        tr.appendChild(tdSkills);
        tr.appendChild(tdActions);

        tbody.appendChild(tr);
    });
}

// Tìm kiếm nhanh trong modal quân số
document.addEventListener('DOMContentLoaded', function() {
    var rosterSearch = document.getElementById('roster_quick_search_input');
    if (rosterSearch) {
        rosterSearch.addEventListener('input', function() {
            var q = this.value.trim().toLowerCase();
            var rows = document.querySelectorAll('.roster-item-row');
            var visible = 0;

            rows.forEach(function(row) {
                var search = row.getAttribute('data-search') || '';
                if (q === '' || search.indexOf(q) !== -1) {
                    row.style.display = '';
                    visible++;
                } else {
                    row.style.display = 'none';
                }
            });

            var noAlert = document.getElementById('roster_no_search_result');
            if (noAlert) {
                noAlert.style.display = (visible === 0 && q !== '') ? 'block' : 'none';
            }
        });
    }
});

// Bật/tắt form Thêm TNV vào ca
function toggleAddVolunteerForm(show) {
    var box = document.getElementById('add_volunteer_form_box');
    if (typeof show === 'boolean') {
        box.style.display = show ? 'block' : 'none';
    } else {
        box.style.display = (box.style.display === 'none' || box.style.display === '') ? 'block' : 'none';
    }
    if (box.style.display === 'block') {
        closeEditRoleForm();
    }
}

// Mở form Sửa vai trò nhanh
function openEditRoleForm(vId, vName, currentRole) {
    var box = document.getElementById('edit_role_form_box');
    document.getElementById('edit_role_volunteer_id').value = vId;
    document.getElementById('edit_role_vol_name').textContent = vName + ' [' + vId + ']';
    document.getElementById('edit_role_select').value = currentRole;
    box.style.display = 'block';
    toggleAddVolunteerForm(false);
}

function closeEditRoleForm() {
    var box = document.getElementById('edit_role_form_box');
    if (box) box.style.display = 'none';
}

// Xuất file CSV danh sách điểm danh
function exportRosterToCSV() {
    if (!currentRosterShiftData) return;
    var shift = currentRosterShiftData;
    var list = shift.assigned_volunteers || [];

    var csv = "\uFEFF"; // UTF-8 BOM
    csv += "DANH SÁCH ĐIỂM DANH LỰC LƯỢNG TÌNH NGUYỆN VIÊN\n";
    csv += "Mã Ca Trực: " + shift.shift_code + " - " + shift.shift_name + "\n";
    csv += "Hoạt Động: " + shift.activity_title + "\n";
    csv += "Địa Bàn: " + shift.location + " - Chỉ Huy Ca: " + shift.leader + "\n";
    csv += "STT,Mã TNV,Họ và Tên,Vai Trò Nhiệm Vụ,Kỹ Năng,Trạng Thái,Ký Nhận Có Mặt\n";

    list.forEach(function(v, idx) {
        var sk = (v.skills || []).join('; ');
        var st = v.status === 'absent' ? 'BÁO VẮNG' : 'ĐÃ GHI NHẬN';
        var r = v.role || 'TNV Thực Địa';
        csv += '"' + (idx + 1) + '","' + v.id + '","' + v.name + '","' + r + '","' + sk + '","' + st + '",""\n';
    });

    var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    var link = document.createElement("a");
    var url = URL.createObjectURL(blob);
    link.setAttribute("href", url);
    link.setAttribute("download", "Diem_danh_" + shift.shift_code + ".csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// =========================================================================
// JAVASCRIPT ĐIỀU PHỐI THAY THẾ NHÂN SỰ (MODAL REPLACE)
// =========================================================================
function openReplaceModal(shiftId, shiftName, absentVId, absentVName) {
    document.getElementById('modalShiftId').value = shiftId;
    document.getElementById('modalShiftName').textContent = shiftName;
    document.getElementById('modalAbsentVId').value = absentVId;
    document.getElementById('modalAbsentVName').textContent = absentVName;
    document.getElementById('replaceModal').style.display = 'flex';
    resetCandidateFilter();
}

function closeReplaceModal() {
    document.getElementById('replaceModal').style.display = 'none';
}

function selectQuickTest(vId) {
    chooseCandidateDirectly(vId);
}

function chooseCandidateDirectly(vId) {
    var select = document.getElementById('replacementSelect');
    if (select) {
        select.value = vId;
        highlightSelectedCandidateCard(vId);
    }
}

function highlightSelectedCandidateCard(vId) {
    var cards = document.querySelectorAll('.candidate-row-card');
    cards.forEach(function(card) {
        if (card.getAttribute('data-id') === vId) {
            card.style.borderColor = '#059669';
            card.style.background = '#ecfdf5';
            card.style.boxShadow = '0 0 0 2px #10b981';
        } else {
            card.style.borderColor = '#cbd5e1';
            card.style.background = '#ffffff';
            card.style.boxShadow = 'none';
        }
    });
}

function filterCandidates() {
    var group = document.getElementById('candidateGroupFilter').value;
    var query = document.getElementById('candidateKeywordSearch').value.trim().toLowerCase();
    var certifiedOnly = document.getElementById('candidateCertifiedFilter').checked;
    var cards = document.querySelectorAll('.candidate-row-card');
    var visibleCount = 0;

    cards.forEach(function(card) {
        var cardGroups = (card.getAttribute('data-groups') || '').split(',');
        var cardText = card.getAttribute('data-skills-text') || '';
        var isCert = card.getAttribute('data-certified') === '1';

        var matchGroup = (group === 'all' || cardGroups.indexOf(group) !== -1);
        var matchKeyword = (query === '' || cardText.indexOf(query) !== -1);
        var matchCert = (!certifiedOnly || isCert);

        if (matchGroup && matchKeyword && matchCert) {
            card.style.display = 'flex';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    var countBadge = document.getElementById('candidate_count_badge');
    if (countBadge) {
        countBadge.textContent = visibleCount + ' Ứng viên';
    }

    var noAlert = document.getElementById('no_candidate_alert');
    if (noAlert) {
        noAlert.style.display = visibleCount === 0 ? 'block' : 'none';
    }
}

function resetCandidateFilter() {
    var group = document.getElementById('candidateGroupFilter');
    var search = document.getElementById('candidateKeywordSearch');
    var cert = document.getElementById('candidateCertifiedFilter');
    if (group) group.value = 'all';
    if (search) search.value = '';
    if (cert) cert.checked = false;
    filterCandidates();
}

document.addEventListener('DOMContentLoaded', function() {
    var groupFilter = document.getElementById('candidateGroupFilter');
    var keywordSearch = document.getElementById('candidateKeywordSearch');
    var certFilter = document.getElementById('candidateCertifiedFilter');

    if (groupFilter) groupFilter.addEventListener('change', filterCandidates);
    if (keywordSearch) keywordSearch.addEventListener('input', filterCandidates);
    if (certFilter) certFilter.addEventListener('change', filterCandidates);
});

window.onclick = function(event) {
    var modal1 = document.getElementById('shift_roster_modal');
    var modal2 = document.getElementById('replaceModal');
    if (modal1 && event.target === modal1) {
        closeShiftRosterModal();
    }
    if (modal2 && event.target === modal2) {
        closeReplaceModal();
    }
};
</script>
<?php endif; ?>

<?php require __DIR__ . '/../templates/footer.php'; ?>
