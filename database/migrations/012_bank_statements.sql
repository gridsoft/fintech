-- Банка / изводи + порамнување (Фаза 7). Без ова, AR/AP контата никогаш
-- реално не се затвораат — статусот "платена" досега беше само етикета,
-- без книжење. Секоја трансакција од извод се матчира со ТОЧНО една
-- излезна или влезна фактура (или останува неповрзана); преостанатото
-- салдо на фактурата се пресметува од веќе матчираните трансакции, не се
-- чува посебно поле — делумно плаќање едноставно значи фактурата останува
-- отворена со намалено салдо, статус "платена" само кога салдото стигне 0.

CREATE TABLE bank_statements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    statement_date DATE NOT NULL,
    reference VARCHAR(50) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bank_statements_account FOREIGN KEY (account_id) REFERENCES accounts (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- invoice_id е намерно без FK — полиморфно упатува на invoices ИЛИ
-- purchase_invoices во зависност од invoice_type, интегритетот се чува во
-- PaymentMatchingService, не во базата.
CREATE TABLE bank_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bank_statement_id INT NOT NULL,
    transaction_date DATE NOT NULL,
    description VARCHAR(255) NULL,
    amount DECIMAL(15,2) NOT NULL,
    direction ENUM('in', 'out') NOT NULL,
    partner_id INT NULL,
    matched_status ENUM('unmatched', 'matched') NOT NULL DEFAULT 'unmatched',
    invoice_type ENUM('sales', 'purchase') NULL,
    invoice_id INT NULL,
    journal_entry_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bank_transactions_statement FOREIGN KEY (bank_statement_id) REFERENCES bank_statements (id) ON DELETE CASCADE,
    CONSTRAINT fk_bank_transactions_partner FOREIGN KEY (partner_id) REFERENCES partners (id),
    CONSTRAINT fk_bank_transactions_journal_entry FOREIGN KEY (journal_entry_id) REFERENCES journal_entries (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_bank_transactions_matched_status ON bank_transactions (matched_status);
CREATE INDEX idx_bank_transactions_invoice ON bank_transactions (invoice_type, invoice_id);
