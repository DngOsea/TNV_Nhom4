#!/usr/bin/env bash
# SCRIPT ĐÓNG GÓI BÀI TẬP LỚN NỘP CUỐI KỲ — ĐỀ TÀI 06
# Tự động loại trừ các tệp rác (.git, .local, log, file tạm) và tạo file zip chuẩn.

set -u
root="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
group_name="${1:-BTL_DeTai06}"
zip_name="${group_name}.zip"

echo "============================================================"
echo "ĐÓNG GÓI BÀI TẬP LỚN: HỆ THỐNG PHÂN CÔNG TÌNH NGUYỆN VIÊN (D2L)"
echo "Tên gói xuất: $zip_name"
echo "============================================================"

# Kiểm tra bộ kiểm thử trước khi đóng gói
echo "1. Đang chạy kiểm thử tự động toàn diện..."
cd "$root/Ma_nguon" || exit 2
bash lab.sh btl-test || { echo "LỖI: Bộ kiểm thử chưa đạt, không thể đóng gói!"; exit 1; }

# Tạo file zip tại thư mục cha
cd "$root" || exit 2
rm -f "$zip_name"

echo "2. Đang nén mã nguồn và tài liệu bằng Python..."
python3 -c "
import os, zipfile

root = os.path.abspath('$root')
zip_path = os.path.join(root, '$zip_name')

exclude_prefixes = [
    os.path.join(root, '.git'),
    os.path.join(root, 'Ma_nguon', '.local'),
]

with zipfile.ZipFile(zip_path, 'w', zipfile.ZIP_DEFLATED) as z:
    for dirpath, dirnames, filenames in os.walk(root):
        # Exclude directories
        if any(dirpath.startswith(ex) for ex in exclude_prefixes):
            continue
        for f in filenames:
            if f.endswith('.zip') or f.endswith('.log') or f in ['.DS_Store', 'Thumbs.db']:
                continue
            fp = os.path.join(dirpath, f)
            arcname = os.path.relpath(fp, root)
            z.write(fp, arcname)

print('Nén hoàn tất!')
"

echo "============================================================"
echo "THÀNH CÔNG: Đã đóng gói tệp nộp bài tại: $root/$zip_name"
echo "Kích thước tệp:"
ls -lh "$zip_name"
echo "============================================================"
