-- Конто по трансакција е СЕКОГАШ рачен избор (нема повеќе автоматско
-- разрешување 1200/1201/2200/2201 според partner->isForeign()) — секоја
-- трансакција отсега веднаш се книжи во главната книга наспроти избраното
-- конто, дури и кога нема поврзана фактура. Ова ја затвора празнината
-- каде трансакции без фактура (банкарски провизии, плати...) никогаш не
-- се книжеа. Салдото по ред (Салдо (ново) во UI) го бара почетно салдо
-- на изводот за да има смисла.

ALTER TABLE bank_statements
    ADD COLUMN opening_balance DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER reference;

ALTER TABLE bank_transactions
    ADD COLUMN code VARCHAR(50) NULL AFTER description,
    ADD COLUMN gl_account_id INT NULL AFTER partner_id,
    ADD COLUMN balance_after DECIMAL(15,2) NULL AFTER amount,
    ADD CONSTRAINT fk_bank_transactions_gl_account FOREIGN KEY (gl_account_id) REFERENCES accounts (id);
