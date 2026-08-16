-- Почетен контен план (основни групи и сметки за старт)

-- Класа 0 — Основни средства
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('0000', 'Основни средства', 'asset', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('0100', 'Земјиште', 'asset', (SELECT id FROM (SELECT id FROM accounts WHERE code = '0000') a), 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('0200', 'Градежни објекти', 'asset', (SELECT id FROM (SELECT id FROM accounts WHERE code = '0000') a), 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('0300', 'Опрема', 'asset', (SELECT id FROM (SELECT id FROM accounts WHERE code = '0000') a), 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('0900', 'Акумулирана амортизација', 'asset', (SELECT id FROM (SELECT id FROM accounts WHERE code = '0000') a), 1);

-- Класа 1 — Залихи
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('1000', 'Залихи', 'asset', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('1200', 'Материјали', 'asset', (SELECT id FROM (SELECT id FROM accounts WHERE code = '1000') a), 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('1300', 'Стоки', 'asset', (SELECT id FROM (SELECT id FROM accounts WHERE code = '1000') a), 1);

-- Класа 2 — Пари, побарувања и краткорочни хартии од вредност
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('2000', 'Пари и побарувања', 'asset', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('2200', 'Купувачи (побарувања)', 'asset', (SELECT id FROM (SELECT id FROM accounts WHERE code = '2000') a), 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('2400', 'Парични средства во благајна', 'asset', (SELECT id FROM (SELECT id FROM accounts WHERE code = '2000') a), 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('2410', 'Трансакциска сметка', 'asset', (SELECT id FROM (SELECT id FROM accounts WHERE code = '2000') a), 1);

-- Класа 4 — Обврски
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('4000', 'Обврски', 'liability', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('4200', 'Добавувачи', 'liability', (SELECT id FROM (SELECT id FROM accounts WHERE code = '4000') a), 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('4300', 'Обврски за ДДВ', 'liability', (SELECT id FROM (SELECT id FROM accounts WHERE code = '4000') a), 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('4600', 'Обврски кон вработени', 'liability', (SELECT id FROM (SELECT id FROM accounts WHERE code = '4000') a), 1);

-- Класа 6 — Приходи
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('6000', 'Приходи', 'revenue', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('6100', 'Приходи од продажба на производи и услуги', 'revenue', (SELECT id FROM (SELECT id FROM accounts WHERE code = '6000') a), 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('6600', 'Финансиски приходи', 'revenue', (SELECT id FROM (SELECT id FROM accounts WHERE code = '6000') a), 1);

-- Класа 7 — Расходи
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('7000', 'Расходи', 'expense', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('7400', 'Трошоци за материјали', 'expense', (SELECT id FROM (SELECT id FROM accounts WHERE code = '7000') a), 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('7500', 'Трошоци за вработени', 'expense', (SELECT id FROM (SELECT id FROM accounts WHERE code = '7000') a), 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('7600', 'Останати оперативни трошоци', 'expense', (SELECT id FROM (SELECT id FROM accounts WHERE code = '7000') a), 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('7700', 'Финансиски расходи', 'expense', (SELECT id FROM (SELECT id FROM accounts WHERE code = '7000') a), 1);

-- Класа 9 — Капитал
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('9000', 'Капитал', 'equity', NULL, 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('9100', 'Основна главнина', 'equity', (SELECT id FROM (SELECT id FROM accounts WHERE code = '9000') a), 1);
INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES ('9800', 'Акумулирана добивка/загуба', 'equity', (SELECT id FROM (SELECT id FROM accounts WHERE code = '9000') a), 1);
