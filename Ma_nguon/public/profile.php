<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/Input.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/SkillManager.php';
require_once __DIR__ . '/../src/Database.php';

$currentUser = Auth::getUser();

// XỬ LÝ LƯU HỒ SƠ TÌNH NGUYỆN VIÊN (POST)
$savedSuccess = false;
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$currentUser) {
        $errorMessage = 'Vui lòng đăng nhập trước khi cập nhật hồ sơ.';
    } else {
        $name = trim(field($_POST, 'name'));
        $phone = trim(field($_POST, 'phone'));
        $email = trim(field($_POST, 'email'));
        $dob = trim(field($_POST, 'dob'));
        $gender = trim(field($_POST, 'gender'));
        $unit = trim(field($_POST, 'unit'));
        $address = trim(field($_POST, 'address'));
        $emergencyContact = trim(field($_POST, 'emergency_contact'));
        $emergencyPhone = trim(field($_POST, 'emergency_phone'));
        $expectations = trim(field($_POST, 'expectations'));

        // Xử lý kỹ năng có sẵn (checkbox)
        $presetSkills = isset($_POST['preset_skills']) && is_array($_POST['preset_skills']) ? $_POST['preset_skills'] : [];

        // Xử lý kỹ năng tự nhập (tách theo dấu phẩy hoặc mảng)
        $customSkillsRaw = trim(field($_POST, 'custom_skills_input'));
        $customSkills = [];
        if ($customSkillsRaw !== '') {
            $parts = explode(',', $customSkillsRaw);
            foreach ($parts as $p) {
                $pClean = trim($p);
                if ($pClean !== '' && strlen($pClean) <= 60) {
                    $customSkills[] = $pClean;
                }
            }
        }
        if (isset($_POST['custom_skills']) && is_array($_POST['custom_skills'])) {
            foreach ($_POST['custom_skills'] as $cs) {
                $csClean = trim((string)$cs);
                if ($csClean !== '' && !in_array($csClean, $customSkills, true)) {
                    $customSkills[] = $csClean;
                }
            }
        }

        // Kỹ năng đã chứng minh / xác thực
        $certifiedSkills = isset($_POST['certified_skills']) && is_array($_POST['certified_skills']) ? $_POST['certified_skills'] : ($currentUser['certified_skills'] ?? []);

        // Validation cơ bản
        if ($name === '' || strlen($name) > 120) {
            $errorMessage = 'Họ và tên không được để trống và tối đa 120 ký tự.';
        } elseif ($phone === '' || strlen($phone) < 9) {
            $errorMessage = 'Số điện thoại liên hệ phải có ít nhất 9 chữ số.';
        } else {
            // Cập nhật thông tin vào tài khoản hiện tại trong Session
            $currentUser['name'] = $name;
            $currentUser['phone'] = $phone;
            if ($email !== '') {
                $currentUser['email'] = $email;
            }
            $currentUser['dob'] = $dob;
            $currentUser['gender'] = $gender;
            $currentUser['unit'] = $unit ?: 'Thanh niên tình nguyện D2L';
            $currentUser['address'] = $address;
            $currentUser['emergency_contact'] = $emergencyContact;
            $currentUser['emergency_phone'] = $emergencyPhone;
            $currentUser['expectations'] = $expectations ?: 'Cống hiến vì cộng đồng và lan tỏa yêu thương.';
            
            // Chuyển đổi các preset skills thành mã nhóm kỹ năng hoặc tên chuẩn
            $currentUser['skills'] = $presetSkills;
            $currentUser['custom_skills'] = $customSkills;
            $currentUser['certified_skills'] = $certifiedSkills;

            // Xử lý khung giờ rảnh cố định trong tuần (mảng key: t2_sang, t7_chieu...)
            $regularAvailability = isset($_POST['regular_availability']) && is_array($_POST['regular_availability']) 
                ? $_POST['regular_availability'] 
                : [];
            $currentUser['regular_availability'] = $regularAvailability;

            // Xử lý lịch bận đột xuất trong tương lai
            $busyDates = [];
            if (!empty($_POST['busy_dates']) && is_array($_POST['busy_dates'])) {
                foreach ($_POST['busy_dates'] as $b) {
                    $bDate = trim((string)($b['date'] ?? ''));
                    if ($bDate !== '') {
                        $busyDates[] = [
                            'date' => $bDate,
                            'slot' => trim((string)($b['slot'] ?? 'all_day')),
                            'reason' => trim((string)($b['reason'] ?? 'Bận việc riêng'))
                        ];
                    }
                }
            }
            $currentUser['busy_dates'] = $busyDates;

            if (empty($currentUser['pass_id'])) {
                $currentUser['pass_id'] = 'D2L-TNV-' . strtoupper(substr(md5(($currentUser['email'] ?? $name) . 'SALT'), 0, 6));
            }

            $_SESSION['user'] = $currentUser;
            $_SESSION['volunteer_profile'] = $currentUser;
            $savedSuccess = true;

            // Lưu trực tiếp vào bảng volunteers trong CSDL (MySQL / SQLite)
            try {
                Database::getInstance()->saveVolunteerProfile($currentUser);
            } catch (Throwable $e) {
                error_log("Profile: saveVolunteerProfile error: " . $e->getMessage());
            }

            // Nếu người dùng nhấn nút lưu riêng ở phần Lịch Rảnh
            if (isset($_POST['save_action']) && $_POST['save_action'] === 'save_schedule') {
                $_SESSION['flash_success'] = 'Đã lưu thành công Cài đặt Lịch Rảnh Hàng Tuần & Lịch Bận Tương Lai!';
                header('Location: /profile.php?section=schedule#section_schedule');
                exit;
            }
        }
    }
}

// Chuẩn bị dữ liệu hiển thị Profile
$name = $currentUser['name'] ?? '';
$email = $currentUser['email'] ?? '';
$phone = $currentUser['phone'] ?? '';
$dob = $currentUser['dob'] ?? '2004-05-15';
$gender = $currentUser['gender'] ?? 'Nam';
$unit = $currentUser['unit'] ?? 'ĐH Quốc Gia Hà Nội';
$address = $currentUser['address'] ?? 'Quận Cầu Giấy, Hà Nội';
$emergencyContact = $currentUser['emergency_contact'] ?? 'Gia đình / Người thân';
$emergencyPhone = $currentUser['emergency_phone'] ?? '0912.345.678';
$expectations = $currentUser['expectations'] ?? 'Sẵn sàng tham gia các chiến dịch vì cộng đồng, tích lũy giờ công tác xã hội và rèn luyện kỹ năng thực tế.';
$passId = $currentUser['pass_id'] ?? ('D2L-TNV-' . strtoupper(substr(md5($email ?: 'DEMO'), 0, 6)));
$volunteerHours = (int)($currentUser['volunteer_hours'] ?? 24);
$eventsJoined = (int)($currentUser['events_joined'] ?? 5);

// Kỹ năng hiện có
$selectedSkills = $currentUser['skills'] ?? [
    'Sơ cấp cứu & Băng bó vết thương ban đầu',
    'Thể lực tốt & Khuân vác bốc dỡ hàng cứu trợ'
];
$customSkills = $currentUser['custom_skills'] ?? [
    'Biết chèo thuyền dã ngoại',
    'Thiết kế Canva cơ bản'
];
$certifiedSkills = $currentUser['certified_skills'] ?? [
    'Sơ cấp cứu & Băng bó vết thương ban đầu'
];

// Khung giờ rảnh cố định và Lịch bận trong tương lai
$regularAvailability = $currentUser['regular_availability'] ?? [
    't7_sang', 't7_chieu', 'cn_sang', 'cn_chieu'
];
$busyDates = $currentUser['busy_dates'] ?? [
    [
        'date' => date('Y-m-d', strtotime('+5 days')),
        'slot' => 'all_day',
        'reason' => 'Thi kết thúc học phần trên trường'
    ]
];

$allSkillGroups = SkillManager::getGroups();
$presetSkillsByGroup = SkillManager::getPresetSkillsByGroup();

// Lấy danh sách kỹ năng đã tổng hợp để hiển thị thẻ
$volunteerSkillsProfile = SkillManager::getVolunteerSkillsProfile([
    'skills' => $selectedSkills,
    'custom_skills' => $customSkills,
    'certified_skills' => $certifiedSkills,
]);

require __DIR__ . '/../templates/header.php';
?>

<div class="page-heading-pro">
    <div class="heading-container">
        <span class="sub-badge">HỒ SƠ TÌNH NGUYỆN VIÊN</span>
        <h1>Hồ Sơ Năng Lực & Thẻ Điện Tử Cá Nhân</h1>
        <p>Quản lý thông tin định danh, chuẩn hóa danh mục kỹ năng cố định và bổ sung kỹ năng tự nhập phục vụ điều phối thực địa.</p>
    </div>
</div>

<div class="page-content-wrapper">
    <?php if (!$currentUser): ?>
        <!-- CHƯA ĐĂNG NHẬP -->
        <div class="auth-required-card">
            <div class="auth-required-header">
                <h3>Yêu Cầu Đăng Nhập Để Quản Lý Hồ Sơ</h3>
                <p>
                    Vui lòng đăng nhập tài khoản tình nguyện viên để cập nhật kỹ năng chuyên môn, xem chứng chỉ được công nhận và sử dụng Thẻ tình nguyện viên điện tử.
                </p>
            </div>
            <div class="auth-required-actions">
                <a href="/dang_nhap.php?redirect=/profile.php" class="btn-primary" style="padding: 12px 28px; font-weight: 700;">
                    Đăng Nhập Ngay
                </a>
                <a href="/dang_ky_tk.php?redirect=/profile.php" class="btn-detail" style="padding: 12px 28px; font-weight: 700;">
                    Tạo Tài Khoản Mới
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- ĐÃ ĐĂNG NHẬP -->
        <?php if ($savedSuccess): ?>
            <div class="alert-success-box" role="alert" style="background: #ecfdf5; border: 1px solid #10b981; border-radius: 10px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span style="font-size: 1.5rem; color: #059669;">&#10003;</span>
                    <div>
                        <strong style="color: #065f46; font-size: 1rem;">Cập Nhật Hồ Sơ Thành Công!</strong>
                        <p style="color: #047857; margin: 2px 0 0; font-size: 0.9rem;">Thông tin chuyên môn, kỹ năng có sẵn và kỹ năng tự nhập của bạn đã được cập nhật vào hệ thống điều phối.</p>
                    </div>
                </div>
                <button type="button" class="btn-close-alert" onclick="this.parentElement.remove()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #047857;">&times;</button>
            </div>
        <?php elseif ($errorMessage !== ''): ?>
            <div class="alert-error-box" role="alert" style="background: #fef2f2; border: 1px solid #ef4444; border-radius: 10px; padding: 16px 20px; margin-bottom: 24px;">
                <strong style="color: #991b1b;">Thông tin chưa hợp lệ:</strong>
                <p style="color: #b91c1c; margin: 4px 0 0; font-size: 0.9rem;"><?= htmlspecialchars($errorMessage) ?></p>
            </div>
        <?php endif; ?>

        <div class="profile-layout-grid">
            <!-- CỘT TRÁI: FORM CẬP NHẬT HỒ SƠ & KỸ NĂNG -->
            <div class="profile-main-col">
                <!-- THANH TABS CHUYỂN NHANH DANH MỤC TRONG HỒ SƠ -->
                <nav class="profile-nav-tabs-bar" aria-label="Danh mục hồ sơ">
                    <button type="button" class="tab-profile-pill active" onclick="switchProfileSection('all', this)">
                        <span>Tất Cả</span>
                    </button>
                    <button type="button" class="tab-profile-pill" onclick="switchProfileSection('info', this)">
                        <span>1. Thông Tin Cá Nhân</span>
                    </button>
                    <button type="button" class="tab-profile-pill" onclick="switchProfileSection('skills', this)">
                        <span>2. Kỹ Năng</span>
                        <span class="tab-badge-num"><?= count($selectedSkills) + count($customSkills) ?></span>
                    </button>
                    <button type="button" class="tab-profile-pill" onclick="switchProfileSection('schedule', this)">
                        <span>3. Lịch Rảnh & Báo Bận</span>
                        <span class="tab-badge-num"><?= count($regularAvailability) ?> ca</span>
                    </button>
                    <button type="button" class="tab-profile-pill" onclick="switchProfileSection('emergency', this)">
                        <span>4. Liên Hệ Khẩn Cấp</span>
                    </button>
                    <button type="button" class="tab-profile-pill" onclick="switchProfileSection('card', this)">
                        <span>Thẻ Điện Tử</span>
                    </button>
                </nav>

                <form method="POST" action="/profile.php" class="profile-form-card" id="volunteer_profile_form">
                    <!-- KHỐI 1: THÔNG TIN CÁ NHÂN -->
                    <div class="profile-section-box" id="section_info">
                        <div class="section-title-wrap">
                            <span class="step-circle">1</span>
                            <div>
                                <h3 class="section-h3">Thông Tin Cá Nhân</h3>
                                <p class="section-desc">Thông tin dùng để liên hệ và cấp giấy chứng nhận.</p>
                            </div>
                        </div>

                        <div class="form-grid-2">
                            <div class="form-group-item">
                                <label for="name">Họ và tên <strong class="req-star">*</strong></label>
                                <input type="text" id="name" name="name" value="<?= htmlspecialchars($name) ?>" required maxlength="120" placeholder="Ví dụ: Nguyễn Văn An">
                            </div>
                            <div class="form-group-item">
                                <label for="email">Địa chỉ Email <strong class="req-star">*</strong></label>
                                <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required placeholder="Ví dụ: volunteer@d2l.org">
                            </div>
                        </div>

                        <div class="form-grid-3" style="margin-top: 14px;">
                            <div class="form-group-item">
                                <label for="phone">Số điện thoại <strong class="req-star">*</strong></label>
                                <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($phone) ?>" required placeholder="Ví dụ: 0912345678">
                            </div>
                            <div class="form-group-item">
                                <label for="dob">Ngày sinh</label>
                                <input type="date" id="dob" name="dob" value="<?= htmlspecialchars($dob) ?>">
                            </div>
                            <div class="form-group-item">
                                <label for="gender">Giới tính</label>
                                <select id="gender" name="gender">
                                    <option value="Nam" <?= $gender === 'Nam' ? 'selected' : '' ?>>Nam</option>
                                    <option value="Nữ" <?= $gender === 'Nữ' ? 'selected' : '' ?>>Nữ</option>
                                    <option value="Khác" <?= $gender === 'Khác' ? 'selected' : '' ?>>Khác</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-grid-2" style="margin-top: 14px;">
                            <div class="form-group-item">
                                <label for="unit">Trường Đại học / Đơn vị công tác</label>
                                <input type="text" id="unit" name="unit" value="<?= htmlspecialchars($unit) ?>" placeholder="Ví dụ: ĐH Khoa học Tự nhiên, Khoa CNTT">
                            </div>
                            <div class="form-group-item">
                                <label for="address">Địa bàn cư trú / Quận huyện</label>
                                <input type="text" id="address" name="address" value="<?= htmlspecialchars($address) ?>" placeholder="Ví dụ: Quận Cầu Giấy, Hà Nội">
                            </div>
                        </div>
                    </div>

                    <!-- KHỐI 2: KỸ NĂNG -->
                    <div class="profile-section-box" id="section_skills">
                        <div class="section-title-wrap">
                            <span class="step-circle">2</span>
                            <div>
                                <h3 class="section-h3">Kỹ Năng Chuyên Môn</h3>
                                <p class="section-desc">Kỹ năng sẵn có và các kỹ năng do bạn tự bổ sung.</p>
                            </div>
                        </div>

                        <!-- 2.1: KỸ NĂNG TIÊU CHUẨN -->
                        <div class="skills-block-subtitle">
                            <strong>A. Kỹ năng tiêu chuẩn:</strong>
                            <small style="color: #64748b; display: block; margin-top: 2px;">Chọn các kỹ năng bạn có thể đảm nhận.</small>
                        </div>

                        <div class="preset-skills-accordion">
                            <?php foreach ($presetSkillsByGroup as $gKey => $skillsList): 
                                $gMeta = $allSkillGroups[$gKey] ?? [];
                            ?>
                                <div class="skill-group-panel">
                                    <div class="skill-group-heading" style="border-left: 4px solid <?= htmlspecialchars($gMeta['color'] ?? '#059669') ?>;">
                                        <span class="g-badge" style="background: <?= htmlspecialchars($gMeta['bg_color'] ?? '#ecfdf5') ?>; color: <?= htmlspecialchars($gMeta['color'] ?? '#059669') ?>;">
                                            <?= htmlspecialchars($gMeta['badge'] ?? $gKey) ?>
                                        </span>
                                        <strong><?= htmlspecialchars($gMeta['name'] ?? $gKey) ?></strong>
                                    </div>

                                    <div class="skill-checkbox-matrix">
                                        <?php foreach ($skillsList as $skItem): 
                                            $isChecked = in_array($skItem, $selectedSkills, true);
                                            $isCert = in_array($skItem, $certifiedSkills, true);
                                        ?>
                                            <label class="skill-choice-label <?= $isChecked ? 'selected' : '' ?>">
                                                <input type="checkbox" name="preset_skills[]" value="<?= htmlspecialchars($skItem) ?>" <?= $isChecked ? 'checked' : '' ?> onchange="this.closest('.skill-choice-label').classList.toggle('selected', this.checked)">
                                                <span class="skill-choice-name">
                                                    <?= htmlspecialchars($skItem) ?>
                                                    <?php if ($isCert): ?>
                                                        <span class="badge-certified" title="Kỹ năng đã được Ban Chỉ Huy thẩm định và cấp chứng chỉ">
                                                            &#10003; Đã chứng minh
                                                        </span>
                                                    <?php endif; ?>
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- 2.2: KỸ NĂNG BỔ SUNG -->
                        <div class="custom-skills-management-box" style="margin-top: 24px; padding: 18px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 10px;">
                            <div class="skills-block-subtitle">
                                <strong style="color: #1e293b;">B. Kỹ năng bổ sung (tự nhập):</strong>
                                <small style="color: #64748b; display: block; margin-top: 2px;">
                                    Nhập thêm các kỹ năng khác của bạn (ví dụ: lái xe, bơi lội, ngoại ngữ, sơ cứu, thiết kế...).
                                </small>
                            </div>

                            <!-- Ô NHẬP KỸ NĂNG MỚI -->
                            <div class="custom-skill-input-row" style="display: flex; gap: 10px; margin: 14px 0;">
                                <input type="text" id="new_custom_skill_input" placeholder="Nhập tên kỹ năng của bạn..." style="flex: 1; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.92rem;">
                                <button type="button" class="btn-primary" id="btn_add_custom_skill" style="padding: 10px 20px; white-space: nowrap; font-weight: 600;">
                                    + Thêm Kỹ Năng
                                </button>
                            </div>

                            <!-- CÁC TAG GỢI Ý THƯỜNG GẶP -->
                            <div class="quick-suggest-tags" style="margin-bottom: 12px; font-size: 0.85rem; color: #64748b;">
                                <span>Gợi ý thêm nhanh: </span>
                                <span class="suggest-chip" onclick="addSuggestedSkill('Biết lái xe bán tải chở đồ')">+ Xe bán tải</span>
                                <span class="suggest-chip" onclick="addSuggestedSkill('Biết bơi lội & Cứu đuối nước')">+ Bơi lội & Cứu hộ</span>
                                <span class="suggest-chip" onclick="addSuggestedSkill('Thiết kế đồ họa Canva & Photoshop')">+ Canva / Photoshop</span>
                                <span class="suggest-chip" onclick="addSuggestedSkill('Sửa chữa điện & Âm thanh sự kiện')">+ Sửa chữa điện</span>
                                <span class="suggest-chip" onclick="addSuggestedSkill('Biết đàn Guitar sinh hoạt tập thể')">+ Đàn Guitar</span>
                                <span class="suggest-chip" onclick="addSuggestedSkill('Giao tiếp ngôn ngữ ký hiệu')">+ Ngôn ngữ ký hiệu</span>
                            </div>

                            <!-- DANH SÁCH TAG KỸ NĂNG TỰ NHẬP HIỆN TẠI -->
                            <div id="custom_skills_container" class="custom-skills-tags-wrap">
                                <?php foreach ($customSkills as $cSkill): ?>
                                    <span class="custom-skill-item-tag">
                                        <input type="hidden" name="custom_skills[]" value="<?= htmlspecialchars($cSkill) ?>">
                                        <span class="tag-text"><?= htmlspecialchars($cSkill) ?></span>
                                        <button type="button" class="btn-remove-tag" onclick="this.parentElement.remove()" title="Xóa kỹ năng này">&times;</button>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- KHỐI 3: LỊCH RẢNH & BÁO BẬN -->
                    <div class="profile-section-box" id="section_schedule">
                        <div class="section-title-wrap">
                            <span class="step-circle">3</span>
                            <div>
                                <h3 class="section-h3">Lịch Rảnh & Báo Bận</h3>
                                <p class="section-desc">Cập nhật lịch rảnh trong tuần và các ngày bận để sắp xếp ca trực phù hợp.</p>
                            </div>
                        </div>

                        <!-- 3.1: KHUNG GIỜ RẢNH CHUNG TRONG TUẦN -->
                        <div style="margin-bottom: 24px;">
                            <div class="skills-block-subtitle" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                                <div>
                                    <strong style="color: #1e293b; font-size: 0.95rem;">A. Lịch rảnh trong tuần:</strong>
                                    <small style="color: #64748b; display: block; margin-top: 2px;">Chọn các buổi bạn có thể tham gia ca trực.</small>
                                </div>
                                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                    <button type="button" onclick="applyAvailPreset('weekend')" style="background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer;">
                                        + Cuối Tuần (T7, CN)
                                    </button>
                                    <button type="button" onclick="applyAvailPreset('evening')" style="background: #f5f3ff; color: #7c3aed; border: 1px solid #ddd6fe; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer;">
                                        + Các Buổi Tối
                                    </button>
                                    <button type="button" onclick="applyAvailPreset('all')" style="background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer;">
                                        + Rảnh Tất Cả
                                    </button>
                                    <button type="button" onclick="applyAvailPreset('none')" style="background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer;">
                                        Xóa Hết
                                    </button>
                                </div>
                            </div>

                            <!-- BẢNG LỊCH TUẦN -->
                            <div style="overflow-x: auto; margin-top: 14px;">
                                <table style="width: 100%; border-collapse: collapse; text-align: center; font-size: 13px;">
                                    <thead>
                                        <tr style="background: #f1f5f9; border-bottom: 2px solid #cbd5e1; color: #334155;">
                                            <th style="padding: 10px 8px; text-align: left; width: 140px;">Khung Giờ \ Thứ</th>
                                            <?php 
                                            $daysDef = [
                                                't2' => 'Thứ Hai',
                                                't3' => 'Thứ Ba',
                                                't4' => 'Thứ Tư',
                                                't5' => 'Thứ Năm',
                                                't6' => 'Thứ Sáu',
                                                't7' => 'Thứ Bảy',
                                                'cn' => 'Chủ Nhật'
                                            ];
                                            foreach ($daysDef as $dKey => $dLabel): 
                                                $isWk = in_array($dKey, ['t7', 'cn'], true);
                                            ?>
                                                <th style="padding: 10px 6px; color: <?= $isWk ? '#059669' : '#334155' ?>; font-weight: 700;">
                                                    <?= $dLabel ?>
                                                </th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $shiftsDef = [
                                            'sang' => ['name' => 'Ca Sáng', 'time' => '07:30 – 11:30'],
                                            'chieu' => ['name' => 'Ca Chiều', 'time' => '13:30 – 17:30'],
                                            'toi' => ['name' => 'Ca Tối', 'time' => '18:00 – 21:30'],
                                        ];
                                        foreach ($shiftsDef as $sKey => $sMeta):
                                        ?>
                                            <tr style="border-bottom: 1px solid #e2e8f0;">
                                                <td style="padding: 10px 8px; text-align: left; background: #f8fafc; font-weight: 600; color: #1e293b;">
                                                    <div><?= $sMeta['name'] ?></div>
                                                    <div style="font-size: 11px; color: #64748b; font-weight: normal;"><?= $sMeta['time'] ?></div>
                                                </td>
                                                <?php foreach ($daysDef as $dKey => $dLabel): 
                                                    $slotKey = $dKey . '_' . $sKey;
                                                    $isChecked = in_array($slotKey, $regularAvailability, true);
                                                ?>
                                                    <td style="padding: 6px;">
                                                        <label class="avail-slot-pill <?= $isChecked ? 'active' : '' ?>" style="display: block; padding: 8px 4px; border-radius: 6px; cursor: pointer; border: 1px solid <?= $isChecked ? '#059669' : '#cbd5e1' ?>; background: <?= $isChecked ? '#ecfdf5' : '#ffffff' ?>; color: <?= $isChecked ? '#059669' : '#64748b' ?>; font-weight: 600; transition: all 0.15s ease;">
                                                            <input type="checkbox" name="regular_availability[]" value="<?= $slotKey ?>" <?= $isChecked ? 'checked' : '' ?> style="display: none;" onchange="toggleAvailSlot(this)">
                                                            <span class="slot-text"><?= $isChecked ? '&#10003; Rảnh' : 'Bận' ?></span>
                                                        </label>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- 3.2: BÁO NGÀY BẬN -->
                        <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 10px; padding: 18px;">
                            <div class="skills-block-subtitle">
                                <strong style="color: #1e293b; font-size: 0.95rem;">B. Báo ngày bận:</strong>
                                <small style="color: #64748b; display: block; margin-top: 2px;">
                                    Khai báo ngày bận để ban điều phối không xếp ca vào thời gian này.
                                </small>
                            </div>

                            <!-- FORM THÊM LỊCH BẬN NHANH -->
                            <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin: 14px 0;">
                                <div style="min-width: 150px;">
                                    <input type="date" id="input_busy_date" min="<?= date('Y-m-d') ?>" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13.5px; background: #ffffff;">
                                </div>
                                <div style="min-width: 140px;">
                                    <select id="input_busy_slot" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; background: #ffffff;">
                                        <option value="all_day">Cả ngày (Toàn bộ)</option>
                                        <option value="morning">Chỉ bận Ca Sáng</option>
                                        <option value="afternoon">Chỉ bận Ca Chiều</option>
                                        <option value="evening">Chỉ bận Ca Tối</option>
                                    </select>
                                </div>
                                <div style="flex: 1; min-width: 200px;">
                                    <input type="text" id="input_busy_reason" placeholder="Lý do (Ví dụ: Thi cuối kỳ, Về quê, Công tác...)" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13.5px; background: #ffffff;">
                                </div>
                                <button type="button" id="btn_add_busy_date" class="btn-primary" style="padding: 8px 16px; white-space: nowrap; font-size: 13.5px; font-weight: 700; border-radius: 6px;">
                                    + Báo Bận
                                </button>
                            </div>

                            <!-- DANH SÁCH CÁC NGÀY BẬN ĐÃ ĐĂNG KÝ -->
                            <div id="busy_dates_container" style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px;">
                                <?php if (!empty($busyDates)): ?>
                                    <?php foreach ($busyDates as $bIdx => $bItem): 
                                        $slotLabel = $bItem['slot'] === 'morning' ? 'Ca Sáng' : ($bItem['slot'] === 'afternoon' ? 'Ca Chiều' : ($bItem['slot'] === 'evening' ? 'Ca Tối' : 'Cả ngày'));
                                    ?>
                                        <div class="busy-date-tag" style="background: #ffffff; border: 1px solid #fecaca; border-left: 3px solid #dc2626; border-radius: 6px; padding: 8px 12px; font-size: 12.5px; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                                            <input type="hidden" name="busy_dates[<?= $bIdx ?>][date]" value="<?= htmlspecialchars($bItem['date']) ?>">
                                            <input type="hidden" name="busy_dates[<?= $bIdx ?>][slot]" value="<?= htmlspecialchars($bItem['slot']) ?>">
                                            <input type="hidden" name="busy_dates[<?= $bIdx ?>][reason]" value="<?= htmlspecialchars($bItem['reason']) ?>">
                                            <div>
                                                <strong style="color: #991b1b;"><?= date('d/m/Y', strtotime($bItem['date'])) ?></strong>
                                                <span style="color: #64748b;">(<?= $slotLabel ?>):</span>
                                                <span style="color: #1e293b;"><?= htmlspecialchars($bItem['reason']) ?></span>
                                            </div>
                                            <button type="button" onclick="this.closest('.busy-date-tag').remove()" style="background: none; border: none; color: #dc2626; font-size: 16px; font-weight: bold; cursor: pointer; line-height: 1;" title="Hủy báo bận">&times;</button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- NÚT LƯU RIÊNG DÀNH CHO PHẦN LỊCH RẢNH & BÁO BẬN -->
                        <div style="margin-top: 24px; padding: 16px 20px; background: #ecfdf5; border: 1.5px solid #059669; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; box-shadow: 0 3px 10px rgba(5,150,105,0.12);">
                            <div>
                                <strong style="color: #065f46; font-size: 0.95rem; display: block;">&#128197; Lưu Lịch Làm Việc:</strong>
                                <small style="color: #047857; font-size: 12.5px;">Lưu lịch rảnh và ngày bận vào hệ thống.</small>
                            </div>
                            <button type="submit" name="save_action" value="save_schedule" class="btn-primary" style="padding: 11px 24px; font-size: 14px; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(5,150,105,0.35); cursor: pointer; border: none; background: #059669; color: #ffffff;">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                                <span>Lưu Lịch Làm Việc</span>
                            </button>
                        </div>
                    </div>

                    <!-- KHỐI 4: LIÊN HỆ KHẨN CẤP -->
                    <div class="profile-section-box" id="section_emergency">
                        <div class="section-title-wrap">
                            <span class="step-circle">4</span>
                            <div>
                                <h3 class="section-h3">Liên Hệ Khẩn Cấp</h3>
                                <p class="section-desc">Thông tin người thân khi cần liên lạc trong ca trực.</p>
                            </div>
                        </div>

                        <div class="form-grid-2">
                            <div class="form-group-item">
                                <label for="emergency_contact">Họ tên người liên hệ khẩn cấp (Người thân / Bạn bè)</label>
                                <input type="text" id="emergency_contact" name="emergency_contact" value="<?= htmlspecialchars($emergencyContact) ?>" placeholder="Ví dụ: Bố - Nguyễn Văn B">
                            </div>
                            <div class="form-group-item">
                                <label for="emergency_phone">Số điện thoại khẩn cấp</label>
                                <input type="tel" id="emergency_phone" name="emergency_phone" value="<?= htmlspecialchars($emergencyPhone) ?>" placeholder="Ví dụ: 0987654321">
                            </div>
                        </div>

                        <div class="form-group-item" style="margin-top: 14px; width: 100%;">
                            <label for="expectations" style="display: block; font-weight: 700; margin-bottom: 6px; color: #1e293b;">Mong muốn / Lời nhắn gửi tới Ban Chỉ Huy</label>
                            <textarea id="expectations" name="expectations" rows="3" style="width: 100%; box-sizing: border-box; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; resize: vertical; min-height: 85px; font-family: inherit;" placeholder="Chia sẻ định hướng tham gia hoạt động, các nguyện vọng rèn luyện hoặc đề xuất của bạn..."><?= htmlspecialchars($expectations) ?></textarea>
                        </div>
                    </div>

                    <!-- NÚT LƯU THÔNG TIN -->
                    <div class="form-submit-bar" style="display: flex; gap: 14px; align-items: center; margin-top: 24px;">
                        <button type="submit" class="btn-primary" style="padding: 13px 32px; font-size: 1rem; font-weight: 700; border-radius: 8px;">
                            Lưu Hồ Sơ
                        </button>
                        <a href="/hoat_dong.php" class="btn-detail" style="padding: 13px 24px; font-weight: 600; border-radius: 8px;">
                            Xem Ca Trực &rarr;
                        </a>
                    </div>
                </form>
            </div>

            <!-- CỘT PHẢI: XEM TRƯỚC THẺ TÌNH NGUYỆN VIÊN ĐIỆN TỬ -->
            <div class="profile-sidebar-col" id="section_card">
                <div class="digital-id-pass-card sticky-card">
                    <div class="id-pass-top">
                        <div class="id-pass-brand">
                            <div class="badge-emblem">D2L</div>
                            <div>
                                <strong>THẺ TÌNH NGUYỆN VIÊN</strong>
                                <span>THẺ ĐIỆN TỬ D2L</span>
                            </div>
                        </div>
                        <span class="id-code-pill"><?= htmlspecialchars($passId) ?></span>
                    </div>

                    <div class="id-pass-body">
                        <div class="id-avatar-box">
                            <div class="avatar-holder" style="background: <?= htmlspecialchars($currentUser['avatar_color'] ?? '#059669') ?>;">
                                <?= mb_substr($name ?: 'U', 0, 1) ?>
                            </div>
                            <div class="id-meta-text">
                                <h3 class="id-user-name"><?= htmlspecialchars($name ?: 'Tình Nguyện Viên') ?></h3>
                                <span class="id-user-role"><?= htmlspecialchars($currentUser['role'] ?? 'Tình Nguyện Viên Nòng Cốt') ?></span>
                                <span class="id-user-unit"><?= htmlspecialchars($unit) ?></span>
                            </div>
                        </div>

                        <div class="id-stats-grid" style="grid-template-columns: repeat(4, 1fr);">
                            <div class="stat-box">
                                <strong><?= $volunteerHours ?> giờ</strong>
                                <span>Tích lũy</span>
                            </div>
                            <div class="stat-box">
                                <strong><?= $eventsJoined ?></strong>
                                <span>Chiến dịch</span>
                            </div>
                            <div class="stat-box">
                                <strong><?= count($volunteerSkillsProfile) ?></strong>
                                <span>Kỹ năng</span>
                            </div>
                            <div class="stat-box">
                                <strong id="sidebar_avail_count"><?= count($regularAvailability) ?> ca</strong>
                                <span>Rảnh/tuần</span>
                            </div>
                        </div>

                        <div class="id-skills-summary">
                            <div class="id-skills-title">Kỹ Năng Đã Ghi Nhận:</div>
                            <div class="id-skills-pills-list">
                                <?php foreach ($volunteerSkillsProfile as $sk): ?>
                                    <span class="id-skill-pill <?= $sk['is_custom'] ? 'custom' : '' ?>" style="border-left: 3px solid <?= htmlspecialchars($sk['color']) ?>;">
                                        <?= htmlspecialchars($sk['name']) ?>
                                        <?php if (!empty($sk['is_certified'])): ?>
                                            <span class="cert-check">&#10003;</span>
                                        <?php elseif (!empty($sk['is_custom'])): ?>
                                            <span class="custom-dot" title="Kỹ năng do bạn tự thêm">*</span>
                                        <?php endif; ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="id-qr-box">
                            <svg class="qr-svg-preview" viewBox="0 0 100 100" width="110" height="110">
                                <rect width="100" height="100" fill="#ffffff" rx="6"/>
                                <rect x="10" y="10" width="26" height="26" fill="#059669" rx="3"/>
                                <rect x="15" y="15" width="16" height="16" fill="#ffffff" rx="1"/>
                                <rect x="19" y="19" width="8" height="8" fill="#059669"/>
                                <rect x="64" y="10" width="26" height="26" fill="#059669" rx="3"/>
                                <rect x="69" y="15" width="16" height="16" fill="#ffffff" rx="1"/>
                                <rect x="73" y="19" width="8" height="8" fill="#059669"/>
                                <rect x="10" y="64" width="26" height="26" fill="#059669" rx="3"/>
                                <rect x="15" y="69" width="16" height="16" fill="#ffffff" rx="1"/>
                                <rect x="19" y="73" width="8" height="8" fill="#059669"/>
                                <rect x="42" y="12" width="6" height="6" fill="#1e293b"/>
                                <rect x="52" y="18" width="6" height="6" fill="#1e293b"/>
                                <rect x="42" y="28" width="6" height="6" fill="#1e293b"/>
                                <rect x="52" y="38" width="6" height="6" fill="#1e293b"/>
                                <rect x="12" y="44" width="6" height="6" fill="#1e293b"/>
                                <rect x="22" y="52" width="6" height="6" fill="#1e293b"/>
                                <rect x="42" y="52" width="6" height="6" fill="#1e293b"/>
                                <rect x="64" y="44" width="6" height="6" fill="#1e293b"/>
                                <rect x="76" y="52" width="6" height="6" fill="#1e293b"/>
                                <rect x="84" y="64" width="6" height="6" fill="#1e293b"/>
                                <rect x="64" y="76" width="6" height="6" fill="#1e293b"/>
                                <rect x="74" y="84" width="6" height="6" fill="#1e293b"/>
                                <rect x="44" y="70" width="6" height="6" fill="#1e293b"/>
                                <rect x="52" y="82" width="6" height="6" fill="#1e293b"/>
                            </svg>
                            <span class="qr-note">Mã QR điểm danh</span>
                        </div>
                    </div>

                    <div class="id-pass-bottom">
                        <button type="button" class="btn-primary" style="width: 100%;" onclick="window.print()">
                            In Thẻ
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Thao tác bật tắt ô lịch rảnh
function toggleAvailSlot(cb) {
    var pill = cb.closest('.avail-slot-pill');
    var txt = pill.querySelector('.slot-text');
    if (cb.checked) {
        pill.classList.add('active');
        pill.style.borderColor = '#059669';
        pill.style.background = '#ecfdf5';
        pill.style.color = '#059669';
        txt.innerHTML = '&#10003; Rảnh';
    } else {
        pill.classList.remove('active');
        pill.style.borderColor = '#cbd5e1';
        pill.style.background = '#ffffff';
        pill.style.color = '#64748b';
        txt.innerHTML = 'Bận';
    }
    updateSidebarAvailCount();
}

// Áp dụng chọn nhanh khung giờ rảnh
function applyAvailPreset(type) {
    var cbs = document.querySelectorAll('input[name="regular_availability[]"]');
    cbs.forEach(function(cb) {
        var val = cb.value;
        if (type === 'all') {
            cb.checked = true;
        } else if (type === 'none') {
            cb.checked = false;
        } else if (type === 'weekend') {
            cb.checked = (val.indexOf('t7_') === 0 || val.indexOf('cn_') === 0);
        } else if (type === 'evening') {
            cb.checked = (val.indexOf('_toi') !== -1);
        }
        toggleAvailSlot(cb);
    });
}

function updateSidebarAvailCount() {
    var count = document.querySelectorAll('input[name="regular_availability[]"]:checked').length;
    var el = document.getElementById('sidebar_avail_count');
    if (el) el.innerText = count + ' ca';
}

// Thao tác thêm lịch bận đột xuất trong tương lai
document.addEventListener('DOMContentLoaded', function() {
    var btnBusy = document.getElementById('btn_add_busy_date');
    var inputDate = document.getElementById('input_busy_date');
    var inputSlot = document.getElementById('input_busy_slot');
    var inputReason = document.getElementById('input_busy_reason');
    var busyContainer = document.getElementById('busy_dates_container');

    if (btnBusy && inputDate && inputSlot && busyContainer) {
        btnBusy.addEventListener('click', function() {
            var dateVal = inputDate.value;
            if (!dateVal) {
                alert('Vui lòng chọn ngày bạn có lịch bận.');
                inputDate.focus();
                return;
            }
            var slotVal = inputSlot.value;
            var slotText = inputSlot.options[inputSlot.selectedIndex].text.replace('Chỉ bận ', '');
            var reasonVal = (inputReason.value || 'Bận việc riêng').trim();

            var dParts = dateVal.split('-');
            var displayDate = dParts[2] + '/' + dParts[1] + '/' + dParts[0];

            var idx = Date.now();
            var div = document.createElement('div');
            div.className = 'busy-date-tag';
            div.style.cssText = 'background: #ffffff; border: 1px solid #fecaca; border-left: 3px solid #dc2626; border-radius: 6px; padding: 8px 12px; font-size: 12.5px; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);';
            div.innerHTML = '<input type="hidden" name="busy_dates[' + idx + '][date]" value="' + escapeHtml(dateVal) + '">' +
                            '<input type="hidden" name="busy_dates[' + idx + '][slot]" value="' + escapeHtml(slotVal) + '">' +
                            '<input type="hidden" name="busy_dates[' + idx + '][reason]" value="' + escapeHtml(reasonVal) + '">' +
                            '<div><strong style="color: #991b1b;">' + escapeHtml(displayDate) + '</strong> ' +
                            '<span style="color: #64748b;">(' + escapeHtml(slotText) + '):</span> ' +
                            '<span style="color: #1e293b;">' + escapeHtml(reasonVal) + '</span></div>' +
                            '<button type="button" onclick="this.closest(\'.busy-date-tag\').remove()" style="background: none; border: none; color: #dc2626; font-size: 16px; font-weight: bold; cursor: pointer; line-height: 1;" title="Hủy báo bận">&times;</button>';
            busyContainer.appendChild(div);

            inputReason.value = '';
        });
    }

    // Thao tác thêm kỹ năng tự nhập bằng JavaScript
    var btnAdd = document.getElementById('btn_add_custom_skill');
    var input = document.getElementById('new_custom_skill_input');
    var container = document.getElementById('custom_skills_container');

    if (btnAdd && input && container) {
        function addSkill(text) {
            var val = (text || input.value).trim();
            if (!val) return;

            // Kiểm tra trùng lặp
            var existing = container.querySelectorAll('input[name="custom_skills[]"]');
            for (var i = 0; i < existing.length; i++) {
                if (existing[i].value.toLowerCase() === val.toLowerCase()) {
                    alert('Kỹ năng "' + val + '" đã có trong danh sách của bạn.');
                    return;
                }
            }

            var span = document.createElement('span');
            span.className = 'custom-skill-item-tag';
            span.innerHTML = '<input type="hidden" name="custom_skills[]" value="' + escapeHtml(val) + '">' +
                             '<span class="tag-text">' + escapeHtml(val) + '</span>' +
                             '<button type="button" class="btn-remove-tag" onclick="this.parentElement.remove()" title="Xóa">&times;</button>';
            container.appendChild(span);
            input.value = '';
        }

        btnAdd.addEventListener('click', function() {
            addSkill();
        });

        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addSkill();
            }
        });

        window.addSuggestedSkill = function(text) {
            addSkill(text);
        };
    }

    // =========================================================================
    // HỖ TRỢ ĐIỀU HƯỚNG TABS & LỌC DANH MỤC TRONG HỒ SƠ (VISUAL QUALITY)
    // =========================================================================
    window.switchProfileSection = function(sectionKey, btn) {
        var pills = document.querySelectorAll('.tab-profile-pill');
        pills.forEach(function(p) { p.classList.remove('active'); });
        if (btn) btn.classList.add('active');

        var secInfo = document.getElementById('section_info');
        var secSkills = document.getElementById('section_skills');
        var secSchedule = document.getElementById('section_schedule');
        var secEmergency = document.getElementById('section_emergency');
        var secCard = document.getElementById('section_card');

        var allSecs = [secInfo, secSkills, secSchedule, secEmergency];

        if (sectionKey === 'all') {
            allSecs.forEach(function(s) { if (s) s.style.display = ''; });
            if (secCard) secCard.style.display = '';
        } else if (sectionKey === 'info') {
            allSecs.forEach(function(s) { if (s) s.style.display = (s === secInfo ? '' : 'none'); });
            if (secCard) secCard.style.display = '';
            if (secInfo) secInfo.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else if (sectionKey === 'skills') {
            allSecs.forEach(function(s) { if (s) s.style.display = (s === secSkills ? '' : 'none'); });
            if (secCard) secCard.style.display = '';
            if (secSkills) secSkills.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else if (sectionKey === 'schedule') {
            allSecs.forEach(function(s) { if (s) s.style.display = (s === secSchedule ? '' : 'none'); });
            if (secCard) secCard.style.display = '';
            if (secSchedule) secSchedule.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else if (sectionKey === 'emergency') {
            allSecs.forEach(function(s) { if (s) s.style.display = (s === secEmergency ? '' : 'none'); });
            if (secCard) secCard.style.display = '';
            if (secEmergency) secEmergency.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else if (sectionKey === 'card') {
            allSecs.forEach(function(s) { if (s) s.style.display = ''; });
            if (secCard) {
                secCard.style.display = '';
                secCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    };

    // Kiểm tra URL param ?section=... khi người dùng bấm từ Header Dropdown
    try {
        var urlParams = new URLSearchParams(window.location.search);
        var initialSec = urlParams.get('section');
        if (initialSec) {
            var targetBtn = document.querySelector('.tab-profile-pill[onclick*="\'' + initialSec + '\'"]');
            if (targetBtn) {
                targetBtn.click();
            }
        }
    } catch(e) {}

    function escapeHtml(string) {
        return String(string).replace(/[&<>"'`=\/]/g, function (s) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
                '/': '&#x2F;',
                '`': '&#x60;',
                '=': '&#x3D;'
            }[s];
        });
    }
});
</script>

<?php require __DIR__ . '/../templates/footer.php'; ?>

