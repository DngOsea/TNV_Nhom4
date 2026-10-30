<?php
require __DIR__ . '/../src/Input.php';
$volunteerData = require __DIR__ . '/../data/volunteer_data.php';
$categories = $volunteerData['categories'];
$allActivities = $volunteerData['activities'];

require __DIR__ . '/../templates/header.php';
?>

<!-- ========================================================
     1. BANNER HERO TỔNG QUAN
     ======================================================== -->
<section class="banner_hero_slider">
    <!-- SLIDE 1: TỔNG QUAN HỆ THỐNG -->
    <div class="slide_banner slide_logo_toan_man active" style="background-image: url('/assets/images/banner_logo_d2l.jpg');">
        <a href="/hoat_dong.php" class="lop_link_toan_slide" aria-label="Khám phá các chiến dịch tình nguyện của D2L Team" tabindex="-1"></a>
        <div class="khung_nut_banner_logo">
            <a href="/hoat_dong.php" class="btn_kham_pha_logo" title="Khám phá các chiến dịch tình nguyện của D2L Team">
                <span class="btn_kham_pha_icon_explore" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"></polygon>
                    </svg>
                </span>
                <span class="btn_kham_pha_text">Khám phá các chiến dịch tình nguyện</span>
                <span class="btn_kham_pha_arrow" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </span>
            </a>
        </div>
    </div>

    <!-- SLIDE 2: MÔI TRƯỜNG VÀ THỰC ĐỊA -->
    <div class="slide_banner" style="background-image: url('/assets/images/banner_tnv_1.jpg');">
        <div class="lop_phu_slide">
            <div class="noi_dung_slide">
                <span class="huy_hieu_slide">CHIẾN DỊCH TRỌNG ĐIỂM</span>
                <h1 class="tieu_de_slide">Chủ Nhật Xanh & Trồng Rừng Cần Giờ</h1>
                <p class="mo_ta_slide">
                    Huy động thanh niên tình nguyện ra quân dọn sạch kênh rạch ô nhiễm và gieo mầm 10.000 cây đước chắn sóng bảo vệ môi trường.
                </p>
                <div class="cum_nut_slide">
                    <a href="/hoat_dong.php" class="btn-primary nut_cta_lon">Xem Danh Sách Hoạt Động</a>
                    <a href="/dang_ky_ca.php" class="btn-detail nut_cta_trang">Đăng Ký Ca Làm Việc</a>
                </div>
            </div>
        </div>
    </div>

    <!-- SLIDE 3: HIẾN MÁU VÀ CỨU TRỢ -->
    <div class="slide_banner" style="background-image: url('/assets/images/banner_tnv_2.jpg');">
        <div class="lop_phu_slide">
            <div class="noi_dung_slide">
                <span class="huy_hieu_slide huy_hieu_crimson">HOẠT ĐỘNG AN SINH</span>
                <h1 class="tieu_de_slide">Giọt Hồng Nghĩa Tình & Bếp Ăn 0 Đồng</h1>
                <p class="mo_ta_slide">
                    Tiếp nhận hàng ngàn đơn vị máu cứu người và trao gửi 1.500 suất cơm nóng nghĩa tình mỗi tuần đến những hoàn cảnh khó khăn.
                </p>
                <div class="cum_nut_slide">
                    <a href="/lich_truc.php" class="btn-primary nut_cta_lon">Xem Lịch Trực Thực Địa</a>
                    <a href="/dang_ky_ca.php" class="btn-detail nut_cta_trang">Đăng Ký Ca Đa Ngày</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Nút điều khiển trái / phải -->
    <button class="nut_chuyen_trai" aria-label="Slide trước">&#10094;</button>
    <button class="nut_chuyen_phai" aria-label="Slide tiếp theo">&#10095;</button>

    <!-- Chấm tròn điều khiển -->
    <div class="cac_cham_dieu_khien">
        <span class="cham_chuyen_slide active"></span>
        <span class="cham_chuyen_slide"></span>
        <span class="cham_chuyen_slide"></span>
    </div>
</section>

<!-- ========================================================
     2. DẢI THỐNG KÊ TỔNG QUAN
     ======================================================== -->
<section class="bang_thong_ke_nhanh">
    <div class="luoi_thong_ke">
        <div class="the_so_lieu">
            <strong>05</strong>
            <span>Nhóm Chủ Đề Hoạt Động</span>
        </div>
        <div class="the_so_lieu">
            <strong>08</strong>
            <span>Chiến Dịch Trọng Điểm</span>
        </div>
        <div class="the_so_lieu">
            <strong>1.250+</strong>
            <span>Lượt TNV Tham Gia</span>
        </div>
        <div class="the_so_lieu">
            <strong>8.600+</strong>
            <span>Giờ Tình Nguyện Cống Hiến</span>
        </div>
        <div class="the_so_lieu">
            <strong>15.000+</strong>
            <span>Người Dân Thụ Hưởng</span>
        </div>
    </div>
</section>

<!-- ========================================================
     3. TỔNG QUAN 5 NHÓM CHỦ ĐỀ HOẠT ĐỘNG TÌNH NGUYỆN
     ======================================================== -->
<section class="section-container" id="nhom_chu_de">
    <div class="section-header-centered">
        <span class="section-tag-pill">TỔNG QUAN HOẠT ĐỘNG</span>
        <h2 class="section-main-title">5 Nhóm Chủ Đề Hoạt Động Trọng Điểm</h2>
        <p class="section-sub-title">Các chương trình tình nguyện được tổ chức khoa học, thiết thực và gắn liền với đời sống xã hội.</p>
    </div>

    <div class="category-cards-grid">
        <?php foreach ($categories as $catKey => $cat): ?>
            <?php 
                $catCount = count(array_filter($allActivities, fn($a) => $a['category'] === $catKey));
            ?>
            <div class="category-card" style="--cat-accent: <?= $cat['color'] ?>; --cat-bg: <?= $cat['bg_color'] ?>; --cat-border: <?= $cat['border_color'] ?>;">
                <div class="category-card-top">
                    <span class="cat-code-badge">[<?= htmlspecialchars($cat['code'] ?? 'TNV') ?>]</span>
                    <span class="cat-count-badge"><?= $catCount ?> hoạt động</span>
                </div>
                <h3 class="cat-title"><?= htmlspecialchars($cat['name']) ?></h3>
                <p class="cat-slogan">"<?= htmlspecialchars($cat['slogan']) ?>"</p>
                <p class="cat-desc"><?= htmlspecialchars($cat['description']) ?></p>
                <a href="/hoat_dong.php?cat=<?= urlencode($catKey) ?>" class="cat-action-link">
                    Xem danh sách chiến dịch &rarr;
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <div style="text-align: center; margin-top: 30px;">
        <a href="/hoat_dong.php" class="btn-primary" style="padding: 11px 26px; font-weight: 700; border-radius: 6px; display: inline-block;">
            Xem Toàn Bộ <?= count($allActivities) ?> Chiến Dịch Tình Nguyện &rarr;
        </a>
    </div>
</section>

<!-- ========================================================
     4. BA TRỤ CỘT ĐIỀU PHỐI CHUYÊN NGHIỆP
     ======================================================== -->
<section class="section-container bg-subtle-section">
    <div class="section-header-centered">
        <span class="section-tag-pill">ĐIỀU PHỐI VÀ QUẢN LÝ</span>
        <h2 class="section-main-title">Hệ Thống Quản Lý Hoạt Động Tình Nguyện</h2>
        <p class="section-sub-title">Hỗ trợ kết nối, phân công ca trực và xác nhận kết quả công tác xã hội một cách minh bạch.</p>
    </div>

    <div class="pillars-three-grid">
        <!-- Cột 1: Phân công ca trực -->
        <div class="pillar-card">
            <span class="pillar-number">01</span>
            <h3>Phân Công Ca Trực</h3>
            <p>Theo dõi lịch trực theo ca (Sáng, Chiều, Tối), phân bổ nhân lực và quản lý chỉ tiêu quân số từng địa bàn theo thời gian thực.</p>
            <a href="/phan_cong.php" class="pillar-link">Truy cập bảng phân công &rarr;</a>
        </div>

        <!-- Cột 2: Đăng ký trực tuyến & Thẻ điện tử -->
        <div class="pillar-card">
            <span class="pillar-number">02</span>
            <h3>Hồ Sơ & Thẻ Điện Tử</h3>
            <p>Quản lý năng lực chuyên môn, bổ sung kỹ năng tự do và cấp Thẻ Tình Nguyện Viên điện tử kèm mã QR phục vụ điểm danh trực tiếp.</p>
            <a href="/profile.php" class="pillar-link">Cập nhật hồ sơ & kỹ năng &rarr;</a>
        </div>

        <!-- Cột 3: Lịch Trực & Báo Vắng Trực Tuyến -->
        <div class="pillar-card">
            <span class="pillar-number">03</span>
            <h3>Lịch Trực & Báo Vắng</h3>
            <p>Tra cứu ca trực cá nhân, kiểm tra đồng đội cùng ca và chủ động gửi thông báo báo vắng trực tuyến để ban chỉ huy kịp thời điều động.</p>
            <a href="/lich_truc.php" class="pillar-link">Xem lịch trực ca ngay &rarr;</a>
        </div>
    </div>
</section>

<!-- ========================================================
     5. QUY TRÌNH THAM GIA ĐƠN GIẢN CHO TÌNH NGUYỆN VIÊN
     ======================================================== -->
<section class="section-container">
    <div class="section-header-centered">
        <span class="section-tag-pill">HƯỚNG DẪN THAM GIA</span>
        <h2 class="section-main-title">3 Bước Tham Gia Đội Hình Tình Nguyện</h2>
        <p class="section-sub-title">Quy trình đơn giản, rõ ràng giúp bạn nhanh chóng hòa mình vào các hoạt động vì cộng đồng.</p>
    </div>

    <div class="steps-flow-grid">
        <div class="step-flow-item">
            <div class="step-flow-num">Bước 1</div>
            <h4>Đăng Ký Tài Khoản</h4>
            <p>Tạo tài khoản tình nguyện viên hoặc đăng nhập vào hệ thống để bắt đầu quản lý lịch trình cá nhân.</p>
        </div>
        <div class="step-flow-item">
            <div class="step-flow-num">Bước 2</div>
            <h4>Chọn Chiến Dịch & Ca Trực</h4>
            <p>Khám phá các hoạt động theo chủ đề, chọn ca làm việc (Sáng/Chiều/Tối) phù hợp với thời gian biểu của bạn.</p>
        </div>
        <div class="step-flow-item">
            <div class="step-flow-num">Bước 3</div>
            <h4>Nhận Thẻ & Tham Gia</h4>
            <p>Nhận Thẻ Điều Động điện tử có mã QR, có mặt tại điểm tập kết đúng giờ và nhận trang bị bảo hộ từ Ban Chỉ Huy.</p>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../templates/footer.php'; ?>
