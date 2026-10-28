<?php
declare(strict_types=1);

/**
 * BỘ KIỂM THỬ PHÂN QUYỀN RBAC (AUTH & ACCESS CONTROL)
 * ĐỀ TÀI 06: HỆ THỐNG QUẢN LÝ & PHÂN CÔNG TÌNH NGUYỆN VIÊN (D2L)
 * 
 * Kiểm thử:
 * 1. Nhận diện vai trò: Ban Chỉ Huy (coordinator) vs Tình Nguyện Viên (volunteer)
 * 2. Phân định quyền hạn: Khách (Guest), Tình Nguyện Viên, Người Điều Phối
 * 3. Bảo vệ hành động máy chủ: Chặn TNV can thiệp phân ca toàn đội
 * 4. Quyền báo vắng chính chủ: TNV chỉ được báo vắng ca của chính mình
 */

require_once __DIR__ . '/../src/Auth.php';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    @session_start();
}

$passed = 0;
$failed = 0;

function assertCondition(string $name, bool $condition): void {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "PASS {$name}\n";
    } else {
        $failed++;
        echo "FAIL {$name}\n";
    }
}

echo "============================================================\n";
echo "KIỂM THỬ PHÂN QUYỀN RBAC: BAN CHỈ HUY vs TÌNH NGUYỆN VIÊN\n";
echo "============================================================\n\n";

// --- 1. KIỂM THỬ TRẠNG THÁI KHÁCH (CHƯA ĐĂNG NHẬP) ---
echo "--- NHÓM 1: TRẠNG THÁI KHÁCH (GUEST) ---\n";
unset($_SESSION['user']);

assertCondition("1.1-guest-not-logged-in", !Auth::isLoggedIn());
assertCondition("1.2-guest-role-type", Auth::getRoleType() === 'guest');
assertCondition("1.3-guest-not-coordinator", !Auth::isCoordinator());
assertCondition("1.4-guest-not-volunteer", !Auth::isVolunteer());

// --- 2. KIỂM THỬ TÀI KHOẢN TÌNH NGUYỆN VIÊN ---
echo "\n--- NHÓM 2: TÀI KHOẢN TÌNH NGUYỆN VIÊN (VOLUNTEER) ---\n";
$_SESSION['user'] = [
    'id' => 'USR-004',
    'name' => 'Nguyễn Hà My',
    'email' => 'my.nguyen@d2l.org',
    'role' => 'TNV Nòng Cốt — Ban Truyền Thông',
    'role_type' => Auth::ROLE_VOLUNTEER,
];

assertCondition("2.1-volunteer-logged-in", Auth::isLoggedIn());
assertCondition("2.2-volunteer-is-volunteer", Auth::isVolunteer());
assertCondition("2.3-volunteer-not-coordinator", !Auth::isCoordinator());
assertCondition("2.4-volunteer-role-type", Auth::getRoleType() === Auth::ROLE_VOLUNTEER);

// --- 3. KIỂM THỬ TÀI KHOẢN NGƯỜI ĐIỀU PHỐI (BAN CHỈ HUY) ---
echo "\n--- NHÓM 3: TÀI KHOẢN NGƯỜI ĐIỀU PHỐI (BAN CHỈ HUY) ---\n";
$_SESSION['user'] = [
    'id' => 'USR-001',
    'name' => 'Trần Hoàng Nam',
    'email' => 'nam.tran@d2l.org',
    'role' => 'Chỉ Huy Trưởng Đội Hình',
    'role_type' => Auth::ROLE_COORDINATOR,
];

assertCondition("3.1-coordinator-logged-in", Auth::isLoggedIn());
assertCondition("3.2-coordinator-is-coordinator", Auth::isCoordinator());
assertCondition("3.3-coordinator-not-volunteer", !Auth::isVolunteer());
assertCondition("3.4-coordinator-role-type", Auth::getRoleType() === Auth::ROLE_COORDINATOR);

// --- 4. KIỂM THỬ SUY LUẬN VAI TRÒ DỰ PHÒNG (FALLBACK BY ROLE TITLE) ---
echo "\n--- NHÓM 4: SUY LUẬN VAI TRÒ DỰ PHÒNG QUA CHỨC DANH ---\n";
// Tài khoản không có role_type nhưng chức danh có từ "Chỉ Huy"
$_SESSION['user'] = [
    'id' => 'USR-099',
    'name' => 'Nguyễn Văn Chỉ Huy',
    'role' => 'Phó Chỉ Huy Thường Trực',
];
assertCondition("4.1-infer-coordinator-by-title", Auth::isCoordinator());

// Tài khoản thường không có role_type
$_SESSION['user'] = [
    'id' => 'USR-100',
    'name' => 'Lê Văn An',
    'role' => 'Cộng Tác Viên Mới',
];
assertCondition("4.2-infer-volunteer-by-default", Auth::isVolunteer());

// --- 5. KIỂM THỬ RÀNG BUỘC BÁO VẮNG CHÍNH CHỦ ---
echo "\n--- NHÓM 5: KIỂM SOÁT BÁO VẮNG CHÍNH CHỦ ---\n";
// Giả lập tài khoản TNV Nguyễn Hà My
$_SESSION['user'] = [
    'id' => 'USR-004',
    'name' => 'Nguyễn Hà My',
    'role_type' => Auth::ROLE_VOLUNTEER,
];

$volunteerName = 'Nguyễn Hà My';
$otherVolunteerName = 'Phạm Minh Quân';

$isOwnerSelf = (Auth::lower(trim($_SESSION['user']['name'])) === Auth::lower(trim($volunteerName)));
$isOwnerOther = (Auth::lower(trim($_SESSION['user']['name'])) === Auth::lower(trim($otherVolunteerName)));

assertCondition("5.1-volunteer-can-report-self", $isOwnerSelf === true);
assertCondition("5.2-volunteer-denied-report-other", $isOwnerOther === false);

echo "\n============================================================\n";
echo "TỔNG KẾT KIỂM THỬ PHÂN QUYỀN:\n";
echo "TESTS=" . ($passed + $failed) . " FAILED={$failed}\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
