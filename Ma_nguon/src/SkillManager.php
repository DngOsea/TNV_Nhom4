<?php
declare(strict_types=1);

/**
 * MODULE QUẢN LÝ KỸ NĂNG & PHÂN NHÓM KỸ NĂNG THÔNG MINH (D2L SKILL MANAGER)
 * HỖ TRỢ:
 * 1. Kỹ năng chuẩn có sẵn (Preset Skills)
 * 2. Kỹ năng đã chứng minh / Xác thực (Certified Skills)
 * 3. Kỹ năng do Tình nguyện viên tự nhập (Custom Skills)
 * 4. Thuật toán gom nhóm kỹ năng thông minh (Skill Grouping & Fuzzy Keyword Matching)
 */

// Polyfill an toàn nếu môi trường máy chủ thiếu ext-mbstring
if (!function_exists('mb_substr')) {
    function mb_substr(string $string, int $start, ?int $length = null, ?string $encoding = null): string {
        return ($length === null) ? substr($string, $start) : substr($string, $start, $length);
    }
}
if (!function_exists('mb_strlen')) {
    function mb_strlen(string $string, ?string $encoding = null): int {
        return strlen($string);
    }
}
if (!function_exists('mb_strpos')) {
    function mb_strpos(string $haystack, string $needle, int $offset = 0, ?string $encoding = null) {
        return strpos($haystack, $needle, $offset);
    }
}
if (!function_exists('mb_strtolower')) {
    function mb_strtolower(string $string, ?string $encoding = null): string {
        return strtolower($string);
    }
}

class SkillManager
{
    /**
     * Danh mục các nhóm kỹ năng cốt lõi của hệ thống
     */
    public static function getGroups(): array
    {
        return [
            'y_te' => [
                'id' => 'y_te',
                'name' => 'Sơ cấp cứu & Y tế',
                'badge' => 'Y Tế',
                'color' => '#dc2626',
                'bg_color' => '#fef2f2',
                'border_color' => '#fecaca',
                'keywords' => [
                    'y te', 'yte', 'so cuu', 'so cap cuu', 'bang bo', 'vet thuong', 'bac si',
                    'dieu duong', 'y si', 'thuoc', 'tiem', 'duoi nuoc', 'hoi suc', 'cpr',
                    'huyet ap', 'do huyet ap', 'hien mau', 'benh vien', 'tram y te', 'duoc',
                    'cap cuu', 'chua benh', 'y khoa'
                ]
            ],
            'dieu_phoi' => [
                'id' => 'dieu_phoi',
                'name' => 'Điều phối & Giao thông',
                'badge' => 'Điều Phối',
                'color' => '#2563eb',
                'bg_color' => '#eff6ff',
                'border_color' => '#bfdbfe',
                'keywords' => [
                    'dieu phoi', 'dieu huong', 'giao thong', 'phan luong', 'chot xe', 'dan duong',
                    'huong dan', 'lai xe', 'xe may', 'xe om', 'o to', 'ban tai', 'xe tai',
                    'bang lai', 'bang b2', 'bang c', 'duong xa', 'xe dap', 'doi xe', 'an toan'
                ]
            ],
            'hau_can' => [
                'id' => 'hau_can',
                'name' => 'Hậu cần, Bếp ăn & Thể lực',
                'badge' => 'Hậu Cần',
                'color' => '#d97706',
                'bg_color' => '#fffbeb',
                'border_color' => '#fde68a',
                'keywords' => [
                    'hau can', 'the luc', 'boc vac', 'khuan vac', 'bung be', 'boc do', 'bep an',
                    'nau an', 'so che', 'nau nuong', 'chia com', 'phat qua', 'don dep', 've sinh',
                    'boi loi', 'boi song', 'khuong vung', 'suc khoe', 'vat tu', 'kho hang',
                    'trong cay', 'cuoc dat', 'nhao dat', 'che bat', 'dung leu'
                ]
            ],
            'truyen_thong' => [
                'id' => 'truyen_thong',
                'name' => 'Truyền thông & Sáng tạo',
                'badge' => 'Truyền Thông',
                'color' => '#7c3aed',
                'bg_color' => '#f5f3ff',
                'border_color' => '#ddd6fe',
                'keywords' => [
                    'truyen thong', 'chup anh', 'quay phim', 'nhiep anh', 'camera', 'may co',
                    'dslr', 'video', 'clip', 'tiktok', 'reels', 'canva', 'photoshop', 'illustrator',
                    'premiere', 'viet bai', 'content', 'fanpage', 'viet lach', 'do hoa', 'banner',
                    'poster', 'thiet ke', 'phong van', 'tin tuc', 'bien tap'
                ]
            ],
            'su_pham' => [
                'id' => 'su_pham',
                'name' => 'Sư phạm, Dạy học & Ngoại ngữ',
                'badge' => 'Sư Phạm',
                'color' => '#059669',
                'bg_color' => '#ecfdf5',
                'border_color' => '#a7f3d0',
                'keywords' => [
                    'su pham', 'day hoc', 'day kem', 'gia su', 'giao vien', 'tre em', 'hoc sinh',
                    'toan', 'van', 'ngoai ngu', 'tieng anh', 'english', 'ielts', 'toeic',
                    'tieng phap', 'tieng trung', 'hsk', 'tieng nhat', 'tieng han', 'hoat nao',
                    'mc', 'quan tro', 'tro choi', 'dan chuong trinh', 'ky hieu', 'ngon ngu ky hieu',
                    'khiem thinh', 'hat', 'guitar', 'am nhac'
                ]
            ],
            'ky_thuat' => [
                'id' => 'ky_thuat',
                'name' => 'Kỹ thuật & Thiết bị',
                'badge' => 'Kỹ Thuật',
                'color' => '#0284c7',
                'bg_color' => '#f0f9ff',
                'border_color' => '#bae6fd',
                'keywords' => [
                    'ky thuat', 'dien', 'dien nuoc', 'sua dien', 'lap rap', 'am thanh', 'loa dai',
                    'anh sang', 'may tinh', 'it', 'cntt', 'lap trinh', 'phan mem', 'mang', 'wifi',
                    'co khi', 'han', 'moc', 'sua xe'
                ]
            ],
        ];
    }

    /**
     * Danh mục các kỹ năng cố định có sẵn phân theo nhóm
     */
    public static function getPresetSkillsByGroup(): array
    {
        return [
            'y_te' => [
                'Sơ cấp cứu & Băng bó vết thương ban đầu',
                'Đo huyết áp & Chăm sóc người bệnh cơ bản',
                'Kỹ thuật ép tim & Hô hấp nhân tạo (CPR)',
                'Hỗ trợ y tế & Chăm sóc người hiến máu'
            ],
            'dieu_phoi' => [
                'Phân luồng giao thông & Điều tiết tại các nút giao',
                'Lái xe máy an toàn & Thông thạo đường sá đô thị',
                'Lái xe ô tô / Xe bán tải vận chuyển vật tư',
                'Hướng dẫn chỉ đường & Tiếp đón đoàn thể'
            ],
            'hau_can' => [
                'Thể lực tốt & Khuân vác bốc dỡ hàng cứu trợ',
                'Nấu ăn & Sơ chế thực phẩm bếp lớn tập thể',
                'Dọn dẹp môi trường, khơi thông kênh mương',
                'Biết bơi lội & Cứu hộ vùng ngập lụt'
            ],
            'truyen_thong' => [
                'Nhiếp ảnh & Chụp ảnh sự kiện máy ảnh cơ',
                'Quay phim & Biên tập video ngắn (Reels, TikTok)',
                'Viết bài truyền thông, tin bài báo chí',
                'Thiết kế ấn phẩm đồ họa (Canva, Photoshop)'
            ],
            'su_pham' => [
                'Kỹ năng sư phạm & Dạy kèm văn hóa cho trẻ em',
                'Hoạt náo viên, MC & Quản trò sinh hoạt tập thể',
                'Giao tiếp tiếng Anh lưu loát với người nước ngoài',
                'Giao tiếp bằng ngôn ngữ ký hiệu cho người khiếm thính'
            ],
            'ky_thuat' => [
                'Sửa chữa điện dân dụng, lắp đặt âm thanh sự kiện',
                'Hỗ trợ kỹ thuật máy tính, mạng & Nhập liệu vi tính',
                'Cơ khí & Lắp dựng khung rạp, che chắn bạt dã ngoại'
            ],
        ];
    }

    /**
     * Chuyển chuỗi tiếng Việt có dấu thành không dấu và chuẩn hóa chữ thường
     */
    public static function normalizeString(string $str): string
    {
        $unicode = [
            'a' => 'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ|Á|À|Ả|Ã|Ạ|Ă|Ắ|Ặ|Ằ|Ẳ|Ẵ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
            'd' => 'đ|Đ',
            'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ|É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
            'i' => 'í|ì|ỉ|ĩ|ị|Í|Ì|Ỉ|Ĩ|Ị',
            'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ|Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
            'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự|Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
            'y' => 'ý|ỳ|ỷ|ỹ|ỵ|Ý|Ỳ|Ỷ|Ỹ|Ỵ',
        ];
        foreach ($unicode as $nonAccent => $accent) {
            $str = preg_replace("/($accent)/u", $nonAccent, $str);
        }
        $str = strtolower(trim((string)$str));
        $str = preg_replace('/[^a-z0-9\s]/', ' ', $str);
        return trim((string)preg_replace('/\s+/', ' ', (string)$str));
    }

    /**
     * Tự động suy luận nhóm kỹ năng của một kỹ năng bất kỳ (kể cả do TNV tự nhập)
     */
    public static function detectSkillGroup(string $skillName): string
    {
        $normalized = self::normalizeString($skillName);
        if ($normalized === '') {
            return 'hau_can';
        }

        $groups = self::getGroups();
        $bestGroup = 'hau_can';
        $maxScore = 0;

        foreach ($groups as $gId => $gMeta) {
            $score = 0;
            foreach ($gMeta['keywords'] as $kw) {
                if (str_contains($normalized, $kw)) {
                    // Trọng số theo độ dài từ khóa khớp
                    $score += strlen($kw);
                }
            }
            if ($score > $maxScore) {
                $maxScore = $score;
                $bestGroup = $gId;
            }
        }

        return $bestGroup;
    }

    /**
     * Lấy toàn bộ danh sách kỹ năng tổng hợp của 1 TNV (gồm kỹ năng chuẩn, kỹ năng tự nhập, chứng nhận)
     */
    public static function getVolunteerSkillsProfile(array $volunteer): array
    {
        $rawSkills = $volunteer['skills'] ?? [];
        $customSkills = $volunteer['custom_skills'] ?? [];
        $certifiedSkills = $volunteer['certified_skills'] ?? [];

        $profile = [];

        // 1. Kỹ năng chuẩn từ mã hệ thống
        $groups = self::getGroups();
        foreach ($rawSkills as $sk) {
            if (isset($groups[$sk])) {
                $profile[] = [
                    'name' => $groups[$sk]['name'],
                    'code' => $sk,
                    'group_id' => $sk,
                    'group_name' => $groups[$sk]['name'],
                    'is_custom' => false,
                    'is_certified' => in_array($sk, $certifiedSkills, true) || in_array($groups[$sk]['name'], $certifiedSkills, true),
                    'badge' => $groups[$sk]['badge'],
                    'color' => $groups[$sk]['color'],
                ];
            } else {
                $gId = self::detectSkillGroup($sk);
                $profile[] = [
                    'name' => $sk,
                    'code' => $sk,
                    'group_id' => $gId,
                    'group_name' => $groups[$gId]['name'] ?? 'Kỹ Năng Chung',
                    'is_custom' => false,
                    'is_certified' => in_array($sk, $certifiedSkills, true),
                    'badge' => $groups[$gId]['badge'] ?? 'Kỹ Năng',
                    'color' => $groups[$gId]['color'] ?? '#64748b',
                ];
            }
        }

        // 2. Kỹ năng tự nhập
        foreach ($customSkills as $cSk) {
            $cSk = trim((string)$cSk);
            if ($cSk === '') continue;

            // Kiểm tra trùng lặp
            $exists = false;
            foreach ($profile as $p) {
                if (strcasecmp($p['name'], $cSk) === 0) {
                    $exists = true;
                    break;
                }
            }
            if ($exists) continue;

            $gId = self::detectSkillGroup($cSk);
            $profile[] = [
                'name' => $cSk,
                'code' => 'custom_' . substr(md5($cSk), 0, 8),
                'group_id' => $gId,
                'group_name' => $groups[$gId]['name'] ?? 'Kỹ Năng Tự Nhập',
                'is_custom' => true,
                'is_certified' => in_array($cSk, $certifiedSkills, true),
                'badge' => 'Tự Nhập (' . ($groups[$gId]['badge'] ?? 'Khác') . ')',
                'color' => $groups[$gId]['color'] ?? '#64748b',
            ];
        }

        return $profile;
    }

    /**
     * Kiểm tra xem 1 Tình nguyện viên có phù hợp với bộ lọc kỹ năng / từ khóa hay không
     * Hỗ trợ tìm kiếm mờ (Fuzzy search) và nhóm kỹ năng
     */
    public static function matchesFilter(array $volunteer, string $targetGroup = '', string $keyword = '', bool $certifiedOnly = false): bool
    {
        $skillsProfile = self::getVolunteerSkillsProfile($volunteer);

        if ($certifiedOnly) {
            $hasCertified = false;
            foreach ($skillsProfile as $sk) {
                if (!empty($sk['is_certified'])) {
                    $hasCertified = true;
                    break;
                }
            }
            if (!$hasCertified) return false;
        }

        // Kiểm tra nhóm kỹ năng
        if ($targetGroup !== '' && $targetGroup !== 'all') {
            $groupMatched = false;
            foreach ($skillsProfile as $sk) {
                if ($sk['group_id'] === $targetGroup) {
                    $groupMatched = true;
                    break;
                }
            }
            if (!$groupMatched) return false;
        }

        // Kiểm tra từ khóa tìm kiếm (so sánh tên, kỹ năng, ghi chú)
        if ($keyword !== '') {
            $normKeyword = self::normalizeString($keyword);
            $normName = self::normalizeString($volunteer['name'] ?? '');
            $normUnit = self::normalizeString($volunteer['unit'] ?? '');
            
            $keywordFound = false;
            if (str_contains($normName, $normKeyword) || str_contains($normUnit, $normKeyword)) {
                $keywordFound = true;
            } else {
                foreach ($skillsProfile as $sk) {
                    $normSkill = self::normalizeString($sk['name']);
                    $normGroup = self::normalizeString($sk['group_name']);
                    if (str_contains($normSkill, $normKeyword) || str_contains($normGroup, $normKeyword)) {
                        $keywordFound = true;
                        break;
                    }
                }
            }
            if (!$keywordFound) return false;
        }

        return true;
    }
}
