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

<div class="auth-split-wrapper">
    <div class="auth-split-card">
        <!-- CỘT TRÁI: GIỚI THIỆU QUYỀN LỢI THÀNH VIÊN ĐỘI TÌNH NGUYỆN -->
        <div class="auth-info-side">
            <div class="auth-info-header">
                <div class="auth-brand-badge">D2L VOLUNTEER</div>
                <h2>Gia Nhập Lực Lượng Tình Nguyện Viên</h2>
                <p>Cùng hàng ngàn bạn trẻ cống hiến nhiệt huyết vì cộng đồng, bảo vệ môi trường và sẻ chia yêu thương.</p>
            </div>

            <div class="auth-benefits-list">
                <div class="benefit-item">
                    <span class="benefit-check" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </span>
                    <div>
                        <strong>Chứng Nhận Giờ Tình Nguyện</strong>
                        <p>Được tích lũy giờ hoạt động xã hội và cấp Giấy chứng nhận chính thức để xét điểm rèn luyện.</p>
                    </div>
                </div>

                <div class="benefit-item">
                    <span class="benefit-check" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </span>
                    <div>
                        <strong>Tập Huấn Kỹ Năng Thực Địa</strong>
                        <p>Được trang bị kiến thức sơ cấp cứu y tế, điều phối giao thông và kỹ năng xử lý sự cố hiện trường.</p>
                    </div>
                </div>

                <div class="benefit-item">
                    <span class="benefit-check" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </span>
                    <div>
                        <strong>Thẻ Điện Tử & Bảo Hộ Đầy Đủ</strong>
                        <p>Nhận Thẻ Tình Nguyện Viên số hóa, áo đồng phục và đồ bảo hộ an toàn cho từng chiến dịch.</p>
                    </div>
                </div>
            </div>

            <div class="auth-quote-box">
                <p>"Không ai có thể làm được tất cả, nhưng mỗi người đều có thể làm được một điều gì đó ý nghĩa."</p>
                <span>Ban Chỉ Huy Đội Tình Nguyện D2L</span>
            </div>
        </div>

        <!-- CỘT PHẢI: FORM ĐĂNG KÝ TÀI KHOẢN -->
        <div class="auth-form-side">
            <div class="auth-form-header">
                <h2>Tạo Tài Khoản Mới</h2>
                <p>Điền thông tin cơ bản để tham gia và theo dõi lịch trực</p>
            </div>

            <?php if (!empty($loi)): ?>
                <div class="auth-error-alert" role="alert">
                    <ul class="auth-error-list">
                        <?php foreach ($loi as $item): ?>
                            <li><?= htmlspecialchars($item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="/dang_ky_tk.php" class="auth-form-body">
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirectUrl) ?>">

                <div class="auth-input-group">
                    <label for="ho_ten">Họ và tên tình nguyện viên <span class="text-req">*</span></label>
                    <input type="text" id="ho_ten" name="ho_ten" placeholder="Ví dụ: Lê Minh Trí" value="<?= htmlspecialchars(field($_POST, 'ho_ten')) ?>" required autofocus>
                </div>

                <div class="auth-grid-2">
                    <div class="auth-input-group">
                        <label for="email">Địa chỉ Email <span class="text-req">*</span></label>
                        <input type="email" id="email" name="email" placeholder="email@sinhvien.edu.vn" value="<?= htmlspecialchars(field($_POST, 'email')) ?>" required>
                    </div>

                    <div class="auth-input-group">
                        <label for="phone">Số điện thoại liên hệ</label>
                        <input type="tel" id="phone" name="phone" placeholder="Ví dụ: 0912345678" value="<?= htmlspecialchars(field($_POST, 'phone')) ?>">
                    </div>
                </div>

                <div class="auth-grid-2">
                    <div class="auth-input-group">
                        <label for="unit">Trường / Đơn vị học tập</label>
                        <input type="text" id="unit" name="unit" placeholder="Ví dụ: ĐH Bách Khoa Hà Nội" value="<?= htmlspecialchars(field($_POST, 'unit')) ?>">
                    </div>

                    <div class="auth-input-group">
                        <label for="mssv">Mã số sinh viên (MSSV)</label>
                        <input type="text" id="mssv" name="mssv" placeholder="Ví dụ: 21120000" value="<?= htmlspecialchars(field($_POST, 'mssv')) ?>">
                    </div>
                </div>

                <div class="auth-input-group">
                    <label for="vi_tri">Đội hình sở trường</label>
                    <select id="vi_tri" name="vi_tri">
                        <option value="Tình Nguyện Viên Nòng Cốt">Tình Nguyện Viên Nòng Cốt (Đa năng)</option>
                        <option value="Phụ Trách Hậu Cần & Kỹ Thuật">Đội Hậu Cần & Kỹ Thuật</option>
                        <option value="Tình Nguyện Viên Y Tế & Sơ Cấp Cứu">Đội Y Tế & Sơ Cấp Cứu</option>
                        <option value="Tình Nguyện Viên Truyền Thông & Nhiếp Ảnh">Đội Truyền Thông & Nhiếp Ảnh</option>
                        <option value="Tình Nguyện Viên Giảng Dạy & Hoạt Náo">Đội Hoạt Náo & Giảng Dạy</option>
                    </select>
                </div>

                <div class="auth-grid-2">
                    <div class="auth-input-group">
                        <label for="mat_khau">Mật khẩu <span class="text-req">*</span></label>
                        <input type="password" id="mat_khau" name="mat_khau" placeholder="Tối thiểu 6 ký tự" required>
                    </div>

                    <div class="auth-input-group">
                        <label for="xac_nhan_mk">Xác nhận mật khẩu <span class="text-req">*</span></label>
                        <input type="password" id="xac_nhan_mk" name="xac_nhan_mk" placeholder="Nhập lại mật khẩu" required>
                    </div>
                </div>

                <button type="submit" class="btn-auth-submit">
                    Hoàn Tất Đăng Ký Tài Khoản
                </button>

                <div class="auth-footer-link">
                    Đã có tài khoản? <a href="/dang_nhap.php?redirect=<?= urlencode($redirectUrl) ?>">Đăng nhập ngay</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
