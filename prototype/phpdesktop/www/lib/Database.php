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

    public function getTables(): array
    {
        return $this->pdo->query(
            "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
        )->fetchAll(PDO::FETCH_COLUMN);
    }
}
