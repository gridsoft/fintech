-- Боледување/сменска работа/празнична работа се МЕСЕЧНИ варијабли (различен
-- број денови секој период), не постојани својства на вработениот — затоа
-- се внесуваат по вработен на секое извршување (види /payroll/prepare), не
-- се чуваат тука. Тука само стапките/делителот (уредливи, статусно-слични
-- на другите стапки во оваа табела).

ALTER TABLE payroll_settings
    ADD COLUMN sick_leave_pay_rate DECIMAL(5,2) NOT NULL DEFAULT 70.00 AFTER seniority_rate_per_year,
    ADD COLUMN shift_day_rate DECIMAL(5,2) NOT NULL DEFAULT 5.00 AFTER sick_leave_pay_rate,
    ADD COLUMN holiday_day_rate DECIMAL(5,2) NOT NULL DEFAULT 50.00 AFTER shift_day_rate,
    ADD COLUMN daily_rate_divisor SMALLINT NOT NULL DEFAULT 21 AFTER holiday_day_rate;

UPDATE payroll_settings SET sick_leave_pay_rate = 70.00, shift_day_rate = 5.00, holiday_day_rate = 50.00, daily_rate_divisor = 21;
