-- Додаток на плата за работен стаж (Чл. 106 од Законот за работни односи) —
-- законски минимум 0,5% од основната плата за секоја година вкупен работен
-- стаж (кај сите работодавци, не само оваа фирма). Уредливо тука исто како
-- другите стапки, во случај колективен договор да пропишува повисока стапка.

ALTER TABLE payroll_settings
    ADD COLUMN seniority_rate_per_year DECIMAL(5,2) NOT NULL DEFAULT 0.50 AFTER pit_rate;

UPDATE payroll_settings SET seniority_rate_per_year = 0.50;
