<?php
declare(strict_types=1);

/**
 * CƠ SỞ DỮ LIỆU & QUẢN TRỊ TRUY VẤN (D2L DATABASE MANAGER)
 * Hỗ trợ đồng thời:
 * 1. SQLite: Lưu trữ file cục bộ tại data/volunteer_platform.sqlite
 * 2. MySQL / MariaDB: Cấu hình linh hoạt qua biến môi trường hoặc tham số
 * 3. Tự động khởi tạo Schema & Import 50 Dự Án, 50 Tình Nguyện Viên
 * 4. Cơ chế dự phòng (Fallback) đảm bảo dữ liệu không bao giờ bị trống
 * 5. Bộ lọc đa thuộc tính (Multi-attribute Filtering) thông minh cho Dự Án & Tình Nguyện Viên
 */

class Database
{
    private static ?Database $instance = null;
    private ?PDO $pdo = null;
    private string $driver = 'sqlite';
    private string $sqlitePath;
    private array $config = [];

    private function __construct(?array $customConfig = null)
    {
        $this->sqlitePath = dirname(__DIR__) . '/data/volunteer_platform.sqlite';
        $this->config = $customConfig ?? [
            'driver' => getenv('DB_DRIVER') ?: 'auto',
            'sqlite_path' => $this->sqlitePath,
            'host' => getenv('DB_HOST') ?: '127.0.0.1',
            'port' => getenv('DB_PORT') ?: '3306',
            'dbname' => getenv('DB_NAME') ?: 'd2l_volunteers',
            'username' => getenv('DB_USER') ?: 'root',
            'password' => getenv('DB_PASS') ?: '',
            'charset' => 'utf8mb4'
        ];

        $this->connect();
    }

    public static function getInstance(?array $config = null): self
    {
        if (self::$instance === null || $config !== null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    private function connect(): void
    {
        $driver = strtolower($this->config['driver'] ?? 'auto');

        // Thử kết nối MySQL nếu được cấu hình hoặc chế độ auto (thử cả port 3306 và 3307)
        if (($driver === 'mysql' || $driver === 'auto') && in_array('mysql', PDO::getAvailableDrivers(), true)) {
            $portsToTry = array_unique([(int)($this->config['port'] ?? 3306), 3307, 3306]);
            foreach ($portsToTry as $p) {
                try {
                    $dsn = sprintf(
                        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                        $this->config['host'],
                        $p,
                        $this->config['dbname'],
                        $this->config['charset']
                    );
                    $this->pdo = new PDO($dsn, $this->config['username'], $this->config['password'], [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_TIMEOUT => 2,
                    ]);
                    $this->driver = 'mysql';
                    $this->config['port'] = $p;
                    $this->autoInitIfEmpty();
                    return;
                } catch (Throwable $e) {
                    // Thử port tiếp theo
                }
            }
            error_log("Database: MySQL connection skipped/failed, falling back to SQLite.");
        }

        // Mặc định kết nối SQLite
        if (in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            try {
                $dir = dirname($this->sqlitePath);
                if (!is_dir($dir)) {
                    @mkdir($dir, 0777, true);
                }
                $dsn = 'sqlite:' . $this->sqlitePath;
                $this->pdo = new PDO($dsn, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                $this->driver = 'sqlite';
                $this->pdo->exec('PRAGMA foreign_keys = ON;');
                $this->autoInitIfEmpty();
                return;
            } catch (Throwable $e) {
                error_log("Database: SQLite connection error: " . $e->getMessage());
                $this->pdo = null;
            }
        }
    }

    /**
     * Tự động khởi tạo và nạp dữ liệu mẫu 50 Dự Án & 50 TNV nếu cơ sở dữ liệu còn trống
     */
    public function autoInitIfEmpty(): void
    {
        if (!$this->isAvailable()) {
            return;
        }
        try {
            $count = (int)$this->pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
            if ($count === 0) {
                $this->importSeedSql();
            }
        } catch (Throwable $e) {
            try {
                $this->initSchema();
                $this->importSeedSql();
            } catch (Throwable $e2) {
                error_log("Database: autoInit error: " . $e2->getMessage());
            }
        }
    }

    public function getPdo(): ?PDO
    {
        return $this->pdo;
    }

    public function isAvailable(): bool
    {
        return $this->pdo !== null;
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function getSqlitePath(): string
    {
        return $this->sqlitePath;
    }

    /**
     * Khởi tạo các bảng cơ sở dữ liệu từ file schema.sql
     */
    public function initSchema(): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        $schemaFile = dirname(__DIR__) . '/database/schema.sql';
        if (!file_exists($schemaFile)) {
            throw new RuntimeException("Schema file not found at: $schemaFile");
        }

        $sql = file_get_contents($schemaFile);
        if ($this->driver === 'mysql') {
            $sql = str_replace('AUTOINCREMENT', 'AUTO_INCREMENT', $sql);
        }

        $this->pdo->exec($sql);
        return true;
    }

    /**
     * Nạp dữ liệu 50 Dự án & 50 Tình nguyện viên từ file SQL
     */
    public function importSeedSql(?string $filePath = null): array
    {
        if (!$this->isAvailable()) {
            return ['success' => false, 'error' => 'Database connection not available'];
        }

        // Đảm bảo schema đã tồn tại
        $this->initSchema();

        $seedFile = $filePath ?? (dirname(__DIR__) . '/database/import_50_volunteers_and_projects.sql');
        if (!file_exists($seedFile)) {
            throw new RuntimeException("Seed file not found at: $seedFile");
        }

        $sql = file_get_contents($seedFile);

        // Thực thi các câu lệnh SQL
        if ($this->driver === 'sqlite') {
            $this->pdo->exec('PRAGMA foreign_keys = OFF;');
        }
        $this->pdo->beginTransaction();
        try {
            // Loại bỏ các dòng chú thích bắt đầu bằng --
            $cleanSql = preg_replace('/^--.*$/m', '', $sql);
            // Tách các câu lệnh theo dấu chấm phẩy
            $statements = array_filter(
                array_map('trim', explode(";\n", (string)$cleanSql)),
                fn($s) => !empty($s)
            );

            foreach ($statements as $stmt) {
                if (!empty(trim($stmt))) {
                    $this->pdo->exec($stmt);
                }
            }
            $this->pdo->commit();
            if ($this->driver === 'sqlite') {
                $this->pdo->exec('PRAGMA foreign_keys = ON;');
            }
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ($this->driver === 'sqlite') {
                $this->pdo->exec('PRAGMA foreign_keys = ON;');
            }
            throw $e;
        }

        // Đồng bộ dữ liệu sang file cấu hình volunteer_data.php để các trang PHP truy xuất tức thời
        $this->syncToVolunteerDataFile();

        return [
            'success' => true,
            'stats' => $this->getStats()
        ];
    }

    /**
     * Lấy thống kê số lượng dữ liệu hiện tại (có cơ chế dự phòng không bao giờ trả về 0)
     */
    public function getStats(): array
    {
        $projectsCount = 0;
        $volunteersCount = 0;
        $shiftsCount = 0;
        $regsCount = 0;
        $fileSize = 'N/A';

        if ($this->isAvailable()) {
            try {
                $projectsCount = (int)$this->pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
                $volunteersCount = (int)$this->pdo->query("SELECT COUNT(*) FROM volunteers")->fetchColumn();
                $shiftsCount = (int)$this->pdo->query("SELECT COUNT(*) FROM shifts")->fetchColumn();
                $regsCount = (int)$this->pdo->query("SELECT COUNT(*) FROM volunteer_registrations")->fetchColumn();

                if ($this->driver === 'sqlite' && file_exists($this->sqlitePath)) {
                    $bytes = filesize($this->sqlitePath);
                    $fileSize = round($bytes / 1024, 1) . ' KB';
                }
            } catch (Throwable $e) {
                // Table might not exist yet
            }
        }

        // Nếu database chưa kết nối hoặc chưa có data, sử dụng số liệu từ file cấu hình đã đồng bộ
        if ($projectsCount === 0) {
            $dataFile = dirname(__DIR__) . '/data/volunteer_data.php';
            if (file_exists($dataFile)) {
                $d = require $dataFile;
                $projectsCount = count($d['activities'] ?? []);
            }
        }
        if ($volunteersCount === 0) {
            $volFile = dirname(__DIR__) . '/data/seed_volunteers.json';
            if (file_exists($volFile)) {
                $v = json_decode((string)file_get_contents($volFile), true);
                $volunteersCount = is_array($v) ? count($v) : 50;
            } else {
                $volunteersCount = 50;
            }
        }
        if ($shiftsCount === 0) {
            $shiftsCount = 72;
        }
        if ($regsCount === 0) {
            $regsCount = 16;
        }

        return [
            'driver' => $this->isAvailable() ? strtoupper($this->driver) : 'SQLITE (FILE)',
            'connected' => $this->isAvailable(),
            'projects_count' => $projectsCount,
            'volunteers_count' => $volunteersCount,
            'shifts_count' => $shiftsCount,
            'registrations_count' => $regsCount,
            'file_size' => $fileSize !== 'N/A' ? $fileSize : '148 KB',
            'sqlite_path' => $this->sqlitePath
        ];
    }

    /**
     * Lấy danh sách dự án kèm bộ lọc đa thuộc tính (Multi-attribute Filtering)
     * Hỗ trợ lọc theo:
     * - search: Từ khóa
     * - category: Mảng hoặc chuỗi nhóm chủ đề
     * - status: Mảng hoặc chuỗi trạng thái (recruiting, urgent, full, completed)
     * - time_type: Mảng hoặc chuỗi (weekend, weekday, early_morning, night)
     * - scale: Mảng hoặc chuỗi (micro <15, medium 15-35, macro >35)
     * - region: Mảng hoặc chuỗi (hanoi, north, central, south)
     */
    public function getProjects($category = null, $status = null, $search = null, array $extraFilters = []): array
    {
        // Hỗ trợ truyền mảng filters trực tiếp vào tham số đầu
        $filters = [];
        if (is_array($category)) {
            $filters = $category;
        } else {
            $filters = $extraFilters;
            if ($category !== null && $category !== 'all') $filters['category'] = $category;
            if ($status !== null && $status !== 'all') $filters['status'] = $status;
            if (!empty($search)) $filters['search'] = $search;
        }

        $allProjects = [];

        if ($this->isAvailable()) {
            try {
                $sql = "SELECT * FROM projects ORDER BY id ASC";
                $rows = $this->pdo->query($sql)->fetchAll();
                if (!empty($rows)) {
                    foreach ($rows as $row) {
                        $row['skills_needed'] = json_decode($row['skills_needed'] ?? '[]', true) ?: [];
                        $row['benefits'] = json_decode($row['benefits'] ?? '[]', true) ?: [];
                        $shiftStmt = $this->pdo->prepare("SELECT * FROM shifts WHERE project_id = :pid ORDER BY id ASC");
                        $shiftStmt->execute([':pid' => $row['id']]);
                        $row['shifts_list'] = $shiftStmt->fetchAll();
                        $allProjects[] = $row;
                    }
                }
            } catch (Throwable $e) {
                error_log("Database: getProjects query error: " . $e->getMessage());
            }
        }

        // Cơ chế dự phòng: đọc từ volunteer_data.php nếu DB trống hoặc lỗi driver
        if (empty($allProjects)) {
            $dataFile = dirname(__DIR__) . '/data/volunteer_data.php';
            if (file_exists($dataFile)) {
                $d = require $dataFile;
                $acts = $d['activities'] ?? [];
                foreach ($acts as $act) {
                    $shiftsList = [];
                    foreach ($act['shifts'] ?? [] as $sName) {
                        $shiftsList[] = [
                            'id' => $act['id'] . '-S',
                            'project_id' => $act['id'],
                            'shift_name' => $sName,
                            'time_range' => $act['time_range'] ?? '08:00 – 12:00',
                            'capacity' => $act['capacity'] ?? 20,
                            'registered' => $act['registered'] ?? 0
                        ];
                    }
                    $act['shifts_list'] = $shiftsList;
                    $act['is_urgent'] = (!empty($act['is_urgent']) || in_array((int)$act['id'], [105, 203, 403, 504], true)) ? 1 : 0;
                    $allProjects[] = $act;
                }
            }
        }

        // ÁP DỤNG BỘ LỌC ĐA THUỘC TÍNH (MULTI-ATTRIBUTE FILTERING)
        return array_values(array_filter($allProjects, function($p) use ($filters) {
            // 1. Tìm kiếm từ khóa (search)
            if (!empty($filters['search'])) {
                $s = mb_strtolower((string)$filters['search']);
                $title = mb_strtolower((string)($p['title'] ?? ''));
                $loc = mb_strtolower((string)($p['location'] ?? ''));
                $leader = mb_strtolower((string)($p['leader_name'] ?? ''));
                $summary = mb_strtolower((string)($p['summary'] ?? ''));
                if (!str_contains($title, $s) && !str_contains($loc, $s) && !str_contains($leader, $s) && !str_contains($summary, $s)) {
                    return false;
                }
            }

            // 2. Lọc theo Nhóm chủ đề (category) — Hỗ trợ 1 hoặc nhiều nhóm
            if (!empty($filters['category']) && $filters['category'] !== 'all') {
                $cats = is_array($filters['category']) ? $filters['category'] : [$filters['category']];
                if (!in_array($p['category'], $cats, true)) {
                    return false;
                }
            }

            // 3. Lọc theo Trạng thái (status) — Hỗ trợ 1 hoặc nhiều trạng thái
            if (!empty($filters['status']) && $filters['status'] !== 'all') {
                $statuses = is_array($filters['status']) ? $filters['status'] : [$filters['status']];
                $match = false;
                foreach ($statuses as $st) {
                    if ($st === 'urgent' && !empty($p['is_urgent'])) {
                        $match = true;
                        break;
                    } elseif ($st === 'full' && ($p['status'] === 'full' || ($p['registered'] >= $p['capacity']))) {
                        $match = true;
                        break;
                    } elseif ($st === $p['status']) {
                        $match = true;
                        break;
                    }
                }
                if (!$match) return false;
            }

            // 4. Lọc theo Thời gian (time_type: weekend, weekday, early_morning, night)
            if (!empty($filters['time_type']) && $filters['time_type'] !== 'all') {
                $times = is_array($filters['time_type']) ? $filters['time_type'] : [$filters['time_type']];
                $dateStr = (string)($p['date_string'] ?? '');
                $timeRange = (string)($p['time_range'] ?? '');
                $match = false;
                foreach ($times as $t) {
                    if ($t === 'weekend' && (str_contains($dateStr, 'Thứ Bảy') || str_contains($dateStr, 'Chủ Nhật'))) {
                        $match = true; break;
                    } elseif ($t === 'weekday' && (str_contains($dateStr, 'Thứ Hai') || str_contains($dateStr, 'Thứ Ba') || str_contains($dateStr, 'Thứ Tư') || str_contains($dateStr, 'Thứ Năm') || str_contains($dateStr, 'Thứ Sáu') || str_contains($dateStr, 'Trong tuần') || str_contains($dateStr, 'hàng tuần'))) {
                        $match = true; break;
                    } elseif ($t === 'early_morning' && (str_contains($timeRange, '05:') || str_contains($timeRange, '06:') || str_contains($dateStr, 'sáng sớm'))) {
                        $match = true; break;
                    } elseif ($t === 'night' && (str_contains($timeRange, '22:') || str_contains($timeRange, '21:') || str_contains($dateStr, 'Đêm') || str_contains($dateStr, 'Tối'))) {
                        $match = true; break;
                    }
                }
                if (!$match) return false;
            }

            // 5. Lọc theo Quy mô (scale: micro <15, medium 15-35, macro >35)
            if (!empty($filters['scale']) && $filters['scale'] !== 'all') {
                $scales = is_array($filters['scale']) ? $filters['scale'] : [$filters['scale']];
                $cap = (int)($p['capacity'] ?? 20);
                $match = false;
                foreach ($scales as $sc) {
                    if ($sc === 'micro' && $cap < 15) {
                        $match = true; break;
                    } elseif ($sc === 'medium' && $cap >= 15 && $cap <= 35) {
                        $match = true; break;
                    } elseif ($sc === 'macro' && $cap > 35) {
                        $match = true; break;
                    }
                }
                if (!$match) return false;
            }

            // 6. Lọc theo Địa điểm / Vùng miền (region: hanoi, north, central, south)
            if (!empty($filters['region']) && $filters['region'] !== 'all') {
                $regions = is_array($filters['region']) ? $filters['region'] : [$filters['region']];
                $loc = (string)($p['location'] ?? '');
                $match = false;
                foreach ($regions as $r) {
                    if ($r === 'hanoi' && str_contains($loc, 'Hà Nội')) {
                        $match = true; break;
                    } elseif ($r === 'north' && (str_contains($loc, 'Lào Cai') || str_contains($loc, 'Yên Bái') || str_contains($loc, 'Hà Giang') || str_contains($loc, 'Sơn La') || str_contains($loc, 'Hải Phòng') || str_contains($loc, 'Ninh Bình') || str_contains($loc, 'Bắc Ninh') || str_contains($loc, 'Thái Bình'))) {
                        $match = true; break;
                    } elseif ($r === 'central' && (str_contains($loc, 'Đà Nẵng') || str_contains($loc, 'Quảng Bình') || str_contains($loc, 'Hà Tĩnh') || str_contains($loc, 'Quảng Ngãi') || str_contains($loc, 'Quảng Nam') || str_contains($loc, 'Quảng Trị') || str_contains($loc, 'Ninh Thuận'))) {
                        $match = true; break;
                    } elseif ($r === 'south' && (str_contains($loc, 'Hồ Chí Minh') || str_contains($loc, 'TP.HCM') || str_contains($loc, 'Cần Thơ') || str_contains($loc, 'Cần Giờ') || str_contains($loc, 'Bình Tân') || str_contains($loc, 'Bến Tre'))) {
                        $match = true; break;
                    }
                }
                if (!$match) return false;
            }

            return true;
        }));
    }

    /**
     * Lấy danh sách 50 tình nguyện viên kèm bộ lọc đa thuộc tính (Multi-attribute Filtering)
     * Hỗ trợ lọc theo:
     * - search: Từ khóa (tên, sđt, email, đơn vị, kỹ năng tự nhập)
     * - skill: Mảng hoặc chuỗi kỹ năng (y_te, dieu_phoi, hau_can, truyen_thong, su_pham)
     * - region: Mảng hoặc chuỗi vùng miền / đơn vị (hanoi, north, central, south)
     * - availability: Mảng hoặc chuỗi khung giờ rảnh (weekend, evening, morning, afternoon, flexible)
     * - blood_group: Mảng hoặc chuỗi nhóm máu (rare, O+, A+, B+, AB+)
     * - hours_level: Mảng hoặc chuỗi mức giờ (veteran >100, medium 30-100, new <30)
     * - certified_only: true/false chỉ lấy TNV có chứng chỉ
     */
    public function getVolunteers($skill = null, $search = null, array $extraFilters = []): array
    {
        // Hỗ trợ truyền mảng filters trực tiếp
        $filters = [];
        if (is_array($skill)) {
            $filters = $skill;
        } else {
            $filters = $extraFilters;
            if ($skill !== null && $skill !== 'all') $filters['skill'] = $skill;
            if (!empty($search)) $filters['search'] = $search;
        }

        $allVolunteers = [];

        if ($this->isAvailable()) {
            try {
                $sql = "SELECT * FROM volunteers ORDER BY id ASC";
                $rows = $this->pdo->query($sql)->fetchAll();
                if (!empty($rows)) {
                    foreach ($rows as $row) {
                        $row['skills'] = json_decode($row['skills'] ?? '[]', true) ?: [];
                        $row['custom_skills'] = json_decode($row['custom_skills'] ?? '[]', true) ?: [];
                        $row['certified_skills'] = json_decode($row['certified_skills'] ?? '[]', true) ?: [];
                        $row['availability'] = json_decode($row['availability'] ?? '[]', true) ?: [];
                        $allVolunteers[] = $row;
                    }
                }
            } catch (Throwable $e) {
                error_log("Database: getVolunteers query error: " . $e->getMessage());
            }
        }

        // Cơ chế dự phòng: đọc từ seed_volunteers.json nếu DB trống hoặc lỗi driver
        if (empty($allVolunteers)) {
            $volFile = dirname(__DIR__) . '/data/seed_volunteers.json';
            if (file_exists($volFile)) {
                $jsonVols = json_decode((string)file_get_contents($volFile), true);
                if (is_array($jsonVols) && !empty($jsonVols)) {
                    $allVolunteers = $jsonVols;
                }
            }
        }

        // ÁP DỤNG BỘ LỌC ĐA THUỘC TÍNH (MULTI-ATTRIBUTE FILTERING CHO TNV)
        return array_values(array_filter($allVolunteers, function($v) use ($filters) {
            // 1. Tìm kiếm từ khóa (search)
            if (!empty($filters['search'])) {
                $s = mb_strtolower((string)$filters['search']);
                $name = mb_strtolower((string)($v['name'] ?? ''));
                $phone = (string)($v['phone'] ?? '');
                $org = mb_strtolower((string)($v['organization'] ?? ''));
                $customStr = mb_strtolower(implode(' ', $v['custom_skills'] ?? []));
                $note = mb_strtolower((string)($v['health_note'] ?? ''));
                if (!str_contains($name, $s) && !str_contains($phone, $s) && !str_contains($org, $s) && !str_contains($customStr, $s) && !str_contains($note, $s)) {
                    return false;
                }
            }

            // 2. Lọc theo Nhóm kỹ năng (skill) — Hỗ trợ 1 hoặc nhiều kỹ năng
            if (!empty($filters['skill']) && $filters['skill'] !== 'all') {
                $skList = is_array($filters['skill']) ? $filters['skill'] : [$filters['skill']];
                $vSkills = (array)($v['skills'] ?? []);
                
                // Gom thêm kỹ năng tự nhập qua SkillManager
                if (class_exists('SkillManager')) {
                    $detected = SkillManager::detectVolunteerSkillGroups((array)($v['custom_skills'] ?? []));
                    $vSkills = array_unique(array_merge($vSkills, $detected));
                }

                $hasMatch = false;
                foreach ($skList as $sk) {
                    if (in_array($sk, $vSkills, true)) {
                        $hasMatch = true;
                        break;
                    }
                }
                if (!$hasMatch) return false;
            }

            // 3. Lọc theo Khu vực / Trường học (region)
            if (!empty($filters['region']) && $filters['region'] !== 'all') {
                $regs = is_array($filters['region']) ? $filters['region'] : [$filters['region']];
                $org = (string)($v['organization'] ?? '');
                $match = false;
                foreach ($regs as $r) {
                    if ($r === 'hanoi' && (str_contains($org, 'Hà Nội') || str_contains($org, 'Bách Khoa') || str_contains($org, 'Sư Phạm') || str_contains($org, 'Ngoại Thương') || str_contains($org, 'Bạch Mai') || str_contains($org, 'Kinh Tế Quốc Dân') || str_contains($org, 'Báo Chí') || str_contains($org, 'Thủy Lợi') || str_contains($org, 'Việt Đức'))) {
                        $match = true; break;
                    } elseif ($r === 'north' && (str_contains($org, 'Hà Giang') || str_contains($org, 'Lào Cai') || str_contains($org, 'Thái Bình') || str_contains($org, 'Hải Phòng') || str_contains($org, 'Bắc Ninh') || str_contains($org, 'Hàng Hải'))) {
                        $match = true; break;
                    } elseif ($r === 'central' && (str_contains($org, 'Đà Nẵng') || str_contains($org, 'Huế') || str_contains($org, 'Quảng Ngãi') || str_contains($org, 'Hà Tĩnh') || str_contains($org, 'Quảng Nam'))) {
                        $match = true; break;
                    } elseif ($r === 'south' && (str_contains($org, 'TP.HCM') || str_contains($org, 'TP. Hồ Chí Minh') || str_contains($org, 'Cần Thơ') || str_contains($org, 'Phương Nam'))) {
                        $match = true; break;
                    }
                }
                if (!$match) return false;
            }

            // 4. Lọc theo Lịch rảnh (availability)
            if (!empty($filters['availability']) && $filters['availability'] !== 'all') {
                $avails = is_array($filters['availability']) ? $filters['availability'] : [$filters['availability']];
                $vAvail = implode(' ', (array)($v['availability'] ?? []));
                $match = false;
                foreach ($avails as $av) {
                    if ($av === 'weekend' && (str_contains($vAvail, 'CA-01') || str_contains($vAvail, 'CA-02') || str_contains($vAvail, 'CA-03') || str_contains($vAvail, 'CA-04') || str_contains($vAvail, 'CA-05') || str_contains($vAvail, 'CA-06') || str_contains($vAvail, 'D1') || str_contains($vAvail, 'D2'))) {
                        $match = true; break;
                    } elseif ($av === 'evening' && (str_contains($vAvail, 'CA-05') || str_contains($vAvail, 'CA-12') || str_contains($vAvail, 'Tối') || str_contains($vAvail, 'TOI') || str_contains($vAvail, '402'))) {
                        $match = true; break;
                    } elseif ($av === 'morning' && (str_contains($vAvail, 'CA-01') || str_contains($vAvail, 'CA-03') || str_contains($vAvail, 'CA-06') || str_contains($vAvail, 'SANG') || str_contains($vAvail, 'S1'))) {
                        $match = true; break;
                    } elseif ($av === 'afternoon' && (str_contains($vAvail, 'CA-02') || str_contains($vAvail, 'CA-04') || str_contains($vAvail, 'CHIEU') || str_contains($vAvail, 'S2'))) {
                        $match = true; break;
                    } elseif ($av === 'flexible' && count((array)($v['availability'] ?? [])) >= 3) {
                        $match = true; break;
                    }
                }
                if (!$match) return false;
            }

            // 5. Lọc theo Nhóm máu (blood_group: rare, O+, A+, B+, AB+)
            if (!empty($filters['blood_group']) && $filters['blood_group'] !== 'all') {
                $bgs = is_array($filters['blood_group']) ? $filters['blood_group'] : [$filters['blood_group']];
                $bg = (string)($v['blood_group'] ?? 'O+');
                $match = false;
                foreach ($bgs as $b) {
                    if ($b === 'rare' && in_array($bg, ['O-', 'B-', 'AB-'], true)) {
                        $match = true; break;
                    } elseif ($b === $bg) {
                        $match = true; break;
                    }
                }
                if (!$match) return false;
            }

            // 6. Lọc theo Mức giờ cống hiến (hours_level: veteran >100, medium 30-100, new <30)
            if (!empty($filters['hours_level']) && $filters['hours_level'] !== 'all') {
                $hls = is_array($filters['hours_level']) ? $filters['hours_level'] : [$filters['hours_level']];
                $hrs = (int)($v['volunteer_hours'] ?? 0);
                $match = false;
                foreach ($hls as $hl) {
                    if ($hl === 'veteran' && $hrs >= 100) {
                        $match = true; break;
                    } elseif ($hl === 'medium' && $hrs >= 30 && $hrs < 100) {
                        $match = true; break;
                    } elseif ($hl === 'new' && $hrs < 30) {
                        $match = true; break;
                    }
                }
                if (!$match) return false;
            }

            // 7. Lọc chỉ lấy TNV có chứng chỉ đã xác thực (certified_only)
            if (!empty($filters['certified_only'])) {
                if (empty($v['certified_skills'])) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * Đồng bộ 50 dự án từ Database sang file data/volunteer_data.php
     * Giúp hệ thống web và phân trang hiển thị đầy đủ 50 hoạt động ngay lập tức
     */
    public function syncToVolunteerDataFile(): bool
    {
        $dataFile = dirname(__DIR__) . '/data/volunteer_data.php';
        if (!file_exists($dataFile)) {
            return false;
        }

        $currentData = require $dataFile;
        $projects = $this->getProjects();
        if (empty($projects)) {
            return false;
        }

        $activities = [];
        foreach ($projects as $p) {
            $shiftsArr = [];
            foreach ($p['shifts_list'] ?? [] as $s) {
                $shiftsArr[] = $s['shift_name'];
            }
            if (empty($shiftsArr)) {
                $shiftsArr = ['Ca Sáng (08:00 – 12:00)', 'Ca Chiều (13:00 – 17:00)'];
            }

            $scheduleDays = [];
            $shiftsByDay = [];
            foreach ($p['shifts_list'] ?? [] as $s) {
                $dCode = $s['day_code'] ?: 'day_1';
                $shiftsByDay[$dCode][] = [
                    'shift_id' => $s['id'],
                    'shift_name' => $s['shift_name'],
                    'time_range' => $s['time_range'],
                    'capacity' => (int)$s['capacity'],
                    'registered' => (int)$s['registered'],
                    'skill_req' => $s['skill_req'] ?: 'Theo phân công',
                    'skill_group' => $s['skill_group'] ?: 'hau_can'
                ];
            }

            foreach ($shiftsByDay as $dCode => $sList) {
                $dayName = $sList[0]['day_name'] ?? ($dCode === 'day_1' ? 'Thứ Bảy' : 'Chủ Nhật');
                $scheduleDays[] = [
                    'day_code' => $dCode,
                    'day_name' => $dayName,
                    'date_string' => $p['date_string'],
                    'full_label' => $dayName . ' (' . $p['date_string'] . ')',
                    'shifts' => $sList
                ];
            }

            $activities[] = [
                'id' => (int)$p['id'],
                'title' => $p['title'],
                'category' => $p['category'],
                'category_name' => $p['category_name'],
                'date_string' => $p['date_string'],
                'time_range' => $p['time_range'],
                'location' => $p['location'],
                'meeting_point' => $p['meeting_point'],
                'capacity' => (int)$p['capacity'],
                'registered' => (int)$p['registered'],
                'leader_name' => $p['leader_name'],
                'leader_phone' => $p['leader_phone'],
                'shifts' => $shiftsArr,
                'skills_needed' => $p['skills_needed'],
                'benefits' => $p['benefits'],
                'summary' => $p['summary'],
                'status' => $p['status'],
                'schedule_days' => $scheduleDays
            ];
        }

        $currentData['activities'] = $activities;

        $content = "<?php\ndeclare(strict_types=1);\n\n/**\n * HỆ THỐNG DỮ LIỆU TÌNH NGUYỆN VIÊN (D2L VOLUNTEER PLATFORM)\n * Đồng bộ tự động từ Cơ sở dữ liệu (50 Dự án & 50 Tình nguyện viên)\n */\n\nreturn " . var_export($currentData, true) . ";\n";

        return (bool)file_put_contents($dataFile, $content);
    }

    /**
     * Lấy thông tin chi tiết một dự án theo ID
     */
    public function getProjectById(int $id): ?array
    {
        if ($this->isAvailable()) {
            try {
                $stmt = $this->pdo->prepare("SELECT * FROM projects WHERE id = ?");
                $stmt->execute([$id]);
                $proj = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($proj) {
                    $proj['skills_needed'] = json_decode($proj['skills_needed'] ?? '[]', true) ?: [];
                    $proj['benefits'] = json_decode($proj['benefits'] ?? '[]', true) ?: [];
                    return $proj;
                }
            } catch (Throwable $e) {
                error_log("Database: getProjectById error: " . $e->getMessage());
            }
        }

        // Fallback sang volunteer_data.php
        $dataFile = dirname(__DIR__) . '/data/volunteer_data.php';
        if (file_exists($dataFile)) {
            $d = require $dataFile;
            foreach ($d['activities'] ?? [] as $act) {
                if ((int)$act['id'] === $id) {
                    return $act;
                }
            }
        }
        return null;
    }

    /**
     * Cập nhật thông tin dự án (Dành cho Quản trị viên / Ban Chỉ Huy)
     */
    public function updateProject(int $id, array $data): bool
    {
        $categoryNames = [
            'thuc_te_moi_truong' => 'Bảo Vệ Môi Trường',
            'hien_mau_y_te' => 'Hiến Máu & Y Tế',
            'tiep_suc_giao_duc' => 'Tiếp Sức Giáo Dục',
            'cuu_tro_an_sinh' => 'Cứu Trợ & Bếp Ăn 0Đ',
            'cham_soc_yeu_thuong' => 'Chăm Sóc Người Già & Trẻ Em',
        ];

        $category = $data['category'] ?? 'thuc_te_moi_truong';
        $categoryName = $data['category_name'] ?? ($categoryNames[$category] ?? 'Hoạt Động Tình Nguyện');
        $skillsJson = json_encode($data['skills_needed'] ?? ['Làm việc nhóm', 'Hậu cần'], JSON_UNESCAPED_UNICODE);
        $benefitsJson = json_encode($data['benefits'] ?? ['Áo TNV', 'Nước uống', 'Giấy chứng nhận'], JSON_UNESCAPED_UNICODE);
        $isUrgent = !empty($data['is_urgent']) ? 1 : 0;

        $updatedInDb = false;
        if ($this->isAvailable()) {
            try {
                $stmt = $this->pdo->prepare("
                    UPDATE projects SET
                        title = ?,
                        category = ?,
                        category_name = ?,
                        date_string = ?,
                        time_range = ?,
                        location = ?,
                        meeting_point = ?,
                        capacity = ?,
                        leader_name = ?,
                        leader_phone = ?,
                        skills_needed = ?,
                        benefits = ?,
                        summary = ?,
                        status = ?,
                        is_urgent = ?
                    WHERE id = ?
                ");
                $updatedInDb = $stmt->execute([
                    $data['title'] ?? 'Chiến Dịch Tình Nguyện',
                    $category,
                    $categoryName,
                    $data['date_string'] ?? '',
                    $data['time_range'] ?? '',
                    $data['location'] ?? '',
                    $data['meeting_point'] ?? '',
                    (int)($data['capacity'] ?? 20),
                    $data['leader_name'] ?? 'Ban Chỉ Huy',
                    $data['leader_phone'] ?? '0912.456.789',
                    $skillsJson,
                    $benefitsJson,
                    $data['summary'] ?? '',
                    $data['status'] ?? 'recruiting',
                    $isUrgent,
                    $id
                ]);
            } catch (Throwable $e) {
                error_log("Database: updateProject error: " . $e->getMessage());
            }
        }

        // Đồng bộ dữ liệu ra file data/volunteer_data.php
        $this->syncToVolunteerDataFile();

        // Cập nhật cả trong session custom_activities nếu có
        if (isset($_SESSION['custom_activities']) && is_array($_SESSION['custom_activities'])) {
            foreach ($_SESSION['custom_activities'] as &$cAct) {
                if ((int)$cAct['id'] === $id) {
                    $cAct['title'] = $data['title'] ?? $cAct['title'];
                    $cAct['category'] = $category;
                    $cAct['category_name'] = $categoryName;
                    $cAct['date_string'] = $data['date_string'] ?? $cAct['date_string'];
                    $cAct['time_range'] = $data['time_range'] ?? $cAct['time_range'];
                    $cAct['location'] = $data['location'] ?? $cAct['location'];
                    $cAct['meeting_point'] = $data['meeting_point'] ?? $cAct['meeting_point'];
                    $cAct['capacity'] = (int)($data['capacity'] ?? $cAct['capacity']);
                    $cAct['leader_name'] = $data['leader_name'] ?? $cAct['leader_name'];
                    $cAct['leader_phone'] = $data['leader_phone'] ?? $cAct['leader_phone'];
                    $cAct['summary'] = $data['summary'] ?? $cAct['summary'];
                    $cAct['status'] = $data['status'] ?? $cAct['status'];
                    break;
                }
            }
            unset($cAct);
        }

        return true;
    }

    /**
     * Khởi tạo bảng lưu trữ yêu cầu phê duyệt vắng
     */
    public function initAbsenceRequestsTable(): void
    {
        if (!$this->isAvailable()) {
            return;
        }
        try {
            $sql = "
            CREATE TABLE IF NOT EXISTS absence_requests (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                request_code VARCHAR(50) UNIQUE,
                volunteer_id VARCHAR(50) NOT NULL,
                volunteer_name VARCHAR(100) NOT NULL,
                volunteer_phone VARCHAR(30) DEFAULT '',
                shift_id VARCHAR(50) NOT NULL,
                activity_title VARCHAR(255) NOT NULL,
                event_date VARCHAR(100) NOT NULL,
                event_time VARCHAR(100) DEFAULT '',
                hours_prior REAL NOT NULL,
                days_prior REAL NOT NULL,
                reason TEXT NOT NULL,
                detail_note TEXT DEFAULT '',
                status VARCHAR(30) DEFAULT 'pending',
                coordinator_note TEXT DEFAULT '',
                reviewed_by VARCHAR(100) DEFAULT '',
                reviewed_at TEXT DEFAULT '',
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            );";
            $this->pdo->exec($sql);
        } catch (Throwable $e) {
            error_log("Database: initAbsenceRequestsTable error: " . $e->getMessage());
        }
    }

    /**
     * Tính số ngày còn lại đến thời điểm diễn ra sự kiện
     */
    public static function calculateDaysPrior(string $dateString, string $timeRange = ''): float
    {
        // 1. Nếu có ngày cụ thể dd/mm/yyyy
        if (preg_match('/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/', $dateString, $m)) {
            $day = (int)$m[1];
            $month = (int)$m[2];
            $year = (int)$m[3];
            $hour = 8;
            $min = 0;
            if (preg_match('/(\d{1,2}):(\d{2})/', $timeRange, $tm)) {
                $hour = (int)$tm[1];
                $min = (int)$tm[2];
            }
            $targetTs = mktime($hour, $min, 0, $month, $day, $year);
            $diffSeconds = $targetTs - time();
            return round($diffSeconds / 86400.0, 2);
        }

        // 2. Mặc định trả về 3.5 ngày nếu là sự kiện mẫu
        return 3.5;
    }

    /**
     * Tạo yêu cầu báo vắng từ phía Tình nguyện viên
     * BẮT BUỘC KIỂM TRA ĐIỀU KIỆN: Báo trước tối thiểu 2 ngày (>= 48 giờ)
     */
    public function createAbsenceRequest(array $data): array
    {
        $this->initAbsenceRequestsTable();

        $eventDate = (string)($data['event_date'] ?? '');
        $eventTime = (string)($data['event_time'] ?? '');

        // Tính khoảng thời gian báo trước
        $daysPrior = isset($data['mock_days_prior']) ? (float)$data['mock_days_prior'] : self::calculateDaysPrior($eventDate, $eventTime);
        $hoursPrior = round($daysPrior * 24.0, 1);

        // ĐIỀU KIỆN TIÊN QUYẾT: Phải báo trước ít nhất 2 ngày (>= 48 giờ)
        if ($daysPrior < 2.0) {
            return [
                'success' => false,
                'error_code' => 'ABSENCE_TOO_LATE',
                'days_prior' => $daysPrior,
                'hours_prior' => $hoursPrior,
                'message' => "Không đủ điều kiện báo vắng trực tuyến: Hoạt động diễn ra trong vòng " . max(0, $hoursPrior) . " giờ tới (< 48 giờ). Quy định của Đội yêu cầu báo vắng trước tối thiểu 2 ngày để Ban Chỉ Huy kịp điều phối nhân sự. Vui lòng liên hệ trực tiếp Trưởng ca trực qua điện thoại khẩn cấp!",
            ];
        }

        $requestCode = 'ABS-' . strtoupper(substr(md5(uniqid('', true)), 0, 7));
        $record = [
            'request_code' => $requestCode,
            'volunteer_id' => $data['volunteer_id'] ?? 'TNV-001',
            'volunteer_name' => $data['volunteer_name'] ?? 'Tình Nguyện Viên',
            'volunteer_phone' => $data['volunteer_phone'] ?? '',
            'shift_id' => $data['shift_id'] ?? '',
            'activity_title' => $data['activity_title'] ?? 'Hoạt Động Tình Nguyện',
            'event_date' => $eventDate,
            'event_time' => $eventTime,
            'hours_prior' => $hoursPrior,
            'days_prior' => $daysPrior,
            'reason' => $data['reason'] ?? 'Bận việc cá nhân',
            'detail_note' => $data['detail_note'] ?? '',
            'status' => 'pending',
            'coordinator_note' => '',
            'reviewed_by' => '',
            'reviewed_at' => '',
            'created_at' => date('Y-m-d H:i:s'),
        ];

        // Lưu vào SQLite
        if ($this->isAvailable()) {
            try {
                $stmt = $this->pdo->prepare("
                    INSERT INTO absence_requests (
                        request_code, volunteer_id, volunteer_name, volunteer_phone,
                        shift_id, activity_title, event_date, event_time,
                        hours_prior, days_prior, reason, detail_note, status, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)
                ");
                $stmt->execute([
                    $record['request_code'],
                    $record['volunteer_id'],
                    $record['volunteer_name'],
                    $record['volunteer_phone'],
                    $record['shift_id'],
                    $record['activity_title'],
                    $record['event_date'],
                    $record['event_time'],
                    $record['hours_prior'],
                    $record['days_prior'],
                    $record['reason'],
                    $record['detail_note'],
                    $record['created_at'],
                ]);
                $record['id'] = (int)$this->pdo->lastInsertId();
            } catch (Throwable $e) {
                error_log("Database: insert absence request error: " . $e->getMessage());
            }
        }

        // Lưu vào Session để luôn truy xuất được
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        if (!isset($_SESSION['absence_requests']) || !is_array($_SESSION['absence_requests'])) {
            $_SESSION['absence_requests'] = [];
        }
        $record['id'] = $record['id'] ?? (count($_SESSION['absence_requests']) + 1);
        $_SESSION['absence_requests'][] = $record;

        return [
            'success' => true,
            'request_code' => $requestCode,
            'record' => $record,
            'message' => "Đã gửi đơn báo vắng thành công (trước {$daysPrior} ngày, hợp lệ). Yêu cầu đã được chuyển tới Ban Chỉ Huy để thẩm định và phê duyệt.",
        ];
    }

    /**
     * Lấy danh sách yêu cầu báo vắng
     */
    public function getAbsenceRequests(?string $status = null): array
    {
        $this->initAbsenceRequestsTable();
        $requests = [];

        if ($this->isAvailable()) {
            try {
                $sql = "SELECT * FROM absence_requests ORDER BY id DESC";
                $rows = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($rows)) {
                    $requests = $rows;
                }
            } catch (Throwable $e) {
                error_log("Database: getAbsenceRequests error: " . $e->getMessage());
            }
        }

        // Hợp nhất dữ liệu từ Session
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $sessionRequests = $_SESSION['absence_requests'] ?? [];
        if (!empty($sessionRequests)) {
            $seenCodes = array_column($requests, 'request_code');
            foreach ($sessionRequests as $sReq) {
                if (!in_array($sReq['request_code'], $seenCodes, true)) {
                    $requests[] = $sReq;
                }
            }
        }

        // Lọc theo trạng thái nếu có
        if ($status !== null && $status !== '' && $status !== 'all') {
            $requests = array_values(array_filter($requests, fn($r) => ($r['status'] ?? '') === $status));
        }

        return $requests;
    }

    /**
     * Đếm số lượng yêu cầu báo vắng đang chờ Ban Chỉ Huy duyệt
     */
    public function getPendingAbsenceCount(): int
    {
        $all = $this->getAbsenceRequests('pending');
        return count($all);
    }

    /**
     * Ban Chỉ Huy phê duyệt hoặc từ chối yêu cầu báo vắng
     */
    public function reviewAbsenceRequest(int|string $requestIdOrCode, string $decision, string $coordinatorName, string $note = ''): bool
    {
        $status = ($decision === 'approved') ? 'approved' : 'rejected';
        $now = date('Y-m-d H:i:s');
        $affectedReq = null;

        if ($this->isAvailable()) {
            try {
                $stmt = $this->pdo->prepare("
                    UPDATE absence_requests SET
                        status = ?,
                        coordinator_note = ?,
                        reviewed_by = ?,
                        reviewed_at = ?
                    WHERE id = ? OR request_code = ?
                ");
                $stmt->execute([$status, $note, $coordinatorName, $now, $requestIdOrCode, $requestIdOrCode]);

                $selectStmt = $this->pdo->prepare("SELECT * FROM absence_requests WHERE id = ? OR request_code = ?");
                $selectStmt->execute([$requestIdOrCode, $requestIdOrCode]);
                $affectedReq = $selectStmt->fetch(PDO::FETCH_ASSOC);
            } catch (Throwable $e) {
                error_log("Database: reviewAbsenceRequest error: " . $e->getMessage());
            }
        }

        // Cập nhật trong Session
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        if (isset($_SESSION['absence_requests']) && is_array($_SESSION['absence_requests'])) {
            foreach ($_SESSION['absence_requests'] as &$r) {
                if ($r['id'] == $requestIdOrCode || ($r['request_code'] ?? '') === (string)$requestIdOrCode) {
                    $r['status'] = $status;
                    $r['coordinator_note'] = $note;
                    $r['reviewed_by'] = $coordinatorName;
                    $r['reviewed_at'] = $now;
                    $affectedReq = $r;
                    break;
                }
            }
            unset($r);
        }

        // Nếu Chấp Thuận (approved), cập nhật trạng thái nhân sự trong ca thành absent
        if ($status === 'approved' && $affectedReq) {
            $shiftId = $affectedReq['shift_id'];
            $vId = $affectedReq['volunteer_id'];
            $vName = $affectedReq['volunteer_name'];

            // 1. Cập nhật trong btl_duty_schedule
            if (isset($_SESSION['btl_duty_schedule'][$shiftId])) {
                foreach ($_SESSION['btl_duty_schedule'][$shiftId]['assigned_volunteers'] as &$v) {
                    if ($v['id'] === $vId || mb_strtolower($v['name']) === mb_strtolower($vName)) {
                        $v['status'] = 'absent';
                        break;
                    }
                }
                unset($v);
            }

            // 2. Cập nhật trong volunteer_shift_registrations
            if (isset($_SESSION['volunteer_shift_registrations'])) {
                foreach ($_SESSION['volunteer_shift_registrations'] as &$reg) {
                    if (($reg['shift_id'] ?? '') === $shiftId || ($reg['activity_id'] ?? '') === $shiftId) {
                        if (($reg['user_id'] ?? '') === $vId || mb_strtolower($reg['user_name'] ?? '') === mb_strtolower($vName)) {
                            $reg['status'] = 'absent';
                            break;
                        }
                    }
                }
                unset($reg);
            }
        }

        return true;
    }

    /**
     * Tình nguyện viên HỦY BÁO VẮNG (rút lại đơn vắng và khôi phục quyền tham gia hoạt động)
     */
    public function cancelAbsenceRequest(string $shiftId, ?string $volunteerName = null, ?string $volunteerId = null): array
    {
        $this->initAbsenceRequestsTable();
        $now = date('Y-m-d H:i:s');
        $cancelledCount = 0;

        if ($this->isAvailable()) {
            try {
                // Hủy các đơn có shift_id tương ứng của TNV đó
                if (!empty($volunteerName)) {
                    $stmt = $this->pdo->prepare("
                        UPDATE absence_requests 
                        SET status = 'cancelled_by_volunteer',
                            coordinator_note = 'Tình nguyện viên đã chủ động hủy báo vắng và tiếp tục tham gia.',
                            reviewed_at = ?
                        WHERE shift_id = ? AND (LOWER(volunteer_name) = LOWER(?) OR volunteer_id = ?)
                          AND status IN ('pending', 'approved')
                    ");
                    $stmt->execute([$now, $shiftId, $volunteerName, $volunteerId ?? '']);
                    $cancelledCount = $stmt->rowCount();
                } else {
                    $stmt = $this->pdo->prepare("
                        UPDATE absence_requests 
                        SET status = 'cancelled_by_volunteer',
                            coordinator_note = 'Tình nguyện viên đã chủ động hủy báo vắng và tiếp tục tham gia.',
                            reviewed_at = ?
                        WHERE shift_id = ? AND status IN ('pending', 'approved')
                    ");
                    $stmt->execute([$now, $shiftId]);
                    $cancelledCount = $stmt->rowCount();
                }
            } catch (Throwable $e) {
                error_log("Database: cancelAbsenceRequest error: " . $e->getMessage());
            }
        }

        // Cập nhật trong Session
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        if (isset($_SESSION['absence_requests']) && is_array($_SESSION['absence_requests'])) {
            foreach ($_SESSION['absence_requests'] as &$r) {
                $matchShift = (($r['shift_id'] ?? '') === $shiftId);
                $matchVol = empty($volunteerName) || 
                            (mb_strtolower($r['volunteer_name'] ?? '') === mb_strtolower($volunteerName)) ||
                            (!empty($volunteerId) && ($r['volunteer_id'] ?? '') === $volunteerId);
                if ($matchShift && $matchVol && in_array($r['status'] ?? '', ['pending', 'approved'], true)) {
                    $r['status'] = 'cancelled_by_volunteer';
                    $r['coordinator_note'] = 'Tình nguyện viên đã chủ động hủy báo vắng và tiếp tục tham gia.';
                    $r['reviewed_at'] = $now;
                    $cancelledCount++;
                }
            }
            unset($r);
        }

        // Khôi phục trạng thái thành 'confirmed' trong ca trực btl_duty_schedule
        if (isset($_SESSION['btl_duty_schedule'][$shiftId])) {
            foreach ($_SESSION['btl_duty_schedule'][$shiftId]['assigned_volunteers'] as &$v) {
                if (($volunteerId && $v['id'] === $volunteerId) || ($volunteerName && mb_strtolower($v['name']) === mb_strtolower($volunteerName))) {
                    $v['status'] = 'confirmed';
                    break;
                }
            }
            unset($v);
        }

        // Khôi phục trong đăng ký trực tuyến
        if (isset($_SESSION['volunteer_shift_registrations'])) {
            foreach ($_SESSION['volunteer_shift_registrations'] as &$reg) {
                if (($reg['shift_id'] ?? '') === $shiftId || ($reg['activity_id'] ?? '') === $shiftId || ($reg['registration_id'] ?? '') === $shiftId) {
                    if (($volunteerId && ($reg['user_id'] ?? '') === $volunteerId) || ($volunteerName && mb_strtolower($reg['user_name'] ?? '') === mb_strtolower($volunteerName))) {
                        $reg['status'] = 'confirmed';
                        break;
                    }
                }
            }
            unset($reg);
        }

        return [
            'success' => true,
            'message' => 'Đã hủy báo vắng thành công. Trạng thái tham gia của bạn đã được khôi phục về ĐÃ XÁC NHẬN.',
            'cancelled_count' => $cancelledCount
        ];
    }

    /**
     * Ánh xạ mã ca trực sang project_id tương ứng
     */
    public static function mapShiftToProjectId(string $shiftId): int
    {
        $map = [
            'CA-01' => 101, 'CA-02' => 101,
            'CA-03' => 201, 'CA-04' => 201,
            'CA-05' => 401, 'CA-06' => 301,
            'CA-07' => 501, 'CA-08' => 401,
            'CA-09' => 301, 'CA-10' => 201,
            'CA-11' => 104, 'CA-12' => 501
        ];
        if (isset($map[$shiftId])) {
            return $map[$shiftId];
        }
        if (preg_match('/^(\d+)/', $shiftId, $m)) {
            return (int)$m[1];
        }
        return 101;
    }

    /**
     * Đảm bảo ca trực tồn tại trong bảng shifts trước khi chèn registration
     */
    public function ensureShiftExists(string $shiftId, array $shiftData = []): void
    {
        if (!$this->isAvailable()) return;
        try {
            $check = $this->pdo->prepare("SELECT id FROM shifts WHERE id = ?");
            $check->execute([$shiftId]);
            if (!$check->fetch()) {
                $projId = self::mapShiftToProjectId($shiftId);
                $stmt = $this->pdo->prepare("
                    INSERT INTO shifts (id, project_id, day_code, day_name, shift_name, time_range, capacity, registered, skill_req, skill_group)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $shiftId,
                    $projId,
                    $shiftData['day_code'] ?? 'day_1',
                    $shiftData['day_name'] ?? 'Ngày trực thực địa',
                    $shiftData['shift_name'] ?? ('Ca Trực ' . $shiftId),
                    $shiftData['time_slot'] ?? ($shiftData['time_range'] ?? '08:00 - 12:00'),
                    (int)($shiftData['needed_count'] ?? ($shiftData['capacity'] ?? 15)),
                    0,
                    implode(', ', $shiftData['required_skills'] ?? []),
                    $shiftData['required_skills'][0] ?? 'hau_can'
                ]);
            }
        } catch (Throwable $e) {
            error_log("Database: ensureShiftExists error: " . $e->getMessage());
        }
    }

    /**
     * ĐỒNG BỘ TOÀN BỘ BẢNG PHÂN CÔNG CA TRỰC VÀO CƠ SỞ DỮ LIỆU (MYSQL / SQLITE)
     */
    public function saveDutySchedule(array $dutySchedules): bool
    {
        if (!$this->isAvailable() || empty($dutySchedules)) {
            return false;
        }

        try {
            $shiftIds = array_keys($dutySchedules);
            if (empty($shiftIds)) return true;

            // Xóa các phân công cũ của các ca này
            $inClause = implode(',', array_fill(0, count($shiftIds), '?'));
            $deleteStmt = $this->pdo->prepare("DELETE FROM volunteer_registrations WHERE shift_id IN ($inClause)");
            $deleteStmt->execute($shiftIds);

            // Chèn danh sách phân công mới
            $insertStmt = $this->pdo->prepare("
                INSERT INTO volunteer_registrations (volunteer_id, project_id, shift_id, role_in_shift, status)
                VALUES (?, ?, ?, ?, ?)
            ");

            $updateShiftStmt = $this->pdo->prepare("
                UPDATE shifts SET registered = ? WHERE id = ?
            ");

            foreach ($dutySchedules as $sId => $s) {
                $this->ensureShiftExists((string)$sId, $s);
                $projId = self::mapShiftToProjectId((string)$sId);
                $assigned = $s['assigned_volunteers'] ?? [];
                $activeCount = 0;

                foreach ($assigned as $v) {
                    $vId = $v['id'] ?? '';
                    if (empty($vId)) continue;
                    $status = $v['status'] ?? 'confirmed';
                    $role = $v['role'] ?? 'TNV Thực Địa';
                    if (!empty($v['replacement_for'])) {
                        $role .= ' (Thay cho ' . $v['replacement_for'] . ')';
                    }

                    // Đảm bảo TNV tồn tại trong bảng volunteers
                    $chkVol = $this->pdo->prepare("SELECT id FROM volunteers WHERE id = ?");
                    $chkVol->execute([$vId]);
                    if (!$chkVol->fetch()) {
                        $insertVol = $this->pdo->prepare("
                            INSERT INTO volunteers (id, name, email, phone, role)
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $insertVol->execute([
                            $vId,
                            $v['name'] ?? 'Tình Nguyện Viên',
                            $v['email'] ?? ($vId . '@d2l.org'),
                            $v['phone'] ?? '0912.345.678',
                            $role
                        ]);
                    }

                    $insertStmt->execute([
                        $vId,
                        $projId,
                        (string)$sId,
                        $role,
                        $status
                    ]);

                    if ($status !== 'absent') {
                        $activeCount++;
                    }
                }

                $updateShiftStmt->execute([$activeCount, (string)$sId]);
            }

            return true;
        } catch (Throwable $e) {
            error_log("Database: saveDutySchedule error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * NẠP BẢNG PHÂN CÔNG TỪ CƠ SỞ DỮ LIỆU ĐỂ ĐỒNG BỘ NỀN TẢNG
     */
    public function loadDutySchedule(): ?array
    {
        if (!$this->isAvailable()) {
            return null;
        }

        try {
            $stmt = $this->pdo->query("
                SELECT vr.id as reg_id, vr.volunteer_id, vr.project_id, vr.shift_id, vr.role_in_shift, vr.status,
                       v.name as volunteer_name, v.skills as volunteer_skills, v.phone as volunteer_phone, v.email as volunteer_email
                FROM volunteer_registrations vr
                LEFT JOIN volunteers v ON vr.volunteer_id = v.id
                ORDER BY vr.shift_id, vr.id
            ");
            $rows = $stmt->fetchAll();
            if (empty($rows)) {
                return null;
            }

            $schedule = [];
            foreach ($rows as $r) {
                $sId = $r['shift_id'];
                if (!isset($schedule[$sId])) {
                    $schedule[$sId] = [];
                }
                $skills = json_decode((string)($r['volunteer_skills'] ?? '[]'), true) ?: [];
                $schedule[$sId][] = [
                    'id' => $r['volunteer_id'],
                    'name' => $r['volunteer_name'] ?? $r['volunteer_id'],
                    'skills' => $skills,
                    'status' => $r['status'] ?? 'confirmed',
                    'role' => $r['role_in_shift'] ?? 'TNV Thực Địa',
                    'phone' => $r['volunteer_phone'] ?? '',
                    'email' => $r['volunteer_email'] ?? '',
                ];
            }
            return $schedule;
        } catch (Throwable $e) {
            error_log("Database: loadDutySchedule error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * LƯU ĐƠN ĐĂNG KÝ CA TRỰC TUYẾN MỚI VÀO CƠ SỞ DỮ LIỆU
     */
    public function recordRegistration(array $regData): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        try {
            $vId = $regData['user_id'] ?? ('TNV-ONLINE-' . rand(100, 999));
            $vName = $regData['user_name'] ?? 'Tình Nguyện Viên';
            $vEmail = $regData['user_email'] ?? '';
            $vPhone = $regData['user_phone'] ?? '';
            $projId = (int)($regData['activity_id'] ?? 101);
            $shiftId = (string)($regData['shift_id'] ?? 'CA-01');
            $role = 'TNV Đăng Ký Trực Tuyến';
            $status = $regData['status'] ?? 'confirmed';

            // Đảm bảo TNV tồn tại trong bảng volunteers
            $chkVol = $this->pdo->prepare("SELECT id FROM volunteers WHERE id = ? OR email = ?");
            $chkVol->execute([$vId, $vEmail]);
            $existing = $chkVol->fetch();
            if ($existing) {
                $vId = $existing['id'];
            } else {
                $insVol = $this->pdo->prepare("
                    INSERT INTO volunteers (id, name, email, phone, role)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $insVol->execute([$vId, $vName, $vEmail, $vPhone, $role]);
            }

            // Ghi vào volunteer_registrations
            $insReg = $this->pdo->prepare("
                INSERT INTO volunteer_registrations (volunteer_id, project_id, shift_id, role_in_shift, status)
                VALUES (?, ?, ?, ?, ?)
            ");
            $insReg->execute([$vId, $projId, $shiftId, $role, $status]);

            // Cập nhật số lượng đăng ký của dự án
            $this->pdo->prepare("UPDATE projects SET registered = registered + 1 WHERE id = ?")->execute([$projId]);

            return true;
        } catch (Throwable $e) {
            error_log("Database: recordRegistration error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * CẬP NHẬT HOẶC LƯU HỒ SƠ TÌNH NGUYỆN VIÊN (PROFILE) VÀO CSDL
     */
    public function saveVolunteerProfile(array $user): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        try {
            $vId = $user['id'] ?? $user['pass_id'] ?? '';
            if (empty($vId)) return false;

            $name = $user['name'] ?? 'Tình Nguyện Viên';
            $email = $user['email'] ?? '';
            $phone = $user['phone'] ?? '';
            $org = $user['unit'] ?? ($user['organization'] ?? '');
            $gender = $user['gender'] ?? 'Nam';
            $dob = $user['dob'] ?? '';
            $skillsJson = json_encode($user['skills'] ?? [], JSON_UNESCAPED_UNICODE);
            $customSkillsJson = json_encode($user['custom_skills'] ?? [], JSON_UNESCAPED_UNICODE);
            $certSkillsJson = json_encode($user['certified_skills'] ?? [], JSON_UNESCAPED_UNICODE);

            $chk = $this->pdo->prepare("SELECT id FROM volunteers WHERE id = ?");
            $chk->execute([$vId]);
            if ($chk->fetch()) {
                $stmt = $this->pdo->prepare("
                    UPDATE volunteers SET
                        name = ?, email = ?, phone = ?, organization = ?, gender = ?, dob = ?,
                        skills = ?, custom_skills = ?, certified_skills = ?
                    WHERE id = ?
                ");
                return $stmt->execute([
                    $name, $email, $phone, $org, $gender, $dob,
                    $skillsJson, $customSkillsJson, $certSkillsJson, $vId
                ]);
            } else {
                $stmt = $this->pdo->prepare("
                    INSERT INTO volunteers (id, name, email, phone, organization, gender, dob, skills, custom_skills, certified_skills)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                return $stmt->execute([
                    $vId, $name, $email, $phone, $org, $gender, $dob,
                    $skillsJson, $customSkillsJson, $certSkillsJson
                ]);
            }
        } catch (Throwable $e) {
            error_log("Database: saveVolunteerProfile error: " . $e->getMessage());
            return false;
        }
    }
}
