<?php
/**
 * Plugin manifest for the analytics & dashboards module.
 *
 * Loaded by osTicket's PluginManager from include/plugins/analytics/.
 */
return [
    'id'             => 'analytics:dashboards',
    'version'        => '0.1.0',
    'ost_version'    => '1.18',
    'name'           => 'Analytics & Dashboards',
    'author'         => 'Suntsov A.D. (PNRPU, gr. ASU-22-1b)',
    'description'    => 'KPI aggregation and interactive dashboards for osTicket. '
                       .'Reads ost_* tables, computes FRT/MTTR/SLA/load metrics and '
                       .'renders them via Chart.js on the staff panel.',
    'url'            => 'https://github.com/akylee4claude/osticket-diplom',
    'plugin'         => 'analytics.php:AnalyticsPlugin',
];
