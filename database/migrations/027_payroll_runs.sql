CREATE TABLE payroll_runs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    period_date DATE NOT NULL,
    journal_entry_id INT NOT NULL,
    total_gross DECIMAL(15,2) NOT NULL,
    total_net DECIMAL(15,2) NOT NULL,
    total_pit DECIMAL(15,2) NOT NULL,
    total_pension DECIMAL(15,2) NOT NULL,
    total_health DECIMAL(15,2) NOT NULL,
    total_employment DECIMAL(15,2) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payroll_runs_journal_entry FOREIGN KEY (journal_entry_id) REFERENCES journal_entries (id),
    UNIQUE KEY uq_payroll_runs_period (period_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE payslips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payroll_run_id INT NOT NULL,
    employee_id INT NOT NULL,
    gross_salary DECIMAL(15,2) NOT NULL,
    pension_contribution DECIMAL(15,2) NOT NULL,
    health_contribution DECIMAL(15,2) NOT NULL,
    employment_contribution DECIMAL(15,2) NOT NULL,
    taxable_base DECIMAL(15,2) NOT NULL,
    pit DECIMAL(15,2) NOT NULL,
    net_salary DECIMAL(15,2) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payslips_run FOREIGN KEY (payroll_run_id) REFERENCES payroll_runs (id),
    CONSTRAINT fk_payslips_employee FOREIGN KEY (employee_id) REFERENCES employees (id),
    UNIQUE KEY uq_payslips_run_employee (payroll_run_id, employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
