"""DDL for analytics tables (created by worker if missing)."""

from sqlalchemy import text
from sqlalchemy.engine import Engine

DAILY_STATS_DDL = """
CREATE TABLE IF NOT EXISTS `analytics_daily_stats` (
    `id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `bucket_date`           DATE NOT NULL,
    `total_tickets`         INT UNSIGNED NOT NULL DEFAULT 0,
    `opened_tickets`        INT UNSIGNED NOT NULL DEFAULT 0,
    `closed_tickets`        INT UNSIGNED NOT NULL DEFAULT 0,
    `overdue_tickets`       INT UNSIGNED NOT NULL DEFAULT 0,
    `avg_frt_minutes`       DECIMAL(12,2) NULL,
    `avg_mttr_hours`        DECIMAL(12,2) NULL,
    `sla_frt_percent`       DECIMAL(6,2)  NULL,
    `sla_mttr_percent`      DECIMAL(6,2)  NULL,
    `agent_load`            JSON NULL,
    `status_distribution`   JSON NULL,
    `department_load`       JSON NULL,
    `created_at`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_bucket_date` (`bucket_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
"""

LOGS_DDL = """
CREATE TABLE IF NOT EXISTS `analytics_logs` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ts`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `level`      VARCHAR(16) NOT NULL DEFAULT 'INFO',
    `component`  VARCHAR(64) NOT NULL DEFAULT 'worker',
    `event`      VARCHAR(128) NOT NULL,
    `message`    TEXT NULL,
    `payload`    JSON NULL,
    PRIMARY KEY (`id`),
    KEY `idx_ts` (`ts`),
    KEY `idx_level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
"""


def ensure_tables(engine: Engine) -> None:
    with engine.begin() as conn:
        conn.execute(text(DAILY_STATS_DDL))
        conn.execute(text(LOGS_DDL))
