<?php
/** @var array  $defaults         */
/** @var string $apiBase          */
/** @var bool   $isAdmin          */
/** @var string $role             */
/** @var int    $currentStaffId   */
/** @var string $currentStaffName */
/** @var array  $staffList        */
/** @var array  $deptList         */
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
.ost-analytics__role {
    display: inline-flex; align-items: center; gap: 6px; font-size: 11px;
    font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;
    padding: 3px 10px; border-radius: 12px; line-height: 1.4;
}
.ost-analytics__role--admin   { background: #fde9e7; color: #a02524; }
.ost-analytics__role--manager { background: #fff1de; color: #a35a14; }
.ost-analytics__role--agent   { background: #e6f5ec; color: #1e7a3f; }
.ost-analytics__role-meta { font-size: 11px; color: #6b7480; }

.ost-analytics__scope-hint {
    display: flex; align-items: center; gap: 8px;
    margin: 0 0 14px; padding: 8px 12px; font-size: 12.5px;
    border-radius: 6px; border-left: 3px solid;
}
.ost-analytics__scope-hint--agent   { background: #f1faf3; border-left-color: #27ae60; color: #1c5e35; }
.ost-analytics__scope-hint b { font-weight: 600; }

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
.ost-analytics__staff-label { min-width: 240px; }
.ost-analytics__staff-label .select2-container { min-width: 240px; }
.ost-analytics__staff-label .select2-container .select2-selection { height: 32px; border-radius: 4px; }
.ost-analytics__staff-label .select2-container .select2-selection__rendered { line-height: 30px; }
/* Скрытие сотрудников «не из выбранного отдела» — управляется JS-классом */
.ost-analytics__staff-label option.is-hidden { display: none; }

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
.ost-analytics__anomalies-toolbar { display: flex; flex-wrap: wrap; align-items: center;
    gap: 10px; margin-bottom: 10px; font-size: 12.5px; color: #4a5260; }
.ost-analytics__anomalies-toolbar select { padding: 4px 8px; border: 1px solid #ccd2d8;
    border-radius: 4px; background: #fff; font-size: 12.5px; color: #222; }
.ost-analytics__anomalies-toolbar .summary { margin-left: auto; color: #6b7480; }
.ost-analytics__anomalies .anomaly { padding: 8px 10px; border-left: 3px solid #c0392b;
    background: #fff5f3; margin-bottom: 6px; font-size: 12.5px; border-radius: 0 4px 4px 0;
    cursor: pointer; transition: background .12s ease, transform .12s ease; }
.ost-analytics__anomalies .anomaly:hover { background: #ffeae6; transform: translateX(2px); }
.ost-analytics__anomalies .anomaly--warn { border-left-color: #e67e22; background: #fff8ee; }
.ost-analytics__anomalies .anomaly--warn:hover { background: #ffefd8; }
.ost-analytics__anomalies-pager { display: flex; align-items: center; gap: 8px;
    justify-content: center; margin-top: 8px; font-size: 12.5px; color: #4a5260; }
.ost-analytics__anomalies-pager button { padding: 4px 10px; border: 1px solid #ccd2d8;
    background: #fff; border-radius: 4px; cursor: pointer; color: #4a5260; }
.ost-analytics__anomalies-pager button:hover:not(:disabled) { background: #f3f5f7; }
.ost-analytics__anomalies-pager button:disabled { opacity: 0.5; cursor: not-allowed; }
.ost-analytics__status code { background: #eef2f7; padding: 1px 6px; border-radius: 3px;
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12px; color: #1f2933; }
.ost-analytics .muted { color: #8a93a0; font-size: 12.5px; }

/* Админ-блок: триггер пересчёта + журнал событий ---------------------------- */
.ost-analytics__admin { margin-top: 18px; border: 1px solid #e2e6ea; border-radius: 6px;
    background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.03); }
.ost-analytics__admin > summary { padding: 12px 16px; cursor: pointer;
    font-size: 13px; font-weight: 600; color: #4a5260;
    text-transform: uppercase; letter-spacing: 0.04em; }
.ost-analytics__admin[open] > summary { border-bottom: 1px solid #e2e6ea; }
.ost-analytics__admin-body { padding: 14px 16px; display: flex; flex-direction: column; gap: 14px; }
.ost-analytics__admin-controls { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
.ost-analytics__admin-controls .action-button { margin: 0; }
.ost-analytics__admin-msg { font-size: 12.5px; padding: 6px 10px; border-radius: 4px;
    background: #eef2f7; color: #4a5260; display: none; }
.ost-analytics__admin-msg.is-visible { display: inline-block; }
.ost-analytics__admin-msg--ok   { background: #e6f5ec; color: #1e7a3f; }
.ost-analytics__admin-msg--bad  { background: #fde9e7; color: #a02524; }
.ost-analytics__logs-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
.ost-analytics__logs-table th, .ost-analytics__logs-table td {
    padding: 6px 8px; border-bottom: 1px solid #eef2f7; text-align: left; vertical-align: top;
}
.ost-analytics__logs-table th { background: #f8fafc; color: #4a5260; font-weight: 600;
    text-transform: uppercase; font-size: 11px; letter-spacing: 0.04em; }
.ost-analytics__logs-table tr:hover td { background: #fafbfc; }
.ost-analytics__log-level { display: inline-block; padding: 1px 6px; border-radius: 10px;
    font-size: 10px; font-weight: 600; letter-spacing: 0.04em; }
.ost-analytics__log-level--INFO    { background: #eef2f7; color: #4a5260; }
.ost-analytics__log-level--WARNING { background: #fff1de; color: #a35a14; }
.ost-analytics__log-level--ERROR   { background: #fde9e7; color: #a02524; }

/* Drill-down модалка по клику на аномалию ------------------------------------ */
.ost-modal-backdrop {
    position: fixed; inset: 0; background: rgba(15, 23, 42, 0.55);
    display: flex; align-items: center; justify-content: center;
    z-index: 10000; padding: 24px;
}
.ost-modal {
    background: #fff; border-radius: 8px; max-width: 1000px; width: 100%;
    max-height: 90vh; overflow: auto;
    box-shadow: 0 12px 40px rgba(0,0,0,0.25);
    display: flex; flex-direction: column;
}
.ost-modal__head { display: flex; align-items: center; justify-content: space-between;
    padding: 14px 18px; border-bottom: 1px solid #e2e6ea;
    background: linear-gradient(180deg,#fafbfc,#f3f5f7); position: sticky; top: 0; }
.ost-modal__title { margin: 0; font-size: 16px; font-weight: 600; color: #1f2933; }
.ost-modal__close { background: transparent; border: 0; font-size: 22px; line-height: 1;
    color: #6b7480; cursor: pointer; padding: 4px 8px; border-radius: 4px; }
.ost-modal__close:hover { background: #eef2f7; color: #1f2933; }
.ost-modal__body { padding: 16px 18px; }
.ost-modal__section { margin-bottom: 18px; }
.ost-modal__section h5 { margin: 0 0 8px; font-size: 12px; text-transform: uppercase;
    color: #6b7480; letter-spacing: 0.04em; font-weight: 600; }
.ost-modal__kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 10px; }
.ost-modal__kpi { background: #f8fafc; border: 1px solid #e2e6ea; border-radius: 5px;
    padding: 10px 12px; }
.ost-modal__kpi--highlight { background: #fff5f3; border-color: #f1b9b4; }
.ost-modal__kpi-label { font-size: 10px; text-transform: uppercase; color: #6b7480;
    letter-spacing: 0.05em; font-weight: 600; }
.ost-modal__kpi-value { font-size: 18px; font-weight: 600; margin-top: 4px; color: #1f2933; }
.ost-modal__kpi-sub   { font-size: 11px; color: #8a93a0; margin-top: 2px; }
.ost-modal__table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
.ost-modal__table th, .ost-modal__table td {
    padding: 6px 8px; border-bottom: 1px solid #eef2f7; text-align: left; vertical-align: top;
}
.ost-modal__table th { background: #f8fafc; color: #4a5260; font-weight: 600;
    text-transform: uppercase; font-size: 11px; letter-spacing: 0.04em; }
.ost-modal__table tr:hover td { background: #fafbfc; }
.ost-modal__badge { display: inline-block; padding: 2px 6px; border-radius: 10px;
    font-size: 10px; font-weight: 600; line-height: 1.4; text-transform: uppercase; }
.ost-modal__badge--bad  { background: #fde9e7; color: #a02524; }
.ost-modal__badge--warn { background: #fff1de; color: #a35a14; }
.ost-modal__badge--ok   { background: #e6f5ec; color: #1e7a3f; }
.ost-modal__badge--neutral { background: #eef2f7; color: #4a5260; }
.ost-modal__split { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
@media (max-width: 720px) { .ost-modal__split { grid-template-columns: 1fr; } }
.ost-modal__list { font-size: 12.5px; }
.ost-modal__list li { margin: 2px 0; }
</style>

<?php
$roleLabels = [
    'admin'   => __('Администратор'),
    'manager' => __('Руководитель'),
    'agent'   => __('Агент'),
];
$roleLabel = $roleLabels[$role ?? 'agent'] ?? __('Гость');
// Подсказку про «видимый объём» показываем только агенту: ему важно
// понимать, что данные ограничены своими тикетами. Admin/manager видят
// всё по дефолту, и для них эта плашка была бы избыточным шумом.
$scopeHint = ($role ?? 'agent') === 'agent'
    ? __('Показаны только <b>ваши собственные тикеты</b>. Чтобы увидеть данные коллег или отдела целиком — обратитесь к руководителю.')
    : '';
?>
<div class="ost-analytics__head">
    <h2 class="ost-analytics__title">
        <i class="icon-bar-chart"></i>
        <?= __('Аналитика и дашборды') ?>
    </h2>
    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
        <span class="ost-analytics__role ost-analytics__role--<?= htmlspecialchars($role ?? 'agent', ENT_QUOTES, 'UTF-8') ?>"
              title="<?= __('Роль определяется правами в osTicket. Влияет на состав показанных данных.') ?>">
            <?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?>
        </span>
        <?php if (!empty($currentStaffName)): ?>
            <span class="ost-analytics__role-meta"><?= htmlspecialchars($currentStaffName, ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
        <a class="ost-analytics__back" href="<?= ROOT_PATH ?>scp/index.php">
            <i class="icon-chevron-left"></i> <?= __('Назад к панели') ?>
        </a>
    </div>
</div>

<?php if (!empty($scopeHint)): ?>
<div class="ost-analytics__scope-hint ost-analytics__scope-hint--<?= htmlspecialchars($role ?? 'agent', ENT_QUOTES, 'UTF-8') ?>">
    <span><?= $scopeHint /* Безопасно: значения сформированы выше и не содержат пользовательского ввода */ ?></span>
</div>
<?php endif; ?>

<div class="ost-analytics" data-api="<?= htmlspecialchars($apiBase) ?>"
                            data-role="<?= htmlspecialchars($role ?? 'agent', ENT_QUOTES, 'UTF-8') ?>"
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
<?php if (($role ?? 'agent') !== 'agent'): ?>
        <label>
            <?= __('Отдел') ?>
            <select name="dept_id" id="ost-analytics-dept">
                <option value="0"><?= __('Все') ?></option>
                <?php foreach (($deptList ?? []) as $dt): ?>
                    <option value="<?= (int) $dt['id'] ?>"><?= htmlspecialchars($dt['name'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="ost-analytics__staff-label">
            <?= __('Сотрудники') ?>
            <select name="staff_ids[]" id="ost-analytics-staff" multiple
                    data-placeholder="<?= __('Все') ?>">
                <?php foreach (($staffList ?? []) as $st): ?>
                    <option value="<?= (int) $st['id'] ?>" data-dept-id="<?= (int) $st['dept_id'] ?>">
                        <?= htmlspecialchars($st['name'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
<?php endif; ?>
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
        <label>
            <?= __('Формат') ?>
            <select id="ost-analytics-export-format">
                <option value="csv">CSV</option>
                <option value="xlsx">Excel (XLSX)</option>
                <option value="pdf">PDF</option>
            </select>
        </label>
        <label id="ost-analytics-export-kind-label">
            <?= __('Данные') ?>
            <select id="ost-analytics-export-kind">
                <option value="agg"><?= __('Агрегаты (KPI по дням)') ?></option>
                <option value="tickets"><?= __('Сырые тикеты') ?></option>
            </select>
        </label>
        <a class="action-button no-pjax" id="ost-analytics-export" href="#" download><?= __('Скачать') ?></a>
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
<?php if (!empty($isAdmin)): ?>
    <details class="ost-analytics__admin" id="ost-analytics-admin">
        <summary><?= __('Администрирование') ?></summary>
        <div class="ost-analytics__admin-body">
            <div class="ost-analytics__admin-controls">
                <button type="button" class="action-button" id="ost-admin-trigger">
                    <?= __('Запустить пересчёт сейчас') ?>
                </button>
                <button type="button" class="action-button" id="ost-admin-logs-refresh">
                    <?= __('Обновить журнал') ?>
                </button>
                <label>
                    <?= __('Уровень') ?>
                    <select id="ost-admin-logs-level">
                        <option value="all"><?= __('Все') ?></option>
                        <option value="WARNING"><?= __('Предупреждения и выше') ?></option>
                        <option value="ERROR"><?= __('Только ошибки') ?></option>
                    </select>
                </label>
                <span class="ost-analytics__admin-msg" id="ost-admin-msg"></span>
            </div>
            <div>
                <h5 class="muted" style="margin:0 0 6px;text-transform:uppercase;letter-spacing:0.04em">
                    <?= __('Журнал событий') ?>
                </h5>
                <div id="ost-admin-logs"><span class="muted"><?= __('Загрузка…') ?></span></div>
            </div>
        </div>
    </details>
<?php endif; ?>
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
    score: (v) => v == null ? '—' : `${Number(v).toFixed(2)} / 5`,
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
  const exportFormatSel = document.getElementById('ost-analytics-export-format');
  const exportKindSel = document.getElementById('ost-analytics-export-kind');
  const exportKindLabel = document.getElementById('ost-analytics-export-kind-label');

  // ----- Select2 + связка «отдел → сотрудники» ------------------------------
  // Select2 загружается osTicket'ом в footer.inc.php → на момент выполнения
  // нашего IIFE он, как правило, уже доступен. Но возможен и обратный
  // порядок, поэтому пробуем; при отсутствии — оставляем обычный
  // <select multiple>.
  const staffSel = document.getElementById('ost-analytics-staff');
  const deptSel  = document.getElementById('ost-analytics-dept');
  if (staffSel && window.jQuery && window.jQuery.fn.select2) {
    try {
      window.jQuery(staffSel).select2({
        width: '240px',
        placeholder: staffSel.dataset.placeholder || 'Все',
        allowClear: true,
      });
    } catch (e) { console.warn('Select2 init failed, falling back', e); }
  }
  // При смене отдела прячем сотрудников не из него и снимаем выбор у
  // скрытых, чтобы они не утекали в API-запрос.
  function syncStaffByDept() {
    if (!staffSel || !deptSel) return;
    const dept = parseInt(deptSel.value || '0', 10);
    const opts = staffSel.querySelectorAll('option');
    let changed = false;
    opts.forEach(opt => {
      const own = parseInt(opt.dataset.deptId || '0', 10);
      const hide = dept > 0 && own !== dept;
      opt.classList.toggle('is-hidden', hide);
      if (hide && opt.selected) { opt.selected = false; changed = true; }
    });
    if (changed && window.jQuery && window.jQuery.fn.select2) {
      // Сообщаем Select2 что значения снаружи поменялись.
      try { window.jQuery(staffSel).trigger('change.select2'); } catch (e) {}
    }
  }
  if (deptSel) deptSel.addEventListener('change', syncStaffByDept);
  syncStaffByDept();

  function updateExportLink() {
    const fmt = exportFormatSel.value;
    const params = currentParams();
    params.set('action', 'export.' + fmt);
    // PDF — это «справка»: только агрегаты, поле «Данные» прячем.
    if (fmt === 'pdf') {
      exportKindLabel.style.display = 'none';
    } else {
      exportKindLabel.style.display = '';
      params.set('kind', exportKindSel.value);
    }
    exportLink.href = `${apiBase}?${params.toString()}`;
  }
  exportFormatSel.addEventListener('change', updateExportLink);
  exportKindSel.addEventListener('change', updateExportLink);
  // Также пересчитываем href на лету при любом изменении формы фильтров,
  // чтобы не пришлось всегда давить «Применить» перед скачиванием.
  filtersForm.addEventListener('change', updateExportLink);
  filtersForm.addEventListener('input', updateExportLink);

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
    // Фильтры по сотруднику/отделу — пишем в URL только при выборе конкретного,
    // чтобы запросы по «все/все» оставались чистыми (и кешировались proxy).
    const deptId  = parseInt(fd.get('dept_id')  || '0', 10);
    if (deptId  > 0) params.set('dept_id',  String(deptId));
    // staff_ids — массив (multi-select). FormData.getAll вернёт все
    // выбранные значения. Передаём CSV-формой, она короче и проще читается
    // в URL, чем staff_ids[]=...&staff_ids[]=...
    const staffIds = fd.getAll('staff_ids[]')
      .map(v => parseInt(v, 10))
      .filter(v => v > 0);
    if (staffIds.length > 0) params.set('staff_ids', staffIds.join(','));
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
  const DELTA_BETTER_WHEN_UP = new Set(['closed_tickets', 'sla_frt_percent', 'sla_mttr_percent',
                                         'fcr_percent', 'csat_score']);
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
      card('FCR', fmt.pct(s.fcr_percent),
           'Решено без переоткрытия', slaClass(s.fcr_percent, 65), 'fcr_percent'),
      card('CSAT', fmt.score(s.csat_score),
           'Оценка клиентов (1..5)',
           s.csat_score == null ? 'accent'
             : (s.csat_score >= 4.5 ? 'ok' : (s.csat_score >= 3.5 ? 'warn' : 'bad')),
           'csat_score'),
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
    fcr_percent:      {label: 'FCR (без переоткрытий)',      kind: 'pct'},
    csat_score:       {label: 'CSAT (оценка клиентов)',      kind: 'score'},
  };

  function fmtMetric(kind, v) {
    if (v == null) return '—';
    if (kind === 'int') return Number(v).toLocaleString('ru-RU');
    return fmt[kind] ? fmt[kind](v) : String(v);
  }

  // Локальное состояние блока аномалий: исходные данные + текущий фильтр и
  // страница. Хранится в module-scope, чтобы переход по страницам и смена
  // фильтра не дёргали бэкенд.
  const anomState = {
    raw: [],
    filter: 'all',  // 'all' | 'warn' | 'bad'
    page: 0,
    perPage: 5,
  };

  function renderAnomalies(payload) {
    // Сортируем один раз и кладём в состояние; всё остальное — на клиенте.
    const list = (payload && payload.anomalies) ? payload.anomalies.slice() : [];
    list.sort((a, b) => {
      if (a.bucket_date !== b.bucket_date) return a.bucket_date < b.bucket_date ? 1 : -1;
      return Math.abs(b.z_score) - Math.abs(a.z_score);
    });
    anomState.raw = list;
    anomState.page = 0;
    renderAnomaliesView();
  }

  function severityOf(a) {
    return Math.abs(a.z_score) >= 3 ? 'bad' : 'warn';
  }

  function renderAnomaliesView() {
    const filtered = anomState.raw.filter(a =>
      anomState.filter === 'all' ? true : severityOf(a) === anomState.filter
    );
    const totalRaw = anomState.raw.length;
    const totalFiltered = filtered.length;
    const totalPages = Math.max(1, Math.ceil(totalFiltered / anomState.perPage));
    if (anomState.page >= totalPages) anomState.page = totalPages - 1;

    // Тулбар + сводка одинаковые для всех состояний.
    const toolbar = `
      <div class="ost-analytics__anomalies-toolbar">
        <label>Показать
          <select id="ost-anomalies-filter">
            <option value="all"${anomState.filter==='all'?' selected':''}>Все</option>
            <option value="warn"${anomState.filter==='warn'?' selected':''}>Только предупреждения</option>
            <option value="bad"${anomState.filter==='bad'?' selected':''}>Только критические</option>
          </select>
        </label>
        <span class="summary">${
          totalRaw === 0
            ? 'Всего: 0 отклонений'
            : (anomState.filter === 'all'
                ? `Всего: <b>${totalRaw}</b> отклонений`
                : `Показано: <b>${totalFiltered}</b> из <b>${totalRaw}</b>`)
        }</span>
      </div>`;

    if (totalRaw === 0) {
      anomaliesEl.innerHTML = toolbar +
        `<span class="muted">За выбранный период резких отклонений не обнаружено. ` +
        `Метрики в пределах обычной вариации.</span>`;
      bindAnomalyToolbar();
      return;
    }

    if (totalFiltered === 0) {
      anomaliesEl.innerHTML = toolbar +
        `<span class="muted">По выбранному фильтру записей нет. ` +
        `Сейчас в периоде <b>${totalRaw}</b> отклонений другого уровня.</span>`;
      bindAnomalyToolbar();
      return;
    }

    const start = anomState.page * anomState.perPage;
    const slice = filtered.slice(start, start + anomState.perPage);

    const list = slice.map(a => {
      const info = METRIC_INFO[a.metric] || {label: a.metric, kind: 'int'};
      const isUp = a.value > a.mean;
      const arrow = isUp ? '▲' : '▼';
      const cls = severityOf(a) === 'bad' ? '' : 'anomaly--warn';
      const verb = isUp ? 'выше' : 'ниже';
      // data-* атрибуты — задел под drill-down: при клике в будущем
      // достанем оттуда метрику и дату и откроем подробности.
      return `<div class="anomaly ${cls}" data-metric="${a.metric}"
                                          data-date="${a.bucket_date}"
                                          title="Подробности по этому дню — в следующей версии">
        ${arrow} <b>${info.label}</b> · ${a.bucket_date}:
        текущее значение <b>${fmtMetric(info.kind, a.value)}</b>,
        что заметно ${verb} обычного
        (среднее по предыдущим суткам ≈ ${fmtMetric(info.kind, a.mean)}).
      </div>`;
    }).join('');

    const pager = totalPages > 1 ? `
      <div class="ost-analytics__anomalies-pager">
        <button id="ost-anomalies-prev" ${anomState.page === 0 ? 'disabled' : ''}>← Назад</button>
        <span>Стр. <b>${anomState.page + 1}</b> из <b>${totalPages}</b></span>
        <button id="ost-anomalies-next" ${anomState.page >= totalPages - 1 ? 'disabled' : ''}>Вперёд →</button>
      </div>` : '';

    anomaliesEl.innerHTML = toolbar + list + pager;
    bindAnomalyToolbar();
    bindAnomalyPager();
    bindAnomalyCards();
  }

  function bindAnomalyToolbar() {
    const sel = document.getElementById('ost-anomalies-filter');
    if (sel) sel.addEventListener('change', (e) => {
      anomState.filter = e.target.value;
      anomState.page = 0;
      renderAnomaliesView();
    });
  }

  function bindAnomalyPager() {
    const prev = document.getElementById('ost-anomalies-prev');
    const next = document.getElementById('ost-anomalies-next');
    if (prev) prev.addEventListener('click', () => {
      if (anomState.page > 0) { anomState.page--; renderAnomaliesView(); }
    });
    if (next) next.addEventListener('click', () => {
      anomState.page++; renderAnomaliesView();
    });
  }

  function bindAnomalyCards() {
    anomaliesEl.querySelectorAll('.anomaly').forEach(card => {
      card.addEventListener('click', () => {
        openDetail(card.dataset.date, card.dataset.metric);
      });
    });
  }

  // ----- Drill-down модалка -------------------------------------------------

  let detailKeyHandler = null;

  function closeDetail() {
    const back = document.getElementById('ost-modal-backdrop');
    if (back) back.remove();
    if (detailKeyHandler) {
      document.removeEventListener('keydown', detailKeyHandler);
      detailKeyHandler = null;
    }
  }

  function openDetailSkeleton(date, metric) {
    closeDetail();
    const info = METRIC_INFO[metric] || {label: metric || 'агрегат', kind: 'int'};
    const back = document.createElement('div');
    back.id = 'ost-modal-backdrop';
    back.className = 'ost-modal-backdrop';
    back.innerHTML = `
      <div class="ost-modal" role="dialog" aria-modal="true">
        <div class="ost-modal__head">
          <h4 class="ost-modal__title">
            Подробности за ${date}${metric ? ' · ' + info.label : ''}
          </h4>
          <button class="ost-modal__close" type="button" aria-label="Закрыть">×</button>
        </div>
        <div class="ost-modal__body">
          <div class="muted">Загружаем данные за ${date}…</div>
        </div>
      </div>`;
    document.body.appendChild(back);

    // Закрытие: крестик, клик по бэкдропу, Esc.
    back.querySelector('.ost-modal__close').addEventListener('click', closeDetail);
    back.addEventListener('click', (e) => { if (e.target === back) closeDetail(); });
    detailKeyHandler = (e) => { if (e.key === 'Escape') closeDetail(); };
    document.addEventListener('keydown', detailKeyHandler);
  }

  async function openDetail(date, metric) {
    openDetailSkeleton(date, metric);
    try {
      const params = new URLSearchParams({action: 'detail', date});
      if (metric) params.set('metric', metric);
      const r = await fetch(`${apiBase}?${params.toString()}`, {
        credentials: 'same-origin',
        headers: {'Accept': 'application/json'},
      });
      if (!r.ok) throw new Error(`HTTP ${r.status}`);
      const data = await r.json();
      renderDetailBody(data);
    } catch (e) {
      const body = document.querySelector('#ost-modal-backdrop .ost-modal__body');
      if (body) body.innerHTML = `<div class="muted" style="color:#a02524">
        Не удалось загрузить подробности: ${e.message}</div>`;
    }
  }

  function renderDetailBody(data) {
    const body = document.querySelector('#ost-modal-backdrop .ost-modal__body');
    if (!body) return;

    if (!data.bucket) {
      body.innerHTML = `<div class="muted">Агрегат за ${data.date} не найден в БД. ` +
        `Возможно, воркер ещё не обработал этот день — попробуйте позже или ` +
        `выполните <code>analytics.tools.reaggregate --days 90</code>.</div>`;
      return;
    }
    const b = data.bucket;
    const ctx = data.context || [];

    // KPI-карточки, целевая метрика выделена.
    const kpiList = [
      ['total_tickets',    'Всего заявок'],
      ['opened_tickets',   'Открытых'],
      ['closed_tickets',   'Закрытых'],
      ['overdue_tickets',  'Просроченных'],
      ['avg_frt_minutes',  'Среднее FRT'],
      ['avg_mttr_hours',   'Среднее MTTR'],
      ['sla_frt_percent',  'SLA по FRT'],
      ['sla_mttr_percent', 'SLA по MTTR'],
      ['fcr_percent',      'FCR'],
      ['csat_score',       'CSAT'],
    ];
    const kpiHtml = kpiList.map(([key, label]) => {
      const info = METRIC_INFO[key] || {kind: 'int'};
      const hi = key === data.metric ? ' ost-modal__kpi--highlight' : '';
      // Среднее по предыдущим суткам для контекста — для подписи под значением.
      const vals = ctx.map(r => r[key]).filter(v => v != null).map(Number);
      const mean = vals.length ? vals.reduce((a, c) => a + c, 0) / vals.length : null;
      const sub = mean != null
        ? `обычное ~ ${fmtMetric(info.kind, mean)}`
        : '';
      return `<div class="ost-modal__kpi${hi}">
        <div class="ost-modal__kpi-label">${label}</div>
        <div class="ost-modal__kpi-value">${fmtMetric(info.kind, b[key])}</div>
        <div class="ost-modal__kpi-sub">${sub}</div>
      </div>`;
    }).join('');

    // Распределения за день.
    const distList = (title, obj) => {
      const entries = Object.entries(obj || {});
      if (entries.length === 0) return `<div class="muted">${title}: нет данных.</div>`;
      const total = entries.reduce((s, [, v]) => s + Number(v), 0);
      const lis = entries.map(([k, v]) => {
        const pct = total ? (Number(v) / total * 100).toFixed(0) : '0';
        return `<li><b>${k}</b> — ${v} (${pct}%)</li>`;
      }).join('');
      return `<div><h5>${title}</h5><ul class="ost-modal__list">${lis}</ul></div>`;
    };

    // Таблица тикетов за день.
    const ticketsHtml = (data.tickets && data.tickets.length)
      ? `<table class="ost-modal__table">
          <thead><tr>
            <th>№</th><th>Создан</th><th>Статус</th><th>Сотрудник</th>
            <th>Отдел</th><th>FRT</th><th>Флаги</th>
          </tr></thead>
          <tbody>
          ${data.tickets.map(t => {
            const flags = [];
            if (t.isoverdue) flags.push('<span class="ost-modal__badge ost-modal__badge--bad">просрочен</span>');
            if (!t.isanswered) flags.push('<span class="ost-modal__badge ost-modal__badge--warn">без ответа</span>');
            if (t.closed) flags.push('<span class="ost-modal__badge ost-modal__badge--ok">закрыт</span>');
            if (flags.length === 0) flags.push('<span class="ost-modal__badge ost-modal__badge--neutral">в работе</span>');
            const frt = t.frt_minutes == null ? '—'
              : (t.frt_minutes >= 60
                  ? `${(t.frt_minutes / 60).toFixed(1)} ч`
                  : `${t.frt_minutes} мин`);
            return `<tr>
              <td><b>${t.number || ('#' + t.ticket_id)}</b></td>
              <td>${t.created}</td>
              <td>${t.status_name}</td>
              <td>${t.staff_name}</td>
              <td>${t.dept_name}</td>
              <td>${frt}</td>
              <td>${flags.join(' ')}</td>
            </tr>`;
          }).join('')}
          </tbody>
        </table>
        ${data.tickets_truncated
          ? '<div class="muted" style="margin-top:6px">Показаны первые 200 тикетов (отсортированы: просроченные → без ответа → новые).</div>'
          : ''}`
      : '<div class="muted">Тикетов за этот день не найдено в источнике.</div>';

    body.innerHTML = `
      <div class="ost-modal__section">
        <h5>Сводка за ${data.date}</h5>
        <div class="ost-modal__kpis">${kpiHtml}</div>
      </div>
      <div class="ost-modal__section ost-modal__split">
        ${distList('Распределение по статусам', b.status_distribution)}
        ${distList('Загрузка сотрудников',    b.agent_load)}
      </div>
      <div class="ost-modal__section">
        <h5>Тикеты, созданные ${data.date}</h5>
        ${ticketsHtml}
      </div>
    `;
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
      updateExportLink();
    } catch (e) {
      console.error(e);
      kpisEl.innerHTML = `<div class="ost-analytics__kpi ost-analytics__kpi--bad">
        <div class="ost-analytics__kpi-label">Ошибка</div>
        <div class="ost-analytics__kpi-value">—</div>
        <div class="ost-analytics__kpi-sub">${e.message}</div></div>`;
    }
  }

  // ----- Админ-блок: триггер пересчёта + журнал -----------------------------
  const adminPanel = document.getElementById('ost-analytics-admin');
  if (adminPanel) {
    const triggerBtn = document.getElementById('ost-admin-trigger');
    const refreshBtn = document.getElementById('ost-admin-logs-refresh');
    const levelSel   = document.getElementById('ost-admin-logs-level');
    const msgEl      = document.getElementById('ost-admin-msg');
    const logsEl     = document.getElementById('ost-admin-logs');

    function adminMsg(text, kind) {
      msgEl.className = 'ost-analytics__admin-msg ost-analytics__admin-msg--' + (kind || 'neutral') + ' is-visible';
      msgEl.textContent = text;
      if (kind === 'ok') setTimeout(() => msgEl.classList.remove('is-visible'), 6000);
    }

    function csrfToken() {
      const meta = document.querySelector('meta[name="csrf_token"]');
      return meta ? meta.getAttribute('content') : '';
    }

    async function triggerRun() {
      triggerBtn.disabled = true;
      adminMsg('Отправляем запрос…', 'neutral');
      try {
        const r = await fetch(`${apiBase}?action=trigger.run`, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Accept': 'application/json',
            'X-CSRFToken': csrfToken(),
          },
        });
        const data = await r.json();
        if (!r.ok || !data.ok) {
          throw new Error(data.error || data.message || `HTTP ${r.status}`);
        }
        adminMsg(data.message || 'Запрос принят.', 'ok');
        // Дайте воркеру несколько секунд, потом перечитайте журнал.
        setTimeout(loadLogs, 4000);
      } catch (e) {
        adminMsg('Не удалось: ' + e.message, 'bad');
      } finally {
        triggerBtn.disabled = false;
      }
    }

    async function loadLogs() {
      logsEl.innerHTML = '<span class="muted">Загрузка…</span>';
      try {
        const params = new URLSearchParams({action: 'logs', level: levelSel.value, limit: 30});
        const r = await fetch(`${apiBase}?${params.toString()}`, {
          credentials: 'same-origin',
          headers: {'Accept': 'application/json'},
        });
        if (!r.ok) throw new Error(`HTTP ${r.status}`);
        const data = await r.json();
        renderLogs(data.rows || []);
      } catch (e) {
        logsEl.innerHTML = `<span class="muted" style="color:#a02524">Ошибка: ${e.message}</span>`;
      }
    }

    function renderLogs(rows) {
      if (!rows.length) {
        logsEl.innerHTML = '<span class="muted">Записей нет.</span>';
        return;
      }
      const esc = (s) => String(s).replace(/[&<>"']/g, c => ({
        '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
      const html = ['<table class="ost-analytics__logs-table">',
        '<thead><tr><th>Время</th><th>Уровень</th><th>Компонент</th><th>Событие</th><th>Сообщение</th></tr></thead>',
        '<tbody>'];
      rows.forEach(r => {
        const lvl = r.level || 'INFO';
        html.push(`<tr>
          <td><code>${esc(r.ts)}</code></td>
          <td><span class="ost-analytics__log-level ost-analytics__log-level--${esc(lvl)}">${esc(lvl)}</span></td>
          <td>${esc(r.component || '')}</td>
          <td>${esc(r.event || '')}</td>
          <td>${esc(r.message || '')}</td>
        </tr>`);
      });
      html.push('</tbody></table>');
      logsEl.innerHTML = html.join('');
    }

    triggerBtn.addEventListener('click', triggerRun);
    refreshBtn.addEventListener('click', loadLogs);
    levelSel.addEventListener('change', loadLogs);
    // Первая загрузка журнала — при первом раскрытии секции.
    adminPanel.addEventListener('toggle', () => {
      if (adminPanel.open && !adminPanel.dataset.loaded) {
        adminPanel.dataset.loaded = '1';
        loadLogs();
      }
    });
  }

  filtersForm.addEventListener('submit', (e) => { e.preventDefault(); refresh(); });
  refresh();
});
</script>
