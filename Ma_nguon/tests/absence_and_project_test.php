<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Auth.php';

$passed = 0;
$failed = 0;

function assertCondition(string $name, bool $condition, string $detail = ''): void {
    global $passed, $failed;
    if ($condition) {
        echo "PASS {$name}\n";
        $passed++;
    } else {
        echo "FAIL {$name}: {$detail}\n";
        $failed++;
    }
}

echo "============================================================\n";
echo "KIỂM THỬ TÍNH NĂNG: BÁO VẮNG 2 NGÀY & PHÊ DUYỆT BAN CHỈ HUY\n";
echo "============================================================\n";

$db = Database::getInstance();

// 1. Kiểm thử báo vắng < 2 ngày (< 48h) -> BẮT BUỘC TỪ CHỐI
$resultTooLate = $db->createAbsenceRequest([
    'volunteer_id' => 'TNV-005',
    'volunteer_name' => 'Lê Nguyễn Phương Thảo',
    'shift_id' => 'CA-01',
    'activity_title' => 'Chủ Nhật Xanh Hà Nội',
    'event_date' => date('d/m/Y', strtotime('+1 day')), // 1 ngày tới (< 2 ngày)
    'mock_days_prior' => 1.2,
    'reason' => 'Bận thi học kỳ'
]);

assertCondition(
    "1.1-reject-absence-under-2-days",
    $resultTooLate['success'] === false && $resultTooLate['error_code'] === 'ABSENCE_TOO_LATE',
    "Báo vắng dưới 2 ngày phải bị từ chối"
);

// 2. Kiểm thử báo vắng >= 2 ngày (>= 48h) -> HỢP LỆ, TRẠNG THÁI CHỜ DUYỆT (PENDING)
$resultValid = $db->createAbsenceRequest([
    'volunteer_id' => 'TNV-005',
    'volunteer_name' => 'Lê Nguyễn Phương Thảo',
    'volunteer_phone' => '0912.888.999',
    'shift_id' => 'CA-01',
    'activity_title' => 'Chủ Nhật Xanh Hà Nội',
    'event_date' => date('d/m/Y', strtotime('+4 days')), // 4 ngày tới
    'mock_days_prior' => 3.5,
    'reason' => 'Gia đình có việc hiếu hỷ',
    'detail_note' => 'Đã thông báo cho trưởng nhóm'
]);

assertCondition(
    "2.1-accept-absence-over-2-days",
    $resultValid['success'] === true && isset($resultValid['request_code']),
    "Báo vắng trên 2 ngày phải được chấp nhận tạo đơn"
);

$reqCode = $resultValid['request_code'];

// 3. Kiểm thử danh sách đơn chờ duyệt
$pendingList = $db->getAbsenceRequests('pending');
$foundPending = false;
foreach ($pendingList as $p) {
    if ($p['request_code'] === $reqCode) {
        $foundPending = true;
        break;
    }
}
assertCondition("3.1-absence-request-listed-as-pending", $foundPending === true);

// 4. Kiểm thử Ban Chỉ Huy phê duyệt đơn vắng (Approve)
$reviewOk = $db->reviewAbsenceRequest($reqCode, 'approved', 'Trần Hoàng Nam (Chỉ Huy)', 'Đồng ý cho vắng, đã điều người thay thế');
assertCondition("4.1-coordinator-approve-absence", $reviewOk === true);

$allApproved = $db->getAbsenceRequests('approved');
$isApproved = false;
foreach ($allApproved as $a) {
    if ($a['request_code'] === $reqCode && $a['status'] === 'approved') {
        $isApproved = true;
        break;
    }
}
assertCondition("4.2-absence-status-updated-to-approved", $isApproved === true);

// 5. Kiểm thử Ban Chỉ Huy từ chối đơn vắng (Reject)
$resultValid2 = $db->createAbsenceRequest([
    'volunteer_id' => 'TNV-009',
    'volunteer_name' => 'Hoàng Gia Bảo',
    'shift_id' => 'CA-02',
    'activity_title' => 'Chủ Nhật Xanh Hà Nội',
    'mock_days_prior' => 2.5,
    'reason' => 'Bận việc riêng'
]);
$reqCode2 = $resultValid2['request_code'];
$reviewReject = $db->reviewAbsenceRequest($reqCode2, 'rejected', 'Trần Hoàng Nam (Chỉ Huy)', 'Ca trực thiếu quân số, yêu cầu có mặt');
assertCondition("5.1-coordinator-reject-absence", $reviewReject === true);

$allRejected = $db->getAbsenceRequests('rejected');
$isRejected = false;
foreach ($allRejected as $r) {
    if ($r['request_code'] === $reqCode2 && $r['status'] === 'rejected') {
        $isRejected = true;
        break;
    }
}
assertCondition("5.2-absence-status-updated-to-rejected", $isRejected === true);

echo "\n============================================================\n";
echo "KIỂM THỬ TÍNH NĂNG: CHỈNH SỬA THÔNG TIN DỰ ÁN\n";
echo "============================================================\n";

$testProj = $db->getProjectById(101);
assertCondition("6.1-fetch-project-by-id", $testProj !== null && (int)$testProj['id'] === 101);

$originalTitle = $testProj['title'];
$newTitle = 'Chủ Nhật Xanh Hà Nội - Ra Quân Đợt Mới';
$updateOk = $db->updateProject(101, [
    'title' => $newTitle,
    'category' => $testProj['category'] ?? 'thuc_te_moi_truong',
    'date_string' => 'Thứ Bảy & Chủ Nhật, 14-15/11/2026',
    'time_range' => '07:30 – 17:00',
    'location' => 'Quận Hoàn Kiếm, Hà Nội',
    'meeting_point' => 'Tượng đài Lý Thái Tổ',
    'capacity' => 25,
    'leader_name' => 'Trần Hoàng Nam',
    'leader_phone' => '0912.345.678',
    'summary' => 'Dự án đã được ban chỉ huy cập nhật thông tin.',
    'status' => 'recruiting',
    'is_urgent' => 1
]);

assertCondition("6.2-update-project-success", $updateOk === true);

$reloadedProj = $db->getProjectById(101);
assertCondition("6.3-verify-updated-fields", $reloadedProj['title'] === $newTitle && (int)$reloadedProj['capacity'] === 25);

// Khôi phục lại tên ban đầu
$db->updateProject(101, [
    'title' => $originalTitle,
    'category' => $testProj['category'] ?? 'thuc_te_moi_truong',
    'date_string' => $testProj['date_string'] ?? '24/10/2026 – 25/10/2026',
    'time_range' => $testProj['time_range'] ?? '08:00 – 17:00',
    'location' => $testProj['location'] ?? 'Bờ Hồ Hoàn Kiếm, Hà Nội',
    'meeting_point' => $testProj['meeting_point'] ?? 'Tượng đài Lý Thái Tổ',
    'capacity' => (int)($testProj['capacity'] ?? 20),
    'leader_name' => $testProj['leader_name'] ?? 'Trần Hoàng Nam',
    'leader_phone' => $testProj['leader_phone'] ?? '0912.345.678',
    'summary' => $testProj['summary'] ?? '',
    'status' => $testProj['status'] ?? 'recruiting',
    'is_urgent' => (int)($testProj['is_urgent'] ?? 0)
]);

echo "\n============================================================\n";
echo "TỔNG KẾT KIỂM THỬ:\n";
echo "TESTS=" . ($passed + $failed) . " FAILED={$failed}\n";

if ($failed > 0) {
    exit(1);
}
exit(0);

