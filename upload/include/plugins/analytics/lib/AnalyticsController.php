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
        global $thisstaff, $nav;
        if (!$this->access()) {
            \Http::response(403, __('Access denied'));
            return;
        }

        $plugin = self::findPlugin();
        $cfg = $plugin ? $plugin->getConfig() : null;
        $defaults = [
            'sla_frt_minutes' => $cfg ? (int)$cfg->get('sla_frt_minutes') : 60,
            'sla_mttr_hours' => $cfg ? (int)$cfg->get('sla_mttr_hours') : 24,
            'anomaly_z' => $cfg ? (float)$cfg->get('anomaly_z') : 2.0,
            'default_period_days' => $cfg ? (int)$cfg->get('default_period_days') : 30,
        ];

        $apiBase = '/scp/apps/analytics/api';

        if ($nav && method_exists($nav, 'setTabActive')) {
            $nav->setTabActive('apps');
        }

        include __DIR__ . '/../templates/dashboard.tmpl.php';
    }

    private static function findPlugin(): ?\Plugin {
        foreach (\PluginManager::allActive() as $p) {
            $info = $p->getInfo();
            if (($info['id'] ?? null) === 'analytics:dashboards') {
                return $p;
            }
        }
        return null;
    }
}
