CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partner_id INT NOT NULL,
    number VARCHAR(30) NOT NULL,
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('draft', 'issued', 'paid', 'cancelled') NOT NULL DEFAULT 'draft',
    total_net DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_vat DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_gross DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    journal_entry_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_invoices_number (number),
    CONSTRAINT fk_invoices_partner FOREIGN KEY (partner_id) REFERENCES partners (id),
    CONSTRAINT fk_invoices_journal_entry FOREIGN KEY (journal_entry_id) REFERENCES journal_entries (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE invoice_lines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1.00,
    unit_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    vat_rate DECIMAL(5,2) NOT NULL DEFAULT 18.00,
    line_total DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT fk_invoice_lines_invoice FOREIGN KEY (invoice_id) REFERENCES invoices (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_invoices_partner ON invoices (partner_id);
CREATE INDEX idx_invoices_status ON invoices (status);
