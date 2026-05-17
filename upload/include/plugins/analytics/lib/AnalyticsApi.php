<?php

namespace Analytics;

require_once __DIR__ . '/AnalyticsRepository.php';

class Api {
    public function access(): bool {
        global $thisstaff;
        return $thisstaff && $thisstaff->getId();
    }

    public function dashboard() {
        if (!$this->access()) return $this->json(['error' => 'forbidden'], 403);

        [$from, $to] = $this->resolvePeriod();
        $rows = Repository::dailyRange($from, $to);
        $summary = Repository::summarise($rows);
        $lastRun = Repository::lastWorkerRun();

        return $this->json([
            'period' => [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ],
            'series' => self::seriesShape($rows),
            'summary' => $summary,
            'last_worker_run' => $lastRun,
        ]);
    }

    /**
     * Сравнение двух периодов (ТЗ 4.2.6). Период A — из `from`/`to`/`days`,
     * период B — из `from2`/`to2`. Если B не задан, по умолчанию берётся
     * отрезок такой же длины, расположенный непосредственно перед A.
     */
    public function compare() {
        if (!$this->access()) return $this->json(['error' => 'forbidden'], 403);

        [$fromA, $toA] = $this->resolvePeriod();
        [$fromB, $toB] = $this->resolvePeriodB($fromA, $toA);

        $rowsA = Repository::dailyRange($fromA, $toA);
        $rowsB = Repository::dailyRange($fromB, $toB);
        $sumA = Repository::summarise($rowsA);
        $sumB = Repository::summarise($rowsB);

        return $this->json([
            'a' => [
                'period' => ['from' => $fromA->format('Y-m-d'), 'to' => $toA->format('Y-m-d')],
                'series' => self::seriesShape($rowsA),
                'summary' => $sumA,
            ],
            'b' => [
                'period' => ['from' => $fromB->format('Y-m-d'), 'to' => $toB->format('Y-m-d')],
                'series' => self::seriesShape($rowsB),
                'summary' => $sumB,
            ],
            'delta' => self::computeDelta($sumA, $sumB),
            'last_worker_run' => Repository::lastWorkerRun(),
        ]);
    }

    public function anomalies() {
        if (!$this->access()) return $this->json(['error' => 'forbidden'], 403);

        [$from, $to] = $this->resolvePeriod();
        $rows = Repository::dailyRange($from, $to);
        $z = isset($_GET['z']) ? (float)$_GET['z'] : 2.0;
        return $this->json([
            'period' => ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')],
            'z' => $z,
            'anomalies' => Repository::detectAnomalies($rows, $z),
        ]);
    }

    public function exportCsv() {
        if (!$this->access()) {
            \Http::response(403, 'Forbidden');
            return;
        }
        [$from, $to] = $this->resolvePeriod();
        $rows = Repository::dailyRange($from, $to);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="analytics_' .
            $from->format('Ymd') . '_' . $to->format('Ymd') . '.csv"');

        $out = fopen('php://output', 'w');
        // UTF-8 BOM for Excel
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['date','total','opened','closed','overdue',
                       'avg_frt_min','avg_mttr_hours','sla_frt_pct','sla_mttr_pct']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['bucket_date'],
                $r['total_tickets'], $r['opened_tickets'],
                $r['closed_tickets'], $r['overdue_tickets'],
                $r['avg_frt_minutes'], $r['avg_mttr_hours'],
                $r['sla_frt_percent'], $r['sla_mttr_percent'],
            ]);
        }
        fclose($out);
    }

    public function health() {
        if (!$this->access()) return $this->json(['error' => 'forbidden'], 403);
        return $this->json([
            'ok' => true,
            'last_worker_run' => Repository::lastWorkerRun(),
        ]);
    }

    private function resolvePeriod(): array {
        $tz = new \DateTimeZone(date_default_timezone_get());
        $now = new \DateTimeImmutable('today', $tz);
        $days = isset($_GET['days']) ? max(1, (int)$_GET['days']) : 30;

        if (!empty($_GET['from']) && !empty($_GET['to'])) {
            try {
                $from = new \DateTimeImmutable($_GET['from'], $tz);
                $to = new \DateTimeImmutable($_GET['to'], $tz);
                return [$from, $to];
            } catch (\Exception $e) {
                // fall through to default
            }
        }
        $from = $now->modify("-{$days} days");
        return [$from, $now];
    }

    private function resolvePeriodB(\DateTimeInterface $fromA, \DateTimeInterface $toA): array {
        $tz = new \DateTimeZone(date_default_timezone_get());
        if (!empty($_GET['from2']) && !empty($_GET['to2'])) {
            try {
                return [
                    new \DateTimeImmutable($_GET['from2'], $tz),
                    new \DateTimeImmutable($_GET['to2'], $tz),
                ];
            } catch (\Exception $e) {
                // fall through to default
            }
        }
        // По умолчанию — такой же отрезок прямо перед A: [fromA - len, fromA - 1 day]
        $daySec = 86400;
        $lenDays = (int) floor(($toA->getTimestamp() - $fromA->getTimestamp()) / $daySec);
        $fromB = $fromA->modify('-' . ($lenDays + 1) . ' days');
        $toB = $fromA->modify('-1 day');
        return [$fromB, $toB];
    }

    private static function seriesShape(array $rows): array {
        return array_map(fn($r) => [
            'date' => $r['bucket_date'],
            'total_tickets' => (int)$r['total_tickets'],
            'opened_tickets' => (int)$r['opened_tickets'],
            'closed_tickets' => (int)$r['closed_tickets'],
            'overdue_tickets' => (int)$r['overdue_tickets'],
            'avg_frt_minutes' => $r['avg_frt_minutes'] === null ? null : (float)$r['avg_frt_minutes'],
            'avg_mttr_hours' => $r['avg_mttr_hours'] === null ? null : (float)$r['avg_mttr_hours'],
            'sla_frt_percent' => $r['sla_frt_percent'] === null ? null : (float)$r['sla_frt_percent'],
            'sla_mttr_percent' => $r['sla_mttr_percent'] === null ? null : (float)$r['sla_mttr_percent'],
        ], $rows);
    }

    private static function computeDelta(array $a, array $b): array {
        $metrics = ['total_tickets', 'opened_tickets', 'closed_tickets', 'overdue_tickets',
                    'avg_frt_minutes', 'avg_mttr_hours', 'sla_frt_percent', 'sla_mttr_percent'];
        $delta = [];
        foreach ($metrics as $m) {
            $vA = $a[$m] ?? null;
            $vB = $b[$m] ?? null;
            if ($vA === null || $vB === null) {
                $delta[$m] = ['value' => null, 'percent' => null];
                continue;
            }
            $diff = (float)$vA - (float)$vB;
            $pct = ((float)$vB != 0.0) ? ($diff / (float)$vB) * 100.0 : null;
            $delta[$m] = [
                'value' => round($diff, 2),
                'percent' => $pct === null ? null : round($pct, 1),
            ];
        }
        return $delta;
    }

    private function json(array $payload, int $status = 200): void {
        if ($status !== 200) {
            http_response_code($status);
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}
