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

    // 3. MODAL CHI TIẾT HOẠT ĐỘNG
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

    document.querySelectorAll('.btn-view-activity-detail').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const actDataStr = this.getAttribute('data-activity');
            if (!actDataStr || !activityModal) return;

            try {
                const data = JSON.parse(actDataStr);
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

                // Set register link
                if (modalRegisterBtn) {
                    modalRegisterBtn.href = '/register.php?event_id=' + data.id;
                    if (data.status === 'full') {
                        modalRegisterBtn.textContent = 'Đã đủ chỉ tiêu tuyển quân';
                        modalRegisterBtn.classList.add('disabled');
                        modalRegisterBtn.style.pointerEvents = 'none';
                        modalRegisterBtn.style.opacity = '0.6';
                    } else {
                        modalRegisterBtn.textContent = 'Đăng Ký Tham Gia Hoạt Động';
                        modalRegisterBtn.classList.remove('disabled');
                        modalRegisterBtn.style.pointerEvents = '';
                        modalRegisterBtn.style.opacity = '1';
                    }
                }

                activityModal.classList.add('open');
                document.body.classList.add('modal-open');
            } catch (err) {
                console.error('Error parsing activity data:', err);
            }
        });
    });

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
