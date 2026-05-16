<?php
/**
 * Direct entry point for the analytics dashboard and its JSON API.
 *
 * scp/apps/dispatcher.php aborts with `die('Access denied')` on PHP-FPM /
 * mod_php setups where SCRIPT_NAME still resolves to `dispatcher.php` after
 * mod_rewrite, so we bypass it entirely and call the controller / API
 * directly.
 */
// TEMP: shake out the 500. Force errors to the browser AND to a fixed file
// (so we can read it from outside even if Apache swallows the body).
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('log_errors', '1');
ini_set('error_log', '/tmp/analytics-debug.log');
error_reporting(E_ALL);

set_exception_handler(function (\Throwable $e) {
    $msg = '['.date('c').'] '.get_class($e).': '.$e->getMessage()
        .' in '.$e->getFile().':'.$e->getLine()."\n"
        .$e->getTraceAsString()."\n";
    @file_put_contents('/tmp/analytics-debug.log', $msg, FILE_APPEND);
    while (ob_get_level() > 0) { ob_end_clean(); }
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo $msg;
});
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        $msg = '['.date('c').'] FATAL '.$err['message']
            .' in '.$err['file'].':'.$err['line']."\n";
        @file_put_contents('/tmp/analytics-debug.log', $msg, FILE_APPEND);
        while (ob_get_level() > 0) { ob_end_clean(); }
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo $msg;
    }
});

// staff.inc.php uses CWD-relative `require('../main.inc.php')`, so we have to
// pretend we're being served from /scp/ before pulling it in.
chdir(__DIR__ . '/..');
require_once(__DIR__ . '/../staff.inc.php');
require_once INCLUDE_DIR.'plugins/analytics/lib/AnalyticsController.php';
require_once INCLUDE_DIR.'plugins/analytics/lib/AnalyticsApi.php';

$action = isset($_GET['action']) ? (string) $_GET['action'] : '';

if ($action === '') {
    $ctrl = new \Analytics\Controller();
    if (!$ctrl->access()) {
        Http::response(403, __('Access denied'));
        exit;
    }
    $ctrl->dashboard();
    exit;
}

$api = new \Analytics\Api();
if (!$api->access()) {
    Http::response(403, __('Access denied'));
    exit;
}

switch ($action) {
    case 'dashboard':
        $api->dashboard();
        break;
    case 'anomalies':
        $api->anomalies();
        break;
    case 'export.csv':
        $api->exportCsv();
        break;
    case 'health':
        $api->health();
        break;
    default:
        Http::response(404, 'Unknown action');
}
