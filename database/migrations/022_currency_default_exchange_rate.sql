-- Стандарден курс по валута (UX): MKD/EUR е стабилен со години (де-факто
-- фиксиран околу 61.5), па внесување ист курс на секоја фактура/трансакција
-- е непотребно повторување. Ова е ЧИСТО UI-предполнување (mirror на
-- product.price → InvoiceLine.unit_price образецот) — курсот сепак се чува
-- посебно на секој документ/трансакција (invoices.exchange_rate,
-- bank_transactions.exchange_rate), видлив и уредлив, никогаш не се чита
-- "во живо" од currencies при книжење. Нема ефект на постоечкото книжење.

ALTER TABLE currencies
    ADD COLUMN default_exchange_rate DECIMAL(10,6) NULL AFTER is_active;

UPDATE currencies SET default_exchange_rate = 61.500000 WHERE code = 'EUR';
