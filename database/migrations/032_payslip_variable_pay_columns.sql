-- gross_salary отсега значи: основна + додаток за стаж - одбиток за
-- боледување + додаток за смени + додаток за празници. Бројот денови и
-- дневната стапка се чуваат замрзнати по платна листа (внесени еднократно
-- на подготвителниот чекор пред извршување) за транспарентност/ревизија.

ALTER TABLE payslips
    ADD COLUMN daily_rate DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER seniority_supplement,
    ADD COLUMN sick_days INT NOT NULL DEFAULT 0 AFTER daily_rate,
    ADD COLUMN sick_deduction DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER sick_days,
    ADD COLUMN shift_days INT NOT NULL DEFAULT 0 AFTER sick_deduction,
    ADD COLUMN shift_supplement DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER shift_days,
    ADD COLUMN holiday_days INT NOT NULL DEFAULT 0 AFTER shift_supplement,
    ADD COLUMN holiday_supplement DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER holiday_days;

ALTER TABLE payroll_runs
    ADD COLUMN total_sick_deduction DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER total_seniority_supplement,
    ADD COLUMN total_shift_supplement DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER total_sick_deduction,
    ADD COLUMN total_holiday_supplement DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER total_shift_supplement;
