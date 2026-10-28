<?php
require __DIR__ . '/../src/Input.php';
require __DIR__ . '/../src/Auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$loi = [];
$thanh_cong = false;
$ho_ten = '';
$email = '';
$mssv = '';
$phone = '';
$unit = '';
$vi_tri = '';
$redirectUrl = field($_GET, 'redirect') ?: field($_POST, 'redirect') ?: '';

// Tự động chuyển hướng nếu tài khoản đã đăng nhập sẵn
if (Auth::isLoggedIn()) {
    $target = Auth::isCoordinator() ? '/phan_cong.php' : '/lich_truc.php';
    if (!empty($redirectUrl) && $redirectUrl !== '/' && !str_contains($redirectUrl, 'dang_nhap') && !str_contains($redirectUrl, 'dang_ky')) {
        $target = $redirectUrl;
    }
    header("Location: " . $target);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ho_ten = trim(field($_POST, 'ho_ten'));
    $mssv = trim(field($_POST, 'mssv'));
    $email = trim(field($_POST, 'email'));
    $phone = trim(field($_POST, 'phone'));
    $unit = trim(field($_POST, 'unit'));
    $vi_tri = field($_POST, 'vi_tri') ?: 'Tình Nguyện Viên Nòng Cốt';
    $mat_khau = field($_POST, 'mat_khau');
    $xac_nhan_mk = field($_POST, 'xac_nhan_mk');

    if ($ho_ten === '') { $loi[] = 'Họ và tên không được để trống.'; }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $loi[] = 'Email không hợp lệ.'; }
    if (strlen($mat_khau) < 6) { $loi[] = 'Mật khẩu phải từ 6 ký tự trở lên.'; }
    if ($mat_khau !== $xac_nhan_mk) { $loi[] = 'Xác nhận mật khẩu không trùng khớp.'; }

    if (empty($loi)) {
        // Tài khoản đăng ký mới luôn luôn là Tình Nguyện Viên Thực Địa
        $_SESSION['user'] = [
            'id' => 'USR-' . rand(500, 999),
            'name' => $ho_ten,
            'email' => $email,
            'role' => $vi_tri,
            'role_type' => Auth::ROLE_VOLUNTEER,
            'badge' => 'Mầm Xanh Khởi Đầu',
            'volunteer_hours' => 0,
            'events_joined' => 0,
            'avatar_letter' => mb_substr($ho_ten, 0, 1),
            'avatar_color' => '#059669',
            'unit' => $unit ?: 'Đội Tình Nguyện D2L',
            'joined_date' => date('d/m/Y'),
            'phone' => $phone
        ];

        $_SESSION['flash_message'] = 'Chào mừng ' . $ho_ten . ' đã đăng ký tài khoản thành công! Bạn có thể xem ca trực hoặc hoàn thiện hồ sơ.';

        $target = '/lich_truc.php';
        if (!empty($redirectUrl) && $redirectUrl !== '/' && !str_contains($redirectUrl, 'dang_nhap') && !str_contains($redirectUrl, 'dang_ky')) {
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
            <h2>Đăng Ký Tài Khoản Mới</h2>
            <p>Tạo tài khoản để đăng nhập, theo dõi lịch trực và tham gia các chiến dịch tình nguyện D2L</p>
        </div>

        <?php if (!empty($loi)): ?>
            <div class="error-summary-pro" style="margin-bottom: 20px;">
                <ul class="error-list" style="margin: 0; padding-left: 20px;">
                    <?php foreach ($loi as $item): ?>
                        <li><?= htmlspecialchars($item) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="/dang_ky_tk.php" class="form_xac_thuc_pro">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirectUrl) ?>">

            <div class="form-field-pro">
                <label for="ho_ten">Họ và tên tình nguyện viên <strong class="req">*</strong></label>
                <input type="text" id="ho_ten" name="ho_ten" placeholder="Ví dụ: Lê Minh Trí" value="<?= htmlspecialchars(field($_POST, 'ho_ten')) ?>" required autofocus>
            </div>

            <div class="form-row-2">
                <div class="form-field-pro">
                    <label for="mssv">Mã số sinh viên (MSSV)</label>
                    <input type="text" id="mssv" name="mssv" placeholder="Ví dụ: 21120000" value="<?= htmlspecialchars(field($_POST, 'mssv')) ?>">
                </div>

                <div class="form-field-pro">
                    <label for="email">Địa chỉ Email liên hệ <strong class="req">*</strong></label>
                    <input type="email" id="email" name="email" placeholder="email@sinhvien.edu.vn" value="<?= htmlspecialchars(field($_POST, 'email')) ?>" required>
                </div>
            </div>

            <div class="form-row-2">
                <div class="form-field-pro">
                    <label for="phone">Số điện thoại liên hệ</label>
                    <input type="tel" id="phone" name="phone" placeholder="Ví dụ: 0912345678" value="<?= htmlspecialchars(field($_POST, 'phone')) ?>">
                </div>

                <div class="form-field-pro">
                    <label for="unit">Trường / Đơn vị học tập</label>
                    <input type="text" id="unit" name="unit" placeholder="Ví dụ: ĐH Khoa Học Tự Nhiên" value="<?= htmlspecialchars(field($_POST, 'unit')) ?>">
                </div>
            </div>

            <div class="form-field-pro">
                <label for="vi_tri">Sở trường / Vị trí mong muốn cống hiến</label>
                <select id="vi_tri" name="vi_tri">
                    <option value="Tình Nguyện Viên Nòng Cốt">Tình Nguyện Viên Nòng Cốt (Đa năng)</option>
                    <option value="Phụ Trách Hậu Cần & Kỹ Thuật">Phụ Trách Hậu Cần & Kỹ Thuật</option>
                    <option value="Tình Nguyện Viên Y Tế & Sơ Cấp Cứu">Đội Y Tế & Chăm Sóc Sức Khỏe</option>
                    <option value="Tình Nguyện Viên Truyền Thông & Nhiếp Ảnh">Đội Truyền Thông & Nhiếp Ảnh</option>
                    <option value="Tình Nguyện Viên Giảng Dạy & Hoạt Náo">Đội Hoạt Náo & Giảng Dạy</option>
                </select>
            </div>

            <div class="form-row-2">
                <div class="form-field-pro">
                    <label for="mat_khau">Mật khẩu tài khoản <strong class="req">*</strong></label>
                    <input type="password" id="mat_khau" name="mat_khau" placeholder="Tối thiểu 6 ký tự" required>
                </div>

                <div class="form-field-pro">
                    <label for="xac_nhan_mk">Xác nhận lại mật khẩu <strong class="req">*</strong></label>
                    <input type="password" id="xac_nhan_mk" name="xac_nhan_mk" placeholder="Nhập lại mật khẩu" required>
                </div>
            </div>

            <button type="submit" class="btn-primary nut_toan_chieu_pro">Tạo Tài Khoản Ngay</button>

            <p class="chuyen_huong_tk">
                Đã có tài khoản? <a href="/dang_nhap.php?redirect=<?= urlencode($redirectUrl) ?>">Đăng nhập tại đây</a>
            </p>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
