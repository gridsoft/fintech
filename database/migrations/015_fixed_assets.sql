-- Фаза 8: Основни средства. Влезна фактура за категорија со
-- is_capitalizable = true (веќе постоечко поле, досега гардирано со
-- исклучок во PurchaseInvoiceService) сега создава запис во fixed_assets
-- при book-ирање, и добива месечна права-линиска амортизација.

ALTER TABLE expense_categories
    ADD COLUMN default_useful_life_months INT NULL AFTER is_capitalizable;

CREATE TABLE fixed_assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    account_id INT NOT NULL,
    purchase_invoice_id INT NOT NULL,
    purchase_invoice_line_id INT NOT NULL,
    purchase_date DATE NOT NULL,
    purchase_value DECIMAL(15,2) NOT NULL,
    useful_life_months INT NOT NULL,
    status ENUM('active', 'disposed') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_fixed_assets_account FOREIGN KEY (account_id) REFERENCES accounts (id),
    CONSTRAINT fk_fixed_assets_purchase_invoice FOREIGN KEY (purchase_invoice_id) REFERENCES purchase_invoices (id),
    CONSTRAINT fk_fixed_assets_purchase_invoice_line FOREIGN KEY (purchase_invoice_line_id) REFERENCES purchase_invoice_lines (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Едно амортизациско book-ирање по средство по период (месец); UNIQUE го
-- прави повторното извршување за ист период безопасно (се прескокнува).
CREATE TABLE fixed_asset_depreciation_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fixed_asset_id INT NOT NULL,
    period_date DATE NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    journal_entry_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_fad_asset FOREIGN KEY (fixed_asset_id) REFERENCES fixed_assets (id),
    CONSTRAINT fk_fad_journal_entry FOREIGN KEY (journal_entry_id) REFERENCES journal_entries (id),
    UNIQUE KEY uq_fad_asset_period (fixed_asset_id, period_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
