-- gross_salary отсега значи ВКУПНО бруто (основна + додаток за стаж) —
-- истото поле што и досега се користеше како основа за придонеси/данок.
-- base_salary/seniority_months/seniority_supplement се чуваат посебно за
-- транспарентност на платната листа (распад на бруто износот).

ALTER TABLE payslips
    ADD COLUMN base_salary DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER employee_id,
    ADD COLUMN seniority_months INT NOT NULL DEFAULT 0 AFTER base_salary,
    ADD COLUMN seniority_supplement DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER seniority_months;

ALTER TABLE payroll_runs
    ADD COLUMN total_seniority_supplement DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER total_gross;
