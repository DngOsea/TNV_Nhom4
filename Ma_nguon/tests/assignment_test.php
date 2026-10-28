<?php
declare(strict_types=1);

/**
 * BỘ KIỂM THỬ TỰ ĐỘNG — ĐỀ TÀI 06 (BÀI TẬP LỚN)
 * ĐỐI CHIẾU 5 PHÉP THỬ BẮT BUỘC (MỤC 6 TRONG ĐỀ BÀI)
 *
 * Phép thử 1: Một người không được phân vào hai ca trùng giờ.
 * Phép thử 2: Ca có yêu cầu kỹ năng không nhận người thiếu kỹ năng đó.
 * Phép thử 3: Sửa phân công làm vượt số người của ca phải bị từ chối.
 * Phép thử 4: Khi thiếu người, báo rõ các ca chưa đủ; không tạo thêm người giả để lấp chỗ.
 * Phép thử 5: Báo cáo so sánh dùng cùng dữ liệu và lưu cấu hình của từng thuật toán.
 */

require __DIR__ . '/helper.php';
require __DIR__ . '/../src/AssignmentEngine.php';

$btlData = require __DIR__ . '/../data/btl_dataset.php';
$shifts = $btlData['shifts'];
$volunteers = $btlData['volunteers'];

echo "============================================================\n";
echo "KIỂM THỬ ĐỀ TÀI 06: HỆ THỐNG PHÂN CÔNG TÌNH NGUYỆN VIÊN (D2L)\n";
echo "Đối chiếu 5 Phép thử bắt buộc theo Rubric BTL (Mục 6)\n";
echo "============================================================\n\n";

// =========================================================================
// PHÉP THỬ 1: MỘT NGƯỜI KHÔNG ĐƯỢC PHÂN VÀO HAI CA TRÙNG GIỜ
// =========================================================================
echo "--- PHÉP THỬ 1: KIỂM SOÁT TRÙNG LỊCH CA TRỰC ---\n";

// 1.1 Kiểm tra kết quả chạy thuật toán Smart Greedy trên 30 TNV và 12 ca
$resultSmart = AssignmentEngine::assignSmartGreedy($volunteers, $shifts);
same($resultSmart['metrics']['time_conflicts'], 0, '1.1-smart-algorithm-zero-time-conflicts');

// 1.2 Kiểm tra logic thẩm định phía máy chủ khi cố tình gán người trùng giờ
$sampleVolunteer = $volunteers[0]; // TNV-001
$targetShift = $shifts[2]; // CA-03 (2026-10-31_SANG)
$existingOtherShifts = [
    $shifts[0] // CA-01 (cũng vào 2026-10-31_SANG)
];
$validation1 = AssignmentEngine::validateReassignment(
    $sampleVolunteer,
    $targetShift,
    [],
    $existingOtherShifts
);
same($validation1['valid'], false, '1.2-reject-time-conflict-detected');
same($validation1['error_code'], 'TIME_CONFLICT', '1.3-time-conflict-error-code');

// =========================================================================
// PHÉP THỬ 2: CA CÓ YÊU CẦU KỸ NĂNG KHÔNG NHẬN NGƯỜI THIẾU KỸ NĂNG ĐÓ
// =========================================================================
echo "\n--- PHÉP THỬ 2: ĐÁP ỨNG KỸ NĂNG CHUYÊN MÔN ---\n";

// 2.1 Thuật toán không bao giờ gán người thiếu kỹ năng
same($resultSmart['metrics']['skill_violations'], 0, '2.1-smart-algorithm-zero-skill-violations');

// 2.2 Thẩm định từ chối khi gán người chỉ có kỹ năng Hậu cần vào ca Y tế
$unskilledVolunteer = [
    'id' => 'TNV-TEST-01',
    'name' => 'Nguyễn Văn Test',
    'skills' => ['hau_can'], // Không có y_te
];
$medicalShift = $shifts[2]; // CA-03: Yêu cầu 'y_te'
$validation2 = AssignmentEngine::validateReassignment(
    $unskilledVolunteer,
    $medicalShift,
    [],
    []
);
same($validation2['valid'], false, '2.2-reject-missing-skill');
same($validation2['error_code'], 'MISSING_SKILL', '2.3-missing-skill-error-code');

// 2.3 Cho phép khi TNV có đủ kỹ năng yêu cầu
$skilledVolunteer = [
    'id' => 'TNV-TEST-02',
    'name' => 'Trần Thị Y Sĩ',
    'skills' => ['y_te', 'dieu_phoi'],
];
$validation2b = AssignmentEngine::validateReassignment(
    $skilledVolunteer,
    $medicalShift,
    [],
    []
);
same($validation2b['valid'], true, '2.4-accept-qualified-volunteer');

// =========================================================================
// PHÉP THỬ 3: SỬA PHÂN CÔNG LÀM VƯỢT SỐ NGƯỜI CỦA CA PHẢI BỊ TỪ CHỐI
// =========================================================================
echo "\n--- PHÉP THỬ 3: TUÂN THỦ GIỚI HẠN QUÂN SỐ MỖI CA ---\n";

$shiftCapacity2 = [
    'id' => 'CA-TEST-CAPACITY',
    'shift_name' => 'Ca Thử Nghiệm Sĩ Số',
    'needed_count' => 2,
    'required_skills' => ['dieu_phoi'],
    'time_slot' => '2026-11-05_SANG',
];

// Danh sách ca hiện đã đủ 2 người
$alreadyFullAssigned = [
    ['id' => 'V_EXIST_1', 'name' => 'Người 1'],
    ['id' => 'V_EXIST_2', 'name' => 'Người 2'],
];

$candidateToOverfill = [
    'id' => 'V_EXTRA',
    'name' => 'Người Thừa Thứ 3',
    'skills' => ['dieu_phoi'],
];

$validation3 = AssignmentEngine::validateReassignment(
    $candidateToOverfill,
    $shiftCapacity2,
    $alreadyFullAssigned,
    []
);
same($validation3['valid'], false, '3.1-reject-over-capacity');
same($validation3['error_code'], 'OVER_CAPACITY', '3.2-over-capacity-error-code');

// =========================================================================
// PHÉP THỬ 4: KHI THIẾU NGƯỜI, BÁO RÕ CÁC CA CHƯA ĐỦ; KHÔNG TẠO NGƯỜI GIẢ
// =========================================================================
echo "\n--- PHÉP THỬ 4: TÍNH MINH BẠCH KHI THIẾU NHÂN SỰ (KHÔNG SINH NGƯỜI ẢO) ---\n";

// Tạo tập nhân sự bị thiếu hụt nghiêm trọng (chỉ 5 người cho 12 ca)
$scarceVolunteers = array_slice($volunteers, 0, 5);
$resultShortage = AssignmentEngine::assignSmartGreedy($scarceVolunteers, $shifts);

// 4.1 Báo cáo rõ số ca bị thiếu
same($resultShortage['metrics']['understaffed_shifts_count'] > 0, true, '4.1-report-understaffed-shifts');

// 4.2 Liệt kê chi tiết từng ca thiếu và chỉ rõ số người thiếu (deficit)
$hasDeficitDetail = false;
foreach ($resultShortage['understaffed_shifts'] as $underShift) {
    if ($underShift['deficit'] > 0) {
        $hasDeficitDetail = true;
        break;
    }
}
same($hasDeficitDetail, true, '4.2-detail-deficit-count-present');

// 4.3 Kiểm tra không sinh người ảo: Tất cả người được gán phải nằm trong danh sách đầu vào
$knownIds = array_column($scarceVolunteers, 'id');
$allAssignedAreReal = true;

foreach ($resultShortage['assignments'] as $sData) {
    foreach ($sData['assigned'] as $person) {
        if (!in_array($person['id'], $knownIds, true)) {
            $allAssignedAreReal = false;
            break 2;
        }
    }
}
same($allAssignedAreReal, true, '4.3-no-synthetic-dummy-volunteers-created');

// =========================================================================
// PHÉP THỬ 5: BÁO CÁO SO SÁNH DÙNG CÙNG DỮ LIỆU & LƯU CẤU HÌNH THUẬT TOÁN
// =========================================================================
echo "\n--- PHÉP THỬ 5: ĐỐI SOÁT & TÍNH NHẤT QUÁN THUẬT TOÁN ---\n";

// 5.1 Chạy đối soát lần 1
$benchmark1 = AssignmentEngine::compareAlgorithms($volunteers, $shifts);

// 5.2 Chạy đối soát lần 2 trên cùng tập dữ liệu
$benchmark2 = AssignmentEngine::compareAlgorithms($volunteers, $shifts);

// Đối chiếu tính nhất quán tuyệt đối (Deterministic Behavior)
same($benchmark1['fcfs']['metrics']['fill_rate'], $benchmark2['fcfs']['metrics']['fill_rate'], '5.1-benchmark-fcfs-consistency');
same($benchmark1['smart']['metrics']['fill_rate'], $benchmark2['smart']['metrics']['fill_rate'], '5.2-benchmark-smart-consistency');
same($benchmark1['comparison']['winner'], $benchmark2['comparison']['winner'], '5.3-benchmark-winner-consistency');

// 5.4 Thuật toán Smart Greedy phải có độ cân bằng tải (Workload Gap) tốt hơn hoặc bằng FCFS
$smartGap = $benchmark1['smart']['metrics']['workload_gap'];
$fcfsGap = $benchmark1['fcfs']['metrics']['workload_gap'];
same($smartGap <= $fcfsGap, true, '5.4-smart-greedy-better-or-equal-workload-balance');

echo "\n============================================================\n";
echo "TỔNG KẾT BỘ KIỂM THỬ:\n";
finish();
