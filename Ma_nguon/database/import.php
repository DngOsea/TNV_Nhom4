<?php
declare(strict_types=1);

/**
 * CLI DATABASE IMPORTER FOR D2L PLATFORM
 * Chạy lệnh: php Ma_nguon/database/import.php
 * Khởi tạo Schema và Nạp 50 Dự án & 50 Tình nguyện viên vào SQLite / MySQL
 */

require_once dirname(__DIR__) . '/src/Database.php';
require_once dirname(__DIR__) . '/src/SkillManager.php';

echo "============================================================\n";
echo "D2L VOLUNTEER PLATFORM — DATABASE IMPORT & SYNC TOOL\n";
echo "============================================================\n\n";

$db = Database::getInstance();

if (!$db->isAvailable()) {
    echo "ERROR: Không thể kết nối cơ sở dữ liệu PDO. Vui lòng kiểm tra pdo_sqlite hoặc pdo_mysql.\n";
    exit(1);
}

echo "1. Động cơ cơ sở dữ liệu: " . $db->getDriver() . "\n";
if ($db->getDriver() === 'sqlite') {
    echo "   Đường dẫn SQLite file: " . $db->getSqlitePath() . "\n";
}

echo "\n2. Tiến hành khởi tạo cấu trúc bảng (Schema DDL)...\n";
try {
    $db->initSchema();
    echo "   -> [OK] Đã tạo thành công các bảng: projects, volunteers, shifts, volunteer_registrations\n";
} catch (Throwable $e) {
    echo "   -> [ERROR] Lỗi tạo schema: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n3. Tiến hành nạp 50 Dự án & 50 Tình nguyện viên đa dạng thuộc tính...\n";
try {
    $result = $db->importSeedSql();
    echo "   -> [OK] Nạp dữ liệu hoàn tất thành công!\n";
} catch (Throwable $e) {
    echo "   -> [ERROR] Lỗi khi nạp dữ liệu: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n4. Thống kê dữ liệu trong Database:\n";
$stats = $db->getStats();
echo "   - Tổng số Dự án / Hoạt động:    " . $stats['projects_count'] . " dự án (5 nhóm chủ đề)\n";
echo "   - Tổng số Tình nguyện viên:    " . $stats['volunteers_count'] . " TNV (TNV-001 đến TNV-050)\n";
echo "   - Tổng số Ca làm việc (Shifts): " . $stats['shifts_count'] . " ca\n";
echo "   - Phân công mẫu điểm danh:      " . $stats['registrations_count'] . " lượt\n";
if ($stats['driver'] === 'SQLITE') {
    echo "   - Dung lượng SQLite Database:   " . $stats['file_size'] . "\n";
}

echo "\n5. Kiểm tra tính tương thích ứng dụng Web:\n";
require_once dirname(__DIR__) . '/src/ActivityManager.php';
$activities = ActivityManager::getAllActivities();
echo "   -> ActivityManager tải được: " . count($activities) . " hoạt động.\n";
echo "\n============================================================\n";
echo "HOÀN TẤT IMPORT DỮ LIỆU THÀNH CÔNG 100%!\n";
echo "============================================================\n";

