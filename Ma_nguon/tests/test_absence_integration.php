<?php
require_once __DIR__ . '/../src/Database.php';

$db = Database::getInstance();

// 1. Tạo đơn báo vắng hợp lệ
$initCount = $db->getPendingAbsenceCount();
$res = $db->createAbsenceRequest([
    'volunteer_id' => 'TNV-CANCEL-01',
    'volunteer_name' => 'Tran Van Huy Vang',
    'shift_id' => 'CA-07',
    'activity_title' => 'Test Chien Dich Huy Vang',
    'event_date' => '2026-11-20',
    'reason' => 'Ban dot xuat',
    'mock_days_prior' => 4.0
]);

if (!$res['success']) {
    echo "FAIL: Could not create absence\n";
    exit(1);
}

$pendingCount = $db->getPendingAbsenceCount();
if ($pendingCount < $initCount + 1) {
    echo "FAIL: Pending count did not increase\n";
    exit(1);
}

// 2. TNV hủy báo vắng khi đang ở trạng thái pending
$cancelRes = $db->cancelAbsenceRequest('CA-07', 'Tran Van Huy Vang', 'TNV-CANCEL-01');
if (!$cancelRes['success']) {
    echo "FAIL: cancelAbsenceRequest failed\n";
    exit(1);
}

// Đơn không còn ở trạng thái pending nữa
$afterCancelCount = $db->getPendingAbsenceCount();
if ($afterCancelCount !== $initCount) {
    echo "FAIL: Pending count did not decrease back after cancel ($afterCancelCount vs $initCount)\n";
    exit(1);
}

// 3. Kiểm tra trường hợp TNV hủy báo vắng sau khi đã được BCH Approved
$res2 = $db->createAbsenceRequest([
    'volunteer_id' => 'TNV-CANCEL-02',
    'volunteer_name' => 'Le Thi Huy Vang',
    'shift_id' => 'CA-08',
    'activity_title' => 'Test Chien Dich Huy Vang 2',
    'event_date' => '2026-11-22',
    'reason' => 'Ban di cong tac',
    'mock_days_prior' => 3.5
]);

$db->reviewAbsenceRequest($res2['request_code'], 'approved', 'Chi Huy Truong', 'Dong y');

// Giả lập trạng thái trong session btl_duty_schedule
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['btl_duty_schedule']['CA-08'] = [
    'assigned_volunteers' => [
        ['id' => 'TNV-CANCEL-02', 'name' => 'Le Thi Huy Vang', 'status' => 'absent']
    ]
];

// TNV hủy báo vắng để đi lại
$cancelRes2 = $db->cancelAbsenceRequest('CA-08', 'Le Thi Huy Vang', 'TNV-CANCEL-02');
if (!$cancelRes2['success']) {
    echo "FAIL: cancelAbsenceRequest 2 failed\n";
    exit(1);
}

$volStatus = $_SESSION['btl_duty_schedule']['CA-08']['assigned_volunteers'][0]['status'];
if ($volStatus !== 'confirmed') {
    echo "FAIL: Volunteer status in btl_duty_schedule was not restored to confirmed (is: $volStatus)\n";
    exit(1);
}

echo "ALL_TESTS_PASSED: Cancel Absence Request feature is working flawlessly!\n";
