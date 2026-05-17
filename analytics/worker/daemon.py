"""Long-running entry point: ensures schema, then aggregates on an interval."""

from __future__ import annotations

import logging
import signal
import sys
import time

from sqlalchemy import text

from . import config
from .aggregator import run_once, write_log
from .db import make_engine, wait_until_ready
from .schema import ensure_tables

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s %(levelname)-7s %(name)s :: %(message)s",
)
log = logging.getLogger("worker")

_stop = False


def _handle_signal(signum, _frame):  # noqa: ANN001
    global _stop
    log.info("Received signal %s, stopping", signum)
    _stop = True


def _latest_run_request_id(engine) -> int:
    """Возвращает MAX(id) записей analytics_logs с event='run.requested'.

    Эти записи пишет PHP-плагин при нажатии кнопки «Запустить пересчёт» в
    админ-блоке дашборда. Воркер сравнивает значение с тем, что видел в
    предыдущей итерации; если появилось новее — прерывает sleep и запускает
    агрегацию немедленно.
    """
    try:
        with engine.connect() as conn:
            row = conn.execute(text(
                "SELECT MAX(id) FROM `analytics_logs` WHERE event = 'run.requested'"
            )).scalar()
        return int(row) if row else 0
    except Exception:
        # Не валим демон из-за временной недоступности БД — следующая итерация
        # повторит. wait_until_ready на старте всё равно гарантирует базовую
        # связность.
        log.exception("Failed to poll analytics_logs for manual run requests")
        return 0


def main() -> int:
    settings = config.load()
    engine = make_engine(settings)
    wait_until_ready(engine)
    ensure_tables(engine)
    write_log(engine, event="worker.start", message="analytics worker started")

    signal.signal(signal.SIGINT, _handle_signal)
    signal.signal(signal.SIGTERM, _handle_signal)

    last_request_id = _latest_run_request_id(engine)

    while not _stop:
        try:
            result = run_once(engine, settings)
            log.info(
                "Run complete: rows=%d buckets=%d window=%s..%s",
                result.rows_processed,
                result.buckets_upserted,
                result.date_from,
                result.date_to,
            )
        except Exception as exc:
            log.exception("Aggregation failed: %s", exc)
            try:
                write_log(engine, event="aggregate.error", level="ERROR", message=str(exc))
            except Exception:
                log.exception("Failed to write error to analytics_logs")

        # Сон между прогонами: каждую секунду проверяем стоп-сигнал И запрос
        # на принудительный запуск. Это даёт UI «реакцию <1с» на нажатие
        # кнопки «Запустить пересчёт» вместо ожидания следующего тика.
        for _ in range(settings.interval_sec):
            if _stop:
                break
            new_id = _latest_run_request_id(engine)
            if new_id > last_request_id:
                last_request_id = new_id
                log.info("Manual run requested (request id=%d), breaking sleep", new_id)
                break
            time.sleep(1)

    write_log(engine, event="worker.stop", message="analytics worker stopped")
    return 0


if __name__ == "__main__":
    sys.exit(main())
