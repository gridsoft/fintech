-- Влезни фактури (набавки). Сметката се резолвира автоматски од
-- expense_categories (веќе создадени во 008, до сега неискористени) +
-- контекст домашен/странски партнер — исто како кај излезните фактури.
-- За разлика од продажната страна, ДДВ стапката НЕ се резолвира од
-- категоријата туку се внесува рачно по линија, бидејќи е онаа што стои
-- на примената фактура од добавувачот. Види POSTING_RULES_ADDENDUM.md §5.

-- Влезен (одбивен) ДДВ треба своја сметка, различна од сметката за обврска
-- за излезен ДДВ (260) која веќе постои на payable_account_id.
ALTER TABLE vat_rates
    ADD COLUMN receivable_account_id INT NULL AFTER payable_account_id,
    ADD CONSTRAINT fk_vat_rates_receivable_account FOREIGN KEY (receivable_account_id) REFERENCES accounts (id);

UPDATE vat_rates
    SET receivable_account_id = (SELECT id FROM accounts WHERE code = '160')
    WHERE type IN ('standard', 'reduced');

-- Аналитички под-сметки на официјалните 220/221, огледало на 1200/1201
-- аналитиката веќе воведена за побарувањата (010_receivables_analytics.sql).
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '2200', 'Обврски спрема добавувачи во земјата — аналитика', 'liability', id, 1 FROM accounts WHERE code = '220';
INSERT INTO accounts (code, name, type, parent_id, is_active)
    SELECT '2201', 'Обврски спрема добавувачи во странство — аналитика', 'liability', id, 1 FROM accounts WHERE code = '221';

CREATE TABLE purchase_invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partner_id INT NOT NULL,
    supplier_number VARCHAR(50) NOT NULL,
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('draft', 'posted', 'paid', 'cancelled') NOT NULL DEFAULT 'draft',
    total_net DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_vat DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_gross DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    journal_entry_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_purchase_invoices_partner_number (partner_id, supplier_number),
    CONSTRAINT fk_purchase_invoices_partner FOREIGN KEY (partner_id) REFERENCES partners (id),
    CONSTRAINT fk_purchase_invoices_journal_entry FOREIGN KEY (journal_entry_id) REFERENCES journal_entries (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE purchase_invoice_lines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_invoice_id INT NOT NULL,
    expense_category_id INT NOT NULL,
    description VARCHAR(255) NULL,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1.00,
    unit_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    account_id INT NOT NULL,
    vat_rate_id INT NOT NULL,
    vat_rate DECIMAL(5,2) NOT NULL,
    line_total DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT fk_pil_invoice FOREIGN KEY (purchase_invoice_id) REFERENCES purchase_invoices (id) ON DELETE CASCADE,
    CONSTRAINT fk_pil_category FOREIGN KEY (expense_category_id) REFERENCES expense_categories (id),
    CONSTRAINT fk_pil_account FOREIGN KEY (account_id) REFERENCES accounts (id),
    CONSTRAINT fk_pil_vat_rate FOREIGN KEY (vat_rate_id) REFERENCES vat_rates (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_purchase_invoices_partner ON purchase_invoices (partner_id);
CREATE INDEX idx_purchase_invoices_status ON purchase_invoices (status);
