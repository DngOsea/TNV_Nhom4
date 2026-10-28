<?php
declare(strict_types=1);

/**
 * QUẢN LÝ LỰC LƯỢNG TÌNH NGUYỆN VIÊN (CRUD & PHÂN TRANG)
 * Hỗ trợ lưu trữ bền vững JSON, tìm kiếm theo kỹ năng/đơn vị/ca trực và phân trang
 */
class VolunteerManager
{
    private static string $storageFile = __DIR__ . '/../data/volunteers_store.json';

    /**
     * Khởi tạo danh sách TNV ban đầu từ btl_dataset.php
     */
    private static function initStorage(): array
    {
        $btlData = require __DIR__ . '/../data/btl_dataset.php';
        $initial = $btlData['volunteers'] ?? [];

        // Bổ sung các trường đầy đủ nếu chưa có
        $units = [
            'ĐH Bách Khoa Hà Nội',
            'ĐH Y Hà Nội',
            'ĐH Sư Phạm Hà Nội',
            'ĐH Ngoại Thương',
            'ĐH Kinh Tế Quốc Dân',
            'ĐH Giao Thông Vận Tải',
            'Thanh Niên Xung Kích Thành Đoàn',
        ];

        $i = 0;
        foreach ($initial as &$v) {
            if (empty($v['unit'])) {
                $v['unit'] = $units[$i % count($units)];
            }
            if (empty($v['email'])) {
                $v['email'] = strtolower(str_replace(' ', '', $v['id'])) . '@d2l.org';
            }
            if (!isset($v['status'])) {
                $v['status'] = 'active'; // active, busy, absent
            }
            if (!isset($v['hours'])) {
                $v['hours'] = rand(8, 48);
            }
            $i++;
        }
        unset($v);

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
     * Lấy toàn bộ danh sách tình nguyện viên
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
    public static function saveAll(array $volunteers): bool
    {
        $dir = dirname(self::$storageFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        return (bool)@file_put_contents(
            self::$storageFile,
            json_encode(array_values($volunteers), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * Tìm kiếm TNV theo ID
     */
    public static function getById(string $id): ?array
    {
        $all = self::getAll();
        foreach ($all as $v) {
            if (($v['id'] ?? '') === $id) {
                return $v;
            }
        }
        return null;
    }

    /**
     * Thêm mới tình nguyện viên
     */
    public static function create(array $data): string
    {
        $all = self::getAll();

        // Tạo mã TNV tự động
        $maxNum = 30;
        foreach ($all as $v) {
            if (preg_match('/^TNV-(\d+)$/', $v['id'] ?? '', $m)) {
                $num = (int)$m[1];
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }
        $newId = sprintf('TNV-%03d', $maxNum + 1);

        $newItem = [
            'id' => $newId,
            'name' => trim($data['name'] ?? 'Tình Nguyện Viên Mới'),
            'phone' => trim($data['phone'] ?? '0912.345.678'),
            'email' => trim($data['email'] ?? (strtolower($newId) . '@d2l.org')),
            'unit' => trim($data['unit'] ?? 'Thanh Niên Tình Nguyện Thủ Đô'),
            'skills' => is_array($data['skills'] ?? null) && !empty($data['skills']) ? $data['skills'] : ['dieu_phoi'],
            'available_shifts' => is_array($data['available_shifts'] ?? null) && !empty($data['available_shifts']) ? $data['available_shifts'] : ['CA-01', 'CA-02'],
            'registered_at' => date('Y-m-d H:i:s'),
            'max_shifts' => max(1, (int)($data['max_shifts'] ?? 2)),
            'status' => $data['status'] ?? 'active',
            'hours' => max(0, (int)($data['hours'] ?? 8)),
        ];

        array_unshift($all, $newItem);
        self::saveAll($all);

        return $newId;
    }

    /**
     * Chỉnh sửa thông tin tình nguyện viên
     */
    public static function update(string $id, array $data): bool
    {
        $all = self::getAll();
        $found = false;

        foreach ($all as &$v) {
            if (($v['id'] ?? '') === $id) {
                if (isset($data['name'])) $v['name'] = trim($data['name']);
                if (isset($data['phone'])) $v['phone'] = trim($data['phone']);
                if (isset($data['email'])) $v['email'] = trim($data['email']);
                if (isset($data['unit'])) $v['unit'] = trim($data['unit']);
                if (isset($data['skills']) && is_array($data['skills'])) $v['skills'] = $data['skills'];
                if (isset($data['available_shifts']) && is_array($data['available_shifts'])) $v['available_shifts'] = $data['available_shifts'];
                if (isset($data['max_shifts'])) $v['max_shifts'] = max(1, (int)$data['max_shifts']);
                if (isset($data['status'])) $v['status'] = $data['status'];
                if (isset($data['hours'])) $v['hours'] = max(0, (int)$data['hours']);
                $found = true;
                break;
            }
        }
        unset($v);

        if ($found) {
            return self::saveAll($all);
        }
        return false;
    }

    /**
     * Xóa tình nguyện viên
     */
    public static function delete(string $id): bool
    {
        $all = self::getAll();
        $filtered = array_filter($all, fn($v) => ($v['id'] ?? '') !== $id);

        if (count($filtered) !== count($all)) {
            return self::saveAll($filtered);
        }
        return false;
    }

    /**
     * Tìm kiếm và lọc danh sách
     */
    public static function search(array $volunteers, string $keyword = '', string $skill = '', string $status = ''): array
    {
        $kw = mb_strtolower(trim($keyword));
        $skill = trim($skill);
        $status = trim($status);

        return array_values(array_filter($volunteers, function($v) use ($kw, $skill, $status) {
            if ($skill !== '') {
                $hasSkill = in_array($skill, $v['skills'] ?? [], true);
                if (!$hasSkill) return false;
            }
            if ($status !== '' && ($v['status'] ?? 'active') !== $status) {
                return false;
            }
            if ($kw !== '') {
                $searchStr = mb_strtolower(($v['name'] ?? '') . ' ' . ($v['id'] ?? '') . ' ' . ($v['phone'] ?? '') . ' ' . ($v['email'] ?? '') . ' ' . ($v['unit'] ?? ''));
                if (!str_contains($searchStr, $kw)) {
                    return false;
                }
            }
            return true;
        }));
    }

    /**
     * Phân trang danh sách tình nguyện viên
     */
    public static function paginate(array $items, int $page = 1, int $perPage = 10): array
    {
        $total = count($items);
        $perPage = max(1, min(100, $perPage));
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
