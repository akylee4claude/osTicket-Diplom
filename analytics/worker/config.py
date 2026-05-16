import os
from dataclasses import dataclass


@dataclass(frozen=True)
class Settings:
    db_host: str
    db_port: int
    db_name: str
    db_user: str
    db_password: str
    table_prefix: str
    interval_sec: int
    sla_frt_minutes: int
    sla_mttr_hours: int
    anomaly_z: float

    @property
    def sqlalchemy_url(self) -> str:
        return (
            f"mysql+pymysql://{self.db_user}:{self.db_password}"
            f"@{self.db_host}:{self.db_port}/{self.db_name}?charset=utf8mb4"
        )


def load() -> Settings:
    return Settings(
        db_host=os.environ.get("OSTICKET_DB_HOST", "db"),
        db_port=int(os.environ.get("OSTICKET_DB_PORT", "3306")),
        db_name=os.environ.get("OSTICKET_DB_NAME", "osticket"),
        db_user=os.environ.get("OSTICKET_DB_USER", "osticket"),
        db_password=os.environ.get("OSTICKET_DB_PASSWORD", "osticket"),
        table_prefix=os.environ.get("OSTICKET_TABLE_PREFIX", "ost_"),
        interval_sec=int(os.environ.get("ANALYTICS_INTERVAL_SEC", "900")),
        sla_frt_minutes=int(os.environ.get("ANALYTICS_SLA_FRT_MIN", "60")),
        sla_mttr_hours=int(os.environ.get("ANALYTICS_SLA_MTTR_HOURS", "24")),
        anomaly_z=float(os.environ.get("ANALYTICS_ANOMALY_Z", "2.0")),
    )
