<?php
/**
 * Analytics & Dashboards plugin for osTicket 1.18.
 *
 * Registers a staff app under /scp/apps/analytics/ and exposes:
 *   - a dashboard page with Chart.js visualisations of KPIs
 *   - a JSON API used by the dashboard to fetch aggregated data
 *
 * KPI aggregation itself is performed by the external Python worker (see
 * analytics/worker/) which writes into the `analytics_daily_stats` table.
 */

require_once INCLUDE_DIR . 'class.plugin.php';
require_once INCLUDE_DIR . 'class.signal.php';
require_once INCLUDE_DIR . 'class.app.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/AnalyticsSchema.php';

class AnalyticsPlugin extends Plugin {
    var $config_class = 'AnalyticsPluginConfig';

    /**
     * init() runs for every installed plugin on each request (even before
     * instances are bootstrapped). Single-instance plugins like this one
     * register their global routes / menus here, gated on isActive() so the
     * menu disappears when the plugin is disabled.
     */
    function init() {
        if (!$this->isActive()) {
            return;
        }

        Analytics\Schema::ensure();

        Application::registerStaffApp(
            __('Аналитика'),
            'apps/analytics/',
            ['title' => __('Аналитика и дашборды')]
        );

        Signal::connect('apps.scp', function ($dispatcher) {
            $dispatcher->append(
                url('^/analytics/', include __DIR__ . '/urls.php')
            );
        });
    }

    function isMultiInstance() {
        return false;
    }
}
