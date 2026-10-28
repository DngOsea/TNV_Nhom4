# HỆ THỐNG QUẢN LÝ & PHÂN CÔNG HOẠT ĐỘNG TÌNH NGUYỆN (D2L VOLUNTEER)
### BÀI TẬP LỚN — HỌC PHẦN: PHÁT TRIỂN PHẦN MỀM MÃ NGUỒN MỞ (FIT4111)
**Đề tài 06:** Xây dựng hệ thống tổ chức hoạt động và phân công tình nguyện viên

---

## 1. Giới Thiệu Dự Án

Hệ thống **D2L Volunteer Platform** là nền tảng điều hành, tổ chức và điều phối lực lượng tình nguyện viên thực địa, được xây dựng theo kiến trúc phân lớp sạch (Clean Architecture) sử dụng **PHP 8.1+** và **CSS chuẩn mực**, đáp ứng trọn vẹn các tiêu chí của Đề tài 06:
* **Quản lý danh mục hoạt động & ca trực:** 8 Chiến dịch trọng điểm phân bố theo 5 nhóm chủ đề xã hội thực tế (Môi trường, Y tế, Tiếp sức mùa thi, Bếp ăn từ thiện, Giáo dục vùng xa).
* **Quản lý hồ sơ lực lượng & năng lực chuyên môn:** Theo dõi 5 nhóm năng lực chuẩn mực: Sơ cấp cứu y tế, Điều phối giao thông, Hậu cần, Truyền thông và Sư phạm.
* **Lõi phân bổ nhân sự tự động (Yêu cầu kỹ thuật 1):** Giải thuật tối ưu hóa đa ràng buộc xét đồng thời: *Chuyên môn bắt buộc + Lịch rảnh/tránh trùng ca + Giới hạn quân số*.
* **Đối soát & Báo cáo hiệu suất (Yêu cầu kỹ thuật 2):** So sánh trực quan giữa giải thuật tối ưu của nhóm và phương án cơ sở FCFS (First-Come, First-Served) trên cùng tập dữ liệu thực nghiệm.
* **Cơ chế điều phối nhân sự dự bị:** Xử lý báo vắng và thay thế nhân sự với thẩm định máy chủ nghiêm ngặt (từ chối tức thì nếu trùng lịch, thiếu chuyên môn hoặc quá sĩ số).

---

## 2. Cấu Trúc Thư Mục Chuẩn Hóa

```text
Ma_nguon/
├── lab.sh                              # Script điều hành máy chủ và bộ kiểm thử
├── data/
│   ├── btl_dataset.php                 # Bộ dữ liệu thực nghiệm chuẩn (30 TNV, 12 Ca trực, 5 Kỹ năng)
│   ├── volunteer_data.php              # Dữ liệu hoạt động thực tế & tài khoản điều hành
│   └── events.php                      # Dữ liệu phục vụ kiểm thử bài lab cơ bản
├── src/
│   ├── AssignmentEngine.php            # LÕI THUẬT TOÁN PHÂN BỔ & THẨM ĐỊNH RÀNG BUỘC
│   ├── Input.php                       # Lọc dữ liệu đầu vào & xác thực an toàn
│   └── EventFilter.php                 # Bộ lọc tìm kiếm hoạt động
├── templates/
│   ├── header.php                      # Thanh điều hướng chuẩn mực
│   └── footer.php                      # Chân trang chuẩn Đoàn - Hội
├── tests/
│   ├── helper.php                      # Thư viện kiểm thử cơ bản
│   ├── run.php                         # Kiểm thử mẫu của Lab
│   ├── extension.php                   # Kiểm thử mở rộng của Lab
│   └── assignment_test.php             # 5 PHÉP THỬ BẮT BUỘC ĐỀ TÀI 06 BTL
└── public/
    ├── index.php                       # Trang chủ hệ thống & Banner chuyển động
    ├── hoat_dong.php                   # Danh mục chiến dịch & Bộ lọc chuyên môn
    ├── phan_cong.php                   # BẢNG ĐIỀU HÀNH & PHÂN BỔ NHÂN SỰ
    ├── doi_soat_thuat_toan.php         # BÁO CÁO PHÂN TÍCH & ĐỐI SOÁT HIỆU SUẤT
    ├── bieu_mau.php                    # Hồ sơ cam kết & minh chứng tình nguyện
    ├── register.php                    # Tiếp nhận đăng ký điều phối
    ├── dang_nhap.php                   # Xác thực tài khoản điều hành
    ├── dang_ky_tk.php                  # Đăng ký tài khoản tình nguyện viên
    └── assets/
        ├── css/
        │   ├── ql_tinh_nguyen.css      # Toàn bộ giao diện hệ thống
        │   ├── tokens.css
        │   └── app.css
        ├── js/
        │   ├── volunteer_portal.js     # Tương tác cổng tình nguyện viên
        │   └── banner_chuyen_dong.js   # Hiệu ứng chuyển động banner
        └── images/                     # Ảnh banner thực tế độ phân giải cao
```

---

## 3. Hướng Dẫn Cài Đặt & Khởi Chạy

### 3.1. Yêu cầu môi trường
* Hệ điều hành: Linux, Ubuntu hoặc WSL (Windows Subsystem for Linux).
* PHP: Phiên bản **8.1 trở lên**.
* Git.

### 3.2. Khởi chạy máy chủ cục bộ
Mở terminal tại thư mục `Ma_nguon/` và thực thi:
```bash
# 1. Kiểm tra môi trường
bash lab.sh check

# 2. Khởi chạy máy chủ Web
bash lab.sh run
```
Truy cập hệ thống tại: **http://127.0.0.1:8103**

---

## 4. Hướng Dẫn Kiểm Thử Tự Động (Automated Testing)

Hệ thống tích hợp đầy đủ các kịch bản kiểm thử tự động, chạy độc lập và trả về kết quả rõ ràng:

```bash
# 1. Kiểm thử bài lab cơ bản (PASS 7/7)
bash lab.sh test

# 2. Kiểm thử mở rộng (PASS 8/8)
bash lab.sh extend

# 3. KIỂM THỬ 5 PHÉP THỬ BẮT BUỘC ĐỀ TÀI 06 BTL (PASS 16/16)
bash lab.sh btl-test
```

### Chi tiết 5 phép thử trọng tâm trong `assignment_test.php`:
1. **Phép thử 1:** Không có bất kỳ nhân sự nào bị phân công vào hai ca trùng khung giờ (`zero-time-conflicts`).
2. **Phép thử 2:** Ca yêu cầu chuyên môn Y tế kiên quyết từ chối người chỉ có chuyên môn Hậu cần (`MISSING_SKILL`).
3. **Phép thử 3:** Thao tác bổ sung vượt quá chỉ tiêu quân số của ca trực bị từ chối (`OVER_CAPACITY`).
4. **Phép thử 4:** Khi thiếu hụt nhân sự, báo cáo chính xác số lượng thiếu tại từng ca, **tuyệt đối không sinh dữ liệu người ảo**.
5. **Phép thử 5:** Chạy đối soát độc lập nhiều lần trên cùng nguồn dữ liệu cho kết quả nhất quán 100% (Deterministic), chứng minh độ cân bằng số ca (Workload Gap) vượt trội.

---

## 5. Hướng Dẫn Nghiệm Thu & Trình Diễn Các Chức Năng Cốt Lõi

Khi giảng viên nghiệm thu hoặc bảo vệ, thực hiện các thao tác trực quan sau:

### Trình diễn 1: Tự động phân bổ ca trực theo thuật toán (Yêu cầu kỹ thuật 1)
1. Mở trang **Ca Trực**: `http://127.0.0.1:8103/phan_cong.php`.
2. Trên thanh điều hành tác nghiệp, nhấn nút **"Tự Động Phân Ca"**.
3. Thuật toán `AssignmentEngine::assignSmartGreedy` tự động phân tích độ hiếm chuyên môn, ưu tiên các ca Y tế và Sư phạm xếp trước, và cân bằng số ca giữa 30 tình nguyện viên.
4. Nhấn **"Phê Duyệt Lịch Trực"** để chốt danh sách chính thức.

### Trình diễn 2: Xử lý báo vắng & Điều phối nhân sự thay thế
1. Tại ca trực **CA-03 (Ngày hội Hiến máu - Y tế)** trên bảng phân công:
   * Nhấn nút **"Báo vắng"** tại 1 vị trí bất kỳ $\rightarrow$ Hệ thống cập nhật trạng thái `ĐÃ BÁO VẮNG` và ca trực báo thiếu người.
2. Nhấn nút **"Thay người"** $\rightarrow$ Cửa sổ điều phối mở ra:
   * **Thử nghiệm trường hợp vi phạm:** Chọn đồng chí *Phạm Minh Quân (Chuyên môn Hậu cần)* $\rightarrow$ Nhấn *Xác Nhận Điều Phối* $\rightarrow$ Máy chủ từ chối ngay lập tức và hiển thị cảnh báo đỏ do thiếu kỹ năng Y tế.
   * **Thử nghiệm trường hợp hợp lệ:** Chọn đồng chí *Lê Nguyễn Phương Thảo (Chuyên môn Y tế)* $\rightarrow$ Nhấn *Xác Nhận Điều Phối* $\rightarrow$ Máy chủ phê duyệt, cập nhật trạng thái `Thay cho: [Tên người cũ]` và ca trực trở lại đủ chỉ tiêu.

### Trình diễn 3: Đối soát & So sánh hiệu suất thuật toán (Yêu cầu kỹ thuật 2)
1. Mở trang **Đối Soát Phân Công**: `http://127.0.0.1:8103/doi_soat_thuat_toan.php`.
2. Đánh giá bảng so sánh KPI giữa **Phương án tuần tự (FCFS)** và **Phương án tối ưu đa ràng buộc (Greedy with Priority)**.
3. Chuyển đổi qua lại giữa **3 Mô hình dữ liệu thực nghiệm**:
   * *Mô hình 1 (Tiêu chuẩn):* Đủ 30 TNV cho 12 ca.
   * *Mô hình 2 (Áp lực thiếu hụt):* Cắt giảm nguồn lực Y tế/Sư phạm để xem hệ thống cảnh báo thiếu minh bạch.
   * *Mô hình 3 (Trùng lịch cao điểm):* Dồn nhân sự vào ca sáng để xem hệ thống triệt tiêu trùng lịch.

---

## 6. Phân Công Trách Nhiệm Thành Viên Nhóm

| STT | Họ và Tên | Mã Sinh Viên | Vai trò & Nhiệm vụ phụ trách | Minh chứng Commit |
| :---: | :--- | :---: | :--- | :--- |
| 1 | *[Họ tên thành viên 1]* | *[Mã SV]* | **Nhóm trưởng:** Thiết kế kiến trúc tổng thể, xây dựng lõi thuật toán `AssignmentEngine.php`, trang đối soát benchmark `doi_soat_thuat_toan.php`. | `feat: AssignmentEngine`, `feat: Benchmark` |
| 2 | *[Họ tên thành viên 2]* | *[Mã SV]* | **Thành viên:** Xây dựng bảng điều phối thực địa `phan_cong.php`, chức năng báo vắng & thay người, chuẩn hóa bộ dữ liệu `btl_dataset.php`. | `feat: Dispatch dashboard`, `feat: Reassignment modal` |
| 3 | *[Họ tên thành viên 3]* | *[Mã SV]* | **Thành viên:** Xây dựng bộ kiểm thử tự động `assignment_test.php`, tích hợp `lab.sh`, hoàn thiện hồ sơ mã nguồn mở (`README.md`, `CONTRIBUTING.md`). | `test: 5 mandatory tests`, `docs: Open source docs` |

---

## 7. Giấy Phép Sử Dụng (License)
Dự án được phân phối theo giấy phép mã nguồn mở **MIT License** (xem chi tiết tại tệp [LICENSE](LICENSE)).
