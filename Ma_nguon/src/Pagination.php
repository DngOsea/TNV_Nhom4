<?php
declare(strict_types=1);

/**
 * LỚP HỖ TRỢ PHÂN TRANG CHUYÊN NGHIỆP DÀNH CHO TOÀN BỘ HỆ THỐNG
 */
class Pagination
{
    /**
     * Cắt danh sách theo trang hiện tại và trả về metadata phân trang
     */
    public static function paginate(array $items, int $page, int $perPage = 6, bool $preserveKeys = true): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $total = count($items);
        $totalPages = max(1, (int)ceil($total / $perPage));

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $offset = ($page - 1) * $perPage;
        $sliced = array_slice($items, $offset, $perPage, $preserveKeys);

        return [
            'items' => $sliced,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'offset' => $offset,
        ];
    }

    /**
     * Xuất HTML thanh phân trang phong cách doanh nghiệp (SaaS pagination)
     */
    public static function render(int $currentPage, int $totalPages, int $totalItems, int $perPage, string $baseUrl, array $queryParams = []): string
    {
        if ($totalItems === 0) {
            return '';
        }

        $startItem = ($currentPage - 1) * $perPage + 1;
        $endItem = min($totalItems, $currentPage * $perPage);

        $buildUrl = function(int $p) use ($baseUrl, $queryParams): string {
            $params = array_merge($queryParams, ['page' => $p]);
            // Bỏ các param rỗng
            $params = array_filter($params, fn($val) => $val !== '' && $val !== null);
            return $baseUrl . (empty($params) ? '' : '?' . http_build_query($params));
        };

        $html = '<div class="app-pagination-wrapper">';
        $html .= '<div class="pagination-info">Hiển thị <strong>' . $startItem . ' – ' . $endItem . '</strong> trong tổng số <strong>' . $totalItems . '</strong> mục</div>';

        if ($totalPages > 1) {
            $html .= '<nav class="app-pagination" aria-label="Phân trang danh sách">';

            // Nút Trước
            if ($currentPage > 1) {
                $html .= '<a href="' . htmlspecialchars($buildUrl($currentPage - 1)) . '" class="page-link prev-link" title="Trang trước">&lsaquo; Trước</a>';
            } else {
                $html .= '<span class="page-link disabled prev-link">&lsaquo; Trước</span>';
            }

            // Các số trang
            for ($i = 1; $i <= $totalPages; $i++) {
                if ($i === 1 || $i === $totalPages || ($i >= $currentPage - 2 && $i <= $currentPage + 2)) {
                    if ($i === $currentPage) {
                        $html .= '<span class="page-link active" aria-current="page">' . $i . '</span>';
                    } else {
                        $html .= '<a href="' . htmlspecialchars($buildUrl($i)) . '" class="page-link">' . $i . '</a>';
                    }
                } elseif ($i === $currentPage - 3 || $i === $currentPage + 3) {
                    $html .= '<span class="page-ellipsis">&hellip;</span>';
                }
            }

            // Nút Sau
            if ($currentPage < $totalPages) {
                $html .= '<a href="' . htmlspecialchars($buildUrl($currentPage + 1)) . '" class="page-link next-link" title="Trang kế tiếp">Sau &rsaquo;</a>';
            } else {
                $html .= '<span class="page-link disabled next-link">Sau &rsaquo;</span>';
            }

            $html .= '</nav>';
        }

        $html .= '</div>';
        return $html;
    }
}

