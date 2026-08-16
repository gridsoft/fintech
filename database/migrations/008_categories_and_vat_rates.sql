-- Автоматска резолуција сметка/ДДВ преку категории на производи/услуги.
-- Види POSTING_RULES_ADDENDUM.md. Партнерот добива "земја" за да се
-- определи контекстот (домашен/странски) при резолуција.

ALTER TABLE partners
    ADD COLUMN country VARCHAR(2) NOT NULL DEFAULT 'MK' AFTER contact;

CREATE TABLE vat_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    rate DECIMAL(5,2) NOT NULL,
    type ENUM('standard', 'reduced', 'zero', 'exempt_with_credit', 'exempt_no_credit', 'out_of_scope', 'reverse_charge') NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE product_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    domestic_account_id INT NOT NULL,
    domestic_vat_rate_id INT NOT NULL,
    foreign_account_id INT NULL,
    foreign_vat_rate_id INT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_product_categories_domestic_account FOREIGN KEY (domestic_account_id) REFERENCES accounts (id),
    CONSTRAINT fk_product_categories_domestic_vat FOREIGN KEY (domestic_vat_rate_id) REFERENCES vat_rates (id),
    CONSTRAINT fk_product_categories_foreign_account FOREIGN KEY (foreign_account_id) REFERENCES accounts (id),
    CONSTRAINT fk_product_categories_foreign_vat FOREIGN KEY (foreign_vat_rate_id) REFERENCES vat_rates (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    category_id INT NOT NULL,
    price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES product_categories (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE service_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    domestic_account_id INT NOT NULL,
    domestic_vat_rate_id INT NOT NULL,
    foreign_account_id INT NULL,
    foreign_vat_rate_id INT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_service_categories_domestic_account FOREIGN KEY (domestic_account_id) REFERENCES accounts (id),
    CONSTRAINT fk_service_categories_domestic_vat FOREIGN KEY (domestic_vat_rate_id) REFERENCES vat_rates (id),
    CONSTRAINT fk_service_categories_foreign_account FOREIGN KEY (foreign_account_id) REFERENCES accounts (id),
    CONSTRAINT fk_service_categories_foreign_vat FOREIGN KEY (foreign_vat_rate_id) REFERENCES vat_rates (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    category_id INT NOT NULL,
    price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_services_category FOREIGN KEY (category_id) REFERENCES service_categories (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Страна на набавки (влезни фактури) — сè уште нема UI/flow (идна фаза),
-- но табелата се создава сега за да ја следиме постапноста од addendum-от.
CREATE TABLE expense_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    domestic_account_id INT NOT NULL,
    foreign_account_id INT NULL,
    vat_deductible ENUM('full', 'none', 'partial') NOT NULL DEFAULT 'full',
    is_capitalizable TINYINT(1) NOT NULL DEFAULT 0,
    reverse_charge_applicable TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_expense_categories_domestic_account FOREIGN KEY (domestic_account_id) REFERENCES accounts (id),
    CONSTRAINT fk_expense_categories_foreign_account FOREIGN KEY (foreign_account_id) REFERENCES accounts (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- invoice_lines: линијата сега упатува на производ ИЛИ услуга (никогаш
-- двете), а сметката/ДДВ стапката се резолвираат и се "замрзнуваат" на
-- линијата во моментот на креирање на фактурата (за стабилна ревизорска
-- трага, дури и ако категоријата подоцна се промени).
ALTER TABLE invoice_lines
    ADD COLUMN product_id INT NULL AFTER invoice_id,
    ADD COLUMN service_id INT NULL AFTER product_id,
    ADD COLUMN account_id INT NULL AFTER vat_rate,
    ADD COLUMN vat_rate_id INT NULL AFTER account_id,
    MODIFY COLUMN description VARCHAR(255) NULL,
    ADD CONSTRAINT fk_invoice_lines_product FOREIGN KEY (product_id) REFERENCES products (id),
    ADD CONSTRAINT fk_invoice_lines_service FOREIGN KEY (service_id) REFERENCES services (id),
    ADD CONSTRAINT fk_invoice_lines_account FOREIGN KEY (account_id) REFERENCES accounts (id),
    ADD CONSTRAINT fk_invoice_lines_vat_rate FOREIGN KEY (vat_rate_id) REFERENCES vat_rates (id);
