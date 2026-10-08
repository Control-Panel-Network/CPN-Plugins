<?php
/**
 * Mr Agent bootstrap.
 */
if (!defined('MRA_INIT')) {
    define('MRA_INIT', true);
}

define('MRA_ROOT', dirname(__DIR__));
define('MRA_VERSION', '1.3.0');
define('MRA_PLUGIN_ID', 'mrAgent');

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/config_loader.php';
require_once __DIR__ . '/secrets.php';
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/acl.php';
require_once __DIR__ . '/rate_limit.php';
require_once __DIR__ . '/http_client.php';
require_once __DIR__ . '/skills.php';
require_once __DIR__ . '/tools.php';
require_once __DIR__ . '/free_helper.php';
require_once __DIR__ . '/providers.php';
require_once __DIR__ . '/chat.php';
require_once __DIR__ . '/api.php';

function mra_boot()
{
    mra_safe_mkdir(MRA_ROOT . '/data', 0700);
    return mra_config();
}
