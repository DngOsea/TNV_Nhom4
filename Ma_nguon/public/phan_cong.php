<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/../src/Input.php';
require __DIR__ . '/../src/Auth.php';
require __DIR__ . '/../src/AssignmentEngine.php';
require_once __DIR__ . '/../src/RegistrationManager.php';

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
// TỰ ĐỘNG ĐỒNG BỘ CÁC ĐƠN ĐĂNG KÝ TRỰC TUYẾN MỚI TỪ REGISTER.PHP SANG CA TRỰC
RegistrationManager::syncIntoSchedule($_SESSION['btl_duty_schedule']);
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
                'content' => 'Thao tác tự động phân bổ ca trực yêu cầu quyền Người Điều Phối (Ban Chỉ Huy). Tài khoản của bạn không được phép thực hiện.',
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
        // Giữ lại các TNV đã đăng ký trực tuyến vào ca tương ứng
        RegistrationManager::syncIntoSchedule($_SESSION['btl_duty_schedule']);
        $_SESSION['flash_msg'] = [
            'type' => 'success',
            'title' => 'Đã Hoàn Tất Phân Bổ Tự Động',
            'content' => 'Hệ thống đã tự động sắp xếp nhân sự cho toàn bộ các ca trực, tối ưu hóa theo năng lực chuyên môn và thời gian đăng ký.',
        ];
        header('Location: /phan_cong.php');
        exit;
    }

    // 2. XÁC NHẬN PHÊ DUYỆT LỊCH TRỰC CHÍNH THỨC (CHỈ DÀNH CHO BAN CHỈ HUY)
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

    // 3. XỬ LÝ YÊU CẦU BÁO VẮNG (TNV CHỈ ĐƯỢC BÁO VẮNG CA CỦA MÌNH, CHỈ HUY ĐƯỢC PHÉP BÁO VẮNG HỘ)
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

        // Nếu là tài khoản Tình Nguyện Viên thường -> Kiểm tra chặt chẽ tính chính chủ
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
                    'content' => 'Tài khoản Tình Nguyện Viên chỉ có quyền báo vắng cho ca trực của chính bản thân mình. Bạn không thể can thiệp lịch trực của tình nguyện viên khác.',
                ];
                header('Location: /phan_cong.php#shift-' . urlencode($shiftId));
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
                        'content' => 'Tình nguyện viên ' . htmlspecialchars($v['name']) . ' đã thông báo vắng mặt tại ca ' . $shiftId . '. Thông tin đã được chuyển tới Ban Chỉ Huy để điều động thay thế.',
                    ];
                    break;
                }
            }
            unset($v);
        }
        header('Location: /phan_cong.php#shift-' . urlencode($shiftId));
        exit;
    }

    // 4. THAY THẾ NHÂN SỰ VẮNG MẶT (CHỈ BAN CHỈ HUY MỚI CÓ QUYỀN ĐIỀU ĐỘNG THAY THẾ)
    if ($action === 'replace_volunteer') {
        if (!Auth::isCoordinator()) {
            $_SESSION['flash_msg'] = [
                'type' => 'danger',
                'title' => 'Từ Chối Quyền Truy Cập',
                'content' => 'Chỉ Người Điều Phối (Ban Chỉ Huy) mới có quyền chỉ định và thay thế nhân sự bổ sung vào ca trực.',
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

        // Danh sách hiện tại (loại bỏ người đang báo vắng)
        $activeAssigned = array_filter(
            $dutySchedules[$shiftId]['assigned_volunteers'],
            fn($v) => $v['id'] !== $absentVId && $v['status'] !== 'absent'
        );

        // GỌI HÀM KIỂM TRA RÀNG BUỘC CỦA THUẬT TOÁN
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
            header('Location: /phan_cong.php#shift-' . urlencode($shiftId));
            exit;
        }

        // HỢP LỆ -> THAY THẾ THÀNH CÔNG
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
        header('Location: /phan_cong.php#shift-' . urlencode($shiftId));
        exit;
    }

    // 5. THIẾT LẬP LẠI TRẠNG THÁI BAN ĐẦU (CHỈ DÀNH CHO BAN CHỈ HUY)
    if ($action === 'reset_schedule') {
        if (!Auth::isCoordinator()) {
            $_SESSION['flash_msg'] = [
                'type' => 'danger',
                'title' => 'Từ Chối Quyền Truy Cập',
                'content' => 'Chỉ Người Điều Phối (Ban Chỉ Huy) mới có quyền thiết lập lại dữ liệu ca trực.',
            ];
            header('Location: /phan_cong.php');
            exit;
        }

        $_SESSION['btl_duty_schedule'] = initDefaultSchedule($rawShifts, $allVolunteers);
        RegistrationManager::syncIntoSchedule($_SESSION['btl_duty_schedule']);
        $_SESSION['flash_msg'] = [
            'type' => 'info',
            'title' => 'Đã Thiết Lập Lại Bảng Phân Công',
            'content' => 'Dữ liệu các ca trực đã được đưa về trạng thái phân bổ tiêu chuẩn.',
        ];
        header('Location: /phan_cong.php');
        exit;
    }
}

// Lọc ca trực theo thời gian
$selectedFilter = field($_GET, 'shift_type');
$filteredDuties = array_filter($dutySchedules, function($d) use ($selectedFilter) {
    if ($selectedFilter === '') return true;
    return stripos($d['shift_name'], $selectedFilter) !== false;
});

// Thống kê tổng hợp
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
    <!-- THANH TÁC VỤ ĐIỀU HÀNH HỢP NHẤT (CLEAN COMMAND BAR) -->
    <div class="coordinator-command-bar">
        <div class="command-officer-info">
            <span class="officer-badge">BAN CHỈ HUY</span>
            <div>
                <strong><?= htmlspecialchars($currentUser['name'] ?? '') ?></strong>
                <span class="officer-role">— <?= htmlspecialchars($currentUser['role'] ?? 'Cán Bộ Chỉ Huy') ?></span>
            </div>
        </div>

        <div class="command-actions-cluster">
            <?php if (Auth::isCoordinator()): ?>
                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="auto_assign">
                    <button type="submit" class="btn-cmd btn-cmd-primary">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                        Tự Động Phân Ca
                    </button>
                </form>

                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="confirm_all">
                    <button type="submit" class="btn-cmd btn-cmd-secondary">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        Phê Duyệt Lịch Trực
                    </button>
                </form>

                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="reset_schedule">
                    <button type="submit" class="btn-cmd btn-cmd-ghost" onclick="return confirm('Bạn có chắc muốn thiết lập lại bảng phân công?')">
                        Thiết Lập Lại
                    </button>
                </form>
            <?php endif; ?>

            <a href="/doi_soat_thuat_toan.php" class="btn-cmd btn-cmd-outline">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                Đối Soát Hiệu Suất
            </a>

            <a href="/lich_truc.php" class="btn-cmd btn-cmd-ghost" title="Xem dưới góc nhìn tình nguyện viên">
                Xem Lịch TNV &rarr;
            </a>
        </div>
    </div>

    <!-- THÔNG BÁO TÁC VỤ -->
    <?php if ($flashMessage): ?>
        <div class="alert-box-interactive alert-<?= htmlspecialchars($flashMessage['type']) ?>" style="margin-top: 16px;">
            <div class="alert-content">
                <strong><?= htmlspecialchars($flashMessage['title']) ?></strong>
                <p><?= htmlspecialchars($flashMessage['content']) ?></p>
            </div>
            <button type="button" class="btn-close-alert" onclick="this.parentElement.remove()" aria-label="Đóng">&times;</button>
        </div>
    <?php endif; ?>

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

    <!-- KHUNG THEO DÕI ĐƠN ĐĂNG KÝ TRỰC TUYẾN TIẾP NHẬN TỨC THÌ (REAL-TIME SYNC) -->
    <?php 
    $onlineRegistrations = RegistrationManager::getAll();
    $onlineCount = count($onlineRegistrations);
    ?>
    <div class="online-sync-card" style="margin-bottom: 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden;">
        <div style="padding: 14px 18px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #10b981; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);"></span>
                <strong style="font-size: 0.92rem; color: #0f172a;">Hồ Sơ Đăng Ký Cần Phê Duyệt</strong>
                <span style="background: #e0f2fe; color: #0369a1; font-size: 0.74rem; font-weight: 700; padding: 2px 8px; border-radius: 10px;">
                    <?= $onlineCount ?> hồ sơ
                </span>
            </div>
            <span style="font-size: 0.78rem; color: #64748b;">
                Tự động đồng bộ và biên chế vào ca trực khi TNV hoàn tất đăng ký online
            </span>
        </div>
        
        <?php if ($onlineCount === 0): ?>
            <div style="padding: 20px; text-align: center; color: #64748b; font-size: 0.85rem;">
                Chưa có đơn đăng ký trực tuyến mới nào. Khi tình nguyện viên nộp hồ sơ qua trang <em>Hồ Sơ Năng Lực & Đăng Ký</em>, thông tin sẽ lập tức xuất hiện tại đây và tự động gán vào ca trực tương ứng.
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.84rem; text-align: left;">
                    <thead>
                        <tr style="background: #fdfdfd; border-bottom: 1px solid #e2e8f0; color: #475569; font-weight: 600;">
                            <th style="padding: 9px 16px;">Mã Thẻ / Thời Gian</th>
                            <th style="padding: 9px 16px;">Tình Nguyện Viên</th>
                            <th style="padding: 9px 16px;">Chiến Dịch & Ca Đăng Ký</th>
                            <th style="padding: 9px 16px;">Ca Trực Được Biên Chế</th>
                            <th style="padding: 9px 16px;">Trạng Thái Điều Phối</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($onlineRegistrations, 0, 5) as $reg): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 9px 16px;">
                                    <strong style="color: #0369a1;"><?= htmlspecialchars($reg['pass_id'] ?? '') ?></strong>
                                    <div style="font-size: 0.73rem; color: #64748b;"><?= htmlspecialchars($reg['registered_at'] ?? '') ?></div>
                                </td>
                                <td style="padding: 9px 16px;">
                                    <strong style="color: #0f172a;"><?= htmlspecialchars($reg['name'] ?? '') ?></strong>
                                    <div style="font-size: 0.74rem; color: #64748b;"><?= htmlspecialchars($reg['phone'] ?? '') ?> &bull; <?= htmlspecialchars($reg['email'] ?? '') ?></div>
                                    <div style="font-size: 0.73rem; color: #475569;"><?= htmlspecialchars($reg['unit'] ?? '') ?></div>
                                </td>
                                <td style="padding: 9px 16px;">
                                    <div style="font-weight: 600; color: #334155;"><?= htmlspecialchars($reg['event_title'] ?? '') ?></div>
                                    <span style="font-size: 0.74rem; color: #0284c7; background: #f0f9ff; padding: 1px 6px; border-radius: 4px; display: inline-block; margin-top: 2px;">
                                        <?= htmlspecialchars($reg['shift_name'] ?? '') ?>
                                    </span>
                                </td>
                                <td style="padding: 9px 16px;">
                                    <a href="#shift-row-<?= htmlspecialchars($reg['shift_code'] ?? 'CA-01') ?>" style="display: inline-flex; align-items: center; gap: 4px; text-decoration: none; font-weight: 600; color: #047857; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 3px 8px; border-radius: 4px; font-size: 0.76rem;">
                                        <?= htmlspecialchars($reg['shift_code'] ?? 'CA-01') ?> &rarr; Đến ca trực
                                    </a>
                                </td>
                                <td style="padding: 9px 16px;">
                                    <span style="display: inline-flex; align-items: center; gap: 4px; font-weight: 600; font-size: 0.78rem; color: #059669;">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                        Đã biên chế vào ca
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- BỘ LỌC VÀ ĐIỀU KHIỂN CHẾ ĐỘ XEM TRỰC QUAN -->
    <div class="table-control-bar">
        <!-- Bộ lọc ca trực -->
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <a href="/phan_cong.php" class="tab-duty <?= $selectedFilter === '' ? 'active' : '' ?>">Tất Cả (<?= $totalShifts ?>)</a>
            <a href="/phan_cong.php?shift_type=Sáng" class="tab-duty <?= $selectedFilter === 'Sáng' ? 'active' : '' ?>">Ca Sáng</a>
            <a href="/phan_cong.php?shift_type=Chiều" class="tab-duty <?= $selectedFilter === 'Chiều' ? 'active' : '' ?>">Ca Chiều</a>
            <a href="/phan_cong.php?shift_type=Tối" class="tab-duty <?= $selectedFilter === 'Tối' ? 'active' : '' ?>">Ca Tối</a>
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
                        <th style="width: 35%;">Lực Lượng Thực Địa & Thao Tác Điều Động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filteredDuties as $duty): 
                        $sId = $duty['id'];
                        $needed = (int)$duty['needed_count'];
                        $activeAssignedCount = count(array_filter($duty['assigned_volunteers'], fn($v) => $v['status'] !== 'absent'));
                        $isFull = $activeAssignedCount >= $needed;
                        $percent = min(100, (int)round(($activeAssignedCount / max(1, $needed)) * 100));
                        $hasAbsent = count(array_filter($duty['assigned_volunteers'], fn($v) => $v['status'] === 'absent')) > 0;
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
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <?php foreach ($duty['assigned_volunteers'] as $v): 
                                        $isAbsent = ($v['status'] === 'absent');
                                    ?>
                                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; background: <?= $isAbsent ? '#fef2f2' : '#f8fafc' ?>; border: 1px solid <?= $isAbsent ? '#fca5a5' : '#e2e8f0' ?>; padding: 5px 10px; border-radius: 6px;">
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <span class="t-v-avatar" style="<?= $isAbsent ? 'background:#dc2626;' : 'background:#059669;' ?>">
                                                    <?= mb_substr($v['name'], 0, 1) ?>
                                                </span>
                                                <div>
                                                    <strong style="font-size: 0.85rem; color: #1e293b; <?= $isAbsent ? 'text-decoration: line-through; color: #991b1b;' : '' ?>">
                                                        <?= htmlspecialchars($v['name']) ?>
                                                    </strong>
                                                    <?php if (!empty($v['registered_online'])): ?>
                                                        <span style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; font-size: 0.68rem; font-weight: 700; padding: 1px 5px; border-radius: 4px; margin-left: 4px;">Đăng Ký Online</span>
                                                    <?php endif; ?>
                                                    <span style="font-size: 0.72rem; color: #64748b; margin-left: 6px;">
                                                        <?php if ($isAbsent): ?>
                                                            <strong style="color: #dc2626;">(BÁO VẮNG)</strong>
                                                        <?php elseif (!empty($v['replacement_for'])): ?>
                                                            <span style="color: #0284c7; font-weight: 600;">(Thay cho: <?= htmlspecialchars($v['replacement_for']) ?>)</span>
                                                        <?php else: ?>
                                                            (<?= htmlspecialchars($v['role'] ?? 'TNV') ?>)
                                                        <?php endif; ?>
                                                    </span>
                                                    <?php if (!empty($v['phone'])): ?>
                                                        <div style="font-size: 0.70rem; color: #64748b; margin-top: 2px;">
                                                            <?= htmlspecialchars($v['phone']) ?> <?= !empty($v['unit']) ? '• ' . htmlspecialchars($v['unit']) : '' ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div>
                                                <?php if (!$isAbsent): ?>
                                                    <form method="POST" style="margin: 0; display: inline-block;" onsubmit="return confirm('Xác nhận báo vắng cho đồng chí <?= htmlspecialchars(addslashes($v['name'])) ?>?')">
                                                        <input type="hidden" name="action" value="report_absent">
                                                        <input type="hidden" name="shift_id" value="<?= htmlspecialchars($sId) ?>">
                                                        <input type="hidden" name="volunteer_id" value="<?= htmlspecialchars($v['id']) ?>">
                                                        <button type="submit" class="t-btn-action t-btn-warn" style="padding: 3px 8px; font-size: 0.74rem;" title="Báo vắng nhân sự ca này">
                                                            Báo vắng
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <button type="button" class="t-btn-action t-btn-replace" style="padding: 4px 10px; font-size: 0.74rem;"
                                                            onclick="openReplaceModal('<?= htmlspecialchars($sId) ?>', '<?= htmlspecialchars($duty['shift_name']) ?>', '<?= htmlspecialchars($v['id']) ?>', '<?= htmlspecialchars(addslashes($v['name'])) ?>')">
                                                        Thay người
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 2. GIAO DIỆN DẠNG DANH SÁCH THẺ THU GỌN (COMPACT LIST VIEW) -->
    <div id="dispatch_list_container" class="duty-compact-list" style="display: none;">
        <?php foreach ($filteredDuties as $duty): 
            $sId = $duty['id'];
            $needed = (int)$duty['needed_count'];
            $activeAssignedCount = count(array_filter($duty['assigned_volunteers'], fn($v) => $v['status'] !== 'absent'));
            $isFull = $activeAssignedCount >= $needed;
            $hasAbsent = count(array_filter($duty['assigned_volunteers'], fn($v) => $v['status'] === 'absent')) > 0;
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
                    <div style="font-size: 0.78rem; font-weight: 700; color: #475569; margin-bottom: 6px;">Lực lượng phân công & Thao tác:</div>
                    <div style="display: flex; flex-direction: column; gap: 6px;">
                        <?php foreach ($duty['assigned_volunteers'] as $v): 
                            $isAbsent = ($v['status'] === 'absent');
                        ?>
                            <div style="display: flex; align-items: center; justify-content: space-between; background: <?= $isAbsent ? '#fef2f2' : '#f8fafc' ?>; border: 1px solid <?= $isAbsent ? '#fca5a5' : '#e2e8f0' ?>; padding: 4px 8px; border-radius: 6px;">
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <span class="t-v-avatar" style="<?= $isAbsent ? 'background:#dc2626;' : 'background:#059669;' ?>">
                                        <?= mb_substr($v['name'], 0, 1) ?>
                                    </span>
                                    <span style="font-size: 0.82rem; font-weight: 600; <?= $isAbsent ? 'text-decoration:line-through; color:#991b1b;' : 'color:#1e293b;' ?>">
                                        <?= htmlspecialchars($v['name']) ?>
                                    </span>
                                    <?php if (!empty($v['registered_online'])): ?>
                                        <span style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; font-size: 0.67rem; font-weight: 700; padding: 1px 4px; border-radius: 3px;">Online</span>
                                    <?php endif; ?>
                                    <?php if ($isAbsent): ?>
                                        <span style="font-size: 0.7rem; color: #dc2626; font-weight: 700;">(VẮNG)</span>
                                    <?php elseif (!empty($v['replacement_for'])): ?>
                                        <span style="font-size: 0.7rem; color: #0284c7;">(Thay thế)</span>
                                    <?php endif; ?>
                                </div>

                                <div>
                                    <?php if (!$isAbsent): ?>
                                        <form method="POST" style="margin: 0; display: inline-block;" onsubmit="return confirm('Xác nhận báo vắng cho đồng chí <?= htmlspecialchars(addslashes($v['name'])) ?>?')">
                                            <input type="hidden" name="action" value="report_absent">
                                            <input type="hidden" name="shift_id" value="<?= htmlspecialchars($sId) ?>">
                                            <input type="hidden" name="volunteer_id" value="<?= htmlspecialchars($v['id']) ?>">
                                            <button type="submit" class="t-btn-action t-btn-warn" style="padding: 2px 7px; font-size: 0.72rem;">
                                                Báo vắng
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button type="button" class="t-btn-action t-btn-replace" style="padding: 3px 8px; font-size: 0.72rem;"
                                                onclick="openReplaceModal('<?= htmlspecialchars($sId) ?>', '<?= htmlspecialchars($duty['shift_name']) ?>', '<?= htmlspecialchars($v['id']) ?>', '<?= htmlspecialchars(addslashes($v['name'])) ?>')">
                                            Thay người
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- THÔNG BÁO TÌM KIẾM KHÔNG THẤY -->
    <div id="no_dispatch_result" style="display: none; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; padding: 36px; text-align: center; margin-bottom: 24px;">
        <p style="color: #64748b; font-size: 0.95rem; margin: 0;">Không tìm thấy ca trực nào phù hợp với từ khóa tìm kiếm.</p>
    </div>

    <!-- JAVASCRIPT ĐIỀU KHIỂN CHẾ ĐỘ XEM VÀ TÌM KIẾM TỨC THỜI CHO BẢNG PHÂN CÔNG -->
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

            // Khôi phục view đã lưu
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
     MODAL POPUP: ĐIỀU PHỐI NHÂN SỰ THAY THẾ (CHỈ BAN CHỈ HUY MỚI CÓ QUYỀN)
     ========================================================================= -->
<div id="replaceModal" class="modal-overlay" style="display: none;">
    <div class="modal-content-card">
        <div class="modal-header">
            <h3>Điều Phối Nhân Sự Thay Thế</h3>
            <button type="button" class="btn-modal-close" onclick="closeReplaceModal()">&times;</button>
        </div>

        <form method="POST" action="/phan_cong.php">
            <input type="hidden" name="action" value="replace_volunteer">
            <input type="hidden" id="modalShiftId" name="shift_id" value="">
            <input type="hidden" id="modalAbsentVId" name="absent_volunteer_id" value="">

            <div class="modal-body">
                <div class="modal-meta-box">
                    <div>Ca trực: <strong id="modalShiftName">...</strong></div>
                    <div>Nhân sự báo vắng: <strong id="modalAbsentVName" class="text-danger">...</strong></div>
                </div>

                <div class="form-field-group">
                    <label for="replacementSelect"><strong>Chọn Nhân Sự Dự Bị Thay Thế:</strong></label>
                    <select id="replacementSelect" name="replacement_volunteer_id" required class="select-pro">
                        <option value="">-- Lựa chọn nhân sự từ danh sách lực lượng --</option>
                        <?php foreach ($allVolunteers as $cand): 
                            $skillsStr = implode(', ', $cand['skills'] ?? []);
                        ?>
                            <option value="<?= htmlspecialchars($cand['id']) ?>">
                                <?= htmlspecialchars($cand['name']) ?> [<?= htmlspecialchars($cand['id']) ?>] — Chuyên môn: [<?= htmlspecialchars($skillsStr) ?>]
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- GỢI Ý CHỌN NHANH THEO CHUYÊN MÔN -->
                <div class="modal-test-cases-box">
                    <small><strong>Gợi ý chọn nhân sự theo chuyên môn:</strong></small>
                    <div class="quick-test-buttons">
                        <button type="button" class="btn-quick-fill" onclick="selectQuickTest('TNV-001')">
                            Đ/c Lê Nguyễn Phương Thảo (Chuyên môn Y tế)
                        </button>
                        <button type="button" class="btn-quick-fill danger" onclick="selectQuickTest('TNV-013')">
                            Đ/c Phạm Minh Quân (Hậu cần - Không có chuyên môn Y tế)
                        </button>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeReplaceModal()">Hủy Bỏ</button>
                <button type="submit" class="btn-submit-replace">Xác Nhận Điều Phối</button>
            </div>
        </form>
    </div>
</div>

<script>
function openReplaceModal(shiftId, shiftName, absentVId, absentVName) {
    document.getElementById('modalShiftId').value = shiftId;
    document.getElementById('modalShiftName').textContent = shiftName;
    document.getElementById('modalAbsentVId').value = absentVId;
    document.getElementById('modalAbsentVName').textContent = absentVName;
    document.getElementById('replaceModal').style.display = 'flex';
}

function closeReplaceModal() {
    document.getElementById('replaceModal').style.display = 'none';
}

function selectQuickTest(vId) {
    const select = document.getElementById('replacementSelect');
    if (select) {
        select.value = vId;
    }
}

window.onclick = function(event) {
    const modal = document.getElementById('replaceModal');
    if (modal && event.target === modal) {
        closeReplaceModal();
    }
};
</script>
<?php endif; ?>

<?php require __DIR__ . '/../templates/footer.php'; ?>
