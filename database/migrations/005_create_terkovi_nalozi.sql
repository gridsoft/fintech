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

-- Забелешка: оваа migration некогаш сееше стандарден терк/налог тука со
-- INSERT-и што упатуваа на аналитички сметки (2200/6100/4300) создадени
-- дури во подоцнежна migration (011) — на чисто нова база тоа фрлаше
-- грешка (сметките сеуште не постојат на овој чекор). Тие INSERT-и се
-- отстранети: терк/налог механизмот е сепак целосно демонтиран 2 чекори
-- подоцна (007_drop_terk_nalog.sql), па привремено семе податоци тука
-- никогаш не преживуваше до нешто што реално ги користи.
