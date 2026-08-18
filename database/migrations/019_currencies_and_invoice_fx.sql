-- Валута/курсни разлики (Фаза 9, останато). Излезните и влезните фактури
-- можат да се издаваат во странска валута (курсот се внесува рачно по
-- документ, датумот на фактурата е референтен датум за курсот). Документот
-- (totalNet/totalVat/totalGross, unit_price/line_total) останува во
-- валутата на фактурата — книжењето во главната книга секогаш конвертира
-- во MKD при издавање/заведување (issue()/post()), бидејќи главната книга
-- е секогаш во денари, нема мулти-валутни конта.
--
-- MKD е базната валута (is_base = 1), се внесува прва во оваа миграција па
-- добива id = 1 — затоа ADD COLUMN ... DEFAULT 1 подолу е безбеден за
-- постојните редови (сите досегашни фактури се денарски).

CREATE TABLE currencies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(3) NOT NULL,
    name VARCHAR(50) NOT NULL,
    is_base TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_currencies_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO currencies (code, name, is_base, is_active) VALUES ('MKD', 'Македонски денар', 1, 1);
INSERT INTO currencies (code, name, is_base, is_active) VALUES ('EUR', 'Евро', 0, 1);

ALTER TABLE invoices
    ADD COLUMN currency_id INT NOT NULL DEFAULT 1 AFTER partner_id,
    ADD COLUMN exchange_rate DECIMAL(10,6) NOT NULL DEFAULT 1.000000 AFTER currency_id,
    ADD CONSTRAINT fk_invoices_currency FOREIGN KEY (currency_id) REFERENCES currencies (id);

ALTER TABLE purchase_invoices
    ADD COLUMN currency_id INT NOT NULL DEFAULT 1 AFTER partner_id,
    ADD COLUMN exchange_rate DECIMAL(10,6) NOT NULL DEFAULT 1.000000 AFTER currency_id,
    ADD CONSTRAINT fk_purchase_invoices_currency FOREIGN KEY (currency_id) REFERENCES currencies (id);
