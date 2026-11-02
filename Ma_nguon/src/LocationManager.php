<?php
declare(strict_types=1);

// Polyfill an toàn nếu môi trường máy chủ Linux/WSL thiếu ext-mbstring
if (!function_exists('mb_stripos')) {
    function mb_stripos(string $haystack, string $needle, int $offset = 0, ?string $encoding = null) {
        if (function_exists('mb_strtolower')) {
            $h = mb_strtolower($haystack, $encoding ?? 'UTF-8');
            $n = mb_strtolower($needle, $encoding ?? 'UTF-8');
            return function_exists('mb_strpos') ? mb_strpos($h, $n, $offset, $encoding) : strpos($h, $n, $offset);
        }
        return stripos($haystack, $needle, $offset);
    }
}

/**
 * MODULE QUẢN LÝ ĐỊA BÀN & ĐỊA ĐIỂM HOẠT ĐỘNG (D2L LOCATION MANAGER)
 * Đồng bộ phân loại vùng miền, tỉnh thành, quận huyện và định dạng hiển thị không bị tràn chữ
 */
class LocationManager
{
    public const REGIONS = [
        'hanoi' => [
            'key' => 'hanoi',
            'name' => 'Hà Nội (Thủ đô)',
            'short_name' => 'Hà Nội',
            'color' => '#0284c7',
            'bg' => '#f0f9ff'
        ],
        'north' => [
            'key' => 'north',
            'name' => 'Miền Bắc (Tây Bắc & Đông Bắc)',
            'short_name' => 'Miền Bắc',
            'color' => '#059669',
            'bg' => '#ecfdf5'
        ],
        'central' => [
            'key' => 'central',
            'name' => 'Miền Trung & Tây Nguyên',
            'short_name' => 'Miền Trung',
            'color' => '#d97706',
            'bg' => '#fffbeb'
        ],
        'south' => [
            'key' => 'south',
            'name' => 'Miền Nam (TP.HCM & Miền Tây)',
            'short_name' => 'Miền Nam',
            'color' => '#7c3aed',
            'bg' => '#f5f3ff'
        ]
    ];

    public const PROVINCES_MAP = [
        'hanoi' => [
            'Hà Nội' => ['Ba Đình', 'Hoàn Kiếm', 'Cầu Giấy', 'Đống Đa', 'Hai Bà Trưng', 'Hoàng Mai', 'Thanh Xuân', 'Hà Đông', 'Nam Từ Liêm', 'Bắc Từ Liêm', 'Tây Hồ', 'Long Biên', 'Gia Lâm', 'Đông Anh', 'Sóc Sơn', 'Ba Vì', 'Mỹ Đức', 'Chương Mỹ', 'Thạch Thất', 'Sơn Tây']
        ],
        'north' => [
            'Yên Bái' => ['Mù Cang Chải', 'Văn Chấn', 'Trấn Yên', 'Nghĩa Lộ', 'TP. Yên Bái'],
            'Hà Giang' => ['Đồng Văn', 'Mèo Vạc', 'Yên Minh', 'Quản Bạ', 'Hoàng Su Phì', 'TP. Hà Giang'],
            'Lào Cai' => ['Sa Pa', 'Bắc Hà', 'Bát Xát', 'Mường Khương', 'TP. Lào Cai'],
            'Hải Phòng' => ['Đồ Sơn', 'Hồng Bàng', 'Ngô Quyền', 'Lê Chân', 'Cát Hải', 'Thủy Nguyên'],
            'Quảng Ninh' => ['Hạ Long', 'Cẩm Phả', 'Uông Bí', 'Móng Cái', 'Vân Đồn'],
            'Sơn La' => ['Mộc Châu', 'Mai Sơn', 'Thuận Châu', 'TP. Sơn La'],
            'Bắc Ninh' => ['TP. Bắc Ninh', 'Từ Sơn', 'Thuận Thành', 'Tiên Du'],
            'Ninh Bình' => ['Hoa Lư', 'Gia Viễn', 'Nho Quan', 'TP. Ninh Bình'],
            'Thái Bình' => ['Tiền Hải', 'Thái Thụy', 'Vũ Thư', 'TP. Thái Bình'],
            'Hòa Bình' => ['Mai Châu', 'Lương Sơn', 'Kim Bôi', 'TP. Hòa Bình']
        ],
        'central' => [
            'Đà Nẵng' => ['Hải Châu', 'Thanh Khê', 'Sơn Trà', 'Ngũ Hành Sơn', 'Liên Chiểu', 'Hòa Vang'],
            'Thừa Thiên Huế' => ['TP. Huế', 'Hương Thủy', 'Phú Vang', 'A Lưới'],
            'Quảng Nam' => ['Hội An', 'Tam Kỳ', 'Núi Thành', 'Điện Bàn'],
            'Quảng Ngãi' => ['Lý Sơn', 'Bình Sơn', 'Ba Tơ', 'TP. Quảng Ngãi'],
            'Quảng Bình' => ['Đồng Hới', 'Bố Trạch', 'Lệ Thủy', 'Quảng Trạch'],
            'Hà Tĩnh' => ['Hương Sơn', 'Kỳ Anh', 'Nghi Xuân', 'TP. Hà Tĩnh'],
            'Nghệ An' => ['TP. Vinh', 'Cửa Lò', 'Nam Đàn', 'Con Cuông', 'Tương Dương'],
            'Lâm Đồng' => ['Đà Lạt', 'Bảo Lộc', 'Lạc Dương', 'Đơn Dương'],
            'Đắk Lắk' => ['Buôn Ma Thuột', 'Krông Pắc', 'Ea Kar', 'Buôn Đôn'],
            'Ninh Thuận' => ['Phan Rang - Tháp Chàm', 'Ninh Hải', 'Ninh Phước']
        ],
        'south' => [
            'TP. Hồ Chí Minh' => ['Quận 1', 'Quận 3', 'Quận 5', 'Quận 7', 'Quận 10', 'Bình Thạnh', 'Tân Bình', 'Thủ Đức', 'Bình Tân', 'Cần Giờ', 'Củ Chi', 'Hóc Môn', 'Bình Chánh', 'Nhà Bè'],
            'Cần Thơ' => ['Ninh Kiều', 'Cái Răng', 'Bình Thủy', 'Ô Môn', 'Phong Điền'],
            'Bến Tre' => ['Châu Thành', 'Ba Tri', 'Giồng Trôm', 'TP. Bến Tre'],
            'Đồng Nai' => ['Biên Hòa', 'Long Thành', 'Nhơn Trạch'],
            'Bình Dương' => ['Thủ Dầu Một', 'Dĩ An', 'Thuận An', 'Bến Cát'],
            'An Giang' => ['Long Xuyên', 'Châu Đốc', 'Tri Tôn', 'Tịnh Biên'],
            'Kiên Giang' => ['Phú Quốc', 'Rạch Giá', 'Hà Tiên']
        ]
    ];

    /**
     * Bóc tách chuỗi địa chỉ thành cấu trúc chuẩn: Miền, Tỉnh, Khu vực và chuỗi rút gọn
     */
    public static function parse(string $rawLocation): array
    {
        $raw = trim($rawLocation);
        $detectedRegion = 'hanoi';
        $detectedProvince = 'Hà Nội';
        $detectedDistrict = '';

        // 1. Dò tìm Tỉnh / Thành phố
        foreach (self::PROVINCES_MAP as $regKey => $provinces) {
            foreach ($provinces as $provName => $districts) {
                // Kiểm tra tên tỉnh hoặc tên viết tắt phổ biến
                $checkNames = [$provName];
                if ($provName === 'TP. Hồ Chí Minh') {
                    $checkNames = ['Hồ Chí Minh', 'TP.HCM', 'TPHCM', 'Sài Gòn'];
                }
                foreach ($checkNames as $cName) {
                    if (mb_stripos($raw, $cName) !== false) {
                        $detectedRegion = $regKey;
                        $detectedProvince = $provName;
                        // Dò tiếp quận huyện trong tỉnh này
                        foreach ($districts as $dist) {
                            if (mb_stripos($raw, $dist) !== false) {
                                $detectedDistrict = $dist;
                                break;
                            }
                        }
                        break 2;
                    }
                }
            }
        }

        // 2. Nếu chưa tìm thấy tỉnh, thử dò qua danh sách quận huyện đặc thù
        if (empty($detectedDistrict)) {
            // Danh sách các quận nội thành Hà Nội phổ biến
            $hnDistricts = ['Cầu Giấy', 'Ba Đình', 'Hoàn Kiếm', 'Đống Đa', 'Hai Bà Trưng', 'Tô Lịch', 'Bạch Mai', 'Trần Hưng Đạo', 'Tân Triều', 'Tràng Thi', 'Mỹ Đình', 'Sư Phạm'];
            foreach ($hnDistricts as $d) {
                if (mb_stripos($raw, $d) !== false) {
                    $detectedRegion = 'hanoi';
                    $detectedProvince = 'Hà Nội';
                    $detectedDistrict = ($d === 'Tô Lịch' || $d === 'Sư Phạm') ? 'Cầu Giấy' : (($d === 'Tràng Thi' || $d === 'Trần Hưng Đạo') ? 'Hoàn Kiếm' : ($d === 'Tân Triều' ? 'Thanh Trì' : $d));
                    break;
                }
            }
        }

        // 3. Tạo chuỗi hiển thị siêu ngắn gọn (Short Location) cho thẻ card
        $provDisplay = ($detectedProvince === 'TP. Hồ Chí Minh') ? 'TP.HCM' : $detectedProvince;
        if (!empty($detectedDistrict)) {
            $shortLoc = $detectedDistrict . ', ' . $provDisplay;
        } else {
            $shortLoc = $provDisplay;
        }

        $regionInfo = self::REGIONS[$detectedRegion] ?? self::REGIONS['hanoi'];

        return [
            'region_key' => $detectedRegion,
            'region_name' => $regionInfo['short_name'],
            'region_full_name' => $regionInfo['name'],
            'region_color' => $regionInfo['color'],
            'region_bg' => $regionInfo['bg'],
            'province' => $detectedProvince,
            'province_short' => $provDisplay,
            'district' => $detectedDistrict,
            'short_location' => $shortLoc,
            'full_location' => $raw
        ];
    }

    /**
     * Kiểm tra xem địa điểm có khớp với bộ lọc khu vực hay không
     */
    public static function matches(string $rawLocation, string $filterRegion): bool
    {
        if (empty($filterRegion) || $filterRegion === 'all') {
            return true;
        }
        $info = self::parse($rawLocation);
        if ($filterRegion === 'hanoi' && $info['region_key'] === 'hanoi') {
            return true;
        }
        if ($filterRegion === 'north' && ($info['region_key'] === 'north' || $info['region_key'] === 'hanoi')) {
            return true;
        }
        if ($filterRegion === 'central' && $info['region_key'] === 'central') {
            return true;
        }
        if ($filterRegion === 'south' && $info['region_key'] === 'south') {
            return true;
        }
        return false;
    }

    /**
     * Danh sách phẳng các tỉnh thành để làm dropdown
     */
    public static function getAllProvinces(): array
    {
        $res = [];
        foreach (self::PROVINCES_MAP as $regKey => $provinces) {
            foreach ($provinces as $prov => $districts) {
                $res[] = [
                    'region' => $regKey,
                    'province' => $prov,
                    'districts' => $districts
                ];
            }
        }
        return $res;
    }
}

