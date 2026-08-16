-- Налог и терк се целосно засебни концепти. Теркот повеќе не е фиксиран
-- на налог, туку корисникот го избира при самото издавање на фактурата.

ALTER TABLE invoices
    ADD COLUMN terk_id INT NULL AFTER nalog_id,
    ADD CONSTRAINT fk_invoices_terk FOREIGN KEY (terk_id) REFERENCES terkovi (id);

-- Backfill: веќе издадените фактури го користеа теркот на нивниот (тогашен) налог.
UPDATE invoices i
    JOIN nalozi n ON n.id = i.nalog_id
    SET i.terk_id = n.terk_id
    WHERE i.status IN ('issued', 'paid') AND i.terk_id IS NULL;

ALTER TABLE nalozi
    DROP FOREIGN KEY fk_nalozi_terk,
    DROP COLUMN terk_id;
