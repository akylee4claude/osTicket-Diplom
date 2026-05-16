import logging
import time
from contextlib import contextmanager

from sqlalchemy import create_engine, text
from sqlalchemy.engine import Engine
from sqlalchemy.exc import OperationalError

from .config import Settings

log = logging.getLogger(__name__)


def make_engine(settings: Settings) -> Engine:
    return create_engine(
        settings.sqlalchemy_url,
        pool_pre_ping=True,
        pool_recycle=1800,
        future=True,
    )


def wait_until_ready(engine: Engine, attempts: int = 30, delay: float = 2.0) -> None:
    last_error: Exception | None = None
    for n in range(1, attempts + 1):
        try:
            with engine.connect() as conn:
                conn.execute(text("SELECT 1"))
            return
        except OperationalError as exc:
            last_error = exc
            log.info("DB not ready (attempt %d/%d): %s", n, attempts, exc)
            time.sleep(delay)
    raise RuntimeError(f"Database not reachable after {attempts} attempts: {last_error}")


@contextmanager
def transaction(engine: Engine):
    with engine.begin() as conn:
        yield conn
