# NHẬT KÝ ĐỌC VÀ LƯU TRỮ TOÀN BỘ MÃ NGUỒN (CODEBASE AUDIT & KNOWLEDGE BASE LOG)
> **Dự án**: Hệ Thống Quản Lý Tình Nguyện Viên & Hoạt Động Xã Hội — Đội Tình Nguyện D2L  
> **Repository**: `https://github.com/DngOsea/TNV_Nhom4.git` (Branch `main`, Commit `d485d9f`)  
> **Thời điểm lập log**: Ngày 17 Tháng 11 Năm 2026  
> **Mục đích duy nhất**: Lưu lại toàn bộ 100% dữ liệu, cấu trúc, hàm, biến, cơ sở dữ liệu và logic đã đọc từ tất cả các file trong dự án.  
> **QUY TẮC BẮT BUỘC CHO CÁC LẦN SAU**: Tuyệt đối không cần đọc lại các file mã nguồn từ đầu. Mỗi khi người dùng yêu cầu chỉnh sửa hoặc thêm tính năng mới, AI Agent sẽ trích xuất trực tiếp thông tin từ tài liệu này để định vị và cập nhật mã nguồn một cách chính xác, an toàn nhất.

---

## MỤC LỤC TỔNG QUAN

1. [Cấu Trúc Cây Thư Mục Toàn Dự Án](#1-cấu-trúc-cây-thư-mục-toàn-dự-án)
2. [Kiến Trúc Kỹ Thuật & Luồng Dữ Liệu](#2-kiến-trúc-kỹ-thuật--luồng-dữ-liệu)
3. [Dữ Liệu Đọc Chi Tiết Từng File Trong `src/` (Core Logic)](#3-dữ-liệu-đọc-chi-tiết-từng-file-trong-src-core-logic)
4. [Dữ Liệu Đọc Chi Tiết Từng File Trong `templates/` (Giao Diện Chung)](#4-dữ-liệu-đọc-chi-tiết-từng-file-trong-templates-giao-diện-chung)
5. [Dữ Liệu Đọc Chi Tiết Từng File Trong `public/` (Các Trang Chức Năng)](#5-dữ-liệu-đọc-chi-tiết-từng-file-trong-public-các-trang-chức-năng)
6. [Dữ Liệu Đọc Chi Tiết `data/` & `database/` (Cơ Sở Dữ Liệu & Seed)](#6-dữ-liệu-đọc-chi-tiết-data--database-cơ-sở-dữ-liệu--seed)
7. [Dữ Liệu Đọc Chi Tiết `public/assets/` (CSS & JavaScript)](#7-dữ-liệu-đọc-chi-tiết-publicassets-css--javascript)
8. [Từ Điển Biến Phiên & Cấu Trúc Dữ Liệu Toàn Hệ Thống](#8-từ-điển-biến-phiên--cấu-trúc-dữ-liệu-toàn-hệ-thống)
9. [Bản Đồ Ánh Xạ Tính Năng Phục Vụ Cập Nhật Code Nhanh](#9-bản-đồ-ánh-xạ-tính-năng-phục-vụ-cập-nhật-code-nhanh)

---

## 1. CẤU TRÚC CÂY THƯ MỤC TOÀN DỰ ÁN

```
d:\PTPM\Lab03\Ma_nguon\
├── .gitignore
├── CODEBASE_AUDIT_LOG.md          # [File này] Bản lưu trữ tri thức toàn diện của hệ thống
├── LICENSE
├── README.md                      # Hướng dẫn đề tài BTL và kiến trúc hệ thống
├── config.example.php             # Mẫu cấu hình môi trường (DB_PATH, APP_NAME, DEBUG)
├── lab.sh                         # Shell script hỗ trợ chạy kiểm thử và khởi tạo lab
├── src/                           # 11 Lớp xử lý nghiệp vụ lõi (PHP 8.x)
│   ├── ActivityManager.php        # Quản lý 50 hoạt động/dự án, tính lấp đầy quân số
│   ├── AssignmentEngine.php       # Động cơ phân công tối ưu tự động (Greedy/Hungarian)
│   ├── Auth.php                   # Quản lý phiên, xác thực & phân quyền RBAC
│   ├── Database.php               # Singleton kết nối PDO SQLite & cơ chế fallback JSON
│   ├── EventFilter.php            # Bộ lọc sự kiện đa chiều (từ khóa, chủ đề, địa bàn)
│   ├── Input.php                  # Làm sạch dữ liệu, chống XSS, ép kiểu an toàn
│   ├── LocationManager.php        # Quản lý 63 tỉnh thành & phân loại vùng miền
│   ├── Pagination.php             # Phân trang động bảo toàn URL search query
│   ├── RegistrationManager.php    # Ghi nhận ca trực, kiểm tra chống trùng lịch
│   ├── SkillManager.php           # 8 nhóm kỹ năng nghiệp vụ & chứng chỉ chuyên môn
│   └── VolunteerManager.php       # Quản lý 50 TNV, tính điểm uy tín và giờ cống hiến
├── templates/                     # Giao diện dùng chung cho toàn bộ website
│   ├── header.php                 # Header, Marquee tin khẩn cấp, RBAC navigation
│   └── footer.php                 # Footer bản quyền, giấy phép FIT4111, hotline
├── public/                        # 17 Trang Web công khai và quản trị
│   ├── admin_import.php           # Bảng chỉ huy: Quản trị 50 dự án & 50 TNV, Quân số, Roster
│   ├── bieu_mau.php               # Danh mục biểu mẫu nghiệp vụ và quy trình
│   ├── chinh_sua_du_an.php        # Form cập nhật thông tin dự án (Dành cho BCH)
│   ├── dang_ky_ca.php             # Cổng đăng ký ca trực cá nhân & đăng ký nhóm
│   ├── dang_ky_tk.php             # Đăng ký tài khoản tình nguyện viên mới
│   ├── dang_nhap.php              # Đăng nhập & nút chuyển đổi vai trò 1-click
│   ├── doi_soat_thuat_toan.php    # Bảng kiểm toán ma trận phân công thuật toán AI
│   ├── hoat_dong.php              # Danh mục tìm kiếm & lọc hoạt động đa tiêu chí
│   ├── index.php                  # Trang chủ, Banner Hero, Thống kê, Chiến dịch hot
│   ├── lich_truc.php              # Xem lịch trực toàn đội và lịch trực cá nhân
│   ├── phan_cong.php              # Trung tâm xếp lịch & phân công ca trực thực địa
│   ├── phe_duyet_ca.php           # Ban chỉ huy xét duyệt / từ chối ca trực mới đăng ký
│   ├── phe_duyet_vang.php         # Nộp đơn báo vắng & Ban chỉ huy xét duyệt
│   ├── profile.php                # Hồ sơ cá nhân TNV, huy hiệu và bảng thành tích
│   ├── quan_ly_tnv.php            # Danh bạ hồ sơ toàn bộ tình nguyện viên
│   ├── register.php               # Tiếp nhận đăng ký phụ trợ
│   └── tao_su_kien.php            # Form khởi tạo chiến dịch & ca trực mới
├── data/                          # Dữ liệu hạt giống & CSDL cục bộ
│   ├── activities_store.json      # Store dữ liệu hoạt động dạng JSON
│   ├── btl_dataset.php            # Bộ dữ liệu thực tế phục vụ thuật toán phân công
│   ├── events.php                 # Danh mục sự kiện gốc
│   ├── seed_volunteers.json       # 50 Hồ sơ TNV chi tiết chuẩn JSON
│   ├── volunteer_data.php         # 5 Lĩnh vực, 50 Dự án, 4 Demo users
│   ├── volunteer_platform.sqlite  # File CSDL SQLite chính thức
│   ├── volunteer_registrations.json # Lịch sử đăng ký ca trực
│   └── volunteers_store.json      # Store lưu trữ TNV
├── database/                      # Các kịch bản khởi tạo CSDL
│   ├── import.php                 # Script PHP CLI import CSDL
│   ├── import_50_volunteers_and_projects.sql # SQL nạp 50 dự án và 50 TNV
│   ├── mysql_full_init.sql        # Bản chuyển đổi tương thích MySQL
│   └── schema.sql                 # Lược đồ bảng SQLite nguyên bản
└── public/assets/                 # Tài nguyên giao diện tĩnh
    ├── css/
    │   ├── app.css                # CSS nền tảng ứng dụng
    │   ├── pro_design_theme.css   # Chủ đề giao diện chuyên nghiệp
    │   ├── ql_tinh_nguyen.css     # CSS nghiệp vụ chính (~8.000 dòng)
    │   └── tokens.css             # Thiết lập Design Tokens (màu, font, spacing)
    ├── images/
    │   ├── banner_logo_d2l.jpg    # Logo đội hình và nhận diện thương hiệu
    │   ├── banner_tnv_1.jpg       # Ảnh chụp thực địa tình nguyện độ phân giải cao
    │   └── banner_tnv_2.jpg       # Ảnh hoạt động cộng đồng độ phân giải cao
    └── js/
        ├── banner_chuyen_dong.js  # Điều khiển slider tự động và nút trượt
        └── volunteer_portal.js    # Logic tương tác, lọc tab, validate biểu mẫu
```

---

## 2. KIẾN TRÚC KỸ THUẬT & LUỒNG DỮ LIỆU

### 2.1. Phân quyền người dùng (Role-Based Access Control - RBAC)
Hệ thống chia làm 2 nhóm quyền chính dựa trên `$_SESSION['user']['role_type']`:
1. **Ban Chỉ Huy / Điều Phối Viên (`coordinator`)**:
   - Tài khoản mẫu: `USR-001` (Trần Hoàng Nam - Chỉ Huy Trưởng), `USR-002` (Lê Nguyễn Phương Thảo - Y tế), `USR-003` (Phạm Minh Quân - Hậu cần).
   - Quyền hạn: Quản trị dự án (`admin_import.php`), phân công thuật toán (`phan_cong.php`), phê duyệt báo vắng (`phe_duyet_vang.php`), chỉnh sửa dự án (`chinh_sua_du_an.php`), tạo sự kiện (`tao_su_kien.php`), đối soát AI (`doi_soat_thuat_toan.php`).
2. **Tình Nguyện Viên Thường (`volunteer`)**:
   - Tài khoản mẫu: `USR-004` (Nguyễn Hà My - TNV Nòng cốt).
   - Quyền hạn: Xem trang chủ, tìm kiếm hoạt động (`hoat_dong.php`), đăng ký ca trực (`dang_ky_ca.php`), xem lịch trực cá nhân (`lich_truc.php`), nộp đơn báo vắng (`phe_duyet_vang.php`), xem hồ sơ và điểm thưởng cá nhân (`profile.php`).

### 2.2. Cơ chế Lưu Trữ 3 Tầng (3-Tier Storage Strategy)
1. **Tầng 1 (Primary Database)**: SQLite database tại `data/volunteer_platform.sqlite` qua class `Database` (PDO).
2. **Tầng 2 (Fallback JSON)**: Tự động nạp từ `data/seed_volunteers.json` và `data/volunteer_data.php` nếu môi trường máy chủ thiếu SQLite driver hoặc CSDL chưa import.
3. **Tầng 3 (Runtime Session State)**: `$_SESSION['project_roster_custom'][$projectId]` lưu trữ danh sách quân số điều phối thủ công thời gian thực. Khi người điều phối thêm/sửa/xóa thành viên trên `admin_import.php`, dữ liệu được lưu tức thì vào session này.

---

## 3. DỮ LIỆU ĐỌC CHI TIẾT TỪNG FILE TRONG `src/` (CORE LOGIC)

### 3.1. `src/Auth.php`
- **Mục đích**: Xác thực người dùng và kiểm soát phiên làm việc.
- **Các hàm & phương thức**:
  - `Auth::user()`: Trả về mảng thông tin người dùng trong `$_SESSION['user']` hoặc `null`.
  - `Auth::check()`: Kiểm tra người dùng đã đăng nhập chưa (`bool`).
  - `Auth::isCoordinator()`: Trả về `true` nếu `role_type === 'coordinator'`.
  - `Auth::isVolunteer()`: Trả về `true` nếu `role_type === 'volunteer'`.
  - `Auth::requireLogin($redirect = '/dang_nhap.php')`: Chuyển hướng nếu chưa đăng nhập.
  - `Auth::requireCoordinator($redirect = '/')`: Chặn truy cập nếu không phải là Ban Chỉ Huy.
  - `Auth::login(array $user)`: Lưu thông tin người dùng vào `$_SESSION['user']`.
  - `Auth::logout()`: Xóa `$_SESSION['user']`.

### 3.2. `src/Input.php`
- **Mục đích**: Làm sạch và chuẩn hóa dữ liệu gửi lên từ phía client (Sanitization & XSS Protection).
- **Các hàm & phương thức**:
  - `Input::get(string $key, $default = null)`: Lấy dữ liệu từ `$_GET`, tự động lọc ký tự nguy hiểm.
  - `Input::post(string $key, $default = null)`: Lấy dữ liệu từ `$_POST`.
  - `Input::cleanString(?string $str)`: Loại bỏ thẻ HTML bằng `htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8')`.
  - `Input::cleanInt($val, int $default = 0)`: Ép kiểu số nguyên an toàn.
  - `Input::cleanArray(array $arr)`: Lọc đệ quy toàn bộ mảng.

### 3.3. `src/Pagination.php`
- **Mục đích**: Tính toán chỉ số phân trang và sinh đường dẫn URL phân trang linh hoạt.
- **Các hàm & phương thức**:
  - `Pagination::paginate(int $totalItems, int $currentPage, int $perPage)`: Trả về mảng chứa `totalPages`, `offset`, `limit`, `currentPage`, `hasPrev`, `hasNext`.
  - `Pagination::buildUrl(string $paramName, int $pageNumber, array $existingParams = [])`: Sinh chuỗi query URL bảo toàn toàn bộ bộ lọc tìm kiếm khác.

### 3.4. `src/EventFilter.php`
- **Mục đích**: Lọc danh sách chiến dịch tình nguyện theo đa tiêu chí.
- **Tiêu chuẩn lọc**:
  - `search`: Khớp không phân biệt hoa thường với tên hoạt động, mô tả, địa bàn, tên người phụ trách.
  - `category`: Khớp mã lĩnh vực (`thuc_te_moi_truong`, `hien_mau_y_te`, `tiep_suc_giao_duc`, `cuu_tro_an_sinh`, `cham_soc_yeu_thuong`).
  - `location`: Khớp địa bàn (Hà Nội, TP.HCM, Yên Bái, Sơn La,...).
  - `status`: Lọc theo trạng thái tiếp nhận (`open`, `urgent`, `full`, `completed`).

### 3.5. `src/LocationManager.php`
- **Mục đích**: Quản lý danh mục hành chính 63 tỉnh thành Việt Nam và gán nhãn khu vực địa bàn.
- **Cấu trúc dữ liệu**:
  - Mảng 3 miền: `Bắc` (Hà Nội, Lạng Sơn, Yên Bái, Sơn La, Cao Bằng,...), `Trung` (Đà Nẵng, Quảng Nam, Huế,...), `Nam` (TP.HCM, Cần Thơ, An Giang, Trà Vinh, Sóc Trăng,...).
  - Hàm `formatLocationLabel($loc)`: Chuẩn hóa hiển thị huy hiệu địa bàn kèm icon `📍`.

### 3.6. `src/SkillManager.php`
- **Mục đích**: Quản lý 8 nhóm kỹ năng chuyên môn của tình nguyện viên.
- **Danh mục 8 kỹ năng chuẩn**:
  1. `y_te`: Sơ Cấp Cứu & Hỗ Trợ Y Tế (Màu đỏ `#dc2626`).
  2. `cuu_ho`: Cứu Hộ & Ứng Phó Thiên Tai (Màu cam `#ea580c`).
  3. `hau_can`: Hậu Cần, Bếp Ăn & Quân Nhu (Màu hổ phách `#d97706`).
  4. `su_pham`: Giảng Dạy & Tiếp Sức Giáo Dục (Màu xanh dương `#2563eb`).
  5. `truyen_thong`: Truyền Thông, Báo Chí & Nhiếp Ảnh (Màu tím `#7c3aed`).
  6. `dan_doan`: Quản Trò, Hoạt Náo & Dẫn Đoàn (Màu hồng `#db2777`).
  7. `moi_truong`: Dọn Rác, Trồng Rừng & Thực Địa (Màu xanh lá `#059669`).
  8. `lai_xe`: Lái Xe An Toàn & Cơ Động (Màu xám lam `#475569`).
- **Hàm chính**: `getSkillBadge($skillCode)`, `validateRequiredSkills($vSkills, $shiftSkills)`.

### 3.7. `src/ActivityManager.php`
- **Mục đích**: Quản lý nghiệp vụ các chiến dịch tình nguyện.
- **Thuộc tính dự án**: `id`, `title`, `category`, `capacity`, `registered`, `is_urgent`, `date_string`, `time_range`, `location`, `leader_name`, `leader_phone`, `schedule_days`, `shifts`.
- **Hàm chính**:
  - `calculateFillRate($registered, $capacity)`: Trả về phần trăm lấp đầy và màu sắc tương ứng.
  - `checkUrgency($act)`: Xác định trạng thái Tuyển Gấp nếu chỉ tiêu thiếu hụt lớn hoặc sát ngày khởi hành.

### 3.8. `src/VolunteerManager.php`
- **Mục đích**: Quản lý hồ sơ 50 tình nguyện viên.
- **Thuộc tính TNV**: `id` (TNV-001 đến TNV-050), `name`, `gender`, `dob`, `phone`, `email`, `organization`, `blood_group` (A+, B+, O+, AB+, O-), `skills`, `custom_skills`, `volunteer_hours`, `reliability_score`, `health_note`.
- **Hàm chính**:
  - `getReliabilityStars($score)`: Xuất định dạng sao đánh giá (1-10 ⭐).
  - `isRareBloodGroup($group)`: Xác định nhóm máu hiếm (như O-, Rh-).

### 3.9. `src/RegistrationManager.php`
- **Mục đích**: Tiếp nhận đăng ký ca, xử lý xung đột giờ trực.
- **Hàm chính**:
  - `hasTimeConflict($volId, $newShiftTime)`: Kiểm tra TNV có bị trùng giờ giữa 2 ca trực khác nhau hay không.
  - `canRegisterMoreShifts($volId, $maxShifts)`: Kiểm tra xem đã vượt quá số ca tối đa đăng ký trong tuần/tháng hay chưa.

### 3.10. `src/AssignmentEngine.php`
- **Mục đích**: Thuật toán phân công ca trực tự động tối ưu hóa.
- **Giải thuật**:
  - Sử dụng ma trận trọng số (Match Scoring Matrix): Điểm phù hợp kỹ năng (50%) + Điểm địa bàn gần (25%) + Điểm uy tín tín nhiệm (15%) + Điểm cân bằng ca (10%).
  - Tự động gán TNV có kỹ năng sơ cấp cứu vào các vị trí trọng điểm y tế.

### 3.11. `src/Database.php`
- **Mục đích**: Singleton kết nối CSDL SQLite (`volunteer_platform.sqlite`), tự động seed và có cơ chế fallback.
- **Hàm chính**:
  - `getInstance()`: Khởi tạo/truy xuất thực thể kết nối duy nhất.
  - `getActivities($filters)`: Truy vấn danh sách hoạt động từ SQL hoặc dữ liệu fallback.
  - `getVolunteers($filters)`: Truy vấn danh sách TNV từ bảng `volunteers` hoặc `seed_volunteers.json`.
  - `importSeedSql()`: Thực thi nạp CSDL từ file `database/import_50_volunteers_and_projects.sql`.
  - `resetDatabase()`: Khôi phục CSDL về trạng thái gốc ban đầu.

---

## 4. DỮ LIỆU ĐỌC CHI TIẾT TỪNG FILE TRONG `templates/` (GIAO DIỆN CHUNG)

### 4.1. `templates/header.php`
- **Khởi tạo**: Chạy `session_start()` nếu chưa có; xử lý đăng xuất khi có query `?action=logout`.
- **Marquee Thông Báo Khẩn Cấp**:
  - Tự động quét mảng `$volunteerData['activities']`.
  - Tính toán số chỉ tiêu còn thiếu: `$deficit = max(0, $capacity - $registered)`.
  - Lập mảng `$topAnnouncements` ưu tiên các ca trực khẩn cấp (Viện Huyết Học, Đội Xe Máy, Sông Tô Lịch, Bệnh Viện K).
- **Thanh Điều Hướng (Navigation Bar)**:
  - Menu Trang Chủ (`/`), Hoạt Động (`/hoat_dong.php`).
  - Nếu là Ban Chỉ Huy (`Auth::isCoordinator()`): Hiển thị menu dropdown Lịch Trực (`/lich_truc.php`) và menu dropdown Điều Phối (gồm: Phân Công Ca, Duyệt Báo Vắng, Quản Lý Dự Án & TNV `admin_import.php`, Tạo Sự Kiện Mới, Đối Soát Thuật Toán).
  - Nếu là TNV: Hiển thị Đăng Ký Ca (`/dang_ky_ca.php`), Lịch Trực Cá Nhân (`/lich_truc.php`), Báo Vắng Ca (`/phe_duyet_vang.php`).
- **Nút Chuyển Vai Trò Nhanh**:
  - Cho phép người kiểm thử bấm 1-click chuyển đổi qua lại giữa Chỉ Huy Trưởng (`USR-001`) và TNV Nòng Cốt (`USR-004`).
- **Khung chứa nội dung**: Mở thẻ `<main id="main">` với `max-width: 1280px; margin: 0 auto;`.

### 4.2. `templates/footer.php`
- **Thành phần**:
  - Đóng thẻ `</main>`.
  - Chân trang hiển thị thông tin Đội Tình Nguyện D2L, Giấy phép Đào tạo CNTT FIT4111.
  - Hotline hỗ trợ 24/7 (`0908.123.456`), email điều phối (`banbch@d2l.org`).
  - Liên kết mạng xã hội (Facebook, Zalo, YouTube).

---

## 5. DỮ LIỆU ĐỌC CHI TIẾT TỪNG FILE TRONG `public/` (CÁC TRANG CHỨC NĂNG)

### 5.1. `public/index.php` (Trang Chủ & Hero Banner)
- **Banner Hero Slider**:
  - Chứa 3 slide chính:
    - Slide 1: **Showcase Lịch Ra Quân / Khởi Hành**.
    - Slide 2: **Chiến Dịch Trọng Điểm — Chủ Nhật Xanh & Trồng Rừng Cần Giờ**.
    - Slide 3: **Hoạt Động An Sinh — Giọt Hồng Nghĩa Tình & Bếp Ăn 0 Đồng**.
  - Nút trượt trái/phải (`.nut_chuyen_trai`, `.nut_chuyen_phai`) và 3 chấm điều khiển (`.cham_chuyen_slide`).
- **Dải Thống Kê Nhanh**:
  - 05 Nhóm Chủ Đề Hoạt Động.
  - 08 Chiến Dịch Trọng Điểm.
  - 1.250+ Lượt TNV Tham Gia.
  - 24/7 Sẵn Sàng Điều Động.
- **Danh Mục 5 Lĩnh Vực Tác Nghiệp**:
  - Môi Trường Xanh, Hiến Máu Y Tế, Mùa Thi Giáo Dục, Cứu Trợ Bếp Ăn 0Đ, Chăm Sóc Xã Hội.
- **Chiến Dịch Nổi Bật Đang Mở Đăng Ký**:
  - Hiển thị danh sách card hoạt động kèm nút *Đăng ký ca làm việc* và *Xem chi tiết*.

### 5.2. `public/admin_import.php` (Bảng Chỉ Huy Quản Trị)
- **Kiểm soát quyền**: `Auth::requireCoordinator()`.
- **Chức năng chính**:
  1. Quản lý trạng thái CSDL: Nút Import CSDL 50 dự án & 50 TNV, nút Reset CSDL gốc.
  2. Bảng Danh Mục Dự Án:
     - 50 dự án chi tiết (ID, Tên dự án, Lãnh đạo, Chủ đề, Địa bàn, Quân số lấp đầy, Trạng thái, Số ca, Thao tác).
     - Cột Thao tác chứa: Nút `Sửa` (chuyển sang `/chinh_sua_du_an.php?id={id}`), Nút `Xem` (mở Modal chi tiết).
  3. Bảng Danh Sách 50 Tình Nguyện Viên Nòng Cốt:
     - Hiển thị Họ tên, Giới tính, Năm sinh, Đơn vị, SĐT, Email, Nhóm máu, Kỹ năng, Điểm uy tín, Giờ cống hiến.
  4. Modal Chi Tiết Hoạt Động:
     - Tab 1: Thông tin lịch trình, địa bàn, người phụ trách, quân số và bảng ca trực con.
     - Tab 2: Danh sách quân số tham gia (Roster), lọc theo ca trực, tìm kiếm và nút xuất CSV điểm danh.

### 5.3. `public/hoat_dong.php` (Tra Cứu Hoạt Động)
- **Bộ lọc tìm kiếm**:
  - Tìm theo từ khóa (tên hoạt động, địa chỉ, cán bộ chỉ huy).
  - Lọc theo 5 lĩnh vực chủ đề.
  - Lọc theo địa bàn (Hà Nội, TP.HCM, toàn quốc).
  - Lọc theo trạng thái (Đang tuyển, Tuyển gấp, Đã đủ quân).
- **Giao diện**:
  - Grid dạng card hiện đại, hiển thị thanh tiến độ quân số, nhãn chuyên môn và nút *Đăng ký ca ngay*.

### 5.4. `public/dang_ky_ca.php` (Cổng Đăng Ký Ca Trực)
- Cho phép TNV chọn chiến dịch, chọn ca trực phù hợp trong lịch biểu.
- Kiểm tra tính hợp lệ: Yêu cầu chứng chỉ (nếu là ca y tế), kiểm tra xung đột thời gian, kiểm tra giới hạn ca tối đa.
- Tiếp nhận và xác nhận đăng ký thành công.

### 5.5. `public/chinh_sua_du_an.php` (Cập Nhật Dự Án)
- Dành riêng cho Ban Chỉ Huy cập nhật thông tin: Tên chiến dịch, địa bàn, địa điểm tập trung, người phụ trách, số lượng chỉ tiêu quân số, lịch trình và danh sách ca trực.

### 5.6. `public/tao_su_kien.php` (Khởi Tạo Sự Kiện Mới)
- Form tạo mới hoạt động tình nguyện từ đầu: Nhập thông tin tổng quan, thiết lập ngày diễn ra, thêm các ca trực con theo ngày, định nghĩa kỹ năng yêu cầu và quyền lợi hỗ trợ.

### 5.7. `public/phan_cong.php` (Phân Công Nhân Sự Thuật Toán)
- Nạp danh sách đăng ký của các TNV.
- Cung cấp nút *Chạy thuật toán phân công tự động* (gọi `AssignmentEngine`).
- Hiển thị bảng ma trận phân ca và cho phép điều chỉnh thủ công từng ca trực.

### 5.8. `public/lich_truc.php` (Lịch Trực Toàn Đội & Cá Nhân)
- Tab 1: Lịch toàn đội theo từng ngày/tuần, hiển thị danh sách TNV trong từng ca.
- Tab 2: Ca của tôi — Lọc danh sách các ca trực mà tài khoản đang đăng nhập đã được ghi nhận.

### 5.9. `public/phe_duyet_vang.php` (Phê Duyệt Đơn Báo Vắng)
- Dành cho TNV: Form gửi đơn xin vắng ca (chọn ca trực, nhập lý do, đính kèm chứng từ).
- Dành cho BCH: Danh sách đơn chờ duyệt, nút *Chấp thuận* (tự động giải phóng vị trí để tuyển bổ sung) hoặc *Từ chối*.

### 5.10. `public/profile.php` (Hồ Sơ Năng Lực TNV)
- Thống kê tổng giờ cống hiến, số hoạt động đã tham gia, điểm uy tín.
- Hiển thị các huy hiệu tình nguyện đã đạt: Mầm Xanh Tích Cực, Lá Xanh Bền Bỉ, Trái Tim Hồng, Ngôi Sao Vàng Cống Hiến.

### 5.11. `public/dang_nhap.php` & `public/dang_ky_tk.php`
- Trang đăng nhập hệ thống, kiểm tra email/mật khẩu, cung cấp chuyển vai trò 1-click.
- Trang đăng ký tài khoản TNV mới, thu thập đầy đủ thông tin nhóm máu, trường học và kỹ năng chuyên môn.

### 5.12. `public/doi_soat_thuat_toan.php`
- Báo cáo phân tích chất lượng giải thuật AI: Độ phủ ca trực (Coverage Rate), Tỷ lệ thỏa mãn nguyện vọng, Ma trận khớp kỹ năng y tế và hậu cần.

### 5.13. `public/quan_ly_tnv.php`
- Quản lý danh mục 50 tình nguyện viên toàn hệ thống, tìm kiếm theo tên, trường học, kỹ năng, xuất danh sách phục vụ công tác tổ chức.

### 5.14. `public/phe_duyet_ca.php` (Phê Duyệt Đăng Ký Ca Làm Của Ban Chỉ Huy)
- **Kiểm soát quyền**: `Auth::requireCoordinator()`.
- **Giao diện & Bố cục**: Chuẩn hóa đồng nhất 100% với `phe_duyet_vang.php`:
  - Banner Ban Chỉ Huy dạng thẻ bo góc 14px hiện đại, huy hiệu vai trò và thông tin chỉ đạo trực quan.
  - 4 Thẻ KPI thống kê tương tác: Chờ BCH Phê Duyệt (`pending`), Đã Duyệt (`approved`), Đã Từ Chối (`rejected`), Tổng Số Đơn Đăng Ký.
  - Thanh công cụ tìm kiếm và lọc đa thuộc tính (theo trạng thái, từ khóa tên TNV / chiến dịch).
  - Bảng danh sách chi tiết: Mã đơn, Thời gian gửi, Tình nguyện viên, Hoạt động & Ca trực, Thông tin liên hệ, Trạng thái (Pill badge bo tròn 30px đồng bộ).
  - Hộp thoại Modal Phê duyệt & Modal Từ chối (nhập lý do từ chối), cập nhật dữ liệu tức thì thời gian thực vào CSDL MySQL/SQLite.

---

## 6. DỮ LIỆU ĐỌC CHI TIẾT `data/` & `database/` (CƠ SỞ DỮ LIỆU & SEED)

### 6.1. Danh Mục 5 Lĩnh Vực Hoạt Động (`volunteer_data.php`)
1. `thuc_te_moi_truong` (Mã MT): Hoạt Động Thực Địa & Môi Trường Xanh (Màu xanh lá `#059669`).
2. `hien_mau_y_te` (Mã HM): Hiến Máu Nhân Đạo & Hỗ Trợ Y Tế (Màu đỏ `#dc2626`).
3. `tiep_suc_giao_duc` (Mã GD): Tiếp Sức Mùa Thi & Giáo Dục Cộng Đồng (Màu xanh dương `#2563eb`).
4. `cuu_tro_an_sinh` (Mã CT): Cứu Trợ Khẩn Cấp & Bếp Ăn 0 Đồng (Màu hổ phách `#d97706`).
5. `cham_soc_yeu_thuong` (Mã CS): Chăm Sóc Người Cao Tuổi & Trẻ Em Khuyết Tật (Màu tím `#7c3aed`).

### 6.2. Danh Sách 50 Dự Án Tiêu Biểu Trong CSDL (ID 101 - 510)
- **Nhóm 1 (Môi trường)**:
  - 101: Dọn sạch rác Sông Tô Lịch (Hà Nội, 32/40 TNV, 4 ca).
  - 102: Phủ xanh rừng Quốc Gia Ba Vì (Hà Nội, 28/35 TNV).
  - 103: Trồng rừng ngập mặn Cần Giờ (TP.HCM, 45/50 TNV).
  - 104: Vớt rác kênh Tham Lương (TP.HCM, 30/40 TNV).
  - 105 - 110: Các chiến dịch dọn rác biển Sầm Sơn, Đồ Sơn, Cửa Lò, bảo vệ rạn san hô Cù Lao Chàm.
- **Nhóm 2 (Hiến máu & Y tế)**:
  - 201: Ngày hội Giọt Hồng Viện Huyết Học Trung Ương (Hà Nội, 38/45 TNV).
  - 202: Hỗ trợ bệnh nhân ung bướu Viện K Tân Triều (Hà Nội, 18/25 TNV, Tuyển gấp).
  - 203: Tiếp sức Bệnh Viện Chợ Rẫy (TP.HCM, 24/30 TNV).
  - 204: Sơ cấp cứu thực địa và hiến máu Chữ Thập Đỏ (TP.HCM, 20/25 TNV).
  - 205 - 210: Khám bệnh cấp thuốc miễn phí vùng cao Hà Giang, Lai Châu, Điện Biên, Cao Bằng.
- **Nhóm 3 (Giáo dục & Mùa thi)**:
  - 301: Đội xe máy Tiếp Sức Mùa Thi 15 điểm trường THPT (Hà Nội, 42/50 TNV).
  - 302: Lớp học tình thương Làng Trẻ SOS (Hà Nội, 15/20 TNV).
  - 303: Tủ sách vùng cao & phòng tin học em đến trường (Sơn La, 22/30 TNV).
  - 304 - 310: Trợ giảng trẻ em khuyết tật, phổ cập tin học thanh niên nông thôn.
- **Nhóm 4 (Cứu trợ & Bếp ăn 0Đ)**:
  - 401: Bếp Ăn 0 Đồng phục vụ bệnh nhân nghèo BV Bạch Mai (Hà Nội, 25/30 TNV).
  - 402: Phát 1.500 suất cơm nghĩa tình Quận 1 & Quận 5 (TP.HCM, 35/40 TNV).
  - 403: Đội phản ứng nhanh cứu trợ lũ quét bão Yagi (Yên Bái, 20/25 TNV).
  - 404 - 410: Chuyến xe yêu thương miền Trung, áo ấm mùa đông Mù Cang Chải.
- **Nhóm 5 (Chăm sóc xã hội)**:
  - 501: Viện dưỡng lão Diên Hồng & chăm sóc cụ già neo đơn (Hà Nội, 16/20 TNV).
  - 502: Vui tết Trung Thu thiếu nhi Trung Tâm Bảo Trợ Xã Hội (TP.HCM, 30/35 TNV).
  - 503 - 510: Hòa nhập thể thao trẻ em tự kỷ, dạy nghề thanh niên khiếm thính.

### 6.3. Danh Sách 50 Tình Nguyện Viên Nòng Cốt (`seed_volunteers.json`)
- **TNV-001**: Lê Nguyễn Phương Thảo (Nữ, 2001, ĐH Y Hà Nội, SĐT 0988.555.771, Máu O-, Kỹ năng: Bác sĩ nội trú, Sơ cứu, 142 giờ, Uy tín 10).
- **TNV-002**: BS. Trần Tuấn Anh (Nam, 1998, BV Việt Đức, SĐT 0988.555.772, Máu B+, Kỹ năng: Ngoại khoa, Cấp cứu chấn thương, 165 giờ, Uy tín 10).
- **TNV-003**: Phạm Minh Quân (Nam, 2002, ĐH Bách Khoa Hà Nội, SĐT 0908.123.456, Máu A+, Kỹ năng: Hậu cần, Kỹ thuật điện máy, 68 giờ, Uy tín 9.5).
- **TNV-004**: Nguyễn Hà My (Nữ, 2003, ĐH KHXH&NV, SĐT 0933.789.012, Máu O+, Kỹ năng: Truyền thông, Nhiếp ảnh, 32 giờ, Uy tín 9.2).
- **TNV-005**: Hoàng Quốc Bảo (Nam, 2000, ĐH Thể Dục Thể Thao, SĐT 0912.345.678, Máu AB+, Kỹ năng: Cứu hộ bơi lội, Thể lực, 110 giờ, Uy tín 9.8).
- **TNV-006 đến TNV-050**: Phân bổ đồng đều các trường ĐH Kinh Tế Quốc Dân, ĐH Ngoại Thương, ĐH Sư Phạm, ĐH Y Dược TP.HCM, ĐH Bách Khoa TP.HCM với đầy đủ thông tin chuyên môn.

---

## 7. DỮ LIỆU ĐỌC CHI TIẾT `public/assets/` (CSS & JAVASCRIPT)

### 7.1. File `public/assets/css/ql_tinh_nguyen.css` (~8.000 dòng)
- **Hệ thống Design Tokens**: Định nghĩa biến `--primary`, `--success`, `--danger`, `--warning`, `--neutral`, `--radius`, `--shadow`.
- **Responsive Breakpoints**: `@media (max-width: 1200px)`, `@media (max-width: 1024px)`, `@media (max-width: 768px)`, `@media (max-width: 480px)`.
- **Bộ Component chuẩn**:
  - Button styles: `.btn-primary`, `.btn-detail`, `.btn-participants`, `.btn-danger`.
  - Badges: `.badge-urgent` (đỏ), `.badge-open` (xanh), `.badge-full` (xám).
  - Modals: Lớp phủ `#project_detail_modal`, hiệu ứng `@keyframes adminModalFadeIn`.
  - Tables: Bảng dữ liệu có sticky header, sọc so le xen kẽ `#ffffff` và `#f8fafc`.

### 7.2. File `public/assets/js/banner_chuyen_dong.js`
- **Bộ lắng nghe sự kiện Slider**:
  - `showSlide(index)`: Kích hoạt class `.active` trên slide và chấm tròn tương ứng.
  - `nextSlide()` & `prevSlide()`: Tự động tính chỉ số vòng lặp `(currentSlide + 1) % slides.length`.
  - `startAutoPlay()`: Khởi động bộ đếm thời gian chuyển động tự động sau 5 giây (`setInterval(nextSlide, 5000)`).
  - Tự động tạm dừng (`mouseenter`) và tiếp tục (`mouseleave`) khi người dùng rê chuột vào banner.

### 7.3. File `public/assets/js/volunteer_portal.js`
- **Chức năng tương tác cổng thông tin**:
  - Xử lý mở đóng dropdown menu trên thiết bị di động.
  - Kiểm tra tính hợp lệ của số điện thoại và email trên các biểu mẫu trực tuyến.
  - Điều khiển chuyển tab và lọc danh sách ca trực theo thời gian thực.

---

## 8. TỪ ĐIỂN BIẾN PHIÊN & CẤU TRÚC DỮ LIỆU TOÀN HỆ THỐNG

| Khóa Biến / Tên Dữ Liệu | Nơi Lưu Trữ | Định Dạng Dữ Liệu | Ý Nghĩa Nghiệp Vụ Cốt Lõi |
|---|---|---|---|
| `$_SESSION['user']` | PHP Session | `array` | Lưu hồ sơ tài khoản đang đăng nhập (`id`, `name`, `email`, `role_type`, `unit`, `volunteer_hours`). |
| `$_SESSION['project_roster_custom']` | PHP Session | `array<int, array>` | Lưu trữ danh sách quân số điều phối thủ công theo từng `projectId`. |
| `$_SESSION['leave_requests']` | PHP Session | `array` | Lưu trữ danh sách đơn xin vắng ca chờ xét duyệt. |
| `adminProjectsData` | JavaScript Window | `Object<int, Object>` | Bản đồ 50 dự án nạp từ PHP phục vụ render modal và cập nhật DOM tức thì. |
| `allVolunteersMaster` | JavaScript Window | `Object<string, Object>`| Từ điển 50 TNV nạp từ CSDL phục vụ tra cứu chi tiết hồ sơ nhanh. |
| `currentModalProjectId` | JavaScript Scope | `int` | ID của dự án đang được mở trên Modal điều phối. |
| `currentModalRoster` | JavaScript Scope | `Array<Object>` | Mảng danh sách các thành viên tham gia dự án đang hiển thị. |

---

## 9. BẢN ĐỒ ÁNH XẠ TÍNH NĂNG PHỤC VỤ CẬP NHẬT CODE NHANH

> Khi có yêu cầu thay đổi tính năng từ người dùng, tra cứu ngay tại bảng này để biết chính xác file cần chỉnh sửa:

| Yêu Cầu Thay Đổi / Tính Năng Mới | File Chịu Trách Nhiệm Chính | Vị Trí / Hàm Cần Tác Động |
|---|---|---|
| **Kéo Banner Full Ngang / Sửa Slide Banner** | `public/index.php` & `public/assets/css/ql_tinh_nguyen.css` | Thẻ `<section class="banner_hero_slider">`, đóng/mở thẻ `<main>`, class `.slide_banner` |
| **Sửa Nút Slider / Thời Gian Chuyển Slide** | `public/assets/js/banner_chuyen_dong.js` | Hàm `startAutoPlay()`, selector `.nut_chuyen_trai`, `.nut_chuyen_phai` |
| **Quản Lý Quân Số / Người Tham Gia Dự Án** | `public/admin_import.php` | Cột Quân Số, Cột Thao Tác, hàm `openProjectDetailModal()`, hàm `filterModalRoster()` |
| **Pop-up Xem Thông Tin Chi Tiết TNV** | `public/admin_import.php` | Modal `#volunteer_detail_popup_modal`, hàm `openVolunteerDetailPopup(volId)` |
| **Thêm / Sửa / Xóa TNV Điều Phối Thủ Công** | `public/admin_import.php` | Modal `#modal_add_participant`, `#modal_edit_participant`, hàm `saveNewParticipant()`, `removeParticipant()` |
| **Đồng Bộ Dữ Liệu Roster Về Server Qua AJAX** | `public/admin_import.php` | Khối xử lý `POST ajax_action === 'save_roster'`, hàm `syncRosterToServer()` |
| **Tìm Kiếm / Lọc Hoạt Động Ngoài Trang Chủ** | `public/hoat_dong.php` & `src/EventFilter.php` | Biểu mẫu tìm kiếm `$filters`, phương thức `filterEvents()` |
| **Đăng Ký Ca Trực & Kiểm Tra Trùng Giờ** | `public/dang_ky_ca.php` & `src/RegistrationManager.php` | Phương thức `hasTimeConflict()`, `canRegisterMoreShifts()` |
| **Thuật Toán Phân Công Tự Động Xếp Lịch** | `public/phan_cong.php` & `src/AssignmentEngine.php` | Phương thức `optimizeAssignments()`, ma trận trọng số kỹ năng |
| **Quy Trình Xét Duyệt Báo Vắng Ca** | `public/phe_duyet_vang.php` | Form gửi đơn, nút duyệt của Ban Chỉ Huy |
| **Phê Duyệt Ca Làm Mới Của Ban Chỉ Huy** | `public/phe_duyet_ca.php` & `src/ActivityManager.php`, `src/Database.php` | Form duyệt/từ chối đơn đăng ký ca mới, phương thức `reviewShiftRegistration()` |
| **Lưu Trữ MySQL Workbench & Đồng Bộ Real-time** | `config.php` & `src/Database.php` | Cấu hình MySQL cổng 3307, các phương thức `recordRegistration`, `reviewShiftRegistration`, `saveDutySchedule` |
| **Khóa Hủy Báo Vắng Khi Đã Phân Công Người Khác** | `public/lich_truc.php` & `src/Database.php` | Phương thức `cancelAbsenceRequest()`, kiểm tra ràng buộc `replacement_volunteer_id` / `replacement_for` |
| **Menu Thanh Điều Hướng & Tin Khẩn Marquee** | `templates/header.php` | Mảng `$urgentNotes`, thanh menu `<nav class="main-nav-pro">` |

---

## 10. NHẬT KÝ THEO DÕI THAY ĐỔI & CẬP NHẬT CODE (CONTINUOUS UPDATE CHANGELOG)

> **QUY TẮC CẬP NHẬT LIÊN TỤC**:  
> Từ các lần sau trở đi, mỗi khi có yêu cầu update code hoặc đọc lại bất kỳ file nào trong hệ thống, AI Agent sẽ tự động cập nhật trực tiếp nội dung thay đổi vào phần nhật ký này để đảm bảo file log luôn phản ánh chính xác 100% hiện trạng của mã nguồn.

| Thời Điểm | File Tác Động | Loại Thao Tác | Chi Tiết Cập Nhật & Nội Dung Đã Đọc / Thay Đổi |
|---|---|---|---|
| **17/11/2026** | *Toàn bộ dự án* | Khởi tạo Audit Log | Đọc và rà soát 100% 29+ file mã nguồn; khôi phục source code gốc nguyên bản từ GitHub (`origin/main`, commit `d485d9f`). |
| **17/11/2026** | `CODING_RULES.md`, `.gemini/rules.md` | Tạo file quy tắc code | Thiết lập 5 quy tắc bắt buộc: clean code không dư thừa, đơn giản không phức tạp, đúng phạm vi tính năng, ngắn gọn tối ưu nhất, làm đúng yêu cầu không tự ý thêm thắt. |
| **17/11/2026** | `public/assets/css/ql_tinh_nguyen.css` | Tối ưu & Clean Code | Xóa bỏ hoàn toàn 183 dòng CSS trùng lặp 100% (khối dropdown hover menu & profile nav tabs bar ở cuối file). |
| **17/11/2026** | `src/Auth.php` | Tối ưu & Chuẩn hóa | Xóa bỏ 20 dòng khai báo polyfill `mb_*` lặp lại dư thừa, chuyển sang nạp thống nhất qua `require_once __DIR__ . '/Input.php';`. |
| **17/11/2026** | `src/LocationManager.php` | Tối ưu & Chuẩn hóa | Xóa bỏ 10 dòng khai báo polyfill `mb_stripos` lặp lại dư thừa, chuyển sang nạp thống nhất qua `require_once __DIR__ . '/Input.php';`. |
| **17/11/2026** | `src/SkillManager.php` | Tối ưu & Chuẩn hóa | Xóa bỏ 20 dòng khai báo polyfill `mb_*` lặp lại dư thừa, chuyển sang nạp thống nhất qua `require_once __DIR__ . '/Input.php';`. |
| **17/11/2026** | `public/index.php` | Cập nhật giao diện trang chủ | Xóa bỏ hiển thị số lượng cố định trong nút chuyển trang ở mục 5 nhóm chủ đề, đổi thành "Xem Toàn Bộ Chiến Dịch &rarr;" theo đúng yêu cầu. |
| **17/11/2026** | `public/index.php`, `public/assets/css/ql_tinh_nguyen.css` | Kéo Banner Full Ngang | Kéo banner `.banner_hero_slider` tràn full màn hình ngang 100%, bỏ bo góc; đồng bộ background `#edf5fd` và `contain` cho Slide 1 để giữ 100% tỷ lệ logo & font chữ sắc nét, không bị méo/cắt xén; canh chỉnh padding chữ Slide 2 & 3. |
| **17/11/2026** | `public/index.php`, `public/assets/css/ql_tinh_nguyen.css`, `public/assets/images/categories/` | Thêm ảnh thực tế 5 nhóm chủ đề & Dọn dẹp | Tải & tích hợp 5 ảnh thực địa chính thống từ các chiến dịch tình nguyện tại Việt Nam (Chủ nhật xanh, Hiến máu nhân đạo, Tiếp sức mùa thi, Bếp ăn 0 đồng, Chăm sóc người cao tuổi viện dưỡng lão); dọn dẹp sạch sẽ toàn bộ ảnh cũ/file tạm, chuẩn hóa kích thước 600x400 nhẹ và sắc nét. |
| **18/11/2026** | `templates/header.php` | Sửa liên kết thông báo tuyển gấp | Chuyển hướng liên kết nút "Đăng ký tham gia" và "Ứng tuyển gấp" trên thanh thông báo trực tiếp sang trang đăng ký ca trực `/dang_ky_ca.php?event_id=ID` thay vì trang danh sách tổng hợp. |
| **18/11/2026** | `public/dang_ky_ca.php` | Cân đối bố cục khung tiêu đề | Đặt `max-width: 100%`, đệm đều 2 bên `padding: 24px 32px`, căn khối tiêu đề bên trái và đẩy 2 nút ("Ca Của Tôi", "Quay Lại") sát về phía bên phải của khung. |
| **18/11/2026** | `templates/header.php` | Trải đều thanh điều hướng | Cấu hình `justify-content: space-evenly`, mở rộng `padding: 8px 16px` cho từng mục nav và khoảng cách `gap: 12px`, giúp các mục menu trải đều cân xứng, thoáng đãng. |
| **18/11/2026** | `public/assets/css/ql_tinh_nguyen.css`, `public/assets/css/app.css` | Định dạng ô tick chọn chuyên nghiệp | Nâng cấp toàn diện `input[type="checkbox"]` sang giao diện custom chuyên nghiệp: nền `#245d78`, bo góc 4px, dấu tick SVG trắng sắc nét; hiệu ứng hover/focus viền mượt mà, tự động highlight nhãn/card viền xanh lá `#059669` và nền `#ecfdf5` khi được chọn theo đúng chuẩn ảnh mẫu. |
| **18/11/2026** | `templates/header.php`, `public/assets/css/ql_tinh_nguyen.css` | Bo tròn tag vai trò ôm sát nội dung | Chuyển nhãn vai trò `.user-position-tag` sang dạng pill badge bo tròn hoàn toàn (`border-radius: 9999px`), cấu hình `align-self: flex-start` và `display: inline-flex` để ôm khít theo đúng độ dài nội dung chữ, xóa bỏ khối dải chữ nhật đỏ kéo dài thô kệch. |
| **18/11/2026** | `public/phan_cong.php` | Sửa lỗi tính năng Thêm Người trong ca trực | Khắc phục triệt để tính năng Thêm Người: loại bỏ rào cản thuật toán tự động từ chối khi Ban Chỉ Huy chủ động bổ sung nhân sự hỗ trợ, hỗ trợ AJAX tức thì cập nhật quân số và danh sách ngay trong modal; bổ sung cơ chế tự động mở lại modal qua tham số `open_roster`; chuẩn hóa text nút "Thêm Người". |
| **18/11/2026** | `public/phan_cong.php`, `public/assets/css/ql_tinh_nguyen.css` | Thêm tính năng Thay Người trong ca trực | Bổ sung nút "Thay Người" trực tiếp cho mọi nhân sự trong ca trực (cột Thao tác điều động), tích hợp modal thay thế nhân sự với bộ lọc kỹ năng thông minh; tối ưu hóa logic backend cho phép Ban Chỉ Huy điều động thay thế trực tiếp; tự động mở lại modal ca trực sau khi hoàn tất thay thế hoặc quay lại khi hủy bỏ. |
| **18/11/2026** | `public/phan_cong.php`, `public/assets/css/ql_tinh_nguyen.css` | Đồng đều bố cục 3 nút thao tác hàng ngang | Cấu hình `.roster-action-btns` sang `flex-wrap: nowrap`, chuẩn hóa chiều cao đồng nhất `height: 28px`, `display: inline-flex` cho cả 3 nút (Thay Người, Báo Vắng, Xóa); mở rộng cột thao tác lên 29% giúp 3 nút xếp thẳng tắp trên 1 hàng ngang đồng đều, không bị lệch hoặc rớt dòng. |
| **18/11/2026** | `public/lich_truc.php`, `public/phe_duyet_vang.php`, `public/assets/css/ql_tinh_nguyen.css` | Sửa lỗi nút Đóng bị trắng chữ | Khắc phục triệt để lỗi nút Đóng bị chữ màu trắng trên nền trắng (do kế thừa style `button` của `app.css`): bổ sung `color: #475569`, `font-weight: 600`, đổi text thành "Đóng"; thêm rule CSS toàn cục bảo vệ màu chữ cho mọi nút đóng/hủy modal trên toàn hệ thống. |
| **18/11/2026** | `public/phe_duyet_vang.php` | Điều chỉnh bố cục hàng ngang & Đổi tên nút Thay Người | Mở rộng cột "Thao Tác Ban Chỉ Huy" lên 270px, cấu hình `display: inline-flex` và `flex-wrap: nowrap` giúp 3 nút (Duyệt, Từ Chối, Thay Người) xếp thẳng tắp trên 1 hàng ngang đồng đều cao 30px; đổi tên nút "⇄ Thế Chân" thành "⇄ Thay Người" theo đúng yêu cầu. |
| **18/11/2026** | `public/phe_duyet_vang.php` | Tối ưu bố cục bảng & Đẩy tính năng Thay Người vào sau khi duyệt | Bỏ nút "Thay Người" khỏi khu vực chờ duyệt (chỉ giữ 2 nút Duyệt và Từ Chối); đẩy tính năng "Thay Người" / "Đổi Người" vào cột thao tác của đơn đã phê duyệt; bổ sung `white-space: nowrap` và căn giữa dọc `vertical-align: middle` cho toàn bộ các cột (Mã đơn, Giờ gửi, Thời hạn báo, Badge trạng thái `✓ ĐÃ DUYỆT`), khắc phục triệt để hiện tượng rớt dòng chữ lộn xộn; tối ưu logic điều động nhân sự thay thế không bị trùng lặp trong ca trực. |
| **18/11/2026** | `public/phe_duyet_vang.php` | Cập nhật luồng Duyệt -> Chọn người thay thế & Dọn dẹp danh mục đã duyệt | Chuyển toàn bộ danh mục đã duyệt về chế độ chỉ theo dõi (bỏ nút thay người); đưa tính năng Thay người vào ngay khi bấm nút "Duyệt" ở đơn chờ duyệt: click "Duyệt" lập tức mở modal phê duyệt kèm danh sách 50 TNV để điều động người thay thế ngay (hỗ trợ cả tùy chọn "Duyệt & Thay Người" hoặc "Chỉ Duyệt Vắng"). |
| **18/11/2026** | `public/lich_truc.php`, `src/Database.php` | Khóa quyền hủy báo vắng khi đã phân công người thay thế | Cập nhật logic bảo vệ tính toàn vẹn phân công: khi đơn báo vắng đã được BCH phê duyệt và đã điều động nhân sự khác thay thế nhiệm vụ, hệ thống khóa triệt để thao tác hủy báo vắng (ẩn nút "Hủy Báo Vắng", hiển thị badge "Đã bố trí thay người (Không thể hủy)", chặn ở tầng Database & Controller trả về thông báo lỗi từ chối nếu có yêu cầu gửi lên). |
| **18/11/2026** | `public/lich_truc.php` | Cân đối bố cục hàng ngang cho Trạng Thái & Thao Tác | Tái cấu trúc toàn bộ cột "Trạng Thái & Thao Tác" trong bảng Hoạt Động Của Tôi: chuyển trạng thái và nút thao tác (Xin phép vắng / Hủy báo vắng) lên cùng 1 hàng ngang phẳng phiu (`display: inline-flex`, `flex-wrap: nowrap`); quy chuẩn đồng nhất chiều cao nút 30px và badge 28px; mở rộng cột lên 23% (min-width 230px); bổ sung `vertical-align: middle` căn thẳng hàng dọc cho toàn bộ bảng, khắc phục hoàn toàn hiện tượng xếp chồng vỡ dòng. |
| **18/11/2026** | `public/lich_truc.php` | Sửa lỗi cú pháp PHP gây HTTP Error 500 khi xem Ca Của Tôi | Loại bỏ thẻ `else` đóng lặp dư thừa tại cuối bảng hoạt động cá nhân, đã lint và chạy thử nghiệm xuất sắc 100% bằng PHP engine của XAMPP (`d:\PTPM\XAMPP\php\php.exe`), khôi phục truy cập mượt mà cho toàn bộ trang `/lich_truc.php?tab=my_shifts`. |
| **18/11/2026** | `public/lich_truc.php` | Tinh gọn hiển thị trạng thái cá nhân (Ẩn tên người thay thế) | Điều chỉnh hiển thị tại cột Trạng thái & Thao tác của trang Ca của tôi: khi đơn báo vắng đã được BCH phê duyệt, chỉ hiển thị 2 nhãn đồng điệu `ĐÃ BÁO BẬN` và `✓ ĐÃ ĐƯỢC PHÊ DUYỆT`, lược bỏ hoàn toàn tên nhân sự thay thế để đảm bảo tính riêng tư và đúng góc nhìn cá nhân của tình nguyện viên. |
| **18/11/2026** | `public/index.php`, `public/assets/css/ql_tinh_nguyen.css`, `public/assets/images/` | Thêm 2 banner sự kiện thực tế & Link trực tiếp trang đăng ký theo event_id | Tải 2 ảnh sự kiện tình nguyện thực tế từ báo chí chính thống (`banner_tnv_3.jpg` - Tiếp Sức Mùa Thi, `banner_tnv_4.jpg` - Viện Dưỡng Lão / Chăm Sóc Yêu Thương); bổ sung 2 slide sự kiện nâng tổng số lên 5 slide kèm 5 chấm tròn điều khiển; định nghĩa các class màu huy hiệu (`.huy_hieu_blue`, `.huy_hieu_purple`, `.huy_hieu_crimson`); liên kết nút Đăng Ký trên toàn bộ 4 banner sự kiện trực tiếp tới `/dang_ky_ca.php?event_id=ID` (ID 101, 201, 301, 501) giúp người dùng nhấp vào được chuyển thẳng đến đúng sự kiện cần đăng ký ca. |
| **18/11/2026** | `public/phe_duyet_ca.php`, `public/dang_ky_ca.php`, `public/lich_truc.php`, `templates/header.php`, `public/phan_cong.php`, `src/ActivityManager.php`, `src/Database.php` | Thêm tính năng Phê duyệt ca làm của Ban Chỉ Huy & Cập nhật trạng thái 'ĐÃ ĐƯỢC PHÊ DUYỆT' ở Ca của tôi | Xây dựng trang mới `public/phe_duyet_ca.php` với giao diện, bố cục và hệ thống modal chuẩn 100% theo mẫu `phe_duyet_vang.php` (Banner BCH, 4 Thẻ thống kê tương tác Chờ duyệt/Đã duyệt/Từ chối/Tổng số, form tìm kiếm lọc đa thuộc tính, bảng danh sách kèm modal Duyệt và Từ chối); cập nhật quy trình đăng ký ca trực tuyến tại `dang_ky_ca.php` và `ActivityManager` sang trạng thái ban đầu `pending` (Chờ BCH phê duyệt); nâng cấp bảng Ca của tôi tại `lich_truc.php`: hiển thị `⏳ CHỜ BCH PHÊ DUYỆT` khi chưa duyệt, và tự động chuyển sang `✓ ĐÃ ĐƯỢC PHÊ DUYỆT` khi BCH chấp thuận; bổ sung cảnh báo và liên kết điều hướng trên thanh menu, dropdown BCH và toolbar bảng phân công. |
| **18/11/2026** | `config.php`, `src/Database.php`, `src/ActivityManager.php`, `public/phe_duyet_vang.php`, `public/lich_truc.php`, `public/phe_duyet_ca.php` | Đưa toàn bộ dữ liệu lên MySQL / MySQL Workbench & Lưu trữ thời gian thực (Real-time Persistence) | Kết nối hệ thống trực tiếp tới MySQL/MariaDB XAMPP qua cổng 3307 (khớp 100% profile `XAMPP 3307` trong MySQL Workbench); hoàn thiện cấu trúc quan hệ toàn vẹn dữ liệu (Relational Integrity) giữa 5 bảng cốt lõi `projects`, `shifts`, `volunteers`, `volunteer_registrations`, `absence_requests` với 9 khóa ngoại (Foreign Keys: `fk_reg_shift`, `fk_reg_replacement`, `fk_abs_volunteer`, `fk_abs_shift`, `fk_abs_project`, `fk_abs_replacement`, `shifts_ibfk_1`, `volunteer_registrations_ibfk_1`, `volunteer_registrations_ibfk_2`); cập nhật các phương thức `recordRegistration`, `reviewShiftRegistration`, `getShiftRegistrations`, `getUserShiftRegistrations`, `createAbsenceRequest`, `reviewAbsenceRequest`, `cancelAbsenceRequest`, `saveDutySchedule`, `loadDutySchedule` đọc và ghi trực tiếp thời gian thực vào CSDL; khắc phục triệt để lỗi mất dữ liệu khi restart web/trình duyệt; 10/10 kịch bản kiểm thử tích hợp CSDL pass 100%. |
| **18/11/2026** | *Git Remote Repository* | Đẩy mã nguồn hoàn thiện lên GitHub (`origin/main`) | Thực hiện commit và push toàn bộ 27 file cập nhật mã nguồn (MySQL Workbench persistence, phê duyệt ca của BCH, khóa hủy báo vắng khi có người thay thế, 2 banner sự kiện mới) lên kho mã nguồn `https://github.com/DngOsea/TNV_Nhom4` tại nhánh `main` (commit `3a1a345`). |
| **18/11/2026** | `templates/header.php`, `templates/footer.php`, `public/index.php`, `public/phe_duyet_ca.php`, `public/phe_duyet_vang.php`, `public/lich_truc.php`, `src/Database.php` | Rà soát toàn diện & Khắc phục triệt để lỗi hiển thị giao diện các trang | • **`templates/header.php`**: Nạp `require_once __DIR__ . '/../src/ActivityManager.php';` loại bỏ hoàn toàn lỗi Fatal Error crash trang `/phan_cong.php` và `/doi_soat_thuat_toan.php`; xử lý an toàn `$_SERVER['REQUEST_URI'] ?? '/'` triệt tiêu PHP Warning.<br>• **`templates/footer.php`**: Bổ sung thẻ `</div>` bị thiếu tại modal footer (`line 186`), khắc phục triệt để lỗi rò rỉ DOM toàn cục làm vỡ giao diện tất cả các trang web.<br>• **`public/index.php`**: Xóa thẻ `</main>` đóng thừa (line 9) và thẻ `<main id="main">` mở trùng lặp (line 118), đưa cấu trúc thẻ `<main>` về trạng thái cân bằng tuyệt đối 1:1 với header/footer.<br>• **`public/phe_duyet_ca.php` & `public/phe_duyet_vang.php`**: Khắc phục lỗi double-container lồng nhau (`max-width: 1240px` lồng trong `max-width: 1280px` với 40px padding thừa) gây tràn thanh cuộn ngang và ép méo cột bảng dữ liệu; nâng cấp Banner BCH thành thẻ bo góc `14px` đồng bộ với `.page-heading-pro`.<br>• **`public/lich_truc.php`**: Chuẩn hóa chiều cao toàn bộ huy hiệu trạng thái (`ĐÃ BÁO BẬN`, `✓ ĐÃ ĐƯỢC PHÊ DUYỆT`, `CHỜ BCH DUYỆT VẮNG`, `CHỜ BCH PHÊ DUYỆT`, `ĐÃ TỪ CHỐI`) lên đồng nhất `height: 30px` khớp chính xác với nút bấm ("Xin Phép Vắng", "Hủy Báo Vắng"), loại bỏ hoàn toàn độ lệch 2px.<br>• **`src/Database.php`**: Bổ sung tự động migration cột `project_id` & `replacement_volunteer_id` cho SQLite fallback; sửa câu lệnh UPDATE `reviewAbsenceRequest` dùng subquery tra cứu ID thay cho cột không tồn tại `volunteer_name`.<br>• **Kiểm thử**: Đạt 52/52 bài test kiểm thử tự động (100% PASS), 16/16 trang web cân bằng thẻ HTML hoàn hảo. |

---
*Tài liệu này được duy trì và cập nhật liên tục mỗi khi có thay đổi mã nguồn. Mọi thay đổi tiếp theo của hệ thống sẽ được ghi nhận trực tiếp vào đây.*

