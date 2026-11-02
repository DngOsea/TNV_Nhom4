<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/Input.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/ActivityManager.php';
require_once __DIR__ . '/../src/Database.php';

$allActivities = ActivityManager::getAllActivities();
$selectedEventId = field($_POST, 'activity_id') ?: field($_GET, 'event_id') ?: field($_GET, 'activity_id') ?: '';
$fromPage = (int)(field($_GET, 'from_page') ?: field($_POST, 'from_page') ?: 1);
if ($fromPage < 1) $fromPage = 1;

// Nếu truy cập trực tiếp trang đăng ký mà chưa chọn hoạt động, chuyển hướng sang danh mục hoạt động để chọn trước
if ($selectedEventId === '' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: /hoat_dong.php');
    exit;
}

// Tìm hoạt động được chọn, mặc định lấy hoạt động đầu tiên nếu không có ID hợp lệ
$selectedActivity = null;
if ($selectedEventId !== '') {
    $selectedActivity = ActivityManager::findActivityById($selectedEventId);
}
if (!$selectedActivity && !empty($allActivities)) {
    $selectedActivity = $allActivities[0];
    $selectedEventId = (string)$selectedActivity['id'];
}

$errorMessage = '';
$currentUser = Auth::getUser();

// Xử lý gửi biểu mẫu đăng ký ca
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && field($_POST, 'action') === 'register_shifts') {
    $selectedShifts = isset($_POST['selected_shifts']) && is_array($_POST['selected_shifts']) ? $_POST['selected_shifts'] : [];
    $note = trim(field($_POST, 'volunteer_note'));

    $user = $currentUser;
    if (!$user) {
        $guestName = trim(field($_POST, 'guest_name'));
        $guestPhone = trim(field($_POST, 'guest_phone'));
        $guestEmail = trim(field($_POST, 'guest_email'));

        if ($guestName === '' || $guestPhone === '') {
            $errorMessage = 'Vui lòng nhập họ tên và số điện thoại để đăng ký ca trực.';
        } else {
            $user = [
                'id' => 'GUEST-' . strtoupper(substr(md5(uniqid('', true)), 0, 6)),
                'name' => $guestName,
                'phone' => $guestPhone,
                'email' => $guestEmail,
                'role' => 'Tình Nguyện Viên Khách',
                'role_type' => Auth::ROLE_VOLUNTEER,
            ];
            $_SESSION['guest_user'] = $user;
        }
    }

    if ($errorMessage === '') {
        if (empty($selectedShifts)) {
            $errorMessage = 'Vui lòng tích chọn ít nhất 1 ca làm việc từ bảng lịch bên dưới.';
        } elseif (!$selectedActivity) {
            $errorMessage = 'Không tìm thấy hoạt động cần đăng ký.';
        } else {
            $regResult = ActivityManager::registerVolunteerShifts($user, $selectedEventId, $selectedShifts, $note);
            if ($regResult['success']) {
                $_SESSION['flash_message'] = 'Đăng ký thành công ' . $regResult['count'] . ' ca trực cho hoạt động "' . ($selectedActivity['title'] ?? '') . '"! Lịch trực của bạn đã được cập nhật.';
                header('Location: /lich_truc.php?tab=my_shifts');
                exit;
            } else {
                $errorMessage = $regResult['message'] ?? 'Đăng ký không thành công. Vui lòng thử lại.';
            }
        }
    }
}

$pageTitle = 'Đăng Ký Ca Trực';
require_once __DIR__ . '/../templates/header.php';
?>

<div class="page-heading-pro">
    <div class="heading-container" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <span class="sub-badge">ĐĂNG KÝ CA TRỰC</span>
            <h1>Đăng Ký Ca Trực</h1>
            <p>Lựa chọn các ca làm việc phù hợp với lịch trình của bạn.</p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="/hoat_dong.php?page=<?= $fromPage ?>#act_<?= $selectedEventId ?>" class="btn-detail" style="padding: 10px 18px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                &larr; Quay Lại Danh Sách Hoạt Động
            </a>
            <a href="/lich_truc.php?tab=my_shifts" class="btn-primary" style="padding: 10px 18px; font-weight: 700; text-decoration: none;">
                Ca Của Tôi
            </a>
        </div>
    </div>
</div>

<div class="page-content-wrapper" style="max-width: 1200px; margin: 24px auto; padding: 0 20px;">
    <?php if ($errorMessage !== ''): ?>
        <div class="alert alert-danger" style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 14px 20px; border-radius: 10px; margin-bottom: 24px;">
            <strong>Thông báo:</strong> <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <div class="dang-ky-ca-grid">
        <!-- CỘT TRÁI: FORM ĐĂNG KÝ CA CHI TIẾT -->
        <div>
            <!-- KHỐI 1: THÔNG TIN HOẠT ĐỘNG ĐƯỢC CHỌN -->
            <?php if ($selectedActivity): 
                $catColor = '#059669';
                $catBg = '#ecfdf5';
                $catCode = 'TNV';
                $catName = $selectedActivity['category_name'] ?? 'Hoạt Động Tình Nguyện';
                $scheduleDays = $selectedActivity['schedule_days'] ?? [];
            ?>
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 22px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
                        <span style="background: <?= $catBg ?>; color: <?= $catColor ?>; padding: 4px 12px; border-radius: 16px; font-size: 12.5px; font-weight: 700; border: 1px solid <?= $catColor ?>30;">
                            [<?= htmlspecialchars($catCode) ?>] <?= htmlspecialchars($catName) ?>
                        </span>
                        
                        <!-- NÚT QUAY LẠI ĐỔI HOẠT ĐỘNG KHÁC -->
                        <div>
                            <a href="/hoat_dong.php?page=<?= $fromPage ?>#act_<?= $selectedEventId ?>" class="btn-detail" style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; font-size: 13px; font-weight: 700; border-radius: 8px; text-decoration: none; background: #f8fafc;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                                <span>Đổi Hoạt Động Khác (Quay lại danh mục)</span>
                            </a>
                        </div>
                    </div>

                    <h2 style="font-size: 20px; font-weight: 800; color: #0f172a; margin: 0 0 10px 0; line-height: 1.4;">
                        <?= htmlspecialchars($selectedActivity['title']) ?>
                    </h2>
                    <p style="font-size: 14px; color: #475569; margin: 0 0 16px 0; line-height: 1.5;">
                        <?= htmlspecialchars($selectedActivity['summary'] ?? '') ?>
                    </p>

                    <!-- LƯỚI THÔNG TIN ĐỊA BÀN & THỜI GIAN -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 16px;">
                        <div>
                            <span style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block;">Thời Gian:</span>
                            <strong style="color: #0f172a; font-size: 13.5px;"><?= htmlspecialchars($selectedActivity['date_string'] ?? 'Theo ca trực') ?></strong>
                        </div>
                        <div>
                            <span style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block;">Địa Điểm:</span>
                            <strong style="color: #0f172a; font-size: 13.5px;"><?= htmlspecialchars($selectedActivity['location'] ?? 'Tại thực địa') ?></strong>
                        </div>
                        <div>
                            <span style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block;">Tập Kết:</span>
                            <span style="color: #0f172a; font-size: 13.5px;"><?= htmlspecialchars($selectedActivity['meeting_point'] ?? 'Tại thực địa') ?></span>
                        </div>
                        <div>
                            <span style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block;">Phụ Trách:</span>
                            <span style="color: #0f172a; font-size: 13.5px;"><?= htmlspecialchars($selectedActivity['leader_name'] ?? 'Ban Điều Phối') ?> (<?= htmlspecialchars($selectedActivity['leader_phone'] ?? '0912.456.789') ?>)</span>
                        </div>
                    </div>
                </div>

                <!-- FORM ĐĂNG KÝ CA TRỰC -->
                <form method="POST" action="/dang_ky_ca.php" id="form_dang_ky_ca" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                    <input type="hidden" name="action" value="register_shifts">
                    <input type="hidden" name="activity_id" value="<?= htmlspecialchars((string)$selectedEventId) ?>">
                    <input type="hidden" name="from_page" value="<?= $fromPage ?>">

                    <!-- KHỐI 2: THÔNG TIN TÌNH NGUYỆN VIÊN -->
                    <div style="margin-bottom: 22px;">
                        <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 10px 0; display: flex; align-items: center; gap: 8px;">
                            <span style="background: #059669; color: #fff; width: 24px; height: 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px;">1</span>
                            Thông Tin Người Đăng Ký
                        </h3>

                        <?php if ($currentUser): ?>
                            <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; padding: 14px 18px; display: flex; align-items: center; gap: 14px;">
                                <div style="width: 42px; height: 42px; border-radius: 50%; background: #059669; color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 16px;">
                                    <?= mb_substr($currentUser['name'] ?? 'T', 0, 1) ?>
                                </div>
                                <div>
                                    <strong style="color: #065f46; font-size: 15px; display: block;"><?= htmlspecialchars($currentUser['name']) ?></strong>
                                    <span style="font-size: 13px; color: #047857;">Email: <?= htmlspecialchars($currentUser['email'] ?? '') ?> &bull; SĐT: <?= htmlspecialchars($currentUser['phone'] ?? 'Chưa cập nhật') ?></span>
                                    <small style="display: block; color: #059669; margin-top: 2px;">✓ Tài khoản đã xác thực. Giờ công tác sẽ được ghi nhận vào hồ sơ cá nhân.</small>
                                </div>
                            </div>
                        <?php else: ?>
                            <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 16px;">
                                <div style="margin-bottom: 12px; font-size: 13px; color: #64748b;">
                                    Bạn chưa đăng nhập. Nhập thông tin nhanh dưới đây hoặc <a href="/dang_nhap.php?redirect=/dang_ky_ca.php?event_id=<?= urlencode((string)$selectedEventId) ?>" style="color: #2563eb; font-weight: 700;">Đăng nhập</a> để tự động lưu giờ tình nguyện.
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                                    <div>
                                        <label for="guest_name" style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 4px;">Họ và tên <span style="color: #dc2626;">*</span></label>
                                        <input type="text" id="guest_name" name="guest_name" required placeholder="Ví dụ: Nguyễn Văn An" style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13.5px;">
                                    </div>
                                    <div>
                                        <label for="guest_phone" style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 4px;">Số điện thoại <span style="color: #dc2626;">*</span></label>
                                        <input type="tel" id="guest_phone" name="guest_phone" required placeholder="Ví dụ: 0912345678" style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13.5px;">
                                    </div>
                                </div>
                                <div style="margin-top: 10px;">
                                    <label for="guest_email" style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 4px;">Địa chỉ Email (tùy chọn)</label>
                                    <input type="email" id="guest_email" name="guest_email" placeholder="email@domain.com" style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13.5px;">
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- KHỐI 3: BẢNG LỰA CHỌN CA LÀM VIỆC -->
                    <div style="margin-bottom: 22px;">
                        <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 10px 0; display: flex; align-items: center; gap: 8px;">
                            <span style="background: #059669; color: #fff; width: 24px; height: 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px;">2</span>
                            Lựa Chọn Ngày & Ca Trực <span style="font-size: 13px; color: #64748b; font-weight: normal;">(Có thể chọn nhiều ca)</span>
                        </h3>

                        <?php if (!empty($scheduleDays)): ?>
                            <div style="border: 1px solid #cbd5e1; border-radius: 8px; overflow: hidden;">
                                <table style="width: 100%; border-collapse: collapse; font-size: 13.5px;">
                                    <thead>
                                        <tr style="background: #f1f5f9; border-bottom: 2px solid #cbd5e1; color: #334155; text-align: left;">
                                            <th style="padding: 11px 16px; width: 35%;">Ngày Hoạt Động</th>
                                            <th style="padding: 11px 16px; width: 65%;">Ca Trực & Khung Giờ (Tích chọn)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($scheduleDays as $day): ?>
                                            <tr style="border-bottom: 1px solid #e2e8f0;">
                                                <td style="padding: 14px 16px; vertical-align: top; background: #fafafa;">
                                                    <strong style="color: #0f172a; font-size: 14px; display: block;">
                                                        <?= htmlspecialchars($day['day_name'] ?? 'Ngày diễn ra') ?>
                                                    </strong>
                                                    <span style="color: #64748b; font-size: 12.5px;">
                                                        <?= htmlspecialchars($day['date_string'] ?? '') ?>
                                                    </span>
                                                </td>
                                                <td style="padding: 10px 16px;">
                                                    <div style="display: flex; flex-direction: column; gap: 8px;">
                                                        <?php foreach ($day['shifts'] ?? [] as $shift): 
                                                            $shiftId = $shift['shift_id'];
                                                            $quota = (int)($shift['quota'] ?? 10);
                                                            $registered = (int)($shift['registered'] ?? 0);
                                                            $remaining = max(0, $quota - $registered);
                                                            $isFull = $remaining <= 0;
                                                        ?>
                                                            <label style="display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; border: 1.5px solid <?= $isFull ? '#e2e8f0' : '#cbd5e1' ?>; border-radius: 8px; background: <?= $isFull ? '#f8fafc' : '#ffffff' ?>; cursor: <?= $isFull ? 'not-allowed' : 'pointer' ?>; transition: all 0.15s ease;">
                                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                                    <input type="checkbox" name="selected_shifts[]" value="<?= htmlspecialchars($shiftId) ?>" <?= $isFull ? 'disabled' : '' ?> style="width: 17px; height: 17px; cursor: pointer;">
                                                                    <div>
                                                                        <strong style="color: <?= $isFull ? '#94a3b8' : '#1e293b' ?>; font-size: 13.5px;"><?= htmlspecialchars($shift['shift_name']) ?></strong>
                                                                        <span style="background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 10px; font-size: 11.5px; font-weight: 600; margin-left: 6px;"><?= htmlspecialchars($shift['time_range']) ?></span>
                                                                        <?php if (!empty($shift['skill_req'])): ?>
                                                                            <span style="font-size: 11.5px; color: #64748b; margin-left: 6px;">[Ưu tiên: <?= htmlspecialchars($shift['skill_req']) ?>]</span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                                <span style="font-size: 12px; font-weight: 700; color: <?= $isFull ? '#dc2626' : '#059669' ?>; background: <?= $isFull ? '#fef2f2' : '#ecfdf5' ?>; padding: 3px 9px; border-radius: 12px;">
                                                                    <?= $isFull ? 'Đã hết chỗ' : 'Còn ' . $remaining . ' suất' ?>
                                                                </span>
                                                            </label>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <!-- FALLBACK NẾU CHƯA CÓ SCHEDULE_DAYS -->
                            <div style="border: 1px solid #cbd5e1; border-radius: 8px; padding: 16px;">
                                <?php foreach ($selectedActivity['shifts'] ?? ['Ca Sáng (08:00 - 12:00)'] as $sIdx => $sh): ?>
                                    <label style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 8px; cursor: pointer;">
                                        <input type="checkbox" name="selected_shifts[]" value="SH_<?= $selectedActivity['id'] ?>_<?= $sIdx ?>" style="width: 17px; height: 17px;">
                                        <strong style="color: #1e293b; font-size: 13.5px;"><?= htmlspecialchars($sh) ?></strong>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- KHỐI 4: GHI CHÚ NGUYỆN VỌNG -->
                    <div style="margin-bottom: 24px;">
                        <label for="volunteer_note" style="display: block; font-size: 13.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                            Ghi chú hoặc nguyện vọng phân công cụ thể (Tùy chọn):
                        </label>
                        <textarea id="volunteer_note" name="volunteer_note" rows="3" placeholder="Ví dụ: Tôi có xe máy riêng, sẵn sàng hỗ trợ chở vật dụng hoặc hậu cần sơ cứu..." style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13.5px;"></textarea>
                    </div>

                    <!-- NÚT HÀNH ĐỘNG -->
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; border-top: 1px solid #e2e8f0; padding-top: 18px;">
                        <a href="/hoat_dong.php" style="padding: 11px 20px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #475569; font-weight: 600; font-size: 14px; text-decoration: none;">
                            &larr; Hủy Bỏ
                        </a>
                        <button type="submit" style="padding: 12px 32px; border-radius: 8px; border: none; background: #059669; color: #ffffff; font-weight: 700; font-size: 15px; cursor: pointer; box-shadow: 0 4px 14px rgba(5,150,105,0.35);">
                            Xác Nhận Đăng Ký Ca Trực
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 36px; text-align: center; color: #64748b;">
                    Không tìm thấy hoạt động hợp lệ. Vui lòng quay lại <a href="/hoat_dong.php">danh sách hoạt động</a> để lựa chọn.
                </div>
            <?php endif; ?>
        </div>

        <!-- CỘT PHẢI: HƯỚNG DẪN & LIÊN HỆ ĐIỀU PHỐI -->
        <div>
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 22px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                <h3 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0 0 14px 0; border-bottom: 2px solid #059669; padding-bottom: 8px;">
                    Quy Trình Đăng Ký & Tham Gia
                </h3>
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <div style="display: flex; gap: 12px; align-items: start;">
                        <span style="background: #ecfdf5; color: #059669; font-weight: 800; width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 13px;">1</span>
                        <div>
                            <strong style="font-size: 13.5px; color: #1e293b; display: block;">Chọn Ca Trực</strong>
                            <span style="font-size: 12.5px; color: #64748b;">Tích chọn 1 hoặc nhiều ca phù hợp với thời gian biểu của bạn.</span>
                        </div>
                    </div>
                    <div style="display: flex; gap: 12px; align-items: start;">
                        <span style="background: #eff6ff; color: #2563eb; font-weight: 800; width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 13px;">2</span>
                        <div>
                            <strong style="font-size: 13.5px; color: #1e293b; display: block;">Xác Nhận & Điều Động</strong>
                            <span style="font-size: 12.5px; color: #64748b;">Hệ thống ghi nhận ca trực vào lịch công tác và đồng bộ cho Ban Chỉ Huy.</span>
                        </div>
                    </div>
                    <div style="display: flex; gap: 12px; align-items: start;">
                        <span style="background: #fffbeb; color: #d97706; font-weight: 800; width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 13px;">3</span>
                        <div>
                            <strong style="font-size: 13.5px; color: #1e293b; display: block;">Tham Gia & Điểm Danh</strong>
                            <span style="font-size: 12.5px; color: #64748b;">Có mặt đúng giờ tại điểm tập kết, quét mã QR Thẻ TNV để ghi nhận kết quả.</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- THÔNG TIN ĐIỀU PHỐI VIÊN -->
            <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; padding: 20px;">
                <h4 style="font-size: 14px; font-weight: 700; color: #334155; margin: 0 0 10px 0;">Hỗ Trợ Điều Phối</h4>
                <p style="font-size: 13px; color: #64748b; margin: 0 0 10px 0;">Nếu cần đổi ca hoặc có việc đột xuất, bạn có thể gửi đơn báo bận tại trang Lịch Trực.</p>
                <div style="font-size: 13px; color: #1e293b;">
                    <strong>Hotline trực ban:</strong> <a href="tel:0912456789" style="color: #059669; font-weight: 700;">0912.456.789</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
