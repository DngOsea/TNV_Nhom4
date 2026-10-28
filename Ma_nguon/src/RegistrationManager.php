<?php
declare(strict_types=1);

/**
 * QUẢN LÝ ĐỒNG BỘ ĐƠN ĐĂNG KÝ TÌNH NGUYỆN VIÊN SANG BẢNG ĐIỀU PHỐI
 * Tự động kết nối 2 chiều giữa Đăng ký trực tuyến (register.php) và Ban Chỉ Huy (phan_cong.php)
 */
class RegistrationManager
{
    private static string $storageFile = __DIR__ . '/../data/volunteer_registrations.json';

    /**
     * Đọc toàn bộ danh sách đăng ký đã lưu
     */
    public static function getAll(): array
    {
        if (!file_exists(self::$storageFile)) {
            return [];
        }
        $content = @file_get_contents(self::$storageFile);
        if (!$content) {
            return [];
        }
        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Lưu một đơn đăng ký mới vào hệ thống
     */
    public static function save(array $registration): bool
    {
        $all = self::getAll();
        
        // Thêm trường thời gian đăng ký nếu chưa có
        if (empty($registration['registered_at'])) {
            $registration['registered_at'] = date('d/m/Y H:i');
        }

        // Chèn vào đầu danh sách
        array_unshift($all, $registration);

        // Giữ tối đa 100 đăng ký gần nhất
        if (count($all) > 100) {
            $all = array_slice($all, 0, 100);
        }

        $dir = dirname(self::$storageFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        return (bool)@file_put_contents(
            self::$storageFile, 
            json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * Ánh xạ mã hoạt động & ca làm việc sang mã ca trực trong BTL dataset (CA-01 đến CA-12)
     */
    public static function mapEventToShiftCode(string|int $eventId, string $shiftName): string
    {
        $id = (int)$eventId;
        $s = mb_strtolower($shiftName);

        return match ($id) {
            101 => 'CA-01', // Chủ Nhật Xanh Sông Tô Lịch -> CA-01 Sáng
            102 => 'CA-02', // Trồng rừng Tây Bắc -> CA-02 Chiều
            201 => 'CA-03', // Giọt Hồng Nghĩa Tình -> CA-03 Sáng
            202 => 'CA-04', // Viện K Tân Triều -> CA-04 Chiều
            301 => (str_contains($s, 'chiều') ? 'CA-06' : 'CA-05'), // Tiếp Sức Mùa Thi
            401 => 'CA-07', // Bếp Ăn 0 Đồng -> CA-07 Sáng
            501 => 'CA-08', // Mái Ấm Tuổi Vàng -> CA-08 Chiều
            103 => 'CA-09', // Ngày Hội Sống Xanh -> CA-09 Sáng
            default => 'CA-01'
        };
    }

    /**
     * Chuẩn hóa kỹ năng từ form đăng ký sang mã kỹ năng chuẩn BTL
     */
    public static function normalizeSkills(array $rawSkills): array
    {
        $map = [
            'Sơ cấp cứu & Y tế cơ bản' => 'y_te',
            'Thể lực tốt & Bưng bê hậu cần' => 'hau_can',
            'Truyền thông, viết bài & Chụp ảnh' => 'truyen_thong',
            'Hoạt náo viên, MC & Quản trò' => 'dieu_phoi',
            'Kỹ năng sư phạm, dạy kèm trẻ nhỏ' => 'su_pham',
            'Lái xe máy an toàn, rành đường xá' => 'hau_can',
            'Nấu ăn & Sơ chế thực phẩm bếp lớn' => 'hau_can',
            'Ngoại ngữ (Tiếng Anh/Pháp) giao tiếp' => 'truyen_thong',
        ];

        $normalized = [];
        foreach ($rawSkills as $sk) {
            if (isset($map[$sk])) {
                $normalized[] = $map[$sk];
            } elseif (in_array($sk, ['y_te', 'dieu_phoi', 'hau_can', 'truyen_thong', 'su_pham'], true)) {
                $normalized[] = $sk;
            }
        }
        return array_values(array_unique($normalized));
    }

    /**
     * Tự động đồng bộ các đơn đăng ký mới vào bảng phân công ca trực của Điều phối
     */
    public static function syncIntoSchedule(array &$dutySchedule): int
    {
        $registrations = self::getAll();
        $syncedCount = 0;

        foreach ($registrations as $reg) {
            $shiftCode = $reg['shift_code'] ?? 'CA-01';
            if (!isset($dutySchedule[$shiftCode])) {
                continue;
            }

            // Kiểm tra xem TNV này đã được gán vào ca trực chưa
            $exists = false;
            foreach ($dutySchedule[$shiftCode]['assigned_volunteers'] as $v) {
                if (($v['id'] ?? '') === ($reg['pass_id'] ?? '') || 
                    (strtolower($v['email'] ?? '') === strtolower($reg['email'] ?? ''))) {
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                $normSkills = self::normalizeSkills($reg['skills'] ?? []);
                $dutySchedule[$shiftCode]['assigned_volunteers'][] = [
                    'id' => $reg['pass_id'] ?? ('TNV-REG-' . rand(100, 999)),
                    'name' => $reg['name'],
                    'email' => $reg['email'],
                    'phone' => $reg['phone'] ?? '',
                    'skills' => !empty($normSkills) ? $normSkills : ($reg['skills'] ?? []),
                    'raw_skills' => $reg['skills'] ?? [],
                    'status' => 'confirmed',
                    'role' => 'TNV Đăng Ký Trực Tuyến',
                    'registered_online' => true,
                    'unit' => $reg['unit'] ?? 'Thanh niên tình nguyện',
                    'registered_at' => $reg['registered_at'] ?? date('d/m/Y H:i'),
                ];
                $syncedCount++;
            }
        }

        return $syncedCount;
    }
}
