-- =====================================================================
-- HỆ THỐNG CƠ SỞ DỮ LIỆU ĐIỀU PHỐI TÌNH NGUYỆN VIÊN (D2L PLATFORM)
-- Tương thích: SQLite 3.x, MySQL 5.7+, MariaDB 10.x, PostgreSQL
-- =====================================================================

-- 1. BẢNG DỰ ÁN / HOẠT ĐỘNG TÌNH NGUYỆN (PROJECTS / ACTIVITIES)
CREATE TABLE IF NOT EXISTS projects (
    id INTEGER PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(50) NOT NULL,
    category_name VARCHAR(100) NOT NULL,
    date_string VARCHAR(150) NOT NULL,
    time_range VARCHAR(100) NOT NULL,
    location VARCHAR(255) NOT NULL,
    meeting_point VARCHAR(255) DEFAULT '',
    capacity INTEGER NOT NULL DEFAULT 20,
    registered INTEGER NOT NULL DEFAULT 0,
    leader_name VARCHAR(100) NOT NULL,
    leader_phone VARCHAR(30) NOT NULL,
    skills_needed TEXT,       -- JSON array định dạng kỹ năng yêu cầu
    benefits TEXT,            -- JSON array quyền lợi tình nguyện viên
    summary TEXT,             -- Tóm tắt nội dung và mục tiêu hoạt động
    status VARCHAR(30) NOT NULL DEFAULT 'recruiting', -- recruiting, full, in_progress, completed
    is_urgent INTEGER NOT NULL DEFAULT 0,            -- 1: Cần tuyển gấp, 0: Bình thường
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- 2. BẢNG TÌNH NGUYỆN VIÊN (VOLUNTEERS)
CREATE TABLE IF NOT EXISTS volunteers (
    id VARCHAR(20) PRIMARY KEY, -- Ví dụ: TNV-001, TNV-050
    name VARCHAR(100) NOT NULL,
    gender VARCHAR(10) NOT NULL DEFAULT 'Nam', -- Nam, Nữ
    dob VARCHAR(20) DEFAULT '',                -- Ngày sinh YYYY-MM-DD
    phone VARCHAR(30) NOT NULL,
    email VARCHAR(120) NOT NULL,
    organization VARCHAR(150) DEFAULT '',     -- Trường học / Cơ quan / Đơn vị
    blood_group VARCHAR(10) DEFAULT 'O+',      -- O+, A+, B+, AB+, O- (hiếm)
    skills TEXT,                               -- JSON array nhóm kỹ năng chuẩn: ["y_te", "hau_can"]
    custom_skills TEXT,                        -- JSON array kỹ năng tự nhập: ["Lái xe bán tải", "CPR"]
    certified_skills TEXT,                     -- JSON array kỹ năng đã được cấp chứng chỉ/xác nhận
    availability TEXT,                         -- JSON array ca/thời gian rảnh có thể nhận nhiệm vụ
    max_shifts INTEGER NOT NULL DEFAULT 2,     -- Giới hạn số ca tối đa có thể đảm nhiệm
    volunteer_hours INTEGER NOT NULL DEFAULT 0,-- Số giờ tình nguyện đã tích lũy
    reliability_score REAL NOT NULL DEFAULT 10.0, -- Điểm uy tín/chuyên cần (thang 10.0)
    health_note TEXT,                          -- Lưu ý y tế/sức khỏe (dị ứng, bệnh lý, thể lực...)
    role VARCHAR(100) DEFAULT 'Tình nguyện viên', -- Phổ thông, Nòng cốt, Trưởng ban, v.v.
    registered_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- 3. BẢNG CA LÀM VIỆC THEO NGÀY (SHIFTS)
CREATE TABLE IF NOT EXISTS shifts (
    id VARCHAR(50) PRIMARY KEY, -- Mã ca, vd: 101-D1-S1, CA-01
    project_id INTEGER NOT NULL,
    day_code VARCHAR(20) DEFAULT 'day_1',
    day_name VARCHAR(50) DEFAULT 'Thứ Bảy',
    shift_name VARCHAR(100) NOT NULL, -- Ca Sáng, Ca Chiều, Ca Tối, Ca Đêm
    time_range VARCHAR(100) NOT NULL, -- 08:00 – 12:00, 13:00 – 17:00
    capacity INTEGER NOT NULL DEFAULT 15,
    registered INTEGER NOT NULL DEFAULT 0,
    skill_req VARCHAR(255) DEFAULT '',
    skill_group VARCHAR(50) DEFAULT 'hau_can',
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- 4. BẢNG PHÂN CÔNG / ĐĂNG KÝ CA LÀM VIỆC (REGISTRATIONS)
CREATE TABLE IF NOT EXISTS volunteer_registrations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    volunteer_id VARCHAR(20) NOT NULL,
    project_id INTEGER NOT NULL,
    shift_id VARCHAR(50) NOT NULL,
    role_in_shift VARCHAR(100) DEFAULT 'TNV Tham gia',
    status VARCHAR(30) DEFAULT 'confirmed', -- confirmed, pending, absent, cancelled
    registered_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (volunteer_id) REFERENCES volunteers(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- TẠO CHỈ MỤC (INDEXES) TỐI ƯU TRUY VẤN
CREATE INDEX IF NOT EXISTS idx_projects_category ON projects(category);
CREATE INDEX IF NOT EXISTS idx_projects_status ON projects(status);
CREATE INDEX IF NOT EXISTS idx_volunteers_blood ON volunteers(blood_group);
CREATE INDEX IF NOT EXISTS idx_shifts_project ON shifts(project_id);
CREATE INDEX IF NOT EXISTS idx_reg_shift ON volunteer_registrations(shift_id);
CREATE INDEX IF NOT EXISTS idx_reg_volunteer ON volunteer_registrations(volunteer_id);

