<?php

namespace Analytics;

require_once __DIR__ . '/AnalyticsRepository.php';
require_once __DIR__ . '/AnalyticsXlsxWriter.php';

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

    /**
     * Подробности за конкретные сутки. Источник кликов — карточки аномалий.
     * Возвращает агрегат за день + список тикетов за день + контекст по 7
     * предыдущим суткам.
     */
    public function detail() {
        if (!$this->access()) return $this->json(['error' => 'forbidden'], 403);

        $date = $_GET['date'] ?? '';
        // Базовая валидация формата YYYY-MM-DD, чтобы строка не утекла в SQL.
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $this->json(['error' => 'bad date'], 400);
        }
        $metric = isset($_GET['metric']) ? (string) $_GET['metric'] : null;

        $ctx = Repository::bucketWithContext($date, 7);
        $tickets = Repository::ticketsForDay($date, 200);

        return $this->json([
            'date' => $date,
            'metric' => $metric,
            'bucket' => $ctx['bucket'],
            'context' => $ctx['context'],
            'tickets' => $tickets,
            'tickets_truncated' => count($tickets) === 200,
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

    /**
     * ТЗ 4.2.5: «Экспорт должен включать как сырые данные (тикеты за период),
     * так и агрегированные показатели (KPI)». kind=agg|tickets.
     */
    public function exportCsv() {
        if (!$this->access()) { \Http::response(403, 'Forbidden'); return; }
        [$from, $to] = $this->resolvePeriod();
        $kind = ($_GET['kind'] ?? 'agg') === 'tickets' ? 'tickets' : 'agg';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' .
            self::filename($kind, $from, $to, 'csv') . '"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM для Excel

        if ($kind === 'agg') {
            fputcsv($out, self::aggHeader());
            foreach (self::aggRows($from, $to) as $row) fputcsv($out, $row);
        } else {
            fputcsv($out, self::ticketsHeader());
            foreach (self::ticketsRows($from, $to) as $row) fputcsv($out, $row);
        }
        fclose($out);
    }

    public function exportXlsx() {
        if (!$this->access()) { \Http::response(403, 'Forbidden'); return; }
        [$from, $to] = $this->resolvePeriod();
        $kind = ($_GET['kind'] ?? 'agg') === 'tickets' ? 'tickets' : 'agg';

        $rows = [];
        if ($kind === 'agg') {
            $rows[] = self::aggHeader();
            foreach (self::aggRows($from, $to) as $r) $rows[] = $r;
            $sheet = 'Агрегаты';
        } else {
            $rows[] = self::ticketsHeader();
            foreach (self::ticketsRows($from, $to) as $r) $rows[] = $r;
            $sheet = 'Тикеты';
        }

        $bytes = XlsxWriter::build($sheet, $rows);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' .
            self::filename($kind, $from, $to, 'xlsx') . '"');
        header('Content-Length: ' . strlen($bytes));
        echo $bytes;
    }

    /**
     * Сводный PDF-отчёт за период: «шапка», карточки KPI, таблица суточных
     * агрегатов, распределение по статусам и нагрузка на сотрудников.
     * Сырые тикеты в PDF не выгружаются (формат «справки», не таблицы данных) —
     * для них есть CSV/XLSX.
     */
    public function exportPdf() {
        if (!$this->access()) { \Http::response(403, 'Forbidden'); return; }
        require_once INCLUDE_DIR . 'class.pdf.php';

        [$from, $to] = $this->resolvePeriod();
        $rows = Repository::dailyRange($from, $to);
        $summary = Repository::summarise($rows);

        $html = self::pdfHtml($from, $to, $rows, $summary);

        $pdf = new \mPDFWithLocalImages([
            'mode' => 'utf-8', 'format' => 'A4',
            'default_font' => 'dejavusans',
            'margin_left' => 12, 'margin_right' => 12,
            'margin_top' => 14, 'margin_bottom' => 14,
        ]);
        $pdf->SetTitle('Отчёт по аналитике ' . $from->format('Y-m-d') . ' — ' . $to->format('Y-m-d'));
        $pdf->WriteHtml($html);
        $bytes = $pdf->Output('', 'S');

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' .
            self::filename('report', $from, $to, 'pdf') . '"');
        header('Content-Length: ' . strlen($bytes));
        echo $bytes;
    }

    // ---- helpers общие между CSV/XLSX/PDF -----------------------------------

    private static function aggHeader(): array {
        return ['Дата', 'Всего', 'Открытых', 'Закрытых', 'Просроченных',
                'Среднее FRT (мин)', 'Среднее MTTR (ч)',
                'SLA по FRT (%)', 'SLA по MTTR (%)'];
    }

    private static function aggRows(\DateTimeInterface $from, \DateTimeInterface $to): \Generator {
        foreach (Repository::dailyRange($from, $to) as $r) {
            yield [
                $r['bucket_date'],
                (int) $r['total_tickets'],
                (int) $r['opened_tickets'],
                (int) $r['closed_tickets'],
                (int) $r['overdue_tickets'],
                $r['avg_frt_minutes'],
                $r['avg_mttr_hours'],
                $r['sla_frt_percent'],
                $r['sla_mttr_percent'],
            ];
        }
    }

    private static function ticketsHeader(): array {
        return ['№', 'Создан', 'Закрыт', 'Статус', 'Сотрудник', 'Отдел',
                'FRT (мин)', 'Просрочена', 'Без ответа'];
    }

    private static function ticketsRows(\DateTimeInterface $from, \DateTimeInterface $to): \Generator {
        // Идём по суткам, в день — до 1000 тикетов, чтобы выгрузка крупного
        // периода не выела память. ticketsForDay уже сортирует
        // «просроченные → без ответа → новые».
        $cur = $from instanceof \DateTimeImmutable ? $from : \DateTimeImmutable::createFromInterface($from);
        $stop = $to instanceof \DateTimeImmutable ? $to : \DateTimeImmutable::createFromInterface($to);
        while ($cur <= $stop) {
            foreach (Repository::ticketsForDay($cur->format('Y-m-d'), 1000) as $t) {
                yield [
                    $t['number'],
                    $t['created'],
                    $t['closed'] ?: '',
                    $t['status_name'],
                    $t['staff_name'],
                    $t['dept_name'],
                    $t['frt_minutes'],
                    $t['isoverdue'] ? 'Да' : 'Нет',
                    $t['isanswered'] ? 'Нет' : 'Да',
                ];
            }
            $cur = $cur->modify('+1 day');
        }
    }

    private static function filename(string $kind, \DateTimeInterface $from,
                                     \DateTimeInterface $to, string $ext): string {
        $prefix = $kind === 'tickets' ? 'analytics_tickets'
                : ($kind === 'report' ? 'analytics_report' : 'analytics_agg');
        return $prefix . '_' . $from->format('Ymd') . '_' . $to->format('Ymd') . '.' . $ext;
    }

    private static function pdfHtml(\DateTimeInterface $from, \DateTimeInterface $to,
                                    array $rows, array $summary): string {
        $esc = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
        $fmtNum = fn($v, $suffix = '') => $v === null ? '—' : (number_format((float) $v, 1, ',', ' ') . $suffix);
        $fmtInt = fn($v) => $v === null ? '—' : number_format((int) $v, 0, ',', ' ');

        $kpi = '<table class="kpi" cellspacing="0" cellpadding="6" width="100%">' .
            '<tr>' .
                '<th>Всего</th><th>Открытых</th><th>Закрытых</th><th>Просроченных</th>' .
                '<th>FRT</th><th>MTTR</th><th>SLA FRT</th><th>SLA MTTR</th>' .
            '</tr><tr>' .
                '<td>' . $fmtInt($summary['total_tickets']) . '</td>' .
                '<td>' . $fmtInt($summary['opened_tickets']) . '</td>' .
                '<td>' . $fmtInt($summary['closed_tickets']) . '</td>' .
                '<td>' . $fmtInt($summary['overdue_tickets']) . '</td>' .
                '<td>' . $fmtNum($summary['avg_frt_minutes'], ' мин') . '</td>' .
                '<td>' . $fmtNum($summary['avg_mttr_hours'], ' ч') . '</td>' .
                '<td>' . $fmtNum($summary['sla_frt_percent'], ' %') . '</td>' .
                '<td>' . $fmtNum($summary['sla_mttr_percent'], ' %') . '</td>' .
            '</tr></table>';

        $tbl = '<table class="daily" cellspacing="0" cellpadding="4" width="100%">' .
            '<thead><tr>' .
            '<th>Дата</th><th>Всего</th><th>Откр.</th><th>Закр.</th><th>Просроч.</th>' .
            '<th>FRT мин</th><th>MTTR ч</th><th>SLA FRT</th><th>SLA MTTR</th>' .
            '</tr></thead><tbody>';
        foreach ($rows as $r) {
            $tbl .= '<tr>' .
                '<td>' . $esc($r['bucket_date']) . '</td>' .
                '<td>' . (int) $r['total_tickets'] . '</td>' .
                '<td>' . (int) $r['opened_tickets'] . '</td>' .
                '<td>' . (int) $r['closed_tickets'] . '</td>' .
                '<td>' . (int) $r['overdue_tickets'] . '</td>' .
                '<td>' . $fmtNum($r['avg_frt_minutes']) . '</td>' .
                '<td>' . $fmtNum($r['avg_mttr_hours']) . '</td>' .
                '<td>' . $fmtNum($r['sla_frt_percent']) . '</td>' .
                '<td>' . $fmtNum($r['sla_mttr_percent']) . '</td>' .
                '</tr>';
        }
        $tbl .= '</tbody></table>';

        $distHtml = function(string $title, array $obj) use ($esc): string {
            if (!$obj) return '';
            arsort($obj);
            $html = '<h3>' . $esc($title) . '</h3><table class="dist" cellspacing="0" cellpadding="4" width="100%">';
            foreach ($obj as $k => $v) {
                $html .= '<tr><td>' . $esc((string) $k) . '</td><td align="right"><b>' . (int) $v . '</b></td></tr>';
            }
            return $html . '</table>';
        };

        $generatedAt = (new \DateTimeImmutable('now'))->format('Y-m-d H:i');

        return '<style>
            body { font-family: dejavusans, sans-serif; color: #222; font-size: 10pt; }
            h1 { font-size: 16pt; margin: 0 0 4px 0; color: #1f2933; }
            h2 { font-size: 12pt; margin: 14px 0 6px 0; color: #2c8aff; }
            h3 { font-size: 11pt; margin: 10px 0 4px 0; color: #4a5260; }
            .meta { color: #6b7480; font-size: 9pt; margin-bottom: 12px; }
            table.kpi th { background: #f3f5f7; color: #4a5260; font-size: 8pt; text-transform: uppercase;
                           border: 1px solid #e2e6ea; font-weight: bold; }
            table.kpi td { background: #fff; border: 1px solid #e2e6ea; font-size: 11pt; font-weight: bold;
                           text-align: center; color: #1f2933; }
            table.daily th, table.daily td { border: 1px solid #e2e6ea; font-size: 9pt; }
            table.daily th { background: #f3f5f7; color: #4a5260; }
            table.daily tr:nth-child(even) td { background: #fafbfc; }
            table.dist td { border-bottom: 1px solid #eef2f7; font-size: 9pt; }
        </style>
        <h1>Отчёт по аналитике</h1>
        <div class="meta">Период: <b>' . $esc($from->format('Y-m-d')) . '</b> — <b>' . $esc($to->format('Y-m-d')) . '</b>.
            Сформирован: ' . $esc($generatedAt) . '.</div>
        <h2>Сводные показатели за период</h2>' . $kpi . '
        <h2>Суточные агрегаты</h2>' . $tbl . '
        <h2>Распределения</h2>' .
        $distHtml('Распределение по статусам', $summary['status_distribution'] ?? []) .
        $distHtml('Загрузка сотрудников',     $summary['agent_load'] ?? []);
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
