<?php
declare(strict_types=1);

/**
 * MODULE QUẢN LÝ HOẠT ĐỘNG, LỊCH TRỰC ĐA NGÀY & ĐIỀU PHỐI (D2L ACTIVITY MANAGER)
 * HỖ TRỢ:
 * 1. Hoạt động diễn ra trong nhiều ngày (Multi-day events)
 * 2. Phân chia cấu trúc 2 cột: Thứ (Ngày) và Ca làm việc (8h-12h, 13h-17h...)
 * 3. Cho phép Người điều phối (Ban Chỉ Huy) thiết lập/setup sự kiện mới
 * 4. Cho phép Tình nguyện viên đăng ký ca làm việc đa ngày/đa ca
 */

require_once __DIR__ . '/Input.php';
require_once __DIR__ . '/SkillManager.php';

class ActivityManager
{
    /**
     * Lấy toàn bộ danh sách hoạt động (bao gồm hoạt động mặc định + hoạt động do điều phối viên tạo mới)
     */
    public static function getAllActivities(): array
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        $baseData = require __DIR__ . '/../data/volunteer_data.php';
        $activities = $baseData['activities'] ?? [];

        // Hợp nhất với các hoạt động mới được tạo trong Session
        $customActivities = $_SESSION['custom_activities'] ?? [];
        if (is_array($customActivities) && !empty($customActivities)) {
            $activities = array_merge($customActivities, $activities);
        }

        // Chuẩn hóa cấu trúc schedule_days cho từng hoạt động
        foreach ($activities as &$act) {
            $act['schedule_days'] = self::ensureScheduleDays($act);
        }
        unset($act);

        return $activities;
    }

    /**
     * Tìm hoạt động theo ID
     */
    public static function findActivityById(int|string $id): ?array
    {
        $all = self::getAllActivities();
        foreach ($all as $act) {
            if ((string)$act['id'] === (string)$id) {
                return $act;
            }
        }
        return null;
    }

    /**
     * Đảm bảo mọi hoạt động đều có cấu trúc nhiều ngày (schedule_days) chia 2 cột: Thứ(ngày) và Ca làm việc
     */
    public static function ensureScheduleDays(array $act): array
    {
        if (!empty($act['schedule_days']) && is_array($act['schedule_days'])) {
            return $act['schedule_days'];
        }

        // Tự động phân tách đa ngày dựa trên thông tin sẵn có
        $days = [];
        $actId = (int)($act['id'] ?? 101);
        $title = $act['title'] ?? 'Chiến dịch tình nguyện';

        if ($actId === 101) { // Chủ Nhật Xanh Hà Nội
            $days = [
                [
                    'day_code' => 'day_1',
                    'day_name' => 'Thứ Bảy',
                    'date_string' => '24/10/2026',
                    'full_label' => 'Thứ Bảy (24/10/2026)',
                    'shifts' => [
                        [
                            'shift_id' => '101-D1-S1',
                            'shift_name' => 'Ca Sáng',
                            'time_range' => '08:00 – 12:00',
                            'capacity' => 20,
                            'registered' => 16,
                            'skill_req' => 'Hậu cần khuân vác, Thể lực tốt',
                            'skill_group' => 'hau_can',
                        ],
                        [
                            'shift_id' => '101-D1-S2',
                            'shift_name' => 'Ca Chiều',
                            'time_range' => '13:00 – 17:00',
                            'capacity' => 20,
                            'registered' => 12,
                            'skill_req' => 'Phân loại rác, Bóc xóa quảng cáo rác',
                            'skill_group' => 'hau_can',
                        ],
                    ],
                ],
                [
                    'day_code' => 'day_2',
                    'day_name' => 'Chủ Nhật',
                    'date_string' => '25/10/2026',
                    'full_label' => 'Chủ Nhật (25/10/2026)',
                    'shifts' => [
                        [
                            'shift_id' => '101-D2-S1',
                            'shift_name' => 'Ca Sáng',
                            'time_range' => '08:00 – 12:00',
                            'capacity' => 25,
                            'registered' => 19,
                            'skill_req' => 'Cải tạo mảng xanh ven sông, Trồng cây',
                            'skill_group' => 'hau_can',
                        ],
                        [
                            'shift_id' => '101-D2-S2',
                            'shift_name' => 'Ca Chiều',
                            'time_range' => '13:00 – 17:00',
                            'capacity' => 15,
                            'registered' => 7,
                            'skill_req' => 'Truyền thông, Chụp ảnh & Hoạt náo',
                            'skill_group' => 'truyen_thong',
                        ],
                    ],
                ],
            ];
        } elseif ($actId === 201) { // Giọt Hồng Nghĩa Tình
            $days = [
                [
                    'day_code' => 'day_1',
                    'day_name' => 'Thứ Sáu',
                    'date_string' => '23/10/2026',
                    'full_label' => 'Thứ Sáu (23/10/2026) — Tập Huấn & Chuẩn Bị',
                    'shifts' => [
                        [
                            'shift_id' => '201-D1-S1',
                            'shift_name' => 'Ca Chiều',
                            'time_range' => '14:00 – 17:30',
                            'capacity' => 15,
                            'registered' => 12,
                            'skill_req' => 'Tập huấn sơ cấp cứu & Hậu cần sắp bàn',
                            'skill_group' => 'y_te',
                        ],
                    ],
                ],
                [
                    'day_code' => 'day_2',
                    'day_name' => 'Thứ Bảy',
                    'date_string' => '24/10/2026',
                    'full_label' => 'Thứ Bảy (24/10/2026) — Chính Hội Hiến Máu',
                    'shifts' => [
                        [
                            'shift_id' => '201-D2-S1',
                            'shift_name' => 'Ca Sáng',
                            'time_range' => '06:30 – 12:00',
                            'capacity' => 25,
                            'registered' => 22,
                            'skill_req' => 'Đo huyết áp, Chăm sóc người hiến máu',
                            'skill_group' => 'y_te',
                        ],
                        [
                            'shift_id' => '201-D2-S2',
                            'shift_name' => 'Ca Chiều',
                            'time_range' => '13:00 – 17:00',
                            'capacity' => 15,
                            'registered' => 11,
                            'skill_req' => 'Cấp phát giấy chứng nhận, Tiếp đón',
                            'skill_group' => 'dieu_phoi',
                        ],
                    ],
                ],
            ];
        } elseif ($actId === 301) { // Tiếp Sức Mùa Thi
            $days = [
                [
                    'day_code' => 'day_1',
                    'day_name' => 'Thứ Năm',
                    'date_string' => '05/11/2026',
                    'full_label' => 'Thứ Năm (05/11/2026) — Ngày Làm Thủ Tục Thi',
                    'shifts' => [
                        [
                            'shift_id' => '301-D1-S1',
                            'shift_name' => 'Ca Sáng',
                            'time_range' => '06:30 – 11:30',
                            'capacity' => 20,
                            'registered' => 15,
                            'skill_req' => 'Phân luồng giao thông & Hướng dẫn phòng thi',
                            'skill_group' => 'dieu_phoi',
                        ],
                        [
                            'shift_id' => '301-D1-S2',
                            'shift_name' => 'Ca Chiều',
                            'time_range' => '13:30 – 17:30',
                            'capacity' => 20,
                            'registered' => 14,
                            'skill_req' => 'Đội xe máy tình nguyện đưa đón thí sinh',
                            'skill_group' => 'dieu_phoi',
                        ],
                    ],
                ],
                [
                    'day_code' => 'day_2',
                    'day_name' => 'Thứ Sáu',
                    'date_string' => '06/11/2026',
                    'full_label' => 'Thứ Sáu (06/11/2026) — Ngày Thi Chính Thức',
                    'shifts' => [
                        [
                            'shift_id' => '301-D2-S1',
                            'shift_name' => 'Ca Sáng',
                            'time_range' => '06:00 – 11:30',
                            'capacity' => 25,
                            'registered' => 21,
                            'skill_req' => 'Phát nước mát, Bánh ăn nhẹ & Cổ vũ sĩ tử',
                            'skill_group' => 'hau_can',
                        ],
                        [
                            'shift_id' => '301-D2-S2',
                            'shift_name' => 'Ca Chiều',
                            'time_range' => '13:00 – 17:30',
                            'capacity' => 20,
                            'registered' => 16,
                            'skill_req' => 'Giữ trật tự khu vực thi & Hướng dẫn phụ huynh',
                            'skill_group' => 'dieu_phoi',
                        ],
                    ],
                ],
            ];
        } else {
            // Mặc định sinh 2 ngày: Thứ Bảy và Chủ Nhật với 2 ca sáng (8h-12h) và chiều (13h-17h)
            $days = [
                [
                    'day_code' => 'day_1',
                    'day_name' => 'Thứ Bảy',
                    'date_string' => 'Giai đoạn 1',
                    'full_label' => 'Thứ Bảy (Ca Ngày 1)',
                    'shifts' => [
                        [
                            'shift_id' => $actId . '-D1-S1',
                            'shift_name' => 'Ca Sáng',
                            'time_range' => '08:00 – 12:00',
                            'capacity' => 15,
                            'registered' => 10,
                            'skill_req' => 'Theo phân công của Ban Chỉ Huy',
                            'skill_group' => 'hau_can',
                        ],
                        [
                            'shift_id' => $actId . '-D1-S2',
                            'shift_name' => 'Ca Chiều',
                            'time_range' => '13:00 – 17:00',
                            'capacity' => 15,
                            'registered' => 8,
                            'skill_req' => 'Theo phân công của Ban Chỉ Huy',
                            'skill_group' => 'hau_can',
                        ],
                    ],
                ],
                [
                    'day_code' => 'day_2',
                    'day_name' => 'Chủ Nhật',
                    'date_string' => 'Giai đoạn 2',
                    'full_label' => 'Chủ Nhật (Ca Ngày 2)',
                    'shifts' => [
                        [
                            'shift_id' => $actId . '-D2-S1',
                            'shift_name' => 'Ca Sáng',
                            'time_range' => '08:00 – 12:00',
                            'capacity' => 15,
                            'registered' => 12,
                            'skill_req' => 'Theo phân công của Ban Chỉ Huy',
                            'skill_group' => 'hau_can',
                        ],
                        [
                            'shift_id' => $actId . '-D2-S2',
                            'shift_name' => 'Ca Chiều',
                            'time_range' => '13:00 – 17:00',
                            'capacity' => 15,
                            'registered' => 6,
                            'skill_req' => 'Theo phân công của Ban Chỉ Huy',
                            'skill_group' => 'truyen_thong',
                        ],
                    ],
                ],
            ];
        }

        return $days;
    }

    /**
     * Người điều phối tạo sự kiện mới và thiết lập các ngày + ca làm việc
     */
    public static function createCustomActivity(array $data): array
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        $id = time(); // ID duy nhất
        $newAct = [
            'id' => $id,
            'title' => trim($data['title'] ?? 'Chiến dịch tình nguyện mới'),
            'category' => $data['category'] ?? 'thuc_te_moi_truong',
            'category_name' => $data['category_name'] ?? 'Hoạt Động Tình Nguyện',
            'date_string' => trim($data['date_string'] ?? 'Nhiều ngày thực địa'),
            'time_range' => 'Theo từng ca trực',
            'location' => trim($data['location'] ?? 'Địa bàn hoạt động'),
            'meeting_point' => trim($data['meeting_point'] ?? 'Tại địa bàn'),
            'capacity' => (int)($data['capacity'] ?? 30),
            'registered' => 0,
            'leader_name' => trim($data['leader_name'] ?? 'Ban Chỉ Huy'),
            'leader_phone' => trim($data['leader_phone'] ?? '0912.456.789'),
            'shifts' => ['Ca Sáng (08:00 – 12:00)', 'Ca Chiều (13:00 – 17:00)'],
            'skills_needed' => $data['skills_needed'] ?? ['Thể lực tốt', 'Làm việc nhóm'],
            'benefits' => ['Áo TNV', 'Nước uống', 'Giấy chứng nhận công tác xã hội'],
            'summary' => trim($data['summary'] ?? 'Chiến dịch do Ban Chỉ Huy phát động.'),
            'status' => 'recruiting',
            'schedule_days' => $data['schedule_days'] ?? [],
        ];

        if (empty($newAct['schedule_days'])) {
            $newAct['schedule_days'] = self::ensureScheduleDays($newAct);
        }

        if (!isset($_SESSION['custom_activities']) || !is_array($_SESSION['custom_activities'])) {
            $_SESSION['custom_activities'] = [];
        }
        $_SESSION['custom_activities'][] = $newAct;

        return $newAct;
    }

    /**
     * Tình nguyện viên đăng ký các ca làm việc nhiều ngày
     */
    public static function registerVolunteerShifts(array $user, int|string $activityId, array $selectedShiftIds, string $note = ''): array
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        $act = self::findActivityById($activityId);
        if (!$act) {
            return ['success' => false, 'message' => 'Không tìm thấy hoạt động cần đăng ký.'];
        }

        if (empty($selectedShiftIds)) {
            return ['success' => false, 'message' => 'Vui lòng chọn ít nhất một ca làm việc.'];
        }

        if (!isset($_SESSION['volunteer_shift_registrations'])) {
            $_SESSION['volunteer_shift_registrations'] = [];
        }

        $registeredItems = [];
        $days = $act['schedule_days'] ?? [];

        foreach ($days as $day) {
            foreach ($day['shifts'] as $shift) {
                if (in_array($shift['shift_id'], $selectedShiftIds, true)) {
                    $item = [
                        'registration_id' => 'REG-' . strtoupper(substr(md5(uniqid('', true)), 0, 8)),
                        'user_id' => $user['id'] ?? 'TNV-ME',
                        'user_name' => $user['name'] ?? 'Tình Nguyện Viên',
                        'user_email' => $user['email'] ?? '',
                        'user_phone' => $user['phone'] ?? '',
                        'activity_id' => $act['id'],
                        'activity_title' => $act['title'],
                        'category' => $act['category'] ?? 'thuc_te_moi_truong',
                        'category_name' => $act['category_name'] ?? 'Hoạt Động Tình Nguyện',
                        'location' => $act['location'],
                        'meeting_point' => $act['meeting_point'] ?? 'Tại thực địa',
                        'leader_name' => $act['leader_name'] ?? 'Ban Chỉ Huy',
                        'leader_phone' => $act['leader_phone'] ?? '0912.456.789',
                        'day_label' => $day['full_label'] ?? ($day['day_name'] . ' ' . $day['date_string']),
                        'day_name' => $day['day_name'] ?? 'Ngày hoạt động',
                        'shift_id' => $shift['shift_id'],
                        'shift_name' => $shift['shift_name'],
                        'time_range' => $shift['time_range'],
                        'skill_req' => $shift['skill_req'] ?? '',
                        'note' => $note,
                        'registered_at' => date('d/m/Y H:i'),
                        'status' => 'confirmed', // confirmed, absent, cancelled
                        'is_new' => true,
                    ];
                    $_SESSION['volunteer_shift_registrations'][] = $item;
                    $registeredItems[] = $item;
                }
            }
        }

        return [
            'success' => true,
            'count' => count($registeredItems),
            'items' => $registeredItems,
            'activity' => $act,
        ];
    }

    /**
     * Lấy các hoạt động / phiên làm việc mà người dùng hiện tại đã đăng ký
     */
    public static function getUserRegisteredShifts(string $userEmail = '', string $userName = ''): array
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $all = $_SESSION['volunteer_shift_registrations'] ?? [];
        if (empty($userEmail) && empty($userName)) {
            return $all;
        }

        $filtered = [];
        $eLower = strtolower(trim($userEmail));
        $nLower = strtolower(trim($userName));

        foreach ($all as $item) {
            $itemEmail = strtolower(trim($item['user_email'] ?? ''));
            $itemName = strtolower(trim($item['user_name'] ?? ''));

            if (($eLower !== '' && $itemEmail === $eLower) || ($nLower !== '' && $itemName === $nLower)) {
                $filtered[] = $item;
            }
        }
        return $filtered;
    }

    /**
     * Hủy đăng ký hoặc báo vắng đối với hoạt động đã đăng ký trực tuyến
     */
    public static function updateRegistrationStatus(string $regId, string $newStatus = 'absent', ?array $user = null): bool
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        if (!isset($_SESSION['volunteer_shift_registrations'])) {
            return false;
        }

        foreach ($_SESSION['volunteer_shift_registrations'] as &$item) {
            if ($item['registration_id'] === $regId) {
                // Kiểm tra chính chủ nếu có user truyền vào
                if ($user) {
                    $uEmail = strtolower(trim($user['email'] ?? ''));
                    $uName = strtolower(trim($user['name'] ?? ''));
                    $itemEmail = strtolower(trim($item['user_email'] ?? ''));
                    $itemName = strtolower(trim($item['user_name'] ?? ''));
                    if ($uEmail !== $itemEmail && $uName !== $itemName && !Auth::isCoordinator()) {
                        return false;
                    }
                }

                $item['status'] = $newStatus;
                return true;
            }
        }
        return false;
    }
}

