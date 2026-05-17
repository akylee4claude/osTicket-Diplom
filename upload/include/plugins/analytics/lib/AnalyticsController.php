<?php

namespace Analytics;

require_once __DIR__ . '/AnalyticsRepository.php';

/**
 * Renders the dashboard HTML shell. Data is fetched by the browser from
 * Analytics\Api endpoints (see urls.php).
 */
class Controller {
    public function access(): bool {
        global $thisstaff;
        return $thisstaff && $thisstaff->getId();
    }

    public function dashboard() {
        global $thisstaff, $nav, $ost, $cfg, $errors, $msg, $warn, $sysnotice;
        if (!$this->access()) {
            \Http::response(403, __('Access denied'));
            return;
        }

        $plugin = self::findPlugin();
        $pluginCfg = $plugin ? $plugin->getConfig() : null;
        $defaults = [
            'sla_frt_minutes' => $pluginCfg ? (int)$pluginCfg->get('sla_frt_minutes') : 60,
            'sla_mttr_hours' => $pluginCfg ? (int)$pluginCfg->get('sla_mttr_hours') : 24,
            'anomaly_z' => $pluginCfg ? (float)$pluginCfg->get('anomaly_z') : 2.0,
            'default_period_days' => $pluginCfg ? (int)$pluginCfg->get('default_period_days') : 30,
        ];

        $apiBase = (defined('ROOT_PATH') ? ROOT_PATH : '/') . 'scp/apps/analytics.php';

        if ($nav) {
            $nav->setTabActive('apps');
        }
        if ($ost) {
            $ost->setPageTitle(__('Аналитика и дашборды'));
        }

        require_once STAFFINC_DIR . 'header.inc.php';
        include __DIR__ . '/../templates/dashboard.tmpl.php';
        require_once STAFFINC_DIR . 'footer.inc.php';
    }

    private static function findPlugin(): ?\Plugin {
        // Plugin exposes its manifest as a public `$info` array (populated in
        // Plugin::__onload). There is no getInfo() method on the base class —
        // calling it crashes with "undefined method".
        foreach (\PluginManager::allActive() as $p) {
            if ((($p->info['id'] ?? null)) === 'analytics:dashboards') {
                return $p;
            }
        }
        return null;
    }
}
