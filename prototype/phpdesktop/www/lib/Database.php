<?php
declare(strict_types=1);

/**
 * Tahap 2: database aplikasi utama.
 * Semua data permanen berada di AppData/LocalAppData, bukan di folder runtime.
 */
final class Database
{
    private PDO $pdo;
    private string $path;
    private const SCHEMA_VERSION = 1;

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
        $allowed = ['school_name', 'operator_name', 'default_volume'];
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

    /**
     * Create a consistent SQLite backup using VACUUM INTO.
     * The caller must provide a new destination path that does not exist yet.
     */
    public function createBackup(string $destination): string
    {
        $directory = dirname($destination);
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

    /** @return array<int, array<string, mixed>> */
    public function getSchedulePackages(): array
    {
        return $this->pdo->query(
            'SELECT id, name, description, is_active, created_at, updated_at
             FROM schedule_packages ORDER BY is_active DESC, name COLLATE NOCASE ASC, id ASC'
        )->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function getSchedulePackage(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, description, is_active, created_at, updated_at
             FROM schedule_packages WHERE id = :id'
        );
        $statement->execute([':id' => $id]);
        $package = $statement->fetch();
        return $package === false ? null : $package;
    }

    public function saveSchedulePackage(?int $id, string $name, string $description): int
    {
        $name = trim($name);
        $description = trim($description);
        if ($name === '' || strlen($name) > 80) {
            throw new InvalidArgumentException('Nama paket wajib diisi dan maksimal 80 karakter.');
        }
        if (strlen($description) > 300) {
            throw new InvalidArgumentException('Keterangan maksimal 300 karakter.');
        }

        $now = date('Y-m-d H:i:s');
        if ($id === null) {
            $statement = $this->pdo->prepare(
                'INSERT INTO schedule_packages (name, description, is_active, created_at, updated_at)
                 VALUES (:name, :description, 0, :created_at, :updated_at)'
            );
            $statement->execute([
                ':name' => $name,
                ':description' => $description,
                ':created_at' => $now,
                ':updated_at' => $now,
            ]);
            return (int) $this->pdo->lastInsertId();
        }

        if ($this->getSchedulePackage($id) === null) {
            throw new RuntimeException('Paket yang akan diubah tidak ditemukan.');
        }
        $statement = $this->pdo->prepare(
            'UPDATE schedule_packages SET name = :name, description = :description, updated_at = :updated_at
             WHERE id = :id'
        );
        $statement->execute([
            ':name' => $name,
            ':description' => $description,
            ':updated_at' => $now,
            ':id' => $id,
        ]);
        return $id;
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

    public function getTables(): array
    {
        return $this->pdo->query(
            "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
        )->fetchAll(PDO::FETCH_COLUMN);
    }
}
