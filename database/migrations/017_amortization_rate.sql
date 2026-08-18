-- "Амортизациски век (месеци)" заменето со "стапка на амортизација" (%
-- годишно) — вака е официјално дефинирано во МК прописите (сметка 430 е
-- буквално "Амортизација по минимални СТАПКИ"), не по месеци. Месечниот
-- износ сега се пресметува: purchase_value * rate/100 / 12.

ALTER TABLE expense_categories
    ADD COLUMN default_annual_rate DECIMAL(5,2) NULL AFTER default_useful_life_months;

UPDATE expense_categories
    SET default_annual_rate = ROUND(1200 / default_useful_life_months, 2)
    WHERE default_useful_life_months IS NOT NULL AND default_useful_life_months > 0;

ALTER TABLE expense_categories
    DROP COLUMN default_useful_life_months;

ALTER TABLE fixed_assets
    ADD COLUMN annual_rate DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER useful_life_months;

UPDATE fixed_assets
    SET annual_rate = ROUND(1200 / useful_life_months, 2)
    WHERE useful_life_months > 0;

ALTER TABLE fixed_assets
    DROP COLUMN useful_life_months;
