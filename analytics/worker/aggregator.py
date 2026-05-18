"""Orchestrates: pull source data from osTicket -> compute KPIs -> upsert agg."""

from __future__ import annotations

import json
import logging
from dataclasses import dataclass
from datetime import date, datetime, timedelta
from typing import Any, Iterable

import pandas as pd
from sqlalchemy import text
from sqlalchemy.engine import Engine

from .config import Settings
from .kpi import daily_buckets
from .queries import Queries

log = logging.getLogger(__name__)


UPSERT_DAILY = """
INSERT INTO `analytics_daily_stats` (
    bucket_date, total_tickets, opened_tickets, closed_tickets, overdue_tickets,
    avg_frt_minutes, avg_mttr_hours, sla_frt_percent, sla_mttr_percent,
    fcr_percent, csat_score,
    agent_load, status_distribution, department_load
) VALUES (
    :bucket_date, :total_tickets, :opened_tickets, :closed_tickets, :overdue_tickets,
    :avg_frt_minutes, :avg_mttr_hours, :sla_frt_percent, :sla_mttr_percent,
    :fcr_percent, :csat_score,
    :agent_load, :status_distribution, :department_load
)
ON DUPLICATE KEY UPDATE
    total_tickets       = VALUES(total_tickets),
    opened_tickets      = VALUES(opened_tickets),
    closed_tickets      = VALUES(closed_tickets),
    overdue_tickets     = VALUES(overdue_tickets),
    avg_frt_minutes     = VALUES(avg_frt_minutes),
    avg_mttr_hours      = VALUES(avg_mttr_hours),
    sla_frt_percent     = VALUES(sla_frt_percent),
    sla_mttr_percent    = VALUES(sla_mttr_percent),
    fcr_percent         = VALUES(fcr_percent),
    csat_score          = VALUES(csat_score),
    agent_load          = VALUES(agent_load),
    status_distribution = VALUES(status_distribution),
    department_load     = VALUES(department_load)
"""

LOG_INSERT = """
INSERT INTO `analytics_logs` (level, component, event, message, payload)
VALUES (:level, :component, :event, :message, :payload)
"""


@dataclass
class RunResult:
    rows_processed: int
    buckets_upserted: int
    date_from: date
    date_to: date


def write_log(
    engine: Engine,
    event: str,
    *,
    level: str = "INFO",
    component: str = "worker",
    message: str | None = None,
    payload: dict[str, Any] | None = None,
) -> None:
    with engine.begin() as conn:
        conn.execute(
            text(LOG_INSERT),
            {
                "level": level,
                "component": component,
                "event": event,
                "message": message,
                "payload": json.dumps(payload, default=str) if payload else None,
            },
        )


def _load_lookup(engine: Engine, sql: str) -> pd.DataFrame:
    return pd.read_sql(text(sql), engine)


def run_once(engine: Engine, settings: Settings, *, lookback_days: int = 30) -> RunResult:
    queries = Queries.build(settings.table_prefix)
    date_to = datetime.utcnow().date() + timedelta(days=1)
    date_from = date_to - timedelta(days=lookback_days)

    statuses = _load_lookup(engine, queries.statuses)
    staff = _load_lookup(engine, queries.staff)
    departments = _load_lookup(engine, queries.departments)

    tickets = pd.read_sql(
        text(queries.tickets_in_range),
        engine,
        params={"date_from": date_from, "date_to": date_to},
    )
    log.info("Loaded %d tickets in window %s..%s", len(tickets), date_from, date_to)

    buckets = daily_buckets(
        tickets,
        statuses,
        staff,
        departments,
        sla_frt_minutes=settings.sla_frt_minutes,
        sla_mttr_hours=settings.sla_mttr_hours,
    )

    upserted = _upsert_buckets(engine, buckets)
    result = RunResult(
        rows_processed=len(tickets),
        buckets_upserted=upserted,
        date_from=date_from,
        date_to=date_to,
    )
    write_log(
        engine,
        event="aggregate.success",
        message=f"Aggregated {upserted} day-buckets from {len(tickets)} tickets",
        payload={
            "rows": len(tickets),
            "buckets": upserted,
            "from": date_from.isoformat(),
            "to": date_to.isoformat(),
        },
    )
    return result


def _upsert_buckets(engine: Engine, buckets: Iterable[dict[str, Any]]) -> int:
    serialised = [
        {
            **b,
            "agent_load": json.dumps(b["agent_load"], ensure_ascii=False),
            "status_distribution": json.dumps(b["status_distribution"], ensure_ascii=False),
            "department_load": json.dumps(b["department_load"], ensure_ascii=False),
        }
        for b in buckets
    ]
    if not serialised:
        return 0
    with engine.begin() as conn:
        conn.execute(text(UPSERT_DAILY), serialised)
    return len(serialised)
