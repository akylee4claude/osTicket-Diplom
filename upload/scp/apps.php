<?php
/**
 * Landing page for the "Applications" top-level tab.
 *
 * osTicket core hardcodes the tab href to `apps.php` (see class.nav.php) but
 * never ships the file itself, so clicking the parent menu always 404'd.
 * Redirect to the first registered staff app instead.
 */
require_once('staff.inc.php');

$apps = Application::getStaffApps();
if ($apps) {
    $first = reset($apps);
    header('Location: ' . ROOT_PATH . 'scp/' . ltrim($first['href'], '/'));
    exit;
}

http_response_code(404);
echo __('No applications are currently registered.');
