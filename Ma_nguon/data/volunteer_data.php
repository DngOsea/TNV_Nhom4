<?php
declare(strict_types=1);

/**
 * HỆ THỐNG DỮ LIỆU TÌNH NGUYỆN VIÊN (D2L VOLUNTEER PLATFORM)
 * 5 Nhóm chủ đề thực tế, 8 chiến dịch trọng điểm (3 - 2 - 1 - 1 - 1), Phân công ca trực, Biểu mẫu
 * Định dạng: Chuẩn mực, trang trọng, không sử dụng biểu tượng cảm xúc (sticker)
 */

return array (
  'categories' => 
  array (
    'thuc_te_moi_truong' => 
    array (
      'id' => 'thuc_te_moi_truong',
      'name' => 'Hoạt Động Thực Địa & Môi Trường Xanh',
      'short_name' => 'Thực Địa & Môi Trường',
      'code' => 'MT',
      'color' => '#059669',
      'bg_color' => '#ecfdf5',
      'border_color' => '#a7f3d0',
      'slogan' => 'Hành động xanh vì một Việt Nam không rác thải',
      'description' => 'Các chiến dịch dọn rác kênh rạch, phủ xanh đất trống, bảo vệ rừng ngập mặn và thu gom rác thải công nghệ.',
    ),
    'hien_mau_y_te' => 
    array (
      'id' => 'hien_mau_y_te',
      'name' => 'Hiến Máu Nhân Đạo & Hỗ Trợ Y Tế',
      'short_name' => 'Hiến Máu & Y Tế',
      'code' => 'HM',
      'color' => '#dc2626',
      'bg_color' => '#fef2f2',
      'border_color' => '#fecaca',
      'slogan' => 'Giọt máu cho đi — Trao thêm một cuộc đời ở lại',
      'description' => 'Tổ chức ngày hội hiến máu, tiếp sức người bệnh cơ nhỡ tại bệnh viện và tập huấn kỹ năng sơ cấp cứu ban đầu.',
    ),
    'tiep_suc_giao_duc' => 
    array (
      'id' => 'tiep_suc_giao_duc',
      'name' => 'Tiếp Sức Mùa Thi & Giáo Dục Cộng Đồng',
      'short_name' => 'Mùa Thi & Giáo Dục',
      'code' => 'GD',
      'color' => '#2563eb',
      'bg_color' => '#eff6ff',
      'border_color' => '#bfdbfe',
      'slogan' => 'Nâng bước sĩ tử, trao tri thức thắp sáng ước mơ',
      'description' => 'Đội hình hỗ trợ thí sinh THPT, mở lớp học tình thương cho trẻ em mồ côi và trao tặng máy tính, sách vở vùng xa.',
    ),
    'cuu_tro_an_sinh' => 
    array (
      'id' => 'cuu_tro_an_sinh',
      'name' => 'Cứu Trợ Khẩn Cấp & Bếp Ăn 0 Đồng',
      'short_name' => 'Cứu Trợ & Bếp Ăn 0Đ',
      'code' => 'CT',
      'color' => '#d97706',
      'bg_color' => '#fffbeb',
      'border_color' => '#fde68a',
      'slogan' => 'Bữa cơm nghĩa tình, sẻ chia hơi ấm giữa dòng đời',
      'description' => 'Nấu phát cơm nóng 0 đồng cho người nghèo, tặng quà đêm người vô gia cư và đội cứu trợ thiên tai bão lũ khẩn cấp.',
    ),
    'cham_soc_yeu_thuong' => 
    array (
      'id' => 'cham_soc_yeu_thuong',
      'name' => 'Chăm Sóc Người Cao Tuổi & Trẻ Em Khuyết Tật',
      'short_name' => 'Chăm Sóc Xã Hội',
      'code' => 'CS',
      'color' => '#7c3aed',
      'bg_color' => '#f5f3ff',
      'border_color' => '#ddd6fe',
      'slogan' => 'Trao gửi yêu thương, kết nối những mảnh đời khó khăn',
      'description' => 'Chăm sóc cụ già neo đơn tại viện dưỡng lão, tổ chức vui chơi, hòa nhập thể thao cho các em nhỏ khuyết tật.',
    ),
  ),
  'activities' => 
  array (
    0 => 
    array (
      'id' => 101,
      'title' => 'Chủ Nhật Xanh Hà Nội 2026 — Dọn Sạch Rác Tuyến Sông Tô Lịch & Cải Tạo Mảng Xanh Ven Bờ',
      'category' => 'thuc_te_moi_truong',
      'category_name' => 'Hoạt Động Thực Địa & Môi Trường Xanh',
      'date_string' => 'Chủ Nhật hàng tuần (Giai đoạn 1: 25/10/2026)',
      'time_range' => '07:00 – 11:30',
      'location' => 'Dọc tuyến Sông Tô Lịch, Quận Cầu Giấy & Đống Đa, Hà Nội',
      'meeting_point' => 'Cầu Cống Mọc, Đường Giáp Nhất, Quận Thanh Xuân, Hà Nội',
      'capacity' => 40,
      'registered' => 32,
      'leader_name' => 'Phạm Minh Quân',
      'leader_phone' => '0908.123.456',
      'shifts' => 
      array (
        0 => 'Ca Sáng (07:00 – 11:30)',
      ),
      'skills_needed' => 
      array (
        0 => 'Thể lực tốt',
        1 => 'Hậu cần khuân vác',
        2 => 'Biết bơi lội',
        3 => 'Ý thức kỷ luật',
      ),
      'benefits' => 
      array (
        0 => 'Áo TNV Môi Trường',
        1 => 'Nước uống và bữa phụ trưa',
        2 => 'Cấp Giấy chứng nhận 8 giờ tình nguyện',
        3 => 'Cộng điểm rèn luyện',
      ),
      'summary' => 'Ra quân dọn sạch rác thải trôi nổi dọc tuyến sông Tô Lịch, bóc xóa quảng cáo rác ven đường và trồng hàng cây xanh tạo cảnh quan.',
      'status' => 'recruiting',
    ),
    1 => 
    array (
      'id' => 102,
      'title' => 'Hành Trình Xanh Tây Bắc — Ươm Mầm & Trồng 10.000 Cây Rừng Phòng Hộ Mù Cang Chải',
      'category' => 'thuc_te_moi_truong',
      'category_name' => 'Hoạt Động Thực Địa & Môi Trường Xanh',
      'date_string' => 'Thứ Bảy, 31/10/2026',
      'time_range' => '05:00 – 18:00',
      'location' => 'Xã La Pán Tẩn, Huyện Mù Cang Chải, Tỉnh Yên Bái (Vùng cao Tây Bắc)',
      'meeting_point' => 'Cổng Công viên Thống Nhất, Hà Nội (Xe đón tập trung lúc 05:00)',
      'capacity' => 60,
      'registered' => 58,
      'leader_name' => 'Trần Hoàng Nam',
      'leader_phone' => '0912.456.789',
      'shifts' => 
      array (
        0 => 'Cả ngày (05:00 – 18:00)',
      ),
      'skills_needed' => 
      array (
        0 => 'Không say xe',
        1 => 'Chịu được leo dốc đồi núi',
        2 => 'Làm việc nhóm',
        3 => 'Nhiếp ảnh, truyền thông',
      ),
      'benefits' => 
      array (
        0 => 'Xe đưa đón 2 chiều từ Hà Nội',
        1 => 'Suất ăn đặc sản Tây Bắc',
        2 => 'Bảo hiểm tai nạn du lịch',
        3 => 'Huy hiệu Đại sứ Rừng Xanh Tây Bắc',
      ),
      'summary' => 'Đồng hành cùng bà con đồng bào H\'Mông và kiểm lâm gieo ươm cây dổi, sa mộc phủ xanh đồi trọc, củng cố đai rừng phòng hộ chống sạt lở mùa mưa.',
      'status' => 'recruiting',
    ),
    2 => 
    array (
      'id' => 103,
      'title' => 'Ngày Hội Sống Xanh Thủ Đô — Thu Gom Pin Cũ, Rác Điện Tử Đổi Cây Sen Đá & Sách',
      'category' => 'thuc_te_moi_truong',
      'category_name' => 'Hoạt Động Thực Địa & Môi Trường Xanh',
      'date_string' => 'Chủ Nhật, 08/11/2026',
      'time_range' => '08:00 – 16:30',
      'location' => 'Công viên Thống Nhất (Cổng Trần Nhân Tông), Hai Bà Trưng, Hà Nội',
      'meeting_point' => 'Khu nhà tròn trung tâm Công viên Thống Nhất',
      'capacity' => 25,
      'registered' => 19,
      'leader_name' => 'Nguyễn Hà My',
      'leader_phone' => '0933.789.012',
      'shifts' => 
      array (
        0 => 'Ca Sáng (08:00 – 12:00)',
        1 => 'Ca Chiều (12:30 – 16:30)',
      ),
      'skills_needed' => 
      array (
        0 => 'Giao tiếp tốt',
        1 => 'Phân loại rác cơ bản',
        2 => 'Chăm sóc cây cảnh mini',
        3 => 'Hoạt náo viên',
      ),
      'benefits' => 
      array (
        0 => 'Tặng 01 chậu sen đá để bàn',
        1 => 'Cơm trưa hộp thân thiện môi trường',
        2 => 'Chứng chỉ tình nguyện viên sự kiện',
      ),
      'summary' => 'Thiết lập 5 trạm thu gom pin axit, điện thoại cũ hỏng tại Hà Nội và hướng dẫn các bạn trẻ phương pháp tái chế rác hữu cơ tại hộ gia đình.',
      'status' => 'recruiting',
    ),
    3 => 
    array (
      'id' => 201,
      'title' => 'Giọt Hồng Nghĩa Tình 2026 — Ngày Hội Hiến Máu Cứu Người Nghĩa Tình Thủ Đô',
      'category' => 'hien_mau_y_te',
      'category_name' => 'Hiến Máu Nhân Đạo & Hỗ Trợ Y Tế',
      'date_string' => 'Thứ Bảy, 24/10/2026',
      'time_range' => '06:30 – 12:00',
      'location' => 'Viện Huyết học — Truyền máu Trung ương, Phạm Văn Bạch, Cầu Giấy, Hà Nội',
      'meeting_point' => 'Sảnh Trung tâm Hội trường Tầng 2, Viện Huyết học',
      'capacity' => 35,
      'registered' => 31,
      'leader_name' => 'Lê Nguyễn Phương Thảo',
      'leader_phone' => '0988.555.777',
      'shifts' => 
      array (
        0 => 'Ca Sáng (06:30 – 12:00)',
      ),
      'skills_needed' => 
      array (
        0 => 'Ân cần chu đáo',
        1 => 'Hỗ trợ y tế sau hiến',
        2 => 'Nhập liệu vi tính',
        3 => 'Sơ cấp cứu cơ bản',
      ),
      'benefits' => 
      array (
        0 => 'Áo TNV Y Tế',
        1 => 'Suất ăn nhẹ bổ dưỡng',
        2 => 'Xét nghiệm máu miễn phí 6 chỉ số',
        3 => 'Giấy khen Hội Chữ Thập Đỏ',
      ),
      'summary' => 'Hỗ trợ bác sĩ Viện Truyền máu đo huyết áp, phát trà đường, chăm sóc người hiến máu và điều phối hàng trăm lượt thanh niên, sinh viên Thủ đô hiến máu.',
      'status' => 'recruiting',
    ),
    4 => 
    array (
      'id' => 202,
      'title' => 'Tiếp Sức Bệnh Nhân Nghèo — Phát Cháo Dinh Dưỡng & Hỗ Trợ Tại Viện K Tân Triều',
      'category' => 'hien_mau_y_te',
      'category_name' => 'Hiến Máu Nhân Đạo & Hỗ Trợ Y Tế',
      'date_string' => 'Thứ Hai đến Thứ Sáu (Ca trực sáng)',
      'time_range' => '05:45 – 09:30',
      'location' => 'Bệnh Viện K (Cơ sở Tân Triều), Số 30 Cầu Bươu, Thanh Trì, Hà Nội',
      'meeting_point' => 'Cổng số 1 Bệnh viện K Tân Triều',
      'capacity' => 20,
      'registered' => 16,
      'leader_name' => 'Lê Nguyễn Phương Thảo',
      'leader_phone' => '0988.555.777',
      'shifts' => 
      array (
        0 => 'Ca Sáng Sớm (05:45 – 09:30)',
      ),
      'skills_needed' => 
      array (
        0 => 'Dậy sớm đúng giờ',
        1 => 'Đồng cảm kiên nhẫn',
        2 => 'Biết sơ chế chia cháo',
        3 => 'Khẩu trang y tế',
      ),
      'benefits' => 
      array (
        0 => 'Suất ăn sáng tại bếp viện',
        1 => 'Hỗ trợ xăng xe ca trực',
        2 => 'Chứng nhận công tác xã hội y tế',
      ),
      'summary' => 'Nấu và trao tận tay 600 bát cháo ấm nóng, hướng dẫn bệnh nhân tỉnh xa làm thủ tục BHYT và đẩy xe lăn hỗ trợ người già điều trị tại Viện K.',
      'status' => 'recruiting',
    ),
    5 => 
    array (
      'id' => 301,
      'title' => 'Tiếp Sức Mùa Thi Thủ Đô 2026 — Đội Xe Ôm Tình Nguyện & Điều Tiết Giao Thông Cổng Trường',
      'category' => 'tiep_suc_giao_duc',
      'category_name' => 'Tiếp Sức Mùa Thi & Giáo Dục Cộng Đồng',
      'date_string' => 'Chiến dịch trọng điểm (26/10 – 29/10/2026)',
      'time_range' => '06:00 – 17:30 (Hai ca)',
      'location' => '15 Điểm Thi Tốt Nghiệp THPT tại Hà Nội (ĐH Sư Phạm HN, THPT Chu Văn An, THPT Kim Liên)',
      'meeting_point' => 'Cụm văn phòng Đoàn các trường Đại học tại Cầu Giấy & Đống Đa, Hà Nội',
      'capacity' => 80,
      'registered' => 74,
      'leader_name' => 'Trần Hoàng Nam',
      'leader_phone' => '0912.456.789',
      'shifts' => 
      array (
        0 => 'Ca Sáng (06:00 – 11:30)',
        1 => 'Ca Chiều (12:30 – 17:30)',
        2 => 'Cả ngày (06:00 – 17:30)',
      ),
      'skills_needed' => 
      array (
        0 => 'Có xe máy và bằng lái xe',
        1 => 'Thông thạo đường xá Hà Nội',
        2 => 'Nhiệt tình hoạt náo',
        3 => 'Giữ gìn trật tự',
      ),
      'benefits' => 
      array (
        0 => 'Áo đồng phục Tiếp Sức Mùa Thi',
        1 => 'Nón tai bèo',
        2 => 'Hỗ trợ xăng xe 100.000đ/ngày',
        3 => 'Bằng khen Thành Đoàn Hà Nội',
      ),
      'summary' => 'Túc trực đón đưa thí sinh đi lạc, phát tặng nước mát, bút chì 2B, áo mưa và phân luồng giao thông chống ùn tắc tại các cổng trường thi Hà Nội.',
      'status' => 'recruiting',
    ),
    6 => 
    array (
      'id' => 401,
      'title' => 'Bếp Ăn 0 Đồng Hà Nội — Nấu 1.500 Suất Cơm Yêu Thương Trao Bệnh Nhân Nghèo Viện Bạch Mai',
      'category' => 'cuu_tro_an_sinh',
      'category_name' => 'Cứu Trợ Khẩn Cấp & Bếp Ăn 0 Đồng',
      'date_string' => 'Chủ Nhật hàng tuần (Bắt đầu 06:30)',
      'time_range' => '06:30 – 12:30',
      'location' => 'Bếp Thiện Nguyện D2L, Ngõ 1 Lê Thanh Nghị, Hai Bà Trưng, Hà Nội (Gần BV Bạch Mai)',
      'meeting_point' => 'Sân trước Bếp Thiện Nguyện D2L Lê Thanh Nghị',
      'capacity' => 30,
      'registered' => 27,
      'leader_name' => 'Phạm Minh Quân',
      'leader_phone' => '0908.123.456',
      'shifts' => 
      array (
        0 => 'Ca Sáng (06:30 – 12:30)',
      ),
      'skills_needed' => 
      array (
        0 => 'Phụ bếp sơ chế rau củ',
        1 => 'Xào nấu số lượng lớn',
        2 => 'Chia hộp cơm',
        3 => 'Vệ sinh bếp',
      ),
      'benefits' => 
      array (
        0 => 'Bữa trưa thân mật cùng đội',
        1 => 'Áo tạp dề Bếp 0 Đồng',
        2 => 'Chứng nhận tình nguyện viên nhân ái',
      ),
      'summary' => 'Cùng các đầu bếp tình nguyện sơ chế rau củ, nấu 1.500 suất cơm mặn và canh nóng chuyển đến bà con xóm chạy thận Lê Thanh Nghị và bệnh nhân nghèo BV Bạch Mai, Việt Đức.',
      'status' => 'recruiting',
    ),
    7 => 
    array (
      'id' => 501,
      'title' => 'Mái Ấm Tuổi Vàng — Thăm Khám, Cắt Tóc & Chăm Sóc Người Già Neo Đơn Viện Dưỡng Lão Ba Vì',
      'category' => 'cham_soc_yeu_thuong',
      'category_name' => 'Chăm Sóc Người Cao Tuổi & Trẻ Em Khuyết Tật',
      'date_string' => 'Chủ Nhật, 01/11/2026',
      'time_range' => '07:30 – 12:00',
      'location' => 'Trung Tâm Chăm Sóc Người Cao Tuổi Hà Nội, Xã Thụy An, Huyện Ba Vì, Hà Nội',
      'meeting_point' => 'Cổng Viện Dưỡng Lão Ba Vì (Xe đón từ Bến xe Mỹ Đình)',
      'capacity' => 25,
      'registered' => 22,
      'leader_name' => 'Lê Nguyễn Phương Thảo',
      'leader_phone' => '0988.555.777',
      'shifts' => 
      array (
        0 => 'Ca Sáng (07:30 – 12:00)',
      ),
      'skills_needed' => 
      array (
        0 => 'Lắng nghe tâm sự',
        1 => 'Biết cắt tóc, gội đầu',
        2 => 'Văn nghệ ca hát',
        3 => 'Bóp tay chân cho các cụ',
      ),
      'benefits' => 
      array (
        0 => 'Chứng nhận tình nguyện viên nhân ái',
        1 => 'Bữa trưa ấm áp tại viện',
        2 => 'Thư cảm ơn của Viện',
      ),
      'summary' => 'Mang lại sự ấm áp cho 90 cụ già neo đơn, đo huyết áp, xoa bóp xương khớp khi mùa đông về, cắt tỉa móng tay và tổ chức sinh nhật chung cho các cụ.',
      'status' => 'recruiting',
    ),
  ),
  'forms' => 
  array (
    0 => 
    array (
      'code' => 'BM-TNV-01',
      'title' => 'Bản Cam Kết Tuân Thủ Nội Quy & Đảm Bảo An Toàn Tình Nguyện Viên',
      'category_badge' => 'Cam kết an toàn',
      'file_type' => 'DOCX / PDF',
      'pages' => 2,
      'downloads' => 1420,
      'desc' => 'Văn bản bắt buộc ký trước khi xuất quân tham gia các chiến dịch thực địa, dọn rác kênh rạch, hiến máu hoặc cứu trợ bão lũ.',
      'preview_content' => 
      array (
        'header' => 'ĐỘI HÌNH TÌNH NGUYỆN XUNG KÍCH D2L
BAN CHỈ HUY ĐIỀU PHỐI ĐỘI
--------------------',
        'doc_title' => 'BẢN CAM KẾT TUÂN THỦ NỘI QUY & AN TOÀN TÌNH NGUYỆN VIÊN
CHIẾN DỊCH: [ĐIỀN TÊN CHIẾN DỊCH]',
        'body' => 'Tôi tên là: [Họ và tên TNV] — MSSV: [Mã sinh viên]
Số điện thoại liên hệ: [Số điện thoại] — Người thân khi cần báo tin khẩn: [Tên & SĐT người thân]

Sau khi được phổ biến nội quy và phương án an toàn, tôi xin cam kết:
1. Chấp hành tuyệt đối sự điều phối của Ban Chỉ huy và Trưởng ca trực.
2. Luôn mang theo thẻ TNV, mặc trang phục nhận diện và đồ bảo hộ đầy đủ.
3. Tuyệt đối không tự ý rời vị trí phân công, không tắm sông suối hoặc đi lại ở khu vực nguy hiểm.
4. Giữ gìn thái độ chuẩn mực, thân thiện, văn minh khi tiếp xúc với nhân dân địa phương.
5. Tự bảo quản tư trang cá nhân và bảo vệ tài sản, trang thiết bị của Đội.',
        'footer' => 'Hà Nội, ngày ...... tháng ...... năm 2026
NGƯỜI LÀM CAM KẾT
(Ký và ghi rõ họ tên)',
      ),
    ),
    1 => 
    array (
      'code' => 'BM-TNV-02',
      'title' => 'Đơn Đăng Ký Gia Nhập Đội Hình Tình Nguyện Viên Nòng Cốt Chính Thức',
      'category_badge' => 'Gia nhập đội',
      'file_type' => 'DOCX / PDF',
      'pages' => 3,
      'downloads' => 890,
      'desc' => 'Dành cho các tình nguyện viên đã tham gia trên 3 hoạt động và có nguyện vọng trở thành Thành viên Nòng Cốt trong Ban Điều Hành.',
      'preview_content' => 
      array (
        'header' => 'ĐỘI HÌNH TÌNH NGUYỆN XUNG KÍCH D2L
BAN CHỈ HUY ĐIỀU PHỐI ĐỘI
--------------------',
        'doc_title' => 'ĐƠN XIN GIA NHẬP LỰC LƯỢNG TÌNH NGUYỆN VIÊN NÒNG CỐT
NHIỆM KỲ 2026 – 2027',
        'body' => 'Kính gửi: Ban Chủ Nhiệm Đội Tình Nguyện D2L Team.

Tôi là: [Họ và tên ứng viên] — MSSV: [Mã sinh viên]
Chuyên ngành: [Khoa / Chuyên ngành] — Khóa: [Khóa học]
Số điện thoại: [Số điện thoại] — Email: [Email cá nhân]

Nguyện vọng ứng tuyển vào Ban Chuyên môn:
[ ] Ban Điều Phối & Phân Công Ca Trực
[ ] Ban Y Tế & Sơ Cấp Cứu Hiện Trường
[ ] Ban Hậu Cần, Kỹ Thuật & Vận Chuyển
[ ] Ban Truyền Thông, Báo Chí & Gây Quỹ

Quá trình hoạt động và đóng góp đã qua:
- Đã tham gia các chiến dịch: Chủ Nhật Xanh Hà Nội, Tiếp Sức Mùa Thi Thủ Đô, Đông Ấm Vùng Cao...
- Tổng số giờ tình nguyện tích lũy: ....... giờ.
- Năng khiếu / Điểm mạnh nổi bật: [Điền năng khiếu lãnh đạo, xử lý sự cố...]

Nếu được tiếp nhận làm Thành viên Nòng cốt, tôi cam kết dành tối thiểu 10 giờ/tuần để cùng Ban Chỉ Huy tổ chức và dẫn dắt các thế hệ TNV kế cận.',
        'footer' => 'Hà Nội, ngày ...... tháng ...... năm 2026
Ý KIẾN BAN CHỦ NHIỆM                     NGƯỜI LÀM ĐƠN
(Duyệt / Chưa duyệt)                    (Ký và ghi rõ họ tên)',
      ),
    ),
    2 => 
    array (
      'code' => 'BM-TNV-03',
      'title' => 'Báo Cáo Tổng Kết & Biên Bản Bàn Giao Ca Trực Thực Địa',
      'category_badge' => 'Vận hành ca trực',
      'file_type' => 'XLSX / PDF',
      'pages' => 1,
      'downloads' => 2150,
      'desc' => 'Trưởng ca trực dùng để điểm danh quân số, ghi nhận sự cố, đánh giá khối lượng công việc và bàn giao vật tư cho ca kế tiếp.',
      'preview_content' => 
      array (
        'header' => 'HỆ THỐNG ĐIỀU PHỐI CA TRỰC D2L
NHẬT KÝ VẬN HÀNH THỰC ĐỊA
--------------------',
        'doc_title' => 'BIÊN BẢN BÀN GIAO CA TRỰC VÀ BÁO CÁO TIẾN ĐỘ
Chiến dịch: [Tên chiến dịch tình nguyện]',
        'body' => '1. THÔNG TIN CA TRỰC:
- Ngày trực: [Ngày/Tháng/Năm] — Ca trực: [Ca Sáng / Ca Chiều / Ca Tối]
- Trưởng ca trực: [Họ tên Trưởng ca] — SĐT: [Số điện thoại]
- Địa điểm phụ trách: [Tuyến đường / Điểm trực cụ thể]

2. QUÂN SỐ VÀ ĐIỂM DANH:
- Tổng số TNV được phân công: ........ đồng chí
- Số TNV có mặt thực tế: ........ đồng chí (Vắng có phép: ...., Không phép: ....)

3. KHỐI LƯỢNG CÔNG VIỆC HOÀN THÀNH:
- Khối lượng rác thải/suất ăn/thí sinh hỗ trợ: .......................................
- Tiến độ so với mục tiêu đề ra: Đạt .....%

4. SỰ CỐ VÀ TÌNH HUỐNG PHÁT SINH:
[Ghi nhận chấn thương nhẹ, thiếu hụt vật tư hoặc tình hình an ninh địa bàn]

5. BÀN GIAO VẬT TƯ CHO CA KẾ TIẾP:
- Trang thiết bị còn lại: .............................................................
- Người nhận bàn giao ca sau: [Ký tên]',
        'footer' => 'XÁC NHẬN CỦA ĐỊA BÀN                     TRƯỞNG CA TRỰC
(Ký và đóng dấu nếu có)                  (Ký và ghi rõ họ tên)',
      ),
    ),
    3 => 
    array (
      'code' => 'BM-TNV-04',
      'title' => 'Phiếu Khảo Sát Nhu Cầu & Đánh Giá Địa Bàn Tình Nguyện Mới',
      'category_badge' => 'Khảo sát thực tế',
      'file_type' => 'DOCX / PDF',
      'pages' => 2,
      'downloads' => 640,
      'desc' => 'Dành cho Ban Tiền Trạm đi thực tế các xã khó khăn, mái ấm hoặc điểm trường để lập kế hoạch triển khai chiến dịch.',
      'preview_content' => 
      array (
        'header' => 'ĐỘI HÌNH TÌNH NGUYỆN XUNG KÍCH D2L
BAN TIỀN TRẠM & KHẢO SÁT ĐỊA BÀN
--------------------',
        'doc_title' => 'PHIẾU ĐÁNH GIÁ THỰC TẾ ĐỊA BÀN TÌNH NGUYỆN
Địa phương: [Tên Xã / Huyện / Mái ấm]',
        'body' => '1. THÔNG TIN ĐỐI TÁC CƠ SỞ:
- Cơ quan tiếp nhận: [UBND Xã / Ban Giám Hiệu Điểm Trường / Trung Tâm Bảo Trợ]
- Người đại diện liên hệ: [Họ và tên] — Chức vụ: [Chức vụ] — SĐT: [SĐT]
- Địa chỉ chính xác: Bản Tả Van Giáy, Xã Tả Van, Thị xã Sa Pa, Tỉnh Lào Cai (Địa bàn vùng cao miền Bắc)

2. THỰC TRẠNG VÀ NHU CẦU CẤP THIẾT:
- Về mùa đông và cước rét / nước sinh hoạt: [Ghi chú thực trạng cước rét, thiếu áo ấm, đường đèo trơn trượt]
- Về số lượng học sinh nghèo / điểm trường lẻ: [Số liệu cụ thể]
- Nhu cầu cần hỗ trợ nhất: (Áo ấm mùa đông / Học bổng / Sách vở / Đèn năng lượng mặt trời / Sơ cấp cứu)

3. ĐIỀU KIỆN ĂN Ở, ĐI LẠI CỦA ĐỘI TÌNH NGUYỆN VIÊN:
- Khoảng cách di chuyển từ trung tâm Hà Nội: ~320 km. Phương tiện tiếp cận: (Xe ô tô gầm cao / Đi bộ vượt dốc)
- Nơi lưu trú cho TNV: (Trường học dân tộc nội trú / Nhà văn hóa thôn). Có điện lưới và nước sinh hoạt.
- Khuyến cáo về an toàn và thời tiết: [Ghi chú mặc ấm nhiều lớp, chuẩn bị thuốc cảm sốt, chống trơn trượt đường đèo].',
        'footer' => 'ĐẠI DIỆN ĐỊA PHƯƠNG KHẢO SÁT            TRƯỞNG ĐOÀN TIỀN TRẠM
(Xác nhận nhu cầu)                      (Ký và ghi rõ họ tên)',
      ),
    ),
    4 => 
    array (
      'code' => 'BM-TNV-05',
      'title' => 'Giấy Xác Nhận Hoàn Thành Hoạt Động Xã Hội & Cấp Giờ Tình Nguyện',
      'category_badge' => 'Chứng nhận chuẩn',
      'file_type' => 'PDF / IN ẤN',
      'pages' => 1,
      'downloads' => 3450,
      'desc' => 'Chứng nhận chính thức có giá trị quy đổi điểm rèn luyện sinh viên, xét học bổng khuyến khích và hoàn thành chỉ tiêu Ngày Thứ Bảy Tình Nguyện.',
      'preview_content' => 
      array (
        'header' => 'HỘI SINH VIÊN VIỆT NAM — THÀNH ĐOÀN HÀ NỘI
ĐỘI HÌNH TÌNH NGUYỆN VIÊN XUNG KÍCH D2L
--------------------',
        'doc_title' => 'GIẤY XÁC NHẬN HOÀN THÀNH CÔNG TÁC XÃ HỘI
VÀ TÍCH LŨY GIỜ TÌNH NGUYỆN NĂM 2026
Số: [Mã chứng nhận TNV-2026-XXXX]',
        'body' => 'BAN CHỈ HUY ĐỘI TÌNH NGUYỆN D2L CHỨNG NHẬN:

Đồng chí: [Họ và tên Tình nguyện viên]
Mã số sinh viên: [MSSV] — Lớp / Khoa: [Khoa]
Đã tích cực tham gia và hoàn thành xuất sắc nhiệm vụ được phân công trong:
Chiến dịch: [Tên Chiến Dịch Tình Nguyện Tham Gia]

KẾT QUẢ ĐẠT ĐƯỢC:
- Tổng số ngày công tác: ..... ngày (Từ ngày .... đến ngày ....)
- Tổng số giờ tình nguyện được ghi nhận: ...... giờ.
- Xếp loại hoàn thành nhiệm vụ: [Xuất Sắc / Hoàn Thành Tốt]

Giấy xác nhận này dùng làm căn cứ cộng điểm rèn luyện học kỳ và chứng nhận hoạt động phục vụ cộng đồng theo quy chế công tác sinh viên hiện hành.',
        'footer' => 'Hà Nội, ngày ...... tháng ...... năm 2026
TM. BAN CHỈ HUY CHIẾN DỊCH
CHỈ HUY TRƯỞNG
(Ký tên và đóng dấu đỏ)',
      ),
    ),
    5 => 
    array (
      'code' => 'BM-TNV-06',
      'title' => 'Kế Hoạch Tổ Chức & Bản Dự Trù Kinh Phí Chiến Dịch Tình Nguyện',
      'category_badge' => 'Kế hoạch & Kinh phí',
      'file_type' => 'XLSX / DOCX',
      'pages' => 4,
      'downloads' => 780,
      'desc' => 'Khung mẫu chuẩn để lập kế hoạch chi tiết, phân công ban bệ, dự trù ngân sách mua sắm vật tư và kêu gọi nhà tài trợ đồng hành.',
      'preview_content' => 
      array (
        'header' => 'ĐỘI HÌNH TÌNH NGUYỆN XUNG KÍCH D2L
BAN KẾ HOẠCH & TÀI CHÍNH
--------------------',
        'doc_title' => 'KẾ HOẠCH TỔ CHỨC VÀ BẢNG DỰ TOÁN KINH PHÍ
Chương trình: [Tên chương trình tình nguyện]',
        'body' => 'I. MỤC ĐÍCH & Ý NGHĨA CHƯƠNG TRÌNH
- Khơi dậy tinh thần tương thân tương ái, cống hiến vì xã hội của tuổi trẻ.
- Trực tiếp hỗ trợ [Số lượng người thụ hưởng] có hoàn cảnh đặc biệt vươn lên trong cuộc sống.

II. TIẾN ĐỘ THỰC HIỆN VÀ PHÂN CÔNG BAN TỔ CHỨC
- Giai đoạn 1 (Tiền trạm & Tuyển quân): Từ ngày .... đến ngày ....
- Giai đoạn 2 (Tập huấn kỹ năng & Vận động gây quỹ): Từ ngày .... đến ngày ....
- Giai đoạn 3 (Thực hiện chiến dịch cao điểm): Từ ngày .... đến ngày ....

III. DỰ TOÁN CHI TIẾT:
1. Thuê xe ô tô vận chuyển quân và hàng hóa lên vùng cao: ....... VNĐ
2. Mua sắm vật tư bảo hộ và dụng cụ lao động: ................. VNĐ
3. Mua quà tặng (Áo phao ấm, gạo, sữa, sách vở, thuốc men): ... VNĐ
4. Suất ăn bồi dưỡng và nước uống cho TNV: .................... VNĐ
5. Chi phí dự phòng phát sinh (10%): .......................... VNĐ
=> TỔNG CỘNG KINH PHÍ DỰ TRÙ: ................................ VNĐ',
        'footer' => 'NGƯỜI LẬP KẾ HOẠCH                       TRƯỞNG BAN TÀI CHÍNH
(Ký và ghi rõ họ tên)                    (Ký và ghi rõ họ tên)',
      ),
    ),
  ),
  'duty_schedule' => 
  array (
    0 => 
    array (
      'shift_code' => 'CA-01',
      'shift_name' => 'Ca Sáng (06:30 – 11:30)',
      'activity_id' => 101,
      'activity_title' => 'Chủ Nhật Xanh Hà Nội — Dọn Rác & Cải Tạo Tuyến Sông Tô Lịch',
      'location' => 'Cầu Cống Mọc, Đường Giáp Nhất, Thanh Xuân, Hà Nội',
      'leader' => 'Phạm Minh Quân',
      'leader_role' => 'Trưởng Ban Hậu Cần',
      'needed_count' => 12,
      'assigned_volunteers' => 
      array (
        0 => 
        array (
          'name' => 'Nguyễn Văn An',
          'role' => 'TNV Đội vớt rác',
          'status' => 'confirmed',
        ),
        1 => 
        array (
          'name' => 'Lê Thị Mai',
          'role' => 'TNV Hậu cần & Nước uống',
          'status' => 'confirmed',
        ),
        2 => 
        array (
          'name' => 'Trần Quốc Bảo',
          'role' => 'TNV Bưng bê vật tư',
          'status' => 'confirmed',
        ),
        3 => 
        array (
          'name' => 'Đặng Thu Trang',
          'role' => 'TNV Truyền thông chụp ảnh',
          'status' => 'confirmed',
        ),
        4 => 
        array (
          'name' => 'Hoàng Minh Tuấn',
          'role' => 'TNV Trồng cây',
          'status' => 'confirmed',
        ),
      ),
      'status' => 'Đang diễn ra',
      'status_class' => 'status-active',
    ),
    1 => 
    array (
      'shift_code' => 'CA-02',
      'shift_name' => 'Ca Sáng (06:30 – 12:00)',
      'activity_id' => 201,
      'activity_title' => 'Giọt Hồng Nghĩa Tình — Tiếp Nhận Hiến Máu Cứu Người Mùa Hè',
      'location' => 'Sảnh Tầng 2 Viện Huyết học — Truyền máu TW, Cầu Giấy, Hà Nội',
      'leader' => 'Lê Nguyễn Phương Thảo',
      'leader_role' => 'Trưởng Ban Y Tế',
      'needed_count' => 15,
      'assigned_volunteers' => 
      array (
        0 => 
        array (
          'name' => 'Võ Thị Kim Oanh',
          'role' => 'TNV Tiếp đón bàn 1',
          'status' => 'confirmed',
        ),
        1 => 
        array (
          'name' => 'Nguyễn Thành Long',
          'role' => 'TNV Pha trà đường & Giữ cặp',
          'status' => 'confirmed',
        ),
        2 => 
        array (
          'name' => 'Đỗ Minh Đức',
          'role' => 'TNV Cấp phát giấy chứng nhận',
          'status' => 'confirmed',
        ),
        3 => 
        array (
          'name' => 'Bùi Thanh Hằng',
          'role' => 'TNV Theo dõi sau hiến máu',
          'status' => 'confirmed',
        ),
      ),
      'status' => 'Đang tuyển thêm 3 TNV',
      'status_class' => 'status-warning',
    ),
    2 => 
    array (
      'shift_code' => 'CA-03',
      'shift_name' => 'Ca Chiều (13:30 – 17:30)',
      'activity_id' => 301,
      'activity_title' => 'Tiếp Sức Mùa Thi Thủ Đô — Điều Tiết Giao Thông & Xe Ôm Tình Nguyện',
      'location' => 'Cổng Trường ĐH Sư Phạm Hà Nội & THPT Chu Văn An',
      'leader' => 'Trần Hoàng Nam',
      'leader_role' => 'Chỉ Huy Trưởng',
      'needed_count' => 20,
      'assigned_volunteers' => 
      array (
        0 => 
        array (
          'name' => 'Lê Hữu Đạt',
          'role' => 'TNV Chốt giao thông 1',
          'status' => 'confirmed',
        ),
        1 => 
        array (
          'name' => 'Phạm Quỳnh Anh',
          'role' => 'TNV Phát nước mát cổng trường',
          'status' => 'confirmed',
        ),
        2 => 
        array (
          'name' => 'Trịnh Gia Huy',
          'role' => 'TNV Lái xe ôm đưa đón',
          'status' => 'confirmed',
        ),
      ),
      'status' => 'Sắp diễn ra',
      'status_class' => 'status-info',
    ),
    3 => 
    array (
      'shift_code' => 'CA-04',
      'shift_name' => 'Ca Tối (18:00 – 21:30)',
      'activity_id' => 401,
      'activity_title' => 'Bếp Ăn 0 Đồng Hà Nội — Nấu & Trao Cơm Cho Bệnh Nhân Nghèo Viện Bạch Mai',
      'location' => 'Bếp Thiện Nguyện D2L, Ngõ 1 Lê Thanh Nghị, Hai Bà Trưng, Hà Nội',
      'leader' => 'Phạm Minh Quân',
      'leader_role' => 'Trưởng Ban Hậu Cần',
      'needed_count' => 10,
      'assigned_volunteers' => 
      array (
        0 => 
        array (
          'name' => 'Trần Minh Quân',
          'role' => 'TNV Nhóm xe tuần tra 1',
          'status' => 'confirmed',
        ),
        1 => 
        array (
          'name' => 'Nguyễn Thị Thảo',
          'role' => 'TNV Phân chia túi quà',
          'status' => 'confirmed',
        ),
      ),
      'status' => 'Mở nhận đăng ký ca',
      'status_class' => 'status-open',
    ),
  ),
  'demo_users' => 
  array (
    'leader' => 
    array (
      'id' => 'USR-001',
      'name' => 'Trần Hoàng Nam',
      'email' => 'nam.tran@d2l.org',
      'role' => 'Chỉ Huy Trưởng Đội Hình',
      'role_type' => 'coordinator',
      'badge' => 'Ngôi Sao Vàng Cống Hiến',
      'volunteer_hours' => 128,
      'events_joined' => 24,
      'avatar_letter' => 'N',
      'avatar_color' => '#059669',
      'unit' => 'Ban Chỉ Huy Đội Tình Nguyện D2L',
      'joined_date' => '15/09/2023',
      'phone' => '0912.456.789',
    ),
    'medical' => 
    array (
      'id' => 'USR-002',
      'name' => 'Lê Nguyễn Phương Thảo',
      'email' => 'thao.le@d2l.org',
      'role' => 'Trưởng Ban Y Tế & Sơ Cấp Cứu',
      'role_type' => 'coordinator',
      'badge' => 'Trái Tim Hồng Tình Nguyện',
      'volunteer_hours' => 84,
      'events_joined' => 16,
      'avatar_letter' => 'T',
      'avatar_color' => '#dc2626',
      'unit' => 'ĐH Y Hà Nội',
      'joined_date' => '02/03/2024',
      'phone' => '0988.555.777',
    ),
    'logistics' => 
    array (
      'id' => 'USR-003',
      'name' => 'Phạm Minh Quân',
      'email' => 'quan.pham@d2l.org',
      'role' => 'Trưởng Ban Hậu Cần & Kỹ Thuật',
      'role_type' => 'coordinator',
      'badge' => 'Lá Xanh Bền Bỉ',
      'volunteer_hours' => 68,
      'events_joined' => 14,
      'avatar_letter' => 'Q',
      'avatar_color' => '#2563eb',
      'unit' => 'ĐH Bách Khoa Hà Nội',
      'joined_date' => '10/05/2024',
      'phone' => '0908.123.456',
    ),
    'volunteer' => 
    array (
      'id' => 'USR-004',
      'name' => 'Nguyễn Hà My',
      'email' => 'my.nguyen@d2l.org',
      'role' => 'TNV Nòng Cốt — Ban Truyền Thông',
      'role_type' => 'volunteer',
      'badge' => 'Mầm Xanh Tích Cực',
      'volunteer_hours' => 32,
      'events_joined' => 6,
      'avatar_letter' => 'M',
      'avatar_color' => '#7c3aed',
      'unit' => 'ĐH Khoa Học Xã Hội & Nhân Văn — ĐHQG Hà Nội',
      'joined_date' => '12/08/2025',
      'phone' => '0933.789.012',
    ),
  ),
);
