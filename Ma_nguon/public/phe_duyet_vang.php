<?php
declare(strict_types=1);

/**
 * TRANG QUẢN LÝ & PHÊ DUYỆT BÁO VẮNG DÀNH CHO BAN CHỈ HUY (D2L ABSENCE APPROVAL MANAGER)
 * 1. Tiếp nhận và hiển thị các yêu cầu báo vắng từ tình nguyện viên (chỉ tiếp nhận khi gửi trước >= 2 ngày)
 * 2. Ban Chỉ Huy thực hiện Phê Duyệt (Chấp thuận) hoặc Từ Chối kèm lý do
 * 3. Hỗ trợ điều động ngay nhân sự thay thế có kỹ năng phù hợp
 */

require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/SkillManager.php';
require_once __DIR__ . '/../src/AssignmentEngine.php';
require_once __DIR__ . '/../src/ActivityManager.php';

Auth::requireCoordinator();

$db = Database::getInstance();
$currentUser = Auth::getUser();
$flashMessage = $_SESSION['flash_msg'] ?? null;
unset($_SESSION['flash_msg']);

// =========================================================================
// XỬ LÝ HÀNH ĐỘNG CỦA BAN CHỈ HUY (PHÊ DUYỆT / TỪ CHỐI / ĐIỀU ĐỘNG THAY THẾ)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = field($_POST, 'action');

    // 1. Phê duyệt (Chấp thuận) đơn xin vắng
    if ($action === 'approve_absence') {
        $reqCode = field($_POST, 'request_code');
        $shiftId = field($_POST, 'shift_id');
        $volId = field($_POST, 'volunteer_id');
        $volName = field($_POST, 'volunteer_name');
        $note = trim(field($_POST, 'coordinator_note')) ?: 'Ban Chỉ Huy chấp thuận yêu cầu báo vắng.';
        $coordName = $currentUser['name'] ?? 'Cán Bộ Chỉ Huy';

        if ($db->reviewAbsenceRequest($reqCode, 'approved', $coordName, $note)) {
            // Cập nhật trạng thái thành 'absent' trong Session lịch ca trực
            if (!empty($shiftId) && isset($_SESSION['btl_duty_schedule'][$shiftId])) {
                foreach ($_SESSION['btl_duty_schedule'][$shiftId]['assigned_volunteers'] as &$v) {
                    if (($volId !== '' && $v['id'] === $volId) || ($volName !== '' && Auth::lower($v['name']) === Auth::lower($volName))) {
                        $v['status'] = 'absent';
                        break;
                    }
                }
                unset($v);
            }

            $_SESSION['flash_msg'] = [
                'type' => 'success',
                'title' => 'Đã Phê Duyệt Yêu Cầu Báo Vắng',
                'content' => "Đã chấp thuận đơn vắng {$reqCode}. Quân số ca trực đã được cập nhật vắng mặt để điều phối nhân sự bổ sung.",
            ];
        }
        header('Location: /phe_duyet_vang.php');
        exit;
    }

    // 2. Từ chối đơn xin vắng
    if ($action === 'reject_absence') {
        $reqCode = field($_POST, 'request_code');
        $shiftId = field($_POST, 'shift_id');
        $volId = field($_POST, 'volunteer_id');
        $volName = field($_POST, 'volunteer_name');
        $note = trim(field($_POST, 'coordinator_note')) ?: 'Ca trực yêu cầu quân số tối thiểu bắt buộc, đề nghị đồng chí thu xếp có mặt.';
        $coordName = $currentUser['name'] ?? 'Cán Bộ Chỉ Huy';

        if ($db->reviewAbsenceRequest($reqCode, 'rejected', $coordName, $note)) {
            // Phục hồi trạng thái thành 'confirmed' trong Session
            if (!empty($shiftId) && isset($_SESSION['btl_duty_schedule'][$shiftId])) {
                foreach ($_SESSION['btl_duty_schedule'][$shiftId]['assigned_volunteers'] as &$v) {
                    if (($volId !== '' && $v['id'] === $volId) || ($volName !== '' && Auth::lower($v['name']) === Auth::lower($volName))) {
                        $v['status'] = 'confirmed';
                        break;
                    }
                }
                unset($v);
            }

            $_SESSION['flash_msg'] = [
                'type' => 'warning',
                'title' => 'Đã Từ Chối Yêu Cầu Báo Vắng',
                'content' => "Đã từ chối đơn {$reqCode}. Lý do: {$note}. Tình nguyện viên vẫn giữ nguyên nhiệm vụ trong ca trực.",
            ];
        }
        header('Location: /phe_duyet_vang.php');
        exit;
    }

    // 3. Phê duyệt VÀ Điều động người thay thế ngay lập tức
    if ($action === 'approve_and_replace') {
        $reqCode = field($_POST, 'request_code');
        $shiftId = field($_POST, 'shift_id');
        $absentVId = field($_POST, 'absent_volunteer_id');
        $replacementVId = field($_POST, 'replacement_volunteer_id');
        $coordName = $currentUser['name'] ?? 'Cán Bộ Chỉ Huy';

        // Lấy thông tin người thay thế từ 50 TNV
        $allVols = $db->getVolunteers();
        $replacementVol = null;
        foreach ($allVols as $v) {
            if ($v['id'] === $replacementVId) {
                $replacementVol = $v;
                break;
            }
        }

        if ($replacementVol) {
            // Chấp thuận đơn vắng trước
            $note = "Đã chấp thuận vắng. Điều động đồng chí {$replacementVol['name']} ({$replacementVId}) thay thế.";
            $db->reviewAbsenceRequest($reqCode, 'approved', $coordName, $note);

            // Bổ sung nhân sự thay thế vào ca trực trong session
            if (isset($_SESSION['btl_duty_schedule'][$shiftId])) {
                $_SESSION['btl_duty_schedule'][$shiftId]['assigned_volunteers'][] = [
                    'id' => $replacementVol['id'],
                    'name' => $replacementVol['name'],
                    'skills' => $replacementVol['skills'] ?? [],
                    'status' => 'confirmed',
                    'role' => 'TNV Thay Thế (' . $absentVId . ')',
                    'replacement_for' => $absentVId,
                ];
            }

            $_SESSION['flash_msg'] = [
                'type' => 'success',
                'title' => 'Phê Duyệt & Điều Động Thành Công',
                'content' => "Đã chấp thuận đơn vắng và điều động đồng chí {$replacementVol['name']} ({$replacementVol['id']}) đảm nhiệm vị trí tại ca {$shiftId}.",
            ];
        } else {
            $_SESSION['flash_msg'] = [
                'type' => 'danger',
                'title' => 'Lỗi Điều Động',
                'content' => 'Không tìm thấy thông tin tình nguyện viên thay thế được chọn.',
            ];
        }
        header('Location: /phe_duyet_vang.php');
        exit;
    }
}

// =========================================================================
// TRUY VẤN DỮ LIỆU ĐƠN BÁO VẮNG
// =========================================================================
$selectedStatus = field($_GET, 'status', 'all');
$searchKeyword = trim(field($_GET, 'search', ''));

$allRequests = $db->getAbsenceRequests();
$totalRequests = count($allRequests);
$pendingCount = count(array_filter($allRequests, fn($r) => ($r['status'] ?? '') === 'pending'));
$approvedCount = count(array_filter($allRequests, fn($r) => ($r['status'] ?? '') === 'approved'));
$rejectedCount = count(array_filter($allRequests, fn($r) => ($r['status'] ?? '') === 'rejected'));

// Lọc theo trạng thái và từ khóa
$filteredRequests = array_filter($allRequests, function($r) use ($selectedStatus, $searchKeyword) {
    if ($selectedStatus !== 'all' && ($r['status'] ?? '') !== $selectedStatus) {
        return false;
    }
    if ($searchKeyword !== '') {
        $haystack = mb_strtolower(
            ($r['request_code'] ?? '') . ' ' .
            ($r['volunteer_name'] ?? '') . ' ' .
            ($r['volunteer_phone'] ?? '') . ' ' .
            ($r['activity_title'] ?? '') . ' ' .
            ($r['shift_id'] ?? '') . ' ' .
            ($r['reason'] ?? '')
        );
        if (!str_contains($haystack, mb_strtolower($searchKeyword))) {
            return false;
        }
    }
    return true;
});

// Lấy danh sách 50 tình nguyện viên để làm modal thay thế
$availableVolunteers = $db->getVolunteers();

$pageTitle = 'Phê Duyệt Yêu Cầu Báo Vắng | Ban Chỉ Huy';
require_once __DIR__ . '/../templates/header.php';
?>

<div class="pro-page-wrapper" style="background: #f8fafc; min-height: 85vh; padding-bottom: 60px;">
    <!-- BANNER BAN CHỈ HUY -->
    <div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #ffffff; padding: 28px 0; border-bottom: 3px solid #dc2626;">
        <div style="max-width: 1240px; margin: 0 auto; padding: 0 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                <div>
                    <div style="display: inline-flex; align-items: center; gap: 8px; background: rgba(220,38,38,0.25); border: 1px solid #dc2626; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 700; color: #fca5a5; margin-bottom: 8px;">
                        <span>Khu Vực Quản Trị</span> &bull; <span>Ban Chỉ Huy Điều Phối</span>
                    </div>
                    <h1 style="font-size: 26px; font-weight: 800; margin: 0 0 6px 0; color: #ffffff;">
                        Thẩm Định & Phê Duyệt Yêu Cầu Báo Vắng
                    </h1>
                    <p style="font-size: 14px; color: #cbd5e1; margin: 0; max-width: 820px; line-height: 1.5;">
                        Quản lý luồng tiếp nhận đơn báo bận/vắng từ Tình nguyện viên (chỉ hợp lệ khi gửi trước ít nhất 2 ngày &ge; 48 giờ). Ban Chỉ Huy xem xét phê duyệt hoặc từ chối, và kích hoạt điều động nhân sự thay thế ngay lập tức.
                    </p>
                </div>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <a href="/phan_cong.php" class="btn-action-pro" style="background: #ffffff; color: #0f172a; text-decoration: none; padding: 9px 18px; border-radius: 8px; font-size: 13.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                        &larr; Bảng Điều Phối Lực Lượng
                    </a>
                    <a href="/lich_truc.php" class="btn-action-pro" style="background: rgba(255,255,255,0.12); color: #ffffff; text-decoration: none; padding: 9px 16px; border-radius: 8px; font-size: 13.5px; font-weight: 600; border: 1px solid rgba(255,255,255,0.2);">
                        Lịch Hoạt Động
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div style="max-width: 1240px; margin: 28px auto 0; padding: 0 20px;">
        <!-- THÔNG BÁO TƯƠNG TÁC -->
        <?php if ($flashMessage): ?>
            <div class="alert alert-<?= htmlspecialchars($flashMessage['type']) ?>" role="alert" style="margin-bottom: 24px; border-radius: 10px; padding: 14px 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                <strong style="font-size: 14.5px;"><?= htmlspecialchars($flashMessage['title']) ?>:</strong>
                <span style="margin-left: 6px; font-size: 14px;"><?= htmlspecialchars($flashMessage['content']) ?></span>
            </div>
        <?php endif; ?>

        <!-- 4 THẺ THỐNG KÊ NHANH (METRIC CARDS TƯƠNG PHẢN CAO) -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px; margin-bottom: 28px;">
            <!-- Thẻ 1: Chờ Duyệt (Màu Cam Rực Rỡ) -->
            <a href="/phe_duyet_vang.php?status=pending" style="text-decoration: none; color: inherit;">
                <div style="background: #ffffff; border-radius: 12px; padding: 20px 22px; border: 2px solid <?= $selectedStatus === 'pending' ? '#d97706' : '#fde68a' ?>; box-shadow: 0 4px 12px rgba(217,119,6,0.08); transition: transform 0.15s ease;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-size: 13px; font-weight: 700; color: #b45309; text-transform: uppercase; letter-spacing: 0.5px;">Chờ Ban Chỉ Huy Duyệt</span>
                        <span style="background: #fef3c7; color: #b45309; width: 32px; height: 32px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 16px;">&#9203;</span>
                    </div>
                    <div style="font-size: 32px; font-weight: 800; color: #b45309; line-height: 1;">
                        <?= $pendingCount ?>
                    </div>
                    <span style="font-size: 12.5px; color: #92400e; margin-top: 6px; display: block;">
                        <?= $pendingCount > 0 ? '⚠ Cần Ban Chỉ Huy xử lý gấp' : '✓ Không có đơn tồn đọng' ?>
                    </span>
                </div>
            </a>

            <!-- Thẻ 2: Đã Phê Duyệt (Màu Xanh Lá) -->
            <a href="/phe_duyet_vang.php?status=approved" style="text-decoration: none; color: inherit;">
                <div style="background: #ffffff; border-radius: 12px; padding: 20px 22px; border: 2px solid <?= $selectedStatus === 'approved' ? '#059669' : '#a7f3d0' ?>; box-shadow: 0 4px 12px rgba(5,150,105,0.08);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-size: 13px; font-weight: 700; color: #047857; text-transform: uppercase; letter-spacing: 0.5px;">Đã Chấp Thuận Vắng</span>
                        <span style="background: #ecfdf5; color: #047857; width: 32px; height: 32px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 16px;">&#10003;</span>
                    </div>
                    <div style="font-size: 32px; font-weight: 800; color: #047857; line-height: 1;">
                        <?= $approvedCount ?>
                    </div>
                    <span style="font-size: 12.5px; color: #065f46; margin-top: 6px; display: block;">
                        Đã điều phối thay thế hoặc mở suất
                    </span>
                </div>
            </a>

            <!-- Thẻ 3: Đã Từ Chối (Màu Đỏ Hồng) -->
            <a href="/phe_duyet_vang.php?status=rejected" style="text-decoration: none; color: inherit;">
                <div style="background: #ffffff; border-radius: 12px; padding: 20px 22px; border: 2px solid <?= $selectedStatus === 'rejected' ? '#dc2626' : '#fecaca' ?>; box-shadow: 0 4px 12px rgba(220,38,38,0.08);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-size: 13px; font-weight: 700; color: #b91c1c; text-transform: uppercase; letter-spacing: 0.5px;">Đã Từ Chối Đơn</span>
                        <span style="background: #fef2f2; color: #b91c1c; width: 32px; height: 32px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 16px;">&#10005;</span>
                    </div>
                    <div style="font-size: 32px; font-weight: 800; color: #b91c1c; line-height: 1;">
                        <?= $rejectedCount ?>
                    </div>
                    <span style="font-size: 12.5px; color: #991b1b; margin-top: 6px; display: block;">
                        Yêu cầu TNV tiếp tục nhiệm vụ
                    </span>
                </div>
            </a>

            <!-- Thẻ 4: Tổng Số Đơn -->
            <a href="/phe_duyet_vang.php?status=all" style="text-decoration: none; color: inherit;">
                <div style="background: #ffffff; border-radius: 12px; padding: 20px 22px; border: 2px solid <?= $selectedStatus === 'all' ? '#0284c7' : '#bae6fd' ?>; box-shadow: 0 4px 12px rgba(2,132,199,0.08);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-size: 13px; font-weight: 700; color: #0369a1; text-transform: uppercase; letter-spacing: 0.5px;">Tổng Lượt Báo Vắng</span>
                        <span style="background: #f0f9ff; color: #0284c7; width: 32px; height: 32px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 16px;">&#9776;</span>
                    </div>
                    <div style="font-size: 32px; font-weight: 800; color: #0369a1; line-height: 1;">
                        <?= $totalRequests ?>
                    </div>
                    <span style="font-size: 12.5px; color: #0284c7; margin-top: 6px; display: block;">
                        Tất cả các đợt chiến dịch
                    </span>
                </div>
            </a>
        </div>

        <!-- BẢNG ĐIỀU HƯỚNG VÀ TÌM KIẾM -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 22px; margin-bottom: 22px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
            <form method="GET" action="/phe_duyet_vang.php" style="display: flex; gap: 14px; align-items: center; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 260px;">
                    <input type="text" name="search" value="<?= htmlspecialchars($searchKeyword) ?>" placeholder="Tìm theo mã đơn, tên TNV, số điện thoại, tên hoạt động, ca trực..."
                           style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px;">
                </div>

                <div style="min-width: 180px;">
                    <select name="status" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; background: #ffffff;">
                        <option value="all" <?= $selectedStatus === 'all' ? 'selected' : '' ?>>-- Tất Cả Trạng Thái (<?= $totalRequests ?>) --</option>
                        <option value="pending" <?= $selectedStatus === 'pending' ? 'selected' : '' ?>>Chờ Duyệt (<?= $pendingCount ?>)</option>
                        <option value="approved" <?= $selectedStatus === 'approved' ? 'selected' : '' ?>>Đã Chấp Thuận (<?= $approvedCount ?>)</option>
                        <option value="rejected" <?= $selectedStatus === 'rejected' ? 'selected' : '' ?>>Đã Từ Chối (<?= $rejectedCount ?>)</option>
                    </select>
                </div>

                <button type="submit" style="background: #0f172a; color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 700; font-size: 13.5px; cursor: pointer;">
                    Lọc Danh Sách
                </button>

                <?php if ($selectedStatus !== 'all' || $searchKeyword !== ''): ?>
                    <a href="/phe_duyet_vang.php" style="color: #64748b; font-size: 13px; text-decoration: underline;">Xóa Lọc</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- BẢNG DANH SÁCH YÊU CẦU BÁO VẮNG -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.04);">
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13.5px; text-align: left;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #334155; font-weight: 700;">
                            <th style="padding: 12px 16px; width: 140px;">Mã Đơn & Giờ Gửi</th>
                            <th style="padding: 12px 16px; min-width: 190px;">Tình Nguyện Viên</th>
                            <th style="padding: 12px 16px; min-width: 220px;">Hoạt Động & Ca Trực</th>
                            <th style="padding: 12px 16px; width: 150px;">Thời Hạn Báo</th>
                            <th style="padding: 12px 16px; min-width: 200px;">Lý Do Xin Vắng</th>
                            <th style="padding: 12px 16px; width: 130px; text-align: center;">Trạng Thái</th>
                            <th style="padding: 12px 18px; width: 180px; text-align: right;">Thao Tác Ban Chỉ Huy</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($filteredRequests)): ?>
                            <tr>
                                <td colspan="7" style="padding: 48px; text-align: center; color: #64748b;">
                                    <div style="font-size: 32px; margin-bottom: 8px;">📋</div>
                                    <strong style="color: #1e293b; font-size: 15px; display: block;">Không có yêu cầu báo vắng nào phù hợp</strong>
                                    <p style="margin: 4px 0 0; font-size: 13px;">Hệ thống chưa ghi nhận đơn báo vắng nào theo tiêu chí tìm kiếm hiện tại.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($filteredRequests as $req): 
                                $status = $req['status'] ?? 'pending';
                                $isPending = ($status === 'pending');
                                $isApproved = ($status === 'approved');
                                $isRejected = ($status === 'rejected');
                                $daysPrior = (float)($req['days_prior'] ?? 0);
                                $hoursPrior = (float)($req['hours_prior'] ?? ($daysPrior * 24));
                                $reqJson = htmlspecialchars(json_encode($req, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                            ?>
                                <tr style="border-bottom: 1px solid #f1f5f9; background: <?= $isPending ? '#fffdfa' : '#ffffff' ?>; transition: background 0.15s ease;">
                                    <!-- Cột 1: Mã đơn & Giờ gửi -->
                                    <td style="padding: 14px 16px;">
                                        <span style="font-weight: 800; color: #0284c7; font-family: monospace; font-size: 13px; display: block;">
                                            <?= htmlspecialchars($req['request_code'] ?? '') ?>
                                        </span>
                                        <span style="font-size: 11.5px; color: #64748b; margin-top: 3px; display: block;">
                                            <?= htmlspecialchars($req['created_at'] ?? '') ?>
                                        </span>
                                    </td>

                                    <!-- Cột 2: Tình nguyện viên -->
                                    <td style="padding: 14px 16px;">
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span style="width: 32px; height: 32px; border-radius: 50%; background: #059669; color: #ffffff; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">
                                                <?= mb_substr($req['volunteer_name'] ?? 'T', 0, 1) ?>
                                            </span>
                                            <div>
                                                <strong style="color: #0f172a; font-size: 13.5px; display: block;">
                                                    <?= htmlspecialchars($req['volunteer_name'] ?? '') ?>
                                                </strong>
                                                <span style="font-size: 12px; color: #64748b;">
                                                    <?= htmlspecialchars($req['volunteer_phone'] ?? 'TNV-ID: ' . ($req['volunteer_id'] ?? '')) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Cột 3: Hoạt động & Ca trực -->
                                    <td style="padding: 14px 16px;">
                                        <span style="display: inline-block; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; margin-bottom: 3px;">
                                            <?= htmlspecialchars($req['shift_id'] ?? '') ?>
                                        </span>
                                        <strong style="color: #1e293b; font-size: 13px; display: block; line-height: 1.35;">
                                            <?= htmlspecialchars($req['activity_title'] ?? '') ?>
                                        </strong>
                                        <span style="font-size: 11.5px; color: #64748b;">
                                            Ngày ca: <?= htmlspecialchars($req['event_date'] ?? '') ?>
                                        </span>
                                    </td>

                                    <!-- Cột 4: Thời hạn báo (Điều kiện >= 2 ngày) -->
                                    <td style="padding: 14px 16px;">
                                        <span style="display: inline-flex; align-items: center; gap: 4px; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; padding: 3px 8px; border-radius: 6px; font-size: 12px; font-weight: 700;">
                                            &#10003; Trước <?= round($daysPrior, 1) ?> ngày
                                        </span>
                                        <span style="display: block; font-size: 11px; color: #059669; margin-top: 2px;">
                                            (~<?= round($hoursPrior, 0) ?> giờ &ge; 48h hợp lệ)
                                        </span>
                                    </td>

                                    <!-- Cột 5: Lý do vắng -->
                                    <td style="padding: 14px 16px;">
                                        <strong style="color: #334155; font-size: 12.5px; display: block;">
                                            <?= htmlspecialchars($req['reason'] ?? '') ?>
                                        </strong>
                                        <?php if (!empty($req['detail_note'])): ?>
                                            <span style="font-size: 12px; color: #64748b; font-style: italic; display: block; margin-top: 2px;">
                                                "<?= htmlspecialchars($req['detail_note']) ?>"
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($req['coordinator_note'])): ?>
                                            <div style="margin-top: 4px; font-size: 11.5px; color: #0284c7; background: #f0f9ff; padding: 3px 6px; border-radius: 4px; border-left: 2px solid #0284c7;">
                                                <strong>Chỉ huy:</strong> <?= htmlspecialchars($req['coordinator_note']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Cột 6: Trạng thái -->
                                    <td style="padding: 14px 16px; text-align: center;">
                                        <?php if ($isPending): ?>
                                            <span style="background: #fffbeb; color: #b45309; border: 1px solid #fde68a; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 800; display: inline-block;">
                                                &#9203; CHỜ DUYỆT
                                            </span>
                                        <?php elseif ($isApproved): ?>
                                            <span style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 800; display: inline-block;">
                                                &#10003; ĐÃ DUYỆT
                                            </span>
                                        <?php elseif ($status === 'cancelled_by_volunteer'): ?>
                                            <span style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 800; display: inline-block;">
                                                &#8630; TNV ĐÃ HỦY VẮNG
                                            </span>
                                        <?php else: ?>
                                            <span style="background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 800; display: inline-block;">
                                                &#10005; TỪ CHỐI
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Cột 7: Thao tác của Ban Chỉ Huy -->
                                    <td style="padding: 14px 18px; text-align: right;">
                                        <?php if ($isPending): ?>
                                            <div style="display: flex; gap: 6px; justify-content: flex-end; flex-wrap: wrap;">
                                                <!-- Nút Chấp thuận -->
                                                <form method="POST" action="/phe_duyet_vang.php" onsubmit="return confirm('Chấp thuận cho đồng chí <?= htmlspecialchars($req['volunteer_name'] ?? '') ?> vắng mặt tại ca này?')" style="margin: 0;">
                                                    <input type="hidden" name="action" value="approve_absence">
                                                    <input type="hidden" name="request_code" value="<?= htmlspecialchars($req['request_code'] ?? '') ?>">
                                                    <input type="hidden" name="shift_id" value="<?= htmlspecialchars($req['shift_id'] ?? '') ?>">
                                                    <input type="hidden" name="volunteer_id" value="<?= htmlspecialchars($req['volunteer_id'] ?? '') ?>">
                                                    <input type="hidden" name="volunteer_name" value="<?= htmlspecialchars($req['volunteer_name'] ?? '') ?>">
                                                    <button type="submit" style="background: #059669; color: #ffffff; border: none; padding: 6px 11px; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer; box-shadow: 0 1px 3px rgba(5,150,105,0.3);" title="Chấp thuận đơn vắng">
                                                        &#10003; Duyệt
                                                    </button>
                                                </form>

                                                <!-- Nút Từ chối -->
                                                <button type="button" onclick="openRejectModal('<?= htmlspecialchars($req['request_code'] ?? '') ?>', '<?= htmlspecialchars($req['volunteer_name'] ?? '') ?>', '<?= htmlspecialchars($req['shift_id'] ?? '') ?>', '<?= htmlspecialchars($req['volunteer_id'] ?? '') ?>')"
                                                        style="background: #ffffff; color: #dc2626; border: 1px solid #dc2626; padding: 6px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer;" title="Từ chối đơn">
                                                    Từ Chối
                                                </button>

                                                <!-- Nút Duyệt & Thay thế ngay -->
                                                <button type="button" onclick='openReplaceModal(<?= $reqJson ?>)'
                                                        style="background: #0284c7; color: #ffffff; border: none; padding: 6px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer;" title="Chọn nhân sự thay thế ngay">
                                                    ⇄ Thế Chân
                                                </button>
                                            </div>
                                        <?php elseif ($status === 'cancelled_by_volunteer'): ?>
                                            <span style="font-size: 12px; color: #059669; font-weight: 700;">
                                                ✓ Tiếp tục tham gia
                                            </span>
                                        <?php else: ?>
                                            <span style="font-size: 12px; color: #94a3b8; font-style: italic;">
                                                Bởi <?= htmlspecialchars($req['reviewed_by'] ?? 'BCH') ?>
                                            </span>
                                        <?php endif; ?>
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

<!-- =========================================================================
     MODAL 1: TỪ CHỐI ĐƠN BÁO VẮNG
     ========================================================================= -->
<div id="modal_reject_absence" class="modal-backdrop-pro" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: #ffffff; border-radius: 14px; max-width: 480px; width: 100%; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2); overflow: hidden;">
        <div style="padding: 18px 24px; background: #fef2f2; border-bottom: 1px solid #fee2e2; display: flex; justify-content: space-between; align-items: center;">
            <strong style="color: #991b1b; font-size: 16px;">Từ Chối Yêu Cầu Báo Vắng</strong>
            <button type="button" onclick="closeRejectModal()" style="border: none; background: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        <form method="POST" action="/phe_duyet_vang.php" style="padding: 24px;">
            <input type="hidden" name="action" value="reject_absence">
            <input type="hidden" name="request_code" id="reject_req_code" value="">
            <input type="hidden" name="shift_id" id="reject_shift_id" value="">
            <input type="hidden" name="volunteer_id" id="reject_vol_id" value="">
            <input type="hidden" name="volunteer_name" id="reject_vol_name_input" value="">

            <p style="font-size: 13.5px; color: #334155; margin: 0 0 14px;">
                Đồng chí đang từ chối đơn vắng của TNV: <strong id="reject_vol_name" style="color: #0f172a;">—</strong>.
            </p>

            <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                Lý Do Từ Chối (Sẽ gửi thông báo cho Tình nguyện viên) <span style="color: #dc2626;">*</span>
            </label>
            <textarea name="coordinator_note" required rows="3" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; color: #334155;"
                      placeholder="Ví dụ: Ca trực đặc thù đang thiếu chuyên môn, đề nghị đồng chí thu xếp có mặt đúng giờ..."></textarea>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" onclick="closeRejectModal()" style="padding: 8px 16px; border: 1px solid #cbd5e1; background: #ffffff; border-radius: 6px; font-size: 13px; cursor: pointer;">
                    Hủy
                </button>
                <button type="submit" style="padding: 8px 20px; border: none; background: #dc2626; color: #ffffff; border-radius: 6px; font-size: 13px; font-weight: 700; cursor: pointer;">
                    Xác Nhận Từ Chối
                </button>
            </div>
        </form>
    </div>
</div>

<!-- =========================================================================
     MODAL 2: DUYỆT VÀ ĐIỀU ĐỘNG NGƯỜI THAY THẾ NGAY
     ========================================================================= -->
<div id="modal_replace_absence" class="modal-backdrop-pro" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: #ffffff; border-radius: 14px; max-width: 580px; width: 100%; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2); overflow: hidden;">
        <div style="padding: 18px 24px; background: #f0fdf4; border-bottom: 1px solid #dcfce7; display: flex; justify-content: space-between; align-items: center;">
            <strong style="color: #166534; font-size: 16px;">Phê Duyệt & Điều Động Người Thay Thế</strong>
            <button type="button" onclick="closeReplaceModal()" style="border: none; background: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        <form method="POST" action="/phe_duyet_vang.php" style="padding: 24px;">
            <input type="hidden" name="action" value="approve_and_replace">
            <input type="hidden" name="request_code" id="rep_req_code" value="">
            <input type="hidden" name="shift_id" id="rep_shift_id" value="">
            <input type="hidden" name="absent_volunteer_id" id="rep_absent_vid" value="">

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; font-size: 13px;">
                <div>TNV Xin Vắng: <strong id="rep_vol_name" style="color: #dc2626;">—</strong></div>
                <div>Ca Trực Cần Bổ Sung: <strong id="rep_shift_code" style="color: #0284c7;">—</strong></div>
            </div>

            <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                Chọn Nhân Sự Thay Thế Từ Danh Sách 50 Tình Nguyện Viên: <span style="color: #dc2626;">*</span>
            </label>
            <select name="replacement_volunteer_id" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; background: #ffffff; margin-bottom: 14px;">
                <option value="">-- Chọn 1 Tình Nguyện Viên Đang Rảnh --</option>
                <?php foreach ($availableVolunteers as $vItem): 
                    $vSkills = implode(', ', (array)($vItem['skills'] ?? []));
                ?>
                    <option value="<?= htmlspecialchars($vItem['id']) ?>">
                        <?= htmlspecialchars($vItem['id']) ?> - <?= htmlspecialchars($vItem['name']) ?> (Kỹ năng: <?= htmlspecialchars($vSkills ?: 'Đa nhiệm') ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" onclick="closeReplaceModal()" style="padding: 8px 16px; border: 1px solid #cbd5e1; background: #ffffff; border-radius: 6px; font-size: 13px; cursor: pointer;">
                    Hủy
                </button>
                <button type="submit" style="padding: 8px 22px; border: none; background: #059669; color: #ffffff; border-radius: 6px; font-size: 13px; font-weight: 700; cursor: pointer; box-shadow: 0 2px 6px rgba(5,150,105,0.3);">
                    Xác Nhận & Điều Động
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openRejectModal(reqCode, volName, shiftId, volId) {
    document.getElementById('reject_req_code').value = reqCode;
    document.getElementById('reject_vol_name').textContent = volName;
    document.getElementById('reject_vol_name_input').value = volName;
    document.getElementById('reject_shift_id').value = shiftId || '';
    document.getElementById('reject_vol_id').value = volId || '';
    document.getElementById('modal_reject_absence').style.display = 'flex';
}
function closeRejectModal() {
    document.getElementById('modal_reject_absence').style.display = 'none';
}

function openReplaceModal(req) {
    document.getElementById('rep_req_code').value = req.request_code;
    document.getElementById('rep_shift_id').value = req.shift_id;
    document.getElementById('rep_absent_vid').value = req.volunteer_id;
    document.getElementById('rep_vol_name').textContent = req.volunteer_name;
    document.getElementById('rep_shift_code').textContent = req.shift_id + ' - ' + req.activity_title;
    document.getElementById('modal_replace_absence').style.display = 'flex';
}
function closeReplaceModal() {
    document.getElementById('modal_replace_absence').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>

