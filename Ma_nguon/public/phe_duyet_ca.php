<?php
declare(strict_types=1);

/**
 * TRANG QUẢN LÝ & PHÊ DUYỆT ĐĂNG KÝ CA LÀM DÀNH CHO BAN CHỈ HUY
 * D2L VOLUNTEER SHIFT REGISTRATION APPROVAL MANAGER
 * 1. Tiếp nhận và hiển thị các đơn đăng ký ca trực mới từ tình nguyện viên
 * 2. Ban Chỉ Huy thực hiện Phê Duyệt (Chấp thuận) hoặc Từ Chối kèm lý do
 * 3. Khi BCH duyệt xong, TNV xem được lịch làm ở mục Ca của tôi với trạng thái ĐÃ ĐƯỢC PHÊ DUYỆT
 */

require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/ActivityManager.php';

Auth::requireCoordinator();

$currentUser = Auth::getUser();
$flashMessage = $_SESSION['flash_msg'] ?? null;
unset($_SESSION['flash_msg']);

// =========================================================================
// XỬ LÝ HÀNH ĐỘNG CỦA BAN CHỈ HUY (PHÊ DUYỆT / TỪ CHỐI ĐĂNG KÝ CA)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = field($_POST, 'action');
    $coordName = $currentUser['name'] ?? 'Cán Bộ Chỉ Huy';

    // 1. Phê duyệt (Chấp thuận) đơn đăng ký ca
    if ($action === 'approve_registration') {
        $regId = field($_POST, 'registration_id');
        $volName = field($_POST, 'volunteer_name');
        $shiftName = field($_POST, 'shift_name');
        $note = trim(field($_POST, 'coordinator_note')) ?: 'Ban Chỉ Huy chấp thuận phân công ca làm việc.';

        if (ActivityManager::reviewShiftRegistration($regId, 'approved', $coordName, $note)) {
            $_SESSION['flash_msg'] = [
                'type' => 'success',
                'title' => 'Đã Phê Duyệt Ca Làm Việc',
                'content' => "Đã chấp thuận đơn {$regId} cho tình nguyện viên {$volName} ({$shiftName}). Tình nguyện viên có thể theo dõi ca trực chính thức tại mục Ca Của Tôi.",
            ];
        }
        header('Location: /phe_duyet_ca.php');
        exit;
    }

    // 2. Từ chối đơn đăng ký ca
    if ($action === 'reject_registration') {
        $regId = field($_POST, 'registration_id');
        $volName = field($_POST, 'volunteer_name');
        $note = trim(field($_POST, 'coordinator_note')) ?: 'Ca trực đã đủ chỉ tiêu phân công hoặc lịch trình chưa phù hợp.';

        if (ActivityManager::reviewShiftRegistration($regId, 'rejected', $coordName, $note)) {
            $_SESSION['flash_msg'] = [
                'type' => 'warning',
                'title' => 'Đã Từ Chối Đơn Đăng Ký Ca',
                'content' => "Đã từ chối đơn {$regId} của tình nguyện viên {$volName}. Lý do: {$note}.",
            ];
        }
        header('Location: /phe_duyet_ca.php');
        exit;
    }
}

// =========================================================================
// TRUY VẤN DỮ LIỆU ĐĂNG KÝ CA LÀM
// =========================================================================
$selectedStatus = field($_GET, 'status', 'all');
$searchKeyword = trim(field($_GET, 'search', ''));

$allRegistrations = ActivityManager::getAllShiftRegistrations();
$totalRequests = count($allRegistrations);
$pendingCount = count(array_filter($allRegistrations, fn($r) => ($r['status'] ?? '') === 'pending'));
$approvedCount = count(array_filter($allRegistrations, fn($r) => in_array($r['status'] ?? '', ['approved', 'confirmed'], true)));
$rejectedCount = count(array_filter($allRegistrations, fn($r) => ($r['status'] ?? '') === 'rejected'));

// Lọc theo trạng thái và từ khóa
$filteredRegistrations = array_filter($allRegistrations, function($r) use ($selectedStatus, $searchKeyword) {
    if ($selectedStatus !== 'all') {
        $st = $r['status'] ?? '';
        if ($selectedStatus === 'approved') {
            if (!in_array($st, ['approved', 'confirmed'], true)) return false;
        } elseif ($st !== $selectedStatus) {
            return false;
        }
    }
    if ($searchKeyword !== '') {
        $haystack = mb_strtolower(
            ($r['registration_id'] ?? '') . ' ' .
            ($r['user_name'] ?? '') . ' ' .
            ($r['user_phone'] ?? '') . ' ' .
            ($r['user_email'] ?? '') . ' ' .
            ($r['activity_title'] ?? '') . ' ' .
            ($r['shift_name'] ?? '') . ' ' .
            ($r['shift_id'] ?? '') . ' ' .
            ($r['note'] ?? '')
        );
        if (!str_contains($haystack, mb_strtolower($searchKeyword))) {
            return false;
        }
    }
    return true;
});

$pageTitle = 'Phê Duyệt Đăng Ký Ca Làm | Ban Chỉ Huy';
require_once __DIR__ . '/../templates/header.php';
?>

<div class="pro-page-wrapper" style="min-height: 85vh; padding-bottom: 40px;">
    <!-- BANNER BAN CHỈ HUY -->
    <div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #ffffff; padding: 24px 28px; border-radius: 14px; border-bottom: 3.5px solid #2563eb; box-shadow: 0 8px 24px rgba(15,23,42,0.12); margin: 12px 0 24px 0;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <div style="display: inline-flex; align-items: center; gap: 8px; background: rgba(37,99,235,0.25); border: 1px solid #2563eb; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 700; color: #93c5fd; margin-bottom: 8px;">
                    <span>Ban Chỉ Huy</span>
                </div>
                <h1 style="font-size: 24px; font-weight: 800; margin: 0 0 6px 0; color: #ffffff;">
                    Duyệt Đăng Ký Ca Làm
                </h1>
                <p style="font-size: 14px; color: #cbd5e1; margin: 0; max-width: 820px; line-height: 1.5;">
                    Xét duyệt đơn đăng ký ca trực của tình nguyện viên và phân công vào ca chính thức.
                </p>
            </div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="/phan_cong.php" class="btn-action-pro" style="background: #ffffff; color: #0f172a; text-decoration: none; padding: 9px 18px; border-radius: 8px; font-size: 13.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                    &larr; Bảng Phân Công
                </a>
                <a href="/phe_duyet_vang.php" class="btn-action-pro" style="background: rgba(255,255,255,0.12); color: #ffffff; text-decoration: none; padding: 9px 16px; border-radius: 8px; font-size: 13.5px; font-weight: 600; border: 1px solid rgba(255,255,255,0.2);">
                    Duyệt Báo Vắng
                </a>
                <a href="/lich_truc.php" class="btn-action-pro" style="background: rgba(255,255,255,0.12); color: #ffffff; text-decoration: none; padding: 9px 16px; border-radius: 8px; font-size: 13.5px; font-weight: 600; border: 1px solid rgba(255,255,255,0.2);">
                    Lịch Trực
                </a>
            </div>
        </div>
    </div>

    <!-- THÔNG BÁO TƯƠNG TÁC -->
        <?php if ($flashMessage): ?>
            <div class="alert alert-<?= htmlspecialchars($flashMessage['type']) ?>" role="alert" style="margin-bottom: 24px; border-radius: 10px; padding: 14px 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                <strong style="font-size: 14.5px;"><?= htmlspecialchars($flashMessage['title']) ?>:</strong>
                <span style="margin-left: 6px; font-size: 14px;"><?= htmlspecialchars($flashMessage['content']) ?></span>
            </div>
        <?php endif; ?>

        <!-- 4 THẺ THỐNG KÊ NHANH (METRIC CARDS TƯƠNG PHẢN CAO) -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px; margin-bottom: 28px;">
            <!-- Thẻ 1: Chờ Duyệt -->
            <a href="/phe_duyet_ca.php?status=pending" style="text-decoration: none; color: inherit;">
                <div class="metric-card-interactive" style="background: #ffffff; border-radius: 14px; padding: 20px 22px; border: 2px solid <?= $selectedStatus === 'pending' ? '#d97706' : '#fde68a' ?>; box-shadow: 0 4px 16px rgba(217,119,6,0.12);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-size: 13px; font-weight: 700; color: #b45309; text-transform: uppercase; letter-spacing: 0.5px;">Chờ Duyệt</span>
                        <span style="background: #fef3c7; color: #b45309; width: 34px; height: 34px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; font-size: 16px; box-shadow: 0 2px 6px rgba(217,119,6,0.15);">&#9203;</span>
                    </div>
                    <div style="font-size: 32px; font-weight: 800; color: #b45309; line-height: 1;">
                        <?= $pendingCount ?>
                    </div>
                    <span style="font-size: 12.5px; color: #92400e; margin-top: 6px; display: block;">
                        <?= $pendingCount > 0 ? '⚠ Cần phê duyệt' : '✓ Không có đơn tồn đọng' ?>
                    </span>
                </div>
            </a>

            <!-- Thẻ 2: Đã Phê Duyệt -->
            <a href="/phe_duyet_ca.php?status=approved" style="text-decoration: none; color: inherit;">
                <div class="metric-card-interactive" style="background: #ffffff; border-radius: 14px; padding: 20px 22px; border: 2px solid <?= $selectedStatus === 'approved' ? '#059669' : '#a7f3d0' ?>; box-shadow: 0 4px 16px rgba(5,150,105,0.12);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-size: 13px; font-weight: 700; color: #047857; text-transform: uppercase; letter-spacing: 0.5px;">Đã Duyệt</span>
                        <span style="background: #ecfdf5; color: #047857; width: 34px; height: 34px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; font-size: 16px; box-shadow: 0 2px 6px rgba(5,150,105,0.15);">&#10003;</span>
                    </div>
                    <div style="font-size: 32px; font-weight: 800; color: #047857; line-height: 1;">
                        <?= $approvedCount ?>
                    </div>
                    <span style="font-size: 12.5px; color: #065f46; margin-top: 6px; display: block;">
                        Đã xác nhận vào ca trực
                    </span>
                </div>
            </a>

            <!-- Thẻ 3: Đã Từ Chối -->
            <a href="/phe_duyet_ca.php?status=rejected" style="text-decoration: none; color: inherit;">
                <div class="metric-card-interactive" style="background: #ffffff; border-radius: 14px; padding: 20px 22px; border: 2px solid <?= $selectedStatus === 'rejected' ? '#dc2626' : '#fecaca' ?>; box-shadow: 0 4px 16px rgba(220,38,38,0.12);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-size: 13px; font-weight: 700; color: #b91c1c; text-transform: uppercase; letter-spacing: 0.5px;">Từ Chối</span>
                        <span style="background: #fef2f2; color: #b91c1c; width: 34px; height: 34px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; font-size: 16px; box-shadow: 0 2px 6px rgba(220,38,38,0.15);">&#10005;</span>
                    </div>
                    <div style="font-size: 32px; font-weight: 800; color: #b91c1c; line-height: 1;">
                        <?= $rejectedCount ?>
                    </div>
                    <span style="font-size: 12.5px; color: #991b1b; margin-top: 6px; display: block;">
                        Không đủ tiêu chuẩn hoặc thừa người
                    </span>
                </div>
            </a>

            <!-- Thẻ 4: Tổng Số Đơn -->
            <a href="/phe_duyet_ca.php?status=all" style="text-decoration: none; color: inherit;">
                <div class="metric-card-interactive" style="background: #ffffff; border-radius: 14px; padding: 20px 22px; border: 2px solid <?= $selectedStatus === 'all' ? '#0284c7' : '#bae6fd' ?>; box-shadow: 0 4px 16px rgba(2,132,199,0.12);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-size: 13px; font-weight: 700; color: #0369a1; text-transform: uppercase; letter-spacing: 0.5px;">Tổng Số Đăng Ký</span>
                        <span style="background: #f0f9ff; color: #0284c7; width: 34px; height: 34px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; font-size: 16px; box-shadow: 0 2px 6px rgba(2,132,199,0.15);">&#9776;</span>
                    </div>
                    <div style="font-size: 32px; font-weight: 800; color: #0369a1; line-height: 1;">
                        <?= $totalRequests ?>
                    </div>
                    <span style="font-size: 12.5px; color: #0284c7; margin-top: 6px; display: block;">
                        Toàn bộ lượt đăng ký
                    </span>
                </div>
            </a>
        </div>

        <!-- BẢNG ĐIỀU HƯỚNG VÀ TÌM KIẾM -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 22px; margin-bottom: 22px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
            <form method="GET" action="/phe_duyet_ca.php" style="display: flex; gap: 14px; align-items: center; flex-wrap: wrap;">
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
                    <a href="/phe_duyet_ca.php" style="color: #64748b; font-size: 13px; text-decoration: underline;">Xóa Lọc</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- BẢNG DANH SÁCH ĐĂNG KÝ CA TRỰC -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.04);">
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13.5px; text-align: left;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #334155; font-weight: 700;">
                            <th style="padding: 12px 14px; width: 130px; white-space: nowrap;">Mã Đơn & Giờ Gửi</th>
                            <th style="padding: 12px 14px; min-width: 175px;">Tình Nguyện Viên</th>
                            <th style="padding: 12px 14px; min-width: 220px;">Hoạt Động & Ca Trực</th>
                            <th style="padding: 12px 14px; min-width: 180px;">Ghi Chú Đăng Ký</th>
                            <th style="padding: 12px 14px; width: 140px; text-align: center; white-space: nowrap;">Trạng Thái</th>
                            <th style="padding: 12px 16px; width: 200px; text-align: right; white-space: nowrap;">Thao Tác Ban Chỉ Huy</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($filteredRegistrations)): ?>
                            <tr>
                                <td colspan="6" style="padding: 48px; text-align: center; color: #64748b;">
                                    <div style="font-size: 32px; margin-bottom: 10px;">📋</div>
                                    <strong style="font-size: 15px; color: #334155;">Không tìm thấy đơn đăng ký ca nào</strong>
                                    <p style="margin: 4px 0 0; font-size: 13px;">Thử thay đổi bộ lọc hoặc từ khóa tìm kiếm phía trên.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($filteredRegistrations as $reg): 
                                $status = $reg['status'] ?? 'pending';
                                $isPending = ($status === 'pending');
                                $isApproved = in_array($status, ['approved', 'confirmed'], true);
                                $isRejected = ($status === 'rejected');
                                $regJson = htmlspecialchars(json_encode($reg), ENT_QUOTES, 'UTF-8');
                            ?>
                                <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s ease;">
                                    <!-- Cột 1: Mã đơn & Giờ gửi -->
                                    <td style="padding: 12px 14px; vertical-align: middle; white-space: nowrap;">
                                        <div style="font-weight: 800; color: #0284c7; font-size: 13px; margin-bottom: 3px;">
                                            <?= htmlspecialchars($reg['registration_id']) ?>
                                        </div>
                                        <div style="font-size: 11.5px; color: #64748b;">
                                            <?= htmlspecialchars($reg['registered_at'] ?? '') ?>
                                        </div>
                                    </td>

                                    <!-- Cột 2: Tình nguyện viên -->
                                    <td style="padding: 12px 14px; vertical-align: middle;">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div style="width: 34px; height: 34px; border-radius: 50%; background: #059669; color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; flex-shrink: 0;">
                                                <?= mb_substr($reg['user_name'] ?? 'T', 0, 1) ?>
                                            </div>
                                            <div>
                                                <div style="font-weight: 700; color: #0f172a; font-size: 13.5px;">
                                                    <?= htmlspecialchars($reg['user_name'] ?? 'Tình Nguyện Viên') ?>
                                                </div>
                                                <div style="font-size: 12px; color: #64748b;">
                                                    <?= htmlspecialchars($reg['user_phone'] ?: ($reg['user_email'] ?: 'Chưa có SĐT')) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Cột 3: Hoạt động & Ca trực -->
                                    <td style="padding: 12px 14px; vertical-align: middle;">
                                        <div style="display: inline-block; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 11px; font-weight: 800; padding: 2px 7px; border-radius: 4px; margin-bottom: 3px;">
                                            <?= htmlspecialchars($reg['shift_id'] ?? 'CA') ?>
                                        </div>
                                        <div style="font-weight: 700; color: #1e293b; font-size: 13px; line-height: 1.35;">
                                            <?= htmlspecialchars($reg['activity_title'] ?? '') ?>
                                        </div>
                                        <div style="font-size: 12px; color: #475569; margin-top: 2px;">
                                            <?= htmlspecialchars(($reg['day_label'] ?? '') . ' · ' . ($reg['shift_name'] ?? '')) ?>
                                        </div>
                                    </td>

                                    <!-- Cột 4: Ghi chú đăng ký -->
                                    <td style="padding: 12px 14px; vertical-align: middle;">
                                        <?php if (!empty($reg['note'])): ?>
                                            <div style="font-size: 12.5px; color: #334155; font-style: italic;">
                                                "<?= htmlspecialchars($reg['note']) ?>"
                                            </div>
                                        <?php else: ?>
                                            <span style="font-size: 12px; color: #94a3b8; font-style: italic;">Không có ghi chú</span>
                                        <?php endif; ?>
                                        <?php if (!empty($reg['coordinator_note'])): ?>
                                            <div style="margin-top: 5px; font-size: 11.5px; color: #0284c7; background: #f0f9ff; padding: 4px 8px; border-radius: 4px; border-left: 2px solid #0284c7; line-height: 1.35;">
                                                <strong>Chỉ huy:</strong> <?= htmlspecialchars($reg['coordinator_note']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Cột 5: Trạng thái -->
                                    <td style="padding: 12px 14px; text-align: center; vertical-align: middle; white-space: nowrap;">
                                        <?php if ($isPending): ?>
                                            <span style="background: #fffbeb; color: #b45309; border: 1px solid #fde68a; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 800; display: inline-block; white-space: nowrap;">
                                                &#9203; CHỜ DUYỆT
                                            </span>
                                        <?php elseif ($isApproved): ?>
                                            <span style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 800; display: inline-block; white-space: nowrap;">
                                                &#10003; ĐÃ DUYỆT
                                            </span>
                                        <?php else: ?>
                                            <span style="background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 800; display: inline-block; white-space: nowrap;">
                                                &#10005; TỪ CHỐI
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Cột 6: Thao tác của Ban Chỉ Huy -->
                                    <td style="padding: 12px 16px; text-align: right; vertical-align: middle; white-space: nowrap;">
                                        <?php if ($isPending): ?>
                                            <div style="display: inline-flex; gap: 6px; justify-content: flex-end; align-items: center; flex-wrap: nowrap; white-space: nowrap;">
                                                <!-- Nút Duyệt -->
                                                <button type="button" onclick='openApproveModal(<?= $regJson ?>)'
                                                        style="height: 30px; display: inline-flex; align-items: center; justify-content: center; background: #059669; color: #ffffff; border: none; padding: 0 13px; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer; box-shadow: 0 1px 3px rgba(5,150,105,0.3); white-space: nowrap;" title="Phê duyệt đơn đăng ký ca làm">
                                                    &#10003; Duyệt
                                                </button>

                                                <!-- Nút Từ chối -->
                                                <button type="button" onclick='openRejectModal(<?= $regJson ?>)'
                                                        style="height: 30px; display: inline-flex; align-items: center; justify-content: center; background: #ffffff; color: #dc2626; border: 1px solid #dc2626; padding: 0 11px; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer; white-space: nowrap;" title="Từ chối đơn">
                                                    Từ Chối
                                                </button>
                                            </div>
                                        <?php elseif ($isApproved): ?>
                                            <span style="font-size: 12px; color: #64748b; font-style: italic; white-space: nowrap;">
                                                Bởi <?= htmlspecialchars($reg['reviewed_by'] ?? 'BCH') ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="font-size: 12px; color: #94a3b8; font-style: italic; white-space: nowrap;">
                                                Bởi <?= htmlspecialchars($reg['reviewed_by'] ?? 'BCH') ?>
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

<!-- =========================================================================
     MODAL 1: PHÊ DUYỆT ĐĂNG KÝ CA LÀM
     ========================================================================= -->
<div id="modal_approve_shift" class="modal-backdrop-pro" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: #ffffff; border-radius: 14px; max-width: 500px; width: 100%; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2); overflow: hidden;">
        <div style="padding: 18px 24px; background: #ecfdf5; border-bottom: 1px solid #d1fae5; display: flex; justify-content: space-between; align-items: center;">
            <strong style="color: #047857; font-size: 16px;">Phê Duyệt Đăng Ký Ca Làm Việc</strong>
            <button type="button" onclick="closeApproveModal()" style="border: none; background: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        <form method="POST" action="/phe_duyet_ca.php" style="padding: 24px;">
            <input type="hidden" name="action" value="approve_registration">
            <input type="hidden" name="registration_id" id="approve_reg_id" value="">
            <input type="hidden" name="volunteer_name" id="approve_vol_name_input" value="">
            <input type="hidden" name="shift_name" id="approve_shift_name_input" value="">

            <div style="margin-bottom: 16px; padding: 12px 14px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                <div style="font-size: 12px; color: #64748b; margin-bottom: 2px;">Tình nguyện viên:</div>
                <div id="approve_vol_name" style="font-weight: 700; color: #0f172a; font-size: 14px;">—</div>
                <div id="approve_shift_info" style="font-size: 12.5px; color: #475569; margin-top: 4px;">—</div>
            </div>

            <p style="font-size: 13.5px; color: #334155; margin: 0 0 14px;">
                Sau khi phê duyệt, ca trực sẽ hiển thị tại mục <strong>Ca Của Tôi</strong> của tình nguyện viên với trạng thái <strong>ĐÃ ĐƯỢC PHÊ DUYỆT</strong>.
            </p>

            <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                Ghi Chú Phê Duyệt / Hướng Dẫn Thêm (Tùy chọn)
            </label>
            <textarea name="coordinator_note" rows="2" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; color: #334155;"
                      placeholder="Ban Chỉ Huy đã phê duyệt phân công ca làm việc."></textarea>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" onclick="closeApproveModal()" style="padding: 8px 16px; border: 1px solid #cbd5e1; background: #ffffff; color: #475569; font-weight: 600; border-radius: 6px; font-size: 13px; cursor: pointer;">
                    Đóng
                </button>
                <button type="submit" style="padding: 8px 20px; border: none; background: #059669; color: #ffffff; border-radius: 6px; font-size: 13px; font-weight: 700; cursor: pointer; box-shadow: 0 2px 6px rgba(5,150,105,0.3);">
                    &#10003; Xác Nhận Phê Duyệt
                </button>
            </div>
        </form>
    </div>
</div>

<!-- =========================================================================
     MODAL 2: TỪ CHỐI ĐĂNG KÝ CA LÀM
     ========================================================================= -->
<div id="modal_reject_shift" class="modal-backdrop-pro" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: #ffffff; border-radius: 14px; max-width: 480px; width: 100%; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2); overflow: hidden;">
        <div style="padding: 18px 24px; background: #fef2f2; border-bottom: 1px solid #fee2e2; display: flex; justify-content: space-between; align-items: center;">
            <strong style="color: #991b1b; font-size: 16px;">Từ Chối Đơn Đăng Ký Ca Làm</strong>
            <button type="button" onclick="closeRejectModal()" style="border: none; background: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        <form method="POST" action="/phe_duyet_ca.php" style="padding: 24px;">
            <input type="hidden" name="action" value="reject_registration">
            <input type="hidden" name="registration_id" id="reject_reg_id" value="">
            <input type="hidden" name="volunteer_name" id="reject_vol_name_input" value="">

            <p style="font-size: 13.5px; color: #334155; margin: 0 0 14px;">
                Đồng chí đang từ chối đơn của TNV: <strong id="reject_vol_name" style="color: #0f172a;">—</strong>.
            </p>

            <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                Lý Do Từ Chối (Sẽ gửi thông báo cho Tình nguyện viên) <span style="color: #dc2626;">*</span>
            </label>
            <textarea name="coordinator_note" required rows="3" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; color: #334155;"
                      placeholder="Ví dụ: Ca trực đã đủ chỉ tiêu phân công quân số, đề nghị đồng chí đăng ký ca khác..."></textarea>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" onclick="closeRejectModal()" style="padding: 8px 16px; border: 1px solid #cbd5e1; background: #ffffff; color: #475569; font-weight: 600; border-radius: 6px; font-size: 13px; cursor: pointer;">
                    Đóng
                </button>
                <button type="submit" style="padding: 8px 18px; border: none; background: #dc2626; color: #ffffff; border-radius: 6px; font-size: 13px; font-weight: 700; cursor: pointer; box-shadow: 0 2px 6px rgba(220,38,38,0.3);">
                    Xác Nhận Từ Chối
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openApproveModal(reg) {
    document.getElementById('approve_reg_id').value = reg.registration_id || '';
    document.getElementById('approve_vol_name_input').value = reg.user_name || '';
    document.getElementById('approve_shift_name_input').value = reg.shift_name || '';
    document.getElementById('approve_vol_name').textContent = reg.user_name || 'Tình Nguyện Viên';
    document.getElementById('approve_shift_info').textContent = (reg.activity_title || '') + ' · ' + (reg.shift_name || '');
    
    var modal = document.getElementById('modal_approve_shift');
    modal.style.display = 'flex';
}

function closeApproveModal() {
    document.getElementById('modal_approve_shift').style.display = 'none';
}

function openRejectModal(reg) {
    document.getElementById('reject_reg_id').value = reg.registration_id || '';
    document.getElementById('reject_vol_name_input').value = reg.user_name || '';
    document.getElementById('reject_vol_name').textContent = reg.user_name || 'Tình Nguyện Viên';
    
    var modal = document.getElementById('modal_reject_shift');
    modal.style.display = 'flex';
}

function closeRejectModal() {
    document.getElementById('modal_reject_shift').style.display = 'none';
}

// Dong modal khi nhan ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeApproveModal();
        closeRejectModal();
    }
});

// Dong modal khi click ra ngoai vung modal
['modal_approve_shift', 'modal_reject_shift'].forEach(function(mId) {
    var el = document.getElementById(mId);
    if (el) {
        el.addEventListener('click', function(e) {
            if (e.target === el) {
                el.style.display = 'none';
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
