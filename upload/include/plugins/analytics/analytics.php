<?php
/**
 * Analytics & Dashboards plugin for osTicket 1.18.
 *
 * Registers a staff app under /scp/apps/analytics.php. The dashboard UI and
 * its JSON API are served by a direct entry point — see scp/apps/analytics.php
 * — because osTicket's bundled apps dispatcher refuses requests when
 * SCRIPT_NAME resolves to dispatcher.php after mod_rewrite (the typical
 * setup on php:apache containers).
 */
require_once INCLUDE_DIR . 'class.plugin.php';
require_once INCLUDE_DIR . 'class.app.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/AnalyticsSchema.php';

class AnalyticsPlugin extends Plugin {
    var $config_class = 'AnalyticsPluginConfig';

    function init() {
        if (!$this->isActive()) {
            return;
        }

        Analytics\Schema::ensure();

        // Application::register*App() in osTicket core is declared without
        // `static`, so calling it statically is fatal on PHP 8.x. Go through
        // a throwaway instance — the storage is a static property anyway.
        $apps = new Application();
        $apps->registerStaffApp(
            __('Аналитика'),
            'apps/analytics.php',
            ['title' => __('Аналитика и дашборды')]
        );
    }

    function isMultiInstance() {
        return false;
    }
}
