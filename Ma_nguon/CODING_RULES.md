# BỘ QUY TẮC PHÁT TRIỂN & CHUẨN MỰC CODE (CODING RULES & CONVENTIONS)
> **Dự án**: Hệ Thống Quản Lý Tình Nguyện Viên & Hoạt Động Xã Hội — Đội Tình Nguyện D2L  
> **Áp dụng cho**: Toàn bộ quá trình lập trình, bảo trì, tối ưu hóa và phát triển tính năng mới.  
> **Hiệu lực**: Bắt buộc tuân thủ 100% đối với mọi dòng code được thêm vào hoặc chỉnh sửa trong dự án.

---

## 1. MỖI DÒNG CODE THÊM VÀO PHẢI CLEAN, KHÔNG DƯ THỪA
- **Mục đích rõ ràng**: Mỗi dòng code (PHP, JavaScript, HTML, CSS, SQL) được viết ra phải phục vụ một mục đích cụ thể, giải quyết trực tiếp bài toán được giao.
- **Không để lại rác**: Tuyệt đối không để lại code thử nghiệm, biến rác không sử dụng, hàm vô danh không được gọi, hay các đoạn mã chú thích (commented-out code) không còn tác dụng.
- **Định dạng chuẩn xác**: Tuân thủ thụt lề chuẩn (indentation 4 spaces), đặt tên biến và hàm tường minh theo đúng quy ước hiện tại của dự án.

## 2. KHÔNG TẠO WEB BẰNG CÁC CÂU LỆNH DÀI DÒNG, PHỨC TẠP
- **Nguyên lý KISS (Keep It Simple, Stupid)**: Ưu tiên cách triển khai đơn giản, mạch lạc và trực diện nhất.
- **Tránh lồng ghép quá mức (Deep Nesting)**: Hạn chế tối đa các cấu trúc `if/else` lồng nhau nhiều tầng, vòng lặp phức tạp không cần thiết; ưu tiên kỹ thuật `early return / guard clause`.
- **Tránh viết lại những gì đã có**: Tận dụng tối đa các hàm có sẵn trong `src/` (`Auth`, `Input`, `Pagination`, `Database`, v.v.) thay vì viết lại các đoạn logic xử lý lặp lại.

## 3. KHÔNG TẠO THÊM CODE DƯ THỪA, NGOÀI PHẠM VI TÍNH NĂNG
- **Đúng trọng tâm**: Chỉ chỉnh sửa và thêm mới code trong phạm vi các file liên quan trực tiếp đến tính năng đang được yêu cầu.
- **Không tác động lan man**: Không tự ý sửa đổi hoặc thêm mới các đoạn mã phụ trợ, không tạo thêm các hàm tiện ích nếu không thực sự phục vụ tính năng hiện tại.
- **Không duplicate code**: Nếu một chức năng đã có thể dùng lại hoặc gộp chung được, tuyệt đối không sao chép (copy-paste) thành nhiều đoạn code trùng lặp ở các trang khác nhau.

## 4. SỬ DỤNG NHỮNG LỆNH CODE NGẮN GỌN NHẤT, TỐI ƯU NHẤT
- **Khai thác tối đa tính năng hiện đại**:
  - **PHP 8.x**: Sử dụng cú pháp ngắn gọn như null coalescing (`??`), ternary operator (`? :`), arrow functions, match expressions khi phù hợp.
  - **JavaScript**: Dùng các phương thức mảng tối ưu (`find`, `filter`, `map`, `some`, `forEach`), template literals, destructuring thay vì các vòng lặp thủ công cồng kềnh.
  - **CSS**: Tận dụng Design Tokens, Flexbox, Grid có sẵn trong `ql_tinh_nguyen.css`, không viết thêm các đoạn CSS dài dòng trùng lặp.
- **Hiệu năng & Tài nguyên**: Tối ưu số lượng truy vấn, tận dụng Session và DOM caching để đạt tốc độ xử lý nhanh nhất với dung lượng code nhỏ nhất.

## 5. LÀM ĐÚNG YÊU CẦU ĐƯỢC ĐƯA RA, KHÔNG THÊM THẮT KHI CHƯA ĐƯỢC YÊU CẦU
- **Tuyệt đối bám sát chỉ thị**: Chỉ thực hiện chính xác và đầy đủ các yêu cầu mà người dùng đã nêu trong prompt.
- **Không tự ý sáng tạo ngoài lề**: Không tự thêm các nút bấm, giao diện, bảng dữ liệu, trường thông tin, hay hiệu ứng chuyển động nếu người dùng chưa yêu cầu.
- **Giữ nguyên trạng thái gốc**: Những phần code, chức năng không liên quan đến yêu cầu hiện tại phải được giữ nguyên vẹn 100%, không bị xáo trộn hay chỉnh sửa ngoài ý muốn.

---
*Bản quy tắc này là tiêu chuẩn bất biến cho mọi thao tác cập nhật mã nguồn trong dự án.*

