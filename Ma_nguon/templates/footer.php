</main>

<!-- ========================================================
     MODAL CHI TIẾT HOẠT ĐỘNG (INTERACTIVE ACTIVITY DETAIL)
     ======================================================== -->
<div id="activity_detail_modal" class="app-modal" role="dialog" aria-modal="true" aria-labelledby="modal_act_title">
    <div class="modal-backdrop"></div>
    <div class="modal-window">
        <div class="modal-header">
            <div>
                <span id="modal_act_category" class="modal-category-badge">Chủ đề</span>
                <h3 id="modal_act_title" class="modal-title">Tiêu đề hoạt động tình nguyện</h3>
            </div>
            <button type="button" class="modal-close-btn" aria-label="Đóng cửa sổ">&times;</button>
        </div>

        <div class="modal-body">
            <div class="modal-meta-grid">
                <div class="meta-box">
                    <div>
                        <span class="meta-field-label">Thời gian diễn ra:</span>
                        <p id="modal_act_date">Chủ Nhật hàng tuần</p>
                    </div>
                </div>
                <div class="meta-box">
                    <div>
                        <span class="meta-field-label">Địa bàn hoạt động:</span>
                        <p id="modal_act_location">Kênh Tham Lương, Quận 12</p>
                    </div>
                </div>
                <div class="meta-box">
                    <div>
                        <span class="meta-field-label">Điểm tập kết, đón xe:</span>
                        <p id="modal_act_meeting">Nhà văn hóa thanh niên</p>
                    </div>
                </div>
                <div class="meta-box">
                    <div>
                        <span class="meta-field-label">Chỉ huy phụ trách:</span>
                        <p><span id="modal_act_leader">Phạm Minh Quân</span> — <a id="modal_act_leader_phone" href="tel:0908123456" class="phone-link">0908.123.456</a></p>
                    </div>
                </div>
            </div>

            <div class="modal-section">
                <h4>Mô Tả Nhiệm Vụ Cụ Thể</h4>
                <p id="modal_act_summary" class="modal-summary-text">Nội dung chi tiết về hoạt động.</p>
            </div>

            <div class="modal-section">
                <h4>Các Ca Làm Việc Cần Tuyển</h4>
                <div id="modal_act_shifts" class="modal-pills-row"></div>
            </div>

            <div class="modal-section">
                <h4>Kỹ Năng Ưu Tiên</h4>
                <div id="modal_act_skills" class="modal-pills-row"></div>
            </div>

            <div class="modal-section">
                <h4>Quyền Lợi Tình Nguyện Viên</h4>
                <ul id="modal_act_benefits" class="modal-benefits-list"></ul>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn-cancel btn-modal-close">Đóng lại</button>
            <a id="modal_act_register_btn" href="/register.php" class="btn-primary modal-cta-btn">
                <span>Đăng Ký Tham Gia Hoạt Động</span>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
            </a>
        </div>
    </div>
</div>

<!-- ========================================================
     SITE FOOTER CHUẨN MỰC
     ======================================================== -->
<footer class="site-footer-pro">
    <div class="footer-top-cta">
        <div class="footer-cta-container">
            <div class="cta-text">
                <h3>Tham Gia Lực Lượng Tình Nguyện Viên Phục Vụ Cộng Đồng</h3>
                <p>Mỗi sự đóng góp của bạn là một bước lan tỏa giá trị nhân văn và bảo vệ môi trường bền vững.</p>
            </div>
            <div class="cta-actions">
                <a href="/register.php" class="btn-cta-green">Đăng Ký Tham Gia</a>
                <a href="/lich_truc.php" class="btn-cta-outline">Xem Lịch Trực Ca</a>
            </div>
        </div>
    </div>

    <div class="footer-main-container">
        <div class="footer-grid">
            <!-- Cột 1: Thông tin tổ chức -->
            <div class="footer-col brand-col">
                <div class="footer-logo">
                    <strong>HỆ THỐNG ĐIỀU PHỐI TÌNH NGUYỆN D2L</strong>
                </div>
                <p class="footer-about">
                    Hệ thống quản lý, điều phối và phân công lực lượng tình nguyện viên nòng cốt. Kết nối các chiến dịch tình nguyện thiết thực với sinh viên và thanh niên trên toàn quốc.
                </p>
            </div>

            <!-- Cột 2: Năm nhóm chủ đề -->
            <div class="footer-col">
                <h4>5 Nhóm Chủ Đề Hoạt Động</h4>
                <ul class="footer-links-list">
                    <li><a href="/hoat_dong.php?cat=thuc_te_moi_truong">Thực địa và môi trường xanh</a></li>
                    <li><a href="/hoat_dong.php?cat=hien_mau_y_te">Hiến máu nhân đạo và hỗ trợ y tế</a></li>
                    <li><a href="/hoat_dong.php?cat=tiep_suc_giao_duc">Tiếp sức mùa thi và giáo dục cộng đồng</a></li>
                    <li><a href="/hoat_dong.php?cat=cuu_tro_an_sinh">Cứu trợ khẩn cấp và bếp ăn 0 đồng</a></li>
                    <li><a href="/hoat_dong.php?cat=cham_soc_yeu_thuong">Chăm sóc người cao tuổi và trẻ khuyết tật</a></li>
                </ul>
            </div>

            <!-- Cột 3: Quản lý & Tiện ích -->
            <div class="footer-col">
                <h4>Nghiệp Vụ & Điều Phối</h4>
                <ul class="footer-links-list">
                    <li><a href="/phan_cong.php">Bảng phân công ca trực hôm nay</a></li>
                    <li><a href="/lich_truc.php">Lịch trực & ca làm việc</a></li>
                    <li><a href="/register.php">Đăng ký tham gia và nhận thẻ nhiệm vụ</a></li>
                    <li><a href="/dang_nhap.php">Cổng đăng nhập hệ thống</a></li>
                    <li><a href="/dang_ky_tk.php">Đăng ký tài khoản thành viên mới</a></li>
                </ul>
            </div>

            <!-- Cột 4: Hotline & Trụ sở -->
            <div class="footer-col contact-col">
                <h4>Ban Chỉ Huy Điều Phối</h4>
                <p><strong>Trụ sở điều phối:</strong> Số 4 Phạm Ngọc Thạch, Phường Bến Nghé, Quận 1, TP. Hồ Chí Minh</p>
                <p><strong>Đường dây nóng hỗ trợ:</strong> <a href="tel:0912456789">0912.456.789</a></p>
                <p><strong>Hòm thư điện tử:</strong> volunteer@d2l.org</p>
                <p><strong>Thời gian trực ban:</strong> 07:00 – 21:30 hàng ngày</p>
            </div>
        </div>

        <div class="footer-bottom-bar">
            <p>© 2026 <strong>Hệ Thống Phân Công Hoạt Động Tình Nguyện D2L</strong>. Nền tảng điều phối thanh niên xung kích.</p>
            <div class="footer-bottom-badges">
                <span class="badge-tag">Chuẩn Đoàn - Hội Sinh Viên</span>
                <span class="badge-tag">Quy Đổi Giờ Tình Nguyện</span>
                <span class="badge-tag">Đảm Bảo An Toàn 100%</span>
            </div>
        </div>
    </div>
</footer>

</body>
</html>
