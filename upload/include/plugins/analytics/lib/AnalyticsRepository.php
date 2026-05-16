<?php

namespace Analytics;

/**
 * Read-only access to `analytics_daily_stats` / `analytics_logs`.
 *
 * Uses osTicket's procedural db helpers so we inherit its connection / charset.
 * No data computation here — that belongs to the Python worker.
 */
class Repository {
    public static function dailyRange(\DateTimeInterface $from, \DateTimeInterface $to): array {
        $sql = sprintf(
            "SELECT bucket_date, total_tickets, opened_tickets, closed_tickets,
                    overdue_tickets, avg_frt_minutes, avg_mttr_hours,
                    sla_frt_percent, sla_mttr_percent,
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

    public static function detectAnomalies(array $rows, float $z): array {
        if (count($rows) < 4) return [];
        $metrics = ['total_tickets', 'avg_frt_minutes', 'avg_mttr_hours',
                    'sla_frt_percent', 'overdue_tickets'];
        $last = $rows[count($rows) - 1];
        $past = array_slice($rows, 0, -1);

        $anomalies = [];
        foreach ($metrics as $metric) {
            $values = array_values(array_filter(
                array_map(fn($r) => $r[$metric] === null ? null : (float)$r[$metric], $past),
                fn($v) => $v !== null
            ));
            if (count($values) < 3) continue;
            $mean = array_sum($values) / count($values);
            $variance = 0.0;
            foreach ($values as $v) { $variance += ($v - $mean) ** 2; }
            $std = sqrt($variance / count($values));
            if ($std == 0.0) continue;
            $current = $last[$metric];
            if ($current === null) continue;
            $score = ((float)$current - $mean) / $std;
            if (abs($score) >= $z) {
                $anomalies[] = [
                    'metric' => $metric,
                    'bucket_date' => $last['bucket_date'],
                    'value' => round((float)$current, 2),
                    'mean' => round($mean, 2),
                    'std' => round($std, 2),
                    'z_score' => round($score, 2),
                ];
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
