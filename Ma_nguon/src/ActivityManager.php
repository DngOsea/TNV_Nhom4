<?php
declare(strict_types=1);

/**
 * QUẢN LÝ HOẠT ĐỘNG TÌNH NGUYỆN (CRUD & PHÂN TRANG)
 * Hỗ trợ lưu trữ bền vững JSON, tìm kiếm, phân trang và chỉnh sửa
 */
class ActivityManager
{
    private static string $storageFile = __DIR__ . '/../data/activities_store.json';

    /**
     * Khởi tạo file lưu trữ nếu chưa có từ volunteer_data.php
     */
    private static function initStorage(): array
    {
        $volunteerData = require __DIR__ . '/../data/volunteer_data.php';
        $initial = $volunteerData['activities'] ?? [];

        $dir = dirname(self::$storageFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        @file_put_contents(
            self::$storageFile, 
            json_encode($initial, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        return $initial;
    }

    /**
     * Lấy toàn bộ danh sách hoạt động
     */
    public static function getAll(): array
    {
        if (!file_exists(self::$storageFile)) {
            return self::initStorage();
        }

        $content = @file_get_contents(self::$storageFile);
        if (!$content) {
            return self::initStorage();
        }

        $data = json_decode($content, true);
        if (!is_array($data) || empty($data)) {
            return self::initStorage();
        }

        return $data;
    }

    /**
     * Lưu toàn bộ danh sách
     */
    public static function saveAll(array $activities): bool
    {
        $dir = dirname(self::$storageFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        return (bool)@file_put_contents(
            self::$storageFile,
            json_encode(array_values($activities), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * Lấy hoạt động theo ID
     */
    public static function getById(int $id): ?array
    {
        $all = self::getAll();
        foreach ($all as $item) {
            if ((int)$item['id'] === $id) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Thêm mới hoạt động
     */
    public static function create(array $data): int
    {
        $all = self::getAll();

        // Tìm ID lớn nhất để tăng tự động
        $maxId = 100;
        foreach ($all as $item) {
            if ((int)$item['id'] > $maxId) {
                $maxId = (int)$item['id'];
            }
        }
        $newId = $maxId + 1;

        $newItem = [
            'id' => $newId,
            'title' => trim($data['title'] ?? 'Chiến Dịch Tình Nguyện Mới'),
            'category' => $data['category'] ?? 'thuc_te_moi_truong',
            'category_name' => $data['category_name'] ?? 'Hoạt Động Tình Nguyện Thực Địa',
            'date_string' => trim($data['date_string'] ?? 'Dự kiến tháng 11/2026'),
            'time_range' => trim($data['time_range'] ?? '07:30 – 11:30'),
            'location' => trim($data['location'] ?? 'Khu vực Hà Nội'),
            'meeting_point' => trim($data['meeting_point'] ?? 'Tập trung theo thông báo Ban Chỉ Huy'),
            'capacity' => max(1, (int)($data['capacity'] ?? 20)),
            'registered' => max(0, (int)($data['registered'] ?? 0)),
            'leader_name' => trim($data['leader_name'] ?? 'Trần Hoàng Nam'),
            'leader_phone' => trim($data['leader_phone'] ?? '0912.456.789'),
            'shifts' => is_array($data['shifts'] ?? null) && !empty($data['shifts']) ? $data['shifts'] : ['Ca Sáng (07:30 – 11:30)'],
            'skills_needed' => is_array($data['skills_needed'] ?? null) && !empty($data['skills_needed']) ? $data['skills_needed'] : ['Thể lực tốt', 'Làm việc nhóm'],
            'benefits' => is_array($data['benefits'] ?? null) && !empty($data['benefits']) ? $data['benefits'] : ['Áo TNV', 'Giấy chứng nhận giờ tình nguyện'],
            'summary' => trim($data['summary'] ?? 'Tham gia các hoạt động thiết thực cống hiến vì cộng đồng.'),
            'status' => 'recruiting'
        ];

        array_unshift($all, $newItem);
        self::saveAll($all);

        return $newId;
    }

    /**
     * Chỉnh sửa hoạt động
     */
    public static function update(int $id, array $data): bool
    {
        $all = self::getAll();
        $found = false;

        foreach ($all as &$item) {
            if ((int)$item['id'] === $id) {
                if (isset($data['title'])) $item['title'] = trim($data['title']);
                if (isset($data['category'])) $item['category'] = $data['category'];
                if (isset($data['category_name'])) $item['category_name'] = $data['category_name'];
                if (isset($data['date_string'])) $item['date_string'] = trim($data['date_string']);
                if (isset($data['time_range'])) $item['time_range'] = trim($data['time_range']);
                if (isset($data['location'])) $item['location'] = trim($data['location']);
                if (isset($data['meeting_point'])) $item['meeting_point'] = trim($data['meeting_point']);
                if (isset($data['capacity'])) $item['capacity'] = max(1, (int)$data['capacity']);
                if (isset($data['registered'])) $item['registered'] = max(0, (int)$data['registered']);
                if (isset($data['leader_name'])) $item['leader_name'] = trim($data['leader_name']);
                if (isset($data['leader_phone'])) $item['leader_phone'] = trim($data['leader_phone']);
                if (isset($data['shifts']) && is_array($data['shifts'])) $item['shifts'] = $data['shifts'];
                if (isset($data['skills_needed']) && is_array($data['skills_needed'])) $item['skills_needed'] = $data['skills_needed'];
                if (isset($data['benefits']) && is_array($data['benefits'])) $item['benefits'] = $data['benefits'];
                if (isset($data['summary'])) $item['summary'] = trim($data['summary']);
                if (isset($data['status'])) $item['status'] = $data['status'];
                $found = true;
                break;
            }
        }
        unset($item);

        if ($found) {
            return self::saveAll($all);
        }
        return false;
    }

    /**
     * Xóa hoạt động
     */
    public static function delete(int $id): bool
    {
        $all = self::getAll();
        $filtered = array_filter($all, fn($item) => (int)$item['id'] !== $id);

        if (count($filtered) !== count($all)) {
            return self::saveAll($filtered);
        }
        return false;
    }

    /**
     * Lọc danh sách theo điều kiện
     */
    public static function filter(array $activities, string $cat = '', string $shift = '', string $keyword = ''): array
    {
        $kw = mb_strtolower(trim($keyword));
        $cat = trim($cat);
        $shift = trim($shift);

        return array_values(array_filter($activities, function($act) use ($cat, $shift, $kw) {
            if ($cat !== '' && ($act['category'] ?? '') !== $cat) {
                return false;
            }
            if ($shift !== '') {
                $hasShift = false;
                foreach ($act['shifts'] ?? [] as $s) {
                    if (stripos($s, $shift) !== false) {
                        $hasShift = true;
                        break;
                    }
                }
                if (!$hasShift) return false;
            }
            if ($kw !== '') {
                $searchContent = mb_strtolower(($act['title'] ?? '') . ' ' . ($act['summary'] ?? '') . ' ' . ($act['location'] ?? '') . ' ' . ($act['leader_name'] ?? ''));
                if (!str_contains($searchContent, $kw)) {
                    return false;
                }
            }
            return true;
        }));
    }

    /**
     * Phân trang dữ liệu
     */
    public static function paginate(array $items, int $page = 1, int $perPage = 6): array
    {
        $total = count($items);
        $perPage = max(1, min(50, $perPage));
        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));

        $offset = ($page - 1) * $perPage;
        $pageItems = array_slice($items, $offset, $perPage);

        return [
            'items' => $pageItems,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
            'has_prev' => $page > 1,
            'has_next' => $page < $totalPages,
            'prev_page' => max(1, $page - 1),
            'next_page' => min($totalPages, $page + 1),
            'start_index' => $total > 0 ? $offset + 1 : 0,
            'end_index' => min($total, $offset + $perPage)
        ];
    }
}
