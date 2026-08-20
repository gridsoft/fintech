-- Плоска табела со тековни вредности (без историја/effective-dating), исто
-- како vat_rates — секое книжење ги замрзнува своите износи во journal_lines
-- во моментот на извршување (payroll_runs/payslips), па подоцнежна промена
-- тука никогаш не влијае на веќе извршени периоди. Стапките и личното
-- ослобување се законски категории што периодично се менуваат — корисникот
-- мора рачно да ги ажурира тука кога ќе се променат.

CREATE TABLE payroll_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pension_rate DECIMAL(5,2) NOT NULL,
    health_rate DECIMAL(5,2) NOT NULL,
    employment_rate DECIMAL(5,2) NOT NULL,
    pit_rate DECIMAL(5,2) NOT NULL,
    personal_exemption DECIMAL(15,2) NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO payroll_settings (pension_rate, health_rate, employment_rate, pit_rate, personal_exemption)
VALUES (18.40, 7.50, 1.20, 10.00, 11463.00);
