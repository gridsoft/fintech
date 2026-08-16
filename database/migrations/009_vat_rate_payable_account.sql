-- Секоја ДДВ стапка знае на која сметка се книжи обврската за ДДВ (кредит),
-- за да може постирањето (сек. 3 од addendum-от) да групира по стапка и
-- директно да знае каде да го книжи износот.
ALTER TABLE vat_rates
    ADD COLUMN payable_account_id INT NULL AFTER type,
    ADD CONSTRAINT fk_vat_rates_payable_account FOREIGN KEY (payable_account_id) REFERENCES accounts (id);
