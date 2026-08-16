# Сметководствен софтвер за МК — План со фази

**Стек:** Pure PHP (без framework), Composer PSR-4 autoload, PDO + MySQL/Postgres, чист PHP templating
**Опсег:** За еден клиент, соло развој, без е-фактура интеграција засега
**Цел:** Целосен double-entry сметководствен систем, изграден инкрементално

---

## Фаза 0 — Поставување на проектот

- [x] Иницијализирај git repo
- [x] `composer.json` со PSR-4 autoload (`App\` → `src/`)
- [x] Основна структура на папки:
  ```
  /public          → index.php (единствена влезна точка), assets (css/js)
  /src
    /Core          → Database.php (PDO wrapper), Router.php, Request.php, Response.php
    /Domain
      /Accounting  → Account.php, JournalEntry.php, JournalLine.php
      /Invoicing   → Invoice.php, InvoiceLine.php
      /Partners    → Partner.php
    /Repository    → AccountRepository.php, JournalRepository.php, PartnerRepository.php, InvoiceRepository.php
    /Service       → LedgerService.php, InvoiceService.php, ReportService.php
    /Http
      /Controllers → AccountController.php, JournalController.php, InvoiceController.php, ReportController.php
    /View          → едноставни .php темплејти (layout.php, partials/)
  /database
    /migrations    → нумерирани .sql фајлови (001_create_accounts.sql, ...)
    seed.sql        → почетен контен план
  /config
    config.php      → DB credentials, env поставки
  ```
- [x] `.env` / `config.php` за DB конекција (не се commit-ира `.env`)
- [x] Едноставен migration runner (PHP скрипта што чита `.sql` фајлови по редослед и ги извршува еднаш)
- [x] Router: match на `$_SERVER['REQUEST_URI']` + метод, наспроти табела на рути → controller method
- [x] Основен layout (header/nav/footer) + едноставен CSS (не троши време на дизајн сега)

**Излез од фазата:** `composer install`, стартувана база, `/` рутата враќа "Hello" преку router+controller.

---

## Фаза 1 — Контен план (Chart of Accounts)

- [x] Табела `accounts`:
  - `id`, `code` (нпр. 1000, 2000...), `name`, `type` (asset/liability/equity/revenue/expense), `parent_id` (за хиерархија), `is_active`
- [x] Seed на македонски контен план (класи 0–9, барем основните сметки за почеток: парични средства, побарувања, залихи, основни средства, обврски кон добавувачи, капитал, приходи од продажба, трошоци)
- [x] `AccountRepository`: CRUD
- [x] Едноставен UI: листа на сметки, форма за додавање/уредување

**Излез од фазата:** Можеш да го прегледаш и уредуваш контниот план преку UI.

---

## Фаза 2 — Journal / главна книга (core на системот)

Ова е најважната фаза — сè друго подоцна само генерира записи тука.

- [x] Табели:
  - `journal_entries`: `id`, `date`, `description`, `reference`, `created_at`
  - `journal_lines`: `id`, `journal_entry_id`, `account_id`, `debit`, `credit`, `description`
- [x] `LedgerService::postEntry()`:
  - Прима листа на линии (account + debit/credit)
  - Проверува: сума(debit) === сума(credit), инаку фрла исклучок
  - Сè во една DB транзакција (`beginTransaction` / `commit` / `rollBack`)
  - Ниту еден друг дел од системот не пишува директно во `journal_lines` — сè оди преку овој сервис
- [x] Едноставен UI за рачно внесување journal entry (за тестирање на core-от пред да гради фактурирање)
- [x] Приказ на "картица на сметка" (сите записи за една сметка + running balance)

**Излез од фазата:** Можеш рачно да внесеш трансакции и да видиш дека дебит/кредит секогаш се балансираат.

---

## Фаза 3 — Партнери (купувачи/добавувачи)

- [x] Табела `partners`: `id`, `name`, `type` (customer/supplier/both), `tax_number` (ЕДБ), `address`, `contact`
- [x] CRUD UI
- [x] Врска кон journal entries (за отворени ставки/salda подоцна)

---

## Фаза 4 — Фактурирање

- [x] Табели:
  - `invoices`: `id`, `partner_id`, `number`, `date`, `due_date`, `status`, `total_net`, `total_vat`, `total_gross`
  - `invoice_lines`: `id`, `invoice_id`, `description`, `quantity`, `unit_price`, `vat_rate`, `line_total`
- [x] `InvoiceService::createInvoice()` + `issue()`:
  - Создава фактура + линии (статус `draft`, нема книжење уште)
  - `issue()` автоматски генерира journal entry преку `LedgerService` (Побарувања/Приходи/ДДВ), само тогаш фактурата влегува во главната книга
- [x] UI: листа фактури, форма за нова фактура (со повеќе линии), печатење/PDF преглед (проста HTML→print верзија за почеток, PDF подоцна)
- [x] Статуси: draft / issued / paid / cancelled

**Излез од фазата:** Издадена фактура автоматски се одразува во главната книга.

---

## Фаза 5 — Основни извештаи

- [x] **Trial balance** (бруто биланс) — збир на debit/credit по сметка, проверка дека вкупно се балансира
- [x] **Главна книга по сметка** — веќе делумно готово од Фаза 2, само форматирано како извештај
- [x] **Едноставна ДДВ евиденција** — излезен ДДВ (од фактури) наспроти влезен ДДВ (кога додадеш модул за влезни фактури/трошоци), без автоматска пријава кон УЈП засега
- [x] **Отворени ставки по партнер** (кој колку должи/побарува)

**Излез од фазата:** Имаш работоспособен систем за еден клиент — внес, фактурирање, контрола преку извештаи.

---

## Фаза 6 (подоцна, не сега)

Само за референца, не се работи веднаш:
- Влезни фактури / трошоци модул
- Плаќања и порамнување (payment matching)
- Биланс на успех / биланс на состојба (P&L, Balance Sheet)
- Плати
- Е-фактура интеграција со УЈП (UBL 2.1, потпис, API) — стана задолжителна од 1.10.2026, но одложено намерно за подоцна
- Multi-tenant / multi-client (кога ќе се проширува производот)

---

## Забелешки за работа со Claude Code

- Секоја фаза = засебна серија commits; не преминувај на следна фаза пред UI+DB на тековната да работи и рачно тестирана
- Барај од Claude Code да пишува migration + repository + service + controller заедно за секој фичер, во тој редослед
- Инсистирај дебит/кредит балансирањето да е тестирано (едноставен PHPUnit тест на `LedgerService` е вреден веднаш штом Фаза 2 е готова)
