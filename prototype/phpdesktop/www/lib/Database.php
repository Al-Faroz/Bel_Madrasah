<?php
declare(strict_types=1);

// Deployment target saat ini menggunakan WIB (Asia/Jakarta); samakan jam halaman, resolver, dan log meskipun php.ini runtime tidak menetapkan date.timezone.
date_default_timezone_set('Asia/Jakarta');

/**
 * Tahap 2: database aplikasi utama.
 * Semua data permanen berada di AppData/LocalAppData, bukan di folder runtime.
 */
final class Database
{
    private PDO $pdo;
    private string $path;
    private const SCHEMA_VERSION = 2;

    public function __construct()
    {
        if (!extension_loaded('pdo_sqlite')) {
            throw new RuntimeException('Ekstensi PDO SQLite tidak tersedia pada runtime ini.');
        }

        $root = getenv('APPDATA');
        if ($root === false || trim($root) === '') {
            $root = getenv('LOCALAPPDATA');
        }
        if ($root === false || trim($root) === '') {
            throw new RuntimeException('Folder AppData/LocalAppData Windows tidak ditemukan.');
        }

        $directory = rtrim($root, "\\\\/") . DIRECTORY_SEPARATOR . 'BelMadrasah' . DIRECTORY_SEPARATOR . 'data';
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException('Folder data aplikasi tidak dapat dibuat. Periksa izin tulis pengguna Windows.');
        }

        $this->path = $directory . DIRECTORY_SEPARATOR . 'bel-madrasah.sqlite';
        $this->pdo = new PDO('sqlite:' . $this->path);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        $this->pdo->exec('PRAGMA busy_timeout = 5000');
        $this->initializeSchema();
    }

    private function initializeSchema(): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS app_meta (
                    meta_key TEXT PRIMARY KEY,
                    meta_value TEXT NOT NULL,
                    updated_at TEXT NOT NULL
                )'
            );
            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS app_settings (
                    setting_key TEXT PRIMARY KEY,
                    setting_value TEXT NOT NULL,
                    updated_at TEXT NOT NULL
                )'
            );
            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS schedule_packages (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL COLLATE NOCASE UNIQUE,
                    description TEXT NOT NULL DEFAULT \'\',
                    is_active INTEGER NOT NULL DEFAULT 0 CHECK (is_active IN (0, 1)),
                    created_at TEXT NOT NULL,
                    updated_at TEXT NOT NULL
                )'
            );
            // Migration additive: existing packages default to the established Sat–Sun off pattern.
            $packageColumns = $this->pdo->query('PRAGMA table_info(schedule_packages)')->fetchAll();
            $hasOffDays = false;
            foreach ($packageColumns as $column) {
                if ((string) $column['name'] === 'off_days') {
                    $hasOffDays = true;
                    break;
                }
            }
            if (!$hasOffDays) {
                $this->pdo->exec("ALTER TABLE schedule_packages ADD COLUMN off_days TEXT NOT NULL DEFAULT '6,7'");
            }

            $this->pdo->exec(
                'CREATE UNIQUE INDEX IF NOT EXISTS ux_schedule_packages_one_active
                 ON schedule_packages (is_active) WHERE is_active = 1'
            );
            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS day_rules (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    package_id INTEGER NOT NULL,
                    weekday INTEGER NOT NULL CHECK (weekday BETWEEN 1 AND 7),
                    rule_mode TEXT NOT NULL DEFAULT \'normal\' CHECK (rule_mode IN (\'normal\', \'special\')),
                    special_type TEXT NULL CHECK (special_type IN (\'pasaran\', \'month_week\') OR special_type IS NULL),
                    special_value TEXT NULL,
                    is_enabled INTEGER NOT NULL DEFAULT 1 CHECK (is_enabled IN (0, 1)),
                    created_at TEXT NOT NULL,
                    updated_at TEXT NOT NULL,
                    FOREIGN KEY (package_id) REFERENCES schedule_packages(id) ON DELETE CASCADE,
                    CHECK (
                        (rule_mode = \'normal\' AND special_type IS NULL AND special_value IS NULL)
                        OR
                        (rule_mode = \'special\' AND special_type IS NOT NULL AND special_value IS NOT NULL)
                    )
                )'
            );
            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS bell_events (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    day_rule_id INTEGER NOT NULL,
                    name TEXT NOT NULL,
                    event_time TEXT NOT NULL CHECK (event_time GLOB \'[0-2][0-9]:[0-5][0-9]\'),
                    audio_path TEXT NOT NULL DEFAULT \'\',
                    is_enabled INTEGER NOT NULL DEFAULT 1 CHECK (is_enabled IN (0, 1)),
                    sort_order INTEGER NOT NULL DEFAULT 0,
                    created_at TEXT NOT NULL,
                    updated_at TEXT NOT NULL,
                    FOREIGN KEY (day_rule_id) REFERENCES day_rules(id) ON DELETE CASCADE
                )'
            );
            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS playback_log (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    package_id INTEGER NULL,
                    event_id INTEGER NULL,
                    scheduled_for TEXT NOT NULL,
                    status TEXT NOT NULL CHECK (status IN (\'started\', \'completed\', \'failed\', \'missed\', \'skipped\')),
                    message TEXT NOT NULL DEFAULT \'\',
                    created_at TEXT NOT NULL,
                    FOREIGN KEY (package_id) REFERENCES schedule_packages(id) ON DELETE SET NULL,
                    FOREIGN KEY (event_id) REFERENCES bell_events(id) ON DELETE SET NULL
                )'
            );

            $now = date('Y-m-d H:i:s');
            $statement = $this->pdo->prepare(
                'INSERT INTO app_meta (meta_key, meta_value, updated_at)
                 VALUES (\'schema_version\', :version, :updated_at)
                 ON CONFLICT(meta_key) DO UPDATE SET meta_value = excluded.meta_value, updated_at = excluded.updated_at'
            );
            $statement->execute([':version' => (string) self::SCHEMA_VERSION, ':updated_at' => $now]);

            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function getSchemaVersion(): int
    {
        $value = $this->pdo->query("SELECT meta_value FROM app_meta WHERE meta_key = 'schema_version'")->fetchColumn();
        return (int) $value;
    }

    /** Perform a write/read/rollback check without leaving test data behind. */
    public function runSelfTest(): bool
    {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->exec('CREATE TEMP TABLE IF NOT EXISTS stage2_probe (value TEXT NOT NULL)');
            $this->pdo->exec('DELETE FROM stage2_probe');
            $statement = $this->pdo->prepare('INSERT INTO stage2_probe (value) VALUES (:value)');
            $statement->execute([':value' => 'BM-STAGE2-OK']);
            $value = $this->pdo->query('SELECT value FROM stage2_probe LIMIT 1')->fetchColumn();
            $this->pdo->rollBack();
            return $value === 'BM-STAGE2-OK';
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function getSetting(string $key, string $default = ''): string
    {
        $statement = $this->pdo->prepare('SELECT setting_value FROM app_settings WHERE setting_key = :key');
        $statement->execute([':key' => $key]);
        $value = $statement->fetchColumn();
        return $value === false ? $default : (string) $value;
    }
    public function saveSettings(array $settings): void
    {
        $allowed = ['school_name', 'operator_name', 'default_volume', 'school_logo_path'];
        $now = date('Y-m-d H:i:s');
        $statement = $this->pdo->prepare(
            'INSERT INTO app_settings (setting_key, setting_value, updated_at)
             VALUES (:key, :value, :updated_at)
             ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value, updated_at = excluded.updated_at'
        );

        $this->pdo->beginTransaction();
        try {
            foreach ($settings as $key => $value) {
                if (!in_array($key, $allowed, true)) {
                    continue;
                }
                $statement->execute([
                    ':key' => $key,
                    ':value' => trim((string) $value),
                    ':updated_at' => $now,
                ]);
            }
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function getBackupDirectory(): string
    {
        $root = getenv('APPDATA');
        if ($root === false || trim($root) === '') {
            $root = getenv('LOCALAPPDATA');
        }
        if ($root === false || trim($root) === '') {
            throw new RuntimeException('Folder AppData/LocalAppData Windows tidak ditemukan.');
        }
        $directory = rtrim($root, "\\\\/") . DIRECTORY_SEPARATOR . 'BelMadrasah' . DIRECTORY_SEPARATOR . 'backups';
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException('Folder backup tidak dapat dibuat.');
        }
        return $directory;
    }

    /** @return array<int, array{name:string,path:string,size:int,modified:int}> */
    public function listBackups(): array
    {
        $directory = realpath($this->getBackupDirectory());
        if ($directory === false) {
            return [];
        }
        $backups = [];
        foreach (scandir($directory) ?: [] as $name) {
            if ($name === '.' || $name === '..' || strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'sqlite') {
                continue;
            }
            $path = $directory . DIRECTORY_SEPARATOR . $name;
            $realPath = realpath($path);
            if ($realPath === false || !is_file($realPath) || dirname($realPath) !== $directory) {
                continue;
            }
            $backups[] = [
                'name' => $name,
                'path' => $realPath,
                'size' => (int) (filesize($realPath) ?: 0),
                'modified' => (int) (filemtime($realPath) ?: 0),
            ];
        }
        usort($backups, static fn (array $a, array $b): int => $b['modified'] <=> $a['modified']);
        return $backups;
    }

    private function resolveManagedBackupPath(string $source): string
    {
        $directory = realpath($this->getBackupDirectory());
        $realPath = realpath($source);
        if ($directory === false || $realPath === false || !is_file($realPath)
            || dirname($realPath) !== $directory
            || strtolower(pathinfo($realPath, PATHINFO_EXTENSION)) !== 'sqlite') {
            throw new InvalidArgumentException('File backup tidak valid atau berada di luar folder backup aplikasi.');
        }
        return $realPath;
    }

    /** Validate SQLite integrity and the minimum application tables before restore. */
    private function validateBackupDatabase(string $path): array
    {
        if (!is_file($path) || filesize($path) === false || filesize($path) <= 0) {
            throw new InvalidArgumentException('File backup kosong atau tidak tersedia.');
        }
        $candidate = new PDO('sqlite:' . $path);
        $candidate->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $integrity = (string) $candidate->query('PRAGMA integrity_check')->fetchColumn();
        if (strtolower($integrity) !== 'ok') {
            throw new RuntimeException('Pemeriksaan integritas SQLite gagal: ' . $integrity);
        }
        $tables = $candidate->query(
            "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"
        )->fetchAll(PDO::FETCH_COLUMN);
        $requiredTables = ['app_meta', 'app_settings', 'schedule_packages', 'day_rules', 'bell_events'];
        foreach ($requiredTables as $requiredTable) {
            if (!in_array($requiredTable, $tables, true)) {
                throw new InvalidArgumentException('File backup bukan database Bel Madrasah yang lengkap. Tabel hilang: ' . $requiredTable);
            }
        }
        $version = $candidate->query(
            "SELECT meta_value FROM app_meta WHERE meta_key = 'schema_version'"
        )->fetchColumn();
        if ($version === false || !is_numeric($version) || (int) $version > self::SCHEMA_VERSION) {
            throw new InvalidArgumentException('Versi skema backup tidak dikenal atau lebih baru daripada aplikasi ini.');
        }
        return ['integrity' => $integrity, 'schema_version' => (int) $version, 'table_count' => count($tables)];
    }

    public function inspectBackup(string $source): array
    {
        $path = $this->resolveManagedBackupPath($source);
        return $this->validateBackupDatabase($path);
    }

    /**
     * Restore a verified backup only after creating a separate pre-restore backup.
     * If replacement/reinitialization fails, attempt to put the pre-restore copy back.
     */
    public function restoreFromBackup(string $source, string $preRestoreDestination): string
    {
        $sourcePath = $this->resolveManagedBackupPath($source);
        $this->validateBackupDatabase($sourcePath);

        $backupDirectory = realpath($this->getBackupDirectory());
        $destinationDirectory = realpath(dirname($preRestoreDestination));
        if ($backupDirectory === false || $destinationDirectory !== $backupDirectory
            || strtolower(pathinfo($preRestoreDestination, PATHINFO_EXTENSION)) !== 'sqlite') {
            throw new InvalidArgumentException('Lokasi backup pra-pemulihan tidak valid.');
        }

        $preRestorePath = $this->createBackup($preRestoreDestination);
        unset($this->pdo);

        try {
            clearstatcache(true, $this->path);
            if (!is_writable(dirname($this->path))) {
                throw new RuntimeException('Folder database aktif tidak dapat ditulis oleh pengguna Windows.');
            }
            if (!@copy($sourcePath, $this->path)) {
                $copyError = error_get_last();
                $detail = is_array($copyError) && isset($copyError['message']) ? ': ' . $copyError['message'] : '';
                throw new RuntimeException('Windows tidak dapat mengganti database aktif dari file backup' . $detail);
            }
            clearstatcache(true, $this->path);
            if (!is_file($this->path) || filesize($this->path) === false || filesize($this->path) <= 0) {
                throw new RuntimeException('File database hasil pemulihan kosong atau tidak terbentuk.');
            }

            $this->pdo = new PDO('sqlite:' . $this->path);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->pdo->exec('PRAGMA foreign_keys = ON');
            $this->pdo->exec('PRAGMA busy_timeout = 5000');
            $this->initializeSchema();

            $verification = $this->validateBackupDatabase($this->path);
            if (strtolower((string) ($verification['integrity'] ?? '')) !== 'ok') {
                throw new RuntimeException('Verifikasi integritas database setelah pemulihan gagal.');
            }

            return $preRestorePath;
        } catch (Throwable $exception) {
            unset($this->pdo);
            $rolledBack = is_file($preRestorePath) && @copy($preRestorePath, $this->path);
            $this->pdo = new PDO('sqlite:' . $this->path);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->pdo->exec('PRAGMA foreign_keys = ON');
            $this->pdo->exec('PRAGMA busy_timeout = 5000');
            if ($rolledBack) {
                $this->initializeSchema();
                throw new RuntimeException(
                    'Restore gagal. Database aktif sudah dikembalikan dari backup pra-pemulihan. Detail: ' . $exception->getMessage(),
                    0,
                    $exception
                );
            }
            throw new RuntimeException(
                'Restore gagal dan pemulihan otomatis juga gagal. Jangan tutup aplikasi; backup pra-pemulihan berada di: ' . $preRestorePath,
                0,
                $exception
            );
        }
    }

    /**
     * Create a consistent SQLite backup using VACUUM INTO.
     * The caller must provide a new destination path that does not exist yet.
     */
    public function createBackup(string $destination): string
    {        $directory = dirname($destination);
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException('Folder backup tidak dapat dibuat.');
        }
        if (file_exists($destination)) {
            throw new RuntimeException('File backup sudah ada. Buat nama backup baru.');
        }

        $escapedDestination = str_replace("'", "''", $destination);
        $this->pdo->exec("VACUUM INTO '" . $escapedDestination . "'");
        if (!is_file($destination) || filesize($destination) === 0) {
            throw new RuntimeException('Backup tidak terbentuk atau ukurannya kosong.');
        }

        return $destination;
    }

    /**
     * Seed one sensible starting package only when the package list is empty.
     * Existing user packages and the selected active package are never replaced.
     */
    public function ensureDefaultSchedulePackage(): void
    {
        $count = (int) $this->pdo->query('SELECT COUNT(*) FROM schedule_packages')->fetchColumn();
        if ($count > 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $statement = $this->pdo->prepare(
            'INSERT INTO schedule_packages (name, description, is_active, created_at, updated_at)
             VALUES (:name, :description, 1, :created_at, :updated_at)'
        );
        $statement->execute([
            ':name' => 'Normal',
            ':description' => 'Paket jadwal utama sekolah.',
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);
    }

    /**
     * Populate a clearly editable starter schedule only when the database has
     * no day rules at all. Never overwrite a schedule that the user has begun
     * configuring. The times are examples and must be reviewed by the school.
     */
    public function ensureStarterScheduleRules(int $packageId): bool
    {
        if ($this->getDayRules($packageId) !== [] || $this->getSchedulePackage($packageId) === null) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $this->pdo->beginTransaction();
        try {
            // Seed only school days according to this package's weekly off-day choice.
            $insertRule = $this->pdo->prepare(
                "INSERT INTO day_rules
                    (package_id, weekday, rule_mode, special_type, special_value, is_enabled, created_at, updated_at)
                 VALUES (:package_id, :weekday, 'normal', NULL, NULL, 1, :created_at, :updated_at)"
            );
            $insertEvent = $this->pdo->prepare(
                'INSERT INTO bell_events
                    (day_rule_id, name, event_time, audio_path, is_enabled, sort_order, created_at, updated_at)
                 VALUES (:day_rule_id, :name, :event_time, \'\', 1, :sort_order, :created_at, :updated_at)'
            );
            $starterEvents = [
                ['name' => 'Bel masuk', 'time' => '07:00', 'order' => 1],
                ['name' => 'Bel istirahat', 'time' => '09:30', 'order' => 2],
                ['name' => 'Bel pulang', 'time' => '12:00', 'order' => 3],
            ];
            $package = $this->getSchedulePackage($packageId);
            $offDays = array_map('intval', explode(',', (string) ($package['off_days'] ?? '6,7')));
            for ($weekday = 1; $weekday <= 7; $weekday++) {
                if (in_array($weekday, $offDays, true)) {
                    continue;
                }
                $insertRule->execute([
                    ':package_id' => $packageId,
                    ':weekday' => $weekday,
                    ':created_at' => $now,
                    ':updated_at' => $now,
                ]);
                $ruleId = (int) $this->pdo->lastInsertId();
                foreach ($starterEvents as $event) {
                    $insertEvent->execute([
                        ':day_rule_id' => $ruleId,
                        ':name' => $event['name'],
                        ':event_time' => $event['time'],
                        ':sort_order' => $event['order'],
                        ':created_at' => $now,
                        ':updated_at' => $now,
                    ]);
                }
            }
            $this->pdo->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    /**
     * Create default Normal day rules for a newly created package based on its off days.
     * Example events are starter rows, editable by the operator.
     */
    public function initializePackageDays(int $packageId, string $offDays): void
    {
        if (!in_array($offDays, ['5', '7', '6,7'], true)) {
            throw new InvalidArgumentException('Pilihan hari libur mingguan tidak valid.');
        }
        if ($this->getSchedulePackage($packageId) === null) {
            throw new InvalidArgumentException('Paket jadwal tidak ditemukan.');
        }
        if ($this->getDayRules($packageId) !== []) {
            return;
        }
        $daysOff = array_map('intval', explode(',', $offDays));
        $now = date('Y-m-d H:i:s');
        $this->pdo->beginTransaction();
        try {
            $insertRule = $this->pdo->prepare(
                "INSERT INTO day_rules
                    (package_id, weekday, rule_mode, special_type, special_value, is_enabled, created_at, updated_at)
                 VALUES (:package_id, :weekday, 'normal', NULL, NULL, 1, :created_at, :updated_at)"
            );
            $insertEvent = $this->pdo->prepare(
                "INSERT INTO bell_events
                    (day_rule_id, name, event_time, audio_path, is_enabled, sort_order, created_at, updated_at)
                 VALUES (:day_rule_id, :name, :event_time, '', 1, :sort_order, :created_at, :updated_at)"
            );
            $starterEvents = [
                ['name' => 'Bel masuk', 'time' => '07:00', 'order' => 1],
                ['name' => 'Bel istirahat', 'time' => '09:30', 'order' => 2],
                ['name' => 'Bel pulang', 'time' => '12:00', 'order' => 3],
            ];
            for ($weekday = 1; $weekday <= 7; $weekday++) {
                if (in_array($weekday, $daysOff, true)) {
                    continue;
                }
                $insertRule->execute([
                    ':package_id' => $packageId,
                    ':weekday' => $weekday,
                    ':created_at' => $now,
                    ':updated_at' => $now,
                ]);
                $ruleId = (int) $this->pdo->lastInsertId();
                foreach ($starterEvents as $event) {
                    $insertEvent->execute([
                        ':day_rule_id' => $ruleId,
                        ':name' => $event['name'],
                        ':event_time' => $event['time'],
                        ':sort_order' => $event['order'],
                        ':created_at' => $now,
                        ':updated_at' => $now,
                    ]);
                }
            }
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function getSchedulePackages(): array
    {
        return $this->pdo->query(
            'SELECT id, name, description, off_days, is_active, created_at, updated_at
             FROM schedule_packages ORDER BY is_active DESC, name COLLATE NOCASE ASC, id ASC'
        )->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function getSchedulePackage(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, description, off_days, is_active, created_at, updated_at
             FROM schedule_packages WHERE id = :id'
        );
        $statement->execute([':id' => $id]);
        $package = $statement->fetch();
        return $package === false ? null : $package;
    }

    public function saveSchedulePackage(?int $id, string $name, string $description, string $offDays = '6,7'): int
    {
        $name = trim($name);
        $description = trim($description);
        if ($name === '' || strlen($name) > 80) {
            throw new InvalidArgumentException('Nama paket wajib diisi dan maksimal 80 karakter.');
        }
        if (strlen($description) > 300) {
            throw new InvalidArgumentException('Keterangan maksimal 300 karakter.');        }
        if (!in_array($offDays, ['5', '7', '6,7'], true)) {
            throw new InvalidArgumentException('Pilihan hari libur mingguan tidak valid.');
        }

        $now = date('Y-m-d H:i:s');
        if ($id === null) {
            $statement = $this->pdo->prepare(
                'INSERT INTO schedule_packages (name, description, off_days, is_active, created_at, updated_at)
                 VALUES (:name, :description, :off_days, 0, :created_at, :updated_at)'
            );
            $statement->execute([
                ':name' => $name,
                ':description' => $description,
                ':off_days' => $offDays,
                ':created_at' => $now,
                ':updated_at' => $now,
            ]);
            return (int) $this->pdo->lastInsertId();
        }

        if ($this->getSchedulePackage($id) === null) {
            throw new RuntimeException('Paket yang akan diubah tidak ditemukan.');
        }
        $statement = $this->pdo->prepare(
            'UPDATE schedule_packages SET name = :name, description = :description, off_days = :off_days, updated_at = :updated_at
             WHERE id = :id'
        );
        $statement->execute([
            ':name' => $name,
            ':description' => $description,
            ':off_days' => $offDays,
            ':updated_at' => $now,
            ':id' => $id,
        ]);
        return $id;
    }

    /**
     * Permanently delete a non-active package and its dependent rules/events.
     * Playback history is retained by its ON DELETE SET NULL foreign keys.
     */
    public function hardDeleteSchedulePackage(int $id): array
    {
        $package = $this->getSchedulePackage($id);
        if ($package === null) {
            throw new InvalidArgumentException('Paket yang akan dihapus tidak ditemukan.');
        }
        if ((int) $package['is_active'] === 1) {
            throw new InvalidArgumentException('Paket aktif tidak dapat dihapus. Aktifkan paket lain terlebih dahulu.');
        }
        $packageCount = (int) $this->pdo->query('SELECT COUNT(*) FROM schedule_packages')->fetchColumn();
        if ($packageCount <= 1) {
            throw new InvalidArgumentException('Paket terakhir tidak dapat dihapus. Aplikasi harus memiliki minimal satu paket jadwal.');
        }

        $this->pdo->beginTransaction();
        try {
            $ruleStatement = $this->pdo->prepare('SELECT id FROM day_rules WHERE package_id = :package_id');
            $ruleStatement->execute([':package_id' => $id]);
            $ruleIds = array_map('intval', $ruleStatement->fetchAll(PDO::FETCH_COLUMN));
            $eventCount = 0;
            if ($ruleIds !== []) {
                $placeholders = implode(',', array_fill(0, count($ruleIds), '?'));
                $eventStatement = $this->pdo->prepare('SELECT COUNT(*) FROM bell_events WHERE day_rule_id IN (' . $placeholders . ')');
                $eventStatement->execute($ruleIds);
                $eventCount = (int) $eventStatement->fetchColumn();
            }

            $statement = $this->pdo->prepare('DELETE FROM schedule_packages WHERE id = :id AND is_active = 0');
            $statement->execute([':id' => $id]);
            if ($statement->rowCount() !== 1) {
                throw new RuntimeException('Paket tidak terhapus. Data mungkin sudah berubah; muat ulang halaman.');
            }

            $this->pdo->commit();
            return ['rules' => count($ruleIds), 'events' => $eventCount];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    /**
     * Atomically switch the globally active package. The partial unique index
     * remains the final guard against two active packages.
     */
    public function activateSchedulePackage(int $id): void
    {
        if ($this->getSchedulePackage($id) === null) {
            throw new RuntimeException('Paket yang akan diaktifkan tidak ditemukan.');
        }

        $this->pdo->beginTransaction();
        try {
            $this->pdo->exec('UPDATE schedule_packages SET is_active = 0 WHERE is_active = 1');
            $statement = $this->pdo->prepare(
                'UPDATE schedule_packages SET is_active = 1, updated_at = :updated_at WHERE id = :id'
            );
            $statement->execute([':updated_at' => date('Y-m-d H:i:s'), ':id' => $id]);
            if ($statement->rowCount() !== 1) {
                throw new RuntimeException('Paket tidak berhasil diaktifkan.');
            }
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    /** Return rules for a package, optionally limited to a weekday (1=Monday ... 7=Sunday). */
    public function getDayRules(int $packageId, ?int $weekday = null): array
    {
        $sql = 'SELECT r.*, p.name AS package_name FROM day_rules r
                INNER JOIN schedule_packages p ON p.id = r.package_id
                WHERE r.package_id = :package_id';
        $params = [':package_id' => $packageId];
        if ($weekday !== null) {
            $sql .= ' AND r.weekday = :weekday';
            $params[':weekday'] = $weekday;
        }
        $sql .= ' ORDER BY r.weekday ASC,
                  CASE r.rule_mode WHEN \'normal\' THEN 0 ELSE 1 END,
                  r.special_type ASC, r.special_value ASC, r.id ASC';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function getDayRule(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM day_rules WHERE id = :id');
        $statement->execute([':id' => $id]);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    public function saveDayRule(?int $id, int $packageId, int $weekday, string $mode, ?string $specialType, ?string $specialValue, bool $enabled): int
    {
        if ($weekday < 1 || $weekday > 7) {
            throw new InvalidArgumentException('Hari harus dipilih dari Senin sampai Minggu.');
        }
        if ($this->getSchedulePackage($packageId) === null) {
            throw new InvalidArgumentException('Paket jadwal tidak ditemukan.');
        }
        if (!in_array($mode, ['normal', 'special'], true)) {
            throw new InvalidArgumentException('Pola hari tidak valid.');
        }

        if ($mode === 'normal') {
            $specialType = null;
            $specialValue = null;
        } else {
            if (!in_array($specialType, ['pasaran', 'month_week'], true)) {
                throw new InvalidArgumentException('Pilih satu jenis kekhususan.');
            }
            $allowedValues = $specialType === 'pasaran'
                ? ['Legi', 'Pahing', 'Pon', 'Wage', 'Kliwon']
                : ['1', '2', '3', '4', '5', 'last'];
            if (!in_array((string) $specialValue, $allowedValues, true)) {
                throw new InvalidArgumentException('Nilai kekhususan tidak valid.');
            }
        }

        $duplicateSql = $mode === 'normal'
            ? 'SELECT id FROM day_rules WHERE package_id = :package_id AND weekday = :weekday AND rule_mode = \'normal\''
            : 'SELECT id FROM day_rules WHERE package_id = :package_id AND weekday = :weekday AND rule_mode = \'special\' AND special_type = :special_type AND special_value = :special_value';
        $duplicate = $this->pdo->prepare($duplicateSql);
        $duplicateParams = [':package_id' => $packageId, ':weekday' => $weekday];
        if ($mode === 'special') {
            $duplicateParams[':special_type'] = $specialType;
            $duplicateParams[':special_value'] = $specialValue;
        }
        $duplicate->execute($duplicateParams);
        $duplicateId = $duplicate->fetchColumn();
        if ($duplicateId !== false && (int) $duplicateId !== (int) $id) {
            throw new InvalidArgumentException($mode === 'normal'
                ? 'Pola Normal untuk hari ini sudah ada pada paket tersebut.'
                : 'Aturan khusus dengan atribut yang sama sudah ada untuk hari ini.');
        }

        $now = date('Y-m-d H:i:s');
        if ($id === null) {
            $statement = $this->pdo->prepare(
                'INSERT INTO day_rules (package_id, weekday, rule_mode, special_type, special_value, is_enabled, created_at, updated_at)
                 VALUES (:package_id, :weekday, :rule_mode, :special_type, :special_value, :is_enabled, :created_at, :updated_at)'
            );
            $statement->execute([
                ':package_id' => $packageId, ':weekday' => $weekday, ':rule_mode' => $mode,
                ':special_type' => $specialType, ':special_value' => $specialValue,
                ':is_enabled' => $enabled ? 1 : 0, ':created_at' => $now, ':updated_at' => $now,
            ]);
            return (int) $this->pdo->lastInsertId();
        }

        $existing = $this->getDayRule($id);        if ($existing === null) {
            throw new InvalidArgumentException('Aturan hari yang akan diubah tidak ditemukan.');
        }
        if ((int) $existing['package_id'] !== $packageId) {
            throw new InvalidArgumentException('Aturan hari tidak sesuai dengan paket yang dipilih.');
        }
        $statement = $this->pdo->prepare(
            'UPDATE day_rules SET weekday = :weekday, rule_mode = :rule_mode, special_type = :special_type,
             special_value = :special_value, is_enabled = :is_enabled, updated_at = :updated_at WHERE id = :id'
        );
        $statement->execute([
            ':weekday' => $weekday, ':rule_mode' => $mode, ':special_type' => $specialType,
            ':special_value' => $specialValue, ':is_enabled' => $enabled ? 1 : 0,
            ':updated_at' => $now, ':id' => $id,
        ]);
        return $id;
    }

    /**
     * Replace every rule and event on a target weekday with a copy of a source weekday.
     * Runs atomically; package and all other weekdays remain untouched.
     * Audio files are never copied—only their stored references are reused.
     *
     * @return array{rules:int,events:int}
     */
    public function replaceWeekdayScheduleFromSource(int $packageId, int $sourceWeekday, int $targetWeekday): array
    {
        if ($sourceWeekday < 1 || $sourceWeekday > 7 || $targetWeekday < 1 || $targetWeekday > 7
            || $sourceWeekday === $targetWeekday) {
            throw new InvalidArgumentException('Pilih dua hari berbeda yang valid.');
        }
        if ($this->getSchedulePackage($packageId) === null) {
            throw new InvalidArgumentException('Paket jadwal tidak ditemukan.');
        }

        $this->pdo->beginTransaction();
        try {
            $sourceRules = $this->getDayRules($packageId, $sourceWeekday);
            if ($sourceRules === []) {
                throw new InvalidArgumentException('Hari sumber belum memiliki aturan. Tidak ada data tujuan yang diubah.');
            }

            $sourceRulesWithEvents = [];
            $eventCount = 0;
            foreach ($sourceRules as $sourceRule) {
                $events = $this->getBellEvents((int) $sourceRule['id']);
                $sourceRulesWithEvents[] = ['rule' => $sourceRule, 'events' => $events];
                $eventCount += count($events);
            }

            $deleteTarget = $this->pdo->prepare(
                'DELETE FROM day_rules WHERE package_id = :package_id AND weekday = :weekday'
            );
            $deleteTarget->execute([':package_id' => $packageId, ':weekday' => $targetWeekday]);

            $now = date('Y-m-d H:i:s');
            $insertRule = $this->pdo->prepare(
                'INSERT INTO day_rules
                    (package_id, weekday, rule_mode, special_type, special_value, is_enabled, created_at, updated_at)
                 VALUES
                    (:package_id, :weekday, :rule_mode, :special_type, :special_value, :is_enabled, :created_at, :updated_at)'
            );
            $insertEvent = $this->pdo->prepare(
                'INSERT INTO bell_events
                    (day_rule_id, name, event_time, audio_path, is_enabled, sort_order, created_at, updated_at)
                 VALUES
                    (:day_rule_id, :name, :event_time, :audio_path, :is_enabled, :sort_order, :created_at, :updated_at)'
            );

            foreach ($sourceRulesWithEvents as $sourceItem) {
                $sourceRule = $sourceItem['rule'];
                $insertRule->execute([
                    ':package_id' => $packageId,
                    ':weekday' => $targetWeekday,
                    ':rule_mode' => (string) $sourceRule['rule_mode'],
                    ':special_type' => $sourceRule['special_type'],
                    ':special_value' => $sourceRule['special_value'],
                    ':is_enabled' => (int) $sourceRule['is_enabled'],
                    ':created_at' => $now,
                    ':updated_at' => $now,
                ]);
                $newRuleId = (int) $this->pdo->lastInsertId();

                foreach ($sourceItem['events'] as $event) {
                    $insertEvent->execute([
                        ':day_rule_id' => $newRuleId,
                        ':name' => (string) $event['name'],
                        ':event_time' => (string) $event['event_time'],
                        ':audio_path' => (string) $event['audio_path'],
                        ':is_enabled' => (int) $event['is_enabled'],
                        ':sort_order' => (int) $event['sort_order'],
                        ':created_at' => $now,
                        ':updated_at' => $now,
                    ]);
                }
            }

            // Verify the target while the transaction is still open; roll back if the copy is incomplete.
            $copiedRules = $this->getDayRules($packageId, $targetWeekday);
            $copiedEventCount = 0;
            foreach ($copiedRules as $copiedRule) {
                $copiedEventCount += count($this->getBellEvents((int) $copiedRule['id']));
            }
            if (count($copiedRules) !== count($sourceRulesWithEvents) || $copiedEventCount !== $eventCount) {
                throw new RuntimeException('Verifikasi salin jadwal gagal. Perubahan hari tujuan dibatalkan.');
            }

            $this->pdo->commit();
            return ['rules' => count($copiedRules), 'events' => $copiedEventCount];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function getBellEvents(int $dayRuleId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM bell_events WHERE day_rule_id = :day_rule_id ORDER BY sort_order ASC, event_time ASC, id ASC'
        );
        $statement->execute([':day_rule_id' => $dayRuleId]);
        return $statement->fetchAll();
    }

    public function getBellEvent(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM bell_events WHERE id = :id');
        $statement->execute([':id' => $id]);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    public function saveBellEvent(?int $id, int $dayRuleId, string $name, string $time, string $audioPath, bool $enabled, int $sortOrder): int
    {
        $name = trim($name);
        if ($name === '' || strlen($name) > 100) {
            throw new InvalidArgumentException('Nama kegiatan wajib diisi dan maksimal 100 karakter.');
        }
        if (!preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
            throw new InvalidArgumentException('Waktu harus valid dengan format 00:00 sampai 23:59.');
        }
        if ($sortOrder < 0 || $sortOrder > 9999) {
            throw new InvalidArgumentException('Urutan harus antara 0 dan 9999.');
        }
        if ($this->getDayRule($dayRuleId) === null) {
            throw new InvalidArgumentException('Aturan hari untuk kegiatan ini tidak ditemukan.');
        }
        if ($id === null) {
            $statement = $this->pdo->prepare(
                'INSERT INTO bell_events (day_rule_id, name, event_time, audio_path, is_enabled, sort_order, created_at, updated_at)
                 VALUES (:day_rule_id, :name, :event_time, :audio_path, :is_enabled, :sort_order, :created_at, :updated_at)'
            );
            $statement->execute([
                ':day_rule_id' => $dayRuleId, ':name' => $name, ':event_time' => $time,
                ':audio_path' => $audioPath, ':is_enabled' => $enabled ? 1 : 0,
                ':sort_order' => $sortOrder, ':created_at' => date('Y-m-d H:i:s'), ':updated_at' => date('Y-m-d H:i:s'),
            ]);
            return (int) $this->pdo->lastInsertId();
        }
        $existing = $this->getBellEvent($id);
        if ($existing === null || (int) $existing['day_rule_id'] !== $dayRuleId) {
            throw new InvalidArgumentException('Kegiatan yang akan diubah tidak ditemukan pada aturan hari ini.');
        }
        $statement = $this->pdo->prepare(
            'UPDATE bell_events SET name = :name, event_time = :event_time, audio_path = :audio_path,
             is_enabled = :is_enabled, sort_order = :sort_order, updated_at = :updated_at WHERE id = :id'
        );
        $statement->execute([
            ':name' => $name, ':event_time' => $time, ':audio_path' => $audioPath,
            ':is_enabled' => $enabled ? 1 : 0, ':sort_order' => $sortOrder,
            ':updated_at' => date('Y-m-d H:i:s'), ':id' => $id,
        ]);
        return $id;
    }

    public function deleteBellEvent(int $id, int $dayRuleId): void
    {
        $statement = $this->pdo->prepare('DELETE FROM bell_events WHERE id = :id AND day_rule_id = :day_rule_id');
        $statement->execute([':id' => $id, ':day_rule_id' => $dayRuleId]);
        if ($statement->rowCount() !== 1) {
            throw new InvalidArgumentException('Kegiatan yang akan dihapus tidak ditemukan.');
        }
    }

    public function getActiveSchedulePackage(): ?array
    {
        $row = $this->pdo->query(
            'SELECT * FROM schedule_packages WHERE is_active = 1 ORDER BY id ASC LIMIT 1'
        )->fetch();

        return $row === false ? null : $row;
    }

    public function getPlaybackLog(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM playback_log WHERE id = :id');
        $statement->execute([':id' => $id]);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    public function getPlaybackLogForEventAt(int $eventId, string $scheduledFor): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM playback_log
             WHERE event_id = :event_id AND scheduled_for = :scheduled_for
             ORDER BY id DESC LIMIT 1'
        );
        $statement->execute([':event_id' => $eventId, ':scheduled_for' => $scheduledFor]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function hasPlaybackStartedForScheduledTime(int $packageId, string $scheduledFor): bool
    {
        $statement = $this->pdo->prepare(
            "SELECT COUNT(*) FROM playback_log
             WHERE package_id = :package_id
               AND scheduled_for = :scheduled_for
               AND status IN ('started', 'completed', 'failed', 'skipped')"
        );
        $statement->execute([':package_id' => $packageId, ':scheduled_for' => $scheduledFor]);

        return (int) $statement->fetchColumn() > 0;
    }

    /**
     * Atomically add one scheduler outcome for an event/time pair.
     * The conditional INSERT prevents a normal poll/refresh from logging the same
     * scheduled event twice. Existing log rows are never overwritten.
     */
    public function recordPlaybackLogIfMissing(
        int $packageId,
        int $eventId,
        string $scheduledFor,
        string $status,
        string $message
    ): ?int {
        if (!in_array($status, ['started', 'completed', 'failed', 'missed', 'skipped'], true)) {
            throw new InvalidArgumentException('Status log pemutaran tidak valid.');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $scheduledFor)) {
            throw new InvalidArgumentException('Waktu jadwal untuk log tidak valid.');
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO playback_log
                (package_id, event_id, scheduled_for, status, message, created_at)
             SELECT :package_id, :event_id, :scheduled_for, :status, :message, :created_at
             WHERE NOT EXISTS (
                SELECT 1 FROM playback_log
                WHERE event_id = :check_event_id AND scheduled_for = :check_scheduled_for
             )'
        );
        $statement->execute([
            ':package_id' => $packageId,
            ':event_id' => $eventId,
            ':scheduled_for' => $scheduledFor,
            ':status' => $status,
            ':message' => substr($message, 0, 500),
            ':created_at' => date('Y-m-d H:i:s'),
            ':check_event_id' => $eventId,
            ':check_scheduled_for' => $scheduledFor,
        ]);

        return $statement->rowCount() === 1 ? (int) $this->pdo->lastInsertId() : null;
    }

    public function updatePlaybackLogStatus(int $id, string $status, string $message): bool
    {
        if (!in_array($status, ['completed', 'failed'], true)) {
            throw new InvalidArgumentException('Status akhir pemutaran tidak valid.');
        }

        $statement = $this->pdo->prepare(
            'UPDATE playback_log
             SET status = :status, message = :message
             WHERE id = :id AND status = \'started\''
        );
        $statement->execute([
            ':status' => $status,
            ':message' => substr($message, 0, 500),
            ':id' => $id,
        ]);

        return $statement->rowCount() === 1;
    }

    /**
     * A page navigation/reload stops the previous page's media element.
     * Reconcile unfinished runs rather than leaving a false "started" status.
     */
    public function markStartedPlaybackAsFailed(string $message = 'Pemutaran terhenti saat halaman dimuat ulang atau berpindah.'): int
    {
        $statement = $this->pdo->prepare(
            'UPDATE playback_log SET status = \'failed\', message = :message WHERE status = \'started\''
        );
        $statement->execute([':message' => substr($message, 0, 500)]);

        return $statement->rowCount();
    }

    public function getLatestPlaybackLog(?string $date = null): ?array
    {
        $sql = 'SELECT l.*, e.name AS event_name, p.name AS package_name
                FROM playback_log l
                LEFT JOIN bell_events e ON e.id = l.event_id
                LEFT JOIN schedule_packages p ON p.id = l.package_id';
        if ($date !== null) {
            if (!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $date)) {
                throw new InvalidArgumentException('Tanggal log pemutaran tidak valid.');
            }
            $sql .= ' WHERE substr(l.scheduled_for, 1, 10) = :date';
            $statement = $this->pdo->prepare($sql . ' ORDER BY l.id DESC LIMIT 1');
            $statement->execute([':date' => $date]);
            $row = $statement->fetch();
        } else {
            $row = $this->pdo->query($sql . ' ORDER BY l.id DESC LIMIT 1')->fetch();
        }

        return $row === false ? null : $row;
    }

    /**
     * Persistent asset root for files copied by the operator.
     * Existing audio files in the legacy /audio directory are intentionally untouched.
     */
    public function getAssetsRootDirectory(): string
    {
        $root = getenv('APPDATA');
        if ($root === false || trim($root) === '') {
            $root = getenv('LOCALAPPDATA');
        }
        if ($root === false || trim($root) === '') {
            throw new RuntimeException('Folder AppData/LocalAppData Windows tidak ditemukan.');
        }

        $directory = rtrim($root, "\\\\/") . DIRECTORY_SEPARATOR . 'BelMadrasah' . DIRECTORY_SEPARATOR . 'assets';
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException('Folder aset aplikasi tidak dapat dibuat.');
        }
        return $directory;
    }

    private function ensureAssetSubdirectory(string $name): string
    {
        if (!in_array($name, ['audio', 'logo'], true)) {
            throw new InvalidArgumentException('Jenis folder aset tidak valid.');
        }
        $directory = $this->getAssetsRootDirectory() . DIRECTORY_SEPARATOR . $name;
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException('Folder aset ' . $name . ' tidak dapat dibuat.');
        }
        return $directory;
    }

    public function getAudioAssetsDirectory(): string
    {
        return $this->ensureAssetSubdirectory('audio');
    }

    public function getLogoAssetsDirectory(): string
    {
        return $this->ensureAssetSubdirectory('logo');
    }

    /** @return array<int, array{name:string,path:string,extension:string,size:int}> */
    private function listAssetFiles(string $directory, array $allowedExtensions): array
    {
        $files = [];
        $realDirectory = realpath($directory);
        if ($realDirectory === false) {
            return [];
        }
        foreach (scandir($realDirectory) ?: [] as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedExtensions, true)) {
                continue;
            }
            $candidate = $realDirectory . DIRECTORY_SEPARATOR . $name;
            $realFile = realpath($candidate);
            if ($realFile === false || !is_file($realFile)
                || !str_starts_with($realFile, $realDirectory . DIRECTORY_SEPARATOR)) {
                continue;
            }
            $files[] = [
                'name' => $name,
                'path' => $realFile,
                'extension' => $extension,
                'size' => (int) (filesize($realFile) ?: 0),
            ];
        }
        usort($files, static fn (array $a, array $b): int => strnatcasecmp($a['name'], $b['name']));