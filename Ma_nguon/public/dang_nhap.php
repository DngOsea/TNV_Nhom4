<?php
require __DIR__ . '/../src/Input.php';
require __DIR__ . '/../src/Auth.php';
$volunteerData = require __DIR__ . '/../data/volunteer_data.php';
$demoUsers = $volunteerData['demo_users'];

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$thong_bao = '';
$loi = '';
$redirectUrl = field($_GET, 'redirect') ?: field($_POST, 'redirect') ?: '/';

// Tự động chuyển hướng nếu tài khoản đã đăng nhập sẵn
if (Auth::isLoggedIn() && field($_GET, 'action') !== 'logout') {
    $target = Auth::isCoordinator() ? '/phan_cong.php' : '/lich_truc.php';
    if ($redirectUrl !== '' && $redirectUrl !== '/' && !str_contains($redirectUrl, 'dang_nhap') && !str_contains($redirectUrl, 'dang_ky')) {
        $target = $redirectUrl;
    }
    header("Location: " . $target);
    exit;
}

// Đăng nhập nhanh tài khoản mẫu phục vụ kiểm thử phân quyền
$quickUser = field($_GET, 'quick');
if ($quickUser !== '' && isset($demoUsers[$quickUser])) {
    $_SESSION['user'] = $demoUsers[$quickUser];
    if (empty($_SESSION['user']['role_type'])) {
        $_SESSION['user']['role_type'] = ($quickUser === 'volunteer') ? Auth::ROLE_VOLUNTEER : Auth::ROLE_COORDINATOR;
    }
    $_SESSION['flash_message'] = 'Đăng nhập thành công với vai trò: ' . ($_SESSION['user']['role'] ?? 'Thành viên');
    $target = Auth::isCoordinator() ? '/phan_cong.php' : '/lich_truc.php';
    if ($redirectUrl !== '' && $redirectUrl !== '/' && !str_contains($redirectUrl, 'dang_nhap') && !str_contains($redirectUrl, 'dang_ky')) {
        $target = $redirectUrl;
    }
    header("Location: " . $target);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(field($_POST, 'email'));
    $mat_khau = field($_POST, 'mat_khau');

    if ($email === '' || $mat_khau === '') {
        $loi = 'Vui lòng nhập đầy đủ Email và Mật khẩu!';
    } else {
        // Kiểm tra xem có khớp với tài khoản demo nào không
        $matchedUser = null;
        foreach ($demoUsers as $u) {
            if (strtolower($u['email']) === strtolower($email)) {
                $matchedUser = $u;
                break;
            }
        }

        if (!$matchedUser) {
            $namePart = explode('@', $email)[0];
            $formattedName = ucwords(str_replace(['.', '_', '-'], ' ', $namePart));
            $matchedUser = [
                'id' => 'USR-' . rand(100, 999),
                'name' => $formattedName ?: 'Tình Nguyện Viên',
                'email' => $email,
                'role' => 'Tình Nguyện Viên Nòng Cốt',
                'role_type' => Auth::ROLE_VOLUNTEER,
                'badge' => 'Mầm Xanh Tích Cực',
                'volunteer_hours' => 16,
                'events_joined' => 3,
                'avatar_letter' => mb_substr($formattedName ?: 'T', 0, 1),
                'avatar_color' => '#059669',
                'unit' => 'Đội Tình Nguyện D2L',
                'joined_date' => date('d/m/Y'),
                'phone' => '0912.345.678'
            ];
        } else {
            if (empty($matchedUser['role_type'])) {
                $roleLower = Auth::lower($matchedUser['role'] ?? '');
                $matchedUser['role_type'] = (str_contains($roleLower, 'chỉ huy') || 
                                             str_contains($roleLower, 'trưởng ban') ||
                                             str_contains($roleLower, 'điều phối'))
                                             ? Auth::ROLE_COORDINATOR : Auth::ROLE_VOLUNTEER;
            }
        }

        $_SESSION['user'] = $matchedUser;
        $_SESSION['flash_message'] = 'Chào mừng ' . ($matchedUser['name'] ?? 'đồng chí') . ' đã đăng nhập thành công!';

        $target = ($matchedUser['role_type'] === Auth::ROLE_COORDINATOR) ? '/phan_cong.php' : '/lich_truc.php';
        if ($redirectUrl !== '' && $redirectUrl !== '/' && !str_contains($redirectUrl, 'dang_nhap') && !str_contains($redirectUrl, 'dang_ky')) {
            $target = $redirectUrl;
        }

        header("Location: " . $target);
        exit;
    }
}

require __DIR__ . '/../templates/header.php';
?>

<div class="khung_xac_thuc_pro">
    <div class="the_tai_khoan_pro">
        <div class="dau_the_tk_pro">
            <h2>Đăng Nhập</h2>
            <p>Dành cho Tình nguyện viên và Ban Chỉ Huy Đội Tình Nguyện D2L</p>
        </div>

        <?php if ($loi !== ''): ?>
            <div class="error-summary-pro" style="margin-bottom: 20px;">
                <p><?= htmlspecialchars($loi) ?></p>
            </div>
        <?php endif; ?>

        <form method="post" action="/dang_nhap.php" class="form_xac_thuc_pro">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirectUrl) ?>">

            <div class="form-field-pro">
                <label for="email">Địa chỉ Email <strong class="req">*</strong></label>
                <input type="email" id="email" name="email" placeholder="ví dụ: nam.tran@d2l.org" value="<?= htmlspecialchars(field($_POST, 'email')) ?>" required autofocus>
            </div>

            <div class="form-field-pro">
                <label for="mat_khau">Mật khẩu truy cập <strong class="req">*</strong></label>
                <input type="password" id="mat_khau" name="mat_khau" placeholder="Nhập mật khẩu của bạn" required>
            </div>

            <div class="tuy_chon_phu_pro">
                <label class="o_danh_dau">
                    <input type="checkbox" name="ghi_nho" checked> Ghi nhớ phiên đăng nhập
                </label>
                <a href="#" class="quen_mk" onclick="alert('Vui lòng liên hệ Trưởng Ban Điều Phối (hotline: 0912.456.789) để cấp lại mật khẩu!'); return false;">Quên mật khẩu?</a>
            </div>

            <button type="submit" class="btn-primary nut_toan_chieu_pro">Đăng Nhập Ngay</button>

            <p class="chuyen_huong_tk">
                Chưa có tài khoản? <a href="/dang_ky_tk.php?redirect=<?= urlencode($redirectUrl) ?>">Đăng ký tài khoản mới tại đây</a>
            </p>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
