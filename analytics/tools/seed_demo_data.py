"""Generate synthetic tickets, threads and responses for demo / load testing.

Inserts directly into osTicket tables — run only against a fresh test install.
Assumes osTicket has been installed (so ost_* tables exist) and default staff
account (admin) is present.

Usage:
    docker compose run --rm worker python -m analytics.tools.seed_demo_data \\
        --tickets 5000 --days 90
"""

from __future__ import annotations

import argparse
import logging
import os
import random
import sys
from datetime import datetime, timedelta

from sqlalchemy import text

# Allow running both as module and as script
if __package__ in (None, ""):
    sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..")))

from analytics.worker import config
from analytics.worker.db import make_engine, wait_until_ready

logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(message)s")
log = logging.getLogger("seed")

SOURCES = ["Web", "Email", "Phone", "API", "Other"]
TOPIC_TITLES = ["Доступ", "Сеть", "Принтер", "Учётная запись", "ПО", "1С", "Почта", "Прочее"]
DEPT_NAMES = ["Поддержка", "Сети", "Разработка", "Безопасность"]
STAFF_SAMPLE = [
    ("ivanov", "Иван", "Иванов"),
    ("petrov", "Пётр", "Петров"),
    ("sidorova", "Светлана", "Сидорова"),
    ("kovalev", "Алексей", "Ковалёв"),
    ("morozova", "Наталья", "Морозова"),
]


def parse_args() -> argparse.Namespace:
    p = argparse.ArgumentParser()
    p.add_argument("--tickets", type=int, default=5000)
    p.add_argument("--days", type=int, default=90)
    p.add_argument("--prefix", default=os.environ.get("OSTICKET_TABLE_PREFIX", "ost_"))
    p.add_argument("--seed", type=int, default=42)
    p.add_argument("--wipe", action="store_true",
                   help="Delete all seeded tickets before generating new ones")
    return p.parse_args()


def fetch_lookups(engine, prefix: str):
    with engine.connect() as conn:
        statuses = conn.execute(text(
            f"SELECT id, name, state FROM `{prefix}ticket_status` ORDER BY id"
        )).all()
        depts = conn.execute(text(
            f"SELECT id FROM `{prefix}department` ORDER BY id"
        )).scalars().all()
        staff = conn.execute(text(
            f"SELECT staff_id FROM `{prefix}staff` ORDER BY staff_id"
        )).scalars().all()
    if not statuses:
        raise SystemExit("No ticket statuses found — install osTicket first.")
    if not depts:
        raise SystemExit("No departments found — install osTicket first.")
    if not staff:
        raise SystemExit("No staff found — create the admin account first.")
    open_status = next((s for s in statuses if s.state == "open"), statuses[0])
    closed_status = next((s for s in statuses if s.state == "closed"), statuses[-1])
    return depts, staff, open_status, closed_status


def wipe_seeded(conn, prefix: str) -> None:
    log.info("Wiping previously seeded tickets")
    conn.execute(text(f"""
        DELETE te FROM `{prefix}thread_entry` te
        JOIN `{prefix}thread` th ON th.id = te.thread_id
        JOIN `{prefix}ticket` t  ON t.ticket_id = th.object_id AND th.object_type = 'T'
        WHERE t.source = 'API' AND t.number LIKE 'SEED-%'
    """))
    conn.execute(text(f"""
        DELETE th FROM `{prefix}thread` th
        JOIN `{prefix}ticket` t ON t.ticket_id = th.object_id AND th.object_type = 'T'
        WHERE t.source = 'API' AND t.number LIKE 'SEED-%'
    """))
    conn.execute(text(f"DELETE FROM `{prefix}ticket` WHERE source='API' AND number LIKE 'SEED-%'"))


def insert_ticket(conn, prefix: str, *, created: datetime, status_id: int,
                   dept_id: int, staff_id: int, closed_at: datetime | None,
                   first_response_at: datetime | None, isoverdue: int, number: str) -> int:
    last_update = closed_at or first_response_at or created
    duedate = created + timedelta(hours=random.choice([4, 8, 24, 48, 72]))
    res = conn.execute(text(f"""
        INSERT INTO `{prefix}ticket` (
            number, user_id, user_email_id, status_id, dept_id, sla_id, topic_id,
            staff_id, team_id, email_id, lock_id, flags, sort, ip_address,
            source, isoverdue, isanswered, duedate, est_duedate, reopened, closed,
            lastupdate, created, updated
        ) VALUES (
            :number, 0, 0, :status_id, :dept_id, 0, 0, :staff_id, 0, 0, 0, 0, 0,
            '127.0.0.1', 'API', :isoverdue,
            :isanswered, :duedate, :duedate, NULL, :closed,
            :lastupdate, :created, :lastupdate
        )
    """), {
        "number": number,
        "status_id": status_id,
        "dept_id": dept_id,
        "staff_id": staff_id,
        "isoverdue": isoverdue,
        "isanswered": 1 if first_response_at else 0,
        "duedate": duedate,
        "closed": closed_at,
        "lastupdate": last_update,
        "created": created,
    })
    return int(res.lastrowid)


def insert_thread(conn, prefix: str, ticket_id: int, created: datetime,
                  last_response: datetime | None) -> int:
    res = conn.execute(text(f"""
        INSERT INTO `{prefix}thread` (object_id, object_type, extra, lastresponse,
                                       lastmessage, created)
        VALUES (:object_id, 'T', NULL, :lastresponse, :created, :created)
    """), {
        "object_id": ticket_id,
        "lastresponse": last_response,
        "created": created,
    })
    return int(res.lastrowid)


def insert_message(conn, prefix: str, thread_id: int, created: datetime, body: str) -> None:
    conn.execute(text(f"""
        INSERT INTO `{prefix}thread_entry` (
            pid, thread_id, staff_id, user_id, type, flags, poster, source,
            title, body, format, ip_address, extra, recipients, created, updated
        ) VALUES (
            0, :thread_id, 0, 0, 'M', 0, 'demo-user', 'API',
            'Seeded ticket', :body, 'text', '127.0.0.1', NULL, NULL, :created, :created
        )
    """), {"thread_id": thread_id, "body": body, "created": created})


def insert_response(conn, prefix: str, thread_id: int, staff_id: int,
                    created: datetime) -> None:
    conn.execute(text(f"""
        INSERT INTO `{prefix}thread_entry` (
            pid, thread_id, staff_id, user_id, type, flags, poster, source,
            title, body, format, ip_address, extra, recipients, created, updated
        ) VALUES (
            0, :thread_id, :staff_id, 0, 'R', 0, 'demo-agent', 'API',
            'Re: Seeded ticket', 'Reply body', 'text', '127.0.0.1', NULL, NULL,
            :created, :created
        )
    """), {"thread_id": thread_id, "staff_id": staff_id, "created": created})


def generate(args) -> None:
    settings = config.load()
    engine = make_engine(settings)
    wait_until_ready(engine)
    random.seed(args.seed)

    depts, staff_ids, open_status, closed_status = fetch_lookups(engine, args.prefix)
    now = datetime.utcnow().replace(microsecond=0)
    window_start = now - timedelta(days=args.days)

    with engine.begin() as conn:
        if args.wipe:
            wipe_seeded(conn, args.prefix)

        for i in range(args.tickets):
            # Skew creation toward business hours of recent days
            day_offset = int(random.triangular(0, args.days, args.days * 0.7))
            base_day = window_start + timedelta(days=args.days - day_offset)
            hour = random.choices(range(24), weights=_hour_weights())[0]
            created = base_day.replace(hour=hour, minute=random.randint(0, 59),
                                       second=random.randint(0, 59))

            # FRT: lognormal-ish, mean ~45 min, but ~10% breaches
            frt_minutes = max(1, int(random.lognormvariate(3.4, 0.9)))
            first_response = created + timedelta(minutes=frt_minutes)

            # MTTR: 70% closed within window, exponential
            is_closed = random.random() < 0.7
            if is_closed:
                mttr_hours = max(0.1, random.expovariate(1 / 18))
                closed_at = first_response + timedelta(hours=mttr_hours)
                status = closed_status
            else:
                closed_at = None
                status = open_status

            # 8% overdue flag
            isoverdue = 1 if random.random() < 0.08 else 0
            staff_id = random.choice(staff_ids + [0] * 2)  # some unassigned
            dept_id = random.choice(depts)
            number = f"SEED-{i+1:06d}"

            ticket_id = insert_ticket(conn, args.prefix, created=created,
                                       status_id=status.id, dept_id=dept_id,
                                       staff_id=staff_id, closed_at=closed_at,
                                       first_response_at=first_response,
                                       isoverdue=isoverdue, number=number)
            thread_id = insert_thread(conn, args.prefix, ticket_id, created,
                                       closed_at or first_response)
            insert_message(conn, args.prefix, thread_id, created, "Original request body")
            if staff_id != 0:
                insert_response(conn, args.prefix, thread_id, staff_id, first_response)

            if (i + 1) % 500 == 0:
                log.info("Inserted %d / %d tickets", i + 1, args.tickets)

    log.info("Seeding done: %d tickets over %d days", args.tickets, args.days)


def _hour_weights() -> list[int]:
    return [1, 1, 1, 1, 1, 1, 2, 3, 6, 9, 10, 10, 9, 9, 10, 9, 7, 4, 3, 2, 2, 1, 1, 1]


if __name__ == "__main__":
    generate(parse_args())
