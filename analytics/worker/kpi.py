"""Pure KPI computation functions over pandas DataFrames.

Kept free of I/O so they are unit-testable. Input DataFrames are the result of
the SQL in `queries.py`.

Columns expected in `tickets`:
    ticket_id, status_id, dept_id, staff_id, created, closed,
    first_response_at, frt_minutes, resolution_minutes,
    isoverdue, isanswered.
"""

from __future__ import annotations

from datetime import date
from typing import Any

import numpy as np
import pandas as pd


def _safe_mean(series: pd.Series) -> float | None:
    cleaned = series.dropna()
    if cleaned.empty:
        return None
    return float(cleaned.mean())


def avg_first_response(tickets: pd.DataFrame) -> float | None:
    """Average First Response Time, minutes. NULL FRT entries are ignored."""
    if tickets.empty or "frt_minutes" not in tickets.columns:
        return None
    return _safe_mean(tickets["frt_minutes"])


def avg_resolution_hours(tickets: pd.DataFrame) -> float | None:
    """Mean Time To Resolution, hours."""
    if tickets.empty or "resolution_minutes" not in tickets.columns:
        return None
    minutes = _safe_mean(tickets["resolution_minutes"])
    return None if minutes is None else round(minutes / 60.0, 2)


def sla_compliance(values: pd.Series, threshold: float) -> float | None:
    """Share of values <= threshold, in percent (0..100). NULLs are excluded."""
    cleaned = values.dropna()
    if cleaned.empty:
        return None
    ok = (cleaned <= threshold).sum()
    return round(float(ok) / float(len(cleaned)) * 100.0, 2)


def status_distribution(
    tickets: pd.DataFrame, statuses: pd.DataFrame
) -> dict[str, int]:
    """Count of tickets per status name."""
    if tickets.empty:
        return {}
    merged = tickets.merge(
        statuses.rename(columns={"id": "status_id", "name": "status_name"}),
        on="status_id",
        how="left",
    )
    merged["status_name"] = merged["status_name"].fillna("Unknown")
    return merged.groupby("status_name")["ticket_id"].count().astype(int).to_dict()


def agent_load(tickets: pd.DataFrame, staff: pd.DataFrame) -> dict[str, int]:
    """Number of tickets assigned per staff member (`Unassigned` for staff_id=0)."""
    if tickets.empty:
        return {}
    merged = tickets.merge(staff, on="staff_id", how="left")
    merged["full_name"] = merged["full_name"].where(
        merged["staff_id"] != 0, other="Unassigned"
    ).fillna(f"Staff#unknown")
    return merged.groupby("full_name")["ticket_id"].count().astype(int).to_dict()


def department_load(tickets: pd.DataFrame, departments: pd.DataFrame) -> dict[str, int]:
    if tickets.empty:
        return {}
    merged = tickets.merge(
        departments.rename(columns={"id": "dept_id", "name": "dept_name"}),
        on="dept_id",
        how="left",
    )
    merged["dept_name"] = merged["dept_name"].fillna("Не указан")
    return merged.groupby("dept_name")["ticket_id"].count().astype(int).to_dict()


def daily_buckets(
    tickets: pd.DataFrame,
    statuses: pd.DataFrame,
    staff: pd.DataFrame,
    departments: pd.DataFrame,
    sla_frt_minutes: int,
    sla_mttr_hours: int,
) -> list[dict[str, Any]]:
    """Aggregate tickets into per-day buckets ready for `analytics_daily_stats`."""
    if tickets.empty:
        return []

    df = tickets.copy()
    df["bucket_date"] = pd.to_datetime(df["created"]).dt.date

    buckets: list[dict[str, Any]] = []
    sla_mttr_minutes = sla_mttr_hours * 60

    for bucket, day_df in df.groupby("bucket_date", sort=True):
        closed_count = int(day_df["closed"].notna().sum())
        bucket_payload = {
            "bucket_date": bucket,
            "total_tickets": int(len(day_df)),
            "opened_tickets": int(len(day_df) - closed_count),
            "closed_tickets": closed_count,
            "overdue_tickets": int(day_df["isoverdue"].fillna(0).astype(int).sum()),
            "avg_frt_minutes": _round_or_none(avg_first_response(day_df), 2),
            "avg_mttr_hours": avg_resolution_hours(day_df),
            "sla_frt_percent": sla_compliance(day_df["frt_minutes"], sla_frt_minutes),
            "sla_mttr_percent": sla_compliance(day_df["resolution_minutes"], sla_mttr_minutes),
            "agent_load": agent_load(day_df, staff),
            "status_distribution": status_distribution(day_df, statuses),
            "department_load": department_load(day_df, departments),
        }
        buckets.append(bucket_payload)
    return buckets


def detect_anomalies(history: pd.DataFrame, z_threshold: float) -> list[dict[str, Any]]:
    """Z-score anomaly detection across numeric metric columns in `history`.

    `history` must contain `bucket_date` plus numeric columns; the last row is
    compared against the mean/std of the preceding rows (>=3 rows required).
    Returns one record per metric that breaches the threshold.
    """
    if history.shape[0] < 4:
        return []

    sorted_hist = history.sort_values("bucket_date").reset_index(drop=True)
    current = sorted_hist.iloc[-1]
    past = sorted_hist.iloc[:-1]

    anomalies: list[dict[str, Any]] = []
    numeric_cols = [
        c for c in sorted_hist.columns
        if c != "bucket_date" and pd.api.types.is_numeric_dtype(sorted_hist[c])
    ]
    for column in numeric_cols:
        past_values = past[column].dropna()
        if past_values.size < 3:
            continue
        std = float(past_values.std(ddof=0))
        if std == 0 or np.isnan(std):
            continue
        mean = float(past_values.mean())
        value = current[column]
        if pd.isna(value):
            continue
        z = (float(value) - mean) / std
        if abs(z) >= z_threshold:
            anomalies.append({
                "metric": column,
                "bucket_date": current["bucket_date"],
                "value": float(value),
                "mean": round(mean, 4),
                "std": round(std, 4),
                "z_score": round(z, 2),
            })
    return anomalies


def _round_or_none(value: float | None, ndigits: int) -> float | None:
    return None if value is None else round(value, ndigits)


__all__ = [
    "avg_first_response",
    "avg_resolution_hours",
    "sla_compliance",
    "status_distribution",
    "agent_load",
    "department_load",
    "daily_buckets",
    "detect_anomalies",
]
