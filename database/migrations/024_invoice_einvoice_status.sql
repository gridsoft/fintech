-- Следење на статусот на е-фактура по излезна фактура. Праќањето кон УЈП е
-- секогаш рачно (копче на веќе издадена фактура) — ова само го памети
-- резултатот, не менува како се книжи фактурата (issue()/journal остануваат
-- непроменети).
ALTER TABLE invoices
    ADD COLUMN einvoice_status ENUM('not_sent', 'sent', 'accepted', 'rejected', 'error') NOT NULL DEFAULT 'not_sent' AFTER status,
    ADD COLUMN einvoice_euid VARCHAR(64) NULL AFTER einvoice_status,
    ADD COLUMN einvoice_error TEXT NULL AFTER einvoice_euid,
    ADD COLUMN einvoice_sent_at DATETIME NULL AFTER einvoice_error;
