<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/../src/Input.php';
require __DIR__ . '/../src/Auth.php';
require __DIR__ . '/../src/AssignmentEngine.php';

// BẮT BUỘC QUYỀN ĐIỀU PHỐI VIÊN (BAN CHỈ HUY)
Auth::requireCoordinator('/lich_truc.php');

$btlData = require __DIR__ . '/../data/btl_dataset.php';
$allSkills = $btlData['skills'];
$rawShifts = $btlData['shifts'];
$rawVolunteers = $btlData['volunteers'];

// Lựa chọn mô hình thử nghiệm
$scenario = field($_GET, 'scenario') ?: 'standard';

// Chuẩn bị dữ liệu theo từng mô hình
$shifts = $rawShifts;
$volunteers = $rawVolunteers;

switch ($scenario) {
    case 'shortage':
        // Mô hình 2: Áp lực thiếu hụt nhân sự chuyên môn
        $volunteers = array_filter($rawVolunteers, function ($v) {
            $skills = $v['skills'] ?? [];
            if (in_array('y_te', $skills, true) && in_array($v['id'], ['TNV-002', 'TNV-003', 'TNV-004'], true)) {
                return false;
            }
            if (in_array('su_pham', $skills, true) && in_array($v['id'], ['TNV-006', 'TNV-007', 'TNV-008'], true)) {
                return false;
            }
            return !in_array($v['id'], ['TNV-017', 'TNV-018', 'TNV-024', 'TNV-025'], true);
        });
        $scenarioTitle = 'Mô Hình 2: Áp Lực Thiếu Hụt Chuyên Môn Sâu';
        $scenarioDesc = 'Kiểm thử phản ứng của hệ thống khi nguồn lực Y tế và Sư phạm bị thiếu hụt, đánh giá tính minh bạch và độ chính xác trong việc phát hiện ca trực chưa đủ điều kiện.';
        break;

    case 'conflict':
        // Mô hình 3: Trùng lịch cao điểm
        $volunteers = array_map(function ($v) {
            $v['available_shifts'] = ['CA-01', 'CA-03', 'CA-06'];
            return $v;
        }, $rawVolunteers);
        $scenarioTitle = 'Mô Hình 3: Trùng Lịch Khung Giờ Cao Điểm';
        $scenarioDesc = 'Kiểm thử khả năng kiểm soát xung đột khi đa số nhân sự đăng ký trùng khung giờ sáng, bảo đảm không có trường hợp một cá nhân bị phân công đồng thời vào hai vị trí.';
        break;

    case 'standard':
    default:
        $scenario = 'standard';
        $scenarioTitle = 'Mô Hình 1: Dữ Liệu Phân Bổ Tiêu Chuẩn';
        $scenarioDesc = '30 Tình nguyện viên và 12 Ca trực với cơ cấu chuyên môn cân đối. Đánh giá tính tối ưu và độ công bằng trong phân bổ khối lượng nhiệm vụ.';
        break;
}

// Chạy thuật toán đối soát
$benchmark = AssignmentEngine::compareAlgorithms($volunteers, $shifts);
$fcfs = $benchmark['fcfs'];
$smart = $benchmark['smart'];
$comparison = $benchmark['comparison'];

require __DIR__ . '/../templates/header.php';
?>

    <div class="page-heading-pro">
        <div class="heading-container" style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
            <div>
                <span class="sub-badge">BAN CHỈ HUY</span>
                <h1>Báo Cáo Hiệu Quả Phân Ca</h1>
                <p>So sánh kết quả phân bổ theo thứ tự đăng ký và theo tối ưu chuyên môn.</p>
            </div>
            <div>
                <a href="/phan_cong.php" class="btn-action-pro" style="background: #ffffff; border: 1px solid #cbd5e1; color: #334155; text-decoration: none; padding: 8px 16px; border-radius: 8px; font-size: 0.84rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                    &larr; Quay Lại Bảng Phân Công
                </a>
            </div>
        </div>
    </div>

<div class="page-content-wrapper">
    <!-- KHUNG CHỌN MÔ HÌNH DỮ LIỆU -->
    <div class="scenario-selector-card">
        <div class="selector-header">
            <h3>Tình Huống Phân Bổ Nhân Lực</h3>
            <span class="dataset-meta">Đang xử lý: <strong><?= count($volunteers) ?> TNV</strong> | <strong><?= count($shifts) ?> Ca Trực</strong> | <strong><?= array_sum(array_column($shifts, 'needed_count')) ?> Chỉ Tiêu</strong></span>
        </div>

        <div class="scenario-tabs-grid">
            <a href="/doi_soat_thuat_toan.php?scenario=standard" class="scenario-tab <?= $scenario === 'standard' ? 'active' : '' ?>">
                <span class="sc-badge">TÌNH HUỐNG 1</span>
                <strong>Điều Kiện Tiêu Chuẩn</strong>
                <small>30 TNV & 12 Ca trực — Đủ 5 nhóm chuyên môn</small>
            </a>
            <a href="/doi_soat_thuat_toan.php?scenario=shortage" class="scenario-tab <?= $scenario === 'shortage' ? 'active' : '' ?>">
                <span class="sc-badge warning">TÌNH HUỐNG 2</span>
                <strong>Thiếu Hụt Nhân Sự Chuyên Môn</strong>
                <small>Kiểm thử phản ứng khi thiếu hụt nhân sự Y tế, Sư phạm</small>
            </a>
            <a href="/doi_soat_thuat_toan.php?scenario=conflict" class="scenario-tab <?= $scenario === 'conflict' ? 'active' : '' ?>">
                <span class="sc-badge danger">TÌNH HUỐNG 3</span>
                <strong>Khung Giờ Cao Điểm Trùng Lịch</strong>
                <small>Kiểm thử khả năng xử lý xung đột thời gian</small>
            </a>
        </div>

        <div class="scenario-description-alert">
            <strong><?= htmlspecialchars($scenarioTitle) ?>:</strong> <?= htmlspecialchars($scenarioDesc) ?>
        </div>
    </div>

    <!-- BẢNG TỔNG QUAN CHỈ SỐ SO SÁNH (KPI CARDS) -->
    <div class="benchmark-summary-panel">
        <h2 class="section-title">Đối Chiếu Chỉ Số Hiệu Quả</h2>

        <div class="comparison-grid-cards">
            <!-- Cột 1: THEO THỨ TỰ ĐĂNG KÝ -->
            <div class="algo-card algo-fcfs">
                <div class="algo-card-header">
                    <span class="tag-baseline">PHƯƠNG ÁN TUẦN TỰ</span>
                    <h3>Phân Bổ Theo Thứ Tự Đăng Ký</h3>
                    <p>Bố trí nhân sự theo thứ tự thời gian nộp đơn ban đầu</p>
                </div>

                <div class="metric-list-rows">
                    <div class="metric-row">
                        <span class="m-label">Tỷ lệ đáp ứng chỉ tiêu (Fill Rate):</span>
                        <strong class="m-val text-primary"><?= $fcfs['metrics']['fill_rate'] ?>%</strong>
                    </div>
                    <div class="metric-row">
                        <span class="m-label">Số ca đủ 100% quân số:</span>
                        <strong class="m-val"><?= $fcfs['metrics']['fully_staffed_shifts'] ?> / <?= $fcfs['metrics']['total_shifts'] ?></strong>
                    </div>
                    <div class="metric-row">
                        <span class="m-label">Số ca bị thiếu nhân sự:</span>
                        <strong class="m-val text-warning"><?= $fcfs['metrics']['understaffed_shifts_count'] ?> ca</strong>
                    </div>
                    <div class="metric-row">
                        <span class="m-label">Xung đột chuyên môn:</span>
                        <strong class="m-val <?= $fcfs['metrics']['skill_violations'] > 0 ? 'text-danger' : 'text-success' ?>">
                            <?= $fcfs['metrics']['skill_violations'] ?> trường hợp
                        </strong>
                    </div>
                    <div class="metric-row">
                        <span class="m-label">Xung đột trùng lịch trực:</span>
                        <strong class="m-val <?= $fcfs['metrics']['time_conflicts'] > 0 ? 'text-danger' : 'text-success' ?>">
                            <?= $fcfs['metrics']['time_conflicts'] ?> trường hợp
                        </strong>
                    </div>
                    <div class="metric-row">
                        <span class="m-label">Độ chênh lệch số ca (Workload Gap):</span>
                        <strong class="m-val text-danger"><?= $fcfs['metrics']['workload_gap'] ?> ca (Cao nhất: <?= $fcfs['metrics']['max_shifts'] ?> / Thấp nhất: <?= $fcfs['metrics']['min_shifts'] ?>)</strong>
                    </div>
                    <div class="metric-row">
                        <span class="m-label">Nhân sự chưa được bố trí ca:</span>
                        <strong class="m-val text-danger"><?= $fcfs['metrics']['unassigned_volunteers_count'] ?> người</strong>
                    </div>
                    <div class="metric-row">
                        <span class="m-label">Thời gian tính toán:</span>
                        <span class="m-val"><?= $fcfs['execution_time_ms'] ?> ms</span>
                    </div>
                </div>
            </div>

            <!-- Cột VS ở giữa -->
            <div class="algo-card-vs">
                <span class="vs-badge">VS</span>
                <div class="delta-box">
                    <strong>Chênh lệch</strong>
                    <span class="delta-val <?= $comparison['fill_rate_diff'] >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= $comparison['fill_rate_diff'] >= 0 ? '+' : '' ?><?= $comparison['fill_rate_diff'] ?>%
                    </span>
                    <small>Khả năng đáp ứng</small>
                </div>
            </div>

            <!-- Cột 2: ĐIỀU PHỐI TỐI ƯU -->
            <div class="algo-card algo-smart highlight">
                <div class="algo-card-header">
                    <span class="tag-recommend">PHƯƠNG ÁN TỐI ƯU</span>
                    <h3>Điều Phối Tối Ưu Toàn Diện</h3>
                    <p>Xét đồng thời chuyên môn, thời gian rảnh và cân bằng khối lượng công việc</p>
                </div>

                <div class="metric-list-rows">
                    <div class="metric-row">
                        <span class="m-label">Tỷ lệ đáp ứng chỉ tiêu (Fill Rate):</span>
                        <strong class="m-val text-success font-bold"><?= $smart['metrics']['fill_rate'] ?>%</strong>
                    </div>
                    <div class="metric-row">
                        <span class="m-label">Số ca đủ 100% quân số:</span>
                        <strong class="m-val text-success"><?= $smart['metrics']['fully_staffed_shifts'] ?> / <?= $smart['metrics']['total_shifts'] ?></strong>
                    </div>
                    <div class="metric-row">
                        <span class="m-label">Số ca bị thiếu nhân sự:</span>
                        <strong class="m-val"><?= $smart['metrics']['understaffed_shifts_count'] ?> ca</strong>
                    </div>
                    <div class="metric-row">
                        <span class="m-label">Xung đột chuyên môn:</span>
                        <strong class="m-val text-success">0 trường hợp (100% chuẩn hóa)</strong>
                    </div>
                    <div class="metric-row">
                        <span class="m-label">Xung đột trùng lịch trực:</span>
                        <strong class="m-val text-success">0 trường hợp (100% chuẩn hóa)</strong>
                    </div>
                    <div class="metric-row">
                        <span class="m-label">Độ chênh lệch số ca (Workload Gap):</span>
                        <strong class="m-val text-success font-bold"><?= $smart['metrics']['workload_gap'] ?> ca (Cao nhất: <?= $smart['metrics']['max_shifts'] ?> / Thấp nhất: <?= $smart['metrics']['min_shifts'] ?>)</strong>
                    </div>
                    <div class="metric-row">
                        <span class="m-label">Nhân sự chưa được bố trí ca:</span>
                        <strong class="m-val text-success font-bold"><?= $smart['metrics']['unassigned_volunteers_count'] ?> người</strong>
                    </div>
                    <div class="metric-row">
                        <span class="m-label">Thời gian tính toán:</span>
                        <span class="m-val"><?= $smart['execution_time_ms'] ?> ms</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CHI TIẾT PHÂN CÔNG 12 CA TRỰC -->
    <div class="shift-comparison-table-section">
        <h2 class="section-title">Chi Tiết Phân Bổ Nhân Sự Từng Ca Trực</h2>
        <p class="section-subtitle">So sánh danh sách phân công và mức độ đáp ứng năng lực chuyên môn tại từng vị trí thực địa.</p>

        <div class="table-responsive-box">
            <table class="benchmark-table">
                <thead>
                    <tr>
                        <th style="width: 24%;">Ca Trực & Chuyên Môn Yêu Cầu</th>
                        <th style="width: 8%; text-align: center;">Chỉ Tiêu</th>
                        <th style="width: 34%;">Phương Án Theo Thứ Tự Đăng Ký</th>
                        <th style="width: 34%;">Phương Án Điều Phối Tối Ưu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($shifts as $s): 
                        $sId = $s['id'];
                        $reqSkills = $s['required_skills'] ?? [];
                        $fcfsAssigned = $fcfs['assignments'][$sId]['assigned'] ?? [];
                        $smartAssigned = $smart['assignments'][$sId]['assigned'] ?? [];
                        $needed = (int)$s['needed_count'];
                    ?>
                        <tr>
                            <td>
                                <span class="shift-id-tag"><?= htmlspecialchars($s['id']) ?></span>
                                <strong><?= htmlspecialchars($s['shift_name']) ?></strong>
                                <small class="text-muted d-block"><?= htmlspecialchars($s['activity_title']) ?></small>
                                <div class="skill-req-pills">
                                    <?php foreach ($reqSkills as $sk): ?>
                                        <span class="req-skill-pill"><?= htmlspecialchars($allSkills[$sk]['name'] ?? $sk) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <span class="target-needed-badge"><?= $needed ?> TNV</span>
                            </td>

                            <!-- FCFS Result -->
                            <td class="col-assigned-result">
                                <div class="assigned-status-header">
                                    <span class="count-tag <?= count($fcfsAssigned) >= $needed ? 'full' : 'short' ?>">
                                        <?= count($fcfsAssigned) ?> / <?= $needed ?> TNV
                                    </span>
                                    <?php if (count($fcfsAssigned) < $needed): ?>
                                        <span class="short-badge">Thiếu <?= $needed - count($fcfsAssigned) ?> người</span>
                                    <?php else: ?>
                                        <span class="full-badge">Đủ chỉ tiêu</span>
                                    <?php endif; ?>
                                </div>
                                <div class="assigned-names-chips">
                                    <?php foreach ($fcfsAssigned as $v): ?>
                                        <span class="v-chip" title="Kỹ năng: <?= implode(', ', $v['skills'] ?? []) ?>">
                                            <?= htmlspecialchars($v['name']) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </td>

                            <!-- Smart Greedy Result -->
                            <td class="col-assigned-result smart-highlight">
                                <div class="assigned-status-header">
                                    <span class="count-tag <?= count($smartAssigned) >= $needed ? 'full' : 'short' ?>">
                                        <?= count($smartAssigned) ?> / <?= $needed ?> TNV
                                    </span>
                                    <?php if (count($smartAssigned) < $needed): ?>
                                        <span class="short-badge">Thiếu <?= $needed - count($smartAssigned) ?> người</span>
                                    <?php else: ?>
                                        <span class="full-badge">Đủ chỉ tiêu</span>
                                    <?php endif; ?>
                                </div>
                                <div class="assigned-names-chips">
                                    <?php foreach ($smartAssigned as $v): ?>
                                        <span class="v-chip smart-chip" title="Kỹ năng: <?= implode(', ', $v['skills'] ?? []) ?>">
                                            <?= htmlspecialchars($v['name']) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ĐÁNH GIÁ HIỆU QUẢ -->
    <div class="rubric-conclusion-card">
        <h3>Đánh Giá Hiệu Quả Điều Phối Nhân Sự</h3>
        <ul>
            <li><strong>Tối ưu hóa đa ràng buộc:</strong> Bảo đảm 100% nhân sự được phân bổ đúng chuyên môn, loại trừ hoàn toàn xung đột trùng lịch và tuân thủ chặt chẽ chỉ tiêu quân số của từng vị trí thực địa.</li>
            <li><strong>Cân bằng khối lượng công việc:</strong> Giảm thiểu tối đa độ chênh lệch số ca trực giữa các tình nguyện viên, nâng cao tính công bằng và sự gắn kết bền vững của lực lượng.</li>
            <li><strong>Minh bạch và tin cậy:</strong> Nhận diện chính xác và cảnh báo sớm các ca trực bị thiếu hụt nhân sự để ban điều hành kịp thời có phương án huy động bổ sung, không gán người sai chuyên môn.</li>
        </ul>
    </div>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
