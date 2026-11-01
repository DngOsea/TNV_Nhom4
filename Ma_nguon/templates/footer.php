</main>

<!-- ========================================================
     MODAL CHI TIẾT HOẠT ĐỘNG (INTERACTIVE ACTIVITY DETAIL)
     ======================================================== -->
<div id="activity_detail_modal" class="app-modal" role="dialog" aria-modal="true" aria-labelledby="modal_act_title">
    <div class="modal-backdrop"></div>
    <div class="modal-window">
        <div class="modal-header">
            <div>
                <div class="modal-header-badges" style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; flex-wrap: wrap;">
                    <span id="modal_act_category" class="modal-category-badge" style="margin-bottom: 0;">Chủ đề</span>
                    <span id="modal_act_quota_status" class="quota-pill-status quota-status-open" style="font-size: 0.74rem; padding: 3px 10px; margin-bottom: 0;">Đang mở đăng ký</span>
                </div>
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

        <div class="modal-footer modal-footer-enhanced" style="justify-content: flex-end;">
            <div class="modal-footer-actions">
                <button type="button" class="btn-cancel btn-modal-close">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                    Đóng lại
                </button>
                <button type="button" id="modal_act_register_btn" class="btn-primary modal-cta-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    Đăng Ký Tham Gia Hoạt Động
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================
     MODAL ĐĂNG KÝ HOẠT ĐỘNG TRỰC TUYẾN (POPUP TIỆN LỢI)
     Thay thế hoàn toàn việc phải chuyển hướng sang trang riêng
     ======================================================== -->
<div id="activity_register_modal" class="app-modal" role="dialog" aria-modal="true" aria-labelledby="reg_modal_title">
    <div class="modal-backdrop"></div>
    <div class="modal-window modal-window-reg">
        <form method="POST" action="/hoat_dong.php" id="activity_register_form">
            <input type="hidden" name="action" value="register_shifts">
            <input type="hidden" name="activity_id" id="reg_modal_act_id" value="">

            <div class="modal-header">
                <div>
                    <span id="reg_modal_cat_badge" class="modal-category-badge">ĐĂNG KÝ HOẠT ĐỘNG</span>
                    <h3 id="reg_modal_title" class="modal-title">Đăng Ký Tham Gia Hoạt Động</h3>
                    <p id="reg_modal_act_name" class="reg-modal-activity-name">Chiến dịch tình nguyện</p>
                </div>
                <button type="button" class="modal-close-btn" aria-label="Đóng cửa sổ">&times;</button>
            </div>

            <div class="modal-body">
                <!-- Tóm tắt địa bàn và thời gian -->
                <div class="reg-meta-compact-grid">
                    <div class="reg-meta-box">
                        <span class="reg-meta-label">Thời gian tổng thể:</span>
                        <strong id="reg_modal_act_date">—</strong>
                    </div>
                    <div class="reg-meta-box">
                        <span class="reg-meta-label">Địa bàn thực địa:</span>
                        <strong id="reg_modal_act_location">—</strong>
                    </div>
                </div>

                <!-- Thông tin tình nguyện viên đăng ký -->
                <?php 
                $footerUser = $_SESSION['user'] ?? null; 
                if ($footerUser): 
                ?>
                    <div class="reg-user-verified-badge">
                        <div class="v-icon-wrap">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                        <div class="v-user-text">
                            <div>Đăng ký với tài khoản: <strong><?= htmlspecialchars($footerUser['name'] ?? 'TNV') ?></strong> (<?= htmlspecialchars($footerUser['email'] ?? '') ?>)</div>
                            <small>Giờ tình nguyện và kết quả sẽ tự động ghi nhận vào mục <strong>Hoạt Động Của Tôi</strong>.</small>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="reg-guest-info-block">
                        <div class="guest-info-head">
                            <strong>Thông tin tình nguyện viên</strong>
                            <span class="guest-pill">Đăng ký nhanh không cần tạo tài khoản</span>
                        </div>
                        <div class="guest-inputs-flex">
                            <div class="guest-input-col">
                                <label for="reg_guest_name">Họ và tên <span class="req">*</span></label>
                                <input type="text" id="reg_guest_name" name="guest_name" placeholder="Ví dụ: Nguyễn Văn An" required>
                            </div>
                            <div class="guest-input-col">
                                <label for="reg_guest_phone">Số điện thoại <span class="req">*</span></label>
                                <input type="tel" id="reg_guest_phone" name="guest_phone" placeholder="Ví dụ: 0912.345.678" required>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Bảng chọn Thứ (Ngày) và Ca làm việc (2 cột trực quan theo đề bài) -->
                <div class="reg-schedule-selection-box">
                    <div class="reg-schedule-box-header">
                        <h4>LỰA CHỌN THỨ (NGÀY) VÀ CA LÀM VIỆC</h4>
                        <p>Tích chọn các ca mà bạn có thể tham gia (có thể chọn nhiều ngày và nhiều ca):</p>
                    </div>

                    <div id="reg_modal_shifts_container" class="reg-shifts-dynamic-container">
                        <!-- JS sẽ đổ danh sách ca theo 2 cột: Thứ (Ngày) & Ca làm việc -->
                    </div>
                </div>

                <!-- Ghi chú nguyện vọng / kỹ năng bổ sung -->
                <div class="reg-notes-group">
                    <label for="reg_volunteer_note">Ghi chú hoặc nguyện vọng phân công cụ thể (Tùy chọn):</label>
                    <textarea id="reg_volunteer_note" name="volunteer_note" rows="2" placeholder="Ví dụ: Tôi có xe máy riêng, sẵn sàng hỗ trợ chở vật dụng hoặc hậu cần sơ cứu..."></textarea>
                </div>
            </div>

            <div class="modal-footer modal-footer-enhanced">
                <div class="modal-footer-status">
                    <span class="quota-pill-status quota-status-open">&#10003; Đăng ký trực tiếp</span>
                </div>
                <div class="modal-footer-actions">
                    <button type="button" class="btn-cancel btn-modal-close">Hủy bỏ</button>
                    <button type="submit" id="btn_confirm_reg" class="btn-primary modal-cta-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Xác Nhận Đăng Ký
                </button>
            </div>
        </form>
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
                    Nền tảng quản lý và điều phối hoạt động tình nguyện, phân công ca trực và hỗ trợ thanh niên tham gia công tác xã hội.
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
                    <li><a href="/phan_cong.php">Phân công ca trực</a></li>
                    <li><a href="/lich_truc.php">Lịch trực cá nhân</a></li>
                    <li><a href="/register.php">Đăng ký ca trực</a></li>
                    <li><a href="/dang_nhap.php">Đăng nhập hệ thống</a></li>
                    <li><a href="/dang_ky_tk.php">Đăng ký tài khoản</a></li>
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
