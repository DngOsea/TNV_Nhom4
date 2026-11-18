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
require_once __DIR__ . '/Database.php';

class ActivityManager
{
    /**
     * Lấy toàn bộ danh sách hoạt động (đồng bộ từ Cơ Sở Dữ Liệu SQLite/MySQL + Session)
     */
    public static function getAllActivities(): array
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        $activities = [];

        // 1. Đồng bộ trực tiếp từ CSDL Database nếu có
        try {
            $db = Database::getInstance();
            if ($db->isAvailable()) {
                $dbProjects = $db->getProjects();
                if (!empty($dbProjects)) {
                    $activities = $dbProjects;
                }
            }
        } catch (Throwable $e) {
            error_log("ActivityManager::getAllActivities DB error: " . $e->getMessage());
        }

        // 2. Cơ chế dự phòng từ tập tin tĩnh volunteer_data.php nếu CSDL trống
        if (empty($activities)) {
            $baseData = require __DIR__ . '/../data/volunteer_data.php';
            $activities = $baseData['activities'] ?? [];
        }

        // 3. Hợp nhất với các hoạt động mới được tạo trong Session (nếu chưa có trong CSDL)
        $customActivities = $_SESSION['custom_activities'] ?? [];
        if (is_array($customActivities) && !empty($customActivities)) {
            $existingIds = array_map('strval', array_column($activities, 'id'));
            foreach ($customActivities as $cAct) {
                if (!in_array((string)($cAct['id'] ?? ''), $existingIds, true)) {
                    array_unshift($activities, $cAct);
                }
            }
        }

        // 4. Chuẩn hóa cấu trúc schedule_days & shifts cho từng hoạt động
        foreach ($activities as &$act) {
            if (empty($act['shifts'])) {
                if (!empty($act['shifts_list'])) {
                    $act['shifts'] = array_values(array_unique(array_column($act['shifts_list'], 'shift_name')));
                } else {
                    $act['shifts'] = ['Ca Sáng (08:00 – 12:00)', 'Ca Chiều (13:00 – 17:00)'];
                }
            }
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

        // 1. Tự động phân nhóm các ca trực từ CSDL nếu có shifts_list
        if (!empty($act['shifts_list']) && is_array($act['shifts_list'])) {
            $grouped = [];
            foreach ($act['shifts_list'] as $s) {
                $dCode = $s['day_code'] ?? 'day_1';
                $dName = $s['day_name'] ?? 'Ngày 1';
                if (!isset($grouped[$dCode])) {
                    $grouped[$dCode] = [
                        'day_code' => $dCode,
                        'day_name' => $dName,
                        'date_string' => $act['date_string'] ?? '',
                        'full_label' => $dName . ' (' . ($act['date_string'] ?? '') . ')',
                        'shifts' => []
                    ];
                }
                $grouped[$dCode]['shifts'][] = [
                    'shift_id' => $s['id'] ?? ($act['id'] . '-' . count($grouped[$dCode]['shifts'])),
                    'shift_name' => $s['shift_name'],
                    'time_range' => $s['time_range'] ?? '08:00 – 12:00',
                    'capacity' => (int)($s['capacity'] ?? 20),
                    'registered' => (int)($s['registered'] ?? 0),
                    'skill_req' => $s['skill_req'] ?? '',
                    'skill_group' => $s['skill_group'] ?? 'hau_can',
                ];
            }
            if (!empty($grouped)) {
                return array_values($grouped);
            }
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

        // Tích hợp lưu trữ trực tiếp vào CSDL Database
        $db = Database::getInstance();
        $newId = $db->createProject($data);
        if ($newId) {
            $act = $db->getProjectById($newId);
            if ($act) {
                return $act;
            }
        }

        $id = $newId ?: time();
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

        if (!isset($_SESSION['volunteer_shift_registrations']) || !is_array($_SESSION['volunteer_shift_registrations'])) {
            $_SESSION['volunteer_shift_registrations'] = self::getDefaultSeedRegistrations();
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
                        'status' => 'pending', // Chờ Ban Chỉ Huy phê duyệt ca trực
                        'coordinator_note' => '',
                        'reviewed_by' => '',
                        'reviewed_at' => '',
                        'is_new' => true,
                    ];
                    $_SESSION['volunteer_shift_registrations'][] = $item;
                    $registeredItems[] = $item;

                    // Lưu trực tiếp vào CSDL MySQL/SQLite
                    try {
                        Database::getInstance()->recordRegistration($item);
                    } catch (Throwable $e) {
                        error_log("ActivityManager: recordRegistration error: " . $e->getMessage());
                    }
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
     * Dữ liệu mẫu khởi tạo ban đầu cho danh mục đăng ký ca
     */
    public static function getDefaultSeedRegistrations(): array
    {
        return [
            [
                'registration_id' => 'REG-84A19B01',
                'user_id' => 'TNV-001',
                'user_name' => 'Lê Nguyễn Phương Thảo',
                'user_email' => 'thao.le@d2l.org',
                'user_phone' => '0988.555.771',
                'activity_id' => 101,
                'activity_title' => 'Chủ Nhật Xanh Hà Nội — Dọn Sạch Rác Tuyến Sông Tô Lịch & Cải Tạo Mảng Xanh Ven Bờ',
                'category' => 'thuc_te_moi_truong',
                'category_name' => 'Bảo Vệ Môi Trường',
                'location' => 'Cầu Cống Mọc, Đường Giáp Nhất, Thanh Xuân, Hà Nội',
                'meeting_point' => 'Cầu Cống Mọc, Đường Giáp Nhất, Thanh Xuân, Hà Nội',
                'leader_name' => 'Phạm Minh Quân',
                'leader_phone' => '0912.456.789',
                'day_label' => 'Thứ Bảy (24/10/2026)',
                'day_name' => 'Thứ Bảy',
                'shift_id' => 'CA-01',
                'shift_name' => 'Sáng Thứ Bảy (07:30 – 11:30)',
                'time_range' => '07:30 – 11:30',
                'skill_req' => 'hau_can',
                'note' => 'Em đã chuẩn bị sẵn ủng cao su và găng tay bảo hộ lao động.',
                'registered_at' => date('d/m/Y 08:30'),
                'status' => 'pending',
                'coordinator_note' => '',
                'reviewed_by' => '',
                'reviewed_at' => '',
                'is_new' => true,
            ],
            [
                'registration_id' => 'REG-32C87D02',
                'user_id' => 'TNV-004',
                'user_name' => 'Nguyễn Hà My',
                'user_email' => 'my.nguyen@d2l.org',
                'user_phone' => '0933.789.012',
                'activity_id' => 201,
                'activity_title' => 'Giọt Hồng Nghĩa Tình 2026 — Ngày Hội Hiến Máu Cứu Người Nghĩa Tình Thủ Đô',
                'category' => 'hien_mau_y_te',
                'category_name' => 'Hiến Máu Nhân Đạo & Hỗ Trợ Y Tế',
                'location' => 'Sảnh Tầng 2 Viện Huyết học — Truyền máu TW, Cầu Giấy, Hà Nội',
                'meeting_point' => 'Sảnh Tầng 2 Viện Huyết học — Truyền máu TW, Cầu Giấy, Hà Nội',
                'leader_name' => 'Lê Nguyễn Phương Thảo',
                'leader_phone' => '0912.456.789',
                'day_label' => 'Thứ Sáu (23/10/2026)',
                'day_name' => 'Thứ Sáu',
                'shift_id' => 'CA-03',
                'shift_name' => 'Sáng Thứ Sáu (07:30 – 11:30)',
                'time_range' => '07:30 – 11:30',
                'skill_req' => 'so_cap_cuu',
                'note' => 'Đã có chứng chỉ sơ cấp cứu cơ bản và kỹ năng đón tiếp người hiến máu.',
                'registered_at' => date('d/m/Y 09:15'),
                'status' => 'pending',
                'coordinator_note' => '',
                'reviewed_by' => '',
                'reviewed_at' => '',
                'is_new' => true,
            ],
            [
                'registration_id' => 'REG-55D42E03',
                'user_id' => 'TNV-003',
                'user_name' => 'Phạm Minh Quân',
                'user_email' => 'quan.pham@d2l.org',
                'user_phone' => '0908.123.456',
                'activity_id' => 301,
                'activity_title' => 'Tiếp Sức Mùa Thi Thủ Đô — Phân Luồng Giao Thông & Đội Xe Ôm Tình Nguyện',
                'category' => 'tiep_suc_giao_duc',
                'category_name' => 'Tiếp Sức Mùa Thi & Giáo Dục Cộng Đồng',
                'location' => 'Cổng Trường ĐH Sư Phạm Hà Nội & THPT Chu Văn An',
                'meeting_point' => 'Cổng Trường ĐH Sư Phạm Hà Nội',
                'leader_name' => 'Trần Hoàng Nam',
                'leader_phone' => '0912.456.789',
                'day_label' => 'Thứ Năm (05/11/2026)',
                'day_name' => 'Thứ Năm',
                'shift_id' => 'CA-06',
                'shift_name' => 'Sáng Thứ Năm (06:30 – 11:30)',
                'time_range' => '06:30 – 11:30',
                'skill_req' => 'giao_thong',
                'note' => 'Có xe máy cá nhân và mũ bảo hiểm dự phòng hỗ trợ đưa đón thí sinh.',
                'registered_at' => date('d/m/Y 07:00'),
                'status' => 'approved',
                'coordinator_note' => 'Ban Chỉ Huy đã duyệt - Phân công tổ xe ôm tình nguyện điểm thi ĐH Sư Phạm.',
                'reviewed_by' => 'Trần Hoàng Nam',
                'reviewed_at' => date('d/m/Y 08:00'),
                'is_new' => false,
            ],
            [
                'registration_id' => 'REG-18F90A04',
                'user_id' => 'TNV-005',
                'user_name' => 'Hoàng Quốc Bảo',
                'user_email' => 'bao.hoang@d2l.org',
                'user_phone' => '0912.345.678',
                'activity_id' => 501,
                'activity_title' => 'Mái Ấm Tuổi Già — Thăm Khám, Xoa Bóp Xương Khớp & Nấu Bữa Cơm Cho Cụ Già Viện Dưỡng Lão',
                'category' => 'cham_soc_yeu_thuong',
                'category_name' => 'Chăm Sóc Người Cao Tuổi & Trẻ Em Khuyết Tật',
                'location' => 'Viện Dưỡng Lão Diên Hồng, Hà Đông, Hà Nội',
                'meeting_point' => 'Viện Dưỡng Lão Diên Hồng, Hà Đông, Hà Nội',
                'leader_name' => 'Lê Nguyễn Phương Thảo',
                'leader_phone' => '0912.456.789',
                'day_label' => 'Chủ Nhật (01/11/2026)',
                'day_name' => 'Chủ Nhật',
                'shift_id' => 'CA-07',
                'shift_name' => 'Sáng Chủ Nhật (08:00 – 12:00)',
                'time_range' => '08:00 – 12:00',
                'skill_req' => 'cham_soc',
                'note' => 'Em đăng ký phụ trách khâu nấu ăn và phát quà.',
                'registered_at' => date('d/m/Y 10:20'),
                'status' => 'rejected',
                'coordinator_note' => 'Ca trực sáng đã đủ chỉ tiêu phân công nấu ăn. Mời đồng chí đăng ký ca chiều.',
                'reviewed_by' => 'Lê Nguyễn Phương Thảo',
                'reviewed_at' => date('d/m/Y 11:00'),
                'is_new' => false,
            ]
        ];
    }

    /**
     * Lấy toàn bộ danh sách đăng ký ca phục vụ Ban Chỉ Huy xét duyệt
     */
    public static function getAllShiftRegistrations(?string $status = null): array
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        $dbRegistrations = [];
        try {
            $db = Database::getInstance();
            if ($db->isAvailable()) {
                $dbRegistrations = $db->getShiftRegistrations($status);
            }
        } catch (Throwable $e) {
            error_log("ActivityManager: getAllShiftRegistrations DB error: " . $e->getMessage());
        }

        if (!empty($dbRegistrations)) {
            $_SESSION['volunteer_shift_registrations'] = $dbRegistrations;
            return $dbRegistrations;
        }

        if (!isset($_SESSION['volunteer_shift_registrations']) || !is_array($_SESSION['volunteer_shift_registrations'])) {
            $_SESSION['volunteer_shift_registrations'] = self::getDefaultSeedRegistrations();
        }
        $all = $_SESSION['volunteer_shift_registrations'];
        if ($status !== null && $status !== '' && $status !== 'all') {
            return array_values(array_filter($all, fn($r) => ($r['status'] ?? '') === $status));
        }
        return $all;
    }

    /**
     * Đếm số ca đăng ký đang chờ Ban Chỉ Huy duyệt
     */
    public static function getPendingRegistrationsCount(): int
    {
        $all = self::getAllShiftRegistrations('pending');
        return count($all);
    }

    /**
     * Ban Chỉ Huy phê duyệt hoặc từ chối đơn đăng ký ca
     */
    public static function reviewShiftRegistration(string $regId, string $decision, string $coordinatorName, string $note = ''): bool
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        if (!isset($_SESSION['volunteer_shift_registrations']) || !is_array($_SESSION['volunteer_shift_registrations'])) {
            $_SESSION['volunteer_shift_registrations'] = self::getDefaultSeedRegistrations();
        }

        $status = ($decision === 'approved' || $decision === 'confirmed') ? 'approved' : 'rejected';
        $now = date('d/m/Y H:i');
        $coordNote = $note ?: ($status === 'approved' ? 'Ban Chỉ Huy đã phê duyệt ca làm việc.' : 'Ban Chỉ Huy từ chối đơn đăng ký ca.');

        // 1. Cập nhật trực tiếp vào CSDL MySQL/MariaDB
        try {
            $db = Database::getInstance();
            if ($db->isAvailable()) {
                $db->reviewShiftRegistration($regId, $status, $coordinatorName, $coordNote);
            }
        } catch (Throwable $e) {
            error_log("ActivityManager: reviewShiftRegistration DB error: " . $e->getMessage());
        }

        // 2. Cập nhật trong Session
        $updatedInSession = false;
        foreach ($_SESSION['volunteer_shift_registrations'] as &$item) {
            if (($item['registration_id'] ?? '') === $regId) {
                $item['status'] = $status;
                $item['reviewed_by'] = $coordinatorName;
                $item['reviewed_at'] = $now;
                $item['coordinator_note'] = $coordNote;
                $updatedInSession = true;
                break;
            }
        }
        unset($item);

        return true;
    }

    /**
     * Lấy các hoạt động / phiên làm việc mà người dùng hiện tại đã đăng ký
     */
    public static function getUserRegisteredShifts(string $userEmail = '', string $userName = ''): array
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        try {
            $db = Database::getInstance();
            if ($db->isAvailable()) {
                $dbItems = $db->getUserShiftRegistrations($userEmail, $userName);
                if (!empty($dbItems)) {
                    return $dbItems;
                }
            }
        } catch (Throwable $e) {
            error_log("ActivityManager: getUserRegisteredShifts DB error: " . $e->getMessage());
        }

        if (!isset($_SESSION['volunteer_shift_registrations']) || !is_array($_SESSION['volunteer_shift_registrations'])) {
            $_SESSION['volunteer_shift_registrations'] = self::getDefaultSeedRegistrations();
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

        // Cập nhật CSDL
        try {
            $db = Database::getInstance();
            if ($db->isAvailable()) {
                $db->updateRegistrationStatus($regId, $newStatus);
            }
        } catch (Throwable $e) {
            error_log("ActivityManager: updateRegistrationStatus DB error: " . $e->getMessage());
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
        return true;
    }

    /**
     * Bóc tách tất cả các ngày diễn ra hoạt động thành định dạng Y-m-d
     */
    public static function extractDates(array $act): array
    {
        $dates = [];
        $text = ($act['date_string'] ?? '') . ' ';
        if (!empty($act['schedule_days']) && is_array($act['schedule_days'])) {
            foreach ($act['schedule_days'] as $d) {
                $text .= ($d['date_string'] ?? '') . ' ' . ($d['full_label'] ?? '') . ' ';
            }
        }
        // Khớp ngày dạng dd/mm/yyyy hoặc dd.mm.yyyy
        if (preg_match_all('/(\d{1,2})[\/\.](\d{1,2})[\/\.](\d{4})/', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
                $month = str_pad($m[2], 2, '0', STR_PAD_LEFT);
                $year = $m[3];
                $dates[] = "$year-$month-$day";
            }
        }
        // Khớp ngày dạng yyyy-mm-dd
        if (preg_match_all('/(\d{4})[\-](\d{1,2})[\-](\d{1,2})/', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $year = $m[1];
                $month = str_pad($m[2], 2, '0', STR_PAD_LEFT);
                $day = str_pad($m[3], 2, '0', STR_PAD_LEFT);
                $dates[] = "$year-$month-$day";
            }
        }
        return array_values(array_unique($dates));
    }

    /**
     * Lấy mốc thời gian sớm nhất của hoạt động (phục vụ sắp xếp thời gian)
     */
    public static function getEarliestTimestamp(array $act): int
    {
        $dates = self::extractDates($act);
        if (!empty($dates)) {
            sort($dates);
            $ts = strtotime($dates[0]);
            if ($ts !== false) {
                return $ts;
            }
        }
        if (!empty($act['created_at'])) {
            $ts = strtotime($act['created_at']);
            if ($ts !== false) {
                return $ts;
            }
        }
        return (int)($act['id'] ?? 0) * 10000;
    }

    /**
     * Kiểm tra xem hoạt động có khớp với ngày được chọn trên lịch hay không
     */
    public static function matchesDate(array $act, string $targetDate): bool
    {
        if (empty($targetDate)) {
            return true;
        }
        $targetTs = strtotime($targetDate);
        if ($targetTs === false) {
            return true;
        }

        $targetDateFormatted = date('Y-m-d', $targetTs);
        $dayOfWeek = (int)date('w', $targetTs); // 0 = CN, 1 = T2, ..., 6 = T7

        // 1. Kiểm tra ngày chính xác
        $dates = self::extractDates($act);
        if (in_array($targetDateFormatted, $dates, true)) {
            return true;
        }

        // 2. Nếu có từ 2 mốc ngày trở lên, kiểm tra xem ngày chọn có nằm trong khoảng [ngày đầu, ngày cuối] không
        if (count($dates) >= 2) {
            sort($dates);
            if ($targetDateFormatted >= $dates[0] && $targetDateFormatted <= end($dates)) {
                return true;
            }
        }

        // 3. Khớp các hoạt động diễn ra định kỳ theo Thứ (Chủ Nhật, Thứ Bảy, Trong tuần, 24/7...)
        $text = mb_strtolower(($act['date_string'] ?? '') . ' ' . ($act['title'] ?? '') . ' ' . ($act['summary'] ?? ''));
        if ($dayOfWeek === 0) {
            if (str_contains($text, 'chủ nhật') || str_contains($text, 'cuối tuần') || str_contains($text, '24/7')) {
                return true;
            }
        } elseif ($dayOfWeek === 6) {
            if (str_contains($text, 'thứ bảy') || str_contains($text, 'thứ 7') || str_contains($text, 'cuối tuần') || str_contains($text, '24/7')) {
                return true;
            }
        } else {
            $weekMap = [1 => 'thứ hai', 2 => 'thứ ba', 3 => 'thứ tư', 4 => 'thứ năm', 5 => 'thứ sáu'];
            $wName = $weekMap[$dayOfWeek] ?? '';
            if ($wName && str_contains($text, $wName)) {
                return true;
            }
            if (str_contains($text, 'trong tuần') || str_contains($text, 'thứ hai đến thứ sáu') || str_contains($text, 'hàng ngày') || str_contains($text, '24/7')) {
                return true;
            }
        }

        return false;
    }
}

