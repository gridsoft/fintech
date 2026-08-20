-- Вкупен работен стаж не може да се изведе само од hire_date (стаж кај
-- ПРЕТХОДНИ работодавци не е познат оттаму) — се внесува рачно еднаш при
-- вработување ("признат стаж"), а натамошниот стаж кај оваа фирма се
-- пресметува автоматски (hire_date -> период) во PayrollService.

ALTER TABLE employees
    ADD COLUMN prior_staz_months INT NOT NULL DEFAULT 0 AFTER hire_date;
