<?php
if (!defined('NTHB_INIT')) {
    exit;
}

/**
 * @param array<string,mixed> $cfg
 * @return PDO
 */
function nthb_db(array $cfg)
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $path = trim((string) ($cfg['sqlite_path'] ?? ''));
    if ($path === '') {
        $path = nthb_data_dir() . '/billing.sqlite';
    }
    $dir = dirname($path);
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new RuntimeException('Could not create data directory for SQLite');
        }
    }

    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    @chmod($path, 0600);
    return $pdo;
}

function nthb_migrate(PDO $pdo)
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS schema_migrations (
            id TEXT PRIMARY KEY,
            applied_at TEXT NOT NULL
        )'
    );

    $file = NTHB_ROOT . '/sql/001_schema.sql';
    if (!is_file($file)) {
        return;
    }
    $id = '001_schema';
    $stmt = $pdo->prepare('SELECT id FROM schema_migrations WHERE id = ?');
    $stmt->execute([$id]);
    if ($stmt->fetch()) {
        return;
    }

    $sql = (string) file_get_contents($file);
    $pdo->exec($sql);
    $ins = $pdo->prepare('INSERT INTO schema_migrations (id, applied_at) VALUES (?, ?)');
    $ins->execute([$id, nthb_now()]);
}
