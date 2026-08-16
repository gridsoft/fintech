-- Терк = шаблон за книжење (кои сметки, дебит/кредит страна, од кој износ на фактурата).
-- Налог = тип документ на кој фактурата се врзува; секој налог покажува кон еден терк.

CREATE TABLE terkovi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE terk_lines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    terk_id INT NOT NULL,
    account_id INT NOT NULL,
    side ENUM('debit', 'credit') NOT NULL,
    amount_source ENUM('net', 'vat', 'gross') NOT NULL,
    tag_partner TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_terk_lines_terk FOREIGN KEY (terk_id) REFERENCES terkovi (id) ON DELETE CASCADE,
    CONSTRAINT fk_terk_lines_account FOREIGN KEY (account_id) REFERENCES accounts (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE nalozi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    terk_id INT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_nalozi_terk FOREIGN KEY (terk_id) REFERENCES terkovi (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE invoices
    ADD COLUMN nalog_id INT NULL AFTER partner_id,
    ADD CONSTRAINT fk_invoices_nalog FOREIGN KEY (nalog_id) REFERENCES nalozi (id);

-- Стандарден терк за продажба, ист мапинг како постојната хардкодирана логика (2200/6100/4300).
INSERT INTO terkovi (name, description) VALUES
    ('Терк за продажба на стоки/услуги', 'Побарувања (бруто) / Приходи (нето) + ДДВ обврска (ддв)');

INSERT INTO terk_lines (terk_id, account_id, side, amount_source, tag_partner, sort_order) VALUES
    ((SELECT id FROM (SELECT id FROM terkovi WHERE name = 'Терк за продажба на стоки/услуги') t),
     (SELECT id FROM (SELECT id FROM accounts WHERE code = '2200') a), 'debit', 'gross', 1, 1),
    ((SELECT id FROM (SELECT id FROM terkovi WHERE name = 'Терк за продажба на стоки/услуги') t),
     (SELECT id FROM (SELECT id FROM accounts WHERE code = '6100') a), 'credit', 'net', 0, 2),
    ((SELECT id FROM (SELECT id FROM terkovi WHERE name = 'Терк за продажба на стоки/услуги') t),
     (SELECT id FROM (SELECT id FROM accounts WHERE code = '4300') a), 'credit', 'vat', 0, 3);

INSERT INTO nalozi (name, terk_id) VALUES
    ('Излезни фактури', (SELECT id FROM (SELECT id FROM terkovi WHERE name = 'Терк за продажба на стоки/услуги') t));

UPDATE invoices SET nalog_id = (SELECT id FROM (SELECT id FROM nalozi WHERE name = 'Излезни фактури') n)
WHERE nalog_id IS NULL;
