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
    `fcr_percent`           DECIMAL(6,2)  NULL,
    `csat_score`            DECIMAL(4,2)  NULL,
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

# Оценки удовлетворённости клиентов (CSAT). В дефолтной поставке osTicket
# такой сущности нет — приёма опросов нет, шкалы оценки тоже. Заводим свою
# таблицу, заполняется опционально (плагин-опрос → этот стол, либо
# tools/seed_demo_data --with-csat для демонстрации на синтетике).
CSAT_DDL = """
CREATE TABLE IF NOT EXISTS `analytics_ticket_csat` (
    `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ticket_id` INT UNSIGNED NOT NULL,
    `score`     TINYINT UNSIGNED NOT NULL,
    `comment`   TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_ticket` (`ticket_id`),
    KEY `idx_score` (`score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
"""

# Идемпотентное добавление колонок: если БД создавалась более ранней
# версией воркера, колонок FCR/CSAT в analytics_daily_stats ещё нет.
# CREATE TABLE IF NOT EXISTS их не добавит — поэтому отдельный ALTER
# с проверкой через information_schema.
_ADD_COLUMN_TEMPLATE = """
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = 'analytics_daily_stats'
             AND COLUMN_NAME = '{column}');
SET @stmt := IF(@c = 0,
    'ALTER TABLE `analytics_daily_stats` ADD COLUMN `{column}` {definition} NULL AFTER `sla_mttr_percent`',
    'SELECT 1');
PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;
"""


def ensure_tables(engine: Engine) -> None:
    with engine.begin() as conn:
        conn.execute(text(DAILY_STATS_DDL))
        conn.execute(text(LOGS_DDL))
        conn.execute(text(CSAT_DDL))
        # Миграция: добиваем колонки в уже существующих БД.
        for column, definition in [
            ("fcr_percent", "DECIMAL(6,2)"),
            ("csat_score",  "DECIMAL(4,2)"),
        ]:
            for stmt in _ADD_COLUMN_TEMPLATE.format(column=column, definition=definition).strip().split(";"):
                stmt = stmt.strip()
                if stmt:
                    conn.execute(text(stmt))
