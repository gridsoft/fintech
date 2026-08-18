-- Превалоризација на нереализирани курсни разлики (крај на период): за
-- отворените странски-валутни фактури, ја преценува MKD книговодствената
-- вредност на преостанатото салдо по нов курс, без да ја менува самата
-- фактура (таа си останува во својот оригинален курс/валута за документот).
-- Секоја наредна превалоризација го смета разликот од ПОСЛЕДНАТА
-- превалоризирана вредност (fx_revaluation_lines.mkd_value_after), не од
-- оригиналниот курс на фактурата — за да не се дуплира ефектот при
-- повеќекратна превалоризација без рачно сторно на претходната.

CREATE TABLE fx_revaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    revaluation_date DATE NOT NULL,
    currency_id INT NOT NULL,
    new_rate DECIMAL(10,6) NOT NULL,
    journal_entry_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_fx_revaluations_currency FOREIGN KEY (currency_id) REFERENCES currencies (id),
    CONSTRAINT fk_fx_revaluations_journal_entry FOREIGN KEY (journal_entry_id) REFERENCES journal_entries (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- invoice_id намерно без FK — полиморфно упатува на invoices ИЛИ
-- purchase_invoices во зависност од invoice_type, исто како bank_transactions.
CREATE TABLE fx_revaluation_lines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fx_revaluation_id INT NOT NULL,
    invoice_type ENUM('sales', 'purchase') NOT NULL,
    invoice_id INT NOT NULL,
    mkd_value_before DECIMAL(15,2) NOT NULL,
    mkd_value_after DECIMAL(15,2) NOT NULL,
    difference DECIMAL(15,2) NOT NULL,
    CONSTRAINT fk_fx_revaluation_lines_revaluation FOREIGN KEY (fx_revaluation_id) REFERENCES fx_revaluations (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_fx_revaluation_lines_invoice ON fx_revaluation_lines (invoice_type, invoice_id);
