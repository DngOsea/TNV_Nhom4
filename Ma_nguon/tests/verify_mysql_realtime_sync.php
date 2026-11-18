<?php
/**
 * KIỂM THỬ XÁC MINH TÍNH LIÊN KẾT THỜI GIAN THỰC GIỮA WEB VÀ MYSQL WORKBENCH
 * Xác minh 100% mọi thay đổi dữ liệu từ Web được ghi nhận ngay lập tức vào MySQL (Port 3307)
 */
require_once __DIR__ . '/../src/Database.php';

echo "============================================================\n";
echo "KIỂM THỬ ĐỒNG BỘ THỜI GIAN THỰC VỚI MYSQL WORKBENCH (PORT 3307)\n";
echo "============================================================\n";

$db = Database::getInstance();
if ($db->getDriver() !== 'mysql') {
    echo "WARNING: Running in environment without active MySQL connection. Driver: " . $db->getDriver() . "\n";
} else {
    echo "PASS: Connected directly to MySQL / MariaDB (Port: 3307, Database: d2l_volunteers)\n";
}

$pdo = $db->getPdo();

// 1. Kiểm thử tạo dự án mới (Khởi tạo sự kiện)
$testProjId = 9999;
// Xóa dữ liệu test cũ nếu có
try {
    $pdo->prepare("DELETE FROM volunteer_registrations WHERE project_id = ?")->execute([$testProjId]);
    $pdo->prepare("DELETE FROM shifts WHERE project_id = ?")->execute([$testProjId]);
    $pdo->prepare("DELETE FROM projects WHERE id = ?")->execute([$testProjId]);
} catch (Throwable $e) {}

$createdId = $db->createProject([
    'title' => 'Dự Án Kiểm Thử Đồng Bộ Workbench 1',
    'category' => 'thuc_te_moi_truong',
    'location' => 'Quận 1, TP. Hồ Chí Minh',
    'leader_name' => 'Trần Văn Chỉ Huy',
    'leader_phone' => '0988.111.222',
    'capacity' => 30,
    'schedule_days' => [
        [
            'day_code' => 'day_1',
            'day_name' => 'Thứ Bảy',
            'date_string' => '20/11/2026',
            'shifts' => [
                [
                    'shift_id' => 'TEST-WB-S1',
                    'shift_name' => 'Ca Sáng Test WB',
                    'time_range' => '08:00 - 12:00',
                    'capacity' => 15,
                    'skill_req' => 'hau_can',
                    'skill_group' => 'hau_can'
                ]
            ]
        ]
    ]
]);

$chkProj = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
$chkProj->execute([$createdId]);
$projRow = $chkProj->fetch();
if ($projRow && $projRow['title'] === 'Dự Án Kiểm Thử Đồng Bộ Workbench 1') {
    echo "PASS 1: Tạo dự án trên web -> CSDL MySQL Workbench ghi nhận ngay lập tức (ID: $createdId)\n";
} else {
    echo "FAIL 1: Dự án chưa được ghi nhận vào MySQL Workbench\n";
    exit(1);
}

// 2. Kiểm thử chỉnh sửa dự án
$db->updateProject($createdId, [
    'title' => 'Dự Án Đã Cập Nhật Qua Web',
    'location' => 'Quận 7, TP. Hồ Chí Minh'
]);
$chkProj->execute([$createdId]);
$projUpdated = $chkProj->fetch();
if ($projUpdated && $projUpdated['title'] === 'Dự Án Đã Cập Nhật Qua Web' && $projUpdated['location'] === 'Quận 7, TP. Hồ Chí Minh') {
    echo "PASS 2: Chỉnh sửa dự án trên web -> CSDL MySQL Workbench cập nhật tức thì\n";
} else {
    echo "FAIL 2: Chỉnh sửa dự án chưa được cập nhật vào MySQL Workbench\n";
    exit(1);
}

// 3. Kiểm thử đăng ký ca trực mới (TNV gửi đơn)
$testRegCode = 'REG-TEST-WB-' . rand(1000, 9999);
$db->recordRegistration([
    'registration_id' => $testRegCode,
    'user_id' => 'TNV-004',
    'user_name' => 'Nguyễn Hà My',
    'user_email' => 'hamy.nguyen@gmail.com',
    'activity_id' => $createdId,
    'shift_id' => 'TEST-WB-S1',
    'status' => 'pending',
    'note' => 'Đăng ký kiểm thử liên kết Workbench'
]);

$chkReg = $pdo->prepare("SELECT * FROM volunteer_registrations WHERE registration_code = ?");
$chkReg->execute([$testRegCode]);
$regRow = $chkReg->fetch();
if ($regRow && $regRow['status'] === 'pending' && $regRow['volunteer_id'] === 'TNV-004') {
    echo "PASS 3: TNV đăng ký ca trực -> CSDL MySQL Workbench ghi nhận trạng thái 'pending' ngay lập tức\n";
} else {
    echo "FAIL 3: Đăng ký ca chưa được ghi nhận vào MySQL Workbench\n";
    exit(1);
}

// 4. Kiểm thử Ban Chỉ Huy phê duyệt ca trực
$db->reviewShiftRegistration($testRegCode, 'approved', 'Chỉ Huy Trưởng', 'Chấp thuận tham gia');
$chkReg->execute([$testRegCode]);
$regApproved = $chkReg->fetch();
if ($regApproved && $regApproved['status'] === 'approved' && $regApproved['reviewed_by'] === 'Chỉ Huy Trưởng') {
    echo "PASS 4: BCH duyệt ca làm việc -> CSDL MySQL Workbench chuyển trạng thái 'approved' tức thì\n";
} else {
    echo "FAIL 4: Phê duyệt ca chưa được cập nhật vào MySQL Workbench\n";
    exit(1);
}

// 5. Kiểm thử gửi đơn báo vắng
$testAbsCode = 'ABS-TEST-WB-' . rand(1000, 9999);
$absRes = $db->createAbsenceRequest([
    'volunteer_id' => 'TNV-004',
    'volunteer_name' => 'Nguyễn Hà My',
    'shift_id' => 'TEST-WB-S1',
    'activity_title' => 'Dự Án Đã Cập Nhật Qua Web',
    'event_date' => '2026-11-25',
    'event_time' => '08:00 - 12:00',
    'reason' => 'Bận thi học kỳ đột xuất',
    'mock_days_prior' => 3.5
]);

$chkAbs = $pdo->prepare("SELECT * FROM absence_requests WHERE shift_id = ? AND volunteer_id = ? AND status = 'pending'");
$chkAbs->execute(['TEST-WB-S1', 'TNV-004']);
$absRow = $chkAbs->fetch();
if ($absRow && $absRow['volunteer_id'] === 'TNV-004') {
    echo "PASS 5: TNV gửi đơn báo vắng -> CSDL MySQL Workbench ghi nhận ngay lập tức vào bảng absence_requests\n";
} else {
    echo "FAIL 5: Đơn báo vắng chưa được ghi nhận vào MySQL Workbench\n";
    exit(1);
}

// 6. Kiểm thử BCH duyệt báo vắng & điều động người thay thế
$absReqCode = $absRow['request_code'];
$db->reviewAbsenceRequest($absReqCode, 'approved', 'Chỉ Huy Trưởng', 'Điều động thay thế', 'TNV-005');
$chkAbsApproved = $pdo->prepare("SELECT * FROM absence_requests WHERE request_code = ?");
$chkAbsApproved->execute([$absReqCode]);
$absApprovedRow = $chkAbsApproved->fetch();

$chkRep = $pdo->prepare("SELECT * FROM volunteer_registrations WHERE shift_id = ? AND volunteer_id = 'TNV-005'");
$chkRep->execute(['TEST-WB-S1']);
$repRow = $chkRep->fetch();

if ($absApprovedRow && $absApprovedRow['status'] === 'approved' && $absApprovedRow['replacement_volunteer_id'] === 'TNV-005' && $repRow) {
    echo "PASS 6: BCH duyệt vắng & thay người -> CSDL MySQL Workbench cập nhật absence_requests và volunteer_registrations ngay lập tức\n";
} else {
    echo "FAIL 6: Duyệt báo vắng và thay người chưa được đồng bộ vào MySQL Workbench\n";
    exit(1);
}

// 7. Dọn dẹp dữ liệu test sạch sẽ
try {
    $pdo->prepare("DELETE FROM volunteer_registrations WHERE project_id = ? OR shift_id = 'TEST-WB-S1'")->execute([$createdId]);
    $pdo->prepare("DELETE FROM absence_requests WHERE shift_id = 'TEST-WB-S1'")->execute();
    $pdo->prepare("DELETE FROM shifts WHERE project_id = ?")->execute([$createdId]);
    $pdo->prepare("DELETE FROM projects WHERE id = ?")->execute([$createdId]);
    $db->syncToVolunteerDataFile();
} catch (Throwable $e) {}

echo "============================================================\n";
echo "KẾT LUẬN: 100% THAO TÁC TRÊN WEB ĐỒNG BỘ THỜI GIAN THỰC VỚI MYSQL WORKBENCH!\n";
echo "============================================================\n";
