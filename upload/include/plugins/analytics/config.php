<?php

require_once INCLUDE_DIR . 'class.plugin.php';

class AnalyticsPluginConfig extends PluginConfig {
    function getOptions() {
        return [
            'sla_frt_minutes' => new TextboxField([
                'label' => __('SLA: время первого ответа (минут)'),
                'configuration' => ['size' => 6, 'length' => 6],
                'default' => 60,
                'hint' => __('Порог First Response Time для расчёта % соблюдения SLA.'),
            ]),
            'sla_mttr_hours' => new TextboxField([
                'label' => __('SLA: время разрешения (часов)'),
                'configuration' => ['size' => 6, 'length' => 6],
                'default' => 24,
                'hint' => __('Порог Mean Time To Resolution для расчёта % соблюдения SLA.'),
            ]),
            'anomaly_z' => new TextboxField([
                'label' => __('Порог обнаружения аномалий (Z-score)'),
                'configuration' => ['size' => 6, 'length' => 6],
                'default' => '2.0',
                'hint' => __('Чем меньше, тем чувствительнее. Рекомендуется 2.0.'),
            ]),
            'default_period_days' => new TextboxField([
                'label' => __('Период по умолчанию (дней)'),
                'configuration' => ['size' => 6, 'length' => 6],
                'default' => 30,
            ]),
        ];
    }
}
