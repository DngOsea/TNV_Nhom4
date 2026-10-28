# HƯỚNG DẪN ĐÓNG GÓP MÃ NGUỒN (CONTRIBUTING GUIDE)
## Hệ Thống Quản Lý & Phân Công Hoạt Động Tình Nguyện (D2L Volunteer)

Chào mừng các thành viên tham gia phát triển dự án! Tài liệu này quy định quy chuẩn cộng tác, phân chia nhánh Git, quy tắc viết mã nguồn và quy trình kiểm thử trước khi bàn giao sản phẩm.

---

### 1. Quy Định Phân Chia Nhánh (Branching Strategy)

Dự án áp dụng mô hình Git Flow rút gọn:
* `main`: Nhánh ổn định cao nhất, dùng để tạo bản phát hành (Release) nộp sản phẩm cuối kỳ.
* `develop` / `lab03`: Nhánh tích hợp các tính năng hoàn thiện, chạy kiểm thử tự động.
* `feature/<ten-tinh-nang>`: Nhánh làm việc của từng thành viên cho từng tính năng cụ thể.
  * Ví dụ: `feature/assignment-engine`, `feature/dispatch-dashboard`, `feature/benchmark-report`.
* `fix/<ten-loi>`: Nhánh sửa lỗi phát sinh trong quá trình kiểm thử.

---

### 2. Quy Chuẩn Commit (Commit Message Convention)

Các commit cần ghi rõ mục đích và phạm vi thay đổi bằng tiếng Việt hoặc tiếng Anh chuẩn:
* `feat:` Bổ sung chức năng mới (VD: `feat: Thêm thuật toán Smart Greedy trong AssignmentEngine`).
* `fix:` Sửa lỗi (VD: `fix: Ngăn chặn vi phạm trùng lịch khi thay thế nhân sự`).
* `test:` Bổ sung hoặc cập nhật bộ kiểm thử (VD: `test: Thêm 5 phép thử bắt buộc trong assignment_test.php`).
* `docs:` Cập nhật tài liệu (VD: `docs: Cập nhật hướng dẫn nghiệm thu trong README.md`).
* `style:` Cải thiện giao diện, CSS mà không đổi logic nghiệp vụ.
* `refactor:` Tối ưu hóa cấu trúc mã nguồn.

---

### 3. Quy Chuẩn Lập Trình (Coding Standards)

* **Ngôn ngữ & Phiên bản:** PHP 8.1 trở lên (chế độ `declare(strict_types=1);`).
* **Kiến trúc phân lớp:**
  * Thư mục `src/`: Chứa các lớp xử lý thuật toán, xác thực dữ liệu (`AssignmentEngine.php`, `Input.php`, `EventFilter.php`). Tuyệt đối không nhúng mã HTML/giao diện trong thư mục này.
  * Thư mục `data/`: Lưu trữ mảng dữ liệu mẫu, cấu trúc seed data (`btl_dataset.php`, `volunteer_data.php`).
  * Thư mục `templates/`: Thành phần giao diện dùng chung (`header.php`, `footer.php`).
  * Thư mục `public/`: Điểm tiếp nhận yêu cầu HTTP và trình diễn giao diện.
  * Thư mục `tests/`: Bộ kiểm thử tự động.
* **Bảo mật & An toàn dữ liệu:**
  * Lọc đầu vào bằng các hàm trong `Input.php` và mã hóa đầu ra bằng `e()` hoặc `htmlspecialchars()`.
  * Không bao giờ đưa thông tin mật, thông tin cá nhân thật hoặc thư mục `.local/` lên Git.

---

### 4. Quy Trình Kiểm Thử Trước Khi Hợp Nhất (Testing & Review)

Mọi thay đổi trước khi tạo Pull Request hợp nhất vào nhánh chính đều phải vượt qua 100% các bộ kiểm thử:
```bash
# 1. Kiểm tra cú pháp toàn bộ tệp PHP
find . -name "*.php" -not -path "./.local/*" -exec php -l {} \;

# 2. Chạy bộ kiểm thử cơ bản của Lab
bash lab.sh test

# 3. Chạy bộ kiểm thử mở rộng
bash lab.sh extend

# 4. Chạy bộ kiểm thử 5 phép thử trọng tâm của Đề tài
bash lab.sh btl-test
```

---

### 5. Quy Trình Đóng Gói Sản Phẩm (Release Packaging)

Khi kết thúc học phần và sẵn sàng nộp bài:
1. Đảm bảo nhánh làm việc đã hợp nhất vào `main` và vượt qua toàn bộ kiểm thử.
2. Tạo thẻ phát hành (Tag): `git tag -a v1.0-btl-final -m "Bản phát hành chính thức nộp BTL"`.
3. Đẩy tag lên kho GitHub: `git push origin v1.0-btl-final`.
4. Tạo tệp nén `BTL_NhomXX.zip` (chỉ gồm mã nguồn, tài liệu, loại trừ `.git` và `.local`).
