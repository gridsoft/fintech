-- Терк/налог механизмот (рачен избор на книжење при издавање фактура) е
-- заменет со автоматска резолуција сметка/ДДВ преку категории на
-- производи/услуги (види POSTING_RULES_ADDENDUM.md).

ALTER TABLE invoices
    DROP FOREIGN KEY fk_invoices_nalog,
    DROP FOREIGN KEY fk_invoices_terk,
    DROP COLUMN nalog_id,
    DROP COLUMN terk_id;

DROP TABLE IF EXISTS terk_lines;
DROP TABLE IF EXISTS nalozi;
DROP TABLE IF EXISTS terkovi;
