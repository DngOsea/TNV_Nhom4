<?php
declare(strict_types=1);

if (!function_exists('field')) {
    function field(array $input, string $key, string $default = ''): string {
        $value = $input[$key] ?? $default;
        return is_string($value) ? trim($value) : '';
    }
}
if (!function_exists('e')) {
    function e(string $value): string {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
if (!function_exists('validateRegistration')) {
    function validateRegistration(array $input, array $events): array {
        $eventId = filter_var(field($input, 'event_id'), FILTER_VALIDATE_INT);
        $name = field($input, 'name');
        $email = strtolower(field($input, 'email')); // Quy ước riêng của mẫu lớp học.
        $errors = [];
        if ($eventId === false || !in_array($eventId, array_column($events, 'id'), true)) {
            $errors[] = 'EVENT_INVALID';
        }
        if ($name === '' || strlen($name) > 120 || str_contains($name, "\0")) {
            $errors[] = 'NAME_INVALID';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 200) {
            $errors[] = 'EMAIL_INVALID';
        }
        return ['errors'=>$errors, 'data'=>[
            'event_id'=>(int)$eventId, 'name'=>$name, 'email'=>$email
        ]];
    }
}

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
