from datetime import datetime

import pandas as pd
import pytest

from analytics.worker.kpi import (
    agent_load,
    avg_first_response,
    avg_resolution_hours,
    daily_buckets,
    department_load,
    detect_anomalies,
    sla_compliance,
    status_distribution,
)


@pytest.fixture
def tickets() -> pd.DataFrame:
    return pd.DataFrame([
        {"ticket_id": 1, "status_id": 1, "dept_id": 1, "staff_id": 10,
         "created": datetime(2026, 5, 1, 9, 0), "closed": datetime(2026, 5, 1, 12, 0),
         "first_response_at": datetime(2026, 5, 1, 9, 30),
         "frt_minutes": 30.0, "resolution_minutes": 180.0,
         "isoverdue": 0, "isanswered": 1},
        {"ticket_id": 2, "status_id": 2, "dept_id": 1, "staff_id": 10,
         "created": datetime(2026, 5, 1, 10, 0), "closed": None,
         "first_response_at": datetime(2026, 5, 1, 11, 0),
         "frt_minutes": 60.0, "resolution_minutes": None,
         "isoverdue": 1, "isanswered": 1},
        {"ticket_id": 3, "status_id": 1, "dept_id": 2, "staff_id": 20,
         "created": datetime(2026, 5, 1, 14, 0), "closed": datetime(2026, 5, 2, 14, 0),
         "first_response_at": None,
         "frt_minutes": None, "resolution_minutes": 1440.0,
         "isoverdue": 0, "isanswered": 0},
        {"ticket_id": 4, "status_id": 1, "dept_id": 2, "staff_id": 0,
         "created": datetime(2026, 5, 2, 9, 0), "closed": None,
         "first_response_at": None,
         "frt_minutes": None, "resolution_minutes": None,
         "isoverdue": 0, "isanswered": 0},
    ])


@pytest.fixture
def statuses() -> pd.DataFrame:
    return pd.DataFrame([
        {"id": 1, "name": "Open", "state": "open"},
        {"id": 2, "name": "Pending", "state": "open"},
    ])


@pytest.fixture
def staff() -> pd.DataFrame:
    return pd.DataFrame([
        {"staff_id": 10, "full_name": "Иванов Иван"},
        {"staff_id": 20, "full_name": "Петров Пётр"},
    ])


@pytest.fixture
def departments() -> pd.DataFrame:
    return pd.DataFrame([
        {"id": 1, "name": "Поддержка"},
        {"id": 2, "name": "Сети"},
    ])


def test_avg_first_response_ignores_nulls(tickets):
    # Only tickets 1, 2 have FRT (30, 60) -> mean 45
    assert avg_first_response(tickets) == pytest.approx(45.0)


def test_avg_resolution_hours_in_hours(tickets):
    # resolution minutes: 180, 1440 -> avg 810 min -> 13.5h
    assert avg_resolution_hours(tickets) == pytest.approx(13.5)


def test_sla_compliance_threshold(tickets):
    # FRT values 30, 60 with threshold 60 -> both ok -> 100%
    assert sla_compliance(tickets["frt_minutes"], threshold=60) == 100.0
    # threshold 45 -> only 30 ok -> 50%
    assert sla_compliance(tickets["frt_minutes"], threshold=45) == 50.0


def test_sla_compliance_all_null():
    assert sla_compliance(pd.Series([None, None]), threshold=10) is None


def test_status_distribution(tickets, statuses):
    dist = status_distribution(tickets, statuses)
    assert dist == {"Open": 3, "Pending": 1}


def test_agent_load_marks_unassigned(tickets, staff):
    load = agent_load(tickets, staff)
    assert load["Иванов Иван"] == 2
    assert load["Петров Пётр"] == 1
    assert load["Unassigned"] == 1


def test_department_load_unknown_bucket(tickets, departments):
    load = department_load(tickets, departments)
    assert load == {"Поддержка": 2, "Сети": 2}


def test_daily_buckets_groups_per_day(tickets, statuses, staff, departments):
    buckets = daily_buckets(tickets, statuses, staff, departments,
                            sla_frt_minutes=60, sla_mttr_hours=24)
    by_date = {b["bucket_date"]: b for b in buckets}
    assert len(buckets) == 2
    day1 = by_date[tickets["created"].iloc[0].date()]
    assert day1["total_tickets"] == 3
    assert day1["closed_tickets"] == 2  # tickets 1 and 3 have non-null closed
    assert day1["opened_tickets"] == 1
    assert day1["overdue_tickets"] == 1
    assert day1["sla_frt_percent"] == 100.0  # 30, 60 both <=60


def test_detect_anomalies_spike():
    hist = pd.DataFrame({
        "bucket_date": pd.date_range("2026-05-01", periods=10).date,
        "total_tickets": [10, 11, 9, 12, 10, 11, 10, 9, 12, 80],  # spike at end
    })
    anomalies = detect_anomalies(hist, z_threshold=2.0)
    assert any(a["metric"] == "total_tickets" and a["z_score"] > 2 for a in anomalies)


def test_detect_anomalies_too_few_rows():
    hist = pd.DataFrame({"bucket_date": [], "total_tickets": []})
    assert detect_anomalies(hist, z_threshold=2.0) == []
