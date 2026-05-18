<?php

namespace Analytics;

/**
 * Read-only access to `analytics_daily_stats` / `analytics_logs`.
 *
 * Uses osTicket's procedural db helpers so we inherit its connection / charset.
 * No data computation here — that belongs to the Python worker.
 */
class Repository {
    /**
     * Список штатных сотрудников для фильтра. Только активные, чтобы не
     * захламлять выпадающий список уволенными.
     */
    public static function allStaff(): array {
        $prefix = TABLE_PREFIX;
        $sql = "SELECT staff_id,
                       COALESCE(NULLIF(TRIM(CONCAT(firstname, ' ', lastname)), ''), username) AS name
                FROM `{$prefix}staff`
                WHERE isactive = 1
                ORDER BY name ASC";
        $rows = [];
        if ($res = \db_query($sql)) {
            while ($r = \db_fetch_array($res)) {
                $rows[] = ['id' => (int) $r['staff_id'], 'name' => $r['name']];
            }
        }
        return $rows;
    }

    /**
     * Список отделов для фильтра.
     */
    public static function allDepartments(): array {
        $prefix = TABLE_PREFIX;
        $sql = "SELECT id, name FROM `{$prefix}department` ORDER BY name ASC";
        $rows = [];
        if ($res = \db_query($sql)) {
            while ($r = \db_fetch_array($res)) {
                $rows[] = ['id' => (int) $r['id'], 'name' => $r['name']];
            }
        }
        return $rows;
    }

    /**
     * Пересчёт KPI на лету для фильтра по сотруднику/отделу (ТЗ 4.2.4).
     *
     * Когда выбран конкретный исполнитель или отдел, таблица
     * `analytics_daily_stats` не подходит — там агрегаты по всему дню без
     * разбивки. Тащим сырые тикеты за период с теми же FRT/resolution и
     * группируем по DATE(created) прямо в PHP. По производительности
     * сопоставимо с воркером: один SELECT + lineary в памяти.
     *
     * Результат повторяет формат строк `dailyRange()`, поэтому
     * `summarise()` и фронт-рендеры работают без изменений.
     */
    public static function computeFiltered(\DateTimeInterface $from, \DateTimeInterface $to,
                                            ?int $staffId, ?int $deptId,
                                            int $slaFrtMin = 60, int $slaMttrHours = 24): array {
        $prefix = TABLE_PREFIX;
        $dFrom = \db_input($from->format('Y-m-d 00:00:00'), false);
        $dTo   = \db_input($to->format('Y-m-d 23:59:59'), false);
        $where = ["t.created BETWEEN '$dFrom' AND '$dTo'"];
        if ($staffId && $staffId > 0) $where[] = 't.staff_id = ' . (int) $staffId;
        if ($deptId && $deptId > 0)   $where[] = 't.dept_id = ' . (int) $deptId;
        $whereSql = implode(' AND ', $where);

        $sql = "
            SELECT
                DATE(t.created) AS bucket_date,
                t.ticket_id, t.closed, t.isoverdue, t.staff_id, t.dept_id, t.status_id,
                t.reopened,
                csat.score AS csat_score,
                COALESCE(NULLIF(TRIM(CONCAT(s.firstname,' ',s.lastname)),''),
                         IF(t.staff_id=0,'Не назначено','—')) AS staff_name,
                d.name AS dept_name,
                TIMESTAMPDIFF(MINUTE, t.created, t.closed) AS resolution_minutes,
                TIMESTAMPDIFF(MINUTE, t.created,
                    (SELECT MIN(te.created)
                       FROM `{$prefix}thread` th
                       JOIN `{$prefix}thread_entry` te
                         ON te.thread_id = th.id
                        AND te.type = 'R'
                        AND te.staff_id > 0
                      WHERE th.object_id = t.ticket_id
                        AND th.object_type = 'T')) AS frt_minutes
            FROM `{$prefix}ticket` t
            LEFT JOIN `{$prefix}staff` s         ON s.staff_id = t.staff_id
            LEFT JOIN `{$prefix}department` d    ON d.id       = t.dept_id
            LEFT JOIN `analytics_ticket_csat` csat ON csat.ticket_id = t.ticket_id
            WHERE $whereSql
        ";

        $byDay = [];
        if ($res = \db_query($sql)) {
            while ($r = \db_fetch_array($res)) {
                $day = $r['bucket_date'];
                if (!isset($byDay[$day])) $byDay[$day] = [];
                $byDay[$day][] = $r;
            }
        }
        ksort($byDay);

        $slaMttrMin = $slaMttrHours * 60;
        $rows = [];
        foreach ($byDay as $day => $tickets) {
            $total = count($tickets);
            $opened = 0; $closed = 0; $overdue = 0;
            $frts = []; $mttrs = [];
            $slaFrtOk = 0; $slaMttrOk = 0;
            $fcrClosed = 0; $fcrNoReopen = 0;
            $csatScores = [];
            $agentLoad = []; $statusDist = []; $deptLoad = [];

            foreach ($tickets as $t) {
                $isClosed  = !empty($t['closed']);
                $isOverdue = ((int) $t['isoverdue']) === 1;
                if ($isClosed) $closed++; else $opened++;
                if ($isOverdue) $overdue++;

                if ($t['frt_minutes'] !== null && $t['frt_minutes'] !== '') {
                    $f = (float) $t['frt_minutes'];
                    $frts[] = $f;
                    if ($f <= $slaFrtMin) $slaFrtOk++;
                }
                if ($isClosed && $t['resolution_minutes'] !== null && $t['resolution_minutes'] !== '') {
                    $m = (float) $t['resolution_minutes'];
                    $mttrs[] = $m;
                    if ($m <= $slaMttrMin) $slaMttrOk++;
                }
                // FCR — только по закрытым; ни разу не переоткрывался — числитель.
                if ($isClosed) {
                    $fcrClosed++;
                    if (empty($t['reopened'])) $fcrNoReopen++;
                }
                if ($t['csat_score'] !== null && $t['csat_score'] !== '') {
                    $csatScores[] = (float) $t['csat_score'];
                }
                $agentLoad[$t['staff_name']] = ($agentLoad[$t['staff_name']] ?? 0) + 1;
                $statusKey = $isClosed
                    ? ($isOverdue ? 'Закрыта с просрочкой' : 'Закрыта')
                    : ($isOverdue ? 'Открыта, просрочена' : 'Открыта');
                $statusDist[$statusKey] = ($statusDist[$statusKey] ?? 0) + 1;
                $deptName = $t['dept_name'] ?: 'Не указан';
                $deptLoad[$deptName] = ($deptLoad[$deptName] ?? 0) + 1;
            }

            $rows[] = [
                'bucket_date'        => $day,
                'total_tickets'      => $total,
                'opened_tickets'     => $opened,
                'closed_tickets'     => $closed,
                'overdue_tickets'    => $overdue,
                'avg_frt_minutes'    => $frts  ? round(array_sum($frts)  / count($frts), 2) : null,
                'avg_mttr_hours'     => $mttrs ? round((array_sum($mttrs) / count($mttrs)) / 60.0, 2) : null,
                'sla_frt_percent'    => $frts  ? round($slaFrtOk * 100.0 / count($frts), 2) : null,
                'sla_mttr_percent'   => $mttrs ? round($slaMttrOk * 100.0 / count($mttrs), 2) : null,
                'fcr_percent'        => $fcrClosed ? round($fcrNoReopen * 100.0 / $fcrClosed, 2) : null,
                'csat_score'         => $csatScores ? round(array_sum($csatScores) / count($csatScores), 2) : null,
                'agent_load'         => $agentLoad,
                'status_distribution'=> $statusDist,
                'department_load'    => $deptLoad,
            ];
        }
        return $rows;
    }

    public static function dailyRange(\DateTimeInterface $from, \DateTimeInterface $to): array {
        $sql = sprintf(
            "SELECT bucket_date, total_tickets, opened_tickets, closed_tickets,
                    overdue_tickets, avg_frt_minutes, avg_mttr_hours,
                    sla_frt_percent, sla_mttr_percent, fcr_percent, csat_score,
                    agent_load, status_distribution, department_load
             FROM `analytics_daily_stats`
             WHERE bucket_date BETWEEN '%s' AND '%s'
             ORDER BY bucket_date ASC",
            \db_input($from->format('Y-m-d'), false),
            \db_input($to->format('Y-m-d'), false)
        );

        $rows = [];
        if ($res = \db_query($sql)) {
            while ($r = \db_fetch_array($res)) {
                $r['agent_load'] = self::decodeJson($r['agent_load']);
                $r['status_distribution'] = self::decodeJson($r['status_distribution']);
                $r['department_load'] = self::decodeJson($r['department_load']);
                $rows[] = $r;
            }
        }
        return $rows;
    }

    public static function summarise(array $rows): array {
        if (!$rows) {
            return [
                'total_tickets' => 0,
                'opened_tickets' => 0,
                'closed_tickets' => 0,
                'overdue_tickets' => 0,
                'avg_frt_minutes' => null,
                'avg_mttr_hours' => null,
                'sla_frt_percent' => null,
                'sla_mttr_percent' => null,
                'fcr_percent' => null,
                'csat_score' => null,
                'agent_load' => [],
                'status_distribution' => [],
                'department_load' => [],
            ];
        }

        $sum = function (string $key) use ($rows): int {
            return array_sum(array_map(fn($r) => (int)($r[$key] ?? 0), $rows));
        };
        $weightedAvg = function (string $key) use ($rows): ?float {
            $num = 0.0; $den = 0;
            foreach ($rows as $r) {
                if ($r[$key] === null || $r[$key] === '') continue;
                $w = (int)$r['total_tickets'];
                $num += (float)$r[$key] * $w;
                $den += $w;
            }
            return $den > 0 ? round($num / $den, 2) : null;
        };
        $mergeJson = function (string $key) use ($rows): array {
            $acc = [];
            foreach ($rows as $r) {
                foreach (($r[$key] ?: []) as $k => $v) {
                    $acc[$k] = (int)(($acc[$k] ?? 0)) + (int)$v;
                }
            }
            arsort($acc);
            return $acc;
        };

        return [
            'total_tickets' => $sum('total_tickets'),
            'opened_tickets' => $sum('opened_tickets'),
            'closed_tickets' => $sum('closed_tickets'),
            'overdue_tickets' => $sum('overdue_tickets'),
            'avg_frt_minutes' => $weightedAvg('avg_frt_minutes'),
            'avg_mttr_hours' => $weightedAvg('avg_mttr_hours'),
            'sla_frt_percent' => $weightedAvg('sla_frt_percent'),
            'sla_mttr_percent' => $weightedAvg('sla_mttr_percent'),
            'fcr_percent' => $weightedAvg('fcr_percent'),
            'csat_score' => $weightedAvg('csat_score'),
            'agent_load' => $mergeJson('agent_load'),
            'status_distribution' => $mergeJson('status_distribution'),
            'department_load' => $mergeJson('department_load'),
        ];
    }

    public static function lastWorkerRun(): ?array {
        $sql = "SELECT ts, event, message, payload
                FROM `analytics_logs`
                WHERE component = 'worker' AND event = 'aggregate.success'
                ORDER BY id DESC LIMIT 1";
        if (($res = \db_query($sql)) && ($r = \db_fetch_array($res))) {
            $r['payload'] = self::decodeJson($r['payload']);
            return $r;
        }
        return null;
    }

    /**
     * Последние записи журнала analytics_logs с опциональным фильтром.
     * Используется UI-блоком администрирования.
     *
     * @param int    $limit Максимальное число строк (по умолчанию 30).
     * @param string $level Фильтр по уровню: 'all' / 'ERROR' / 'WARNING' / 'INFO'.
     */
    public static function recentLogs(int $limit = 30, string $level = 'all'): array {
        $lim = (int) max(1, min(500, $limit));
        $where = '';
        if ($level === 'ERROR') $where = "WHERE level = 'ERROR'";
        elseif ($level === 'WARNING') $where = "WHERE level IN ('ERROR','WARNING')";

        $sql = "SELECT id, ts, level, component, event, message, payload
                FROM `analytics_logs`
                $where
                ORDER BY id DESC
                LIMIT $lim";

        $rows = [];
        if ($res = \db_query($sql)) {
            while ($r = \db_fetch_array($res)) {
                $r['id'] = (int) $r['id'];
                $r['payload'] = self::decodeJson($r['payload']);
                $rows[] = $r;
            }
        }
        return $rows;
    }

    /**
     * Пишет в analytics_logs запись о запросе на принудительный пересчёт.
     * Воркер опрашивает таблицу раз в секунду и просыпается на новых
     * записях с event='run.requested'.
     */
    public static function recordRunRequest(string $requestedBy): int {
        $payload = \db_input(json_encode(['by' => $requestedBy], JSON_UNESCAPED_UNICODE));
        $message = \db_input("Forced reaggregation requested by $requestedBy");
        $sql = "INSERT INTO `analytics_logs` (level, component, event, message, payload)
                VALUES ('INFO', 'admin', 'run.requested', $message, $payload)";
        if (\db_query($sql)) {
            return (int) \db_insert_id();
        }
        return 0;
    }

    /**
     * Один суточный бакет + предыдущие N дней для контекста сравнения.
     * Используется drill-down модалкой при клике на запись аномалии.
     */
    public static function bucketWithContext(string $date, int $contextDays = 7): array {
        $tzDate = \db_input($date, false);
        $bucket = null;
        $context = [];
        $sql = "SELECT bucket_date, total_tickets, opened_tickets, closed_tickets,
                       overdue_tickets, avg_frt_minutes, avg_mttr_hours,
                       sla_frt_percent, sla_mttr_percent,
                       agent_load, status_distribution, department_load
                FROM `analytics_daily_stats`
                WHERE bucket_date BETWEEN DATE_SUB('$tzDate', INTERVAL $contextDays DAY)
                                      AND '$tzDate'
                ORDER BY bucket_date ASC";
        if ($res = \db_query($sql)) {
            while ($r = \db_fetch_array($res)) {
                $r['agent_load'] = self::decodeJson($r['agent_load']);
                $r['status_distribution'] = self::decodeJson($r['status_distribution']);
                $r['department_load'] = self::decodeJson($r['department_load']);
                if ($r['bucket_date'] === $date) {
                    $bucket = $r;
                } else {
                    $context[] = $r;
                }
            }
        }
        return ['bucket' => $bucket, 'context' => $context];
    }

    /**
     * Список тикетов, созданных в указанные сутки, с join-ами на
     * staff/department/status и расчётом FRT (минут до первого ответа
     * штатного сотрудника). Лимит — защита от взрывных запросов: при
     * объёмных днях верх таблицы определяется сортировкой ниже.
     */
    public static function ticketsForDay(string $date, int $limit = 200,
                                         ?int $staffId = null, ?int $deptId = null): array {
        $prefix = TABLE_PREFIX;
        $tzDate = \db_input($date, false);
        $lim = (int) $limit;
        $extra = '';
        if ($staffId && $staffId > 0) $extra .= ' AND t.staff_id = ' . (int) $staffId;
        if ($deptId  && $deptId  > 0) $extra .= ' AND t.dept_id  = ' . (int) $deptId;
        $sql = "
            SELECT t.ticket_id, t.number, t.created, t.closed,
                   t.isoverdue, t.isanswered, t.staff_id, t.dept_id, t.status_id,
                   COALESCE(NULLIF(TRIM(CONCAT(s.firstname,' ',s.lastname)),''),
                            IF(t.staff_id=0,'Не назначено','—')) AS staff_name,
                   d.name AS dept_name,
                   ts.name AS status_name,
                   TIMESTAMPDIFF(MINUTE, t.created,
                       (SELECT MIN(te.created)
                          FROM `{$prefix}thread` th
                          JOIN `{$prefix}thread_entry` te
                            ON te.thread_id = th.id
                         WHERE th.object_id = t.ticket_id
                           AND th.object_type = 'T'
                           AND te.type = 'R'
                           AND te.staff_id > 0)) AS frt_minutes
            FROM `{$prefix}ticket` t
            LEFT JOIN `{$prefix}staff` s          ON s.staff_id = t.staff_id
            LEFT JOIN `{$prefix}department` d     ON d.id = t.dept_id
            LEFT JOIN `{$prefix}ticket_status` ts ON ts.id = t.status_id
            WHERE DATE(t.created) = '$tzDate' $extra
            ORDER BY t.isoverdue DESC, t.isanswered ASC, t.created DESC
            LIMIT $lim";

        $rows = [];
        if ($res = \db_query($sql)) {
            while ($r = \db_fetch_array($res)) {
                $rows[] = [
                    'ticket_id'   => (int) $r['ticket_id'],
                    'number'      => $r['number'],
                    'created'     => $r['created'],
                    'closed'      => $r['closed'],
                    'isoverdue'   => (int) $r['isoverdue'],
                    'isanswered'  => (int) $r['isanswered'],
                    'staff_name'  => $r['staff_name'],
                    'dept_name'   => $r['dept_name'] ?: 'Не указан',
                    'status_name' => $r['status_name'] ?: '—',
                    'frt_minutes' => $r['frt_minutes'] === null ? null : (int) $r['frt_minutes'],
                ];
            }
        }
        return $rows;
    }

    /**
     * Rolling Z-score обнаружение аномалий по суточным агрегатам.
     *
     * Для каждого дня i сравниваем текущее значение метрики со средним и
     * стандартным отклонением по предыдущим `window` дням (ТЗ 4.3.1 —
     * скользящее окно 7 суток). Если |Z| >= $z, день помечается как
     * аномалия для этой метрики.
     *
     * Возвращает массив записей вида:
     *   { metric, bucket_date, value, mean, std, z_score }
     * по одной на каждое (день × метрика), превысившее порог. Сортировка —
     * по дате возрастания, чтобы фронт мог удобно мапить на ось X.
     */
    public static function detectAnomalies(array $rows, float $z, int $window = 7): array {
        if (count($rows) < 4) return [];
        $metrics = ['total_tickets', 'avg_frt_minutes', 'avg_mttr_hours',
                    'sla_frt_percent', 'overdue_tickets'];
        $sorted = $rows;
        usort($sorted, fn($a, $b) => strcmp($a['bucket_date'], $b['bucket_date']));

        $anomalies = [];
        $n = count($sorted);
        for ($i = 1; $i < $n; $i++) {
            $start = max(0, $i - $window);
            $past = array_slice($sorted, $start, $i - $start);
            if (count($past) < 3) continue;
            $current = $sorted[$i];
            foreach ($metrics as $metric) {
                $values = [];
                foreach ($past as $r) {
                    if ($r[$metric] === null || $r[$metric] === '') continue;
                    $values[] = (float) $r[$metric];
                }
                if (count($values) < 3) continue;
                $mean = array_sum($values) / count($values);
                $variance = 0.0;
                foreach ($values as $v) { $variance += ($v - $mean) ** 2; }
                $std = sqrt($variance / count($values));
                if ($std == 0.0) continue;
                $cur = $current[$metric];
                if ($cur === null) continue;
                $score = ((float) $cur - $mean) / $std;
                if (abs($score) >= $z) {
                    $anomalies[] = [
                        'metric' => $metric,
                        'bucket_date' => $current['bucket_date'],
                        'value' => round((float) $cur, 2),
                        'mean' => round($mean, 2),
                        'std' => round($std, 2),
                        'z_score' => round($score, 2),
                    ];
                }
            }
        }
        return $anomalies;
    }

    private static function decodeJson($value) {
        if ($value === null || $value === '') return [];
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }
}
