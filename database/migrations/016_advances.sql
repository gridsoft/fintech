-- Фаза 9 (дел 1): Аванси. Примањето/давањето на самиот аванс НЕ е нов
-- механизам — тоа е обична банкарска трансакција книжена рачно на едно од
-- овие конта (истиот "Додади трансакција без фактура" пат од Фаза 7).
-- Единствено ново е примената на аванс на фактура (advance_applications).

-- Аналитички под-сметки, ист образец како 1200/1201 и 2200/2201
-- (011_purchase_invoices.sql). 370 е официјално за суровини/стоки, но по
-- договор со клиентот се користи генерички за секаков аванс на добавувач.
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '2230', 'Обврски за примени аванси во земјата — аналитика', 'liability', id, 1 FROM accounts WHERE code = '223';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '2231', 'Обврски за примени аванси од странство — аналитика', 'liability', id, 1 FROM accounts WHERE code = '223';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '3700', 'Дадени аванси во земјата — аналитика', 'asset', id, 1 FROM accounts WHERE code = '370';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '3701', 'Дадени аванси во странство — аналитика', 'asset', id, 1 FROM accounts WHERE code = '370';

-- Еден bank_transaction (аванс) може да се примени на повеќе фактури низ
-- време, делумно секој пат — оттука посебна табела, не проширување на
-- постоечкото 1-на-1 matched_status на bank_transactions.
CREATE TABLE advance_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bank_transaction_id INT NOT NULL,
    invoice_type ENUM('sales', 'purchase') NOT NULL,
    invoice_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    journal_entry_id INT NOT NULL,
    applied_date DATE NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_advapp_transaction FOREIGN KEY (bank_transaction_id) REFERENCES bank_transactions (id),
    CONSTRAINT fk_advapp_journal_entry FOREIGN KEY (journal_entry_id) REFERENCES journal_entries (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_advapp_invoice ON advance_applications (invoice_type, invoice_id);
CREATE INDEX idx_advapp_transaction ON advance_applications (bank_transaction_id);
