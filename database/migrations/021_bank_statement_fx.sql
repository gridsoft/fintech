-- Девизни изводи (POSTING_RULES_ADDENDUM.md §8). Курсот живее на трансакцијата,
-- не на изводот — еден извод може да опфаќа повеќе денови, а НБРМ курсот е
-- дневен. currency_id = 1 (MKD) + exchange_rate = 1.000000 го задржува
-- постоечкото однесување за сите денарски изводи непроменето (по конструкција,
-- не по гранка) — истиот образец како invoices/purchase_invoices (migration 019).

ALTER TABLE bank_statements
    ADD COLUMN currency_id INT NOT NULL DEFAULT 1 AFTER account_id,
    ADD CONSTRAINT fk_bank_statements_currency FOREIGN KEY (currency_id) REFERENCES currencies (id);

ALTER TABLE bank_transactions
    ADD COLUMN exchange_rate DECIMAL(10,6) NOT NULL DEFAULT 1.000000 AFTER amount;
