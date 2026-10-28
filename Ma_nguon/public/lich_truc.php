<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/../src/Input.php';
require __DIR__ . '/../src/Auth.php';
require __DIR__ . '/../src/AssignmentEngine.php';

$btlData = require __DIR__ . '/../data/btl_dataset.php';
$allSkills = $btlData['skills'];
$rawShifts = $btlData['shifts'];
$allVolunteers = $btlData['volunteers'];

// Khởi tạo trạng thái lịch trực trong Session nếu chưa có
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
$currentUser = Auth::getUser();
$flashMessage = $_SESSION['flash_msg'] ?? null;
unset($_SESSION['flash_msg']);

// =========================================================================
// XỬ LÝ HÀNH ĐỘNG BÁO VẮNG CỦA TÌNH NGUYỆN VIÊN
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = field($_POST, 'action');

    if ($action === 'volunteer_report_absent') {
        if (!Auth::isLoggedIn()) {
            $_SESSION['flash_msg'] = [
                'type' => 'warning',
                'title' => 'Yêu Cầu Đăng Nhập',
                'content' => 'Vui lòng đăng nhập để thực hiện thông báo vắng ca trực.',
            ];
            header('Location: /dang_nhap.php?redirect=' . urlencode('/lich_truc.php'));
            exit;
        }

        $shiftId = field($_POST, 'shift_id');
        $volunteerId = field($_POST, 'volunteer_id');

        // TNV chỉ được báo vắng ca trực của chính mình
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
                $_SESSION['flash_msg'] = [
                    'type' => 'success',
                    'title' => 'Đã Gửi Báo Vắng Thành Công',
                    'content' => 'Hệ thống đã ghi nhận thông báo vắng của đồng chí tại ca ' . htmlspecialchars($shiftId) . '. Ban Chỉ Huy sẽ sắp xếp nhân sự dự bị thay thế.',
                ];
            } else {
                $_SESSION['flash_msg'] = [
                    'type' => 'danger',
                    'title' => 'Từ Chối Thao Tác',
                    'content' => 'Đồng chí chỉ có thể gửi thông báo vắng đối với ca trực được phân công cho chính bản thân mình.',
                ];
            }
        }
        header('Location: /lich_truc.php');
        exit;
    }
}

// Lọc ca trực của riêng cá nhân hiện tại
$myShifts = [];
if ($currentUser) {
    $cName = Auth::lower(trim($currentUser['name'] ?? ''));
    $cId = $currentUser['id'] ?? '';

    foreach ($dutySchedules as $s) {
        foreach ($s['assigned_volunteers'] as $v) {
            $vName = Auth::lower(trim($v['name'] ?? ''));
            if (($cName !== '' && $cName === $vName) || ($cId !== '' && $cId === $v['id'])) {
                $myShifts[] = [
                    'shift' => $s,
                    'my_record' => $v,
                ];
                break;
            }
        }
    }
}

// Bộ lọc ca trực toàn đội
$selectedFilter = field($_GET, 'shift_type');
$filteredDuties = array_filter($dutySchedules, function($d) use ($selectedFilter) {
    if ($selectedFilter === '') return true;
    return stripos($d['shift_name'], $selectedFilter) !== false;
});

$activeTab = field($_GET, 'tab') ?: (count($myShifts) > 0 ? 'my_shifts' : 'all_shifts');

require __DIR__ . '/../templates/header.php';
?>

<div class="page-heading-pro">
    <div class="heading-container">
        <span class="sub-badge">LỊCH TRỰC THÀNH VIÊN</span>
        <h1>Lịch Trực & Ca Công Tác Tình Nguyện</h1>
        <p>Theo dõi lịch phân công nhiệm vụ, địa điểm tập kết và quản lý ca trực thực địa của tình nguyện viên.</p>
    </div>
</div>

<div class="page-content-wrapper">
    <!-- THÔNG BÁO TƯƠNG TÁC -->
    <?php if ($flashMessage): ?>
        <div class="alert-box-interactive alert-<?= htmlspecialchars($flashMessage['type']) ?>">
            <div class="alert-content">
                <strong><?= htmlspecialchars($flashMessage['title']) ?></strong>
                <p><?= htmlspecialchars($flashMessage['content']) ?></p>
            </div>
            <button type="button" class="btn-close-alert" onclick="this.parentElement.remove()" aria-label="Đóng">&times;</button>
        </div>
    <?php endif; ?>

    <!-- TÙY CHỌN TAB XEM LỊCH TRỰC -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px;">
        <div style="display: flex; gap: 10px;">
            <?php if (Auth::isLoggedIn()): ?>
                <a href="/lich_truc.php?tab=my_shifts" 
                   style="display: inline-block; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 0.92rem; text-decoration: none; transition: all 0.15s ease; <?= $activeTab === 'my_shifts' ? 'background: #059669; color: #ffffff;' : 'background: #f1f5f9; color: #334155;' ?>">
                    Ca Trực Của Tôi (<?= count($myShifts) ?>)
                </a>
            <?php endif; ?>
            <a href="/lich_truc.php?tab=all_shifts" 
               style="display: inline-block; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 0.92rem; text-decoration: none; transition: all 0.15s ease; <?= $activeTab === 'all_shifts' ? 'background: #059669; color: #ffffff;' : 'background: #f1f5f9; color: #334155;' ?>">
                Lịch Trực Toàn Đội (<?= count($dutySchedules) ?>)
            </a>
        </div>

        <div>
            <?php if (Auth::isCoordinator()): ?>
                <a href="/phan_cong.php" class="btn-primary" style="padding: 8px 16px; font-size: 0.85rem; text-decoration: none;">
                    Truy Cập Bảng Điều Phối Ban Chỉ Huy &rarr;
                </a>
            <?php elseif (!Auth::isLoggedIn()): ?>
                <a href="/dang_nhap.php?redirect=/lich_truc.php" class="btn-primary" style="padding: 8px 16px; font-size: 0.85rem; text-decoration: none;">
                    Đăng Nhập Xem Ca Cá Nhân &rarr;
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- =========================================================================
         TAB 1: CA TRỰC CỦA TÔI (DÀNH CHO TÌNH NGUYỆN VIÊN ĐANG ĐĂNG NHẬP)
         ========================================================================= -->
    <?php if ($activeTab === 'my_shifts'): ?>
        <?php if (!Auth::isLoggedIn()): ?>
            <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 36px; text-align: center; margin-bottom: 24px;">
                <h3 style="color: #1e3a8a; margin-bottom: 8px;">Đăng Nhập Để Xem Lịch Trực Cá Nhân</h3>
                <p style="color: #475569; max-width: 520px; margin: 0 auto 20px;">Vui lòng đăng nhập tài khoản Tình nguyện viên để hệ thống xác thực và hiển thị các ca công tác bạn được phân công.</p>
                <a href="/dang_nhap.php?redirect=/lich_truc.php" class="btn-primary" style="display: inline-block; padding: 10px 24px; text-decoration: none;">Đăng Nhập Ngay</a>
            </div>
        <?php elseif (empty($myShifts)): ?>
            <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; padding: 40px; text-align: center; margin-bottom: 24px;">
                <h3 style="color: #334155; margin-bottom: 8px;">Đồng Chí Chưa Có Ca Trực Được Phân Công</h3>
                <p style="color: #64748b; max-width: 520px; margin: 0 auto 20px;">Bạn chưa được xếp lịch vào các ca hiện tại, hoặc vừa mới gia nhập đội. Hãy đăng ký tham gia các chiến dịch mở để nhận ca trực.</p>
                <a href="/register.php" class="btn-primary" style="display: inline-block; padding: 10px 24px; text-decoration: none;">Đăng Ký Tham Gia Hoạt Động Ngay</a>
            </div>
        <?php else: ?>
            <!-- BẢNG CA TRỰC CÁ NHÂN TRỰC QUAN -->
            <div class="duty-table-container">
                <div class="duty-table-responsive">
                    <table class="duty-table-modern">
                        <thead>
                            <tr>
                                <th style="width: 28%;">Mã & Hoạt Động</th>
                                <th style="width: 16%;">Khung Giờ</th>
                                <th style="width: 18%;">Địa Điểm Tập Kết</th>
                                <th style="width: 16%;">Chỉ Huy Ca</th>
                                <th style="width: 12%;">Trạng Thái</th>
                                <th style="width: 10%; text-align: right;">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($myShifts as $item): 
                                $s = $item['shift'];
                                $my = $item['my_record'];
                                $isAbsent = ($my['status'] === 'absent');
                            ?>
                                <tr class="row-highlight-self">
                                    <td>
                                        <span class="t-shift-badge"><?= htmlspecialchars($s['shift_code']) ?></span>
                                        <div class="t-act-title"><?= htmlspecialchars($s['activity_title']) ?></div>
                                        <div style="font-size: 0.78rem; color: #059669; font-weight: 600; margin-top: 3px;">
                                            Đồng đội cùng ca: 
                                            <?php 
                                            $teammates = [];
                                            foreach ($s['assigned_volunteers'] as $tv) {
                                                if ($tv['name'] !== $my['name']) {
                                                    $teammates[] = $tv['name'];
                                                }
                                            }
                                            echo !empty($teammates) ? htmlspecialchars(implode(', ', $teammates)) : 'Đang sắp xếp';
                                            ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="t-time-pill"><?= htmlspecialchars($s['shift_name']) ?></span>
                                    </td>
                                    <td>
                                        <span style="font-weight: 500; color: #334155;"><?= htmlspecialchars($s['location']) ?></span>
                                    </td>
                                    <td>
                                        <strong class="t-leader-name"><?= htmlspecialchars($s['leader']) ?></strong>
                                        <span class="t-leader-role"><?= htmlspecialchars($s['leader_role']) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($isAbsent): ?>
                                            <span class="t-status-tag" style="background:#fee2e2; color:#dc2626;">ĐÃ BÁO VẮNG</span>
                                        <?php else: ?>
                                            <span class="t-status-tag tag-full">ĐÃ XÁC NHẬN</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <?php if (!$isAbsent): ?>
                                            <form method="POST" onsubmit="return confirm('Đồng chí có chắc chắn muốn thông báo vắng mặt tại ca này? Ban Chỉ Huy sẽ nhận được yêu cầu để điều phối người thay thế.')" style="margin: 0; display: inline-block;">
                                                <input type="hidden" name="action" value="volunteer_report_absent">
                                                <input type="hidden" name="shift_id" value="<?= htmlspecialchars($s['id']) ?>">
                                                <input type="hidden" name="volunteer_id" value="<?= htmlspecialchars($my['id']) ?>">
                                                <button type="submit" class="t-btn-action t-btn-warn" title="Gửi thông báo vắng cho ban chỉ huy">
                                                    Báo vắng
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span style="font-size: 0.76rem; color: #dc2626; font-style: italic;">Chờ điều phối</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    <!-- =========================================================================
         TAB 2: LỊCH TRỰC TOÀN ĐỘI (CHẾ ĐỘ XEM TRỰC QUAN TABLE & LIST)
         ========================================================================= -->
    <?php else: ?>
        <!-- THANH ĐIỀU KHIỂN & BỘ LỌC TÌM KIẾM -->
        <div class="table-control-bar">
            <!-- Bộ lọc ca trực -->
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <a href="/lich_truc.php?tab=all_shifts" class="tab-duty <?= $selectedFilter === '' ? 'active' : '' ?>">Tất Cả (<?= count($dutySchedules) ?>)</a>
                <a href="/lich_truc.php?tab=all_shifts&shift_type=Sáng" class="tab-duty <?= $selectedFilter === 'Sáng' ? 'active' : '' ?>">Ca Sáng</a>
                <a href="/lich_truc.php?tab=all_shifts&shift_type=Chiều" class="tab-duty <?= $selectedFilter === 'Chiều' ? 'active' : '' ?>">Ca Chiều</a>
                <a href="/lich_truc.php?tab=all_shifts&shift_type=Tối" class="tab-duty <?= $selectedFilter === 'Tối' ? 'active' : '' ?>">Ca Tối</a>
            </div>

            <!-- Ô tìm kiếm tức thời & Nút chuyển đổi View -->
            <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                <div class="table-search-box">
                    <input type="text" id="live_search_input" class="table-search-input" placeholder="Tìm hoạt động, địa điểm, chỉ huy, tên TNV..." autocomplete="off">
                </div>

                <div class="view-toggle-group">
                    <button type="button" class="btn-view-toggle active" id="btn_toggle_table" title="Xem dạng bảng chuẩn">
                        Bảng (Table)
                    </button>
                    <button type="button" class="btn-view-toggle" id="btn_toggle_list" title="Xem dạng danh sách thẻ thu gọn">
                        Danh Sách (List)
                    </button>
                </div>
            </div>
        </div>

        <!-- 1. GIAO DIỆN DẠNG BẢNG (TABLE VIEW - MẶC ĐỊNH) -->
        <div id="view_table_container" class="duty-table-container">
            <div class="duty-table-responsive">
                <table class="duty-table-modern" id="main_duty_table">
                    <thead>
                        <tr>
                            <th style="width: 22%;">Mã & Hoạt Động</th>
                            <th style="width: 14%;">Khung Giờ</th>
                            <th style="width: 16%;">Chỉ Huy Ca Trực</th>
                            <th style="width: 15%;">Quân Số Ca</th>
                            <th style="width: 23%;">Lực Lượng Phân Công</th>
                            <th style="width: 10%; text-align: right;">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filteredDuties as $duty): 
                            $sId = $duty['id'];
                            $needed = (int)$duty['needed_count'];
                            $activeAssignedCount = count(array_filter($duty['assigned_volunteers'], fn($v) => $v['status'] !== 'absent'));
                            $isFull = $activeAssignedCount >= $needed;
                            $percent = min(100, (int)round(($activeAssignedCount / max(1, $needed)) * 100));

                            // Kiểm tra xem ca này có người dùng hiện tại không
                            $hasMe = false;
                            $myVolunteerInShift = null;
                            if ($currentUser) {
                                $cName = Auth::lower(trim($currentUser['name'] ?? ''));
                                $cId = $currentUser['id'] ?? '';
                                foreach ($duty['assigned_volunteers'] as $v) {
                                    $vName = Auth::lower(trim($v['name'] ?? ''));
                                    if (($cName !== '' && $cName === $vName) || ($cId !== '' && $cId === $v['id'])) {
                                        $hasMe = true;
                                        $myVolunteerInShift = $v;
                                        break;
                                    }
                                }
                            }
                        ?>
                            <tr class="duty-data-row <?= $hasMe ? 'row-highlight-self' : '' ?>" id="shift-row-<?= htmlspecialchars($sId) ?>"
                                data-search="<?= htmlspecialchars(strtolower($duty['shift_code'] . ' ' . $duty['activity_title'] . ' ' . $duty['location'] . ' ' . $duty['leader'] . ' ' . implode(' ', array_column($duty['assigned_volunteers'], 'name')))) ?>">
                                <td>
                                    <span class="t-shift-badge"><?= htmlspecialchars($duty['shift_code']) ?></span>
                                    <div class="t-act-title"><?= htmlspecialchars($duty['activity_title']) ?></div>
                                    <span class="t-act-location"><?= htmlspecialchars($duty['location']) ?></span>
                                </td>
                                <td>
                                    <span class="t-time-pill"><?= htmlspecialchars($duty['shift_name']) ?></span>
                                </td>
                                <td>
                                    <strong class="t-leader-name"><?= htmlspecialchars($duty['leader']) ?></strong>
                                    <span class="t-leader-role"><?= htmlspecialchars($duty['leader_role']) ?></span>
                                </td>
                                <td>
                                    <div class="t-capacity-box">
                                        <div class="t-capacity-label">
                                            <span><?= $activeAssignedCount ?> / <?= $needed ?> TNV</span>
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
                                    <div class="t-v-chips-wrap">
                                        <?php foreach ($duty['assigned_volunteers'] as $v): 
                                            $isAbsent = ($v['status'] === 'absent');
                                            $isSelf = false;
                                            if ($currentUser) {
                                                $cName = Auth::lower(trim($currentUser['name'] ?? ''));
                                                $vName = Auth::lower(trim($v['name'] ?? ''));
                                                if (($cName !== '' && $cName === $vName) || (($currentUser['id'] ?? '') === $v['id'])) {
                                                    $isSelf = true;
                                                }
                                            }
                                        ?>
                                            <span class="t-v-chip <?= $isSelf ? 'chip-self' : '' ?> <?= $isAbsent ? 'chip-absent' : '' ?>" 
                                                  title="<?= $isAbsent ? 'Đã báo vắng mặt' : htmlspecialchars($v['name']) . ($isSelf ? ' (Bạn)' : '') ?>">
                                                <span class="t-v-avatar"><?= mb_substr($v['name'], 0, 1) ?></span>
                                                <span><?= htmlspecialchars($v['name']) ?><?= $isSelf ? ' (Tôi)' : '' ?></span>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td style="text-align: right;">
                                    <?php if ($hasMe && $myVolunteerInShift && $myVolunteerInShift['status'] !== 'absent'): ?>
                                        <form method="POST" style="margin: 0; display: inline-block;" onsubmit="return confirm('Xác nhận báo vắng cho ca trực của bạn?')">
                                            <input type="hidden" name="action" value="volunteer_report_absent">
                                            <input type="hidden" name="shift_id" value="<?= htmlspecialchars($sId) ?>">
                                            <input type="hidden" name="volunteer_id" value="<?= htmlspecialchars($myVolunteerInShift['id']) ?>">
                                            <button type="submit" class="t-btn-action t-btn-warn" title="Báo vắng ca trực của tôi">
                                                Báo vắng
                                            </button>
                                        </form>
                                    <?php elseif ($hasMe && $myVolunteerInShift && $myVolunteerInShift['status'] === 'absent'): ?>
                                        <span style="font-size: 0.76rem; color: #dc2626; font-style: italic;">Đã báo vắng</span>
                                    <?php else: ?>
                                        <span style="font-size: 0.78rem; color: #94a3b8;">Đang trực</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 2. GIAO DIỆN DẠNG DANH SÁCH THẺ THU GỌN (LIST VIEW) -->
        <div id="view_list_container" class="duty-compact-list" style="display: none;">
            <?php foreach ($filteredDuties as $duty): 
                $sId = $duty['id'];
                $needed = (int)$duty['needed_count'];
                $activeAssignedCount = count(array_filter($duty['assigned_volunteers'], fn($v) => $v['status'] !== 'absent'));
                $isFull = $activeAssignedCount >= $needed;

                $hasMe = false;
                $myVolunteerInShift = null;
                if ($currentUser) {
                    $cName = Auth::lower(trim($currentUser['name'] ?? ''));
                    $cId = $currentUser['id'] ?? '';
                    foreach ($duty['assigned_volunteers'] as $v) {
                        $vName = Auth::lower(trim($v['name'] ?? ''));
                        if (($cName !== '' && $cName === $vName) || ($cId !== '' && $cId === $v['id'])) {
                            $hasMe = true;
                            $myVolunteerInShift = $v;
                            break;
                        }
                    }
                }
            ?>
                <div class="compact-shift-card <?= $hasMe ? 'card-highlight-self' : '' ?>" id="shift-card-<?= htmlspecialchars($sId) ?>"
                     data-search="<?= htmlspecialchars(strtolower($duty['shift_code'] . ' ' . $duty['activity_title'] . ' ' . $duty['location'] . ' ' . $duty['leader'] . ' ' . implode(' ', array_column($duty['assigned_volunteers'], 'name')))) ?>">
                    <div class="compact-main-info">
                        <span class="t-shift-badge"><?= htmlspecialchars($duty['shift_code']) ?></span>
                        <h4 class="t-act-title" style="margin: 4px 0;"><?= htmlspecialchars($duty['activity_title']) ?></h4>
                        <span class="t-act-location"><?= htmlspecialchars($duty['location']) ?></span>
                    </div>

                    <div class="compact-meta-info">
                        <div><strong>Khung giờ:</strong> <?= htmlspecialchars($duty['shift_name']) ?></div>
                        <div><strong>Chỉ huy:</strong> <?= htmlspecialchars($duty['leader']) ?> (<em><?= htmlspecialchars($duty['leader_role']) ?></em>)</div>
                        <div><strong>Quân số:</strong> <span class="t-status-tag <?= $isFull ? 'tag-full' : 'tag-need' ?>"><?= $activeAssignedCount ?>/<?= $needed ?> TNV</span></div>
                    </div>

                    <div class="compact-volunteers-info">
                        <div style="font-size: 0.78rem; font-weight: 700; color: #475569; margin-bottom: 5px;">Lực lượng trực ca:</div>
                        <div class="t-v-chips-wrap">
                            <?php foreach ($duty['assigned_volunteers'] as $v): 
                                $isAbsent = ($v['status'] === 'absent');
                                $isSelf = false;
                                if ($currentUser) {
                                    $cName = Auth::lower(trim($currentUser['name'] ?? ''));
                                    $vName = Auth::lower(trim($v['name'] ?? ''));
                                    if (($cName !== '' && $cName === $vName) || (($currentUser['id'] ?? '') === $v['id'])) {
                                        $isSelf = true;
                                    }
                                }
                            ?>
                                <span class="t-v-chip <?= $isSelf ? 'chip-self' : '' ?> <?= $isAbsent ? 'chip-absent' : '' ?>">
                                    <span class="t-v-avatar"><?= mb_substr($v['name'], 0, 1) ?></span>
                                    <span><?= htmlspecialchars($v['name']) ?><?= $isSelf ? ' (Tôi)' : '' ?></span>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="compact-actions">
                        <?php if ($hasMe && $myVolunteerInShift && $myVolunteerInShift['status'] !== 'absent'): ?>
                            <form method="POST" style="margin: 0;" onsubmit="return confirm('Xác nhận báo vắng cho ca trực của bạn?')">
                                <input type="hidden" name="action" value="volunteer_report_absent">
                                <input type="hidden" name="shift_id" value="<?= htmlspecialchars($sId) ?>">
                                <input type="hidden" name="volunteer_id" value="<?= htmlspecialchars($myVolunteerInShift['id']) ?>">
                                <button type="submit" class="t-btn-action t-btn-warn">
                                    Báo vắng
                                </button>
                            </form>
                        <?php elseif ($hasMe && $myVolunteerInShift && $myVolunteerInShift['status'] === 'absent'): ?>
                            <span style="font-size: 0.8rem; color: #dc2626; font-style: italic;">Đã báo vắng</span>
                        <?php else: ?>
                            <span style="font-size: 0.8rem; color: #059669; font-weight: 600;">Đã xác nhận</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- THÔNG BÁO KHI TÌM KIẾM KHÔNG CÓ KẾT QUẢ -->
        <div id="no_search_result" style="display: none; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; padding: 36px; text-align: center; margin-bottom: 24px;">
            <p style="color: #64748b; font-size: 0.95rem; margin: 0;">Không tìm thấy ca trực nào phù hợp với từ khóa tìm kiếm của bạn.</p>
        </div>

        <!-- JAVASCRIPT ĐIỀU KHIỂN BỘ LỌC TỨC THÌ & CHUYỂN ĐỔI CHẾ ĐỘ XEM -->
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var btnTable = document.getElementById('btn_toggle_table');
            var btnList = document.getElementById('btn_toggle_list');
            var viewTable = document.getElementById('view_table_container');
            var viewList = document.getElementById('view_list_container');
            var searchInput = document.getElementById('live_search_input');
            var noResult = document.getElementById('no_search_result');

            // 1. Chuyển đổi View Mode
            if (btnTable && btnList && viewTable && viewList) {
                btnTable.addEventListener('click', function() {
                    btnTable.classList.add('active');
                    btnList.classList.remove('active');
                    viewTable.style.display = '';
                    viewList.style.display = 'none';
                    try { localStorage.setItem('d2l_schedule_view', 'table'); } catch(e){}
                });

                btnList.addEventListener('click', function() {
                    btnList.classList.add('active');
                    btnTable.classList.remove('active');
                    viewTable.style.display = 'none';
                    viewList.style.display = 'flex';
                    try { localStorage.setItem('d2l_schedule_view', 'list'); } catch(e){}
                });

                // Khôi phục tùy chọn đã lưu
                try {
                    var savedView = localStorage.getItem('d2l_schedule_view');
                    if (savedView === 'list') {
                        btnList.click();
                    }
                } catch(e){}
            }

            // 2. Tìm kiếm tức thời (Real-time Live Search)
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    var query = this.value.trim().toLowerCase();
                    var rows = document.querySelectorAll('.duty-data-row');
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
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
