<?php
declare(strict_types=1);

/**
 * HỆ THỐNG DỮ LIỆU TÌNH NGUYỆN VIÊN (D2L VOLUNTEER PLATFORM)
 * Đồng bộ tự động từ Cơ sở dữ liệu (50 Dự án & 50 Tình nguyện viên)
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
      'title' => 'Chủ Nhật Xanh Hà Nội — Dọn Sạch Rác Tuyến Sông Tô Lịch & Cải Tạo Mảng Xanh Ven Bờ',
      'category' => 'thuc_te_moi_truong',
      'category_name' => 'Bảo Vệ Môi Trường',
      'date_string' => 'Thứ Bảy & Chủ Nhật hàng tuần (24/10 – 25/10/2026)',
      'time_range' => '07:00 – 17:00 (Chia 2 ca Sáng/Chiều)',
      'location' => 'Dọc tuyến Sông Tô Lịch, Quận Cầu Giấy & Đống Đa, Hà Nội',
      'meeting_point' => 'Cầu Cống Mọc, Đường Giáp Nhất, Thanh Xuân, Hà Nội',
      'capacity' => 40,
      'registered' => 32,
      'leader_name' => 'Phạm Minh Quân',
      'leader_phone' => '0908.123.456',
      'shifts' => 
      array (
        0 => 'Ca Sáng (08:00 – 12:00)',
        1 => 'Ca Chiều (13:00 – 17:00)',
        2 => 'Ca Sáng (08:00 – 12:00)',
        3 => 'Ca Chiều (13:00 – 17:00)',
      ),
      'skills_needed' => 
      array (
        0 => 'Làm việc nhóm',
        1 => 'Hậu cần',
      ),
      'benefits' => 
      array (
        0 => 'Áo TNV',
        1 => 'Nước uống',
        2 => 'Giấy chứng nhận',
      ),
      'summary' => 'Ra quân dọn sạch rác thải trôi nổi dọc tuyến sông Tô Lịch, bóc xóa quảng cáo rác ven đường và trồng hàng cây xanh tạo cảnh quan sinh thái.',
      'status' => 'recruiting',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Thứ Bảy & Chủ Nhật hàng tuần (24/10 – 25/10/2026)',
          'full_label' => 'Thứ Bảy (Thứ Bảy & Chủ Nhật hàng tuần (24/10 – 25/10/2026))',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '101-D1-S1',
              'shift_name' => 'Ca Sáng (08:00 – 12:00)',
              'time_range' => '08:00 – 12:00',
              'capacity' => 20,
              'registered' => 16,
              'skill_req' => 'Hậu cần khuân vác, Thể lực tốt',
              'skill_group' => 'hau_can',
            ),
            1 => 
            array (
              'shift_id' => '101-D1-S2',
              'shift_name' => 'Ca Chiều (13:00 – 17:00)',
              'time_range' => '13:00 – 17:00',
              'capacity' => 20,
              'registered' => 12,
              'skill_req' => 'Phân loại rác, Bóc xóa quảng cáo rác',
              'skill_group' => 'hau_can',
            ),
          ),
        ),
        1 => 
        array (
          'day_code' => 'day_2',
          'day_name' => 'Chủ Nhật',
          'date_string' => 'Thứ Bảy & Chủ Nhật hàng tuần (24/10 – 25/10/2026)',
          'full_label' => 'Chủ Nhật (Thứ Bảy & Chủ Nhật hàng tuần (24/10 – 25/10/2026))',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '101-D2-S1',
              'shift_name' => 'Ca Sáng (08:00 – 12:00)',
              'time_range' => '08:00 – 12:00',
              'capacity' => 25,
              'registered' => 19,
              'skill_req' => 'Cải tạo mảng xanh ven sông, Trồng cây',
              'skill_group' => 'hau_can',
            ),
            1 => 
            array (
              'shift_id' => '101-D2-S2',
              'shift_name' => 'Ca Chiều (13:00 – 17:00)',
              'time_range' => '13:00 – 17:00',
              'capacity' => 15,
              'registered' => 7,
              'skill_req' => 'Truyền thông, Chụp ảnh & Hoạt náo',
              'skill_group' => 'truyen_thong',
            ),
          ),
        ),
      ),
    ),
    1 => 
    array (
      'id' => 102,
      'title' => 'Hành Trình Xanh Tây Bắc — Ươm Mầm & Trồng 10.000 Cây Rừng Phòng Hộ Mù Cang Chải',
      'category' => 'thuc_te_moi_truong',
      'category_name' => 'Hoạt Động Thực Địa & Môi Trường Xanh',
      'date_string' => 'Thứ Bảy, 31/10/2026',
      'time_range' => '05:00 – 18:00 (Toàn thời gian dã ngoại)',
      'location' => 'Xã La Pán Tẩn, Huyện Mù Cang Chải, Tỉnh Yên Bái',
      'meeting_point' => 'Cổng Công viên Thống Nhất, Hà Nội (Xe đón tập trung lúc 05:00)',
      'capacity' => 60,
      'registered' => 58,
      'leader_name' => 'Trần Hoàng Nam',
      'leader_phone' => '0912.456.789',
      'shifts' => 
      array (
        0 => 'Ca Cả Ngày (05:00 – 18:00)',
      ),
      'skills_needed' => 
      array (
        0 => 'Không say xe',
        1 => 'Chịu được leo dốc đồi núi',
        2 => 'Làm việc nhóm',
        3 => 'Nhiếp ảnh truyền thông',
      ),
      'benefits' => 
      array (
        0 => 'Xe đưa đón 2 chiều từ Hà Nội',
        1 => 'Suất ăn đặc sản Tây Bắc',
        2 => 'Bảo hiểm tai nạn du lịch',
        3 => 'Huy hiệu Đại sứ Rừng Xanh',
      ),
      'summary' => 'Đồng hành cùng kiểm lâm và bà con H Mông gieo ươm cây dổi, sa mộc phủ xanh đồi trọc, củng cố đai rừng phòng hộ chống sạt lở mùa mưa.',
      'status' => 'recruiting',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Thứ Bảy, 31/10/2026',
          'full_label' => 'Thứ Bảy (Thứ Bảy, 31/10/2026)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '102-D1-S1',
              'shift_name' => 'Ca Cả Ngày (05:00 – 18:00)',
              'time_range' => '05:00 – 18:00',
              'capacity' => 60,
              'registered' => 58,
              'skill_req' => 'Leo núi, Vác cây giống đồi cao',
              'skill_group' => 'hau_can',
            ),
          ),
        ),
      ),
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
        2 => 'Chứng chỉ TNV sự kiện',
      ),
      'summary' => 'Thiết lập 5 trạm thu gom pin axit, điện thoại cũ hỏng tại Hà Nội và hướng dẫn các bạn trẻ phương pháp tái chế rác hữu cơ tại hộ gia đình.',
      'status' => 'recruiting',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Chủ Nhật, 08/11/2026',
          'full_label' => 'Thứ Bảy (Chủ Nhật, 08/11/2026)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '103-D1-S1',
              'shift_name' => 'Ca Sáng (08:00 – 12:00)',
              'time_range' => '08:00 – 12:00',
              'capacity' => 15,
              'registered' => 12,
              'skill_req' => 'Thu nhận & Phân loại rác điện tử',
              'skill_group' => 'hau_can',
            ),
            1 => 
            array (
              'shift_id' => '103-D1-S2',
              'shift_name' => 'Ca Chiều (12:30 – 16:30)',
              'time_range' => '12:30 – 16:30',
              'capacity' => 10,
              'registered' => 7,
              'skill_req' => 'Giao lưu đổi sen đá & Hướng dẫn sống xanh',
              'skill_group' => 'truyen_thong',
            ),
          ),
        ),
      ),
    ),
    3 => 
    array (
      'id' => 104,
      'title' => 'Làm Sạch Bờ Biển Đồ Sơn & Thu Gom Rác Thải Nhựa Đại Dương',
      'category' => 'thuc_te_moi_truong',
      'category_name' => 'Hoạt Động Thực Địa & Môi Trường Xanh',
      'date_string' => 'Thứ Bảy, 14/11/2026',
      'time_range' => '06:00 – 16:00',
      'location' => 'Bãi 2 & Bãi 3 Đồ Sơn, Quận Đồ Sơn, TP. Hải Phòng',
      'meeting_point' => 'Khu du lịch Quốc tế Hòn Dấu, Đồ Sơn',
      'capacity' => 50,
      'registered' => 50,
      'leader_name' => 'Vũ Đình Trọng',
      'leader_phone' => '0944.321.987',
      'shifts' => 
      array (
        0 => 'Ca Sáng (06:00 – 12:00)',
        1 => 'Ca Chiều (12:30 – 16:00)',
      ),
      'skills_needed' => 
      array (
        0 => 'Chịu được nắng gió biển',
        1 => 'Sức khỏe dẻo dai',
        2 => 'Kỹ năng làm việc nhóm',
      ),
      'benefits' => 
      array (
        0 => 'Xe ô tô đưa đón Hà Nội – Hải Phòng',
        1 => 'Bữa trưa hải sản địa phương',
        2 => 'Áo cờ đỏ sao vàng',
      ),
      'summary' => 'Chiến dịch thu gom 3 tấn rác thải nhựa đại dương dạt vào bờ biển, trả lại cảnh quan sạch đẹp cho bãi tắm Đồ Sơn.',
      'status' => 'full',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Thứ Bảy, 14/11/2026',
          'full_label' => 'Thứ Bảy (Thứ Bảy, 14/11/2026)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '104-D1-S1',
              'shift_name' => 'Ca Sáng (06:00 – 12:00)',
              'time_range' => '06:00 – 12:00',
              'capacity' => 30,
              'registered' => 30,
              'skill_req' => 'Thu gom rác ven biển',
              'skill_group' => 'hau_can',
            ),
            1 => 
            array (
              'shift_id' => '104-D1-S2',
              'shift_name' => 'Ca Chiều (12:30 – 16:00)',
              'time_range' => '12:30 – 16:00',
              'capacity' => 20,
              'registered' => 20,
              'skill_req' => 'Đóng bao & Vận chuyển lên xe rác',
              'skill_group' => 'hau_can',
            ),
          ),
        ),
      ),
    ),
    4 => 
    array (
      'id' => 105,
      'title' => 'Chiến Dịch Giải Cứu Dòng Kênh Đen Tham Lương — Bến Cát Mùa Mưa',
      'category' => 'thuc_te_moi_truong',
      'category_name' => 'Hoạt Động Thực Địa & Môi Trường Xanh',
      'date_string' => 'Chủ Nhật, 22/11/2026',
      'time_range' => '07:30 – 12:00',
      'location' => 'Kênh Tham Lương, Quận Bình Tân & Quận 12, TP. Hồ Chí Minh',
      'meeting_point' => 'Cầu Tham Lương, Đường Trường Chinh, Q. Bình Tân, TP.HCM',
      'capacity' => 35,
      'registered' => 10,
      'leader_name' => 'Nguyễn Thanh Tùng',
      'leader_phone' => '0978.112.233',
      'shifts' => 
      array (
        0 => 'Ca Sáng Khẩn Cấp (07:30 – 12:00)',
      ),
      'skills_needed' => 
      array (
        0 => 'Biết bơi lội',
        1 => 'Thể lực vượt trội',
        2 => 'Được tiêm phòng uốn ván',
      ),
      'benefits' => 
      array (
        0 => 'Ủng lội nước chuyên dụng',
        1 => 'Găng tay cao su dầy',
        2 => 'Phụ cấp hỗ trợ 150k/ca',
      ),
      'summary' => 'Vớt rác tắc nghẽn dòng chảy kênh Tham Lương chống ngập úng mùa mưa bão. Đang thiếu hụt nhân sự nam thể lực tốt.',
      'status' => 'recruiting',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Chủ Nhật, 22/11/2026',
          'full_label' => 'Thứ Bảy (Chủ Nhật, 22/11/2026)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '105-D1-S1',
              'shift_name' => 'Ca Sáng Khẩn Cấp (07:30 – 12:00)',
              'time_range' => '07:30 – 12:00',
              'capacity' => 35,
              'registered' => 10,
              'skill_req' => 'Lội nước, Vớt bao rác bùn nặng',
              'skill_group' => 'hau_can',
            ),
          ),
        ),
      ),
    ),
    5 => 
    array (
      'id' => 106,
      'title' => 'Trồng Rừng Ngập Mặn Chắn Sóng Cần Giờ — Bảo Vệ Lá Phổi Xanh Phương Nam',
      'category' => 'thuc_te_moi_truong',
      'category_name' => 'Hoạt Động Thực Địa & Môi Trường Xanh',
      'date_string' => 'Thứ Bảy, 28/11/2026',
      'time_range' => '06:30 – 15:30',
      'location' => 'Khu dự trữ sinh quyển Rừng ngập mặn Cần Giờ, Huyện Cần Giờ, TP.HCM',
      'meeting_point' => 'Bến phà Bình Khánh (Phía Nhà Bè), TP. Hồ Chí Minh',
      'capacity' => 40,
      'registered' => 35,
      'leader_name' => 'Trương Gia Bảo',
      'leader_phone' => '0938.445.566',
      'shifts' => 
      array (
        0 => 'Ca Sáng (07:30 – 11:30)',
        1 => 'Ca Chiều (12:30 – 15:30)',
      ),
      'skills_needed' => 
      array (
        0 => 'Không ngại bùn lầy',
        1 => 'Sức khỏe tốt',
        2 => 'Biết bơi lội căn bản',
      ),
      'benefits' => 
      array (
        0 => 'Ca nô đưa vào vùng đầm ngập mặn',
        1 => 'Bữa trưa đặc sản Cần Giờ',
        2 => 'Chứng nhận TNV UNESCO',
      ),
      'summary' => 'Trồng 3.000 cây đước non tại bãi bồi ven biển Cần Giờ nhằm củng cố vành đai chắn triều cường và bảo vệ hệ sinh thái ngập mặn.',
      'status' => 'recruiting',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Thứ Bảy, 28/11/2026',
          'full_label' => 'Thứ Bảy (Thứ Bảy, 28/11/2026)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '106-D1-S1',
              'shift_name' => 'Ca Sáng (07:30 – 11:30)',
              'time_range' => '07:30 – 11:30',
              'capacity' => 25,
              'registered' => 22,
              'skill_req' => 'Cắm cọc & Trồng cây đước bùn lầy',
              'skill_group' => 'hau_can',
            ),
            1 => 
            array (
              'shift_id' => '106-D1-S2',
              'shift_name' => 'Ca Chiều (12:30 – 15:30)',
              'time_range' => '12:30 – 15:30',
              'capacity' => 15,
              'registered' => 13,
              'skill_req' => 'Thu gom túi nilong bọc rễ cây',
              'skill_group' => 'hau_can',
            ),
          ),
        ),
      ),
    ),
    6 => 
    array (
      'id' => 107,
      'title' => 'Hành Động Vì Động Vật Hoang Dã — Dọn Vệ Sinh Vườn Quốc Gia Cúc Phương',
      'category' => 'thuc_te_moi_truong',
      'category_name' => 'Hoạt Động Thực Địa & Môi Trường Xanh',
      'date_string' => 'Chủ Nhật, 06/12/2026',
      'time_range' => '06:00 – 17:00',
      'location' => 'Vườn Quốc Gia Cúc Phương, Huyện Nho Quan, Tỉnh Ninh Bình',
      'meeting_point' => 'Cổng Đại học Quốc Gia Hà Nội, 144 Xuân Thủy, Cầu Giấy',
      'capacity' => 20,
      'registered' => 18,
      'leader_name' => 'Hoàng Ngọc Ánh',
      'leader_phone' => '0987.654.321',
      'shifts' => 
      array (
        0 => 'Ca Trekking Rừng (07:30 – 15:30)',
      ),
      'skills_needed' => 
      array (
        0 => 'Yêu thiên nhiên động vật',
        1 => 'Đi bộ đường rừng trekking',
        2 => 'Quan sát tốt',
      ),
      'benefits' => 
      array (
        0 => 'Vé vào cổng Vườn Quốc Gia',
        1 => 'Tham quan Trung tâm cứu hộ linh trưởng',
        2 => 'Huy hiệu Bảo tồn Rừng',
      ),
      'summary' => 'Dọn dẹp rác thải trên các cung đường mòn trekking và tháo gỡ các bẫy thú trộm trái phép trong vùng lõi vườn quốc gia.',
      'status' => 'recruiting',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Chủ Nhật, 06/12/2026',
          'full_label' => 'Thứ Bảy (Chủ Nhật, 06/12/2026)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '107-D1-S1',
              'shift_name' => 'Ca Trekking Rừng (07:30 – 15:30)',
              'time_range' => '07:30 – 15:30',
              'capacity' => 20,
              'registered' => 18,
              'skill_req' => 'Trekking đường mòn, Rà bẫy & Dọn rác',
              'skill_group' => 'hau_can',
            ),
          ),
        ),
      ),
    ),
    7 => 
    array (
      'id' => 108,
      'title' => 'Phân Loại Rác Tận Nguồn & Tuyên Truyền Giảm Nhựa Tại Chợ Truyền Thống Đồng Xuân',
      'category' => 'thuc_te_moi_truong',
      'category_name' => 'Hoạt Động Thực Địa & Môi Trường Xanh',
      'date_string' => 'Thứ Bảy, 12/12/2026',
      'time_range' => '08:00 – 12:00',
      'location' => 'Chợ Đồng Xuân, Quận Hoàn Kiếm, Hà Nội',
      'meeting_point' => 'Sảnh trước Cổng Chợ Đồng Xuân, Phố Cầu Đông',
      'capacity' => 15,
      'registered' => 14,
      'leader_name' => 'Nguyễn Hà My',
      'leader_phone' => '0933.789.012',
      'shifts' => 
      array (
        0 => 'Ca Sáng (08:00 – 12:00)',
      ),
      'skills_needed' => 
      array (
        0 => 'Kỹ năng giao tiếp bà con tiểu thương',
        1 => 'Năng nổ thân thiện',
        2 => 'Phân loại rác',
      ),
      'benefits' => 
      array (
        0 => 'Túi vải canvas thời trang',
        1 => 'Áo phông TNV Đồng Xuân',
        2 => 'Giấy xác nhận công tác xã hội',
      ),
      'summary' => 'Tặng 1.000 túi vải cho tiểu thương và người đi chợ, vận động hạn chế túi nilong dùng 1 lần trong sinh hoạt.',
      'status' => 'recruiting',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Thứ Bảy, 12/12/2026',
          'full_label' => 'Thứ Bảy (Thứ Bảy, 12/12/2026)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '108-D1-S1',
              'shift_name' => 'Ca Sáng (08:00 – 12:00)',
              'time_range' => '08:00 – 12:00',
              'capacity' => 15,
              'registered' => 14,
              'skill_req' => 'Phát túi vải & Tuyên truyền tiểu thương',
              'skill_group' => 'truyen_thong',
            ),
          ),
        ),
      ),
    ),
    8 => 
    array (
      'id' => 109,
      'title' => 'Khơi Thông Dòng Chảy Kênh Kim Ngưu & Bóc Xóa Biển Quảng Cáo Bẩn Ven Cầu Mai Động',
      'category' => 'thuc_te_moi_truong',
      'category_name' => 'Hoạt Động Thực Địa & Môi Trường Xanh',
      'date_string' => 'Chủ Nhật, 20/12/2026',
      'time_range' => '07:30 – 11:30',
      'location' => 'Dọc đường Kim Ngưu, Cầu Mai Động, Quận Hai Bà Trưng, Hà Nội',
      'meeting_point' => 'Chân Cầu Mai Động, Ngã tư Minh Khai — Kim Ngưu',
      'capacity' => 30,
      'registered' => 6,
      'leader_name' => 'Phạm Minh Quân',
      'leader_phone' => '0908.123.456',
      'shifts' => 
      array (
        0 => 'Ca Sáng (07:30 – 11:30)',
      ),
      'skills_needed' => 
      array (
        0 => 'Thể lực tốt',
        1 => 'Không ngại bẩn',
        2 => 'Sử dụng sủi cạo quảng cáo',
      ),
      'benefits' => 
      array (
        0 => 'Găng tay bảo hộ dày',
        1 => 'Khẩu trang than hoạt tính',
        2 => 'Nước uống giải khát',
      ),
      'summary' => 'Vớt rác ứ đọng ven chân cầu và cạo sạch biển dán khoan cắt bê tông, tín dụng đen làm mất mỹ quan đô thị. Cần tuyển khẩn cấp.',
      'status' => 'recruiting',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Chủ Nhật, 20/12/2026',
          'full_label' => 'Thứ Bảy (Chủ Nhật, 20/12/2026)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '109-D1-S1',
              'shift_name' => 'Ca Sáng (07:30 – 11:30)',
              'time_range' => '07:30 – 11:30',
              'capacity' => 30,
              'registered' => 6,
              'skill_req' => 'Cạo biển quảng cáo rác & Vớt rác chân cầu',
              'skill_group' => 'hau_can',
            ),
          ),
        ),
      ),
    ),
    9 => 
    array (
      'id' => 110,
      'title' => 'Hồi Sinh Đồi Trọc Mèo Vạc — Trồng Cây Chống Sa Mạc Hóa Cao Nguyên Đá Đồng Văn',
      'category' => 'thuc_te_moi_truong',
      'category_name' => 'Hoạt Động Thực Địa & Môi Trường Xanh',
      'date_string' => 'Thứ Sáu đến Chủ Nhật (10/10 – 12/10/2026)',
      'time_range' => 'Chiến dịch dài ngày kết thúc xuất sắc',
      'location' => 'Thị trấn Mèo Vạc, Tỉnh Hà Giang',
      'meeting_point' => 'UBND Huyện Mèo Vạc, Hà Giang',
      'capacity' => 45,
      'registered' => 45,
      'leader_name' => 'Trần Hoàng Nam',
      'leader_phone' => '0912.456.789',
      'shifts' => 
      array (
        0 => 'Toàn Khóa Chiến Dịch (3 Ngày)',
      ),
      'skills_needed' => 
      array (
        0 => 'Chịu rét vùng cao',
        1 => 'Thể lực bền bỉ',
        2 => 'Lái xe đèo dốc',
      ),
      'benefits' => 
      array (
        0 => 'Chứng nhận Đại sứ Cao Nguyên Đá',
        1 => 'Lưu niệm quà tặng thổ cẩm',
        2 => 'Ghi công địa phương',
      ),
      'summary' => 'Chiến dịch cao điểm trồng 5.000 cây sa mộc trên hốc đá vôi đã hoàn thành đạt 100% chỉ tiêu với sự tham gia của 45 TNV.',
      'status' => 'completed',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Thứ Sáu đến Chủ Nhật (10/10 – 12/10/2026)',
          'full_label' => 'Thứ Bảy (Thứ Sáu đến Chủ Nhật (10/10 – 12/10/2026))',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '110-D1-S1',
              'shift_name' => 'Toàn Khóa Chiến Dịch (3 Ngày)',
              'time_range' => '07:00 – 17:00',
              'capacity' => 45,
              'registered' => 45,
              'skill_req' => 'Trồng cây hốc đá cao nguyên',
              'skill_group' => 'hau_can',
            ),
          ),
        ),
      ),
    ),
    10 => 
    array (
      'id' => 201,
      'title' => 'Giọt Hồng Nghĩa Tình 2026 — Ngày Hội Hiến Máu Cứu Người Nghĩa Tình Thủ Đô',
      'category' => 'hien_mau_y_te',
      'category_name' => 'Hiến Máu Nhân Đạo & Hỗ Trợ Y Tế',
      'date_string' => 'Thứ Sáu & Thứ Bảy (23/10 – 24/10/2026)',
      'time_range' => '06:30 – 17:00 (Ca Sáng & Ca Chiều)',
      'location' => 'Viện Huyết học — Truyền máu Trung ương, Phạm Văn Bạch, Cầu Giấy, Hà Nội',
      'meeting_point' => 'Sảnh Trung tâm Hội trường Tầng 2, Viện Huyết học',
      'capacity' => 35,
      'registered' => 31,
      'leader_name' => 'Lê Nguyễn Phương Thảo',
      'leader_phone' => '0988.555.777',
      'shifts' => 
      array (
        0 => 'Ca Chiều Tập Huấn (14:00 – 17:30)',
        1 => 'Ca Sáng Chính Hội (06:30 – 12:00)',
        2 => 'Ca Chiều Hậu Cần (13:00 – 17:00)',
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
      'summary' => 'Hỗ trợ bác sĩ Viện Truyền máu đo huyết áp, phát trà đường, chăm sóc người hiến máu và điều phối hàng trăm lượt người hiến máu.',
      'status' => 'recruiting',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Thứ Sáu & Thứ Bảy (23/10 – 24/10/2026)',
          'full_label' => 'Thứ Bảy (Thứ Sáu & Thứ Bảy (23/10 – 24/10/2026))',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '201-D1-S1',
              'shift_name' => 'Ca Chiều Tập Huấn (14:00 – 17:30)',
              'time_range' => '14:00 – 17:30',
              'capacity' => 15,
              'registered' => 12,
              'skill_req' => 'Tập huấn sơ cấp cứu & Hậu cần sắp bàn',
              'skill_group' => 'y_te',
            ),
          ),
        ),
        1 => 
        array (
          'day_code' => 'day_2',
          'day_name' => 'Chủ Nhật',
          'date_string' => 'Thứ Sáu & Thứ Bảy (23/10 – 24/10/2026)',
          'full_label' => 'Chủ Nhật (Thứ Sáu & Thứ Bảy (23/10 – 24/10/2026))',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '201-D2-S1',
              'shift_name' => 'Ca Sáng Chính Hội (06:30 – 12:00)',
              'time_range' => '06:30 – 12:00',
              'capacity' => 25,
              'registered' => 22,
              'skill_req' => 'Đo huyết áp, Chăm sóc người hiến máu',
              'skill_group' => 'y_te',
            ),
            1 => 
            array (
              'shift_id' => '201-D2-S2',
              'shift_name' => 'Ca Chiều Hậu Cần (13:00 – 17:00)',
              'time_range' => '13:00 – 17:00',
              'capacity' => 15,
              'registered' => 11,
              'skill_req' => 'Cấp phát giấy chứng nhận, Tiếp đón',
              'skill_group' => 'dieu_phoi',
            ),
          ),
        ),
      ),
    ),
    11 => 
    array (
      'id' => 202,
      'title' => 'Tiếp Sức Bệnh Nhân Nghèo — Phát Cháo Dinh Dưỡng & Hỗ Trợ Tại Viện K Tân Triều',
      'category' => 'hien_mau_y_te',
      'category_name' => 'Hiến Máu Nhân Đạo & Hỗ Trợ Y Tế',
      'date_string' => 'Thứ Hai đến Thứ Sáu (Ca trực sáng sớm)',
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
        1 => 'Ca Sáng Sớm (05:45 – 09:30)',
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
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Thứ Hai đến Thứ Sáu (Ca trực sáng sớm)',
          'full_label' => 'Thứ Bảy (Thứ Hai đến Thứ Sáu (Ca trực sáng sớm))',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '202-D1-S1',
              'shift_name' => 'Ca Sáng Sớm (05:45 – 09:30)',
              'time_range' => '05:45 – 09:30',
              'capacity' => 10,
              'registered' => 8,
              'skill_req' => 'Sơ chế & Múc cháo nóng dinh dưỡng',
              'skill_group' => 'hau_can',
            ),
            1 => 
            array (
              'shift_id' => '202-D1-S2',
              'shift_name' => 'Ca Sáng Sớm (05:45 – 09:30)',
              'time_range' => '05:45 – 09:30',
              'capacity' => 10,
              'registered' => 8,
              'skill_req' => 'Đẩy xe lăn hỗ trợ bệnh nhân nhập viện',
              'skill_group' => 'y_te',
            ),
          ),
        ),
      ),
    ),
    12 => 
    array (
      'id' => 203,
      'title' => 'Báo Động Đỏ Y Tế — Đội Phản Ứng Nhanh Hiến Máu Cấp Cứu Nhóm Máu Hiếm O- & B-',
      'category' => 'hien_mau_y_te',
      'category_name' => 'Hiến Máu Nhân Đạo & Hỗ Trợ Y Tế',
      'date_string' => 'Trực chiến 24/7 (Sẵn sàng điều động trong 30 phút)',
      'time_range' => 'Linh hoạt theo ca cấp cứu khẩn cấp',
      'location' => 'Trung tâm Cấp cứu A9, Bệnh viện Bạch Mai, Giải Phóng, Hà Nội',
      'meeting_point' => 'Cổng Khoa Cấp Cứu A9 Bệnh viện Bạch Mai',
      'capacity' => 10,
      'registered' => 4,
      'leader_name' => 'BS. Trần Tuấn Anh',
      'leader_phone' => '0988.555.772',
      'shifts' => 
      array (
        0 => 'Ca Phản Ứng Nhanh 24/7',
      ),
      'skills_needed' => 
      array (
        0 => 'Nhóm máu Rh âm tính (O-, B-)',
        1 => 'Sức khỏe đạt chuẩn hiến máu',
        2 => 'Có xe máy di chuyển nhanh',
      ),
      'benefits' => 
      array (
        0 => 'Bồi dưỡng chế độ hiến máu cấp cứu đặc biệt',
        1 => 'Xét nghiệm chuyên sâu miễn phí',
        2 => 'Huy hiệu Dòng Máu Hiếm Cứu Người',
      ),
      'summary' => 'Ngân hàng máu đang cạn kiệt nhóm máu hiếm O- phục vụ các ca phẫu thuật tim hở và tai nạn giao thông nặng. Cần bổ sung khẩn cấp!',
      'status' => 'recruiting',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Trực chiến 24/7 (Sẵn sàng điều động trong 30 phút)',
          'full_label' => 'Thứ Bảy (Trực chiến 24/7 (Sẵn sàng điều động trong 30 phút))',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '203-D1-S1',
              'shift_name' => 'Ca Phản Ứng Nhanh 24/7',
              'time_range' => 'Theo lệnh điều động khẩn',
              'capacity' => 10,
              'registered' => 4,
              'skill_req' => 'Hiến máu khẩn cấp nhóm máu hiếm O-/B-',
              'skill_group' => 'y_te',
            ),
          ),
        ),
      ),
    ),
    13 => 
    array (
      'id' => 204,
      'title' => 'Khám Bệnh & Cấp Phát Thuốc Miễn Phí Cho Đồng Bào Xã Khó Khăn Ba Vì',
      'category' => 'hien_mau_y_te',
      'category_name' => 'Hiến Máu Nhân Đạo & Hỗ Trợ Y Tế',
      'date_string' => 'Chủ Nhật, 15/11/2026',
      'time_range' => '06:30 – 16:30',
      'location' => 'Trạm Y Tế Xã Ba Vì, Huyện Ba Vì, Hà Nội',
      'meeting_point' => 'Sảnh Trường Đại Học Y Hà Nội, Số 1 Tôn Thất Tùng',
      'capacity' => 30,
      'registered' => 28,
      'leader_name' => 'Lê Nguyễn Phương Thảo',
      'leader_phone' => '0988.555.777',
      'shifts' => 
      array (
        0 => 'Ca Khám Bệnh Sáng (07:30 – 12:00)',
        1 => 'Ca Phát Thuốc Chiều (13:00 – 16:30)',
      ),
      'skills_needed' => 
      array (
        0 => 'Bác sĩ / Sinh viên Y khoa',
        1 => 'Đo huyết áp, tim mạch',
        2 => 'Dược sĩ soạn thuốc',
        3 => 'Giao tiếp bà con dân tộc',
      ),
      'benefits' => 
      array (
        0 => 'Xe ô tô đưa đón 2 chiều',
        1 => 'Cơm trưa giao lưu trạm y tế',
        2 => 'Chứng nhận thiện nguyện ngành Y',
      ),
      'summary' => 'Đoàn bác sĩ trẻ tình nguyện khám tầm soát huyết áp, tiểu đường, siêu âm tổng quát và phát 500 túi thuốc thiết yếu cho người cao tuổi.',
      'status' => 'recruiting',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Chủ Nhật, 15/11/2026',
          'full_label' => 'Thứ Bảy (Chủ Nhật, 15/11/2026)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '204-D1-S1',
              'shift_name' => 'Ca Khám Bệnh Sáng (07:30 – 12:00)',
              'time_range' => '07:30 – 12:00',
              'capacity' => 20,
              'registered' => 19,
              'skill_req' => 'Khám lâm sàng, Đo sinh hiệu & Điện tim',
              'skill_group' => 'y_te',
            ),
            1 => 
            array (
              'shift_id' => '204-D1-S2',
              'shift_name' => 'Ca Phát Thuốc Chiều (13:00 – 16:30)',
              'time_range' => '13:00 – 16:30',
              'capacity' => 10,
              'registered' => 9,
              'skill_req' => 'Dược sĩ chia liều & Hướng dẫn sử dụng thuốc',
              'skill_group' => 'y_te',
            ),
          ),
        ),
      ),
    ),
    14 => 
    array (
      'id' => 205,
      'title' => 'Hành Trình Đỏ Xuyên Việt — Ngày Hội Hiến Máu Thanh Niên Đà Nẵng',
      'category' => 'hien_mau_y_te',
      'category_name' => 'Hiến Máu Nhân Đạo & Hỗ Trợ Y Tế',
      'date_string' => 'Thứ Bảy, 21/11/2026',
      'time_range' => '07:00 – 16:30',
      'location' => 'Cung Thể Thao Tiên Sơn, Đường Phan Đăng Lưu, Quận Hải Châu, Đà Nẵng',
      'meeting_point' => 'Sảnh Cung Tiên Sơn, TP. Đà Nẵng',
      'capacity' => 60,
      'registered' => 60,
      'leader_name' => 'Nguyễn Đình Phúc',
      'leader_phone' => '0905.778.899',
      'shifts' => 
      array (
        0 => 'Ca Sáng (07:00 – 12:00)',
        1 => 'Ca Chiều (12:30 – 16:30)',
      ),
      'skills_needed' => 
      array (
        0 => 'Điều phối dòng người',
        1 => 'Chăm sóc hậu hiến máu',
        2 => 'Truyền thông sự kiện',
      ),
      'benefits' => 
      array (
        0 => 'Áo đồng phục Hành Trình Đỏ',
        1 => 'Gấu bông biểu trưng giọt máu',
        2 => 'Bữa ăn ca tình nguyện',
      ),
      'summary' => 'Ngày hội hiến máu quy mô 1.500 đơn vị máu tiếp sức cho bệnh viện miền Trung. Đã tuyển đủ 100% quân số tình nguyện viên phục vụ.',
      'status' => 'full',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Thứ Bảy, 21/11/2026',
          'full_label' => 'Thứ Bảy (Thứ Bảy, 21/11/2026)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '205-D1-S1',
              'shift_name' => 'Ca Sáng (07:00 – 12:00)',
              'time_range' => '07:00 – 12:00',
              'capacity' => 35,
              'registered' => 35,
              'skill_req' => 'Tiếp đón & Chăm sóc người hiến máu',
              'skill_group' => 'y_te',
            ),
            1 => 
            array (
              'shift_id' => '205-D1-S2',
              'shift_name' => 'Ca Chiều (12:30 – 16:30)',
              'time_range' => '12:30 – 16:30',
              'capacity' => 25,
              'registered' => 25,
              'skill_req' => 'Điều phối xe lấy máu & Cấp giấy khen',
              'skill_group' => 'dieu_phoi',
            ),
          ),
        ),
      ),
    ),
    15 => 
    array (
      'id' => 206,
      'title' => 'Tập Huấn Kỹ Năng Sơ Cấp Cứu Ban Đầu & Hồi Sức Tim Phổi CPR Khu Dân Cư',
      'category' => 'hien_mau_y_te',
      'category_name' => 'Hiến Máu Nhân Đạo & Hỗ Trợ Y Tế',
      'date_string' => 'Chủ Nhật, 29/11/2026',
      'time_range' => '14:00 – 17:30',
      'location' => 'Nhà Sinh Hoạt Cộng Đồng Khu Đô Thị Linh Đàm, Quận Hoàng Mai, Hà Nội',
      'meeting_point' => 'Tầng 1 Nhà NƠ 03, Bán đảo Linh Đàm',
      'capacity' => 25,
      'registered' => 20,
      'leader_name' => 'Lê Nguyễn Phương Thảo',
      'leader_phone' => '0988.555.777',
      'shifts' => 
      array (
        0 => 'Ca Chiều Huấn Luyện (14:00 – 17:30)',
      ),
      'skills_needed' => 
      array (
        0 => 'Chứng chỉ sơ cấp cứu CPR',
        1 => 'Kỹ năng sư phạm truyền đạt',
        2 => 'Thao tác mô hình thực hành',
      ),
      'benefits' => 
      array (
        0 => 'Chứng chỉ huấn luyện viên sơ cấp cứu',
        1 => 'Túi sơ cứu mini cầm tay',
      ),
      'summary' => 'Hướng dẫn trực quan cho 200 người dân chung cư cách xử trí hóc dị vật Heimlich, ép tim thổi ngạt và cố định gãy xương khi có cháy nổ.',
      'status' => 'recruiting',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Chủ Nhật, 29/11/2026',
          'full_label' => 'Thứ Bảy (Chủ Nhật, 29/11/2026)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '206-D1-S1',
              'shift_name' => 'Ca Chiều Huấn Luyện (14:00 – 17:30)',
              'time_range' => '14:00 – 17:30',
              'capacity' => 25,
              'registered' => 20,
              'skill_req' => 'Thực hành ép tim thổi ngạt trên mô hình',
              'skill_group' => 'y_te',
            ),
          ),
        ),
      ),
    ),
    16 => 
    array (
      'id' => 207,
      'title' => 'Chăm Sóc & Đẩy Xe Lăn Tiếp Đón Bệnh Nhân Chạy Thận Viện Bạch Mai',
      'category' => 'hien_mau_y_te',
      'category_name' => 'Hiến Máu Nhân Đạo & Hỗ Trợ Y Tế',
      'date_string' => 'Thứ Ba & Thứ Năm hàng tuần',
      'time_range' => '07:00 – 11:30',
      'location' => 'Khoa Thận Nhân Tạo, Bệnh viện Bạch Mai, Đống Đa, Hà Nội',
      'meeting_point' => 'Cổng Khoa Thận Tầng 1 Nhà Việt Nhật',
      'capacity' => 15,
      'registered' => 6,
      'leader_name' => 'Lê Nguyễn Phương Thảo',
      'leader_phone' => '0988.555.777',
      'shifts' => 
      array (
        0 => 'Ca Sáng (07:00 – 11:30)',
      ),
      'skills_needed' => 
      array (
        0 => 'Kiên nhẫn nhẹ nhàng',
        1 => 'Sức khỏe tốt để nâng đỡ người bệnh',
        2 => 'Thấu cảm',
      ),
      'benefits' => 
      array (
        0 => 'Hỗ trợ ăn trưa tại căng tin viện',
        1 => 'Áo blu tình nguyện viên',
        2 => 'Chứng nhận giờ công tác viện',
      ),
      'summary' => 'Hỗ trợ nâng đỡ các bác bệnh nhân suy thận giai đoạn cuối lên giường lọc máu, đẩy xe lăn ra cổng đón xe buýt về quê. Cần thêm TNV nam hỗ trợ!',
      'status' => 'recruiting',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Thứ Ba & Thứ Năm hàng tuần',
          'full_label' => 'Thứ Bảy (Thứ Ba & Thứ Năm hàng tuần)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '207-D1-S1',
              'shift_name' => 'Ca Sáng (07:00 – 11:30)',
              'time_range' => '07:00 – 11:30',
              'capacity' => 15,
              'registered' => 6,
              'skill_req' => 'Đẩy xe lăn, Nâng đỡ người bệnh thận',
              'skill_group' => 'y_te',
            ),
          ),
        ),
      ),
    ),
    17 => 
    array (
      'id' => 208,
      'title' => 'Tầm Soát Ung Thư & Tuyên Truyền Sức Khỏe Sinh Sản Phụ Nữ Ngoại Thành Đông Anh',
      'category' => 'hien_mau_y_te',
      'category_name' => 'Hiến Máu Nhân Đạo & Hỗ Trợ Y Tế',
      'date_string' => 'Thứ Bảy, 05/12/2026',
      'time_range' => '08:00 – 16:00',
      'location' => 'Trung Tâm Y Tế Huyện Đông Anh, Hà Nội',
      'meeting_point' => 'Cầu Nhật Tân (Xe đón tập trung)',
      'capacity' => 20,
      'registered' => 18,
      'leader_name' => 'Lê Nguyễn Phương Thảo',
      'leader_phone' => '0988.555.777',
      'shifts' => 
      array (
        0 => 'Ca Cả Ngày (08:00 – 16:00)',
      ),
      'skills_needed' => 
      array (
        0 => 'Nữ sinh viên Y dược',
        1 => 'Giao tiếp tâm lý tinh tế',
        2 => 'Nhập liệu hồ sơ bệnh án',
      ),
      'benefits' => 
      array (
        0 => 'Hỗ trợ chi phí đi lại',
        1 => 'Bữa trưa công đoàn',
        2 => 'Giấy khen Huyện đoàn',
      ),
      'summary' => 'Hỗ trợ bác sĩ sản khoa thăm khám tầm soát ung thư cổ tử cung và phát tờ rơi giáo dục sức khỏe cho 400 phụ nữ lao động nông thôn.',
      'status' => 'recruiting',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Thứ Bảy, 05/12/2026',
          'full_label' => 'Thứ Bảy (Thứ Bảy, 05/12/2026)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '208-D1-S1',
              'shift_name' => 'Ca Cả Ngày (08:00 – 16:00)',
              'time_range' => '08:00 – 16:00',
              'capacity' => 20,
              'registered' => 18,
              'skill_req' => 'Hỗ trợ bàn khám phụ khoa & Nhập liệu',
              'skill_group' => 'y_te',
            ),
          ),
        ),
      ),
    ),
    18 => 
    array (
      'id' => 209,
      'title' => 'Khám Nha Khoa Học Đường & Tặng Bàn Chải Kem Đánh Răng Cho Học Sinh Sa Pa',
      'category' => 'hien_mau_y_te',
      'category_name' => 'Hiến Máu Nhân Đạo & Hỗ Trợ Y Tế',
      'date_string' => 'Thứ Sáu & Thứ Bảy (11/12 – 12/12/2026)',
      'time_range' => '07:30 – 17:00',
      'location' => 'Trường PTDT Bán Trú Tiểu Học Tả Van, Sa Pa, Tỉnh Lào Cai',
      'meeting_point' => 'Bến xe Mỹ Đình, Hà Nội (Xe khách giường nằm)',
      'capacity' => 25,
      'registered' => 24,
      'leader_name' => 'Bác sĩ trẻ Trần Tuấn Anh',
      'leader_phone' => '0988.555.772',
      'shifts' => 
      array (
        0 => 'Ca Khám Răng Học Đường (08:00 – 16:30)',
      ),
      'skills_needed' => 
      array (
        0 => 'Bác sĩ Răng Hàm Mặt / Điều dưỡng',
        1 => 'Yêu trẻ nhỏ',
        2 => 'Chịu rét tốt',
      ),
      'benefits' => 
      array (
        0 => 'Vé xe khứ hồi Hà Nội – Sa Pa',
        1 => 'Chỗ ở homestay bản làng',
        2 => 'Áo ấm tình nguyện D2L',
      ),
      'summary' => 'Kiểm tra sâu răng, hàn răng miễn phí và hướng dẫn 600 em nhỏ người Mông, Dao cách chải răng đúng cách phòng ngừa bệnh răng miệng.',
      'status' => 'recruiting',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Thứ Sáu & Thứ Bảy (11/12 – 12/12/2026)',
          'full_label' => 'Thứ Bảy (Thứ Sáu & Thứ Bảy (11/12 – 12/12/2026))',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '209-D1-S1',
              'shift_name' => 'Ca Khám Răng Học Đường (08:00 – 16:30)',
              'time_range' => '08:00 – 16:30',
              'capacity' => 25,
              'registered' => 24,
              'skill_req' => 'Khám răng, Bôi fluor & Tặng kem đánh răng',
              'skill_group' => 'y_te',
            ),
          ),
        ),
      ),
    ),
    19 => 
    array (
      'id' => 210,
      'title' => 'Tủ Thuốc Biên Cương — Tiếp Nhận Dược Phẩm & Băng Gạc Cho Đồn Biên Phòng Mường Khương',
      'category' => 'hien_mau_y_te',
      'category_name' => 'Hiến Máu Nhân Đạo & Hỗ Trợ Y Tế',
      'date_string' => 'Hoàn thành đợt vận động Tháng 10/2026',
      'time_range' => 'Nhiệm vụ đóng gói và bàn giao hoàn tất',
      'location' => 'Đồn Biên Phòng Bản Lầu, Huyện Mường Khương, Tỉnh Lào Cai',
      'meeting_point' => 'Kho Dược Viện Y Học Cổ Truyền Trung Ương',
      'capacity' => 15,
      'registered' => 15,
      'leader_name' => 'Lê Nguyễn Phương Thảo',
      'leader_phone' => '0988.555.777',
      'shifts' => 
      array (
        0 => 'Ca Đóng Gói Thuốc (Đã xong)',
      ),
      'skills_needed' => 
      array (
        0 => 'Dược sĩ',
        1 => 'Đóng gói thuốc chuẩn GSP',
        2 => 'Kiểm tra hạn sử dụng',
      ),
      'benefits' => 
      array (
        0 => 'Bằng khen của Bộ Chỉ Huy Bộ Đội Biên Phòng',
        1 => 'Huy hiệu Vì Chủ Quyền Biên Giới',
      ),
      'summary' => 'Tiếp nhận, phân loại và trao tặng 50 tủ thuốc dã ngoại cùng 2.000 cuộn băng gạc y tế cho chiến sĩ biên phòng tuần tra biên giới.',
      'status' => 'completed',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Hoàn thành đợt vận động Tháng 10/2026',
          'full_label' => 'Thứ Bảy (Hoàn thành đợt vận động Tháng 10/2026)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '210-D1-S1',
              'shift_name' => 'Ca Đóng Gói Thuốc (Đã xong)',
              'time_range' => '08:00 – 17:00',
              'capacity' => 15,
              'registered' => 15,
              'skill_req' => 'Đóng gói dược phẩm biên cương',
              'skill_group' => 'y_te',
            ),
          ),
        ),
      ),
    ),
    20 => 
    array (
      'id' => 301,
      'title' => 'Tiếp Sức Mùa Thi Thủ Đô — Phân Luồng Giao Thông & Đội Xe Ôm Tình Nguyện',
      'category' => 'tiep_suc_giao_duc',
      'category_name' => 'Tiếp Sức Mùa Thi & Giáo Dục Cộng Đồng',
      'date_string' => 'Thứ Năm & Thứ Sáu (05/11 – 06/11/2026)',
      'time_range' => '06:00 – 17:30 (Chia 2 ca Sáng/Chiều)',
      'location' => 'Điểm thi THPT Chu Văn An & ĐH Sư Phạm Hà Nội, Cầu Giấy, Hà Nội',
      'meeting_point' => 'Cổng Trường ĐH Sư Phạm Hà Nội, 136 Xuân Thủy',
      'capacity' => 50,
      'registered' => 42,
      'leader_name' => 'Trần Hoàng Nam',
      'leader_phone' => '0912.456.789',
      'shifts' => 
      array (
        0 => 'Ca Sáng Làm Thủ Tục (06:30 – 11:30)',
        1 => 'Ca Chiều Hỗ Trợ Xe Máy (13:30 – 17:30)',
        2 => 'Ca Sáng Thi Chính Thức (06:00 – 11:30)',
        3 => 'Ca Chiều Giữ Trật Tự (13:00 – 17:30)',
      ),
      'skills_needed' => 
      array (
        0 => 'Có xe máy & Bằng lái xe',
        1 => 'Điều tiết giao thông',
        2 => 'Nhanh nhẹn hoạt bát',
        3 => 'Kỹ năng sơ cứu',
      ),
      'benefits' => 
      array (
        0 => 'Áo xanh Tiếp Sức Mùa Thi',
        1 => 'Mũ bảo hiểm đạt chuẩn',
        2 => 'Hỗ trợ thẻ xăng xe 100k/ngày',
        3 => 'Nước uống & Cơm trưa',
      ),
      'summary' => 'Điều phối chống ùn tắc giao thông cổng trường thi, đưa đón miễn phí các sĩ tử bị sự cố hỏng xe hoặc quên giấy tờ thi kịp giờ làm bài.',
      'status' => 'recruiting',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Thứ Năm & Thứ Sáu (05/11 – 06/11/2026)',
          'full_label' => 'Thứ Bảy (Thứ Năm & Thứ Sáu (05/11 – 06/11/2026))',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '301-D1-S1',
              'shift_name' => 'Ca Sáng Làm Thủ Tục (06:30 – 11:30)',
              'time_range' => '06:30 – 11:30',
              'capacity' => 20,
              'registered' => 15,
              'skill_req' => 'Phân luồng giao thông & Hướng dẫn phòng thi',
              'skill_group' => 'dieu_phoi',
            ),
            1 => 
            array (
              'shift_id' => '301-D1-S2',
              'shift_name' => 'Ca Chiều Hỗ Trợ Xe Máy (13:30 – 17:30)',
              'time_range' => '13:30 – 17:30',
              'capacity' => 20,
              'registered' => 14,
              'skill_req' => 'Đội xe máy tình nguyện đưa đón sĩ tử',
              'skill_group' => 'dieu_phoi',
            ),
          ),
        ),
        1 => 
        array (
          'day_code' => 'day_2',
          'day_name' => 'Chủ Nhật',
          'date_string' => 'Thứ Năm & Thứ Sáu (05/11 – 06/11/2026)',
          'full_label' => 'Chủ Nhật (Thứ Năm & Thứ Sáu (05/11 – 06/11/2026))',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '301-D2-S1',
              'shift_name' => 'Ca Sáng Thi Chính Thức (06:00 – 11:30)',
              'time_range' => '06:00 – 11:30',
              'capacity' => 25,
              'registered' => 21,
              'skill_req' => 'Phát nước mát, Bánh ăn nhẹ & Cổ vũ',
              'skill_group' => 'hau_can',
            ),
            1 => 
            array (
              'shift_id' => '301-D2-S2',
              'shift_name' => 'Ca Chiều Giữ Trật Tự (13:00 – 17:30)',
              'time_range' => '13:00 – 17:30',
              'capacity' => 20,
              'registered' => 16,
              'skill_req' => 'Giữ trật tự khu vực thi & Hướng dẫn phụ huynh',
              'skill_group' => 'dieu_phoi',
            ),
          ),
        ),
      ),
    ),
    21 => 
    array (
      'id' => 302,
      'title' => 'Lớp Học Cầu Vồng — Phổ Cập Tin Học & Dạy Lập Trình Scratch Cho Trẻ Mồ Côi',
      'category' => 'tiep_suc_giao_duc',
      'category_name' => 'Tiếp Sức Mùa Thi & Giáo Dục Cộng Đồng',
      'date_string' => 'Thứ Bảy & Chủ Nhật hàng tuần',
      'time_range' => '08:30 – 11:30',
      'location' => 'Làng Trẻ Em SOS Hà Nội, Số 2 Doãn Kế Thiện, Mai Dịch, Cầu Giấy',
      'meeting_point' => 'Phòng Máy Tính Tầng 2 Nhà Hiệu Bộ SOS',
      'capacity' => 15,
      'registered' => 12,
      'leader_name' => 'Trịnh Quốc Bảo',
      'leader_phone' => '0977.111.005',
      'shifts' => 
      array (
        0 => 'Ca Sáng Dạy Tin Học (08:30 – 11:30)',
      ),
      'skills_needed' => 
      array (
        0 => 'Sinh viên CNTT / Sư phạm',
        1 => 'Kỹ năng sư phạm kiên nhẫn',
        2 => 'Biết lập trình Scratch/Python',
      ),
      'benefits' => 
      array (
        0 => 'Chứng nhận Gia sư công nghệ',
        1 => 'Thư cảm ơn từ Ban Giám đốc Làng SOS',
        2 => 'Phụ cấp tài liệu giảng dạy',
      ),
      'summary' => 'Dạy 40 em nhỏ mồ côi làm quen với máy tính, đánh máy 10 ngón và sáng tạo các trò chơi hoạt hình vui nhộn bằng Scratch.',
      'status' => 'recruiting',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Thứ Bảy & Chủ Nhật hàng tuần',
          'full_label' => 'Thứ Bảy (Thứ Bảy & Chủ Nhật hàng tuần)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '302-D1-S1',
              'shift_name' => 'Ca Sáng Dạy Tin Học (08:30 – 11:30)',
              'time_range' => '08:30 – 11:30',
              'capacity' => 15,
              'registered' => 12,
              'skill_req' => 'Gia sư Scratch & Tin học căn bản cho trẻ',
              'skill_group' => 'su_pham',
            ),
          ),
        ),
      ),
    ),
    22 => 
    array (
      'id' => 303,
      'title' => 'Tủ Sách Vùng Cao — Thu Gom 5.000 Đầu Sách & Dựng Thư Viện Thân Thiện Mộc Châu',
      'category' => 'tiep_suc_giao_duc',
      'category_name' => 'Tiếp Sức Mùa Thi & Giáo Dục Cộng Đồng',
      'date_string' => 'Thứ Sáu đến Chủ Nhật (18/12 – 20/12/2026)',
      'time_range' => '07:00 – 18:00',
      'location' => 'Trường Tiểu Học Chiềng Hắc, Huyện Mộc Châu, Tỉnh Sơn La',
      'meeting_point' => 'Cổng Đại học Sư Phạm Hà Nội (Xe đón)',
      'capacity' => 30,
      'registered' => 30,
      'leader_name' => 'Vũ Thị Minh Hằng',
      'leader_phone' => '0977.111.006',
      'shifts' => 
      array (
        0 => 'Ca Lắp Kệ & Phân Loại Sách (08:00 – 16:30)',
      ),
      'skills_needed' => 
      array (
        0 => 'Phân loại sách thư viện',
        1 => 'Sơn vẽ trang trí tường',
        2 => 'Đóng kệ gỗ đơn giản',
      ),
      'benefits' => 
      array (
        0 => 'Trải nghiệm văn hóa Thái Tây Bắc',
        1 => 'Cơm lam thịt nướng',
        2 => 'Kỷ niệm chương Tủ Sách Hy Vọng',
      ),
      'summary' => 'Vận chuyển 5.000 cuốn sách thiếu nhi, sơn vẽ trang trí lại phòng học cũ thành thư viện mở sắc màu cho 350 học sinh nghèo. Đã đủ quân số!',
      'status' => 'full',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Thứ Sáu đến Chủ Nhật (18/12 – 20/12/2026)',
          'full_label' => 'Thứ Bảy (Thứ Sáu đến Chủ Nhật (18/12 – 20/12/2026))',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '303-D1-S1',
              'shift_name' => 'Ca Lắp Kệ & Phân Loại Sách (08:00 – 16:30)',
              'time_range' => '08:00 – 16:30',
              'capacity' => 30,
              'registered' => 30,
              'skill_req' => 'Đóng giá sách, Vẽ tường & Xếp mã thư viện',
              'skill_group' => 'su_pham',
            ),
          ),
        ),
      ),
    ),
    23 => 
    array (
      'id' => 304,
      'title' => 'Gia Sư Áo Xanh — Bồi Dưỡng Toán & Tiếng Anh Cho Con Em Công Nhân KCN Thăng Long',
      'category' => 'tiep_suc_giao_duc',
      'category_name' => 'Tiếp Sức Mùa Thi & Giáo Dục Cộng Đồng',
      'date_string' => 'Tối Thứ Hai, Thứ Tư, Thứ Sáu hàng tuần',
      'time_range' => '18:30 – 20:30',
      'location' => 'Khu Nhà Trọ Công Nhân Kim Chung, Huyện Đông Anh, Hà Nội',
      'meeting_point' => 'Nhà văn hóa Thôn Hậu Dưỡng, Kim Chung, Đông Anh',
      'capacity' => 20,
      'registered' => 8,
      'leader_name' => 'Nguyễn Khánh Linh',
      'leader_phone' => '0977.111.007',
      'shifts' => 
      array (
        0 => 'Ca Tối Dạy Kèm (18:30 – 20:30)',
      ),
      'skills_needed' => 
      array (
        0 => 'Kèm Toán, Tiếng Anh cấp 1 & 2',
        1 => 'Nhiệt tình với trẻ',
        2 => 'Đi lại buổi tối thuận tiện',
      ),
      'benefits' => 
      array (
        0 => 'Hỗ trợ lộ phí xe buýt',
        1 => 'Giáo trình chuẩn sư phạm',
        2 => 'Chứng nhận công tác Đoàn',
      ),
      'summary' => 'Dạy kèm buổi tối giúp các em học sinh có bố mẹ làm ca đêm tại khu công nghiệp theo kịp chương trình học trên lớp. Đang thiếu giáo viên Tiếng Anh nghiêm trọng!',
      'status' => 'recruiting',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Tối Thứ Hai, Thứ Tư, Thứ Sáu hàng tuần',
          'full_label' => 'Thứ Bảy (Tối Thứ Hai, Thứ Tư, Thứ Sáu hàng tuần)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '304-D1-S1',
              'shift_name' => 'Ca Tối Dạy Kèm (18:30 – 20:30)',
              'time_range' => '18:30 – 20:30',
              'capacity' => 20,
              'registered' => 8,
              'skill_req' => 'Kèm cặp môn Toán & Tiếng Anh lớp 6-9',
              'skill_group' => 'su_pham',
            ),
          ),
        ),
      ),
    ),
    24 => 
    array (
      'id' => 305,
      'title' => 'Tiếng Anh Cộng Đồng 0 Đồng — Giao Tiếp Phản Xạ Cho Học Sinh Ngoại Thành Chương Mỹ',
      'category' => 'tiep_suc_giao_duc',
      'category_name' => 'Tiếp Sức Mùa Thi & Giáo Dục Cộng Đồng',
      'date_string' => 'Chủ Nhật hàng tuần',
      'time_range' => '08:00 – 11:30',
      'location' => 'Trường THCS Thủy Xuân Tiên, Huyện Chương Mỹ, Hà Nội',
      'meeting_point' => 'Bến xe Yên Nghĩa, Hà Đông (Xe buýt tuyến 72)',
      'capacity' => 20,
      'registered' => 17,
      'leader_name' => 'Phan Thanh Tùng',
      'leader_phone' => '0977.111.008',
      'shifts' => 
      array (
        0 => 'Ca Sáng (08:00 – 11:30)',
      ),
      'skills_needed' => 
      array (
        0 => 'Tiếng Anh IELTS 6.5+ hoặc tương đương',
        1 => 'Tổ chức trò chơi tập thể',
        2 => 'Tự tin phát âm chuẩn',
      ),
      'benefits' => 
      array (
        0 => 'Tài liệu giảng dạy Oxford',
        1 => 'Bữa ăn nhẹ giữa giờ',
        2 => 'Chứng chỉ tình nguyện viên giáo dục',
      ),
      'summary' => 'Phá vỡ rào cản sợ nói tiếng Anh của học sinh ngoại thành thông qua các buổi kịch nghệ, hát tiếng Anh và trò chơi đố chữ.',
      'status' => 'recruiting',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Chủ Nhật hàng tuần',
          'full_label' => 'Thứ Bảy (Chủ Nhật hàng tuần)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '305-D1-S1',
              'shift_name' => 'Ca Sáng (08:00 – 11:30)',
              'time_range' => '08:00 – 11:30',
              'capacity' => 20,
              'registered' => 17,
              'skill_req' => 'Tổ chức sinh hoạt Tiếng Anh phản xạ',
              'skill_group' => 'su_pham',
            ),
          ),
        ),
      ),
    ),
    25 => 
    array (
      'id' => 306,
      'title' => 'Mở Rộng Tầm Mắt — Phiên Dịch Ngôn Ngữ Ký Hiệu & Đọc Sách Cho Trẻ Khiếm Thị',
      'category' => 'tiep_suc_giao_duc',
      'category_name' => 'Tiếp Sức Mùa Thi & Giáo Dục Cộng Đồng',
      'date_string' => 'Thứ Bảy, 05/12/2026',
      'time_range' => '08:30 – 11:30',
      'location' => 'Trường Phổ Thông Cơ Sở Nguyễn Đình Chiểu, Số 67 Lạc Trung, Hai Bà Trưng',
      'meeting_point' => 'Thư viện chữ nổi Braille Trường Nguyễn Đình Chiểu',
      'capacity' => 8,
      'registered' => 7,
      'leader_name' => 'Nguyễn Khánh Linh',
      'leader_phone' => '0977.111.007',
      'shifts' => 
      array (
        0 => 'Ca Sáng Đọc Sách Diễn Cảm (08:30 – 11:30)',
      ),
      'skills_needed' => 
      array (
        0 => 'Giọng đọc truyền cảm, chuẩn tiếng Việt',
        1 => 'Biết ngôn ngữ ký hiệu cơ bản',
        2 => 'Kiên nhẫn',
      ),
      'benefits' => 
      array (
        0 => 'Tập huấn chuyên sâu ngôn ngữ ký hiệu',
        1 => 'Thu âm sách nói phát hành toàn quốc',
      ),
      'summary' => 'Đọc diễn cảm các tác phẩm văn học thiếu nhi kinh điển và hỗ trợ trẻ em khiếm thính giao tiếp thông qua ngôn ngữ cử chỉ bàn tay.',
      'status' => 'recruiting',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Thứ Bảy, 05/12/2026',
          'full_label' => 'Thứ Bảy (Thứ Bảy, 05/12/2026)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '306-D1-S1',
              'shift_name' => 'Ca Sáng Đọc Sách Diễn Cảm (08:30 – 11:30)',
              'time_range' => '08:30 – 11:30',
              'capacity' => 8,
              'registered' => 7,
              'skill_req' => 'Thu âm sách nói & Phiên dịch ký hiệu tay',
              'skill_group' => 'su_pham',
            ),
          ),
        ),
      ),
    ),
    26 => 
    array (
      'id' => 307,
      'title' => 'Hành Trang Vào Lớp 1 — Dạy Đánh Vần Cho Con Em Dân Tộc Chăm Tỉnh Ninh Thuận',
      'category' => 'tiep_suc_giao_duc',
      'category_name' => 'Tiếp Sức Mùa Thi & Giáo Dục Cộng Đồng',
      'date_string' => 'Thứ Bảy, 19/12/2026',
      'time_range' => '07:30 – 16:30',
      'location' => 'Làng Chăm Hữu Đức, Xã Phước Hữu, Huyện Ninh Phước, Ninh Thuận',
      'meeting_point' => 'Trường Tiểu Học Phước Hữu',
      'capacity' => 15,
      'registered' => 15,
      'leader_name' => 'Trương Gia Bảo',
      'leader_phone' => '0938.445.566',
      'shifts' => 
      array (
        0 => 'Ca Cả Ngày (07:30 – 16:30)',
      ),
      'skills_needed' => 
      array (
        0 => 'Sư phạm mầm non / Tiểu học',
        1 => 'Vẽ tranh minh họa',
        2 => 'Hát múa thiếu nhi',
      ),
      'benefits' => 
      array (
        0 => 'Bữa ăn trưa truyền thống người Chăm',
        1 => 'Vải thổ cẩm lưu niệm',
        2 => 'Giấy khen ngành GD',
      ),
      'summary' => 'Dạy tiếng Việt tăng cường cho 100 em nhỏ trước khi vào lớp 1 giúp các em tự tin hòa nhập trường học. Đã đủ quân số tham gia!',
      'status' => 'full',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Thứ Bảy, 19/12/2026',
          'full_label' => 'Thứ Bảy (Thứ Bảy, 19/12/2026)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '307-D1-S1',
              'shift_name' => 'Ca Cả Ngày (07:30 – 16:30)',
              'time_range' => '07:30 – 16:30',
              'capacity' => 15,
              'registered' => 15,
              'skill_req' => 'Dạy chữ cái & Đánh vần tiếng Việt',
              'skill_group' => 'su_pham',
            ),
          ),
        ),
      ),
    ),
    27 => 
    array (
      'id' => 308,
      'title' => 'Tiếp Sức Tân Sinh Viên Hoàn Cảnh Khó Khăn — Tìm Nhà Trọ Giá Rẻ & Làm Thẻ Xe Buýt',
      'category' => 'tiep_suc_giao_duc',
      'category_name' => 'Tiếp Sức Mùa Thi & Giáo Dục Cộng Đồng',
      'date_string' => 'Thứ Bảy & Chủ Nhật (26/12 – 27/12/2026)',
      'time_range' => '08:00 – 17:00',
      'location' => 'Bến xe Mỹ Đình & Bến xe Giáp Bát, Hà Nội',
      'meeting_point' => 'Cửa ngõ Bến xe Mỹ Đình, Đường Phạm Hùng',
      'capacity' => 40,
      'registered' => 38,
      'leader_name' => 'Trần Hoàng Nam',
      'leader_phone' => '0912.456.789',
      'shifts' => 
      array (
        0 => 'Ca Đón Bến Xe Mỹ Đình (08:00 – 12:30)',
        1 => 'Ca Đón Bến Xe Giáp Bát (08:00 – 12:30)',
      ),
      'skills_needed' => 
      array (
        0 => 'Thông thạo đường sá Hà Nội',
        1 => 'Cảnh giác lừa đảo trọ',
        2 => 'Nhiệt tình hướng dẫn',
      ),
      'benefits' => 
      array (
        0 => 'Áo TNV D2L',
        1 => 'Hỗ trợ xăng xe máy 80k/ngày',
        2 => 'Nước uống đóng chai',
      ),
      'summary' => 'Khảo sát 200 nhà trọ an toàn không tăng giá điện nước, đón tân sinh viên vùng quê chân ướt chân ráo lên nhập học tránh bẫy đa cấp.',
      'status' => 'recruiting',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Thứ Bảy & Chủ Nhật (26/12 – 27/12/2026)',
          'full_label' => 'Thứ Bảy (Thứ Bảy & Chủ Nhật (26/12 – 27/12/2026))',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '308-D1-S1',
              'shift_name' => 'Ca Đón Bến Xe Mỹ Đình (08:00 – 12:30)',
              'time_range' => '08:00 – 12:30',
              'capacity' => 20,
              'registered' => 19,
              'skill_req' => 'Hướng dẫn tuyến xe buýt & Dẫn xem trọ',
              'skill_group' => 'dieu_phoi',
            ),
          ),
        ),
        1 => 
        array (
          'day_code' => 'day_2',
          'day_name' => 'Chủ Nhật',
          'date_string' => 'Thứ Bảy & Chủ Nhật (26/12 – 27/12/2026)',
          'full_label' => 'Chủ Nhật (Thứ Bảy & Chủ Nhật (26/12 – 27/12/2026))',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '308-D2-S1',
              'shift_name' => 'Ca Đón Bến Xe Giáp Bát (08:00 – 12:30)',
              'time_range' => '08:00 – 12:30',
              'capacity' => 20,
              'registered' => 19,
              'skill_req' => 'Phát bản đồ trọ an ninh & Làm vé xe buýt',
              'skill_group' => 'dieu_phoi',
            ),
          ),
        ),
      ),
    ),
    28 => 
    array (
      'id' => 309,
      'title' => 'Gom Máy Tính Cũ Trao Tặng Điểm Trường Vùng Sâu Huyện Nam Trà My',
      'category' => 'tiep_suc_giao_duc',
      'category_name' => 'Tiếp Sức Mùa Thi & Giáo Dục Cộng Đồng',
      'date_string' => 'Chiến dịch Tháng 10/2026',
      'time_range' => 'Hoàn thành bàn giao phòng máy',
      'location' => 'Trường Tiểu Học Trà Dơn, Huyện Nam Trà My, Tỉnh Quảng Nam',
      'meeting_point' => 'UBND Huyện Nam Trà My',
      'capacity' => 12,
      'registered' => 12,
      'leader_name' => 'Trịnh Quốc Bảo',
      'leader_phone' => '0977.111.005',
      'shifts' => 
      array (
        0 => 'Lắp Đặt Phòng Tin Học (Đã xong)',
      ),
      'skills_needed' => 
      array (
        0 => 'Sửa chữa phần cứng máy tính',
        1 => 'Cài đặt Windows/Linux',
        2 => 'Đi dây mạng LAN',
      ),
      'benefits' => 
      array (
        0 => 'Bằng khen của Sở Giáo Dục Quảng Nam',
        1 => 'Giao lưu văn hóa sâm Ngọc Linh',
      ),
      'summary' => 'Tân trang 25 bộ máy tính cũ của các doanh nghiệp quyên góp, lắp đặt hoàn chỉnh phòng máy có kết nối Internet vệ tinh cho học sinh vùng cao.',
      'status' => 'completed',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Chiến dịch Tháng 10/2026',
          'full_label' => 'Thứ Bảy (Chiến dịch Tháng 10/2026)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '309-D1-S1',
              'shift_name' => 'Lắp Đặt Phòng Tin Học (Đã xong)',
              'time_range' => '08:00 – 17:00',
              'capacity' => 12,
              'registered' => 12,
              'skill_req' => 'Kỹ thuật viên mạng & Phần cứng PC',
              'skill_group' => 'hau_can',
            ),
          ),
        ),
      ),
    ),
    29 => 
    array (
      'id' => 310,
      'title' => 'Học Kỳ Xanh Trải Nghiệm Khoa Học Cho Trẻ Em Trung Tâm Bảo Trợ Xã Hội',
      'category' => 'tiep_suc_giao_duc',
      'category_name' => 'Tiếp Sức Mùa Thi & Giáo Dục Cộng Đồng',
      'date_string' => 'Chủ Nhật, 10/01/2027',
      'time_range' => '08:00 – 16:30',
      'location' => 'Trung Tâm Bảo Trợ Xã Hội 3 Hà Nội, Phường Tây Mỗ, Quận Nam Từ Liêm',
      'meeting_point' => 'Sảnh Bảo Tàng Thiên Nhiên Việt Nam, 18 Hoàng Quốc Việt',
      'capacity' => 18,
      'registered' => 15,
      'leader_name' => 'Vũ Thị Minh Hằng',
      'leader_phone' => '0977.111.006',
      'shifts' => 
      array (
        0 => 'Ca Sáng Thí Nghiệm STEM (08:30 – 12:00)',
        1 => 'Ca Chiều Tham Quan (13:30 – 16:30)',
      ),
      'skills_needed' => 
      array (
        0 => 'Làm thí nghiệm STEM vui',
        1 => 'Quản trò vui nhộn',
        2 => 'Yêu thích khoa học',
      ),
      'benefits' => 
      array (
        0 => 'Vé tham quan bảo tàng',
        1 => 'Bộ kit thí nghiệm núi lửa mini',
        2 => 'Suất ăn buffet trưa',
      ),
      'summary' => 'Tổ chức ngày hội trải nghiệm kính thiên văn, làm tên lửa nước và xem hóa thạch khủng long cho 80 em nhỏ mồ côi.',
      'status' => 'recruiting',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Chủ Nhật, 10/01/2027',
          'full_label' => 'Thứ Bảy (Chủ Nhật, 10/01/2027)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '310-D1-S1',
              'shift_name' => 'Ca Sáng Thí Nghiệm STEM (08:30 – 12:00)',
              'time_range' => '08:30 – 12:00',
              'capacity' => 10,
              'registered' => 8,
              'skill_req' => 'Hướng dẫn làm tên lửa nước & Pha màu hóa học',
              'skill_group' => 'su_pham',
            ),
            1 => 
            array (
              'shift_id' => '310-D1-S2',
              'shift_name' => 'Ca Chiều Tham Quan (13:30 – 16:30)',
              'time_range' => '13:30 – 16:30',
              'capacity' => 8,
              'registered' => 7,
              'skill_req' => 'Thuyết minh bảo tàng thiên nhiên cho thiếu nhi',
              'skill_group' => 'su_pham',
            ),
          ),
        ),
      ),
    ),
    30 => 
    array (
      'id' => 401,
      'title' => 'Bếp Ăn 0 Đồng Hà Nội — Nấu & Trao Cơm Cho Bệnh Nhân Nghèo Viện Bạch Mai',
      'category' => 'cuu_tro_an_sinh',
      'category_name' => 'Cứu Trợ Khẩn Cấp & Bếp Ăn 0 Đồng',
      'date_string' => 'Thứ Bảy hàng tuần',
      'time_range' => '07:00 – 18:30 (Ca Sáng Nấu Cơm & Ca Chiều Phát Cơm)',
      'location' => 'Bếp Thiện Nguyện D2L, Ngõ 1 Lê Thanh Nghị, Hai Bà Trưng, Hà Nội',
      'meeting_point' => 'Cửa số 5 Viện Tim Mạch — Bệnh Viện Bạch Mai',
      'capacity' => 25,
      'registered' => 20,
      'leader_name' => 'Phạm Minh Quân',
      'leader_phone' => '0908.123.456',
      'shifts' => 
      array (
        0 => 'Ca Sáng Nấu Nướng (07:30 – 12:00)',
        1 => 'Ca Chiều Phát Cơm (14:30 – 18:30)',
      ),
      'skills_needed' => 
      array (
        0 => 'Sơ chế rau củ khối lượng lớn',
        1 => 'Nấu ăn bếp công nghiệp',
        2 => 'Chia suất ăn sạch sẽ',
        3 => 'Điều phối trật tự',
      ),
      'benefits' => 
      array (
        0 => 'Bữa ăn cùng đại gia đình thiện nguyện',
        1 => 'Áo Tạp Dề TNV Bếp',
        2 => 'Giấy chứng nhận công tác xã hội',
      ),
      'summary' => 'Nấu đỏ lửa 800 suất cơm nóng hổi, đầy đủ thịt cá rau xanh gửi tới tay bệnh nhân xóm chạy thận và người nhà cơ nhỡ.',
      'status' => 'recruiting',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Thứ Bảy hàng tuần',
          'full_label' => 'Thứ Bảy (Thứ Bảy hàng tuần)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '401-D1-S1',
              'shift_name' => 'Ca Sáng Nấu Nướng (07:30 – 12:00)',
              'time_range' => '07:30 – 12:00',
              'capacity' => 15,
              'registered' => 12,
              'skill_req' => 'Bếp trưởng, Đảo chảo lớn, Nhặt rau',
              'skill_group' => 'hau_can',
            ),
            1 => 
            array (
              'shift_id' => '401-D1-S2',
              'shift_name' => 'Ca Chiều Phát Cơm (14:30 – 18:30)',
              'time_range' => '14:30 – 18:30',
              'capacity' => 10,
              'registered' => 8,
              'skill_req' => 'Đóng hộp, Phát phiếu & Giữ khoảng cách trật tự',
              'skill_group' => 'dieu_phoi',
            ),
          ),
        ),
      ),
    ),
    31 => 
    array (
      'id' => 402,
      'title' => 'Đêm Nghĩa Tình Thủ Đô — Tuần Tra Trao Quà Đêm & Áo Ấm Cho Người Vô Gia Cư',
      'category' => 'cuu_tro_an_sinh',
      'category_name' => 'Cứu Trợ Khẩn Cấp & Bếp Ăn 0 Đồng',
      'date_string' => 'Đêm Thứ Bảy rạng sáng Chủ Nhật (22:00 – 01:30)',
      'time_range' => '22:00 – 01:30 (Ca Đêm Đặc Biệt)',
      'location' => 'Khu vực Ga Hà Nội, Phố Tràng Thi, Tràng Tiền, Vỉa hè Chợ Hôm',
      'meeting_point' => 'Tượng đài Lý Thái Tổ, Đường Đinh Tiên Hoàng, Hoàn Kiếm',
      'capacity' => 20,
      'registered' => 19,
      'leader_name' => 'Trần Hoàng Nam',
      'leader_phone' => '0912.456.789',
      'shifts' => 
      array (
        0 => 'Ca Đêm Tuần Tra (22:00 – 01:30)',
      ),
      'skills_needed' => 
      array (
        0 => 'Thức đêm tốt',
        1 => 'Có xe máy cá nhân',
        2 => 'Lịch thiệp tôn trọng người nghèo',
        3 => 'Kỹ năng an toàn đường phố',
      ),
      'benefits' => 
      array (
        0 => 'Áo phản quang đi đêm',
        1 => 'Xôi nóng bồi dưỡng đêm',
        2 => 'Đồng đội tuần tra bọc lót',
      ),
      'summary' => 'Trao tận tay 150 tấm chăn dạ ấm, sữa chua, bánh mì và dầu gió cho những mảnh đời co ro dưới mái hiên mùa đông Hà Nội.',
      'status' => 'recruiting',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Đêm Thứ Bảy rạng sáng Chủ Nhật (22:00 – 01:30)',
          'full_label' => 'Thứ Bảy (Đêm Thứ Bảy rạng sáng Chủ Nhật (22:00 – 01:30))',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '402-D1-S1',
              'shift_name' => 'Ca Đêm Tuần Tra (22:00 – 01:30)',
              'time_range' => '22:00 – 01:30',
              'capacity' => 20,
              'registered' => 19,
              'skill_req' => 'Lái xe máy cặp đôi, Trao quà nhẹ nhàng',
              'skill_group' => 'dieu_phoi',
            ),
          ),
        ),
      ),
    ),
    32 => 
    array (
      'id' => 403,
      'title' => 'Đội Phản Ứng Nhanh Cứu Trợ Bão Lũ Miền Trung — Xuồng Hơi Cứu Hộ & Vận Chuyển Lương Khô',
      'category' => 'cuu_tro_an_sinh',
      'category_name' => 'Cứu Trợ Khẩn Cấp & Bếp Ăn 0 Đồng',
      'date_string' => 'Lệnh xuất quân khẩn cấp mùa bão lũ',
      'time_range' => '05:00 – 21:00 (Chiến dịch thực địa vùng lũ)',
      'location' => 'Huyện Lệ Thủy & Quảng Ninh, Tỉnh Quảng Bình',
      'meeting_point' => 'Kho Cứu Trợ Thành Phố Đồng Hới, Quảng Bình',
      'capacity' => 40,
      'registered' => 12,
      'leader_name' => 'Phạm Minh Quân',
      'leader_phone' => '0908.123.456',
      'shifts' => 
      array (
        0 => 'Ca Xuồng Hơi Vượt Lũ (06:00 – 17:00)',
      ),
      'skills_needed' => 
      array (
        0 => 'Bơi lội xuất sắc',
        1 => 'Vận hành thuyền máy xuồng hơi',
        2 => 'Chịu sóng gió',
        3 => 'Khuân vác bao gạo 50kg',
      ),
      'benefits' => 
      array (
        0 => 'Áo phao cứu sinh cứu hộ chuyên dụng',
        1 => 'Bảo hiểm tai nạn mức cao',
        2 => 'Ăn ở tập trung',
      ),
      'summary' => 'Nước lũ chia cắt nhiều xã vùng trũng, cần khẩn cấp 28 tình nguyện viên nam có kỹ năng bơi lội và lái xuồng hơi để vận chuyển mì tôm, nước suối.',
      'status' => 'recruiting',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Lệnh xuất quân khẩn cấp mùa bão lũ',
          'full_label' => 'Thứ Bảy (Lệnh xuất quân khẩn cấp mùa bão lũ)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '403-D1-S1',
              'shift_name' => 'Ca Xuồng Hơi Vượt Lũ (06:00 – 17:00)',
              'time_range' => '06:00 – 17:00',
              'capacity' => 40,
              'registered' => 12,
              'skill_req' => 'Lái xuồng hơi, Vác bao gạo & Cứu người',
              'skill_group' => 'hau_can',
            ),
          ),
        ),
      ),
    ),
    33 => 
    array (
      'id' => 404,
      'title' => 'Siêu Thị Mini 0 Đồng — Phiếu Mua Hàng Nhu Yếu Phẩm Miễn Phí Cho Xóm Trọ Nghèo',
      'category' => 'cuu_tro_an_sinh',
      'category_name' => 'Cứu Trợ Khẩn Cấp & Bếp Ăn 0 Đồng',
      'date_string' => 'Chủ Nhật, 13/12/2026',
      'time_range' => '08:00 – 16:30',
      'location' => 'Sân Nhà Thi Đấu Hoàng Mai, Phường Định Công, Quận Hoàng Mai, Hà Nội',
      'meeting_point' => 'Cổng số 2 Nhà Thi Đấu Hoàng Mai',
      'capacity' => 30,
      'registered' => 28,
      'leader_name' => 'Nguyễn Hà My',
      'leader_phone' => '0933.789.012',
      'shifts' => 
      array (
        0 => 'Ca Sáng (08:00 – 12:00)',
        1 => 'Ca Chiều (13:00 – 16:30)',
      ),
      'skills_needed' => 
      array (
        0 => 'Thu ngân tính tiền quầy 0 đồng',
        1 => 'Sắp xếp hàng hóa lên kệ',
        2 => 'Niềm nở với bà con',
      ),
      'benefits' => 
      array (
        0 => 'Áo TNV Siêu Thị Yêu Thương',
        1 => 'Cơm trưa hộp tươi ngon',
        2 => 'Chứng nhận tham gia',
      ),
      'summary' => 'Bà con có hoàn cảnh đặc biệt cầm phiếu chọn miễn phí gạo, mắm, muối, trứng và sữa tươi theo nhu cầu gia đình.',
      'status' => 'recruiting',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Chủ Nhật, 13/12/2026',
          'full_label' => 'Thứ Bảy (Chủ Nhật, 13/12/2026)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '404-D1-S1',
              'shift_name' => 'Ca Sáng (08:00 – 12:00)',
              'time_range' => '08:00 – 12:00',
              'capacity' => 18,
              'registered' => 17,
              'skill_req' => 'Tiếp đón & Hướng dẫn bà con chọn đồ',
              'skill_group' => 'dieu_phoi',
            ),
            1 => 
            array (
              'shift_id' => '404-D1-S2',
              'shift_name' => 'Ca Chiều (13:00 – 16:30)',
              'time_range' => '13:00 – 16:30',
              'capacity' => 12,
              'registered' => 11,
              'skill_req' => 'Bổ sung hàng hóa lên kệ & Đóng gói',
              'skill_group' => 'hau_can',
            ),
          ),
        ),
      ),
    ),
    34 => 
    array (
      'id' => 405,
      'title' => 'Bữa Cơm Yêu Thương — Nấu 500 Suất Cơm Bệnh Viện Ung Bướu TP. Hồ Chí Minh',
      'category' => 'cuu_tro_an_sinh',
      'category_name' => 'Cứu Trợ Khẩn Cấp & Bếp Ăn 0 Đồng',
      'date_string' => 'Thứ Bảy, 19/12/2026',
      'time_range' => '06:00 – 13:00',
      'location' => 'Bếp Ăn Từ Thiện, Số 47 Nơ Trang Long, Phường 14, Quận Bình Thạnh, TP.HCM',
      'meeting_point' => 'Cổng Bệnh Viện Ung Bướu Cơ Sở 1, Bình Thạnh',
      'capacity' => 35,
      'registered' => 35,
      'leader_name' => 'Nguyễn Thanh Tùng',
      'leader_phone' => '0978.112.233',
      'shifts' => 
      array (
        0 => 'Ca Sáng Sớm (06:00 – 13:00)',
      ),
      'skills_needed' => 
      array (
        0 => 'Sơ chế cá thịt',
        1 => 'Chia khay inox thức ăn',
        2 => 'Vệ sinh bếp nấu',
      ),
      'benefits' => 
      array (
        0 => 'Cơm trưa ấm cúng cùng đội',
        1 => 'Nón vải và tạp dề',
        2 => 'Thư khen bệnh viện',
      ),
      'summary' => 'Chuẩn bị và phát tận tay các bệnh nhân ung bướu nghèo bữa cơm ấm nóng đủ dưỡng chất chống chọi bệnh tật. Đã tuyển đủ quân số.',
      'status' => 'full',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Thứ Bảy, 19/12/2026',
          'full_label' => 'Thứ Bảy (Thứ Bảy, 19/12/2026)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '405-D1-S1',
              'shift_name' => 'Ca Sáng Sớm (06:00 – 13:00)',
              'time_range' => '06:00 – 13:00',
              'capacity' => 35,
              'registered' => 35,
              'skill_req' => 'Nấu nướng & Phát cơm viện Ung Bướu',
              'skill_group' => 'hau_can',
            ),
          ),
        ),
      ),
    ),
    35 => 
    array (
      'id' => 406,
      'title' => 'Hơi Ấm Mùa Đông Vùng Cao — Đan Khăn Len & Vận Chuyển Chăn Ấm Lên Đỉnh Lũng Cú',
      'category' => 'cuu_tro_an_sinh',
      'category_name' => 'Cứu Trợ Khẩn Cấp & Bếp Ăn 0 Đồng',
      'date_string' => 'Thứ Năm đến Chủ Nhật (24/12 – 27/12/2026)',
      'time_range' => 'Chiến dịch dã ngoại trọn vẹn 4 ngày',
      'location' => 'Xã Lũng Cú & Xã Ma Lé, Huyện Đồng Văn, Tỉnh Hà Giang',
      'meeting_point' => 'Cột Cờ Quốc Gia Lũng Cú, Hà Giang',
      'capacity' => 25,
      'registered' => 25,
      'leader_name' => 'Trần Hoàng Nam',
      'leader_phone' => '0912.456.789',
      'shifts' => 
      array (
        0 => 'Toàn Khóa Đông Ấm (4 Ngày)',
      ),
      'skills_needed' => 
      array (
        0 => 'Chịu được giá rét sương muối',
        1 => 'Bốc dỡ hàng viện trợ',
        2 => 'Lái xe tải số sàn',
      ),
      'benefits' => 
      array (
        0 => 'Chiêm ngưỡng cực Bắc Tổ quốc',
        1 => 'Lưu trú đồn biên phòng',
        2 => 'Kỷ niệm chương Tình Nguyện Mùa Đông',
      ),
      'summary' => 'Chở 1.000 chiếc chăn bông siêu ấm và 500 đôi ủng đi tuyết trao cho các em nhỏ vùng cao đón gió mùa đông bắc. Đã đủ quân số!',
      'status' => 'full',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Thứ Năm đến Chủ Nhật (24/12 – 27/12/2026)',
          'full_label' => 'Thứ Bảy (Thứ Năm đến Chủ Nhật (24/12 – 27/12/2026))',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '406-D1-S1',
              'shift_name' => 'Toàn Khóa Đông Ấm (4 Ngày)',
              'time_range' => '06:00 – 18:00',
              'capacity' => 25,
              'registered' => 25,
              'skill_req' => 'Vác bao tải chăn bông qua dốc đá',
              'skill_group' => 'hau_can',
            ),
          ),
        ),
      ),
    ),
    36 => 
    array (
      'id' => 407,
      'title' => 'Túi Thuốc An Sinh & Bình Oxy Khẩn Cấp Cho F0 Người Già Neo Đơn',
      'category' => 'cuu_tro_an_sinh',
      'category_name' => 'Cứu Trợ Khẩn Cấp & Bếp Ăn 0 Đồng',
      'date_string' => 'Trực ban các ngày trong tuần',
      'time_range' => '08:00 – 17:30',
      'location' => 'Trạm Y Tế Lưu Động Quận Thanh Xuân, Hà Nội',
      'meeting_point' => 'Nhà văn hóa Quận Thanh Xuân, Đường Khuất Duy Tiến',
      'capacity' => 15,
      'registered' => 13,
      'leader_name' => 'Lê Nguyễn Phương Thảo',
      'leader_phone' => '0988.555.777',
      'shifts' => 
      array (
        0 => 'Ca Trực Bình Oxy (08:00 – 17:30)',
      ),
      'skills_needed' => 
      array (
        0 => 'Kỹ thuật viên y tế',
        1 => 'Biết đo nồng độ oxy SpO2',
        2 => 'Lái xe chở bình oxy mini',
      ),
      'benefits' => 
      array (
        0 => 'Đồ bảo hộ cấp 4',
        1 => 'Phụ cấp ca trực đặc biệt',
        2 => 'Thư tri ân ngành y tế',
      ),
      'summary' => 'Cung cấp bình thở oxy cấp tốc và túi thuốc điều trị tại nhà cho các cụ già neo đơn bị suy hô hấp cấp khi người thân đi vắng.',
      'status' => 'recruiting',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Trực ban các ngày trong tuần',
          'full_label' => 'Thứ Bảy (Trực ban các ngày trong tuần)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '407-D1-S1',
              'shift_name' => 'Ca Trực Bình Oxy (08:00 – 17:30)',
              'time_range' => '08:00 – 17:30',
              'capacity' => 15,
              'registered' => 13,
              'skill_req' => 'Giao bình oxy & Đo chỉ số SpO2',
              'skill_group' => 'y_te',
            ),
          ),
        ),
      ),
    ),
    37 => 
    array (
      'id' => 408,
      'title' => 'Chuyến Xe 0 Đồng Về Quê Đón Tết Cùng Công Nhân Xa Xứ',
      'category' => 'cuu_tro_an_sinh',
      'category_name' => 'Cứu Trợ Khẩn Cấp & Bếp Ăn 0 Đồng',
      'date_string' => '26 đến 28 Tháng Chạp (Tết Nguyên Đán)',
      'time_range' => '05:00 – 19:00',
      'location' => 'Bến xe Nước Ngầm & Bến xe Giáp Bát, Hoàng Mai, Hà Nội',
      'meeting_point' => 'Khu đỗ xe khách tuyến Miền Trung Bến xe Nước Ngầm',
      'capacity' => 50,
      'registered' => 48,
      'leader_name' => 'Phạm Minh Quân',
      'leader_phone' => '0908.123.456',
      'shifts' => 
      array (
        0 => 'Ca Sáng Tiễn Xe (05:00 – 12:00)',
        1 => 'Ca Chiều Tiễn Xe (12:30 – 19:00)',
      ),
      'skills_needed' => 
      array (
        0 => 'Sắp xếp hành lý khoang xe',
        1 => 'Kiểm tra vé xe & Căn cước công dân',
        2 => 'Tặng bánh chưng giò lụa',
      ),
      'benefits' => 
      array (
        0 => 'Hỗ trợ tiền lì xì Tết',
        1 => 'Bữa ăn ca đoàn viên',
        2 => 'Áo khoác đồng phục D2L',
      ),
      'summary' => 'Điều phối 30 chuyến xe khách 45 chỗ đưa hơn 1.200 công nhân may mặc, thợ xây nghèo về các tỉnh Thanh Hóa, Nghệ An, Hà Tĩnh sum họp gia đình.',
      'status' => 'recruiting',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => '26 đến 28 Tháng Chạp (Tết Nguyên Đán)',
          'full_label' => 'Thứ Bảy (26 đến 28 Tháng Chạp (Tết Nguyên Đán))',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '408-D1-S1',
              'shift_name' => 'Ca Sáng Tiễn Xe (05:00 – 12:00)',
              'time_range' => '05:00 – 12:00',
              'capacity' => 25,
              'registered' => 24,
              'skill_req' => 'Bốc xếp vali quà Tết & Xếp chỗ ngồi',
              'skill_group' => 'hau_can',
            ),
            1 => 
            array (
              'shift_id' => '408-D1-S2',
              'shift_name' => 'Ca Chiều Tiễn Xe (12:30 – 19:00)',
              'time_range' => '12:30 – 19:00',
              'capacity' => 25,
              'registered' => 24,
              'skill_req' => 'Phát bánh chưng & Nước uống cho khách',
              'skill_group' => 'dieu_phoi',
            ),
          ),
        ),
      ),
    ),
    38 => 
    array (
      'id' => 409,
      'title' => 'Hỗ Trợ Nông Dân Vùng Lũ Tiêu Thụ Cam Sành Kẹt Phà Miền Tây',
      'category' => 'cuu_tro_an_sinh',
      'category_name' => 'Cứu Trợ Khẩn Cấp & Bếp Ăn 0 Đồng',
      'date_string' => 'Thứ Bảy & Chủ Nhật, 09/01 – 10/01/2027',
      'time_range' => '07:30 – 17:00',
      'location' => 'Bến Ninh Kiều & Siêu thị Co.opmart Cần Thơ, TP. Cần Thơ',
      'meeting_point' => 'Cảng Tân Cảng Cái Cui, Quận Cái Răng, Cần Thơ',
      'capacity' => 20,
      'registered' => 7,
      'leader_name' => 'Võ Văn Hậu',
      'leader_phone' => '0918.234.567',
      'shifts' => 
      array (
        0 => 'Ca Cả Ngày Bốc Dỡ (07:30 – 17:00)',
      ),
      'skills_needed' => 
      array (
        0 => 'Khuân vác sọt trái cây',
        1 => 'Cân ký & Đóng túi lưới',
        2 => 'Bán hàng vui vẻ',
      ),
      'benefits' => 
      array (
        0 => 'Thưởng thức nước cam vắt thỏa thích',
        1 => 'Áo bà ba tình nguyện',
        2 => 'Cộng điểm xã hội',
      ),
      'summary' => 'Giải cứu 20 tấn cam sành rớt giá do lũ tràn đê, bán trực tiếp không qua thương lái giúp nhà vườn thu hồi vốn. Cần bổ sung gấp 13 TNV bốc dỡ!',
      'status' => 'recruiting',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Thứ Bảy & Chủ Nhật, 09/01 – 10/01/2027',
          'full_label' => 'Thứ Bảy (Thứ Bảy & Chủ Nhật, 09/01 – 10/01/2027)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '409-D1-S1',
              'shift_name' => 'Ca Cả Ngày Bốc Dỡ (07:30 – 17:00)',
              'time_range' => '07:30 – 17:00',
              'capacity' => 20,
              'registered' => 7,
              'skill_req' => 'Khuân sọt cam 30kg từ ghe lên bãi',
              'skill_group' => 'hau_can',
            ),
          ),
        ),
      ),
    ),
    39 => 
    array (
      'id' => 410,
      'title' => 'Khắc Phục Hậu Quả Lũ — Dọn Bùn & Khử Trùng Nguồn Nước Giếng Trường Học Kỳ Anh',
      'category' => 'cuu_tro_an_sinh',
      'category_name' => 'Cứu Trợ Khẩn Cấp & Bếp Ăn 0 Đồng',
      'date_string' => 'Hoàn thành thắng lợi Tháng 10/2026',
      'time_range' => 'Đợt cứu trợ kết thúc đúng tiến độ',
      'location' => 'Xã Kỳ Thượng, Huyện Kỳ Anh, Tỉnh Hà Tĩnh',
      'meeting_point' => 'UBND Xã Kỳ Thượng, Hà Tĩnh',
      'capacity' => 30,
      'registered' => 30,
      'leader_name' => 'Trần Hoàng Nam',
      'leader_phone' => '0912.456.789',
      'shifts' => 
      array (
        0 => 'Ca Nạo Vét Bùn Non (Đã xong)',
      ),
      'skills_needed' => 
      array (
        0 => 'Thao tác máy bơm nước bùn',
        1 => 'Khử khuẩn bằng Cloramin B',
        2 => 'Sức khỏe dẻo dai',
      ),
      'benefits' => 
      array (
        0 => 'Bằng khen Tỉnh đoàn Hà Tĩnh',
        1 => 'Tình cảm nồng ấm của thầy cô',
      ),
      'summary' => 'Nạo vét 15 giếng nước ăn bị ngập phù sa, cọ rửa 20 phòng học ngập bùn non đón 600 em học sinh trở lại trường an toàn.',
      'status' => 'completed',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Hoàn thành thắng lợi Tháng 10/2026',
          'full_label' => 'Thứ Bảy (Hoàn thành thắng lợi Tháng 10/2026)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '410-D1-S1',
              'shift_name' => 'Ca Nạo Vét Bùn Non (Đã xong)',
              'time_range' => '07:00 – 17:30',
              'capacity' => 30,
              'registered' => 30,
              'skill_req' => 'Thau rửa giếng & Rửa sạch bàn ghế',
              'skill_group' => 'hau_can',
            ),
          ),
        ),
      ),
    ),
    40 => 
    array (
      'id' => 501,
      'title' => 'Mái Ấm Tuổi Già — Thăm Khám, Xoa Bóp Xương Khớp & Nấu Bữa Cơm Cho Cụ Già Viện Dưỡng Lão',
      'category' => 'cham_soc_yeu_thuong',
      'category_name' => 'Chăm Sóc Người Cao Tuổi & Trẻ Em Khuyết Tật',
      'date_string' => 'Chủ Nhật, 01/11/2026',
      'time_range' => '08:00 – 16:30',
      'location' => 'Trung Tâm Chăm Sóc Người Cao Tuổi Thụy Phương, Bắc Từ Liêm, Hà Nội',
      'meeting_point' => 'Cổng Làng Thụy Phương, Đường Thụy Phương, Bắc Từ Liêm',
      'capacity' => 20,
      'registered' => 17,
      'leader_name' => 'Lê Nguyễn Phương Thảo',
      'leader_phone' => '0988.555.777',
      'shifts' => 
      array (
        0 => 'Ca Sáng Chăm Sóc Sức Khỏe (08:00 – 12:00)',
        1 => 'Ca Chiều Giao Lưu Văn Nghệ (13:00 – 16:30)',
      ),
      'skills_needed' => 
      array (
        0 => 'Kỹ thuật xoa bóp bấm huyệt cơ bản',
        1 => 'Lắng nghe tâm sự người già',
        2 => 'Nấu các món mềm dễ nhai',
      ),
      'benefits' => 
      array (
        0 => 'Bữa trưa thân mật cùng các cụ',
        1 => 'Áo thun Tím Hy Vọng',
        2 => 'Thư cảm ơn của Viện Dưỡng Lão',
      ),
      'summary' => 'Mang lại sự ấm áp cho 90 cụ già neo đơn, đo huyết áp, xoa bóp xương khớp khi mùa đông về, cắt tỉa móng tay và tổ chức sinh nhật chung cho các cụ.',
      'status' => 'recruiting',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Chủ Nhật, 01/11/2026',
          'full_label' => 'Thứ Bảy (Chủ Nhật, 01/11/2026)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '501-D1-S1',
              'shift_name' => 'Ca Sáng Chăm Sóc Sức Khỏe (08:00 – 12:00)',
              'time_range' => '08:00 – 12:00',
              'capacity' => 12,
              'registered' => 10,
              'skill_req' => 'Xoa bóp bấm huyệt, Đo huyết áp & Cắt tóc',
              'skill_group' => 'y_te',
            ),
            1 => 
            array (
              'shift_id' => '501-D1-S2',
              'shift_name' => 'Ca Chiều Giao Lưu Văn Nghệ (13:00 – 16:30)',
              'time_range' => '13:00 – 16:30',
              'capacity' => 8,
              'registered' => 7,
              'skill_req' => 'Hát nhạc đỏ, Chơi cờ tướng & Đọc báo',
              'skill_group' => 'su_pham',
            ),
          ),
        ),
      ),
    ),
    41 => 
    array (
      'id' => 502,
      'title' => 'Tết Trung Thu Cho Em — Rước Đèn, Múa Lân & Phá Cỗ Cùng 150 Trẻ Em Khuyết Tật Thụy An',
      'category' => 'cham_soc_yeu_thuong',
      'category_name' => 'Chăm Sóc Người Cao Tuổi & Trẻ Em Khuyết Tật',
      'date_string' => 'Chủ Nhật, 15/11/2026',
      'time_range' => '13:30 – 20:30',
      'location' => 'Trung Tâm Nuôi Dưỡng Người Già & Trẻ Tàn Tật Thụy An, Huyện Ba Vì, Hà Nội',
      'meeting_point' => 'Cổng Trường ĐH Sư Phạm Hà Nội (Xe ô tô 45 chỗ)',
      'capacity' => 40,
      'registered' => 38,
      'leader_name' => 'Trịnh Quốc Bảo',
      'leader_phone' => '0977.111.005',
      'shifts' => 
      array (
        0 => 'Ca Chiều Trang Trí Mâm Cỗ (13:30 – 17:30)',
        1 => 'Ca Tối Phá Cỗ Đêm Rằm (18:00 – 20:30)',
      ),
      'skills_needed' => 
      array (
        0 => 'Biết múa lân sư rồng',
        1 => 'Hóa trang chú Cuội chị Hằng',
        2 => 'Bày mâm cỗ hoa quả ngũ quả',
        3 => 'Hoạt náo',
      ),
      'benefits' => 
      array (
        0 => 'Vé xe khứ hồi Hà Nội – Ba Vì',
        1 => 'Bánh nướng bánh dẻo truyền thống',
        2 => 'Chứng nhận TNV D2L',
      ),
      'summary' => 'Mang không khí tết trung thu rộn rã tiếng trống lân đến với các em nhỏ bại não, câm điếc giúp các em hòa nhập và vơi đi thiệt thòi số phận.',
      'status' => 'recruiting',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Chủ Nhật, 15/11/2026',
          'full_label' => 'Thứ Bảy (Chủ Nhật, 15/11/2026)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '502-D1-S1',
              'shift_name' => 'Ca Chiều Trang Trí Mâm Cỗ (13:30 – 17:30)',
              'time_range' => '13:30 – 17:30',
              'capacity' => 20,
              'registered' => 19,
              'skill_req' => 'Tỉa hoa quả, Cắt dán đèn ông sao',
              'skill_group' => 'truyen_thong',
            ),
            1 => 
            array (
              'shift_id' => '502-D1-S2',
              'shift_name' => 'Ca Tối Phá Cỗ Đêm Rằm (18:00 – 20:30)',
              'time_range' => '18:00 – 20:30',
              'capacity' => 20,
              'registered' => 19,
              'skill_req' => 'Múa lân, Đánh trống & Dắt tay các bé rước đèn',
              'skill_group' => 'hau_can',
            ),
          ),
        ),
      ),
    ),
    42 => 
    array (
      'id' => 503,
      'title' => 'Hòa Nhập Yêu Thương — Ngày Hội Thể Thao Vận Động Dành Cho Trẻ Tự Kỷ & Bại Não',
      'category' => 'cham_soc_yeu_thuong',
      'category_name' => 'Chăm Sóc Người Cao Tuổi & Trẻ Em Khuyết Tật',
      'date_string' => 'Thứ Bảy, 21/11/2026',
      'time_range' => '07:30 – 12:00',
      'location' => 'Cung Thể Thao Quần Ngựa, Phố Văn Cao, Quận Ba Đình, Hà Nội',
      'meeting_point' => 'Sân Điền Kinh Trung Tâm Cung Quần Ngựa',
      'capacity' => 30,
      'registered' => 28,
      'leader_name' => 'Hoàng Yến Nhi',
      'leader_phone' => '0988.555.774',
      'shifts' => 
      array (
        0 => 'Ca Sáng Vận Động (07:30 – 12:00)',
      ),
      'skills_needed' => 
      array (
        0 => 'Kỹ năng đồng hành 1 kèm 1',
        1 => 'Kiên trì nhẹ nhàng với trẻ tự kỷ',
        2 => 'Cổ vũ nhiệt thành',
      ),
      'benefits' => 
      array (
        0 => 'Huy chương đồng hành tình nguyện',
        1 => 'Áo thun thể thao năng động',
        2 => 'Nước điện giải',
      ),
      'summary' => 'Mỗi TNV làm bạn đồng hành cùng 1 em nhỏ đặc biệt tham gia các trò chơi ném bóng rổ, chạy tiếp sức và vượt chướng ngại vật mềm.',
      'status' => 'recruiting',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Thứ Bảy, 21/11/2026',
          'full_label' => 'Thứ Bảy (Thứ Bảy, 21/11/2026)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '503-D1-S1',
              'shift_name' => 'Ca Sáng Vận Động (07:30 – 12:00)',
              'time_range' => '07:30 – 12:00',
              'capacity' => 30,
              'registered' => 28,
              'skill_req' => 'Đồng hành 1 kèm 1 nâng đỡ trẻ tự kỷ chạy',
              'skill_group' => 'y_te',
            ),
          ),
        ),
      ),
    ),
    43 => 
    array (
      'id' => 504,
      'title' => 'Bữa Trưa Yêu Thương — Chăm Sóc Vệ Sinh & Cho Ăn Trẻ Em Bị Bỏ Rơi Mái Ấm Chùa Bồ Đề',
      'category' => 'cham_soc_yeu_thuong',
      'category_name' => 'Chăm Sóc Người Cao Tuổi & Trẻ Em Khuyết Tật',
      'date_string' => 'Thứ Bảy & Chủ Nhật hàng tuần',
      'time_range' => '09:30 – 14:00',
      'location' => 'Mái Ấm Tình Thương Chùa Bồ Đề, Phố Phú Viên, Bồ Đề, Long Biên, Hà Nội',
      'meeting_point' => 'Cổng Chùa Bồ Đề, Long Biên',
      'capacity' => 15,
      'registered' => 4,
      'leader_name' => 'Vũ Thị Minh Hằng',
      'leader_phone' => '0977.111.006',
      'shifts' => 
      array (
        0 => 'Ca Trưa (09:30 – 14:00)',
      ),
      'skills_needed' => 
      array (
        0 => 'Yêu thương em bé sơ sinh',
        1 => 'Biết bế ẵm pha sữa',
        2 => 'Thay tã vệ sinh cho trẻ',
      ),
      'benefits' => 
      array (
        0 => 'Cơm chay thanh tịnh nhà chùa',
        1 => 'Thư tri ân sư trụ trì',
        2 => 'Chứng nhận công tác xã hội',
      ),
      'summary' => 'Hỗ trợ các mẹ chăm sóc 45 bé sơ sinh và trẻ chập chững biết đi bị bỏ rơi. Rất cần các bạn nữ tỉ mỉ, kiên nhẫn. Cần bổ sung khẩn cấp!',
      'status' => 'recruiting',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Thứ Bảy & Chủ Nhật hàng tuần',
          'full_label' => 'Thứ Bảy (Thứ Bảy & Chủ Nhật hàng tuần)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '504-D1-S1',
              'shift_name' => 'Ca Trưa (09:30 – 14:00)',
              'time_range' => '09:30 – 14:00',
              'capacity' => 15,
              'registered' => 4,
              'skill_req' => 'Pha sữa, Cho bé ăn bột & Giặt khăn tã',
              'skill_group' => 'hau_can',
            ),
          ),
        ),
      ),
    ),
    44 => 
    array (
      'id' => 505,
      'title' => 'Sửa Nhà Tình Nghĩa Cho Mẹ Việt Nam Anh Hùng Huyện Ứng Hòa',
      'category' => 'cham_soc_yeu_thuong',
      'category_name' => 'Chăm Sóc Người Cao Tuổi & Trẻ Em Khuyết Tật',
      'date_string' => 'Thứ Bảy, 05/12/2026',
      'time_range' => '07:00 – 17:30',
      'location' => 'Thôn Cầu Giẽ, Xã Đồng Tân, Huyện Ứng Hòa, Hà Nội',
      'meeting_point' => 'Bến xe Yên Nghĩa (Xe đưa đón)',
      'capacity' => 12,
      'registered' => 12,
      'leader_name' => 'Phạm Minh Quân',
      'leader_phone' => '0908.123.456',
      'shifts' => 
      array (
        0 => 'Ca Sửa Nhà (07:30 – 17:00)',
      ),
      'skills_needed' => 
      array (
        0 => 'Kỹ thuật xây dựng',
        1 => 'Sơn tường trát vữa',
        2 => 'Sửa chữa điện nước gia dụng',
      ),
      'benefits' => 
      array (
        0 => 'Bữa cơm thân mật thôn xóm',
        1 => 'Bằng khen tri ân của Huyện Ứng Hòa',
        2 => 'Áo thợ tình nguyện',
      ),
      'summary' => 'Gia cố lại mái tôn dột nát, thay hệ thống dây điện mục nát và sơn mới lại tường nhà cho Mẹ VNAH 94 tuổi. Đã đủ quân số!',
      'status' => 'full',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Thứ Bảy, 05/12/2026',
          'full_label' => 'Thứ Bảy (Thứ Bảy, 05/12/2026)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '505-D1-S1',
              'shift_name' => 'Ca Sửa Nhà (07:30 – 17:00)',
              'time_range' => '07:30 – 17:00',
              'capacity' => 12,
              'registered' => 12,
              'skill_req' => 'Lợp mái tôn, Quét sơn & Sửa đường điện',
              'skill_group' => 'hau_can',
            ),
          ),
        ),
      ),
    ),
    45 => 
    array (
      'id' => 506,
      'title' => 'Cắt Tóc Tình Nguyện 0 Đồng — Chăm Sóc Diện Mạo Cho Người Cao Tuổi Neo Đơn',
      'category' => 'cham_soc_yeu_thuong',
      'category_name' => 'Chăm Sóc Người Cao Tuổi & Trẻ Em Khuyết Tật',
      'date_string' => 'Chủ Nhật, 13/12/2026',
      'time_range' => '08:00 – 11:30',
      'location' => 'Viện Dưỡng Lão Nghệ Sĩ TP.HCM, Quận 8, TP. Hồ Chí Minh',
      'meeting_point' => 'Cổng Viện Dưỡng Lão Nghệ Sĩ, Phường 3, Quận 8',
      'capacity' => 10,
      'registered' => 8,
      'leader_name' => 'Trương Gia Bảo',
      'leader_phone' => '0938.445.566',
      'shifts' => 
      array (
        0 => 'Ca Sáng Cắt Tóc (08:00 – 11:30)',
      ),
      'skills_needed' => 
      array (
        0 => 'Tay nghề thợ cắt tóc',
        1 => 'Cạo râu tỉa lông mày nhẹ nhàng',
        2 => 'Trò chuyện lễ phép',
      ),
      'benefits' => 
      array (
        0 => 'Bộ kéo lược chuyên nghiệp D2L',
        1 => 'Giao lưu văn nghệ cùng nghệ sĩ lão thành',
      ),
      'summary' => 'Cắt tóc, tỉa gọn gàng và gội đầu thảo dược thư giãn cho 30 nghệ sĩ và cụ già neo đơn đang an dưỡng tại viện.',
      'status' => 'recruiting',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Chủ Nhật, 13/12/2026',
          'full_label' => 'Thứ Bảy (Chủ Nhật, 13/12/2026)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '506-D1-S1',
              'shift_name' => 'Ca Sáng Cắt Tóc (08:00 – 11:30)',
              'time_range' => '08:00 – 11:30',
              'capacity' => 10,
              'registered' => 8,
              'skill_req' => 'Thợ cắt tóc tông đơ & Gội đầu thảo dược',
              'skill_group' => 'hau_can',
            ),
          ),
        ),
      ),
    ),
    46 => 
    array (
      'id' => 507,
      'title' => 'Hát Cho Nhau Nghe — Giao Lưu Văn Nghệ & Kể Chuyện Cùng Cụ Già Viện Dưỡng Lão Diên Hồng',
      'category' => 'cham_soc_yeu_thuong',
      'category_name' => 'Chăm Sóc Người Cao Tuổi & Trẻ Em Khuyết Tật',
      'date_string' => 'Thứ Bảy, 19/12/2026',
      'time_range' => '14:30 – 17:30',
      'location' => 'Trung Tâm Dưỡng Lão Diên Hồng, Khu Đô Thị Nam An Khánh, Hoài Đức, Hà Nội',
      'meeting_point' => 'Sảnh Lễ Tân Viện Diên Hồng Cơ Sở 2',
      'capacity' => 15,
      'registered' => 15,
      'leader_name' => 'Nguyễn Hà My',
      'leader_phone' => '0933.789.012',
      'shifts' => 
      array (
        0 => 'Ca Chiều Văn Nghệ (14:30 – 17:30)',
      ),
      'skills_needed' => 
      array (
        0 => 'Biết đàn guitar/organ',
        1 => 'Hát các ca khúc tiền chiến',
        2 => 'Kỹ năng hoạt náo sân khấu',
      ),
      'benefits' => 
      array (
        0 => 'Bữa tiệc trà chiều trái cây',
        1 => 'Kỷ niệm chương Trái Tim Hồng',
        2 => 'Ghi hình phóng sự',
      ),
      'summary' => 'Tổ chức buổi hòa nhạc acoustic mộc mạc gợi lại ký ức thanh xuân tươi đẹp và đem lại nụ cười rạng rỡ cho các cụ ông cụ bà. Đã đủ quân số!',
      'status' => 'full',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Thứ Bảy, 19/12/2026',
          'full_label' => 'Thứ Bảy (Thứ Bảy, 19/12/2026)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '507-D1-S1',
              'shift_name' => 'Ca Chiều Văn Nghệ (14:30 – 17:30)',
              'time_range' => '14:30 – 17:30',
              'capacity' => 15,
              'registered' => 15,
              'skill_req' => 'Đệm đàn Guitar, Hát đơn ca & Chụp ảnh lưu niệm',
              'skill_group' => 'truyen_thong',
            ),
          ),
        ),
      ),
    ),
    47 => 
    array (
      'id' => 508,
      'title' => 'Lớp Học Kỹ Năng Sống & Tự Vệ Cho Trẻ Em Mồ Côi Tại Làng Hòa Bình Thanh Xuân',
      'category' => 'cham_soc_yeu_thuong',
      'category_name' => 'Chăm Sóc Người Cao Tuổi & Trẻ Em Khuyết Tật',
      'date_string' => 'Chủ Nhật, 27/12/2026',
      'time_range' => '08:30 – 11:30',
      'location' => 'Làng Hòa Bình Thanh Xuân, Số 35 Lê Văn Thiêm, Nhân Chính, Thanh Xuân',
      'meeting_point' => 'Nhà Sinh Hoạt Thể Chất Tầng 1 Làng Hòa Bình',
      'capacity' => 20,
      'registered' => 16,
      'leader_name' => 'Bác sĩ trẻ Trần Tuấn Anh',
      'leader_phone' => '0988.555.772',
      'shifts' => 
      array (
        0 => 'Ca Sáng Tập Huấn (08:30 – 11:30)',
      ),
      'skills_needed' => 
      array (
        0 => 'Biết võ thuật tự vệ (Karate/Taekwondo)',
        1 => 'Kỹ năng phòng chống xâm hại trẻ em',
        2 => 'Tâm lý học đường',
      ),
      'benefits' => 
      array (
        0 => 'Võ phục TNV huấn luyện viên',
        1 => 'Chứng chỉ đào tạo kỹ năng sống',
        2 => 'Quà lưu niệm',
      ),
      'summary' => 'Dạy các em nhỏ bị ảnh hưởng chất độc da cam và mồ côi các thế võ thoát hiểm khi bị kẻ xấu lôi kéo và kỹ năng từ chối đụng chạm nguy hiểm.',
      'status' => 'recruiting',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Chủ Nhật, 27/12/2026',
          'full_label' => 'Thứ Bảy (Chủ Nhật, 27/12/2026)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '508-D1-S1',
              'shift_name' => 'Ca Sáng Tập Huấn (08:30 – 11:30)',
              'time_range' => '08:30 – 11:30',
              'capacity' => 20,
              'registered' => 16,
              'skill_req' => 'Huấn luyện võ tự vệ & Thoát hiểm khi bị ôm bắt',
              'skill_group' => 'su_pham',
            ),
          ),
        ),
      ),
    ),
    48 => 
    array (
      'id' => 509,
      'title' => 'Trao Tặng 100 Xe Lăn & Khung Tập Đi Cho Thương Bệnh Binh Nặng Tỉnh Quảng Ngãi',
      'category' => 'cham_soc_yeu_thuong',
      'category_name' => 'Chăm Sóc Người Cao Tuổi & Trẻ Em Khuyết Tật',
      'date_string' => 'Thứ Bảy, 09/01/2027',
      'time_range' => '07:30 – 16:30',
      'location' => 'Trung Tâm Điều Dưỡng Người Có Công Tỉnh Quảng Ngãi, TP. Quảng Ngãi',
      'meeting_point' => 'Sảnh Trung Tâm Điều Dưỡng, Đường Trương Quang Trọng',
      'capacity' => 25,
      'registered' => 23,
      'leader_name' => 'Nguyễn Đình Phúc',
      'leader_phone' => '0905.778.899',
      'shifts' => 
      array (
        0 => 'Ca Lắp Ráp Xe Lăn (07:30 – 12:00)',
        1 => 'Ca Hướng Dẫn Tập Đi (13:00 – 16:30)',
      ),
      'skills_needed' => 
      array (
        0 => 'Lắp ráp cơ khí xe lăn',
        1 => 'Kỹ thuật vật lý trị liệu phục hồi',
        2 => 'Kính cẩn tri ân',
      ),
      'benefits' => 
      array (
        0 => 'Huy hiệu Đền Ơn Đáp Nghĩa',
        1 => 'Bữa cơm trưa nghĩa tình',
        2 => 'Giấy khen Sở LĐTB&XH',
      ),
      'summary' => 'Lắp ráp căn chỉnh 100 chiếc xe lăn hiện đại và hướng dẫn các bác thương binh mất chân cách sử dụng an toàn trong sinh hoạt hàng ngày.',
      'status' => 'recruiting',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Thứ Bảy, 09/01/2027',
          'full_label' => 'Thứ Bảy (Thứ Bảy, 09/01/2027)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '509-D1-S1',
              'shift_name' => 'Ca Lắp Ráp Xe Lăn (07:30 – 12:00)',
              'time_range' => '07:30 – 12:00',
              'capacity' => 15,
              'registered' => 14,
              'skill_req' => 'Xiết ốc vít, Căn chỉnh phanh bánh xe lăn',
              'skill_group' => 'hau_can',
            ),
            1 => 
            array (
              'shift_id' => '509-D1-S2',
              'shift_name' => 'Ca Hướng Dẫn Tập Đi (13:00 – 16:30)',
              'time_range' => '13:00 – 16:30',
              'capacity' => 10,
              'registered' => 9,
              'skill_req' => 'Vật lý trị liệu & Đỡ thương binh lên xe',
              'skill_group' => 'y_te',
            ),
          ),
        ),
      ),
    ),
    49 => 
    array (
      'id' => 510,
      'title' => 'Hành Trình Tri Ân Anh Hùng Liệt Sĩ — Dâng Hương & Thắp Nến Nghĩa Trang Trường Sơn',
      'category' => 'cham_soc_yeu_thuong',
      'category_name' => 'Chăm Sóc Người Cao Tuổi & Trẻ Em Khuyết Tật',
      'date_string' => 'Hoàn thành đợt 27/07/2026',
      'time_range' => 'Đại lễ tri ân hoàn thành trang trọng',
      'location' => 'Nghĩa Trang Liệt Sĩ Quốc Gia Trường Sơn, Huyện Gio Linh, Tỉnh Quảng Trị',
      'meeting_point' => 'Tượng đài Trung tâm Nghĩa Trang Trường Sơn',
      'capacity' => 50,
      'registered' => 50,
      'leader_name' => 'Trần Hoàng Nam',
      'leader_phone' => '0912.456.789',
      'shifts' => 
      array (
        0 => 'Đại Lễ Thắp Nến Tri Ân (Đã xong)',
      ),
      'skills_needed' => 
      array (
        0 => 'Kỷ luật lễ tân nghiêm trang',
        1 => 'Chăm sóc mộ phần',
        2 => 'Thắp nến hoa đăng',
      ),
      'benefits' => 
      array (
        0 => 'Bằng khen của Tỉnh đoàn Quảng Trị',
        1 => 'Hành trình đỏ học tập truyền thống',
      ),
      'summary' => 'Lau dọn 10.263 phần mộ liệt sĩ, quét vôi ve và thắp nến tri ân tưởng nhớ các thế hệ cha anh đã hy sinh vì nền độc lập tự do của Tổ quốc.',
      'status' => 'completed',
      'schedule_days' => 
      array (
        0 => 
        array (
          'day_code' => 'day_1',
          'day_name' => 'Thứ Bảy',
          'date_string' => 'Hoàn thành đợt 27/07/2026',
          'full_label' => 'Thứ Bảy (Hoàn thành đợt 27/07/2026)',
          'shifts' => 
          array (
            0 => 
            array (
              'shift_id' => '510-D1-S1',
              'shift_name' => 'Đại Lễ Thắp Nến Tri Ân (Đã xong)',
              'time_range' => '17:00 – 21:30',
              'capacity' => 50,
              'registered' => 50,
              'skill_req' => 'Cắm hoa, Thắp nhang & Đặt nến trên mộ',
              'skill_group' => 'hau_can',
            ),
          ),
        ),
      ),
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
