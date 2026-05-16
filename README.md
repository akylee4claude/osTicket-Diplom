# osTicket-Diplom

ВКР: «Проектирование и разработка модуля системы аналитики и дашбордов для системы управления тикетами osTicket».

Автор — Сунцов А. Д., гр. АСУ-22-1б, ПНИПУ, 2026.

## Структура репозитория

```
upload/                          osTicket 1.18.3 (исходники с github.com/osTicket/osTicket)
upload/include/plugins/analytics/   PHP-плагин: дашборд + JSON API
analytics/worker/                Python-воркер: ETL и расчёт KPI (FRT, MTTR, SLA, нагрузка)
analytics/tools/seed_demo_data.py Генератор синтетических тикетов для демо/тестов
analytics/tests/                 Unit-тесты KPI-функций (pytest)
docker/                          Dockerfile'ы для PHP/Apache и Python-воркера
docker-compose.yml               Полный стенд: db + web + worker
docs/                            (зарезервировано) графический материал, скриншоты ВКР
```

## Архитектура (соответствие ТЗ, разд. 4.1)

Гибридная: PHP-плагин внутри osTicket обслуживает UI и JSON-API, Python-воркер
агрегирует сырые ост-таблицы и складывает суточные KPI в `analytics_daily_stats`.
Обмена между PHP и Python нет — только общий MySQL.

```
┌──────────────┐    HTTP    ┌───────────────────┐
│  Браузер     │ ───────▶   │ Plugin (PHP):      │
│  (Chart.js)  │ ◀───JSON── │ /scp/apps/analytics│
└──────────────┘            └─────────┬─────────┘
                                       │ SELECT
                          ┌────────────▼────────────┐
                          │   MySQL (osTicket)      │
                          │ ost_* + analytics_*     │
                          └────────────▲────────────┘
                                       │ UPSERT
                          ┌────────────┴────────────┐
                          │  Python worker          │
                          │  pandas + SQLAlchemy    │
                          └─────────────────────────┘
```

## Быстрый старт (Docker)

```bash
cp .env.example .env
docker compose up -d --build
# osTicket UI откроется на http://localhost:8080
```

Дальше:

1. **Установка osTicket.** Откройте `http://localhost:8080/setup/install.php` и
   выполните мастер установки. В качестве БД укажите `db`, пользователя/пароль
   из `.env` (по умолчанию `osticket/osticket`).
2. **После установки** удалите директорию `setup`:
   ```bash
   docker compose exec web rm -rf /var/www/html/setup
   docker compose exec web chmod 644 /var/www/html/include/ost-config.php
   ```
3. **Установите плагин.** В админ-панели → *Manage → Plugins* → *Add new plugin*
   → выберите *Analytics & Dashboards* → *Install* → *Enable*.
4. **Засейте демо-данные** (≈5000 тикетов за 90 дней):
   ```bash
   docker compose exec worker python -m analytics.tools.seed_demo_data \
       --tickets 5000 --days 90
   ```
5. **Дашборд** появится в меню *Applications → Аналитика* в staff-панели
   (`http://localhost:8080/scp/apps/analytics/`).

Воркер запускается автоматически в Docker и пересчитывает агрегаты каждые
`ANALYTICS_INTERVAL_SEC` секунд (по умолчанию 900 = 15 минут).

## Локальные тесты Python-модуля

```bash
pip install -r analytics/worker/requirements.txt pytest
pytest analytics/tests/ -v
```

## Конфигурация

Все параметры — через переменные окружения (см. `.env.example`):

| Переменная                 | Назначение                                | По умолчанию |
|----------------------------|-------------------------------------------|--------------|
| `MYSQL_*`                  | креды MySQL для osTicket                  | `osticket/osticket` |
| `OSTICKET_TABLE_PREFIX`    | префикс таблиц osTicket                   | `ost_` |
| `ANALYTICS_INTERVAL_SEC`   | период пересчёта агрегатов, сек           | `900` |
| `ANALYTICS_SLA_FRT_MIN`    | порог SLA по First Response Time (мин)    | `60` |
| `ANALYTICS_SLA_MTTR_HOURS` | порог SLA по Mean Time To Resolution (ч)  | `24` |
| `ANALYTICS_ANOMALY_Z`      | порог Z-score для обнаружения аномалий    | `2.0` |

Настройки SLA дополнительно дублируются в UI плагина (admin → Plugins →
Analytics & Dashboards → Config).

## Состав KPI (соответствие ТЗ, разд. 4.2)

* **FRT** — среднее время первого ответа: `created → MIN(thread_entry.created)`
  по записям типа `R` от штатных сотрудников.
* **MTTR** — среднее время разрешения: `created → closed`.
* **Загрузка сотрудников** — количество назначенных тикетов на исполнителя.
* **Распределение по статусам** — `ost_ticket_status.name → count`.
* **SLA FRT/MTTR** — доля тикетов, уложившихся в пороги.
* **Аномалии** — Z-score на наборе суточных метрик за выбранный период.

## Дальнейшее развитие

В рамках MVP (ветка `claude/setup-osticket-DxIQ7`) реализован минимально-
жизнеспособный модуль. Запланировано:

- сравнение двух периодов на одном графике (ТЗ, разд. 4.2.6);
- экспорт PDF (mPDF / dompdf) + расширенный CSV с сырыми тикетами;
- журнал `analytics_logs` UI-визуализатор для администратора;
- тесты PHP-контроллера через PHPUnit.

## Лицензия

GPL-2.0 (наследуется от osTicket).
