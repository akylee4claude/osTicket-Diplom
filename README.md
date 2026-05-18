# osTicket-Diplom

ВКР: «Проектирование и разработка модуля системы аналитики и дашбордов для системы управления тикетами osTicket».

Автор — Сунцов А. Д., гр. АСУ-22-1б, ПНИПУ, 2026.

## Структура репозитория

```
upload/                                osTicket 1.18.3 (исходники с github.com/osTicket/osTicket)
upload/include/plugins/analytics/      PHP-плагин: дашборд + JSON API
upload/scp/apps/analytics.php          entry-точка плагина (UI + API)
upload/scp/js/vendor/chart.umd.js      Chart.js 4.4.4 (vendor, без CDN)
analytics/worker/                      Python-воркер: ETL и расчёт KPI
analytics/tools/seed_demo_data.py      генератор синтетических тикетов
analytics/tools/reaggregate.py         однократный пересчёт агрегатов
analytics/tests/                       Unit-тесты KPI-функций (pytest)
docker/                                Dockerfile'ы для PHP/Apache и Python-воркера
docker-compose.yml                     полный стенд: db + web + worker
```

## Архитектура (соответствие ТЗ, разд. 4.1)

Гибридная: PHP-плагин внутри osTicket обслуживает UI и JSON-API, Python-воркер
агрегирует сырые `ost_*` таблицы и складывает суточные KPI в `analytics_daily_stats`.
Прямого обмена между PHP и Python нет — только общий MySQL.

```
┌──────────────┐    HTTP    ┌───────────────────┐
│  Браузер     │ ───────▶   │ Plugin (PHP):     │
│  (Chart.js)  │ ◀───JSON── │ /scp/apps/        │
└──────────────┘            │    analytics.php  │
                            └─────────┬─────────┘
                                      │ SELECT
                          ┌───────────▼────────────┐
                          │   MySQL (osTicket)     │
                          │ ost_* + analytics_*    │
                          └───────────▲────────────┘
                                      │ UPSERT
                          ┌───────────┴────────────┐
                          │  Python worker         │
                          │  pandas + SQLAlchemy   │
                          └────────────────────────┘
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
2. **После установки** удалите директорию `setup` и закройте config от записи:
   ```bash
   docker compose exec web rm -rf /var/www/html/setup
   docker compose exec web chmod 644 /var/www/html/include/ost-config.php
   ```
3. **Установите плагин.** В админ-панели → *Manage → Plugins* → *Add new plugin*
   → выберите *Analytics & Dashboards* → *Install* → *Enable*.
4. **Засейте демо-данные** (≈5000 тикетов за 90 дней + CSAT-оценки):
   ```bash
   docker compose exec worker python -m analytics.tools.seed_demo_data \
       --tickets 5000 --days 90 --with-csat
   ```
5. **Дашборд** появится в меню *Applications → Аналитика* в staff-панели
   (`http://localhost:8080/scp/apps/analytics.php`).

Воркер запускается автоматически в Docker и пересчитывает агрегаты каждые
`ANALYTICS_INTERVAL_SEC` секунд (по умолчанию 900 = 15 минут). Триггер
ручного пересчёта доступен в админ-блоке дашборда.

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

## Состав KPI (соответствие ТЗ, разд. 4.2 и 4.4.9)

* **FRT** — среднее время первого ответа: `created → MIN(thread_entry.created)`
  по записям типа `R` от штатных сотрудников.
* **MTTR** — среднее время разрешения: `created → closed`.
* **SLA FRT/MTTR** — доля тикетов, уложившихся в пороги (см. конфигурацию).
* **FCR** (First Call Resolution) — доля закрытых тикетов без переоткрытий
  (`closed IS NOT NULL AND reopened IS NULL`).
* **CSAT** (Customer Satisfaction Score) — средняя оценка по шкале 1..5.
  Источник — таблица `analytics_ticket_csat` (в дефолтной osTicket опроса
  нет; для демо генерируется флагом `--with-csat`).
* **Загрузка сотрудников** — количество назначенных тикетов на исполнителя.
* **Распределение по статусам** — четыре бизнес-категории: «Открыта»,
  «Открыта, просрочена», «Закрыта», «Закрыта с просрочкой».
* **Аномалии** — Z-score на скользящем 7-дневном окне для всех числовых
  метрик. Подсветка точек прямо на линейных графиках + список под графиками
  с фильтром и пагинацией.

## Дополнительные возможности

* **Сравнение двух периодов** (ТЗ 4.2.6): селект «Сравнить с» в фильтрах
  → одновременный показ периодов A и B на линейных графиках, дельты на
  KPI-карточках с цветовой семантикой (рост FCR — зелёная, рост FRT —
  красная).
* **Drill-down по клику на аномалию** — модалка с KPI за день,
  распределениями и таблицей конкретных тикетов (с FRT, статусом, флагами).
* **Экспорт отчётов** в трёх форматах (ТЗ 4.2.5):
  * CSV — `Analytics\Api::exportCsv`
  * XLSX — `Analytics\XlsxWriter` (свой минимальный writer на `ZipArchive`,
    без зависимостей)
  * PDF — `mPDF` (bundled в osTicket)
  Каждый формат поддерживает два режима: «агрегаты» (KPI по дням) и
  «сырые тикеты» (per-ticket с join'ами на staff/department/status).
  Активный фильтр (см. ниже) автоматически попадает в экспорт.
* **Журнал событий и ручной триггер пересчёта** для админа (ТЗ 4.2.8):
  кнопка «Запустить пересчёт сейчас» пишет строку `event='run.requested'`
  в `analytics_logs`, воркер опрашивает таблицу раз в секунду и
  просыпается на новой записи.

## Ролевая модель (ТЗ 4.4.5)

Плагин различает три роли на основании штатных признаков osTicket:

| Роль        | Признак osTicket                              | Что видит                                                | UI-различия                                              |
|-------------|-----------------------------------------------|----------------------------------------------------------|----------------------------------------------------------|
| **Администратор** | `$thisstaff->isAdmin() === true`       | Все сотрудники и отделы, полный экспорт                  | Бейдж (красный), **блок «Администрирование»** внизу (журнал + триггер) |
| **Руководитель**  | `$thisstaff->isManager() === true`     | Все сотрудники и отделы, доступна фильтрация             | Бейдж (оранжевый), селекты «Сотрудник» / «Отдел» активны |
| **Агент**         | По умолчанию                           | Только тикеты, назначенные на него                       | Бейдж (зелёный), селекты фильтра скрыты, подсказка о scope'e |

**Защита от подмены параметров (defense in depth):** для роли `agent`
backend в `Analytics\Api::effectiveFilter()` **игнорирует** `?staff_id=...`
из URL и подставляет id из серверной сессии. Подделать невозможно без
доступа к `SECRET_SALT` (через который подписаны сессионные cookie
osTicket). Это значит, что даже если агент вручную составит запрос в
DevTools/curl/postman с чужим `staff_id`, он всё равно получит только
свои данные.

### Создание тестовых учёток

Для проверки ролевой модели на dev-стенде:

```bash
# 1. В Admin Panel → Agents → Departments создаём департамент.
# 2. В Admin Panel → Agents → Add New Agent создаём двух агентов
#    (manager_test, agent_test). Для manager_test — НЕ ставим галку
#    "Administrator". Для agent_test — то же самое.
# 3. В свойствах департамента полем Manager выбираем manager_test.
# 4. Назначаем им часть seed-тикетов и пересчитываем:
docker compose exec db mysql -uosticket -posticket osticket -e \
  "UPDATE ost_ticket SET staff_id = (SELECT staff_id FROM ost_staff \
   WHERE username='agent_test' LIMIT 1) \
   WHERE number LIKE 'SEED-%' AND staff_id != 0 ORDER BY RAND() LIMIT 250;"
docker compose exec worker python -m analytics.tools.reaggregate --days 90 --clear
```

Под manager_test бейдж должен быть оранжевый «Руководитель», под
agent_test — зелёный «Агент». Под admin (исходный) — красный «Администратор».

## Инструменты

* `python -m analytics.tools.seed_demo_data --tickets N --days D [--with-csat] [--anomaly-spike K]`
  — генерация синтетических тикетов. `--with-csat` добавляет CSAT-оценки,
  `--anomaly-spike K` вставляет K дополнительных просроченных за «сегодня»
  для демонстрации аномалий.
* `python -m analytics.tools.reaggregate --days N [--clear]` — однократный
  пересчёт `analytics_daily_stats` за заданное окно. `--clear` сносит
  таблицу полностью перед пересчётом — нужно после изменений в логике
  агрегации (например, изменения формата `status_distribution`).

## Дальнейшее развитие

Закрыто в текущей версии (ветка `dev`):

- Сравнение двух периодов (ТЗ 4.2.6)
- Экспорт CSV / XLSX / PDF + сырые тикеты (ТЗ 4.2.5)
- Журнал `analytics_logs` UI-визуализатор + триггер пересчёта (ТЗ 4.2.8)
- Подсветка аномалий на графиках + drill-down (ТЗ 4.2.7)
- FCR / CSAT метрики (ТЗ 4.3.6, 4.4.9)
- Фильтр по сотруднику и отделу (ТЗ 4.2.4)
- Ролевая модель admin / manager / agent (ТЗ 4.4.5)

Кандидаты на следующие итерации:

- PHPUnit-тесты контроллера и репозитория (сейчас только Python KPI-функции).
- Интеграция с настоящим CSAT-плагином (вместо синтетики).
- Тонкая ролевая модель: тимлид (видит свою команду), аудитор (read-only).
- Real-time обновление дашборда через WebSocket / Server-Sent Events.

## Лицензия

GPL-2.0 (наследуется от osTicket).
