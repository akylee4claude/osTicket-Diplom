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
            <h4><?= __('Статус воркера') ?></h4>
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

  // Тултип для линейных графиков в режиме сравнения: помимо подписи
  // оси X (= даты периода A) показывает абсолютную дату того же индекса
  // для периода B.
  function compareTooltip(seriesB) {
    return {
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

  function buildCharts(data, compareData) {
    const seriesA = data.series;
    const seriesB = compareData ? compareData.b.series : null;
    const labels = seriesA.map(p => p.date);

    // Аккуратно сопоставляем индексы. Длина B может отличаться — Chart.js
    // сам обрежет/дополнит null, нам важно не выйти за длину A.
    const alignB = (key) => seriesB
      ? seriesA.map((_, i) => seriesB[i] ? seriesB[i][key] : null)
      : null;

    const ticketsDatasets = [
      {label: 'Всего', data: seriesA.map(p => p.total_tickets),
       borderColor: colors.primary, backgroundColor: colors.primary + '22',
       fill: !compareData, tension: 0.25},
      {label: 'Закрыто', data: seriesA.map(p => p.closed_tickets),
       borderColor: colors.good, tension: 0.25},
      {label: 'Просрочено', data: seriesA.map(p => p.overdue_tickets),
       borderColor: colors.bad, tension: 0.25, borderDash: [4,4]},
    ];
    if (seriesB) {
      ticketsDatasets.push(
        {label: 'Всего (Б)', data: alignB('total_tickets'),
         borderColor: colors.primary + '80', borderDash: [6,4], tension: 0.25, fill: false, pointRadius: 2},
        {label: 'Закрыто (Б)', data: alignB('closed_tickets'),
         borderColor: colors.good + '80', borderDash: [6,4], tension: 0.25, pointRadius: 2},
      );
    }

    upsertChart('ost-chart-tickets', {
      type: 'line',
      data: { labels, datasets: ticketsDatasets },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
          legend: {position: 'bottom'},
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
      {label: 'FRT, мин', data: seriesA.map(p => p.avg_frt_minutes),
       borderColor: colors.warn, yAxisID: 'y', tension: 0.25},
      {label: 'MTTR, ч', data: seriesA.map(p => p.avg_mttr_hours),
       borderColor: colors.bad, yAxisID: 'y1', tension: 0.25},
    ];
    if (seriesB) {
      timesDatasets.push(
        {label: 'FRT (Б), мин', data: alignB('avg_frt_minutes'),
         borderColor: colors.warn + '80', borderDash: [6,4], yAxisID: 'y', tension: 0.25, pointRadius: 2},
        {label: 'MTTR (Б), ч', data: alignB('avg_mttr_hours'),
         borderColor: colors.bad + '80', borderDash: [6,4], yAxisID: 'y1', tension: 0.25, pointRadius: 2},
      );
    }

    upsertChart('ost-chart-times', {
      type: 'line',
      data: { labels, datasets: timesDatasets },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
          legend: {position: 'bottom'},
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

  function renderAnomalies(payload) {
    if (!payload.anomalies || payload.anomalies.length === 0) {
      anomaliesEl.innerHTML = `<span class="muted">Аномалий не обнаружено (Z ≥ ${payload.z}).</span>`;
      return;
    }
    anomaliesEl.innerHTML = payload.anomalies.map(a => {
      const cls = Math.abs(a.z_score) >= 3 ? '' : 'anomaly--warn';
      return `<div class="anomaly ${cls}">
        <b>${a.metric}</b> на ${a.bucket_date}: текущее <b>${a.value}</b>,
        среднее ${a.mean}, σ=${a.std}, Z=${a.z_score}
      </div>`;
    }).join('');
  }

  function renderStatus(lastRun) {
    if (!lastRun) {
      statusEl.innerHTML = '<span class="muted">Воркер ещё не отрабатывал. Проверьте контейнер <code>worker</code>.</span>';
      return;
    }
    const payload = lastRun.payload || {};
    statusEl.innerHTML = `Последний запуск: <code>${lastRun.ts}</code> · обработано ${payload.rows || '?'} тикетов · сохранено ${payload.buckets || '?'} день-бакетов.`;
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
        buildCharts(cmp.a, cmp);
        renderAnomalies(anomalies);
        renderStatus(cmp.last_worker_run);
        showCompareHint(cmp);
      } else {
        const [dashboard, anomalies] = await Promise.all([
          fetchJson('dashboard'),
          fetchJson('anomalies'),
        ]);
        renderKpis(dashboard.summary, defaults.sla_frt_minutes, defaults.sla_mttr_hours, null);
        buildCharts(dashboard, null);
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
