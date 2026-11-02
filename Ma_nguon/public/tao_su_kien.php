<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/Input.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/SkillManager.php';
require_once __DIR__ . '/../src/ActivityManager.php';
require_once __DIR__ . '/../src/LocationManager.php';

// Chỉ người điều phối (Ban Chỉ Huy) mới có quyền truy cập
$currentUser = Auth::requireCoordinator('/dang_nhap.php');

$errorMsg = '';
$successMsg = '';

// Danh mục các ca làm việc cố định
$fixedShiftsConfig = [
    'sang' => [
        'name' => 'Ca Sáng',
        'time' => '08:00 – 12:00',
        'default_cap' => 15,
        'badge' => 'Sáng',
        'badge_bg' => '#eff6ff',
        'badge_color' => '#1d4ed8',
        'skill_req' => 'Hậu cần khuân vác, Thể lực tốt',
        'skill_group' => 'hau_can'
    ],
    'chieu' => [
        'name' => 'Ca Chiều',
        'time' => '13:30 – 17:30',
        'default_cap' => 15,
        'badge' => 'Chiều',
        'badge_bg' => '#fef3c7',
        'badge_color' => '#b45309',
        'skill_req' => 'Hỗ trợ điều phối, Hướng dẫn',
        'skill_group' => 'dieu_phoi'
    ],
    'toi' => [
        'name' => 'Ca Tối',
        'time' => '18:00 – 21:30',
        'default_cap' => 10,
        'badge' => 'Tối',
        'badge_bg' => '#f3e8ff',
        'badge_color' => '#6b21a8',
        'skill_req' => 'Chăm sóc, Tiếp đón tình nguyện',
        'skill_group' => 'cham_soc'
    ],
    'dem' => [
        'name' => 'Ca Đêm',
        'time' => '21:00 – 00:30',
        'default_cap' => 10,
        'badge' => 'Đêm',
        'badge_bg' => '#e0e7ff',
        'badge_color' => '#3730a3',
        'skill_req' => 'Trực đêm, Cứu trợ khẩn cấp, Tuần tra',
        'skill_group' => 'hau_can'
    ],
    'ca_ngay' => [
        'name' => 'Cả Ngày',
        'time' => '08:00 – 17:00',
        'default_cap' => 20,
        'badge' => 'Toàn Thời Gian',
        'badge_bg' => '#ecfdf5',
        'badge_color' => '#047857',
        'skill_req' => 'Nhiệt tình, Tham gia trọn vẹn',
        'skill_group' => 'hau_can'
    ]
];

$weekdaysMap = [
    0 => 'Chủ Nhật',
    1 => 'Thứ Hai',
    2 => 'Thứ Ba',
    3 => 'Thứ Tư',
    4 => 'Thứ Năm',
    5 => 'Thứ Sáu',
    6 => 'Thứ Bảy',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim(field($_POST, 'title'));
    $category = field($_POST, 'category');

    $locRegion = trim(field($_POST, 'loc_region', 'hanoi'));
    $locProvince = trim(field($_POST, 'loc_province', 'Hà Nội'));
    $locDistrict = trim(field($_POST, 'loc_district', ''));
    $locDetail = trim(field($_POST, 'loc_detail', ''));

    $location = trim(field($_POST, 'location'));
    if (empty($location)) {
        $parts = array_filter([$locDetail, $locDistrict, $locProvince]);
        $location = implode(', ', $parts);
    }

    $meetingPoint = trim(field($_POST, 'meeting_point'));
    $leaderName = trim(field($_POST, 'leader_name')) ?: $currentUser['name'];
    $leaderPhone = trim(field($_POST, 'leader_phone')) ?: ($currentUser['phone'] ?? '0912.456.789');
    $summary = trim(field($_POST, 'summary'));

    // Đọc danh sách các ngày và các ca được chọn từ form
    $daysPost = $_POST['days'] ?? [];
    $scheduleDays = [];

    if (empty($title)) {
        $errorMsg = 'Vui lòng nhập tên hoạt động tình nguyện.';
    } elseif (empty($location)) {
        $errorMsg = 'Vui lòng nhập địa điểm tổ chức hoạt động.';
    } elseif (empty($daysPost) || !is_array($daysPost)) {
        $errorMsg = 'Vui lòng chọn ít nhất một ngày tổ chức trên lịch.';
    } else {
        $dayCounter = 1;
        foreach ($daysPost as $dIdx => $dData) {
            $dateRaw = trim($dData['date'] ?? '');
            if (empty($dateRaw)) {
                continue;
            }

            $ts = strtotime($dateRaw);
            if ($ts === false) {
                continue;
            }

            $dayOfWeekNum = (int)date('w', $ts);
            $dayName = $weekdaysMap[$dayOfWeekNum] ?? ('Ngày ' . $dayCounter);
            $dateFormatted = date('d/m/Y', $ts);

            $shiftsPost = $dData['shifts'] ?? [];
            $shiftsList = [];
            $shiftIndex = 1;

            foreach ($fixedShiftsConfig as $sKey => $sDef) {
                $shiftInput = $shiftsPost[$sKey] ?? null;
                // Nếu ca này được tích chọn
                if (!empty($shiftInput['active'])) {
                    $cap = max(1, (int)($shiftInput['capacity'] ?? $sDef['default_cap']));
                    $shiftsList[] = [
                        'shift_id' => 'ACT-NEW-D' . $dayCounter . '-S' . $shiftIndex,
                        'shift_name' => $sDef['name'],
                        'time_range' => $sDef['time'],
                        'capacity' => $cap,
                        'registered' => 0,
                        'skill_req' => $sDef['skill_req'],
                        'skill_group' => $sDef['skill_group'],
                        'day_name' => $dayName
                    ];
                    $shiftIndex++;
                }
            }

            if (!empty($shiftsList)) {
                $scheduleDays[] = [
                    'day_code' => 'day_' . $dayCounter,
                    'day_name' => $dayName,
                    'date_string' => $dateFormatted,
                    'full_label' => $dayName . ' (' . $dateFormatted . ')',
                    'shifts' => $shiftsList,
                ];
                $dayCounter++;
            }
        }

        if (empty($scheduleDays)) {
            $errorMsg = 'Vui lòng chọn ngày trên lịch và tích chọn ít nhất một ca trực (Sáng, Chiều hoặc Tối).';
        } else {
            $categoriesList = [
                'thuc_te_moi_truong' => 'Hoạt Động Thực Địa & Môi Trường Xanh',
                'hien_mau_y_te' => 'Hiến Máu Nhân Đạo & Hỗ Trợ Y Tế',
                'tiep_suc_giao_duc' => 'Tiếp Sức Mùa Thi & Giáo Dục Cộng Đồng',
                'cuu_tro_an_sinh' => 'Cứu Trợ Khẩn Cấp & Bếp Ăn 0 Đồng',
                'cham_soc_yeu_thuong' => 'Chăm Sóc Người Cao Tuổi & Trẻ Em Khuyết Tật',
            ];

            $dateOverview = count($scheduleDays) === 1
                ? ($scheduleDays[0]['full_label'])
                : (count($scheduleDays) . ' ngày (' . implode(', ', array_column($scheduleDays, 'date_string')) . ')');

            $newActivity = ActivityManager::createCustomActivity([
                'title' => $title,
                'category' => $category,
                'category_name' => $categoriesList[$category] ?? 'Hoạt Động Tình Nguyện',
                'date_string' => $dateOverview,
                'location' => $location,
                'meeting_point' => $meetingPoint,
                'leader_name' => $leaderName,
                'leader_phone' => $leaderPhone,
                'summary' => $summary,
                'schedule_days' => $scheduleDays,
                'capacity' => array_sum(array_map(fn($d) => array_sum(array_column($d['shifts'], 'capacity')), $scheduleDays)),
            ]);

            $from = field($_POST, 'from', field($_GET, 'from', ''));
            $_SESSION['flash_message'] = 'Đã tạo thành công hoạt động: "' . $title . '" và mở các ca trực cho tình nguyện viên đăng ký!';

            if ($from === 'admin') {
                header('Location: /admin_import.php?tab=projects&created=1');
            } else {
                header('Location: /hoat_dong.php?created_id=' . $newActivity['id'] . '#act_' . $newActivity['id']);
            }
            exit;
        }
    }
}

// Ngày mặc định ban đầu: Ngày mai
$defaultDate1 = date('Y-m-d', strtotime('+2 days'));

require __DIR__ . '/../templates/header.php';
?>

<div class="page-heading-pro">
    <div class="heading-container">
        <span class="sub-badge">ĐIỀU PHỐI HOẠT ĐỘNG</span>
        <h1>Tạo Hoạt Động Mới</h1>
        <p>Lên kế hoạch tổ chức hoạt động và mở ca trực cho tình nguyện viên đăng ký.</p>
    </div>
</div>

<div class="page-content-wrapper">
    <?php if ($errorMsg !== ''): ?>
        <div class="alert-error-box" role="alert" style="background: #fef2f2; border: 1.5px solid #ef4444; border-radius: 10px; padding: 14px 18px; margin-bottom: 24px; display: flex; align-items: center; gap: 10px;">
            <span style="background: #dc2626; color: #ffffff; width: 22px; height: 22px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700;">!</span>
            <span style="color: #991b1b; font-size: 0.95rem; font-weight: 600;"><?= htmlspecialchars($errorMsg) ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="/tao_su_kien.php<?= !empty($_GET['from']) ? '?from=' . htmlspecialchars($_GET['from']) : '' ?>" class="pro-event-form">
        <input type="hidden" name="from" value="<?= htmlspecialchars($_GET['from'] ?? '') ?>">

        <!-- PHẦN 1: THÔNG TIN HOẠT ĐỘNG -->
        <div class="form-section-block" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
            <div class="section-block-title" style="margin-bottom: 18px; display: flex; align-items: center; gap: 10px;">
                <span class="num-step" style="background: #059669; color: #ffffff; width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800;">1</span>
                <div>
                    <h3 style="margin: 0; font-size: 1.15rem; color: #0f172a; font-weight: 800;">Thông Tin Hoạt Động</h3>
                    <p style="margin: 2px 0 0; color: #64748b; font-size: 0.88rem;">Tên hoạt động, chủ đề và địa điểm diễn ra.</p>
                </div>
            </div>

            <div class="form-row-2">
                <div class="form-field-pro">
                    <label for="title">Tên hoạt động <strong class="req" style="color: #dc2626;">*</strong></label>
                    <input type="text" id="title" name="title" required placeholder="Ví dụ: Ngày Hội Sống Xanh Thủ Đô — Dọn Rác & Cải Tạo Ven Kênh" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                </div>

                <div class="form-field-pro">
                    <label for="category">Nhóm chủ đề <strong class="req" style="color: #dc2626;">*</strong></label>
                    <select id="category" name="category" required>
                        <option value="thuc_te_moi_truong">[Môi Trường] Hoạt Động Thực Địa & Môi Trường Xanh</option>
                        <option value="hien_mau_y_te">[Y Tế] Hiến Máu Nhân Đạo & Hỗ Trợ Y Tế</option>
                        <option value="tiep_suc_giao_duc">[Giáo Dục] Tiếp Sức Mùa Thi & Giáo Dục Cộng Đồng</option>
                        <option value="cuu_tro_an_sinh">[An Sinh] Cứu Trợ Khẩn Cấp & Bếp Ăn 0 Đồng</option>
                        <option value="cham_soc_yeu_thuong">[Xã Hội] Chăm Sóc Người Cao Tuổi & Trẻ Em Khuyết Tật</option>
                    </select>
                </div>
            </div>

            <!-- CẤU TRÚC PHÂN LOẠI ĐỊA BÀN & ĐỊA ĐIỂM (ĐỒNG BỘ TOÀN HỆ THỐNG) -->
            <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 18px; margin-top: 16px;">
                <div style="font-size: 0.9rem; font-weight: 800; color: #0f172a; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                    <span style="color: #059669; font-size: 1.1rem;">📍</span>
                    <span>Phân Loại Địa Bàn & Địa Điểm Tổ Chức <strong class="req" style="color: #dc2626;">*</strong></span>
                </div>

                <div class="form-row-2">
                    <div class="form-field-pro">
                        <label for="loc_region">Khu vực / Vùng miền <strong class="req" style="color: #dc2626;">*</strong></label>
                        <select id="loc_region" name="loc_region" onchange="onRegionChange(this.value)" style="background: #ffffff;">
                            <option value="hanoi" <?= (($_POST['loc_region'] ?? '') === 'hanoi') ? 'selected' : '' ?>>Hà Nội (Thủ đô)</option>
                            <option value="north" <?= (($_POST['loc_region'] ?? '') === 'north') ? 'selected' : '' ?>>Miền Bắc (Tây Bắc & Đông Bắc)</option>
                            <option value="central" <?= (($_POST['loc_region'] ?? '') === 'central') ? 'selected' : '' ?>>Miền Trung & Tây Nguyên</option>
                            <option value="south" <?= (($_POST['loc_region'] ?? '') === 'south') ? 'selected' : '' ?>>Miền Nam (TP.HCM & Miền Tây)</option>
                        </select>
                    </div>

                    <div class="form-field-pro">
                        <label for="loc_province">Tỉnh / Thành phố <strong class="req" style="color: #dc2626;">*</strong></label>
                        <select id="loc_province" name="loc_province" onchange="syncLocationField()" style="background: #ffffff;">
                            <!-- Sẽ được JS tự động đổ dữ liệu theo Vùng miền -->
                        </select>
                    </div>
                </div>

                <div class="form-row-2" style="margin-top: 12px;">
                    <div class="form-field-pro">
                        <label for="loc_district">Quận / Huyện / Thị xã</label>
                        <input type="text" id="loc_district" name="loc_district" placeholder="Ví dụ: Cầu Giấy, Mù Cang Chải, Bình Tân..." value="<?= htmlspecialchars($_POST['loc_district'] ?? '') ?>" oninput="syncLocationField()">
                    </div>
                    <div class="form-field-pro">
                        <label for="loc_detail">Tuyến đường / Địa chỉ cụ thể</label>
                        <input type="text" id="loc_detail" name="loc_detail" placeholder="Ví dụ: Công viên Cầu Giấy, Dọc tuyến kênh Tô Lịch..." value="<?= htmlspecialchars($_POST['loc_detail'] ?? '') ?>" oninput="syncLocationField()">
                    </div>
                </div>

                <div class="form-row-2" style="margin-top: 12px;">
                    <div class="form-field-pro">
                        <label for="location">Địa chỉ tổng hợp (tự động chuẩn hóa hiển thị)</label>
                        <input type="text" id="location" name="location" readonly style="background: #ffffff; border: 1.5px solid #10b981; color: #0f172a; font-weight: 700;" value="<?= htmlspecialchars($_POST['location'] ?? 'Công viên Cầu Giấy, Cầu Giấy, Hà Nội') ?>">
                    </div>
                    <div class="form-field-pro">
                        <label for="meeting_point">Điểm hẹn tập trung</label>
                        <input type="text" id="meeting_point" name="meeting_point" placeholder="Ví dụ: Cổng chính Công viên lúc 07:30" value="<?= htmlspecialchars($_POST['meeting_point'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="form-row-2" style="margin-top: 14px;">
                <div class="form-field-pro">
                    <label for="leader_name">Người phụ trách</label>
                    <input type="text" id="leader_name" name="leader_name" value="<?= htmlspecialchars($_POST['leader_name'] ?? $currentUser['name']) ?>">
                </div>
                <div class="form-field-pro">
                    <label for="leader_phone">Số điện thoại liên hệ</label>
                    <input type="tel" id="leader_phone" name="leader_phone" value="<?= htmlspecialchars($_POST['leader_phone'] ?? ($currentUser['phone'] ?? '0912.456.789')) ?>">
                </div>
            </div>

            <div class="form-row-1" style="margin-top: 14px;">
                <div class="form-field-pro">
                    <label for="summary">Mô tả tóm tắt nội dung công việc</label>
                    <textarea id="summary" name="summary" rows="2" placeholder="Ghi chú ngắn gọn về mục tiêu và nội dung chính để tình nguyện viên nắm rõ..."><?= htmlspecialchars($_POST['summary'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- PHẦN 2: THỜI GIAN & CA TRỰC -->
        <div class="form-section-block" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
            <div class="section-block-title" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="num-step" style="background: #059669; color: #ffffff; width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800;">2</span>
                    <div>
                        <h3 style="margin: 0; font-size: 1.15rem; color: #0f172a; font-weight: 800;">Thời Gian & Ca Trực</h3>
                        <p style="margin: 2px 0 0; color: #64748b; font-size: 0.88rem;">Chọn ngày trên lịch và chọn ca trực cần tuyển tình nguyện viên.</p>
                    </div>
                </div>
                <button type="button" class="btn-primary" id="btn_add_day" style="padding: 8px 16px; font-size: 0.88rem; background: #059669; border-color: #059669; border-radius: 8px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                    <span>&#10010;</span> Thêm Ngày Hoạt Động
                </button>
            </div>

            <div id="days_container">
                <!-- NGÀY 1 (MẶC ĐỊNH) -->
                <div class="day-setup-card" data-day-index="0" style="background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 12px; padding: 18px 20px; margin-bottom: 20px;">
                    <!-- THANH CHỌN LỊCH NGÀY THÁNG NĂM -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px dashed #cbd5e1; padding-bottom: 12px; flex-wrap: wrap; gap: 10px;">
                        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                            <span style="font-size: 1.25rem;">&#128197;</span>
                            <strong style="color: #0f172a; font-size: 0.98rem;">Chọn ngày tổ chức:</strong>
                            <input type="date" name="days[0][date]" class="input-day-date" value="<?= $defaultDate1 ?>" required onchange="updateDayWeekdayBadge(this)" style="padding: 7px 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 0.92rem; font-weight: 600; background: #ffffff; color: #0f172a;">
                            <span class="day-weekday-badge" style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 700;">
                                <!-- Sẽ được JS điền tên Thứ -->
                            </span>
                        </div>
                        <div class="day-remove-btn-slot">
                            <!-- Nút xóa ngày xuất hiện khi có từ 2 ngày trở lên -->
                        </div>
                    </div>

                    <!-- LỰA CHỌN CÁC CA CỐ ĐỊNH & QUÂN SỐ CẦN TUYỂN -->
                    <div style="margin-bottom: 8px;">
                        <div style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.03em;">
                            Chọn ca trực cần tuyển và nhập số lượng tình nguyện viên:
                        </div>

                        <div class="shifts-grid-selector" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px;">
                            <!-- CA SÁNG -->
                            <div class="shift-option-card active" style="background: #ffffff; border: 1.5px solid #059669; border-radius: 10px; padding: 12px 14px; display: flex; flex-direction: column; gap: 8px; transition: all 0.2s ease;">
                                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin: 0;">
                                    <input type="checkbox" name="days[0][shifts][sang][active]" value="1" checked onchange="toggleShiftCard(this)" style="width: 18px; height: 18px; accent-color: #059669; cursor: pointer;">
                                    <span style="font-weight: 800; color: #0f172a; font-size: 0.95rem;">Ca Sáng</span>
                                    <span style="background: #eff6ff; color: #1d4ed8; padding: 2px 8px; border-radius: 6px; font-size: 0.78rem; font-weight: 700;">08:00 – 12:00</span>
                                </label>
                                <div class="shift-cap-input-wrap" style="display: flex; align-items: center; justify-content: space-between; gap: 8px; border-top: 1px solid #f1f5f9; padding-top: 8px;">
                                    <span style="font-size: 0.85rem; color: #64748b;">Số lượng TNV:</span>
                                    <div style="display: inline-flex; align-items: center; gap: 6px;">
                                        <input type="number" name="days[0][shifts][sang][capacity]" value="15" min="1" max="200" style="width: 75px; padding: 5px 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.92rem; font-weight: 700; text-align: center;">
                                        <span style="font-size: 0.82rem; color: #64748b;">người</span>
                                    </div>
                                </div>
                            </div>

                            <!-- CA CHIỀU -->
                            <div class="shift-option-card active" style="background: #ffffff; border: 1.5px solid #059669; border-radius: 10px; padding: 12px 14px; display: flex; flex-direction: column; gap: 8px; transition: all 0.2s ease;">
                                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin: 0;">
                                    <input type="checkbox" name="days[0][shifts][chieu][active]" value="1" checked onchange="toggleShiftCard(this)" style="width: 18px; height: 18px; accent-color: #059669; cursor: pointer;">
                                    <span style="font-weight: 800; color: #0f172a; font-size: 0.95rem;">Ca Chiều</span>
                                    <span style="background: #fef3c7; color: #b45309; padding: 2px 8px; border-radius: 6px; font-size: 0.78rem; font-weight: 700;">13:30 – 17:30</span>
                                </label>
                                <div class="shift-cap-input-wrap" style="display: flex; align-items: center; justify-content: space-between; gap: 8px; border-top: 1px solid #f1f5f9; padding-top: 8px;">
                                    <span style="font-size: 0.85rem; color: #64748b;">Số lượng TNV:</span>
                                    <div style="display: inline-flex; align-items: center; gap: 6px;">
                                        <input type="number" name="days[0][shifts][chieu][capacity]" value="15" min="1" max="200" style="width: 75px; padding: 5px 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.92rem; font-weight: 700; text-align: center;">
                                        <span style="font-size: 0.82rem; color: #64748b;">người</span>
                                    </div>
                                </div>
                            </div>

                            <!-- CA TỐI -->
                            <div class="shift-option-card" style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; display: flex; flex-direction: column; gap: 8px; transition: all 0.2s ease; opacity: 0.75;">
                                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin: 0;">
                                    <input type="checkbox" name="days[0][shifts][toi][active]" value="1" onchange="toggleShiftCard(this)" style="width: 18px; height: 18px; accent-color: #059669; cursor: pointer;">
                                    <span style="font-weight: 800; color: #0f172a; font-size: 0.95rem;">Ca Tối</span>
                                    <span style="background: #f3e8ff; color: #6b21a8; padding: 2px 8px; border-radius: 6px; font-size: 0.78rem; font-weight: 700;">18:00 – 21:30</span>
                                </label>
                                <div class="shift-cap-input-wrap" style="display: flex; align-items: center; justify-content: space-between; gap: 8px; border-top: 1px solid #f1f5f9; padding-top: 8px;">
                                    <span style="font-size: 0.85rem; color: #64748b;">Số lượng TNV:</span>
                                    <div style="display: inline-flex; align-items: center; gap: 6px;">
                                        <input type="number" name="days[0][shifts][toi][capacity]" value="10" min="1" max="200" style="width: 75px; padding: 5px 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.92rem; font-weight: 700; text-align: center;">
                                        <span style="font-size: 0.82rem; color: #64748b;">người</span>
                                    </div>
                                </div>
                            </div>

                            <!-- CA ĐÊM -->
                            <div class="shift-option-card" style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; display: flex; flex-direction: column; gap: 8px; transition: all 0.2s ease; opacity: 0.75;">
                                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin: 0;">
                                    <input type="checkbox" name="days[0][shifts][dem][active]" value="1" onchange="toggleShiftCard(this)" style="width: 18px; height: 18px; accent-color: #059669; cursor: pointer;">
                                    <span style="font-weight: 800; color: #0f172a; font-size: 0.95rem;">Ca Đêm</span>
                                    <span style="background: #e0e7ff; color: #3730a3; padding: 2px 8px; border-radius: 6px; font-size: 0.78rem; font-weight: 700;">21:00 – 00:30</span>
                                </label>
                                <div class="shift-cap-input-wrap" style="display: flex; align-items: center; justify-content: space-between; gap: 8px; border-top: 1px solid #f1f5f9; padding-top: 8px;">
                                    <span style="font-size: 0.85rem; color: #64748b;">Số lượng TNV:</span>
                                    <div style="display: inline-flex; align-items: center; gap: 6px;">
                                        <input type="number" name="days[0][shifts][dem][capacity]" value="10" min="1" max="200" style="width: 75px; padding: 5px 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.92rem; font-weight: 700; text-align: center;">
                                        <span style="font-size: 0.82rem; color: #64748b;">người</span>
                                    </div>
                                </div>
                            </div>

                            <!-- CẢ NGÀY -->
                            <div class="shift-option-card" style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; display: flex; flex-direction: column; gap: 8px; transition: all 0.2s ease; opacity: 0.75;">
                                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin: 0;">
                                    <input type="checkbox" name="days[0][shifts][ca_ngay][active]" value="1" onchange="toggleShiftCard(this)" style="width: 18px; height: 18px; accent-color: #059669; cursor: pointer;">
                                    <span style="font-weight: 800; color: #0f172a; font-size: 0.95rem;">Cả Ngày</span>
                                    <span style="background: #ecfdf5; color: #047857; padding: 2px 8px; border-radius: 6px; font-size: 0.78rem; font-weight: 700;">08:00 – 17:00</span>
                                </label>
                                <div class="shift-cap-input-wrap" style="display: flex; align-items: center; justify-content: space-between; gap: 8px; border-top: 1px solid #f1f5f9; padding-top: 8px;">
                                    <span style="font-size: 0.85rem; color: #64748b;">Số lượng TNV:</span>
                                    <div style="display: inline-flex; align-items: center; gap: 6px;">
                                        <input type="number" name="days[0][shifts][ca_ngay][capacity]" value="20" min="1" max="200" style="width: 75px; padding: 5px 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.92rem; font-weight: 700; text-align: center;">
                                        <span style="font-size: 0.82rem; color: #64748b;">người</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CỤM NÚT HÀNH ĐỘNG DƯỚI CÙNG -->
        <div style="display: flex; gap: 14px; align-items: center; flex-wrap: wrap; margin-top: 10px;">
            <button type="submit" class="btn-primary" style="padding: 12px 32px; font-size: 1rem; font-weight: 800; border-radius: 10px; background: #059669; border-color: #059669; box-shadow: 0 4px 14px rgba(5,150,105,0.3); display: inline-flex; align-items: center; gap: 8px;">
                <span>&#10003;</span> Tạo Hoạt Động Mới
            </button>
            <a href="<?= (!empty($_GET['from']) && $_GET['from'] === 'admin') ? '/admin_import.php?tab=projects' : '/hoat_dong.php' ?>" class="btn-cancel" style="padding: 12px 24px; text-decoration: none; border-radius: 10px; font-weight: 600;">
                Hủy Bỏ
            </a>
        </div>
    </form>
</div>

<script>
var weekdaysMap = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];
var nextDayIdx = 1;

// Danh mục phân cấp địa bàn (Vùng miền & Tỉnh thành)
var provincesData = <?= json_encode(LocationManager::PROVINCES_MAP, JSON_UNESCAPED_UNICODE) ?>;

function onRegionChange(regKey, selectedProv) {
    var provSelect = document.getElementById('loc_province');
    if (!provSelect) return;
    provSelect.innerHTML = '';
    var provs = provincesData[regKey] || {};
    for (var p in provs) {
        var opt = document.createElement('option');
        opt.value = p;
        opt.textContent = p;
        if (selectedProv && p === selectedProv) {
            opt.selected = true;
        }
        provSelect.appendChild(opt);
    }
    syncLocationField();
}

function syncLocationField() {
    var prov = document.getElementById('loc_province')?.value || '';
    var dist = document.getElementById('loc_district')?.value.trim() || '';
    var detail = document.getElementById('loc_detail')?.value.trim() || '';
    var locInput = document.getElementById('location');
    if (!locInput) return;

    var parts = [];
    if (detail) parts.push(detail);
    if (dist) parts.push(dist);
    if (prov) parts.push(prov);
    locInput.value = parts.join(', ');
}

// Khởi chạy khi load trang
document.addEventListener('DOMContentLoaded', function() {
    var regSelect = document.getElementById('loc_region');
    if (regSelect) {
        onRegionChange(regSelect.value, '<?= addslashes($_POST['loc_province'] ?? 'Hà Nội') ?>');
    }
});

// Cập nhật nhãn Thứ tiếng Việt khi người dùng chọn ngày trên lịch
function updateDayWeekdayBadge(input) {
    var val = input.value;
    var container = input.closest('.day-setup-card');
    if (!container) return;
    var badge = container.querySelector('.day-weekday-badge');
    if (!badge) return;

    if (!val) {
        badge.textContent = 'Chưa chọn ngày';
        badge.style.background = '#f1f5f9';
        badge.style.color = '#64748b';
        return;
    }

    var parts = val.split('-');
    if (parts.length === 3) {
        var d = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
        var dayName = weekdaysMap[d.getDay()] || 'Ngày';
        badge.textContent = dayName;
        badge.style.background = '#e0f2fe';
        badge.style.color = '#0369a1';
    }
}

// Bật/tắt trạng thái visual của thẻ ca trực khi checkbox thay đổi
function toggleShiftCard(checkbox) {
    var card = checkbox.closest('.shift-option-card');
    if (!card) return;
    if (checkbox.checked) {
        card.classList.add('active');
        card.style.borderColor = '#059669';
        card.style.opacity = '1';
    } else {
        card.classList.remove('active');
        card.style.borderColor = '#e2e8f0';
        card.style.opacity = '0.7';
    }
}

// Khởi chạy cập nhật tên Thứ cho ngày mặc định ban đầu
document.querySelectorAll('.input-day-date').forEach(function(input) {
    updateDayWeekdayBadge(input);
});

// Thêm một ngày mới vào lịch
document.getElementById('btn_add_day').addEventListener('click', function() {
    var dIdx = nextDayIdx++;
    var container = document.getElementById('days_container');
    var div = document.createElement('div');
    div.className = 'day-setup-card';
    div.setAttribute('data-day-index', dIdx);
    div.style = 'background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 12px; padding: 18px 20px; margin-bottom: 20px;';

    // Tính ngày tiếp theo (+1 ngày so với ngày trước)
    var prevInputs = document.querySelectorAll('.input-day-date');
    var nextDateStr = '';
    if (prevInputs.length > 0) {
        var lastVal = prevInputs[prevInputs.length - 1].value;
        if (lastVal) {
            var parts = lastVal.split('-');
            var lastD = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
            lastD.setDate(lastD.getDate() + 1);
            var y = lastD.getFullYear();
            var m = String(lastD.getMonth() + 1).padStart(2, '0');
            var day = String(lastD.getDate()).padStart(2, '0');
            nextDateStr = y + '-' + m + '-' + day;
        }
    }

    div.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px dashed #cbd5e1; padding-bottom: 12px; flex-wrap: wrap; gap: 10px;">
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <span style="font-size: 1.25rem;">&#128197;</span>
                <strong style="color: #0f172a; font-size: 0.98rem;">Chọn ngày tổ chức:</strong>
                <input type="date" name="days[` + dIdx + `][date]" class="input-day-date" value="` + nextDateStr + `" required onchange="updateDayWeekdayBadge(this)" style="padding: 7px 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 0.92rem; font-weight: 600; background: #ffffff; color: #0f172a;">
                <span class="day-weekday-badge" style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 700;">
                    Chưa chọn ngày
                </span>
            </div>
            <div>
                <button type="button" onclick="this.closest('.day-setup-card').remove()" class="btn-cancel" style="padding: 6px 12px; font-size: 0.82rem; color: #dc2626; border-color: #fecaca; background: #fef2f2; border-radius: 6px; cursor: pointer; font-weight: 700;">
                    ✕ Xóa Ngày Này
                </button>
            </div>
        </div>

        <div style="margin-bottom: 8px;">
            <div style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.03em;">
                Chọn ca trực cần tuyển và nhập số lượng tình nguyện viên:
            </div>

            <div class="shifts-grid-selector" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px;">
                <!-- CA SÁNG -->
                <div class="shift-option-card active" style="background: #ffffff; border: 1.5px solid #059669; border-radius: 10px; padding: 12px 14px; display: flex; flex-direction: column; gap: 8px; transition: all 0.2s ease;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin: 0;">
                        <input type="checkbox" name="days[` + dIdx + `][shifts][sang][active]" value="1" checked onchange="toggleShiftCard(this)" style="width: 18px; height: 18px; accent-color: #059669; cursor: pointer;">
                        <span style="font-weight: 800; color: #0f172a; font-size: 0.95rem;">Ca Sáng</span>
                        <span style="background: #eff6ff; color: #1d4ed8; padding: 2px 8px; border-radius: 6px; font-size: 0.78rem; font-weight: 700;">08:00 – 12:00</span>
                    </label>
                    <div class="shift-cap-input-wrap" style="display: flex; align-items: center; justify-content: space-between; gap: 8px; border-top: 1px solid #f1f5f9; padding-top: 8px;">
                        <span style="font-size: 0.85rem; color: #64748b;">Số lượng TNV:</span>
                        <div style="display: inline-flex; align-items: center; gap: 6px;">
                            <input type="number" name="days[` + dIdx + `][shifts][sang][capacity]" value="15" min="1" max="200" style="width: 75px; padding: 5px 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.92rem; font-weight: 700; text-align: center;">
                            <span style="font-size: 0.82rem; color: #64748b;">người</span>
                        </div>
                    </div>
                </div>

                <!-- CA CHIỀU -->
                <div class="shift-option-card active" style="background: #ffffff; border: 1.5px solid #059669; border-radius: 10px; padding: 12px 14px; display: flex; flex-direction: column; gap: 8px; transition: all 0.2s ease;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin: 0;">
                        <input type="checkbox" name="days[` + dIdx + `][shifts][chieu][active]" value="1" checked onchange="toggleShiftCard(this)" style="width: 18px; height: 18px; accent-color: #059669; cursor: pointer;">
                        <span style="font-weight: 800; color: #0f172a; font-size: 0.95rem;">Ca Chiều</span>
                        <span style="background: #fef3c7; color: #b45309; padding: 2px 8px; border-radius: 6px; font-size: 0.78rem; font-weight: 700;">13:30 – 17:30</span>
                    </label>
                    <div class="shift-cap-input-wrap" style="display: flex; align-items: center; justify-content: space-between; gap: 8px; border-top: 1px solid #f1f5f9; padding-top: 8px;">
                        <span style="font-size: 0.85rem; color: #64748b;">Số lượng TNV:</span>
                        <div style="display: inline-flex; align-items: center; gap: 6px;">
                            <input type="number" name="days[` + dIdx + `][shifts][chieu][capacity]" value="15" min="1" max="200" style="width: 75px; padding: 5px 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.92rem; font-weight: 700; text-align: center;">
                            <span style="font-size: 0.82rem; color: #64748b;">người</span>
                        </div>
                    </div>
                </div>

                <!-- CA TỐI -->
                <div class="shift-option-card" style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; display: flex; flex-direction: column; gap: 8px; transition: all 0.2s ease; opacity: 0.75;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin: 0;">
                        <input type="checkbox" name="days[` + dIdx + `][shifts][toi][active]" value="1" onchange="toggleShiftCard(this)" style="width: 18px; height: 18px; accent-color: #059669; cursor: pointer;">
                        <span style="font-weight: 800; color: #0f172a; font-size: 0.95rem;">Ca Tối</span>
                        <span style="background: #f3e8ff; color: #6b21a8; padding: 2px 8px; border-radius: 6px; font-size: 0.78rem; font-weight: 700;">18:00 – 21:30</span>
                    </label>
                    <div class="shift-cap-input-wrap" style="display: flex; align-items: center; justify-content: space-between; gap: 8px; border-top: 1px solid #f1f5f9; padding-top: 8px;">
                        <span style="font-size: 0.85rem; color: #64748b;">Số lượng TNV:</span>
                        <div style="display: inline-flex; align-items: center; gap: 6px;">
                            <input type="number" name="days[` + dIdx + `][shifts][toi][capacity]" value="10" min="1" max="200" style="width: 75px; padding: 5px 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.92rem; font-weight: 700; text-align: center;">
                            <span style="font-size: 0.82rem; color: #64748b;">người</span>
                        </div>
                    </div>
                </div>

                <!-- CA ĐÊM -->
                <div class="shift-option-card" style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; display: flex; flex-direction: column; gap: 8px; transition: all 0.2s ease; opacity: 0.75;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin: 0;">
                        <input type="checkbox" name="days[` + dIdx + `][shifts][dem][active]" value="1" onchange="toggleShiftCard(this)" style="width: 18px; height: 18px; accent-color: #059669; cursor: pointer;">
                        <span style="font-weight: 800; color: #0f172a; font-size: 0.95rem;">Ca Đêm</span>
                        <span style="background: #e0e7ff; color: #3730a3; padding: 2px 8px; border-radius: 6px; font-size: 0.78rem; font-weight: 700;">21:00 – 00:30</span>
                    </label>
                    <div class="shift-cap-input-wrap" style="display: flex; align-items: center; justify-content: space-between; gap: 8px; border-top: 1px solid #f1f5f9; padding-top: 8px;">
                        <span style="font-size: 0.85rem; color: #64748b;">Số lượng TNV:</span>
                        <div style="display: inline-flex; align-items: center; gap: 6px;">
                            <input type="number" name="days[` + dIdx + `][shifts][dem][capacity]" value="10" min="1" max="200" style="width: 75px; padding: 5px 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.92rem; font-weight: 700; text-align: center;">
                            <span style="font-size: 0.82rem; color: #64748b;">người</span>
                        </div>
                    </div>
                </div>

                <!-- CẢ NGÀY -->
                <div class="shift-option-card" style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; display: flex; flex-direction: column; gap: 8px; transition: all 0.2s ease; opacity: 0.75;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin: 0;">
                        <input type="checkbox" name="days[` + dIdx + `][shifts][ca_ngay][active]" value="1" onchange="toggleShiftCard(this)" style="width: 18px; height: 18px; accent-color: #059669; cursor: pointer;">
                        <span style="font-weight: 800; color: #0f172a; font-size: 0.95rem;">Cả Ngày</span>
                        <span style="background: #ecfdf5; color: #047857; padding: 2px 8px; border-radius: 6px; font-size: 0.78rem; font-weight: 700;">08:00 – 17:00</span>
                    </label>
                    <div class="shift-cap-input-wrap" style="display: flex; align-items: center; justify-content: space-between; gap: 8px; border-top: 1px solid #f1f5f9; padding-top: 8px;">
                        <span style="font-size: 0.85rem; color: #64748b;">Số lượng TNV:</span>
                        <div style="display: inline-flex; align-items: center; gap: 6px;">
                            <input type="number" name="days[` + dIdx + `][shifts][ca_ngay][capacity]" value="20" min="1" max="200" style="width: 75px; padding: 5px 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.92rem; font-weight: 700; text-align: center;">
                            <span style="font-size: 0.82rem; color: #64748b;">người</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    container.appendChild(div);
    var newInput = div.querySelector('.input-day-date');
    if (newInput) {
        updateDayWeekdayBadge(newInput);
    }
});
</script>

<?php require __DIR__ . '/../templates/footer.php'; ?>
