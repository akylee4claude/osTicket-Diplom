"""One-shot KPI re-aggregation tool.

Когда меняется логика расчёта в `analytics.worker.kpi` (например, состав
бакетов в `status_distribution` или ярлыки в `agent_load`), таблица
`analytics_daily_stats` остаётся со смесью старых и новых JSON-значений до
тех пор, пока штатный воркер не догонит соответствующие дни.

Эта утилита решает проблему в один шаг: опционально стирает существующие
агрегаты и запускает свежий пересчёт по заданному окну.

Пример:
    docker compose exec worker python -m analytics.tools.reaggregate --days 90 --clear
"""

from __future__ import annotations

import argparse
import logging
import os
import sys

from sqlalchemy import text

if __package__ in (None, ""):
    sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..")))

from analytics.worker import config
from analytics.worker.aggregator import run_once
from analytics.worker.db import make_engine, wait_until_ready
from analytics.worker.schema import ensure_tables

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s %(levelname)-7s %(message)s",
)
log = logging.getLogger("reaggregate")


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("--days", type=int, default=90,
                        help="Размер окна пересчёта в днях (по умолчанию 90)")
    parser.add_argument("--clear", action="store_true",
                        help="Перед пересчётом полностью очистить analytics_daily_stats")
    args = parser.parse_args()

    settings = config.load()
    engine = make_engine(settings)
    wait_until_ready(engine)
    ensure_tables(engine)

    if args.clear:
        log.info("Очистка таблицы analytics_daily_stats")
        with engine.begin() as conn:
            conn.execute(text("DELETE FROM `analytics_daily_stats`"))

    log.info("Запуск пересчёта за %d суток…", args.days)
    result = run_once(engine, settings, lookback_days=args.days)
    log.info("Готово: записано %d сут.-агрегатов из %d заявок (окно %s..%s)",
             result.buckets_upserted, result.rows_processed,
             result.date_from, result.date_to)
    return 0


if __name__ == "__main__":
    sys.exit(main())
