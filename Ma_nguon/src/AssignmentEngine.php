<?php
declare(strict_types=1);

/**
 * MODULE THUẬT TOÁN PHÂN CÔNG TÌNH NGUYỆN VIÊN (D2L ASSIGNMENT ENGINE)
 * BÀI TẬP LỚN — ĐỀ TÀI 06: HỆ THỐNG PHÂN CÔNG HOẠT ĐỘNG TÌNH NGUYỆN
 *
 * Đáp ứng trọn vẹn:
 * - Yêu cầu kỹ thuật 1: Thuật toán xét đồng thời Kỹ năng + Thời gian/Trùng ca + Sĩ số ca.
 * - Yêu cầu kỹ thuật 2: So sánh thuật toán đề xuất với phương án cơ sở FCFS trên cùng bộ dữ liệu.
 * - Các phép thử bắt buộc: Không trùng giờ, không thiếu kỹ năng, không vượt sĩ số, không sinh người ảo.
 */

class AssignmentEngine
{
    /**
     * THUẬT TOÁN 1: PHƯƠNG ÁN CƠ SỞ (FCFS - FIRST COME FIRST SERVED)
     * Gán người tuần tự theo thời gian đăng ký (đến trước được chọn trước).
     *
     * Hạn chế tự nhiên của FCFS:
     * - Người nộp đơn sớm chiếm các ca phổ thông dễ làm trước.
     * - Khi đến các ca chuyên môn cao (Y tế, Cứu nạn), các TNV có kỹ năng chuyên môn
     *   đã bị gán kín lịch ở ca khác hoặc không còn slot, dẫn đến tỷ lệ thiếu người ở ca khó tăng vọt.
     * - Phân bổ ca không đều: người nộp trước nhận tối đa ca, người nộp sau không có ca trực.
     *
     * @param array $volunteers Danh sách TNV
     * @param array $shifts Danh sách ca trực
     * @return array Kết quả phân công và thống kê
     */
    public static function assignFCFS(array $volunteers, array $shifts): array
    {
        $startTime = microtime(true);

        // Sắp xếp TNV theo thời gian đăng ký tăng dần (FCFS)
        $sortedVolunteers = $volunteers;
        usort($sortedVolunteers, function ($a, $b) {
            $tA = strtotime($a['registered_at'] ?? '2026-01-01 00:00:00');
            $tB = strtotime($b['registered_at'] ?? '2026-01-01 00:00:00');
            return $tA <=> $tB;
        });

        // Khởi tạo trạng thái ca trực
        $shiftAssignments = [];
        foreach ($shifts as $shift) {
            $shiftAssignments[$shift['id']] = [
                'shift_info' => $shift,
                'assigned' => [],
                'needed' => (int)$shift['needed_count'],
            ];
        }

        // Theo dõi số ca và khung giờ của từng TNV
        $volunteerShiftCount = [];
        $volunteerTimeSlots = []; // [v_id => [time_slot_1, time_slot_2]]
        foreach ($sortedVolunteers as $v) {
            $volunteerShiftCount[$v['id']] = 0;
            $volunteerTimeSlots[$v['id']] = [];
        }

        // FCFS: Lần lượt từng TNV đăng ký sớm được xét chọn ca họ rảnh
        foreach ($sortedVolunteers as $v) {
            $vId = $v['id'];
            $maxShifts = (int)($v['max_shifts'] ?? 2);
            $vSkills = $v['skills'] ?? [];
            $vAvailable = $v['available_shifts'] ?? [];

            foreach ($shifts as $shift) {
                $sId = $shift['id'];
                $sTimeSlot = $shift['time_slot'] ?? $shift['shift_name'];
                $sNeeded = (int)$shift['needed_count'];
                $reqSkills = $shift['required_skills'] ?? [];

                // Đã đủ ca tối đa của người này
                if ($volunteerShiftCount[$vId] >= $maxShifts) {
                    break;
                }

                // Ca đã đủ người
                if (count($shiftAssignments[$sId]['assigned']) >= $sNeeded) {
                    continue;
                }

                // TNV không rảnh vào khung ca này
                if (!empty($vAvailable) && !in_array($sId, $vAvailable, true) && !in_array($sTimeSlot, $vAvailable, true)) {
                    continue;
                }

                // Kiểm tra trùng giờ
                if (in_array($sTimeSlot, $volunteerTimeSlots[$vId], true)) {
                    continue;
                }

                // Trong FCFS thông thường: người đăng ký sớm vẫn có thể được gán nếu có đủ kỹ năng
                $hasRequiredSkill = true;
                foreach ($reqSkills as $reqSkill) {
                    if (!in_array($reqSkill, $vSkills, true)) {
                        $hasRequiredSkill = false;
                        break;
                    }
                }

                if (!$hasRequiredSkill) {
                    continue;
                }

                // Gán vào ca
                $shiftAssignments[$sId]['assigned'][] = [
                    'id' => $vId,
                    'name' => $v['name'],
                    'skills' => $vSkills,
                    'registered_at' => $v['registered_at'] ?? '',
                ];
                $volunteerShiftCount[$vId]++;
                $volunteerTimeSlots[$vId][] = $sTimeSlot;
            }
        }

        $execTimeMs = round((microtime(true) - $startTime) * 1000, 2);
        return self::formatResult('FCFS (Thứ tự đăng ký)', $shiftAssignments, $volunteers, $shifts, $execTimeMs);
    }

    /**
     * THUẬT TOÁN 2: PHƯƠNG ÁN ĐỀ XUẤT CỦA NHÓM (GREEDY VỚI RÀNG BUỘC ĐỒNG THỜI & ƯU TIÊN)
     *
     * Nguyên lý hoạt động:
     * 1. Độ khan hiếm kỹ năng (Most Constrained First):
     *    - Đánh giá độ khó của ca trực: Ca nào yêu cầu kỹ năng hiếm (ít TNV có) sẽ được ưu tiên xếp trước.
     * 2. Ràng buộc đồng thời tuyệt đối (Multi-Constraint Guarantee):
     *    - Kỹ năng bắt buộc: 100% người được gán phải có đủ kỹ năng yêu cầu của ca.
     *    - Không trùng giờ: 1 TNV không bao giờ bị xếp vào 2 ca cùng thời điểm.
     *    - Giới hạn quân số: Không vượt quá needed_count của ca.
     * 3. Phân bổ công bằng (Fair Load Balancing):
     *    - Trong số các ứng viên hợp lệ cho một ca, ưu tiên người hiện có số ca ít nhất (min-load).
     *    - Nếu bằng số ca: ưu tiên người có ít kỹ năng phụ hơn để "để dành" người đa năng cho ca khó hơn.
     *
     * @param array $volunteers Danh sách TNV
     * @param array $shifts Danh sách ca trực
     * @return array Kết quả phân công và thống kê
     */
    public static function assignSmartGreedy(array $volunteers, array $shifts): array
    {
        $startTime = microtime(true);

        // 1. Phân tích độ hiếm của từng kỹ năng trong tập TNV
        $skillCounts = [];
        foreach ($volunteers as $v) {
            foreach ($v['skills'] ?? [] as $sk) {
                $skillCounts[$sk] = ($skillCounts[$sk] ?? 0) + 1;
            }
        }

        // 2. Tính điểm độ khó của từng ca trực để sắp xếp thứ tự ưu tiên
        // Ca có kỹ năng càng hiếm, số TNV đáp ứng càng ít -> Điểm ưu tiên càng cao
        $scoredShifts = [];
        foreach ($shifts as $shift) {
            $reqSkills = $shift['required_skills'] ?? [];
            $difficultyScore = 0;
            if (empty($reqSkills)) {
                $difficultyScore = 1000; // Ca phổ thông, xếp sau
            } else {
                foreach ($reqSkills as $sk) {
                    $availableWithSkill = $skillCounts[$sk] ?? 0;
                    $difficultyScore += $availableWithSkill; // Điểm càng thấp càng hiếm
                }
            }
            $scoredShifts[] = [
                'shift' => $shift,
                'difficulty' => $difficultyScore,
            ];
        }

        // Sắp xếp ca: Ca khó / hiếm kỹ năng lên đầu
        usort($scoredShifts, fn($a, $b) => $a['difficulty'] <=> $b['difficulty']);

        // Khởi tạo cấu trúc lưu trữ
        $shiftAssignments = [];
        foreach ($shifts as $shift) {
            $shiftAssignments[$shift['id']] = [
                'shift_info' => $shift,
                'assigned' => [],
                'needed' => (int)$shift['needed_count'],
            ];
        }

        $volunteerShiftCount = [];
        $volunteerTimeSlots = [];
        foreach ($volunteers as $v) {
            $volunteerShiftCount[$v['id']] = 0;
            $volunteerTimeSlots[$v['id']] = [];
        }

        // 3. Tiến hành gán theo chiến lược Greedy có ưu tiên
        foreach ($scoredShifts as $item) {
            $shift = $item['shift'];
            $sId = $shift['id'];
            $sTimeSlot = $shift['time_slot'] ?? $shift['shift_name'];
            $sNeeded = (int)$shift['needed_count'];
            $reqSkills = $shift['required_skills'] ?? [];

            // Tìm toàn bộ ứng viên hợp lệ cho ca này
            $eligibleCandidates = [];
            foreach ($volunteers as $v) {
                $vId = $v['id'];
                $maxShifts = (int)($v['max_shifts'] ?? 2);
                $vSkills = $v['skills'] ?? [];
                $vAvailable = $v['available_shifts'] ?? [];

                // Đã đạt số ca tối đa
                if ($volunteerShiftCount[$vId] >= $maxShifts) {
                    continue;
                }

                // Không rảnh vào khung giờ này
                if (!empty($vAvailable) && !in_array($sId, $vAvailable, true) && !in_array($sTimeSlot, $vAvailable, true)) {
                    continue;
                }

                // Trùng giờ với ca khác đã nhận
                if (in_array($sTimeSlot, $volunteerTimeSlots[$vId], true)) {
                    continue;
                }

                // Kiểm tra đủ kỹ năng
                $hasAllSkills = true;
                foreach ($reqSkills as $reqSkill) {
                    if (!in_array($reqSkill, $vSkills, true)) {
                        $hasAllSkills = false;
                        break;
                    }
                }
                if (!$hasAllSkills) {
                    continue;
                }

                $eligibleCandidates[] = $v;
            }

            // Sắp xếp ứng viên hợp lệ theo:
            // Tiêu chí 1: Người có ít ca trực nhất hiện tại (cân bằng tải/tính công bằng)
            // Tiêu chí 2: Người có ít kỹ năng tổng thể hơn (để dành người đa năng cho ca khác)
            usort($eligibleCandidates, function ($a, $b) use ($volunteerShiftCount) {
                $countA = $volunteerShiftCount[$a['id']];
                $countB = $volunteerShiftCount[$b['id']];
                if ($countA !== $countB) {
                    return $countA <=> $countB;
                }
                $skillsCountA = count($a['skills'] ?? []);
                $skillsCountB = count($b['skills'] ?? []);
                return $skillsCountA <=> $skillsCountB;
            });

            // Gán các ứng viên tốt nhất vào ca cho đến khi đủ chỉ tiêu
            foreach ($eligibleCandidates as $cand) {
                if (count($shiftAssignments[$sId]['assigned']) >= $sNeeded) {
                    break;
                }
                $cId = $cand['id'];
                $shiftAssignments[$sId]['assigned'][] = [
                    'id' => $cId,
                    'name' => $cand['name'],
                    'skills' => $cand['skills'] ?? [],
                    'registered_at' => $cand['registered_at'] ?? '',
                ];
                $volunteerShiftCount[$cId]++;
                $volunteerTimeSlots[$cId][] = $sTimeSlot;
            }
        }

        $execTimeMs = round((microtime(true) - $startTime) * 1000, 2);
        return self::formatResult('Greedy Đa Ràng Buộc (Đề xuất D2L)', $shiftAssignments, $volunteers, $shifts, $execTimeMs);
    }

    /**
     * SO SÁNH HAI THUẬT TOÁN TRÊN CÙNG MỘT BỘ DỮ LIỆU
     * Đối chiếu toàn bộ chỉ số đánh giá của Rubric Kỹ thuật 2.
     *
     * @param array $volunteers
     * @param array $shifts
     * @return array
     */
    public static function compareAlgorithms(array $volunteers, array $shifts): array
    {
        $fcfs = self::assignFCFS($volunteers, $shifts);
        $smart = self::assignSmartGreedy($volunteers, $shifts);

        return [
            'dataset_summary' => [
                'total_volunteers' => count($volunteers),
                'total_shifts' => count($shifts),
                'total_slots_needed' => array_sum(array_column($shifts, 'needed_count')),
            ],
            'fcfs' => $fcfs,
            'smart' => $smart,
            'comparison' => [
                'fill_rate_diff' => round($smart['metrics']['fill_rate'] - $fcfs['metrics']['fill_rate'], 1),
                'fully_staffed_diff' => $smart['metrics']['fully_staffed_shifts'] - $fcfs['metrics']['fully_staffed_shifts'],
                'workload_gap_diff' => $smart['metrics']['workload_gap'] - $fcfs['metrics']['workload_gap'],
                'unassigned_v_diff' => $fcfs['metrics']['unassigned_volunteers_count'] - $smart['metrics']['unassigned_volunteers_count'],
                'winner' => $smart['metrics']['fill_rate'] >= $fcfs['metrics']['fill_rate'] ? 'smart' : 'fcfs',
            ]
        ];
    }

    /**
     * KIỂM TRA ĐIỀU KIỆN KHI THAY THẾ HOẶC SỬA PHÂN CÔNG (CHO KỊCH BẢN 2 & PHÉP THỬ 3)
     *
     * @param array $volunteer TNV cần gán
     * @param array $targetShift Ca trực đích
     * @param array $currentAssignedVolunteers Danh sách hiện có trong ca
     * @param array $volunteerOtherShifts Các ca khác mà TNV này đã nhận
     * @return array ['valid' => bool, 'reason' => string]
     */
    public static function validateReassignment(
        array $volunteer,
        array $targetShift,
        array $currentAssignedVolunteers,
        array $volunteerOtherShifts = []
    ): array {
        // Phép thử 3: Kiểm tra vượt quá số người quy định của ca
        if (count($currentAssignedVolunteers) >= (int)$targetShift['needed_count']) {
            return [
                'valid' => false,
                'error_code' => 'OVER_CAPACITY',
                'message' => 'Ca trực ' . $targetShift['shift_name'] . ' đã đạt đủ chỉ tiêu tối đa (' . $targetShift['needed_count'] . ' người). Không thể bổ sung thêm.',
            ];
        }

        // Phép thử 1: Kiểm tra trùng giờ
        $targetTime = $targetShift['time_slot'] ?? $targetShift['shift_name'];
        foreach ($volunteerOtherShifts as $otherShift) {
            $otherTime = $otherShift['time_slot'] ?? $otherShift['shift_name'];
            if ($targetTime === $otherTime) {
                return [
                    'valid' => false,
                    'error_code' => 'TIME_CONFLICT',
                    'message' => 'Tình nguyện viên ' . $volunteer['name'] . ' đã có ca trực khác trùng khung giờ (' . $targetTime . ').',
                ];
            }
        }

        // Phép thử 2: Kiểm tra thiếu kỹ năng
        $reqSkills = $targetShift['required_skills'] ?? [];
        $vSkills = $volunteer['skills'] ?? [];
        foreach ($reqSkills as $reqSkill) {
            if (!in_array($reqSkill, $vSkills, true)) {
                return [
                    'valid' => false,
                    'error_code' => 'MISSING_SKILL',
                    'message' => 'Ca trực yêu cầu kỹ năng [' . $reqSkill . '] nhưng TNV ' . $volunteer['name'] . ' chưa có kỹ năng này.',
                ];
            }
        }

        return [
            'valid' => true,
            'error_code' => null,
            'message' => 'Đủ điều kiện tiếp nhận ca trực.',
        ];
    }

    /**
     * Định dạng kết quả và tính toán các chỉ số thống kê đối soát
     */
    private static function formatResult(
        string $algoName,
        array $shiftAssignments,
        array $volunteers,
        array $shifts,
        float $execTimeMs
    ): array {
        $totalSlotsNeeded = 0;
        $totalSlotsAssigned = 0;
        $fullyStaffedShifts = 0;
        $underStaffedShifts = [];
        $vShiftCounts = [];

        // Khởi tạo bộ đếm ca của từng TNV
        foreach ($volunteers as $v) {
            $vShiftCounts[$v['id']] = 0;
        }

        $skillViolations = 0;
        $timeConflicts = 0;
        $capacityViolations = 0;

        foreach ($shiftAssignments as $sId => &$data) {
            $shift = $data['shift_info'];
            $assigned = $data['assigned'];
            $needed = $data['needed'];

            $totalSlotsNeeded += $needed;
            $count = count($assigned);
            $totalSlotsAssigned += $count;

            if ($count >= $needed) {
                $fullyStaffedShifts++;
            } else {
                $underStaffedShifts[] = [
                    'shift_id' => $sId,
                    'shift_name' => $shift['shift_name'],
                    'activity_title' => $shift['activity_title'] ?? '',
                    'needed' => $needed,
                    'assigned' => $count,
                    'deficit' => $needed - $count,
                    'required_skills' => $shift['required_skills'] ?? [],
                ];
            }

            // Kiểm tra vi phạm sĩ số
            if ($count > $needed) {
                $capacityViolations++;
            }

            // Cập nhật số ca và kiểm tra kỹ năng
            $reqSkills = $shift['required_skills'] ?? [];
            foreach ($assigned as $vAssigned) {
                $vId = $vAssigned['id'];
                $vShiftCounts[$vId] = ($vShiftCounts[$vId] ?? 0) + 1;

                foreach ($reqSkills as $reqSkill) {
                    if (!in_array($reqSkill, $vAssigned['skills'] ?? [], true)) {
                        $skillViolations++;
                    }
                }
            }
        }
        unset($data);

        // Kiểm tra vi phạm trùng ca
        $vTimeSlotsMap = [];
        foreach ($shiftAssignments as $data) {
            $timeSlot = $data['shift_info']['time_slot'] ?? $data['shift_info']['shift_name'];
            foreach ($data['assigned'] as $v) {
                $vId = $v['id'];
                if (isset($vTimeSlotsMap[$vId][$timeSlot])) {
                    $timeConflicts++;
                } else {
                    $vTimeSlotsMap[$vId][$timeSlot] = true;
                }
            }
        }

        // Tính độ chênh lệch số ca giữa các TNV (Workload Gap)
        $counts = array_values($vShiftCounts);
        $maxShiftsAssigned = !empty($counts) ? max($counts) : 0;
        $minShiftsAssigned = !empty($counts) ? min($counts) : 0;
        $workloadGap = $maxShiftsAssigned - $minShiftsAssigned;

        $unassignedVolunteers = [];
        foreach ($volunteers as $v) {
            if (($vShiftCounts[$v['id']] ?? 0) === 0) {
                $unassignedVolunteers[] = [
                    'id' => $v['id'],
                    'name' => $v['name'],
                    'skills' => $v['skills'] ?? [],
                ];
            }
        }

        $fillRate = $totalSlotsNeeded > 0 ? round(($totalSlotsAssigned / $totalSlotsNeeded) * 100, 1) : 0.0;

        return [
            'algorithm_name' => $algoName,
            'execution_time_ms' => $execTimeMs,
            'assignments' => $shiftAssignments,
            'metrics' => [
                'total_slots_needed' => $totalSlotsNeeded,
                'total_slots_assigned' => $totalSlotsAssigned,
                'fill_rate' => $fillRate,
                'total_shifts' => count($shifts),
                'fully_staffed_shifts' => $fullyStaffedShifts,
                'understaffed_shifts_count' => count($underStaffedShifts),
                'skill_violations' => $skillViolations,
                'time_conflicts' => $timeConflicts,
                'capacity_violations' => $capacityViolations,
                'workload_gap' => $workloadGap,
                'max_shifts' => $maxShiftsAssigned,
                'min_shifts' => $minShiftsAssigned,
                'unassigned_volunteers_count' => count($unassignedVolunteers),
            ],
            'understaffed_shifts' => $underStaffedShifts,
            'unassigned_volunteers' => $unassignedVolunteers,
            'volunteer_workloads' => $vShiftCounts,
        ];
    }
}
