<?php
/**
 * Direct entry point for the analytics dashboard and its JSON API.
 *
 * scp/apps/dispatcher.php aborts with `die('Access denied')` on PHP-FPM /
 * mod_php setups where SCRIPT_NAME still resolves to `dispatcher.php` after
 * mod_rewrite, so we bypass it entirely and call the controller / API
 * directly.
 */
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
    case 'compare':
        $api->compare();
        break;
    case 'anomalies':
        $api->anomalies();
        break;
    case 'detail':
        $api->detail();
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
