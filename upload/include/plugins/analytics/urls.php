<?php

require_once INCLUDE_DIR . 'class.dispatcher.php';
require_once __DIR__ . '/lib/AnalyticsController.php';
require_once __DIR__ . '/lib/AnalyticsApi.php';

return patterns('',
    url_get('^$', ['Analytics\\Controller', 'dashboard']),
    url_get('^api/dashboard$', ['Analytics\\Api', 'dashboard']),
    url_get('^api/anomalies$', ['Analytics\\Api', 'anomalies']),
    url_get('^api/export\.csv$', ['Analytics\\Api', 'exportCsv']),
    url_get('^api/health$', ['Analytics\\Api', 'health'])
);
