<?php
/** @var array $defaults */
/** @var string $apiBase */
?>
<style>
.ost-analytics { margin: 4px 0 24px; color: #222; }
.ost-analytics__head { display: flex; align-items: center; justify-content: space-between;
    gap: 16px; flex-wrap: wrap; margin-bottom: 14px; }
.ost-analytics__title { display: flex; align-items: center; gap: 10px;
    margin: 0; font-size: 20px; font-weight: 600; color: #1f2933; }
.ost-analytics__title i { color: #2c8aff; }
.ost-analytics__back { font-size: 13px; color: #2c8aff; text-decoration: none;
    display: inline-flex; align-items: center; gap: 4px; }
.ost-analytics__back:hover { text-decoration: underline; }

.ost-analytics__filters {
    display: flex; flex-wrap: wrap; align-items: end; gap: 14px;
    background: linear-gradient(180deg,#fafbfc,#f3f5f7);
    border: 1px solid #e2e6ea; padding: 14px 16px;
    border-radius: 6px; margin-bottom: 18px;
    box-shadow: 0 1px 0 rgba(0,0,0,0.02);
}
.ost-analytics__filters label { display: flex; flex-direction: column;
    font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em;
    color: #6b7480; gap: 4px; }
.ost-analytics__filters select, .ost-analytics__filters input {
    padding: 6px 10px; border: 1px solid #ccd2d8; border-radius: 4px;
    background: #fff; font-size: 13px; color: #222;
    min-width: 160px; height: 32px; box-sizing: border-box;
}
.ost-analytics__filters select { padding-right: 24px; }
.ost-analytics__filters .action-button { margin: 0; }

.ost-analytics__kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 12px; margin-bottom: 20px; }
.ost-analytics__kpi { background: #fff; border: 1px solid #e2e6ea; border-radius: 6px;
    padding: 14px 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    transition: transform .12s ease, box-shadow .12s ease; }
.ost-analytics__kpi:hover { transform: translateY(-1px); box-shadow: 0 3px 8px rgba(0,0,0,0.06); }
.ost-analytics__kpi--accent { border-left: 4px solid #2c8aff; }
.ost-analytics__kpi--warn   { border-left: 4px solid #e67e22; }
.ost-analytics__kpi--bad    { border-left: 4px solid #c0392b; }
.ost-analytics__kpi--ok     { border-left: 4px solid #27ae60; }
.ost-analytics__kpi-label { font-size: 10px; text-transform: uppercase; color: #8a93a0;
    letter-spacing: 0.06em; font-weight: 600; }
.ost-analytics__kpi-value { font-size: 24px; font-weight: 600; margin-top: 6px; color: #1f2933;
    line-height: 1.1; }
.ost-analytics__kpi-sub   { font-size: 11px; color: #8a93a0; margin-top: 4px; }
.ost-analytics__kpi-delta { display: inline-flex; align-items: center; gap: 3px;
    font-size: 12px; font-weight: 600; margin-top: 6px; padding: 2px 6px;
    border-radius: 3px; line-height: 1.2; }
.ost-analytics__kpi-delta--better { color: #1e7a3f; background: #e6f5ec; }
.ost-analytics__kpi-delta--worse  { color: #a02524; background: #fbe8e7; }
.ost-analytics__kpi-delta--neutral{ color: #4a5260; background: #eef2f7; }

.ost-analytics__compare-hint { font-size: 12px; color: #6b7480; margin: -8px 0 14px; }
.ost-analytics__compare-hint b { color: #2c8aff; font-weight: 600; }

.ost-analytics__row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px;
    margin-bottom: 16px; }
@media (max-width: 900px) { .ost-analytics__row { grid-template-columns: 1fr; } }
.ost-analytics__chart, .ost-analytics__panel {
    background: #fff; border: 1px solid #e2e6ea; border-radius: 6px; padding: 14px 16px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
}
.ost-analytics__chart { position: relative; height: 300px; }
.ost-analytics__chart canvas { max-height: 250px !important; }
.ost-analytics__chart h4, .ost-analytics__panel h4 {
    margin: 0 0 12px; font-size: 13px; font-weight: 600; color: #4a5260;
    text-transform: uppercase; letter-spacing: 0.04em;
}
.ost-analytics__panel { min-height: 110px; }
.ost-analytics__anomalies .anomaly { padding: 8px 10px; border-left: 3px solid #c0392b;
    background: #fff5f3; margin-bottom: 6px; font-size: 12.5px; border-radius: 0 4px 4px 0; }
.ost-analytics__anomalies .anomaly--warn { border-left-color: #e67e22; background: #fff8ee; }
.ost-analytics__status code { background: #eef2f7; padding: 1px 6px; border-radius: 3px;
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12px; color: #1f2933; }
.ost-analytics .muted { color: #8a93a0; font-size: 12.5px; }
</style>

<div class="ost-analytics__head">
    <h2 class="ost-analytics__title">
        <i class="icon-bar-chart"></i>
        <?= __('Аналитика и дашборды') ?>
    </h2>
    <a class="ost-analytics__back" href="<?= ROOT_PATH ?>scp/index.php">
        <i class="icon-chevron-left"></i> <?= __('Назад к панели') ?>
    </a>
</div>

<div class="ost-analytics" data-api="<?= htmlspecialchars($apiBase) ?>"
                            data-defaults='<?= htmlspecialchars(json_encode($defaults), ENT_QUOTES) ?>'>
    <form class="ost-analytics__filters" id="ost-analytics-filters" autocomplete="off">
        <label>
            <?= __('Период') ?>
            <select name="days">
                <option value="7">7 дней</option>
                <option value="14">14 дней</option>
                <option value="30" selected>30 дней</option>
                <option value="90">90 дней</option>
            </select>
        </label>
        <label>
            <?= __('С') ?>
            <input type="date" name="from">
        </label>
        <label>
            <?= __('По') ?>
            <input type="date" name="to">
        </label>
        <label>
            <?= __('Сравнить с') ?>
            <select name="compare" id="ost-analytics-compare">
                <option value="" selected>— нет —</option>
                <option value="prev"><?= __('Предыдущим периодом') ?></option>
                <option value="custom"><?= __('Произвольным периодом') ?></option>
            </select>
        </label>
        <label class="ost-analytics__compare-range" style="display:none">
            <?= __('С (период Б)') ?>
            <input type="date" name="from2">
        </label>
        <label class="ost-analytics__compare-range" style="display:none">
            <?= __('По (период Б)') ?>
            <input type="date" name="to2">
        </label>
        <button type="submit" class="action-button"><?= __('Применить') ?></button>
        <a class="action-button" id="ost-analytics-export" href="#"><?= __('Экспорт CSV') ?></a>
    </form>
    <div class="ost-analytics__compare-hint" id="ost-analytics-compare-hint" style="display:none"></div>

    <div class="ost-analytics__kpis" id="ost-analytics-kpis"></div>

    <div class="ost-analytics__row">
        <div class="ost-analytics__chart">
            <h4><?= __('Динамика заявок') ?></h4>
            <canvas id="ost-chart-tickets" height="220"></canvas>
        </div>
        <div class="ost-analytics__chart">
            <h4><?= __('Распределение по статусам') ?></h4>
            <canvas id="ost-chart-status" height="220"></canvas>
        </div>
    </div>

    <div class="ost-analytics__row">
        <div class="ost-analytics__chart">
            <h4><?= __('Загрузка сотрудников') ?></h4>
            <canvas id="ost-chart-agents" height="220"></canvas>
        </div>
        <div class="ost-analytics__chart">
            <h4><?= __('Среднее время ответа / разрешения') ?></h4>
            <canvas id="ost-chart-times" height="220"></canvas>
        </div>
    </div>

    <div class="ost-analytics__row">
        <div class="ost-analytics__panel">
            <h4><?= __('Обнаруженные аномалии') ?></h4>
            <div id="ost-analytics-anomalies" class="ost-analytics__anomalies">
                <span class="muted"><?= __('Загрузка…') ?></span>
            </div>
        </div>
        <div class="ost-analytics__panel">
            <h4><?= __('Статус фоновой агрегации') ?></h4>
            <div id="ost-analytics-status" class="ost-analytics__status muted">
                <?= __('Загрузка…') ?>
            </div>
        </div>
    </div>
</div>

<script>
// Chart.js is shipped with the plugin (see scp/js/vendor/chart.umd.js) so we
// don't depend on any CDN — the original deployment is on a closed network
// where jsdelivr / unpkg / cdnjs aren't reachable. PJAX also strips external
// <script src> tags from partials, so we inject the tag here and start the
// dashboard from its onload callback.
(function ensureChart(cb) {
  if (typeof window.Chart !== 'undefined') { cb(); return; }
  const existing = document.querySelector('script[data-ost-analytics-chartjs]');
  if (existing) {
    if (typeof window.Chart !== 'undefined') { cb(); return; }
    existing.addEventListener('load', cb, {once: true});
    return;
  }
  const s = document.createElement('script');
  s.src = <?= json_encode(ROOT_PATH . 'scp/js/vendor/chart.umd.js?v=4.4.4') ?>;
  s.dataset.ostAnalyticsChartjs = '1';
  s.onload = cb;
  s.onerror = () => {
    const el = document.getElementById('ost-analytics-kpis');
    if (el) el.innerHTML = '<div class="ost-analytics__kpi ost-analytics__kpi--bad">'
        + '<div class="ost-analytics__kpi-label">Ошибка</div>'
        + '<div class="ost-analytics__kpi-value">—</div>'
        + '<div class="ost-analytics__kpi-sub">Не удалось загрузить '
        + s.src + '. Проверьте наличие файла.</div></div>';
  };
  document.head.appendChild(s);
})(() => {
  const root = document.querySelector('.ost-analytics');
  if (!root) return;
  const apiBase = root.dataset.api;
  const defaults = JSON.parse(root.dataset.defaults);

  const fmt = {
    int: (v) => v == null ? '—' : Number(v).toLocaleString('ru-RU'),
    minutes: (v) => v == null ? '—' : `${Number(v).toFixed(1)} мин`,
    hours: (v) => v == null ? '—' : `${Number(v).toFixed(1)} ч`,
    pct: (v) => v == null ? '—' : `${Number(v).toFixed(1)} %`,
  };

  const colors = {
    primary: '#2c8aff', good: '#27ae60', warn: '#e67e22', bad: '#c0392b',
    palette: ['#2c8aff','#27ae60','#e67e22','#c0392b','#8e44ad','#16a085',
              '#d35400','#34495e','#7f8c8d','#f39c12'],
  };

  const charts = {};
  const filtersForm = document.getElementById('ost-analytics-filters');
  const kpisEl = document.getElementById('ost-analytics-kpis');
  const anomaliesEl = document.getElementById('ost-analytics-anomalies');
  const statusEl = document.getElementById('ost-analytics-status');
  const exportLink = document.getElementById('ost-analytics-export');
  const compareSel = document.getElementById('ost-analytics-compare');
  const compareHint = document.getElementById('ost-analytics-compare-hint');
  const compareRangeLabels = document.querySelectorAll('.ost-analytics__compare-range');

  // Показывать/прятать custom-инпуты периода Б в зависимости от выбора.
  function syncCompareUi() {
    const show = compareSel.value === 'custom';
    compareRangeLabels.forEach(el => { el.style.display = show ? '' : 'none'; });
  }
  compareSel.addEventListener('change', syncCompareUi);
  syncCompareUi();

  function currentParams() {
    const fd = new FormData(filtersForm);
    const params = new URLSearchParams();
    const from = fd.get('from'), to = fd.get('to'), days = fd.get('days');
    if (from && to) { params.set('from', from); params.set('to', to); }
    else { params.set('days', days || defaults.default_period_days || 30); }
    // Параметры периода Б — только в custom-режиме. В режиме "prev" backend
    // сам подберёт отрезок такой же длины перед A.
    if (compareSel.value === 'custom') {
      const f2 = fd.get('from2'), t2 = fd.get('to2');
      if (f2) params.set('from2', f2);
      if (t2) params.set('to2', t2);
    }
    return params;
  }

  function compareMode() { return compareSel.value || ''; }

  async function fetchJson(action) {
    const url = `${apiBase}?action=${encodeURIComponent(action)}&${currentParams().toString()}`;
    const r = await fetch(url, {
      credentials: 'same-origin',
      headers: {'Accept': 'application/json'},
    });
    if (!r.ok) throw new Error(`${action}: HTTP ${r.status}`);
    return r.json();
  }

  // Для каких метрик рост — улучшение, для каких — ухудшение.
  // Используется для раскраски дельт в режиме сравнения периодов.
  const DELTA_BETTER_WHEN_UP = new Set(['closed_tickets', 'sla_frt_percent', 'sla_mttr_percent']);
  const DELTA_BETTER_WHEN_DOWN = new Set(['overdue_tickets', 'avg_frt_minutes', 'avg_mttr_hours']);

  function deltaHtml(metric, delta) {
    if (!delta || delta.percent == null || delta.value == null) return '';
    const pct = delta.percent;
    let cls = 'neutral';
    if (DELTA_BETTER_WHEN_UP.has(metric))   cls = pct > 0 ? 'better' : (pct < 0 ? 'worse' : 'neutral');
    if (DELTA_BETTER_WHEN_DOWN.has(metric)) cls = pct < 0 ? 'better' : (pct > 0 ? 'worse' : 'neutral');
    const arrow = pct > 0 ? '▲' : (pct < 0 ? '▼' : '–');
    const sign = pct > 0 ? '+' : '';
    return `<div class="ost-analytics__kpi-delta ost-analytics__kpi-delta--${cls}">${arrow} ${sign}${pct.toFixed(1)}%</div>`;
  }

  function renderKpis(s, frtSla, mttrSla, delta) {
    const card = (label, value, sub, mod, metric) =>
      `<div class="ost-analytics__kpi ost-analytics__kpi--${mod}">
         <div class="ost-analytics__kpi-label">${label}</div>
         <div class="ost-analytics__kpi-value">${value}</div>
         <div class="ost-analytics__kpi-sub">${sub || ''}</div>
         ${metric ? deltaHtml(metric, delta && delta[metric]) : ''}
       </div>`;
    const slaClass = (v, target) => v == null ? 'accent' :
      (v >= target ? 'ok' : (v >= target - 10 ? 'warn' : 'bad'));
    kpisEl.innerHTML = [
      card('Всего заявок', fmt.int(s.total_tickets), '', 'accent', 'total_tickets'),
      card('Открытых', fmt.int(s.opened_tickets), `Закрыто: ${fmt.int(s.closed_tickets)}`, 'accent', 'opened_tickets'),
      card('Просрочено', fmt.int(s.overdue_tickets), '', s.overdue_tickets > 0 ? 'warn' : 'ok', 'overdue_tickets'),
      card('Среднее FRT', fmt.minutes(s.avg_frt_minutes),
           `Цель ≤ ${defaults.sla_frt_minutes} мин`, 'accent', 'avg_frt_minutes'),
      card('Среднее MTTR', fmt.hours(s.avg_mttr_hours),
           `Цель ≤ ${defaults.sla_mttr_hours} ч`, 'accent', 'avg_mttr_hours'),
      card('SLA по FRT', fmt.pct(s.sla_frt_percent),
           `Порог ${frtSla} мин`, slaClass(s.sla_frt_percent, 90), 'sla_frt_percent'),
      card('SLA по MTTR', fmt.pct(s.sla_mttr_percent),
           `Порог ${mttrSla} ч`, slaClass(s.sla_mttr_percent, 90), 'sla_mttr_percent'),
    ].join('');
  }

  function upsertChart(id, cfg) {
    if (charts[id]) { charts[id].destroy(); }
    const ctx = document.getElementById(id).getContext('2d');
    charts[id] = new Chart(ctx, cfg);
  }

  // Тултип для линейных графиков в режиме сравнения. mode:'index' собирает
  // в одном попапе все серии (и A, и Б) по дню оси X, чтобы значения
  // периодов сразу сравнивались бок о бок. afterTitle добавляет
  // абсолютную дату периода Б, соответствующую этому индексу.
  function compareTooltip(seriesB) {
    return {
      mode: 'index',
      intersect: false,
      callbacks: {
        afterTitle: (items) => {
          if (!seriesB) return '';
          const i = items[0].dataIndex;
          const b = seriesB[i];
          return b ? `период Б: ${b.date}` : '';
        },
      },
    };
  }

  // Стиль для серий периода Б: тонкая длинная штриховка, уменьшенная
  // толщина, мелкие точки и пониженная непрозрачность — чтобы при наложении
  // на основной график период Б воспринимался как фоновый «слой памяти».
  function bStyle(color, axisId) {
    return {
      borderColor: color + '70',
      borderDash: [8, 4],
      borderWidth: 2,
      pointRadius: 1.5,
      pointHoverRadius: 4,
      tension: 0.25,
      fill: false,
      yAxisID: axisId,
    };
  }

  // Карта аномалий вида { "YYYY-MM-DD::<metric>": 'warn'|'bad' }.
  // 'bad' — критическое отклонение (|Z|>=3), 'warn' — умеренное (|Z|>=z_порог).
  // Используется для подсветки соответствующих точек на линейных графиках.
  function buildAnomalyMap(anomalies) {
    const map = {};
    if (!anomalies || !Array.isArray(anomalies)) return map;
    anomalies.forEach(a => {
      const sev = Math.abs(a.z_score) >= 3 ? 'bad' : 'warn';
      // если одна и та же точка нарушает несколько метрик — оставляем худшую
      const key = `${a.bucket_date}::${a.metric}`;
      const prev = map[key];
      if (prev === 'bad') return;
      map[key] = sev;
    });
    return map;
  }

  // Возвращает массивы pointRadius / pointBackgroundColor / pointBorderColor
  // длиной = labels.length: обычная точка — дефолт, аномалия — крупная
  // цветная с белой обводкой.
  function pointStylesFor(metric, labels, anomalyMap, fallbackColor) {
    const radii = [], bgs = [], borders = [], hovers = [];
    labels.forEach(date => {
      const sev = anomalyMap[`${date}::${metric}`];
      if (sev === 'bad') {
        radii.push(7); bgs.push(colors.bad); borders.push('#fff'); hovers.push(9);
      } else if (sev === 'warn') {
        radii.push(5); bgs.push(colors.warn); borders.push('#fff'); hovers.push(7);
      } else {
        radii.push(2.5); bgs.push(fallbackColor); borders.push(fallbackColor); hovers.push(5);
      }
    });
    return {
      pointRadius: radii,
      pointBackgroundColor: bgs,
      pointBorderColor: borders,
      pointBorderWidth: 2,
      pointHoverRadius: hovers,
    };
  }

  function buildCharts(data, compareData, anomalies) {
    const seriesA = data.series;
    const seriesB = compareData ? compareData.b.series : null;
    const labels = seriesA.map(p => p.date);
    const anomalyMap = buildAnomalyMap(anomalies);

    // Аккуратно сопоставляем индексы. Длина B может отличаться — Chart.js
    // сам обрежет/дополнит null, нам важно не выйти за длину A.
    const alignB = (key) => seriesB
      ? seriesA.map((_, i) => seriesB[i] ? seriesB[i][key] : null)
      : null;

    const ticketsDatasets = [
      Object.assign(
        {label: 'Всего (период А)', data: seriesA.map(p => p.total_tickets),
         borderColor: colors.primary, backgroundColor: colors.primary + '22',
         borderWidth: 3, fill: !compareData, tension: 0.25},
        pointStylesFor('total_tickets', labels, anomalyMap, colors.primary)),
      Object.assign(
        {label: 'Закрыто (А)', data: seriesA.map(p => p.closed_tickets),
         borderColor: colors.good, borderWidth: 3, tension: 0.25},
        pointStylesFor('closed_tickets', labels, anomalyMap, colors.good)),
      Object.assign(
        {label: 'Просрочено (А)', data: seriesA.map(p => p.overdue_tickets),
         borderColor: colors.bad, borderWidth: 3, tension: 0.25, borderDash: [4,4]},
        pointStylesFor('overdue_tickets', labels, anomalyMap, colors.bad)),
    ];
    if (seriesB) {
      ticketsDatasets.push(
        Object.assign({label: 'Всего (Б)', data: alignB('total_tickets')}, bStyle(colors.primary)),
        Object.assign({label: 'Закрыто (Б)', data: alignB('closed_tickets')}, bStyle(colors.good)),
        Object.assign({label: 'Просрочено (Б)', data: alignB('overdue_tickets')}, bStyle(colors.bad)),
      );
    }

    upsertChart('ost-chart-tickets', {
      type: 'line',
      data: { labels, datasets: ticketsDatasets },
      options: {
        responsive: true, maintainAspectRatio: false,
        interaction: seriesB ? {mode: 'index', intersect: false} : {},
        plugins: {
          legend: {position: 'bottom', labels: {boxWidth: 22}},
          tooltip: seriesB ? compareTooltip(seriesB) : {},
        },
      },
    });

    // Доля по статусам и загрузка сотрудников — снимок периода A.
    // В режиме сравнения их не дублируем (мало смысла визуально).
    const statusEntries = Object.entries(data.summary.status_distribution);
    upsertChart('ost-chart-status', {
      type: 'doughnut',
      data: {
        labels: statusEntries.map(e => e[0]),
        datasets: [{data: statusEntries.map(e => e[1]),
                    backgroundColor: statusEntries.map((_, i) => colors.palette[i % colors.palette.length])}],
      },
      options: {responsive: true, maintainAspectRatio: false,
                plugins: {legend: {position: 'right'}}},
    });

    const agentEntries = Object.entries(data.summary.agent_load).slice(0, 10);
    upsertChart('ost-chart-agents', {
      type: 'bar',
      data: {
        labels: agentEntries.map(e => e[0]),
        datasets: [{label: 'Заявок', data: agentEntries.map(e => e[1]),
                    backgroundColor: colors.primary}],
      },
      options: {indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                plugins: {legend: {display: false}}},
    });

    const timesDatasets = [
      Object.assign(
        {label: 'FRT (А), мин', data: seriesA.map(p => p.avg_frt_minutes),
         borderColor: colors.warn, borderWidth: 3, yAxisID: 'y', tension: 0.25},
        pointStylesFor('avg_frt_minutes', labels, anomalyMap, colors.warn)),
      Object.assign(
        {label: 'MTTR (А), ч', data: seriesA.map(p => p.avg_mttr_hours),
         borderColor: colors.bad, borderWidth: 3, yAxisID: 'y1', tension: 0.25},
        pointStylesFor('avg_mttr_hours', labels, anomalyMap, colors.bad)),
    ];
    if (seriesB) {
      timesDatasets.push(
        Object.assign({label: 'FRT (Б), мин', data: alignB('avg_frt_minutes')}, bStyle(colors.warn, 'y')),
        Object.assign({label: 'MTTR (Б), ч', data: alignB('avg_mttr_hours')}, bStyle(colors.bad, 'y1')),
      );
    }

    upsertChart('ost-chart-times', {
      type: 'line',
      data: { labels, datasets: timesDatasets },
      options: {
        responsive: true, maintainAspectRatio: false,
        interaction: seriesB ? {mode: 'index', intersect: false} : {},
        plugins: {
          legend: {position: 'bottom', labels: {boxWidth: 22}},
          tooltip: seriesB ? compareTooltip(seriesB) : {},
        },
        scales: {
          y:  {position: 'left',  title: {display: true, text: 'мин'}},
          y1: {position: 'right', title: {display: true, text: 'ч'},
               grid: {drawOnChartArea: false}},
        },
      },
    });
  }

  // Карта названий метрик в человеко-понятный вид для блока аномалий.
  // Поле — формат вывода значения.
  const METRIC_INFO = {
    total_tickets:    {label: 'Количество заявок',           kind: 'int'},
    opened_tickets:   {label: 'Открыто заявок',              kind: 'int'},
    closed_tickets:   {label: 'Закрыто заявок',              kind: 'int'},
    overdue_tickets:  {label: 'Просроченных заявок',         kind: 'int'},
    avg_frt_minutes:  {label: 'Среднее время первого ответа',kind: 'minutes'},
    avg_mttr_hours:   {label: 'Среднее время разрешения',    kind: 'hours'},
    sla_frt_percent:  {label: 'SLA по времени ответа',       kind: 'pct'},
    sla_mttr_percent: {label: 'SLA по времени разрешения',   kind: 'pct'},
  };

  function fmtMetric(kind, v) {
    if (v == null) return '—';
    if (kind === 'int') return Number(v).toLocaleString('ru-RU');
    return fmt[kind] ? fmt[kind](v) : String(v);
  }

  function renderAnomalies(payload) {
    if (!payload.anomalies || payload.anomalies.length === 0) {
      anomaliesEl.innerHTML = `<span class="muted">За выбранный период резких отклонений ` +
        `не обнаружено. Метрики в пределах обычной вариации.</span>`;
      return;
    }
    // Сортируем по дате (свежие сверху), при равенстве — по убыванию |Z|.
    const sorted = payload.anomalies.slice().sort((a, b) => {
      if (a.bucket_date !== b.bucket_date) return a.bucket_date < b.bucket_date ? 1 : -1;
      return Math.abs(b.z_score) - Math.abs(a.z_score);
    });
    const header = `<div class="muted" style="margin-bottom:6px">` +
      `Обнаружено отклонений: <b>${sorted.length}</b>. ` +
      `Точки этих дней подсвечены на линейных графиках.</div>`;
    anomaliesEl.innerHTML = header + sorted.map(a => {
      const info = METRIC_INFO[a.metric] || {label: a.metric, kind: 'int'};
      const isUp = a.value > a.mean;
      const arrow = isUp ? '▲' : '▼';
      const cls = Math.abs(a.z_score) >= 3 ? '' : 'anomaly--warn';
      const verb = isUp ? 'выше' : 'ниже';
      return `<div class="anomaly ${cls}">
        ${arrow} <b>${info.label}</b> · ${a.bucket_date}:
        текущее значение <b>${fmtMetric(info.kind, a.value)}</b>,
        что заметно ${verb} обычного
        (среднее по предыдущим суткам ≈ ${fmtMetric(info.kind, a.mean)}).
      </div>`;
    }).join('');
  }

  function renderStatus(lastRun) {
    if (!lastRun) {
      statusEl.innerHTML = '<span class="muted">Фоновая агрегация ещё не запускалась. ' +
        'Проверьте, что контейнер <code>worker</code> работает.</span>';
      return;
    }
    const payload = lastRun.payload || {};
    const rows = payload.rows != null ? Number(payload.rows).toLocaleString('ru-RU') : '?';
    const buckets = payload.buckets != null ? payload.buckets : '?';
    statusEl.innerHTML = `Последний пересчёт: <code>${lastRun.ts}</code> · ` +
      `обработано <b>${rows}</b> заявок · сохранено агрегатов за <b>${buckets}</b> суток.`;
  }

  function showCompareHint(payload) {
    if (!payload) { compareHint.style.display = 'none'; return; }
    const a = payload.a.period, b = payload.b.period;
    compareHint.style.display = '';
    compareHint.innerHTML = `Сравнение: период <b>А = ${a.from} … ${a.to}</b> ` +
      `vs период <b>Б = ${b.from} … ${b.to}</b>. ` +
      `Стрелки на карточках показывают изменение метрик А относительно Б.`;
  }

  async function refresh() {
    try {
      if (compareMode()) {
        const [cmp, anomalies] = await Promise.all([
          fetchJson('compare'),
          fetchJson('anomalies'),
        ]);
        renderKpis(cmp.a.summary, defaults.sla_frt_minutes, defaults.sla_mttr_hours, cmp.delta);
        buildCharts(cmp.a, cmp, anomalies.anomalies);
        renderAnomalies(anomalies);
        renderStatus(cmp.last_worker_run);
        showCompareHint(cmp);
      } else {
        const [dashboard, anomalies] = await Promise.all([
          fetchJson('dashboard'),
          fetchJson('anomalies'),
        ]);
        renderKpis(dashboard.summary, defaults.sla_frt_minutes, defaults.sla_mttr_hours, null);
        buildCharts(dashboard, null, anomalies.anomalies);
        renderAnomalies(anomalies);
        renderStatus(dashboard.last_worker_run);
        showCompareHint(null);
      }
      exportLink.href = `${apiBase}?action=export.csv&${currentParams().toString()}`;
    } catch (e) {
      console.error(e);
      kpisEl.innerHTML = `<div class="ost-analytics__kpi ost-analytics__kpi--bad">
        <div class="ost-analytics__kpi-label">Ошибка</div>
        <div class="ost-analytics__kpi-value">—</div>
        <div class="ost-analytics__kpi-sub">${e.message}</div></div>`;
    }
  }

  filtersForm.addEventListener('submit', (e) => { e.preventDefault(); refresh(); });
  refresh();
});
</script>
