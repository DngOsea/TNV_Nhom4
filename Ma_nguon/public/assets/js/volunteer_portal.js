/**
 * VOLUNTEER PLATFORM INTERACTIVE SCRIPTS
 * Xử lý: Tab chuyển 5 nhóm chủ đề, Modal chi tiết hoạt động, Modal xem biểu mẫu, Phân công ca trực, Dropdown tài khoản
 * Không sử dụng biểu tượng cảm xúc (sticker)
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. DROPDOWN TÀI KHOẢN NGƯỜI DÙNG Ở GÓC TRÊN BÊN PHẢI
    const userBadge = document.querySelector('.user-profile-badge');
    const userDropdown = document.querySelector('.user-profile-dropdown');

    if (userBadge && userDropdown) {
        userBadge.addEventListener('click', function (e) {
            e.stopPropagation();
            const isExpanded = userDropdown.classList.contains('show');
            userDropdown.classList.toggle('show', !isExpanded);
            userBadge.setAttribute('aria-expanded', !isExpanded);
        });

        document.addEventListener('click', function (e) {
            if (!userDropdown.contains(e.target) && !userBadge.contains(e.target)) {
                userDropdown.classList.remove('show');
                userBadge.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // 1B. NÚT THỬ VAI TRÒ TRÊN HEADER (KHI CHƯA ĐĂNG NHẬP)
    const demoBtn = document.getElementById('btn_demo_dropdown');
    const demoMenu = document.querySelector('.demo-dropdown-menu');
    if (demoBtn && demoMenu) {
        demoBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            demoMenu.classList.toggle('show');
        });
        document.addEventListener('click', function (e) {
            if (!demoMenu.contains(e.target) && !demoBtn.contains(e.target)) {
                demoMenu.classList.remove('show');
            }
        });
    }

    // 2. TABS 5 NHÓM CHỦ ĐỀ HOẠT ĐỘNG
    const categoryTabs = document.querySelectorAll('.tab-category-btn, .cat-pill');
    const activityCards = document.querySelectorAll('.activity-card-item');
    const emptyState = document.getElementById('empty_activity_state');

    categoryTabs.forEach(tab => {
        tab.addEventListener('click', function () {
            const selectedCategory = this.getAttribute('data-category');
            if (!selectedCategory) return;

            categoryTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            let visibleCount = 0;
            activityCards.forEach(card => {
                const cardCat = card.getAttribute('data-category');
                if (selectedCategory === 'all' || cardCat === selectedCategory) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            if (emptyState) {
                emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
            }
        });
    });

    // 3. MODAL CHI TIẾT HOẠT ĐỘNG & MODAL ĐĂNG KÝ TRỰC TUYẾN
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    const activityModal = document.getElementById('activity_detail_modal');
    const modalTitle = document.getElementById('modal_act_title');
    const modalCategory = document.getElementById('modal_act_category');
    const modalDate = document.getElementById('modal_act_date');
    const modalLocation = document.getElementById('modal_act_location');
    const modalMeeting = document.getElementById('modal_act_meeting');
    const modalSummary = document.getElementById('modal_act_summary');
    const modalLeader = document.getElementById('modal_act_leader');
    const modalLeaderPhone = document.getElementById('modal_act_leader_phone');
    const modalShifts = document.getElementById('modal_act_shifts');
    const modalSkills = document.getElementById('modal_act_skills');
    const modalBenefits = document.getElementById('modal_act_benefits');
    const modalRegisterBtn = document.getElementById('modal_act_register_btn');
    const modalQuotaStatus = document.getElementById('modal_act_quota_status');

    let currentViewingActivity = null;

    // Các phần tử của Modal Đăng ký
    const regModal = document.getElementById('activity_register_modal');
    const regActId = document.getElementById('reg_modal_act_id');
    const regActName = document.getElementById('reg_modal_act_name');
    const regCatBadge = document.getElementById('reg_modal_cat_badge');
    const regActDate = document.getElementById('reg_modal_act_date');
    const regActLocation = document.getElementById('reg_modal_act_location');
    const regShiftsContainer = document.getElementById('reg_modal_shifts_container');
    const regForm = document.getElementById('activity_register_form');

    // Mở modal đăng ký hoạt động
    function openRegisterModal(data) {
        if (!data || !regModal) return;

        // Đóng các modal khác đang mở
        document.querySelectorAll('.app-modal.open').forEach(m => m.classList.remove('open'));

        if (regActId) regActId.value = data.id || '';
        if (regActName) regActName.textContent = data.title || 'Chiến dịch tình nguyện';
        if (regCatBadge) {
            regCatBadge.textContent = data.category_name || 'ĐĂNG KÝ HOẠT ĐỘNG';
        }
        if (regActDate) {
            regActDate.textContent = (data.date_string || '') + (data.time_range ? ' (' + data.time_range + ')' : '');
        }
        if (regActLocation) {
            regActLocation.textContent = data.location || '';
        }

        // Đổ dữ liệu ca làm việc chia 2 cột: Thứ (Ngày) & Ca làm việc
        if (regShiftsContainer) {
            regShiftsContainer.innerHTML = '';
            const scheduleDays = data.schedule_days && data.schedule_days.length > 0 
                ? data.schedule_days 
                : null;

            if (scheduleDays) {
                const table = document.createElement('table');
                table.className = 'reg-schedule-2col-table';
                table.innerHTML = `
                    <thead>
                        <tr>
                            <th class="th-day-col">Thứ (Ngày Diễn Ra)</th>
                            <th class="th-shift-col">Ca Làm Việc & Khung Giờ (Tích chọn)</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                `;
                const tbody = table.querySelector('tbody');

                scheduleDays.forEach(day => {
                    const tr = document.createElement('tr');

                    // Cột 1: Thứ (Ngày)
                    const tdDay = document.createElement('td');
                    tdDay.className = 'td-day-info';
                    tdDay.innerHTML = `
                        <div class="day-primary-name">${escapeHtml(day.day_name || 'Ngày hoạt động')}</div>
                        <div class="day-date-sub">${escapeHtml(day.date_string || '')}</div>
                    `;

                    // Cột 2: Danh sách ca làm việc
                    const tdShifts = document.createElement('td');
                    tdShifts.className = 'td-shifts-list';

                    (day.shifts || []).forEach(shift => {
                        const shiftId = shift.shift_id;
                        const shiftQuota = parseInt(shift.quota, 10) || 10;
                        const shiftReg = parseInt(shift.registered, 10) || 0;
                        const remaining = Math.max(0, shiftQuota - shiftReg);
                        const isShiftFull = remaining <= 0;

                        const shiftLabel = document.createElement('label');
                        shiftLabel.className = 'shift-check-card' + (isShiftFull ? ' shift-full' : '');

                        shiftLabel.innerHTML = `
                            <input type="checkbox" name="selected_shifts[]" value="${escapeHtml(shiftId)}" ${isShiftFull ? 'disabled' : ''}>
                            <span class="custom-checkbox-marker" aria-hidden="true"></span>
                            <div class="shift-details-text">
                                <span class="shift-name-strong">${escapeHtml(shift.shift_name)}</span>
                                <span class="shift-hours-badge">${escapeHtml(shift.time_range)}</span>
                                ${shift.skill_req ? `<span class="shift-skill-pill">${escapeHtml(shift.skill_req)}</span>` : ''}
                            </div>
                            <span class="shift-quota-indicator ${isShiftFull ? 'quota-full' : 'quota-available'}">
                                ${isShiftFull ? 'Hết chỗ' : 'Còn ' + remaining + ' suất'}
                            </span>
                        `;
                        tdShifts.appendChild(shiftLabel);
                    });

                    tr.appendChild(tdDay);
                    tr.appendChild(tdShifts);
                    tbody.appendChild(tr);
                });

                regShiftsContainer.appendChild(table);
            } else {
                // Fallback nếu không có schedule_days cấu trúc
                const fallbackList = document.createElement('div');
                fallbackList.className = 'fallback-shifts-wrap';
                (data.shifts || ['Ca Sáng (08:00 - 12:00)']).forEach((sh, idx) => {
                    const fallbackId = 'SH_' + (data.id || '101') + '_' + idx;
                    const fLabel = document.createElement('label');
                    fLabel.className = 'shift-check-card';
                    fLabel.innerHTML = `
                        <input type="checkbox" name="selected_shifts[]" value="${escapeHtml(fallbackId)}">
                        <span class="custom-checkbox-marker" aria-hidden="true"></span>
                        <div class="shift-details-text">
                            <span class="shift-name-strong">${escapeHtml(sh)}</span>
                        </div>
                    `;
                    fallbackList.appendChild(fLabel);
                });
                regShiftsContainer.appendChild(fallbackList);
            }
        }

        regModal.classList.add('open');
        document.body.classList.add('modal-open');
    }

    // Gắn sự kiện cho các nút "Đăng Ký Tham Gia" trên từng thẻ hoạt động
    document.querySelectorAll('.btn-open-register-modal').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const actDataStr = this.getAttribute('data-activity');
            if (!actDataStr) return;
            try {
                const data = JSON.parse(actDataStr);
                openRegisterModal(data);
            } catch (err) {
                console.error('Error parsing activity data:', err);
            }
        });
    });

    // Mở Modal Chi Tiết Hoạt Động
    document.querySelectorAll('.btn-view-activity-detail').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const actDataStr = this.getAttribute('data-activity');
            if (!actDataStr || !activityModal) return;

            try {
                const data = JSON.parse(actDataStr);
                currentViewingActivity = data;

                if (modalTitle) modalTitle.textContent = data.title;
                if (modalCategory) {
                    modalCategory.textContent = data.category_name;
                    modalCategory.className = 'modal-category-badge cat-' + data.category;
                }
                if (modalDate) modalDate.textContent = data.date_string + ' (' + data.time_range + ')';
                if (modalLocation) modalLocation.textContent = data.location;
                if (modalMeeting) modalMeeting.textContent = data.meeting_point;
                if (modalSummary) modalSummary.textContent = data.summary;
                if (modalLeader) modalLeader.textContent = data.leader_name;
                if (modalLeaderPhone) {
                    modalLeaderPhone.textContent = data.leader_phone;
                    modalLeaderPhone.href = 'tel:' + data.leader_phone.replace(/[^0-9]/g, '');
                }

                // Render Shifts
                if (modalShifts) {
                    modalShifts.innerHTML = '';
                    (data.shifts || []).forEach(shift => {
                        const span = document.createElement('span');
                        span.className = 'pill-shift';
                        span.textContent = shift;
                        modalShifts.appendChild(span);
                    });
                }

                // Render Skills
                if (modalSkills) {
                    modalSkills.innerHTML = '';
                    (data.skills_needed || []).forEach(skill => {
                        const span = document.createElement('span');
                        span.className = 'pill-skill';
                        span.textContent = skill;
                        modalSkills.appendChild(span);
                    });
                }

                // Render Benefits
                if (modalBenefits) {
                    modalBenefits.innerHTML = '';
                    (data.benefits || []).forEach(benefit => {
                        const li = document.createElement('li');
                        li.textContent = benefit;
                        modalBenefits.appendChild(li);
                    });
                }

                // Cập nhật trạng thái và nút Đăng Ký trong Modal Chi Tiết
                const isFull = (data.registered >= data.capacity) || (data.status === 'full');
                if (modalQuotaStatus) {
                    if (isFull) {
                        modalQuotaStatus.textContent = 'Đã đủ quân số tuyển sinh';
                        modalQuotaStatus.className = 'quota-pill-status quota-status-full';
                    } else {
                        const remaining = Math.max(0, data.capacity - data.registered);
                        modalQuotaStatus.textContent = `Đang mở đăng ký (Còn ${remaining} suất)`;
                        modalQuotaStatus.className = 'quota-pill-status quota-status-open';
                    }
                }

                if (modalRegisterBtn) {
                    if (isFull) {
                        modalRegisterBtn.textContent = 'Đã Đủ Quân Số';
                        modalRegisterBtn.classList.add('disabled');
                        modalRegisterBtn.disabled = true;
                    } else {
                        modalRegisterBtn.innerHTML = `
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            Đăng Ký Tham Gia Hoạt Động
                        `;
                        modalRegisterBtn.classList.remove('disabled');
                        modalRegisterBtn.disabled = false;
                    }
                }

                activityModal.classList.add('open');
                document.body.classList.add('modal-open');
            } catch (err) {
                console.error('Error parsing activity data:', err);
            }
        });
    });

    // Nhấp vào nút "Đăng Ký Tham Gia Hoạt Động" trong Modal Chi Tiết:
    // Chuyển mượt mà sang Modal Đăng Ký của hoạt động đó
    if (modalRegisterBtn) {
        modalRegisterBtn.addEventListener('click', function (e) {
            e.preventDefault();
            if (this.disabled || this.classList.contains('disabled')) {
                showToast('Hoạt động này hiện đã tuyển đủ số lượng.');
                return;
            }
            if (currentViewingActivity) {
                activityModal.classList.remove('open');
                window.location.href = '/dang_ky_ca.php?event_id=' + encodeURIComponent(currentViewingActivity.id);
            }
        });
    }

    // Kiểm tra tính hợp lệ của Form đăng ký trước khi gửi
    if (regForm) {
        regForm.addEventListener('submit', function (e) {
            const checkedBoxes = regForm.querySelectorAll('input[name="selected_shifts[]"]:checked');
            if (checkedBoxes.length === 0) {
                e.preventDefault();
                showToast('Vui lòng tích chọn ít nhất 1 ca làm việc từ bảng lịch trước khi xác nhận.');
            }
        });
    }

    // Tự động mở modal nếu có tham số URL ?open_reg=ID
    try {
        const urlParams = new URLSearchParams(window.location.search);
        const openRegId = urlParams.get('open_reg') || urlParams.get('event_id');
        if (openRegId) {
            const allRegBtns = document.querySelectorAll('.btn-open-register-modal');
            for (let btn of allRegBtns) {
                const raw = btn.getAttribute('data-activity');
                if (raw && (raw.includes('"id":' + openRegId + ',') || raw.includes('"id":"' + openRegId + '"'))) {
                    btn.click();
                    break;
                }
            }
        }
    } catch (e) {
        // Silent
    }

    
// 4. MODAL XEM TRƯỚC BIỂU MẪU
    const formModal = document.getElementById('form_preview_modal');
    const formModalCode = document.getElementById('form_modal_code');
    const formModalTitle = document.getElementById('form_modal_title');
    const formModalBadge = document.getElementById('form_modal_badge');
    const formModalHeader = document.getElementById('form_modal_header');
    const formModalDocTitle = document.getElementById('form_modal_doc_title');
    const formModalBody = document.getElementById('form_modal_body');
    const formModalFooter = document.getElementById('form_modal_footer');

    document.querySelectorAll('.btn-preview-form').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const formDataStr = this.getAttribute('data-form');
            if (!formDataStr || !formModal) return;

            try {
                const formData = JSON.parse(formDataStr);
                if (formModalCode) formModalCode.textContent = formData.code;
                if (formModalTitle) formModalTitle.textContent = formData.title;
                if (formModalBadge) formModalBadge.textContent = formData.category_badge;

                const content = formData.preview_content || {};
                if (formModalHeader) formModalHeader.textContent = content.header || '';
                if (formModalDocTitle) formModalDocTitle.textContent = content.doc_title || '';
                if (formModalBody) formModalBody.textContent = content.body || '';
                if (formModalFooter) formModalFooter.textContent = content.footer || '';

                formModal.classList.add('open');
                document.body.classList.add('modal-open');
            } catch (err) {
                console.error('Error parsing form data:', err);
            }
        });
    });

    // ĐÓNG TẤT CẢ CÁC MODAL
    document.querySelectorAll('.modal-backdrop, .modal-close-btn, .btn-modal-close').forEach(closer => {
        closer.addEventListener('click', function () {
            document.querySelectorAll('.app-modal.open').forEach(m => m.classList.remove('open'));
            document.body.classList.remove('modal-open');
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.app-modal.open').forEach(m => m.classList.remove('open'));
            document.body.classList.remove('modal-open');
            if (userDropdown) userDropdown.classList.remove('show');
        }
    });

    // 5. NÚT NHẬN CA TRỰC NHANH
    document.querySelectorAll('.btn-claim-shift').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const shiftName = this.getAttribute('data-shift-name') || 'Ca trực';
            const actTitle = this.getAttribute('data-act-title') || 'Hoạt động';

            showToast('Hệ thống đã ghi nhận đăng ký: ' + shiftName + ' — ' + actTitle + '. Ban Chỉ Huy sẽ liên hệ xác nhận.');
            
            this.textContent = 'Đã nhận ca';
            this.style.background = '#059669';
            this.style.color = '#ffffff';
            this.disabled = true;
        });
    });

    // 6. GIẢ LẬP TẢI BIỂU MẪU
    document.querySelectorAll('.btn-download-form-file').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const format = this.getAttribute('data-format') || 'Tài liệu';
            const formCode = this.getAttribute('data-code') || 'BM-TNV';
            showToast('Đang tải văn bản mẫu: ' + formCode + ' (' + format + ')... Hoàn tất.');
        });
    });

    // 7. TOAST THÔNG BÁO CHUẨN MỰC
    function showToast(message) {
        let toastContainer = document.getElementById('app_toast_container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'app_toast_container';
            toastContainer.className = 'app-toast-container';
            document.body.appendChild(toastContainer);
        }

        const toast = document.createElement('div');
        toast.className = 'app-toast-item';
        toast.innerHTML = '<span class="toast-msg">' + message + '</span>';
        toastContainer.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('show');
        }, 10);

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                if (toast.parentNode) toast.parentNode.removeChild(toast);
            }, 300);
        }, 3500);
    }

    // 8. TỰ ĐỘNG CẬP NHẬT & XOAY VÒNG THÔNG BÁO ĐIỀU PHỐI (ƯU TIÊN TUYỂN GẤP)
    (function initAnnouncementTicker() {
        const track = document.getElementById('announcement_slider_track');
        if (!track) return;

        const slides = track.querySelectorAll('.announcement-slide-item');
        if (!slides.length) return;

        const counter = document.getElementById('announcement_counter');
        const prevBtn = document.getElementById('announcement_prev_btn');
        const nextBtn = document.getElementById('announcement_next_btn');
        const wrap = document.getElementById('top_announcement_bar');

        let currentIndex = 0;
        let timer = null;
        let isTransitioning = false;
        const total = slides.length;
        const INTERVAL_MS = 3800; // Đổi slide mỗi 3.8s

        function goToSlide(targetIndex, direction = 'next') {
            if (isTransitioning || targetIndex === currentIndex) return;
            isTransitioning = true;

            const currentSlide = slides[currentIndex];
            const nextSlide = slides[targetIndex];

            // Xóa tất cả các class hiệu ứng trước
            slides.forEach(s => {
                s.classList.remove('exit-up', 'exit-down');
            });

            if (direction === 'next') {
                currentSlide.classList.remove('active');
                currentSlide.classList.add('exit-up');
            } else {
                currentSlide.classList.remove('active');
                currentSlide.classList.add('exit-down');
            }

            nextSlide.classList.remove('exit-up', 'exit-down');
            nextSlide.classList.add('active');

            currentIndex = targetIndex;
            if (counter) {
                counter.textContent = (currentIndex + 1) + ' / ' + total;
            }

            setTimeout(() => {
                currentSlide.classList.remove('exit-up', 'exit-down');
                isTransitioning = false;
            }, 400);
        }

        function goNext() {
            const nextIdx = (currentIndex + 1) % total;
            goToSlide(nextIdx, 'next');
        }

        function goPrev() {
            const prevIdx = (currentIndex - 1 + total) % total;
            goToSlide(prevIdx, 'prev');
        }

        function startAutoRotation() {
            stopAutoRotation();
            timer = setInterval(goNext, INTERVAL_MS);
        }

        function stopAutoRotation() {
            if (timer) {
                clearInterval(timer);
                timer = null;
            }
        }

        // Bắt sự kiện click nút chuyển
        if (nextBtn) {
            nextBtn.addEventListener('click', function (e) {
                e.preventDefault();
                goNext();
                startAutoRotation();
            });
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', function (e) {
                e.preventDefault();
                goPrev();
                startAutoRotation();
            });
        }

        // Tạm dừng khi rê chuột vào để người dùng kịp đọc và bấm đăng ký
        if (wrap) {
            wrap.addEventListener('mouseenter', stopAutoRotation);
            wrap.addEventListener('mouseleave', startAutoRotation);
            wrap.addEventListener('focusin', stopAutoRotation);
            wrap.addEventListener('focusout', startAutoRotation);
        }

        // Khởi động vòng xoay tự động
        startAutoRotation();
    })();
});
