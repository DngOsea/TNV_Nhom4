<?php
declare(strict_types=1);

/**
 * MODULE PHÂN QUYỀN NGƯỜI DÙNG (AUTHENTICATION & AUTHORIZATION)
 * HỆ THỐNG QUẢN LÝ & PHÂN CÔNG HOẠT ĐỘNG TÌNH NGUYỆN (D2L VOLUNTEER)
 *
 * Phân chia 2 vai trò cốt lõi:
 * 1. coordinator (Người Điều Phối / Ban Chỉ Huy):
 *    - Toàn quyền điều hành: Tự động phân ca, thay thế nhân sự, phê duyệt và đối soát.
 * 2. volunteer (Tình Nguyện Viên):
 *    - Xem ca trực cá nhân, đăng ký tham gia hoạt động, báo vắng ca của bản thân.
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

class Auth
{
    public const ROLE_COORDINATOR = 'coordinator';
    public const ROLE_VOLUNTEER = 'volunteer';

    /**
     * Chuyển chuỗi về chữ thường an toàn (tương thích cả khi môi trường thiếu ext-mbstring)
     */
    public static function lower(string $text): string
    {
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($text, 'UTF-8');
        }
        return strtolower($text);
    }

    /**
     * Lấy thông tin tài khoản đang đăng nhập từ Session
     */
    public static function getUser(): ?array
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $user = $_SESSION['user'] ?? null;
        return is_array($user) ? $user : null;
    }

    /**
     * Kiểm tra người dùng đã đăng nhập hay chưa
     */
    public static function isLoggedIn(): bool
    {
        return self::getUser() !== null;
    }

    /**
     * Lấy loại vai trò của người dùng hiện tại (mặc định volunteer nếu không xác định)
     */
    public static function getRoleType(): string
    {
        $user = self::getUser();
        if (!$user) {
            return 'guest';
        }

        // Kiểm tra trường role_type trực tiếp
        if (!empty($user['role_type'])) {
            return $user['role_type'];
        }

        // Nhận diện qua chức danh (role title)
        $roleTitle = self::lower($user['role'] ?? '');
        if (str_contains($roleTitle, 'chỉ huy') || 
            str_contains($roleTitle, 'trưởng ban') || 
            str_contains($roleTitle, 'điều phối') ||
            str_contains($roleTitle, 'chi huy') ||
            str_contains($roleTitle, 'truong ban') ||
            str_contains($roleTitle, 'dieu phoi')) {
            return self::ROLE_COORDINATOR;
        }

        return self::ROLE_VOLUNTEER;
    }

    /**
     * Kiểm tra người dùng có quyền Người Điều Phối (Chỉ Huy) không
     */
    public static function isCoordinator(): bool
    {
        return self::getRoleType() === self::ROLE_COORDINATOR;
    }

    /**
     * Kiểm tra người dùng là Tình Nguyện Viên thông thường không
     */
    public static function isVolunteer(): bool
    {
        return self::getRoleType() === self::ROLE_VOLUNTEER;
    }

    /**
     * Bắt buộc phải đăng nhập mới được tiếp tục
     */
    public static function requireLogin(string $redirectUrl = '/dang_nhap.php'): array
    {
        $user = self::getUser();
        if (!$user) {
            header('Location: ' . $redirectUrl);
            exit;
        }
        return $user;
    }

    /**
     * Bắt buộc phải có quyền Điều Phối Viên mới được thực hiện hành động
     */
    public static function requireCoordinator(string $fallbackUrl = '/phan_cong.php'): array
    {
        $user = self::requireLogin();
        if (!self::isCoordinator()) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['flash_msg'] = [
                'type' => 'danger',
                'title' => 'Quyền Truy Cập Bị Từ Chối',
                'content' => 'Thao tác này yêu cầu quyền Người Điều Phối (Ban Chỉ Huy). Tài khoản Tình Nguyện Viên không thể thực hiện.',
            ];
            header('Location: ' . $fallbackUrl);
            exit;
        }
        return $user;
    }

    /**
     * Tên hiển thị trang trọng của nhóm vai trò
     */
    public static function getRoleDisplayBadge(): string
    {
        if (self::isCoordinator()) {
            return 'Người Điều Phối (Ban Chỉ Huy)';
        }
        if (self::isVolunteer()) {
            return 'Tình Nguyện Viên';
        }
        return 'Khách Khảo Sát';
    }
}
