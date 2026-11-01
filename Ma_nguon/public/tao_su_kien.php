<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/Input.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/SkillManager.php';
require_once __DIR__ . '/../src/ActivityManager.php';

// Chỉ người điều phối (Ban Chỉ Huy) mới có quyền truy cập
$currentUser = Auth::requireCoordinator('/dang_nhap.php');

$errorMsg = '';
$successMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim(field($_POST, 'title'));
    $category = field($_POST, 'category');
    $location = trim(field($_POST, 'location'));
    $meetingPoint = trim(field($_POST, 'meeting_point'));
    $leaderName = trim(field($_POST, 'leader_name')) ?: $currentUser['name'];
    $leaderPhone = trim(field($_POST, 'leader_phone')) ?: ($currentUser['phone'] ?? '0912.456.789');
    $summary = trim(field($_POST, 'summary'));

    // Đọc cấu trúc các ngày và các ca được setup từ form
    $daysPost = $_POST['days'] ?? [];
    $scheduleDays = [];

    if (empty($title)) {
        $errorMsg = 'Vui lòng nhập tên chiến dịch tình nguyện.';
    } elseif (empty($daysPost) || !is_array($daysPost)) {
        $errorMsg = 'Vui lòng thiết lập ít nhất một ngày hoạt động và các ca trực tương ứng.';
    } else {
        foreach ($daysPost as $dIdx => $dData) {
            $dayName = trim($dData['day_name'] ?? ('Ngày ' . ($dIdx + 1)));
            $dateString = trim($dData['date_string'] ?? '');
            $shiftsData = $dData['shifts'] ?? [];
            $shiftsList = [];

            foreach ($shiftsData as $sIdx => $sData) {
                $sName = trim($sData['name'] ?? 'Ca Sáng');
                $sTime = trim($sData['time'] ?? '08:00 – 12:00');
                $sCap = max(1, (int)($sData['capacity'] ?? 15));
                $sReq = trim($sData['skill_req'] ?? 'Hậu cần khuân vác');
                $sGroup = SkillManager::detectSkillGroup($sReq);

                $shiftsList[] = [
                    'shift_id' => 'ACT-NEW-D' . ($dIdx + 1) . '-S' . ($sIdx + 1),
                    'shift_name' => $sName,
                    'time_range' => $sTime,
                    'capacity' => $sCap,
                    'registered' => 0,
                    'skill_req' => $sReq,
                    'skill_group' => $sGroup,
                ];
            }

            if (!empty($shiftsList)) {
                $scheduleDays[] = [
                    'day_code' => 'day_' . ($dIdx + 1),
                    'day_name' => $dayName,
                    'date_string' => $dateString,
                    'full_label' => $dayName . ($dateString !== '' ? " ($dateString)" : ''),
                    'shifts' => $shiftsList,
                ];
            }
        }

        if (empty($scheduleDays)) {
            $errorMsg = 'Mỗi ngày hoạt động cần có ít nhất một ca làm việc cụ thể.';
        } else {
            $categoriesList = [
                'thuc_te_moi_truong' => 'Hoạt Động Thực Địa & Môi Trường Xanh',
                'hien_mau_y_te' => 'Hiến Máu Nhân Đạo & Hỗ Trợ Y Tế',
                'tiep_suc_giao_duc' => 'Tiếp Sức Mùa Thi & Giáo Dục Cộng Đồng',
                'cuu_tro_an_sinh' => 'Cứu Trợ Khẩn Cấp & Bếp Ăn 0 Đồng',
                'cham_soc_yeu_thuong' => 'Chăm Sóc Người Cao Tuổi & Trẻ Em Khuyết Tật',
            ];

            $newActivity = ActivityManager::createCustomActivity([
                'title' => $title,
                'category' => $category,
                'category_name' => $categoriesList[$category] ?? 'Hoạt Động Tình Nguyện',
                'date_string' => count($scheduleDays) . ' ngày (' . implode(', ', array_column($scheduleDays, 'day_name')) . ')',
                'location' => $location,
                'meeting_point' => $meetingPoint,
                'leader_name' => $leaderName,
                'leader_phone' => $leaderPhone,
                'summary' => $summary,
                'schedule_days' => $scheduleDays,
                'capacity' => array_sum(array_map(fn($d) => array_sum(array_column($d['shifts'], 'capacity')), $scheduleDays)),
            ]);

            $_SESSION['flash_message'] = 'Đã tạo thành công sự kiện mới với ' . count($scheduleDays) . ' ngày và bảng ca trực đa ca!';
            header('Location: /dang_ky_ca.php?event_id=' . $newActivity['id']);
            exit;
        }
    }
}

$allSkillGroups = SkillManager::getGroups();

require __DIR__ . '/../templates/header.php';
?>

<div class="page-heading-pro">
    <div class="heading-container">
        <span class="sub-badge">TẠO HOẠT ĐỘNG</span>
        <h1>Tạo Hoạt Động & Ca Trực</h1>
        <p>Tạo hoạt động mới và thiết lập các ca làm việc.</p>
    </div>
</div>

<div class="page-content-wrapper">
    <?php if ($errorMsg !== ''): ?>
        <div class="alert-error-box" role="alert" style="background: #fef2f2; border: 1px solid #ef4444; border-radius: 8px; padding: 14px 18px; margin-bottom: 20px;">
            <strong style="color: #991b1b;">Lỗi thiết lập:</strong>
            <span style="color: #b91c1c; margin-left: 6px;"><?= htmlspecialchars($errorMsg) ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="/tao_su_kien.php" class="pro-event-form">
        <!-- KHỐI 1: THÔNG TIN HOẠT ĐỘNG -->
        <div class="form-section-block" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
            <div class="section-block-title" style="margin-bottom: 18px;">
                <span class="num-step">1</span>
                <div>
                    <h3 style="margin: 0; font-size: 1.15rem; color: #1e293b;">Thông Tin Hoạt Động</h3>
                    <p style="margin: 2px 0 0; color: #64748b; font-size: 0.88rem;">Nhập tên sự kiện, chủ đề và thông tin điều phối thực địa.</p>
                </div>
            </div>

            <div class="form-row-2">
                <div class="form-field-pro">
                    <label for="title">Tên hoạt động <strong class="req">*</strong></label>
                    <input type="text" id="title" name="title" required placeholder="Ví dụ: Ngày Hội Sống Xanh Thủ Đô 2026 — Dọn Rác & Cải Tạo Mảng Xanh" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                </div>

                <div class="form-field-pro">
                    <label for="category">Nhóm chủ đề <strong class="req">*</strong></label>
                    <select id="category" name="category" required>
                        <option value="thuc_te_moi_truong">[MT] Hoạt Động Thực Địa & Môi Trường Xanh</option>
                        <option value="hien_mau_y_te">[HM] Hiến Máu Nhân Đạo & Hỗ Trợ Y Tế</option>
                        <option value="tiep_suc_giao_duc">[GD] Tiếp Sức Mùa Thi & Giáo Dục Cộng Đồng</option>
                        <option value="cuu_tro_an_sinh">[CT] Cứu Trợ Khẩn Cấp & Bếp Ăn 0 Đồng</option>
                        <option value="cham_soc_yeu_thuong">[CS] Chăm Sóc Người Cao Tuổi & Trẻ Em Khuyết Tật</option>
                    </select>
                </div>
            </div>

            <div class="form-row-2" style="margin-top: 14px;">
                <div class="form-field-pro">
                    <label for="location">Địa bàn triển khai <strong class="req">*</strong></label>
                    <input type="text" id="location" name="location" required placeholder="Ví dụ: Tuyến Kênh Tô Lịch, Cầu Giấy, Hà Nội" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
                </div>
                <div class="form-field-pro">
                    <label for="meeting_point">Điểm tập kết lực lượng</label>
                    <input type="text" id="meeting_point" name="meeting_point" placeholder="Ví dụ: Cổng Công viên Cầu Giấy lúc 07:15" value="<?= htmlspecialchars($_POST['meeting_point'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row-2" style="margin-top: 14px;">
                <div class="form-field-pro">
                    <label for="leader_name">Chỉ huy phụ trách</label>
                    <input type="text" id="leader_name" name="leader_name" value="<?= htmlspecialchars($currentUser['name']) ?>">
                </div>
                <div class="form-field-pro">
                    <label for="leader_phone">Số điện thoại chỉ huy</label>
                    <input type="tel" id="leader_phone" name="leader_phone" value="<?= htmlspecialchars($currentUser['phone'] ?? '0912.456.789') ?>">
                </div>
            </div>

            <div class="form-row-1" style="margin-top: 14px;">
                <div class="form-field-pro">
                    <label for="summary">Tóm tắt mục đích & Nhiệm vụ chính</label>
                    <textarea id="summary" name="summary" rows="2" placeholder="Tóm tắt ngắn gọn để tình nguyện viên nắm rõ yêu cầu..."><?= htmlspecialchars($_POST['summary'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- KHỐI 2: THIẾT LẬP CÁC NGÀY VÀ CÁC CA LÀM VIỆC (YÊU CẦU TRỌNG TÂM) -->
        <div class="form-section-block" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
            <div class="section-block-title" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span class="num-step">2</span>
                    <div>
                        <h3 style="margin: 0; font-size: 1.15rem; color: #1e293b;">Cấu Hình Thứ (Ngày) & Các Ca Làm Việc</h3>
                        <p style="margin: 2px 0 0; color: #64748b; font-size: 0.88rem;">Thiết lập các ngày diễn ra sự kiện. Mỗi ngày có thể có nhiều ca (Ví dụ: 8h-12h, 13h-17h).</p>
                    </div>
                </div>
                <button type="button" class="btn-primary" id="btn_add_day" style="padding: 8px 16px; font-size: 0.88rem; background: #059669;">
                    + Thêm Ngày Diễn Ra Mới
                </button>
            </div>

            <div id="days_container">
                <!-- NGÀY 1 (MẶC ĐỊNH) -->
                <div class="day-setup-card" data-day-index="0" style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 18px; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px dashed #cbd5e1; padding-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: 12px; flex: 1; max-width: 450px;">
                            <strong style="color: #059669; font-size: 1.05rem; white-space: nowrap;">Thứ (Ngày 1):</strong>
                            <input type="text" name="days[0][day_name]" value="Thứ Bảy" style="width: 140px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: 600;" placeholder="Ví dụ: Thứ Bảy">
                            <input type="text" name="days[0][date_string]" value="24/10/2026" style="width: 160px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px;" placeholder="Ví dụ: 24/10/2026">
                        </div>
                        <div>
                            <button type="button" class="btn-detail" onclick="addShiftToDay(0)" style="padding: 5px 12px; font-size: 0.82rem; margin-right: 6px;">
                                + Thêm Ca Trực
                            </button>
                        </div>
                    </div>

                    <!-- DANH SÁCH CA TRONG NGÀY 1 -->
                    <div class="shifts-container" id="shifts_container_0" style="display: flex; flex-direction: column; gap: 10px;">
                        <!-- CA SÁNG -->
                        <div class="shift-setup-row" style="display: flex; align-items: center; gap: 10px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px;">
                            <span style="font-weight: 700; color: #475569; font-size: 0.85rem; width: 60px;">Ca 1:</span>
                            <input type="text" name="days[0][shifts][0][name]" value="Ca Sáng" placeholder="Tên ca (Ca Sáng)" style="width: 120px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem;">
                            <input type="text" name="days[0][shifts][0][time]" value="08:00 – 12:00" placeholder="Khung giờ (08:00 – 12:00)" style="width: 150px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem;">
                            <input type="number" name="days[0][shifts][0][capacity]" value="20" min="1" placeholder="Chỉ tiêu" style="width: 90px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem;" title="Quân số cần tuyển">
                            <input type="text" name="days[0][shifts][0][skill_req]" value="Hậu cần khuân vác, Thể lực tốt" placeholder="Kỹ năng ưu tiên" style="flex: 1; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem;">
                        </div>

                        <!-- CA CHIỀU -->
                        <div class="shift-setup-row" style="display: flex; align-items: center; gap: 10px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px;">
                            <span style="font-weight: 700; color: #475569; font-size: 0.85rem; width: 60px;">Ca 2:</span>
                            <input type="text" name="days[0][shifts][1][name]" value="Ca Chiều" placeholder="Tên ca (Ca Chiều)" style="width: 120px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem;">
                            <input type="text" name="days[0][shifts][1][time]" value="13:00 – 17:00" placeholder="Khung giờ (13:00 – 17:00)" style="width: 150px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem;">
                            <input type="number" name="days[0][shifts][1][capacity]" value="15" min="1" placeholder="Chỉ tiêu" style="width: 90px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem;">
                            <input type="text" name="days[0][shifts][1][skill_req]" value="Phân loại rác, Bóc biển quảng cáo" placeholder="Kỹ năng ưu tiên" style="flex: 1; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem;">
                            <button type="button" onclick="this.parentElement.remove()" style="background: none; border: none; color: #dc2626; cursor: pointer; font-size: 1.1rem;" title="Xóa ca này">&times;</button>
                        </div>
                    </div>
                </div>

                <!-- NGÀY 2 (MẶC ĐỊNH) -->
                <div class="day-setup-card" data-day-index="1" style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 18px; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px dashed #cbd5e1; padding-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: 12px; flex: 1; max-width: 450px;">
                            <strong style="color: #059669; font-size: 1.05rem; white-space: nowrap;">Thứ (Ngày 2):</strong>
                            <input type="text" name="days[1][day_name]" value="Chủ Nhật" style="width: 140px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: 600;" placeholder="Ví dụ: Chủ Nhật">
                            <input type="text" name="days[1][date_string]" value="25/10/2026" style="width: 160px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px;" placeholder="Ví dụ: 25/10/2026">
                        </div>
                        <div>
                            <button type="button" class="btn-detail" onclick="addShiftToDay(1)" style="padding: 5px 12px; font-size: 0.82rem; margin-right: 6px;">
                                + Thêm Ca Trực
                            </button>
                            <button type="button" onclick="this.closest('.day-setup-card').remove()" class="btn-cancel" style="padding: 5px 10px; font-size: 0.82rem; color: #dc2626;">
                                Xóa Ngày Này
                            </button>
                        </div>
                    </div>

                    <!-- DANH SÁCH CA TRONG NGÀY 2 -->
                    <div class="shifts-container" id="shifts_container_1" style="display: flex; flex-direction: column; gap: 10px;">
                        <div class="shift-setup-row" style="display: flex; align-items: center; gap: 10px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px;">
                            <span style="font-weight: 700; color: #475569; font-size: 0.85rem; width: 60px;">Ca 1:</span>
                            <input type="text" name="days[1][shifts][0][name]" value="Ca Sáng" placeholder="Tên ca (Ca Sáng)" style="width: 120px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem;">
                            <input type="text" name="days[1][shifts][0][time]" value="08:00 – 12:00" placeholder="Khung giờ" style="width: 150px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem;">
                            <input type="number" name="days[1][shifts][0][capacity]" value="25" min="1" placeholder="Chỉ tiêu" style="width: 90px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem;">
                            <input type="text" name="days[1][shifts][0][skill_req]" value="Trồng cây, Khơi thông dòng chảy" placeholder="Kỹ năng ưu tiên" style="flex: 1; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem;">
                        </div>
                        <div class="shift-setup-row" style="display: flex; align-items: center; gap: 10px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px;">
                            <span style="font-weight: 700; color: #475569; font-size: 0.85rem; width: 60px;">Ca 2:</span>
                            <input type="text" name="days[1][shifts][1][name]" value="Ca Chiều" placeholder="Tên ca (Ca Chiều)" style="width: 120px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem;">
                            <input type="text" name="days[1][shifts][1][time]" value="13:00 – 17:00" placeholder="Khung giờ" style="width: 150px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem;">
                            <input type="number" name="days[1][shifts][1][capacity]" value="15" min="1" placeholder="Chỉ tiêu" style="width: 90px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem;">
                            <input type="text" name="days[1][shifts][1][skill_req]" value="Truyền thông chụp ảnh, Viết bài tổng kết" placeholder="Kỹ năng ưu tiên" style="flex: 1; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem;">
                            <button type="button" onclick="this.parentElement.remove()" style="background: none; border: none; color: #dc2626; cursor: pointer; font-size: 1.1rem;">&times;</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 14px; align-items: center;">
            <button type="submit" class="btn-primary" style="padding: 13px 36px; font-size: 1rem; font-weight: 700; border-radius: 8px;">
                Lưu & Công Bố Bảng Lịch Đa Ngày
            </button>
            <a href="/hoat_dong.php" class="btn-cancel" style="padding: 13px 24px;">Hủy Bỏ</a>
        </div>
    </form>
</div>

<script>
var nextDayIdx = 2;

document.getElementById('btn_add_day').addEventListener('click', function() {
    var dIdx = nextDayIdx++;
    var container = document.getElementById('days_container');
    var div = document.createElement('div');
    div.className = 'day-setup-card';
    div.setAttribute('data-day-index', dIdx);
    div.style = 'background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 18px; margin-bottom: 20px;';
    
    div.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px dashed #cbd5e1; padding-bottom: 10px;">
            <div style="display: flex; align-items: center; gap: 12px; flex: 1; max-width: 450px;">
                <strong style="color: #059669; font-size: 1.05rem; white-space: nowrap;">Thứ (Ngày ` + (dIdx + 1) + `):</strong>
                <input type="text" name="days[` + dIdx + `][day_name]" value="Thứ Bảy" style="width: 140px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: 600;" placeholder="Ví dụ: Thứ Bảy">
                <input type="text" name="days[` + dIdx + `][date_string]" value="" style="width: 160px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px;" placeholder="Ví dụ: 31/10/2026">
            </div>
            <div>
                <button type="button" class="btn-detail" onclick="addShiftToDay(` + dIdx + `)" style="padding: 5px 12px; font-size: 0.82rem; margin-right: 6px;">
                    + Thêm Ca Trực
                </button>
                <button type="button" onclick="this.closest('.day-setup-card').remove()" class="btn-cancel" style="padding: 5px 10px; font-size: 0.82rem; color: #dc2626;">
                    Xóa Ngày Này
                </button>
            </div>
        </div>
        <div class="shifts-container" id="shifts_container_` + dIdx + `" style="display: flex; flex-direction: column; gap: 10px;">
            <div class="shift-setup-row" style="display: flex; align-items: center; gap: 10px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px;">
                <span style="font-weight: 700; color: #475569; font-size: 0.85rem; width: 60px;">Ca 1:</span>
                <input type="text" name="days[` + dIdx + `][shifts][0][name]" value="Ca Sáng" placeholder="Tên ca" style="width: 120px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem;">
                <input type="text" name="days[` + dIdx + `][shifts][0][time]" value="08:00 – 12:00" placeholder="Khung giờ" style="width: 150px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem;">
                <input type="number" name="days[` + dIdx + `][shifts][0][capacity]" value="20" min="1" placeholder="Chỉ tiêu" style="width: 90px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem;">
                <input type="text" name="days[` + dIdx + `][shifts][0][skill_req]" value="Hậu cần & Phối hợp" placeholder="Kỹ năng ưu tiên" style="flex: 1; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem;">
            </div>
        </div>
    `;
    container.appendChild(div);
});

function addShiftToDay(dIdx) {
    var container = document.getElementById('shifts_container_' + dIdx);
    if (!container) return;
    var sIdx = container.querySelectorAll('.shift-setup-row').length;
    var div = document.createElement('div');
    div.className = 'shift-setup-row';
    div.style = 'display: flex; align-items: center; gap: 10px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px;';
    div.innerHTML = `
        <span style="font-weight: 700; color: #475569; font-size: 0.85rem; width: 60px;">Ca ` + (sIdx + 1) + `:</span>
        <input type="text" name="days[` + dIdx + `][shifts][` + sIdx + `][name]" value="Ca Chiều" placeholder="Tên ca" style="width: 120px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem;">
        <input type="text" name="days[` + dIdx + `][shifts][` + sIdx + `][time]" value="13:00 – 17:00" placeholder="Khung giờ" style="width: 150px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem;">
        <input type="number" name="days[` + dIdx + `][shifts][` + sIdx + `][capacity]" value="15" min="1" placeholder="Chỉ tiêu" style="width: 90px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem;">
        <input type="text" name="days[` + dIdx + `][shifts][` + sIdx + `][skill_req]" value="Theo điều động" placeholder="Kỹ năng ưu tiên" style="flex: 1; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem;">
        <button type="button" onclick="this.parentElement.remove()" style="background: none; border: none; color: #dc2626; cursor: pointer; font-size: 1.1rem;">&times;</button>
    `;
    container.appendChild(div);
}
</script>

<?php require __DIR__ . '/../templates/footer.php'; ?>

