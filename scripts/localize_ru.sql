-- Перевод на русский язык контента клиентского портала, который хранится в БД
-- (а не в PHP-файлах). Применить один раз на работающую установку osTicket:
--
--   docker compose cp scripts/localize_ru.sql db:/tmp/localize_ru.sql
--   docker compose exec db sh -c "mysql -uosticket -posticket osticket < /tmp/localize_ru.sql"
--
-- Префикс таблиц по умолчанию ost_. При другом префиксе — заменить.

-- 1. Landing page (текст и заголовок на http://localhost:8080/).
--    В osTicket 1.18 хранится в ost_content (а не в ost_page).
UPDATE ost_content
SET body = CONCAT(
    '<h1>Добро пожаловать в центр поддержки</h1>',
    '<p>Чтобы упростить обработку обращений и повысить качество обслуживания, ',
    'мы используем систему управления заявками. Каждой заявке присваивается ',
    'уникальный номер, по которому вы можете отслеживать ход её обработки и ',
    'ответы специалистов. Для вашего удобства мы храним полный архив и историю ',
    'всех ваших обращений. Для подачи заявки требуется действующий адрес ',
    'электронной почты.</p>'
)
WHERE type = 'landing';

-- 2. Форма «Контактная информация» (показывается на /open.php неавторизованному пользователю)
UPDATE ost_form
SET title = 'Контактная информация'
WHERE type = 'U';

-- 3. Поля формы «Контактная информация»
UPDATE ost_form_field ff
JOIN ost_form f ON f.id = ff.form_id AND f.type = 'U'
SET ff.label = 'Электронная почта'
WHERE ff.name = 'email';

UPDATE ost_form_field ff
JOIN ost_form f ON f.id = ff.form_id AND f.type = 'U'
SET ff.label = 'Полное имя'
WHERE ff.name = 'name';

UPDATE ost_form_field ff
JOIN ost_form f ON f.id = ff.form_id AND f.type = 'U'
SET ff.label = 'Номер телефона'
WHERE ff.name = 'phone';

-- 4. Форма «Детали заявки» и её поля — отображаются после выбора темы обращения
UPDATE ost_form
SET title = 'Детали заявки'
WHERE type = 'T';

UPDATE ost_form_field ff
JOIN ost_form f ON f.id = ff.form_id AND f.type = 'T'
SET ff.label = 'Тема заявки'
WHERE ff.name = 'subject';

UPDATE ost_form_field ff
JOIN ost_form f ON f.id = ff.form_id AND f.type = 'T'
SET ff.label = 'Описание проблемы',
    ff.hint  = 'Подробно опишите причину обращения.'
WHERE ff.name = 'message';

UPDATE ost_form_field ff
JOIN ost_form f ON f.id = ff.form_id AND f.type = 'T'
SET ff.label = 'Приоритет'
WHERE ff.name = 'priority';
