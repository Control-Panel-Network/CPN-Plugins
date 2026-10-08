#!/usr/bin/env php
<?php
/**
 * Prune Mr Agent chat logs for one domain (or all under /var/lib/cpn/mr-agent/).
 *
 * Usage:
 *   php modules/cli_prune.php <domain>
 *   php modules/cli_prune.php --all
 *
 * Cron example (daily):
 *   15 3 * * * root php /home/<domain>/plugins/mrAgent/modules/cli_prune.php <domain> >/dev/null 2>&1
 */
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

define('MRA_INIT', true);
require_once __DIR__ . '/bootstrap.php';

$arg = isset($argv[1]) ? strtolower(trim((string) $argv[1])) : '';
if ($arg === '' || $arg === '-h' || $arg === '--help') {
    fwrite(STDOUT, "Usage: php modules/cli_prune.php <domain>|--all\n");
    exit(0);
}

$domains = [];
if ($arg === '--all') {
    $root = '/var/lib/cpn/mr-agent';
    if (is_dir($root)) {
        $dh = opendir($root);
        if ($dh !== false) {
            while (($name = readdir($dh)) !== false) {
                if ($name === '.' || $name === '..') {
                    continue;
                }
                if (is_dir($root . '/' . $name)) {
                    $domains[] = $name;
                }
            }
            closedir($dh);
        }
    }
} else {
    $domains[] = preg_replace('/[^a-z0-9.\-_]/', '', $arg) ?: '';
}

if (empty($domains) || $domains[0] === '') {
    fwrite(STDERR, "No domains to prune\n");
    exit(1);
}

$exit = 0;
foreach ($domains as $domain) {
    if (!is_dir(MRA_ROOT . '/data')) {
        @mkdir(MRA_ROOT . '/data', 0700, true);
    }
    @file_put_contents(MRA_ROOT . '/data/domain.txt', $domain . "\n");
    @chmod(MRA_ROOT . '/data/domain.txt', 0600);
    $cfg = mra_boot();
    $cfg['domain'] = $domain;
    $cfg = mra_normalize_limits($cfg);
    $result = mra_prune_storage($cfg, $domain);
    $line = [
        'ok' => true,
        'domain' => $domain,
        'deleted' => $result['deleted'],
        'bytes_before' => $result['bytes_before'],
        'bytes_after' => $result['bytes_after'],
        'reason' => $result['reason'],
        'max_chat_disk_mb' => (int) ($cfg['max_chat_disk_mb'] ?? 50),
        'chat_retention_days' => (int) ($cfg['chat_retention_days'] ?? 30),
    ];
    echo json_encode($line, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    mra_log('cli_prune', ['domain' => $domain, 'deleted' => $result['deleted']]);
}

exit($exit);
