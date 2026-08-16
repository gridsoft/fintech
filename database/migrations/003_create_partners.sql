CREATE TABLE partners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    type ENUM('customer', 'supplier', 'both') NOT NULL DEFAULT 'customer',
    tax_number VARCHAR(20) NULL,
    address VARCHAR(255) NULL,
    contact VARCHAR(150) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Врска кон journal entries, за да може подоцна (Фаза 5) да се сметаат отворени ставки/салда по партнер.
ALTER TABLE journal_lines
    ADD COLUMN partner_id INT NULL AFTER account_id,
    ADD CONSTRAINT fk_journal_lines_partner FOREIGN KEY (partner_id) REFERENCES partners (id) ON DELETE SET NULL;

CREATE INDEX idx_journal_lines_partner ON journal_lines (partner_id);
