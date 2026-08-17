-- Проширување на партнери со структурирани полиња (адреса, контакт,
-- банкарски детали), Вработени под-листа, и мал optional key/value сет
-- само за навистина непредвидливи полиња. НЕ е избран целосен EAV за
-- стандардните полиња — образложението е во DECISIONS.md.

ALTER TABLE partners
    ADD COLUMN address_line1 VARCHAR(150) NULL AFTER tax_number,
    ADD COLUMN address_line2 VARCHAR(150) NULL AFTER address_line1,
    ADD COLUMN postal_code VARCHAR(20) NULL AFTER address_line2,
    ADD COLUMN city VARCHAR(100) NULL AFTER postal_code,
    ADD COLUMN phone VARCHAR(50) NULL AFTER city,
    ADD COLUMN fax VARCHAR(50) NULL AFTER phone,
    ADD COLUMN mobile VARCHAR(50) NULL AFTER fax,
    ADD COLUMN email VARCHAR(150) NULL AFTER mobile,
    ADD COLUMN website VARCHAR(150) NULL AFTER email,
    ADD COLUMN bank_account VARCHAR(50) NULL AFTER website,
    ADD COLUMN vat_number VARCHAR(30) NULL AFTER bank_account,
    ADD COLUMN iban VARCHAR(50) NULL AFTER vat_number,
    ADD COLUMN swift VARCHAR(20) NULL AFTER iban,
    ADD COLUMN timocom_id VARCHAR(50) NULL AFTER swift;

-- Best-effort пренос на постоечките слободни полиња пред да се отстранат
-- (dummy_data.php ги користеше како едно поле „адреса" и „контакт" = email).
UPDATE partners SET address_line1 = address WHERE address IS NOT NULL AND address_line1 IS NULL;
UPDATE partners SET email = contact WHERE contact IS NOT NULL AND contact LIKE '%@%' AND email IS NULL;
UPDATE partners SET phone = contact WHERE contact IS NOT NULL AND contact NOT LIKE '%@%' AND phone IS NULL;

ALTER TABLE partners
    DROP COLUMN address,
    DROP COLUMN contact;

CREATE TABLE partner_employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partner_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    job_title VARCHAR(100) NULL,
    phone VARCHAR(50) NULL,
    email VARCHAR(150) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_partner_employees_partner FOREIGN KEY (partner_id) REFERENCES partners (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Мал optional key/value само за полиња што не влегуваат во стандардниот
-- сет погоре и не можеме да ги предвидиме однапред — не за реплицирање
-- на веќе познатите полиња.
CREATE TABLE partner_custom_fields (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partner_id INT NOT NULL,
    field_key VARCHAR(100) NOT NULL,
    field_value VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_partner_custom_fields (partner_id, field_key),
    CONSTRAINT fk_partner_custom_fields_partner FOREIGN KEY (partner_id) REFERENCES partners (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
