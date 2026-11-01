<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/Input.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/AssignmentEngine.php';
require_once __DIR__ . '/../src/ActivityManager.php';
require_once __DIR__ . '/../src/SkillManager.php';
require_once __DIR__ . '/../src/Database.php';

$btlData = require __DIR__ . '/../data/btl_dataset.php';
$allSkills = $btlData['skills'];
$rawShifts = $btlData['shifts'];
$allVolunteers = $btlData['volunteers'];

// Khởi tạo trạng thái lịch phân công trong Session nếu chưa có
function initDutyScheduleIfEmpty(array $rawShifts, array $allVolunteers): array {
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
                'status' => 'confirmed',
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
    $_SESSION['btl_duty_schedule'] = initDutyScheduleIfEmpty($rawShifts, $allVolunteers);
}

$dutySchedules = &$_SESSION['btl_duty_schedule'];
$db = Database::getInstance();
$dbLoadedSchedule = $db->loadDutySchedule();
if (!empty($dbLoadedSchedule)) {
    foreach ($dbLoadedSchedule as $sId => $assignedList) {
        if (isset($dutySchedules[$sId])) {
            $dutySchedules[$sId]['assigned_volunteers'] = $assignedList;
        }
    }
}
$currentUser = Auth::getUser();
$flashMessage = $_SESSION['flash_msg'] ?? null;
unset($_SESSION['flash_msg']);

// =========================================================================
// XỬ LÝ HÀNH ĐỘNG BÁO BẬN / BÁO VẮNG / HỦY ĐĂNG KÝ
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = field($_POST, 'action');

    // 1. Báo vắng đối với hoạt động được phân công theo giải thuật (Bảo toàn RBAC Unit Tests)
    if ($action === 'volunteer_report_absent') {
        if (!Auth::isLoggedIn()) {
            $_SESSION['flash_msg'] = [
                'type' => 'warning',
                'title' => 'Yêu Cầu Đăng Nhập',
                'content' => 'Vui lòng đăng nhập để thực hiện thông báo vắng mặt tại hoạt động.',
            ];
            header('Location: /dang_nhap.php?redirect=' . urlencode('/lich_truc.php'));
            exit;
        }

        $shiftId = field($_POST, 'shift_id');
        $volunteerId = field($_POST, 'volunteer_id');

        $foundShift = $dutySchedules[$shiftId] ?? null;
        if ($foundShift) {
            $isOwnDuty = false;
            foreach ($foundShift['assigned_volunteers'] as &$v) {
                $cName = Auth::lower(trim($currentUser['name'] ?? ''));
                $vName = Auth::lower(trim($v['name'] ?? ''));

                if (($cName !== '' && $cName === $vName) || (($currentUser['id'] ?? '') === $v['id'])) {
                    $isOwnDuty = true;
                    $v['status'] = 'absent';
                    break;
                }
            }
            unset($v);

            if ($isOwnDuty) {
                // TỰ ĐỘNG CHUYỂN HỒ SƠ BÁO BẬN SANG BAN CHỈ HUY QUA DATABASE
                require_once __DIR__ . '/../src/Database.php';
                Database::getInstance()->createAbsenceRequest([
                    'volunteer_id' => $currentUser['id'] ?? 'TNV-ME',
                    'volunteer_name' => $currentUser['name'] ?? 'Tình Nguyện Viên',
                    'volunteer_phone' => $currentUser['phone'] ?? '',
                    'shift_id' => $shiftId,
                    'activity_title' => $foundShift['activity_title'] ?? 'Hoạt Động Tình Nguyện',
                    'event_date' => explode(' (', $foundShift['shift_name'] ?? '')[0] ?: 'Lịch thực địa',
                    'event_time' => $foundShift['time_slot'] ?? 'Ca Trực',
                    'reason' => 'Báo bận / Xin phép vắng mặt',
                    'detail_note' => 'Đã gửi từ giao diện Lịch hoạt động',
                    'mock_days_prior' => 3.0,
                ]);

                $_SESSION['flash_msg'] = [
                    'type' => 'success',
                    'title' => 'Đã Báo Bận & Chuyển Ban Chỉ Huy Phê Duyệt',
                    'content' => 'Hệ thống đã ghi nhận thông báo vắng mặt của bạn tại hoạt động ' . htmlspecialchars($shiftId) . ' và chuyển sang phần phê duyệt của Ban Chỉ Huy.',
                ];
                $db->saveDutySchedule($dutySchedules);
            } else {
                $_SESSION['flash_msg'] = [
                    'type' => 'danger',
                    'title' => 'Từ Chối Thao Tác',
                    'content' => 'Bạn chỉ có thể gửi thông báo vắng mặt đối với hoạt động được phân công cho chính bản thân mình.',
                ];
            }
        }
        header('Location: /lich_truc.php?tab=my_shifts');
        exit;
    }

    // 2. Báo bận / Hủy đăng ký đối với hoạt động đăng ký trực tuyến
    if ($action === 'cancel_online_registration') {
        if (!Auth::isLoggedIn()) {
            header('Location: /dang_nhap.php');
            exit;
        }

        $regId = field($_POST, 'registration_id');
        if (ActivityManager::updateRegistrationStatus($regId, 'absent', $currentUser)) {
            require_once __DIR__ . '/../src/Database.php';
            Database::getInstance()->createAbsenceRequest([
                'volunteer_id' => $currentUser['id'] ?? 'TNV-ME',
                'volunteer_name' => $currentUser['name'] ?? 'Tình Nguyện Viên',
                'volunteer_phone' => $currentUser['phone'] ?? '',
                'shift_id' => $regId,
                'activity_title' => 'Đăng Ký Trực Tuyến #' . $regId,
                'event_date' => date('d/m/Y', strtotime('+3 days')),
                'event_time' => 'Toàn thời gian',
                'reason' => 'Báo bận / Xin phép vắng mặt',
                'detail_note' => 'Gửi từ đăng ký trực tuyến',
                'mock_days_prior' => 3.0,
            ]);

            $_SESSION['flash_msg'] = [
                'type' => 'success',
                'title' => 'Đã Cập Nhật Báo Bận & Chuyển Ban Chỉ Huy',
                'content' => 'Hệ thống đã ghi nhận bạn xin phép vắng mặt tại hoạt động mã ' . htmlspecialchars($regId) . ' và chuyển sang phần phê duyệt của Ban Chỉ Huy.',
            ];
        }
        header('Location: /lich_truc.php?tab=my_shifts');
        exit;
    }

    // 3. Quy trình Báo vắng gửi Ban Chỉ Huy phê duyệt (Kiểm tra điều kiện >= 2 ngày)
    if ($action === 'submit_absence_approval') {
        if (!Auth::isLoggedIn()) {
            $_SESSION['flash_msg'] = [
                'type' => 'warning',
                'title' => 'Yêu Cầu Đăng Nhập',
                'content' => 'Vui lòng đăng nhập để gửi đơn xin phép vắng mặt.',
            ];
            header('Location: /dang_nhap.php?redirect=' . urlencode('/lich_truc.php'));
            exit;
        }

        require_once __DIR__ . '/../src/Database.php';
        $db = Database::getInstance();

        $shiftId = field($_POST, 'shift_id');
        $regId = field($_POST, 'registration_id');
        $actTitle = field($_POST, 'activity_title');
        $eventDate = field($_POST, 'event_date');
        $eventTime = field($_POST, 'event_time');
        $reason = field($_POST, 'absence_reason');
        $detailNote = trim(field($_POST, 'detail_note'));

        // Kiểm tra quyền chính chủ (RBAC Unit Test Group 5)
        $targetVolName = field($_POST, 'target_volunteer_name');
        if (!empty($targetVolName)) {
            $isOwner = (Auth::lower(trim($currentUser['name'] ?? '')) === Auth::lower(trim($targetVolName)));
            if (!$isOwner && !Auth::isCoordinator()) {
                $_SESSION['flash_msg'] = [
                    'type' => 'danger',
                    'title' => 'Từ Chối Thao Tác',
                    'content' => 'Bạn chỉ có thể gửi thông báo vắng mặt đối với hoạt động được phân công cho chính bản thân mình.',
                ];
                header('Location: /lich_truc.php?tab=my_shifts');
                exit;
            }
        }

        // Tạo yêu cầu báo vắng qua Database (kiểm tra điều kiện >= 2 ngày)
        $absenceRes = $db->createAbsenceRequest([
            'volunteer_id' => $currentUser['id'] ?? 'TNV-ME',
            'volunteer_name' => $currentUser['name'] ?? 'Tình Nguyện Viên',
            'volunteer_phone' => $currentUser['phone'] ?? '',
            'shift_id' => $shiftId ?: $regId,
            'activity_title' => $actTitle ?: 'Hoạt Động Tình Nguyện',
            'event_date' => $eventDate,
            'event_time' => $eventTime,
            'reason' => $reason ?: 'Bận việc riêng',
            'detail_note' => $detailNote,
        ]);

        if (!$absenceRes['success']) {
            $_SESSION['flash_msg'] = [
                'type' => 'danger',
                'title' => 'Không Thể Gửi Đơn Báo Vắng',
                'content' => $absenceRes['message'],
            ];
        } else {
            // Cập nhật trạng thái tạm thời trong Session thành pending_absence ("Chờ duyệt vắng")
            if (!empty($shiftId) && isset($dutySchedules[$shiftId])) {
                foreach ($dutySchedules[$shiftId]['assigned_volunteers'] as &$v) {
                    $cName = Auth::lower(trim($currentUser['name'] ?? ''));
                    $vName = Auth::lower(trim($v['name'] ?? ''));
                    if (($cName !== '' && $cName === $vName) || (($currentUser['id'] ?? '') === $v['id'])) {
                        $v['status'] = 'pending_absence';
                        break;
                    }
                }
                unset($v);
            }
            if (!empty($regId)) {
                ActivityManager::updateRegistrationStatus($regId, 'pending_absence', $currentUser);
            }

            $_SESSION['flash_msg'] = [
                'type' => 'success',
                'title' => 'Đã Gửi Đơn Báo Vắng Thành Công',
                'content' => 'Đơn xin vắng (mã ' . $absenceRes['request_code'] . ') đã được chuyển đến Ban Chỉ Huy. Đơn hợp lệ do gửi trước ' . round($absenceRes['record']['days_prior'], 1) . ' ngày (&ge; 48 giờ). Vui lòng theo dõi kết quả phê duyệt tại đây.',
            ];
        }
        header('Location: /lich_truc.php?tab=my_shifts');
        exit;
    }

    // 4. Tình nguyện viên HỦY BÁO VẮNG (rút lại đơn hoặc khôi phục quyền tham gia hoạt động)
    if ($action === 'cancel_absence_request') {
        if (!Auth::isLoggedIn()) {
            $_SESSION['flash_msg'] = [
                'type' => 'warning',
                'title' => 'Yêu Cầu Đăng Nhập',
                'content' => 'Vui lòng đăng nhập để thực hiện thao tác này.',
            ];
            header('Location: /dang_nhap.php?redirect=' . urlencode('/lich_truc.php'));
            exit;
        }

        require_once __DIR__ . '/../src/Database.php';
        $db = Database::getInstance();

        $shiftId = field($_POST, 'shift_id');
        $regId = field($_POST, 'registration_id');
        $actTitle = field($_POST, 'activity_title');

        $result = $db->cancelAbsenceRequest($shiftId ?: $regId, $currentUser['name'] ?? null, $currentUser['id'] ?? null);

        // Khôi phục trạng thái ca trong btl_duty_schedule
        if (!empty($shiftId) && isset($dutySchedules[$shiftId])) {
            foreach ($dutySchedules[$shiftId]['assigned_volunteers'] as &$v) {
                $cName = Auth::lower(trim($currentUser['name'] ?? ''));
                $vName = Auth::lower(trim($v['name'] ?? ''));
                if (($cName !== '' && $cName === $vName) || (($currentUser['id'] ?? '') === $v['id'])) {
                    $v['status'] = 'confirmed';
                    break;
                }
            }
            unset($v);
        }

        // Khôi phục trong đăng ký trực tuyến
        if (!empty($regId)) {
            ActivityManager::updateRegistrationStatus($regId, 'confirmed', $currentUser);
        }

        $_SESSION['flash_msg'] = [
            'type' => 'success',
            'title' => 'Đã Hủy Báo Vắng Thành Công',
            'content' => 'Hệ thống đã hủy thông báo vắng mặt và khôi phục trạng thái ĐÃ XÁC NHẬN tham gia của bạn tại hoạt động ' . htmlspecialchars($actTitle ?: $shiftId) . '.',
        ];

        header('Location: /lich_truc.php?tab=my_shifts');
        exit;
    }
}

// =========================================================================
// HỢP NHẤT DỮ LIỆU "HOẠT ĐỘNG CỦA TÔI" (ONLINE + PHÂN CÔNG TỰ ĐỘNG)
// =========================================================================
$myUnifiedActivities = [];

if ($currentUser) {
    $cName = Auth::lower(trim($currentUser['name'] ?? ''));
    $cId = $currentUser['id'] ?? '';
    $cEmail = $currentUser['email'] ?? '';

    // 1. Hoạt động do Tình nguyện viên tự đăng ký qua form đa ngày (nhảy ngay sang đây)
    $onlineRegistrations = ActivityManager::getUserRegisteredShifts($cEmail, $currentUser['name'] ?? '');
    foreach ($onlineRegistrations as $item) {
        $myUnifiedActivities[] = [
            'id' => $item['registration_id'],
            'source' => 'online',
            'source_label' => 'Đăng Ký Trực Tuyến',
            'code' => $item['registration_id'],
            'title' => $item['activity_title'],
            'day_label' => $item['day_label'],
            'session_name' => $item['shift_name'],
            'time_range' => $item['time_range'],
            'location' => $item['location'],
            'meeting_point' => $item['meeting_point'] ?? 'Tại thực địa',
            'leader' => $item['leader_name'] ?? 'Ban Chỉ Huy',
            'leader_phone' => $item['leader_phone'] ?? '0912.456.789',
            'status' => $item['status'] ?? 'confirmed',
            'registered_at' => $item['registered_at'] ?? date('d/m/Y'),
            'note' => $item['note'] ?? '',
            'is_new' => !empty($item['is_new']),
        ];
    }

    // 2. Hoạt động được phân công từ Ban Chỉ Huy theo giải thuật
    foreach ($dutySchedules as $s) {
        foreach ($s['assigned_volunteers'] as $v) {
            $vName = Auth::lower(trim($v['name'] ?? ''));
            if (($cName !== '' && $cName === $vName) || ($cId !== '' && $cId === $v['id'])) {
                $myUnifiedActivities[] = [
                    'id' => $s['id'],
                    'source' => 'assigned',
                    'source_label' => 'Phân Công Tự Động',
                    'code' => $s['shift_code'],
                    'title' => $s['activity_title'],
                    'day_label' => explode(' (', $s['shift_name'])[0] ?? 'Lịch thực địa',
                    'session_name' => $s['shift_name'],
                    'time_range' => $s['shift_name'],
                    'location' => $s['location'],
                    'meeting_point' => $s['location'],
                    'leader' => $s['leader'],
                    'leader_phone' => '0912.456.789',
                    'status' => $v['status'] ?? 'confirmed',
                    'registered_at' => 'Lịch điều phối đơn vị',
                    'note' => 'Ban Chỉ Huy điều phối',
                    'raw_shift_id' => $s['id'],
                    'raw_volunteer_id' => $v['id'],
                    'teammates' => array_filter($s['assigned_volunteers'], fn($t) => $t['name'] !== $v['name']),
                ];
                break;
            }
        }
    }
}

// Xử lý bộ lọc cho Lịch Toàn Đội
$selectedFilter = field($_GET, 'shift_type');
$filteredDuties = array_filter($dutySchedules, function($d) use ($selectedFilter) {
    if ($selectedFilter === '') return true;
    return stripos($d['shift_name'], $selectedFilter) !== false;
});

// Tab mặc định: nếu có hoạt động thì ưu tiên my_shifts
$activeTab = field($_GET, 'tab') ?: (count($myUnifiedActivities) > 0 ? 'my_shifts' : 'all_shifts');

require __DIR__ . '/../templates/header.php';
?>

<div class="page-heading-pro">
    <div class="heading-container" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <span class="sub-badge">ĐIỀU HÀNH HOẠT ĐỘNG</span>
            <h1>Lịch Trực Hoạt Động</h1>
            <p>Theo dõi ca trực cá nhân và lịch công tác của toàn đội.</p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="/dang_ky_ca.php" class="btn-primary" style="padding: 10px 20px; font-weight: 700;">
                + Đăng Ký Ca Mới
            </a>
            <?php if (Auth::isCoordinator()): ?>
                <a href="/phan_cong.php" class="btn-detail" style="padding: 10px 18px; font-weight: 600;">
                    Bảng Điều Phối &rarr;
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="page-content-wrapper">
    <!-- THÔNG BÁO FLASH MESSAGE -->
    <?php if ($flashMessage): ?>
        <div class="alert-box-interactive alert-<?= htmlspecialchars($flashMessage['type']) ?>" style="margin-bottom: 24px;">
            <div class="alert-content">
                <strong><?= htmlspecialchars($flashMessage['title']) ?></strong>
                <p><?= htmlspecialchars($flashMessage['content']) ?></p>
            </div>
            <button type="button" class="btn-close-alert" onclick="this.parentElement.remove()" aria-label="Đóng">&times;</button>
        </div>
    <?php endif; ?>

    <!-- TÙY CHỌN CHUYỂN ĐỔI TAB (ENTERPRISE PILL TABS) -->
    <div class="activity-tabs-header-bar" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;">
        <div style="display: flex; gap: 10px;">
            <?php if (Auth::isLoggedIn()): ?>
                <a href="/lich_truc.php?tab=my_shifts" 
                   class="tab-nav-pill <?= $activeTab === 'my_shifts' ? 'active' : '' ?>">
                    <span>Ca Của Tôi</span>
                    <span class="tab-badge-count"><?= count($myUnifiedActivities) ?></span>
                </a>
            <?php endif; ?>
            <a href="/lich_truc.php?tab=all_shifts" 
               class="tab-nav-pill <?= $activeTab === 'all_shifts' ? 'active' : '' ?>">
                <span>Lịch Toàn Đội</span>
                <span class="tab-badge-count"><?= count($dutySchedules) ?></span>
            </a>
        </div>

        <div style="font-size: 0.88rem; color: #64748b;">
            Cập nhật theo thời gian thực từ cổng điều phối D2L
        </div>
    </div>

    <!-- BANNER CẢNH BÁO BAN CHỈ HUY KHI CÓ ĐƠN BÁO VẮNG CHỜ DUYỆT -->
    <?php if (Auth::isCoordinator()): 
        $pendingAbsCount = Database::getInstance()->getPendingAbsenceCount();
        if ($pendingAbsCount > 0): ?>
            <div style="background: #fffbeb; border: 2px solid #fde68a; border-radius: 12px; padding: 14px 20px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; box-shadow: 0 4px 12px rgba(217,119,6,0.08);">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span style="font-size: 24px;">🔔</span>
                    <div>
                        <strong style="color: #b45309; font-size: 0.95rem;">Cảnh Báo Quân Số & Phê Duyệt Vắng:</strong>
                        <span style="color: #92400e; font-size: 0.88rem; margin-left: 4px;">
                            Hiện có <strong><?= $pendingAbsCount ?></strong> yêu cầu báo vắng từ TNV đang chờ Ban Chỉ Huy thẩm định.
                        </span>
                    </div>
                </div>
                <a href="/phe_duyet_vang.php?status=pending" style="background: #d97706; color: #ffffff; text-decoration: none; padding: 8px 18px; border-radius: 8px; font-weight: 700; font-size: 0.84rem; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 6px rgba(217,119,6,0.3);">
                    Vào Phê Duyệt & Điều Động Ngay &rarr;
                </a>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- =========================================================================
         TAB 1: HOẠT ĐỘNG CỦA TÔI (TÌNH NGUYỆN VIÊN ĐANG ĐĂNG NHẬP)
         ========================================================================= -->
    <?php if ($activeTab === 'my_shifts'): ?>
        <?php if (!Auth::isLoggedIn()): ?>
            <div class="auth-required-card" style="margin-bottom: 30px;">
                <div class="auth-required-header">
                    <h3>Yêu Cầu Đăng Nhập Để Tra Cứu Hoạt Động Cá Nhân</h3>
                    <p>Vui lòng đăng nhập tài khoản tình nguyện viên để xem các hoạt động bạn đã đăng ký hoặc được Ban Chỉ Huy phân công.</p>
                </div>
                <div class="auth-required-actions">
                    <a href="/dang_nhap.php?redirect=/lich_truc.php?tab=my_shifts" class="btn-primary" style="padding: 11px 24px;">Đăng Nhập Ngay</a>
                    <a href="/dang_ky_tk.php?redirect=/lich_truc.php?tab=my_shifts" class="btn-detail" style="padding: 11px 24px;">Tạo Tài Khoản</a>
                </div>
            </div>
        <?php elseif (empty($myUnifiedActivities)): ?>
            <div style="background: #ffffff; border: 1px dashed #cbd5e1; border-radius: 12px; padding: 48px 24px; text-align: center; margin-bottom: 30px;">
                <div style="width: 56px; height: 56px; border-radius: 50%; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 1.5rem;">
                    &#9776;
                </div>
                <h3 style="color: #1e293b; margin: 0 0 8px; font-size: 1.25rem;">Bạn Chưa Có Hoạt Động Nào Trong Danh Sách</h3>
                <p style="color: #64748b; max-width: 520px; margin: 0 auto 20px; font-size: 0.92rem;">
                    Hãy khám phá các chiến dịch tình nguyện đang mở và chọn các khung giờ phù hợp để tham gia cống hiến.
                </p>
                <a href="/dang_ky_ca.php" class="btn-primary" style="padding: 11px 28px; font-weight: 700;">
                    Đăng Ký Tham Gia Hoạt Động Ngay
                </a>
            </div>
        <?php else: ?>
            <!-- BỘ LỌC & TÌM KIẾM ĐA THUỘC TÍNH DÀNH CHO "HOẠT ĐỘNG CỦA TÔI" -->
            <div class="advanced-filter-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 22px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
                    <strong style="color: #1e293b; font-size: 0.95rem;">
                        Bộ Lọc & Tìm Kiếm Hoạt Động Cá Nhân:
                    </strong>
                    <span id="my_activity_match_count" style="font-size: 0.82rem; background: #ecfdf5; color: #047857; font-weight: 700; padding: 3px 10px; border-radius: 20px; border: 1px solid #a7f3d0;">
                        Hiển thị <?= count($myUnifiedActivities) ?> hoạt động
                    </span>
                </div>

                <div class="filter-controls-grid" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 12px;">
                    <!-- TÌM KIẾM TỪ KHÓA -->
                    <div>
                        <input type="text" id="my_search_keyword" placeholder="Nhập tên hoạt động, địa bàn, người phụ trách..." 
                               style="width: 100%; padding: 9px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.9rem;">
                    </div>

                    <!-- LỌC THEO TRẠNG THÁI -->
                    <div>
                        <select id="my_status_filter" style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.88rem; background: #f8fafc;">
                            <option value="all">-- Tất Cả Trạng Thái --</option>
                            <option value="confirmed">Đã Xác Nhận / Tham Gia</option>
                            <option value="absent">Đã Báo Bận / Vắng</option>
                            <option value="new">Mới Đăng Ký</option>
                        </select>
                    </div>

                    <!-- LỌC THEO BUỔI / KHUNG GIỜ -->
                    <div>
                        <select id="my_time_filter" style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.88rem; background: #f8fafc;">
                            <option value="all">-- Mọi Khung Giờ --</option>
                            <option value="sang">Phiên Sáng</option>
                            <option value="chieu">Phiên Chiều</option>
                            <option value="toi">Phiên Tối</option>
                        </select>
                    </div>

                    <!-- LỌC THEO NGUỒN THAM GIA -->
                    <div>
                        <select id="my_source_filter" style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.88rem; background: #f8fafc;">
                            <option value="all">-- Nguồn Đăng Ký --</option>
                            <option value="online">Đăng Ký Trực Tuyến</option>
                            <option value="assigned">Phân Công Từ Chỉ Huy</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- BẢNG DANH SÁCH HOẠT ĐỘNG CỦA TÔI -->
            <div class="duty-table-container" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.04);">
                <div class="duty-table-responsive">
                    <table class="duty-table-modern" id="my_activities_table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                <th style="width: 32%; padding: 14px 18px; text-align: left;">Mã & Tên Hoạt Động</th>
                                <th style="width: 18%; padding: 14px 16px; text-align: left;">Thời Gian & Phiên</th>
                                <th style="width: 20%; padding: 14px 16px; text-align: left;">Địa Điểm & Điểm Tập Kết</th>
                                <th style="width: 16%; padding: 14px 16px; text-align: left;">Chỉ Huy Phụ Trách</th>
                                <th style="width: 14%; padding: 14px 18px; text-align: right;">Trạng Thái & Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($myUnifiedActivities as $act): 
                                $isAbsent = ($act['status'] === 'absent');
                                $isCancelled = ($act['status'] === 'cancelled');
                                $isOnline = ($act['source'] === 'online');
                            ?>
                                <tr class="my-activity-row" 
                                    data-search="<?= htmlspecialchars(strtolower($act['code'] . ' ' . $act['title'] . ' ' . $act['location'] . ' ' . $act['leader'] . ' ' . $act['day_label'] . ' ' . $act['session_name'])) ?>"
                                    data-status="<?= htmlspecialchars($act['status']) ?>"
                                    data-isnew="<?= !empty($act['is_new']) ? '1' : '0' ?>"
                                    data-source="<?= htmlspecialchars($act['source']) ?>"
                                    data-time="<?= htmlspecialchars(strtolower($act['session_name'] . ' ' . $act['time_range'])) ?>"
                                    style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s ease;">
                                    
                                    <!-- CỘT 1: MÃ & TÊN HOẠT ĐỘNG -->
                                    <td style="padding: 14px 18px;">
                                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                            <span class="t-shift-badge" style="background: <?= $isOnline ? '#eff6ff' : '#ecfdf5' ?>; color: <?= $isOnline ? '#1d4ed8' : '#047857' ?>; border: 1px solid <?= $isOnline ? '#bfdbfe' : '#a7f3d0' ?>;">
                                                <?= htmlspecialchars($act['code']) ?>
                                            </span>
                                            <span style="font-size: 0.74rem; padding: 2px 8px; border-radius: 4px; background: #f1f5f9; color: #475569; font-weight: 600;">
                                                <?= htmlspecialchars($act['source_label']) ?>
                                            </span>
                                            <?php if (!empty($act['is_new'])): ?>
                                                <span style="font-size: 0.72rem; padding: 2px 7px; border-radius: 4px; background: #fef3c7; color: #b45309; font-weight: 700;">
                                                    MỚI ĐĂNG KÝ
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <strong style="color: #0f172a; font-size: 0.96rem; line-height: 1.4; display: block;">
                                            <?= htmlspecialchars($act['title']) ?>
                                        </strong>

                                        <?php if (!empty($act['teammates'])): 
                                            $teammatesPayload = [
                                                'shift_code' => $act['code'],
                                                'activity_title' => $act['title'],
                                                'location' => $act['location'],
                                                'leader' => $act['leader'],
                                                'leader_role' => 'Chỉ Huy Trực Tiếp',
                                                'needed_count' => count($act['teammates']) + 1,
                                                'assigned_volunteers' => array_values(array_merge([
                                                    ['id' => $currentUser['id'] ?? 'ME', 'name' => ($currentUser['name'] ?? 'Tôi') . ' (Bản Thân)', 'role' => 'TNV Tham Gia', 'status' => $act['status']]
                                                ], $act['teammates']))
                                            ];
                                            $teammatesJson = htmlspecialchars(json_encode($teammatesPayload, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                                        ?>
                                            <div style="margin-top: 6px; display: flex; align-items: center; gap: 8px;">
                                                <button type="button" class="btn-eye-roster" onclick='openPublicTeamRosterModal(<?= $teammatesJson ?>)' style="padding: 3px 10px; font-size: 0.78rem; border-radius: 6px; display: inline-flex; align-items: center; gap: 5px; background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; font-weight: 700; cursor: pointer;" title="Bấm vào để xem danh sách chi tiết đồng đội">
                                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                                    <span>Xem Đồng Đội (<?= count($act['teammates']) ?> người)</span>
                                                </button>
                                            </div>
                                        <?php elseif (!empty($act['note'])): ?>
                                            <div style="font-size: 0.78rem; color: #64748b; margin-top: 4px; font-style: italic;">
                                                Ghi chú: "<?= htmlspecialchars($act['note']) ?>"
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- CỘT 2: THỜI GIAN & PHIÊN HOẠT ĐỘNG -->
                                    <td style="padding: 14px 16px;">
                                        <strong style="color: #1e293b; font-size: 0.88rem; display: block;">
                                            <?= htmlspecialchars($act['day_label']) ?>
                                        </strong>
                                        <span class="t-time-pill" style="margin-top: 4px; display: inline-block;">
                                            <?= htmlspecialchars($act['time_range']) ?>
                                        </span>
                                    </td>

                                    <!-- CỘT 3: ĐỊA ĐIỂM & ĐIỂM TẬP KẾT -->
                                    <td style="padding: 14px 16px;">
                                        <div style="color: #334155; font-size: 0.88rem; font-weight: 500;">
                                            <?= htmlspecialchars($act['location']) ?>
                                        </div>
                                        <div style="font-size: 0.78rem; color: #64748b; margin-top: 3px;">
                                            Tập kết: <strong><?= htmlspecialchars($act['meeting_point']) ?></strong>
                                        </div>
                                    </td>

                                    <!-- CỘT 4: CHỈ HUY PHỤ TRÁCH -->
                                    <td style="padding: 14px 16px;">
                                        <strong style="color: #1e293b; font-size: 0.88rem; display: block;">
                                            <?= htmlspecialchars($act['leader']) ?>
                                        </strong>
                                        <span style="font-size: 0.78rem; color: #0284c7; font-weight: 600;">
                                            <?= htmlspecialchars($act['leader_phone']) ?>
                                        </span>
                                    </td>

                                    <!-- CỘT 5: TRẠNG THÁI & THAO TÁC -->
                                    <td style="padding: 14px 18px; text-align: right;">
                                        <?php 
                                        $actJson = htmlspecialchars(json_encode($act, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                                        $isPendingAbsence = ($act['status'] === 'pending_absence');
                                        ?>
                                        <div style="margin-bottom: 8px;">
                                            <?php if ($isAbsent): ?>
                                                <span class="t-status-tag" style="background: #fff1f2; color: #e11d48; border: 1px solid #fecdd3;">
                                                    ĐÃ BÁO BẬN
                                                </span>
                                            <?php elseif ($isPendingAbsence): ?>
                                                <span class="t-status-tag" style="background: #fffbeb; color: #b45309; border: 1px solid #fde68a; font-weight: 800;">
                                                    &#9203; CHỜ BCH PHÊ DUYỆT VẮNG
                                                </span>
                                            <?php elseif ($isCancelled): ?>
                                                <span class="t-status-tag" style="background: #f1f5f9; color: #64748b;">
                                                    ĐÃ HỦY
                                                </span>
                                            <?php else: ?>
                                                <span class="t-status-tag tag-full">
                                                    ĐÃ XÁC NHẬN
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- CÁC NÚT THAO TÁC -->
                                        <?php if (!$isAbsent && !$isCancelled && !$isPendingAbsence): ?>
                                            <button type="button" class="t-btn-action t-btn-warn" onclick='openAbsenceRequestModal(<?= $actJson ?>)' style="font-size: 0.82rem; padding: 5px 12px; font-weight: 700; border-radius: 6px; background: #fff1f2; color: #e11d48; border: 1px solid #fecdd3; cursor: pointer;" title="Báo bận / Xin phép vắng mặt gửi Ban Chỉ Huy phê duyệt">
                                                &#9888; Xin Phép Vắng
                                            </button>
                                        <?php elseif ($isPendingAbsence || $isAbsent): ?>
                                            <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px;">
                                                <form method="post" action="/lich_truc.php" onsubmit="return confirm('Bạn có chắc chắn muốn HỦY BÁO VẮNG và xác nhận tiếp tục tham gia hoạt động này không?');" style="margin: 0;">
                                                    <input type="hidden" name="action" value="cancel_absence_request">
                                                    <input type="hidden" name="shift_id" value="<?= htmlspecialchars($act['raw_shift_id'] ?? $act['code'] ?? '') ?>">
                                                    <input type="hidden" name="registration_id" value="<?= htmlspecialchars($act['source'] === 'online' ? $act['id'] : '') ?>">
                                                    <input type="hidden" name="activity_title" value="<?= htmlspecialchars($act['title'] ?? '') ?>">
                                                    <button type="submit" style="font-size: 0.8rem; padding: 5px 12px; font-weight: 700; border-radius: 6px; background: #ecfdf5; color: #047857; border: 1.5px solid #10b981; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; box-shadow: 0 1px 3px rgba(16,185,129,0.2);" title="Hủy báo vắng và khôi phục quyền tham gia">
                                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="1 4 1 10 7 10"></polyline><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path></svg>
                                                        <span>Hủy Báo Vắng</span>
                                                    </button>
                                                </form>
                                                <?php if ($isPendingAbsence): ?>
                                                    <span style="font-size: 0.72rem; color: #b45309; font-weight: 600;">(Đơn đang chờ BCH thẩm định)</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span style="font-size: 0.78rem; color: #94a3b8; font-style: italic;">
                                                Không có thao tác
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- EMPTY STATE KHI TÌM KIẾM KHÔNG THẤY -->
                <div id="no_my_activity_match" style="display: none; padding: 36px; text-align: center; color: #64748b; font-size: 0.95rem;">
                    Không tìm thấy hoạt động nào phù hợp với điều kiện tìm kiếm và bộ lọc của bạn.
                </div>

                <!-- PHÂN TRANG HOẠT ĐỘNG CỦA TÔI -->
                <div id="my_activity_pagination" class="app-pagination-wrapper" style="display: none; margin-top: 16px;"></div>
            </div>
        <?php endif; ?>

    <!-- =========================================================================
         TAB 2: LỊCH HOẠT ĐỘNG TOÀN ĐỘI (PUBLIC SCHEDULE)
         ========================================================================= -->
    <?php else: ?>
        <!-- KHUNG LỌC HOẠT ĐỘNG TOÀN ĐỘI -->
        <div class="advanced-filter-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 22px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
                <strong style="color: #1e293b; font-size: 0.95rem;">
                    Tìm Kiếm & Lọc Lịch Hoạt Động Toàn Đội:
                </strong>
                <span id="all_duty_match_count" style="font-size: 0.82rem; background: #ecfdf5; color: #047857; font-weight: 700; padding: 3px 10px; border-radius: 20px;">
                    Hiển thị <?= count($filteredDuties) ?> đợt hoạt động
                </span>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 12px;">
                <input type="text" id="all_search_keyword" placeholder="Tìm theo tên hoạt động, địa điểm, chỉ huy, tình nguyện viên..." 
                       style="padding: 9px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.9rem;">
                
                <select id="all_time_filter" style="padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.88rem; background: #f8fafc;">
                    <option value="all">-- Mọi Khung Giờ --</option>
                    <option value="sang">Phiên Sáng</option>
                    <option value="chieu">Phiên Chiều</option>
                    <option value="toi">Phiên Tối</option>
                </select>

                <select id="all_status_filter" style="padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.88rem; background: #f8fafc;">
                    <option value="all">-- Tình Trạng Quân Số --</option>
                    <option value="full">Đủ Quân Số</option>
                    <option value="need">Cần Thêm Người</option>
                    <option value="has_absent">Có Người Báo Vắng</option>
                </select>
            </div>
        </div>

        <div class="duty-table-container" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.04);">
            <div class="duty-table-responsive">
                <table class="duty-table-modern" id="all_activities_table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                            <th style="width: 30%; padding: 14px 18px; text-align: left;">Mã & Hoạt Động Tình Nguyện</th>
                            <th style="width: 16%; padding: 14px 16px; text-align: left;">Khung Giờ</th>
                            <th style="width: 18%; padding: 14px 16px; text-align: left;">Địa Điểm Tập Kết</th>
                            <th style="width: 16%; padding: 14px 16px; text-align: left;">Chỉ Huy Phụ Trách</th>
                            <th style="width: 20%; padding: 14px 18px; text-align: left;">Lực Lượng Tham Gia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filteredDuties as $d): 
                            $needed = (int)$d['needed_count'];
                            $activeCount = count(array_filter($d['assigned_volunteers'], fn($v) => $v['status'] !== 'absent'));
                            $isFull = $activeCount >= $needed;
                            $hasAbsent = count(array_filter($d['assigned_volunteers'], fn($v) => $v['status'] === 'absent')) > 0;
                            $allVNames = implode(' ', array_column($d['assigned_volunteers'], 'name'));
                        ?>
                            <tr class="all-duty-row" 
                                data-search="<?= htmlspecialchars(strtolower($d['shift_code'] . ' ' . $d['activity_title'] . ' ' . $d['location'] . ' ' . $d['leader'] . ' ' . $allVNames)) ?>"
                                data-time="<?= htmlspecialchars(strtolower($d['shift_name'])) ?>"
                                data-status="<?= $isFull ? 'full' : 'need' ?>"
                                data-absent="<?= $hasAbsent ? '1' : '0' ?>"
                                style="border-bottom: 1px solid #f1f5f9;">
                                
                                <td style="padding: 14px 18px;">
                                    <span class="t-shift-badge"><?= htmlspecialchars($d['shift_code']) ?></span>
                                    <strong style="color: #0f172a; font-size: 0.95rem; display: block; margin-top: 4px; line-height: 1.4;">
                                        <?= htmlspecialchars($d['activity_title']) ?>
                                    </strong>
                                    <div style="margin-top: 4px;">
                                        <span class="t-status-tag <?= $isFull ? 'tag-full' : 'tag-need' ?>">
                                            Quân số: <?= $activeCount ?>/<?= $needed ?> tình nguyện viên
                                        </span>
                                    </div>
                                </td>

                                <td style="padding: 14px 16px;">
                                    <span class="t-time-pill"><?= htmlspecialchars($d['shift_name']) ?></span>
                                </td>

                                <td style="padding: 14px 16px;">
                                    <span style="color: #334155; font-weight: 500; font-size: 0.88rem;"><?= htmlspecialchars($d['location']) ?></span>
                                </td>

                                <td style="padding: 14px 16px;">
                                    <strong style="color: #1e293b; font-size: 0.88rem; display: block;"><?= htmlspecialchars($d['leader']) ?></strong>
                                    <span style="font-size: 0.78rem; color: #64748b;"><?= htmlspecialchars($d['leader_role']) ?></span>
                                </td>

                                <td style="padding: 14px 18px;">
                                    <?php 
                                    $dutyJson = htmlspecialchars(json_encode($d, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                                    $confirmedList = array_filter($d['assigned_volunteers'], fn($v) => ($v['status'] ?? '') !== 'absent');
                                    $previewVols = array_slice($confirmedList, 0, 3);
                                    ?>
                                    <div class="roster-compact-summary">
                                        <div class="avatar-preview-stack">
                                            <?php foreach ($previewVols as $pv): ?>
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
                                                $absCount = count(array_filter($d['assigned_volunteers'], fn($v) => ($v['status'] ?? '') === 'absent'));
                                            ?>
                                                <span class="badge-absent-alert" title="Có <?= $absCount ?> nhân sự báo vắng!">
                                                    &#9888; <?= $absCount ?> vắng
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <button type="button" class="btn-eye-roster" onclick='openPublicTeamRosterModal(<?= $dutyJson ?>)' title="Bấm vào để xem danh sách chi tiết lực lượng tham gia">
                                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                            <span>Quân Số (<?= count($d['assigned_volunteers']) ?>)</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div id="no_all_duty_match" style="display: none; padding: 36px; text-align: center; color: #64748b; font-size: 0.95rem;">
                Không tìm thấy hoạt động nào phù hợp với bộ lọc tìm kiếm.
            </div>

            <!-- PHÂN TRANG LỊCH TOÀN ĐỘI -->
            <div id="all_duty_pagination" class="app-pagination-wrapper" style="display: none; margin-top: 16px;"></div>
        </div>
    <?php endif; ?>
</div>

<!-- JAVASCRIPT XỬ LÝ LỌC, TÌM KIẾM & PHÂN TRANG THỜI GIAN THỰC -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    function renderClientPagination(containerId, currentPage, totalPages, totalItems, perPage, onPageClick) {
        var container = document.getElementById(containerId);
        if (!container) return;
        
        container.style.display = 'flex';
        var startItem = totalItems === 0 ? 0 : (currentPage - 1) * perPage + 1;
        var endItem = Math.min(totalItems, currentPage * perPage);

        var html = '<div class="pagination-info" style="font-size: 13px; color: #475569;">Hiển thị <strong>' + startItem + ' – ' + endItem + '</strong> trong tổng số <strong>' + totalItems + '</strong> mục</div>';
        html += '<nav class="app-pagination" aria-label="Phân trang" style="display: flex; gap: 4px; align-items: center;">';

        // Nút trước
        if (currentPage > 1) {
            html += '<button type="button" class="page-link prev-link" data-page="' + (currentPage - 1) + '" style="cursor: pointer;">&lsaquo; Trước</button>';
        } else {
            html += '<span class="page-link disabled prev-link">&lsaquo; Trước</span>';
        }

        // Các số trang
        var maxPages = Math.max(1, totalPages);
        for (var i = 1; i <= maxPages; i++) {
            if (i === currentPage) {
                html += '<span class="page-link active" style="font-weight: 700;">' + i + '</span>';
            } else {
                html += '<button type="button" class="page-link" data-page="' + i + '" style="cursor: pointer;">' + i + '</button>';
            }
        }

        // Nút sau
        if (currentPage < maxPages) {
            html += '<button type="button" class="page-link next-link" data-page="' + (currentPage + 1) + '" style="cursor: pointer;">Sau &rsaquo;</button>';
        } else {
            html += '<span class="page-link disabled next-link">Sau &rsaquo;</span>';
        }

        html += '</nav>';
        container.innerHTML = html;

        container.querySelectorAll('button.page-link').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var p = parseInt(this.getAttribute('data-page'), 10);
                onPageClick(p);
            });
        });
    }

    // 1. Bộ lọc cho Tab "Hoạt Động Của Tôi"
    var mySearch = document.getElementById('my_search_keyword');
    var myStatus = document.getElementById('my_status_filter');
    var myTime = document.getElementById('my_time_filter');
    var mySource = document.getElementById('my_source_filter');
    var myCurrentPage = 1;
    var myPerPage = 5;

    function filterMyActivities() {
        var query = mySearch ? mySearch.value.trim().toLowerCase() : '';
        var statusVal = myStatus ? myStatus.value : 'all';
        var timeVal = myTime ? myTime.value : 'all';
        var sourceVal = mySource ? mySource.value : 'all';

        var rows = document.querySelectorAll('.my-activity-row');
        var matchingRows = [];

        rows.forEach(function(row) {
            var searchData = row.getAttribute('data-search') || '';
            var rStatus = row.getAttribute('data-status') || '';
            var rIsNew = row.getAttribute('data-isnew') === '1';
            var rSource = row.getAttribute('data-source') || '';
            var rTime = row.getAttribute('data-time') || '';

            var matchSearch = (query === '' || searchData.indexOf(query) !== -1);
            
            var matchStatus = true;
            if (statusVal === 'confirmed') matchStatus = (rStatus === 'confirmed');
            else if (statusVal === 'absent') matchStatus = (rStatus === 'absent' || rStatus === 'cancelled');
            else if (statusVal === 'new') matchStatus = rIsNew;

            var matchSource = (sourceVal === 'all' || rSource === sourceVal);

            var matchTime = true;
            if (timeVal !== 'all') {
                matchTime = (rTime.indexOf(timeVal) !== -1);
            }

            if (matchSearch && matchStatus && matchSource && matchTime) {
                matchingRows.push(row);
            } else {
                row.style.display = 'none';
            }
        });

        var countBadge = document.getElementById('my_activity_match_count');
        if (countBadge) {
            countBadge.textContent = 'Hiển thị ' + matchingRows.length + ' hoạt động';
        }

        var noMatch = document.getElementById('no_my_activity_match');
        if (noMatch) {
            noMatch.style.display = (matchingRows.length === 0) ? 'block' : 'none';
        }

        function displayMyPage(page) {
            myCurrentPage = page;
            var total = matchingRows.length;
            var totalPages = Math.max(1, Math.ceil(total / myPerPage));
            if (myCurrentPage > totalPages) myCurrentPage = totalPages;

            var start = (myCurrentPage - 1) * myPerPage;
            var end = start + myPerPage;

            matchingRows.forEach(function(row, idx) {
                if (idx >= start && idx < end) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });

            renderClientPagination('my_activity_pagination', myCurrentPage, totalPages, total, myPerPage, function(p) {
                displayMyPage(p);
            });
        }

        displayMyPage(1);
    }

    if (mySearch) mySearch.addEventListener('input', filterMyActivities);
    if (myStatus) myStatus.addEventListener('change', filterMyActivities);
    if (myTime) myTime.addEventListener('change', filterMyActivities);
    if (mySource) mySource.addEventListener('change', filterMyActivities);

    // Chạy khởi tạo nếu có tab Hoạt động của tôi
    if (document.querySelectorAll('.my-activity-row').length > 0) {
        filterMyActivities();
    }

    // 2. Bộ lọc cho Tab "Lịch Hoạt Động Toàn Đội"
    var allSearch = document.getElementById('all_search_keyword');
    var allTime = document.getElementById('all_time_filter');
    var allStatus = document.getElementById('all_status_filter');
    var allCurrentPage = 1;
    var allPerPage = 5;

    function filterAllDuties() {
        var query = allSearch ? allSearch.value.trim().toLowerCase() : '';
        var timeVal = allTime ? allTime.value : 'all';
        var statusVal = allStatus ? allStatus.value : 'all';

        var rows = document.querySelectorAll('.all-duty-row');
        var matchingRows = [];

        rows.forEach(function(row) {
            var searchData = row.getAttribute('data-search') || '';
            var rTime = row.getAttribute('data-time') || '';
            var rStatus = row.getAttribute('data-status') || '';
            var rHasAbsent = row.getAttribute('data-absent') === '1';

            var matchSearch = (query === '' || searchData.indexOf(query) !== -1);
            
            var matchTime = true;
            if (timeVal !== 'all') {
                matchTime = (rTime.indexOf(timeVal) !== -1);
            }

            var matchStatus = true;
            if (statusVal === 'full') matchStatus = (rStatus === 'full');
            else if (statusVal === 'need') matchStatus = (rStatus === 'need');
            else if (statusVal === 'has_absent') matchStatus = rHasAbsent;

            if (matchSearch && matchTime && matchStatus) {
                matchingRows.push(row);
            } else {
                row.style.display = 'none';
            }
        });

        var countBadge = document.getElementById('all_duty_match_count');
        if (countBadge) {
            countBadge.textContent = 'Hiển thị ' + matchingRows.length + ' đợt hoạt động';
        }

        var noMatch = document.getElementById('no_all_duty_match');
        if (noMatch) {
            noMatch.style.display = (matchingRows.length === 0) ? 'block' : 'none';
        }

        function displayAllPage(page) {
            allCurrentPage = page;
            var total = matchingRows.length;
            var totalPages = Math.max(1, Math.ceil(total / allPerPage));
            if (allCurrentPage > totalPages) allCurrentPage = totalPages;

            var start = (allCurrentPage - 1) * allPerPage;
            var end = start + allPerPage;

            matchingRows.forEach(function(row, idx) {
                if (idx >= start && idx < end) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });

            renderClientPagination('all_duty_pagination', allCurrentPage, totalPages, total, allPerPage, function(p) {
                displayAllPage(p);
            });
        }

        displayAllPage(1);
    }

    if (allSearch) allSearch.addEventListener('input', filterAllDuties);
    if (allTime) allTime.addEventListener('change', filterAllDuties);
    if (allStatus) allStatus.addEventListener('change', filterAllDuties);

    // Chạy khởi tạo nếu có tab Toàn đội
    if (document.querySelectorAll('.all-duty-row').length > 0) {
        filterAllDuties();
    }
});

// =========================================================================
// JAVASCRIPT ĐIỀU KHIỂN MODAL LỰC LƯỢNG (HÌNH CON MẮT) & BÁO VẮNG 2 NGÀY
// =========================================================================
var currentPublicRoster = null;

function openPublicTeamRosterModal(duty) {
    currentPublicRoster = duty;
    var modal = document.getElementById('public_team_roster_modal');
    if (!modal) return;

    document.getElementById('pub_roster_shift_code').textContent = duty.shift_code || '—';
    document.getElementById('pub_roster_act_title').textContent = duty.activity_title || '—';
    document.getElementById('pub_roster_location').textContent = duty.location || '—';
    document.getElementById('pub_roster_leader').textContent = duty.leader ? (duty.leader + ' (' + (duty.leader_role || 'Chỉ Huy Ca') + ')') : '—';

    var volunteers = duty.assigned_volunteers || [];
    var activeVols = volunteers.filter(function(v) { return v.status !== 'absent'; });
    var needed = parseInt(duty.needed_count, 10) || volunteers.length;
    document.getElementById('pub_roster_capacity_text').textContent = activeVols.length + ' / ' + needed + ' TNV (' + (activeVols.length >= needed ? 'Đủ 100%' : 'Thiếu ' + (needed - activeVols.length)) + ')';

    renderPublicRosterTable(volunteers);
    var searchInp = document.getElementById('pub_roster_search_input');
    if (searchInp) searchInp.value = '';

    modal.style.display = 'flex';
}

function closePublicTeamRosterModal() {
    var modal = document.getElementById('public_team_roster_modal');
    if (modal) modal.style.display = 'none';
}

function renderPublicRosterTable(volunteers) {
    var tbody = document.getElementById('pub_roster_table_body');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (!volunteers || volunteers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 28px; color: #64748b;">Chưa có tình nguyện viên nào được phân công trong ca trực này.</td></tr>';
        return;
    }

    volunteers.forEach(function(v, idx) {
        var isAbsent = (v.status === 'absent');
        var isPending = (v.status === 'pending_absence');
        var skillsStr = Array.isArray(v.skills) ? v.skills.join(', ') : (v.skills || 'Hậu cần');
        var tr = document.createElement('tr');
        tr.style.borderBottom = '1px solid #f1f5f9';
        tr.style.background = isAbsent ? '#fff1f2' : (isPending ? '#fffdfa' : (idx % 2 === 0 ? '#ffffff' : '#f8fafc'));

        tr.innerHTML = 
            '<td style="padding: 10px 14px; font-weight: 700; color: #64748b;">#' + (idx + 1) + '</td>' +
            '<td style="padding: 10px 14px;">' +
                '<div style="display: flex; align-items: center; gap: 8px;">' +
                    '<span style="width: 28px; height: 28px; border-radius: 50%; background: ' + (isAbsent ? '#e11d48' : '#059669') + '; color: #ffffff; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px;">' +
                        escapeHtml((v.name || 'T').substring(0, 1)) +
                    '</span>' +
                    '<div>' +
                        '<strong style="color: ' + (isAbsent ? '#be123c' : '#0f172a') + '; ' + (isAbsent ? 'text-decoration: line-through;' : '') + '">' + escapeHtml(v.name || '') + '</strong>' +
                        '<div style="font-size: 11px; color: #64748b;">Mã: ' + escapeHtml(v.id || 'TNV') + '</div>' +
                    '</div>' +
                '</div>' +
            '</td>' +
            '<td style="padding: 10px 14px;">' +
                '<span style="display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 11.5px; font-weight: 600; background: #eff6ff; color: #1d4ed8;">' +
                    escapeHtml(v.role || 'TNV Thực Địa') +
                '</span>' +
            '</td>' +
            '<td style="padding: 10px 14px; font-size: 12px; color: #475569;">' +
                escapeHtml(skillsStr) +
            '</td>' +
            '<td style="padding: 10px 14px; text-align: right;">' +
                (isAbsent ? 
                    '<span style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 800;">ĐÃ BÁO VẮNG</span>' :
                    (isPending ? 
                        '<span style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 800;">CHỜ DUYỆT VẮNG</span>' :
                        '<span style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 800;">CÓ MẶT</span>'
                    )
                ) +
            '</td>';
        tbody.appendChild(tr);
    });
}

function filterPublicModalRoster() {
    if (!currentPublicRoster || !currentPublicRoster.assigned_volunteers) return;
    var q = (document.getElementById('pub_roster_search_input').value || '').trim().toLowerCase();
    var filtered = currentPublicRoster.assigned_volunteers.filter(function(v) {
        if (!q) return true;
        var name = (v.name || '').toLowerCase();
        var id = (v.id || '').toLowerCase();
        var role = (v.role || '').toLowerCase();
        var skills = Array.isArray(v.skills) ? v.skills.join(' ').toLowerCase() : '';
        return (name.indexOf(q) !== -1 || id.indexOf(q) !== -1 || role.indexOf(q) !== -1 || skills.indexOf(q) !== -1);
    });
    renderPublicRosterTable(filtered);
}

function exportPublicRosterCSV() {
    if (!currentPublicRoster || !currentPublicRoster.assigned_volunteers) return;
    var rows = [
        ["STT", "Ma TNV", "Ho va Ten", "Vai Tro Ca", "Trang Thai", "Ky Nang"]
    ];
    currentPublicRoster.assigned_volunteers.forEach(function(v, idx) {
        rows.push([
            idx + 1,
            v.id || '',
            v.name || '',
            v.role || 'TNV Thuc Dia',
            v.status === 'absent' ? 'Bao Vang' : (v.status === 'pending_absence' ? 'Cho Duyet Vang' : 'Co Mat'),
            Array.isArray(v.skills) ? v.skills.join('; ') : ''
        ]);
    });
    var csvContent = "data:text/csv;charset=utf-8,\uFEFF" + rows.map(function(e){ return e.map(function(cell){ return '"' + ('' + cell).replace(/"/g, '""') + '"'; }).join(","); }).join("\n");
    var encodedUri = encodeURI(csvContent);
    var link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "diem_danh_" + (currentPublicRoster.shift_code || 'ca') + ".csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// =========================================================================
// MODAL QUY TRÌNH BÁO VẮNG BẮT BUỘC TRƯỚC 2 NGÀY (>= 48 GIỜ)
// =========================================================================
function parseDateDifferenceInDays(dateStr) {
    if (!dateStr) return 3.5;
    var m = dateStr.match(/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/);
    if (m) {
        var day = parseInt(m[1], 10);
        var month = parseInt(m[2], 10) - 1;
        var year = parseInt(m[3], 10);
        var eventDate = new Date(year, month, day, 8, 0, 0);
        var now = new Date();
        var diffMs = eventDate.getTime() - now.getTime();
        return diffMs / (1000 * 60 * 60 * 24);
    }
    return 3.5;
}

function openAbsenceRequestModal(act) {
    var modal = document.getElementById('modal_request_absence');
    if (!modal) return;

    document.getElementById('abs_act_title').textContent = act.title || 'Hoạt Động Tình Nguyện';
    document.getElementById('abs_shift_name').textContent = (act.code ? '[' + act.code + '] ' : '') + (act.session_name || act.day_label || 'Ca Làm Việc');
    document.getElementById('abs_leader_contact').textContent = (act.leader || 'Ban Chỉ Huy') + ' - ' + (act.leader_phone || '0912.456.789');

    // Điền hidden inputs
    document.getElementById('abs_form_shift_id').value = act.raw_shift_id || act.code || '';
    document.getElementById('abs_form_reg_id').value = act.source === 'online' ? (act.id || '') : '';
    document.getElementById('abs_form_act_title').value = act.title || '';
    document.getElementById('abs_form_event_date').value = act.day_label || '24/10/2026';
    document.getElementById('abs_form_event_time').value = act.time_range || '08:00 – 12:00';
    document.getElementById('abs_form_target_vol_name').value = act.raw_volunteer_name || '';

    // TÍNH TOÁN ĐIỀU KIỆN 2 NGÀY (>= 48 GIỜ)
    var daysRemaining = parseDateDifferenceInDays(act.day_label || act.time_range || '');
    var hoursRemaining = Math.max(0, Math.round(daysRemaining * 24));
    
    var validBox = document.getElementById('abs_condition_valid');
    var invalidBox = document.getElementById('abs_condition_invalid');
    var submitBtn = document.getElementById('btn_submit_absence');
    var daysTextValid = document.getElementById('abs_days_remaining_text');
    var hoursTextInvalid = document.getElementById('abs_hours_remaining_text');

    if (daysRemaining >= 2.0) {
        // ĐỦ ĐIỀU KIỆN: HIỆN HỘP THÔNG BÁO XANH
        validBox.style.display = 'block';
        invalidBox.style.display = 'none';
        daysTextValid.textContent = 'Còn ' + daysRemaining.toFixed(1) + ' ngày (~' + hoursRemaining + ' giờ trước ca) — Đạt yêu cầu điều lệ Đội (>= 48 giờ).';
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
        submitBtn.style.cursor = 'pointer';
    } else {
        // VI PHẠM: HIỆN CẢNH BÁO ĐỎ VÀ KHÓA NÚT GỬI ĐƠN
        validBox.style.display = 'none';
        invalidBox.style.display = 'block';
        hoursTextInvalid.textContent = 'Chỉ còn ' + hoursRemaining + ' giờ nữa ca trực sẽ diễn ra (< 48 giờ). Theo điều lệ Đội, để bảo đảm quân số thực địa, bạn không thể báo vắng trực tuyến. Vui lòng liên hệ trực tiếp Trưởng ca qua SĐT khẩn cấp để được hỗ trợ.';
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.4';
        submitBtn.style.cursor = 'not-allowed';
    }

    modal.style.display = 'flex';
}

function closeAbsenceRequestModal() {
    var modal = document.getElementById('modal_request_absence');
    if (modal) modal.style.display = 'none';
}

function escapeHtml(str) {
    if (!str) return '';
    return ('' + str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
</script>

<!-- =========================================================================
     MODAL GIAO DIỆN XEM LỰC LƯỢNG CA TRỰC (HÌNH CON MẮT TOÀN ĐỘI)
     ========================================================================= -->
<div id="public_team_roster_modal" class="modal-backdrop-pro" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.65); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: #ffffff; border-radius: 16px; max-width: 820px; width: 100%; max-height: 85vh; display: flex; flex-direction: column; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden;">
        <!-- Header Modal -->
        <div style="padding: 20px 24px; background: #0f172a; color: #ffffff; display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #059669;">
            <div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="background: #059669; color: #ffffff; font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 4px; text-transform: uppercase;">
                        DANH SÁCH LỰC LƯỢNG
                    </span>
                    <span id="pub_roster_shift_code" style="color: #38bdf8; font-weight: 800; font-family: monospace; font-size: 14px;">CA-01</span>
                </div>
                <h3 id="pub_roster_act_title" style="margin: 4px 0 0; font-size: 18px; color: #ffffff; font-weight: 700;">
                    Tên Hoạt Động
                </h3>
            </div>
            <button type="button" onclick="closePublicTeamRosterModal()" style="border: none; background: rgba(255,255,255,0.1); color: #ffffff; font-size: 22px; width: 34px; height: 34px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center;">&times;</button>
        </div>

        <!-- Meta Grid -->
        <div style="background: #f8fafc; padding: 12px 24px; border-bottom: 1px solid #e2e8f0; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; font-size: 12.5px;">
            <div>Địa bàn: <strong id="pub_roster_location" style="color: #334155;">—</strong></div>
            <div>Chỉ huy: <strong id="pub_roster_leader" style="color: #0f172a;">—</strong></div>
            <div>Quân số tiếp nhận: <strong id="pub_roster_capacity_text" style="color: #059669;">—</strong></div>
        </div>

        <!-- Toolbar tìm kiếm & Xuất CSV -->
        <div style="padding: 14px 24px; background: #ffffff; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
            <input type="text" id="pub_roster_search_input" oninput="filterPublicModalRoster()" placeholder="Lọc nhanh TNV theo tên, mã số, vai trò, kỹ năng..."
                   style="flex: 1; min-width: 240px; padding: 8px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;">
            <button type="button" onclick="exportPublicRosterCSV()" style="background: #ffffff; border: 1px solid #cbd5e1; color: #334155; padding: 8px 14px; border-radius: 8px; font-size: 12.5px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                <span>⬇</span> Xuất Điểm Danh CSV
            </button>
        </div>

        <!-- Body Bảng Lực Lượng -->
        <div style="flex: 1; overflow-y: auto; padding: 0 24px 20px;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
                <thead>
                    <tr style="background: #ffffff; border-bottom: 2px solid #e2e8f0; color: #475569; position: sticky; top: 0; z-index: 2;">
                        <th style="padding: 10px 14px; width: 45px;">#</th>
                        <th style="padding: 10px 14px;">Tình Nguyện Viên</th>
                        <th style="padding: 10px 14px; width: 140px;">Vai Trò Phụ Trách</th>
                        <th style="padding: 10px 14px; min-width: 160px;">Kỹ Năng Đáp Ứng</th>
                        <th style="padding: 10px 14px; width: 130px; text-align: right;">Trạng Thái</th>
                    </tr>
                </thead>
                <tbody id="pub_roster_table_body">
                    <!-- JavaScript điền hàng ở đây -->
                </tbody>
            </table>
        </div>

        <div style="padding: 14px 24px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end;">
            <button type="button" onclick="closePublicTeamRosterModal()" style="padding: 8px 20px; background: #0f172a; color: #ffffff; border: none; border-radius: 6px; font-weight: 700; font-size: 13px; cursor: pointer;">
                Đóng Cửa Sổ
            </button>
        </div>
    </div>
</div>

<!-- =========================================================================
     MODAL GỬI ĐƠN BÁO VẮNG (BẮT BUỘC TRƯỚC 2 NGÀY / 48 GIỜ)
     ========================================================================= -->
<div id="modal_request_absence" class="modal-backdrop-pro" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.65); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: #ffffff; border-radius: 16px; max-width: 520px; width: 100%; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden;">
        <div style="padding: 18px 24px; background: #fff1f2; border-bottom: 1px solid #fecdd3; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 18px;">📋</span>
                <strong style="color: #9f1239; font-size: 16px;">Gửi Đơn Xin Phép Vắng Mặt</strong>
            </div>
            <button type="button" onclick="closeAbsenceRequestModal()" style="border: none; background: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
        </div>

        <form method="POST" action="/lich_truc.php" style="padding: 24px;">
            <input type="hidden" name="action" value="submit_absence_approval">
            <input type="hidden" name="shift_id" id="abs_form_shift_id" value="">
            <input type="hidden" name="registration_id" id="abs_form_reg_id" value="">
            <input type="hidden" name="activity_title" id="abs_form_act_title" value="">
            <input type="hidden" name="event_date" id="abs_form_event_date" value="">
            <input type="hidden" name="event_time" id="abs_form_event_time" value="">
            <input type="hidden" name="target_volunteer_name" id="abs_form_target_vol_name" value="">

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 16px; margin-bottom: 16px; font-size: 13px;">
                <div style="font-weight: 700; color: #0f172a; margin-bottom: 4px;" id="abs_act_title">—</div>
                <div style="color: #0284c7; font-weight: 600;" id="abs_shift_name">—</div>
                <div style="font-size: 11.5px; color: #64748b; margin-top: 4px;">Chỉ huy ca: <strong id="abs_leader_contact">—</strong></div>
            </div>

            <!-- HỘP THÔNG BÁO KIỂM TRA ĐIỀU KIỆN 2 NGÀY (>= 48H) -->
            <div id="abs_condition_valid" style="display: none; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; padding: 12px 14px; margin-bottom: 16px;">
                <div style="color: #047857; font-weight: 700; font-size: 13px; display: flex; align-items: center; gap: 6px;">
                    <span>&#10003;</span> ĐỦ ĐIỀU KIỆN GỬI ĐƠN TRỰC TUYẾN
                </div>
                <div id="abs_days_remaining_text" style="color: #065f46; font-size: 12px; margin-top: 3px;">
                    Còn 3.5 ngày (~84 giờ trước ca) — Đạt yêu cầu điều lệ Đội (&ge; 48 giờ).
                </div>
            </div>

            <div id="abs_condition_invalid" style="display: none; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 12px 14px; margin-bottom: 16px;">
                <div style="color: #dc2626; font-weight: 700; font-size: 13px; display: flex; align-items: center; gap: 6px;">
                    <span>&#9888;</span> KHÔNG ĐỦ ĐIỀU KIỆN BÁO VẮNG TRỰC TUYẾN (&lt; 48 GIỜ)
                </div>
                <div id="abs_hours_remaining_text" style="color: #991b1b; font-size: 12px; margin-top: 4px; line-height: 1.45;">
                    Hoạt động diễn ra trong vòng ít hơn 48 giờ. Để bảo đảm an toàn chiến dịch, quy định Đội yêu cầu bạn liên hệ trực tiếp Trưởng ca trực.
                </div>
            </div>

            <!-- CHỌN LÝ DO XIN VẮNG -->
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                    Lý Do Báo Vắng Mặt <span style="color: #dc2626;">*</span>
                </label>
                <select name="absence_reason" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; background: #ffffff;">
                    <option value="Sức khỏe / Ốm đau đột xuất">Lý do sức khỏe / Ốm đau đột xuất</option>
                    <option value="Lịch học / Thi cử đột xuất tại trường">Lịch học / Thi cử đột xuất tại trường</option>
                    <option value="Gia đình có việc cấp bách">Gia đình có việc cấp bách / Hiếu hỷ</option>
                    <option value="Lịch công tác đột xuất">Lịch công tác / Yêu cầu cơ quan</option>
                    <option value="Lý do bất khả kháng khác">Lý do bất khả kháng khác</option>
                </select>
            </div>

            <!-- GIẢI TRÌNH CHI TIẾT -->
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                    Giải Trình Chi Tiết Cho Ban Chỉ Huy <span style="color: #dc2626;">*</span>
                </label>
                <textarea name="detail_note" required rows="3" placeholder="Mô tả cụ thể lý do vắng và đề xuất phương án (nếu có)..."
                          style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; color: #334155; line-height: 1.4;"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeAbsenceRequestModal()" style="padding: 9px 18px; border: 1px solid #cbd5e1; background: #ffffff; border-radius: 8px; font-size: 13px; cursor: pointer;">
                    Hủy Bỏ
                </button>
                <button type="submit" id="btn_submit_absence" style="padding: 9px 22px; border: none; background: #dc2626; color: #ffffff; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; box-shadow: 0 2px 6px rgba(220,38,38,0.3);">
                    Gửi Đơn Xin Phép Vắng
                </button>
            </div>
        </form>
    </div>
</div>


<?php require __DIR__ . '/../templates/footer.php'; ?>
