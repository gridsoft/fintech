# Сметководствен софтвер за МК — План со фази

**Стек:** Pure PHP (без framework), Composer PSR-4 autoload, PDO + MySQL/Postgres, чист PHP templating, HTML+jQuery+Bootstrap фронтенд
**Опсег:** За еден клиент (услужна дејност — превоз/сервис на опрема), соло развој, без е-фактура интеграција засега
**Цел:** Целосен double-entry сметководствен систем, изграден инкрементално

> Комбинирано: Фази 0–5 се веќе имплементирани (задржани точно како се направени). Фази 6–9 се нови — додадени бидејќи се универзално јадро (не опционални екстри) и релевантни од ден 1 за клиент со возен парк/опрема.

---

## Фаза 0 — Поставување на проектот ✅ Завршено

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
- [x] Основен layout (header/nav/footer) + едноставен CSS

**Излез од фазата:** `composer install`, стартувана база, `/` рутата враќа "Hello" преку router+controller.

---

## Фаза 1 — Контен план (Chart of Accounts) ✅ Завршено

- [x] Табела `accounts`: `id`, `code`, `name`, `type` (asset/liability/equity/revenue/expense), `parent_id`, `is_active`
- [x] Seed на македонски контен план
- [x] `AccountRepository`: CRUD
- [x] Едноставен UI: листа на сметки, форма за додавање/уредување

**Излез од фазата:** Можеш да го прегледаш и уредуваш контниот план преку UI.

---

## Фаза 2 — Journal / главна книга (core на системот) ✅ Завршено

- [x] Табели: `journal_entries`, `journal_lines`
- [x] `LedgerService::postEntry()` — проверува debit===credit, сè во DB транзакција, единствена точка на пишување во `journal_lines`
- [x] Едноставен UI за рачно внесување journal entry
- [x] Приказ на "картица на сметка" (running balance)

**Излез од фазата:** Можеш рачно да внесеш трансакции и да видиш дека дебит/кредит секогаш се балансираат.

---

## Фаза 3 — Партнери (купувачи/добавувачи) ✅ Завршено

- [x] Табела `partners`: `id`, `name`, `type` (customer/supplier/both), `tax_number` (ЕДБ), `address`, `contact`
- [x] CRUD UI
- [x] Врска кон journal entries

---

## Фаза 4 — Фактурирање ✅ Завршено

- [x] Табели: `invoices`, `invoice_lines` (со `vat_rate` како слободно поле, без категорија/конто-мапирање уште)
- [x] `InvoiceService::createInvoice()` + `issue()` — draft статус без книжење, `issue()` генерира journal entry
- [x] UI: листа фактури, форма за нова фактура, печатење/PDF преглед
- [x] Статуси: draft / issued / paid / cancelled

**Излез од фазата:** Издадена фактура автоматски се одразува во главната книга.

> **Забелешка:** тековната имплементација уште нема категории/account-mapping слој (секоја линија оди на исто "генеричко" приходно конто). Тоа се доградува во Фаза 6 подолу, заедно со влезните фактури — истата логика важи за двете страни, па се работи заедно.

---

## Фаза 5 — Основни извештаи ✅ Завршено

- [x] Trial balance (бруто биланс)
- [x] Главна книга по сметка
- [x] Едноставна ДДВ евиденција (само излезен ДДВ засега — влезен доаѓа со Фаза 6)
- [x] Отворени ставки по партнер

**Излез од фазата:** Имаш работоспособен систем за еден клиент — внес, фактурирање, контрола преку извештаи.

---

## Фаза 6 — Категории + Account-mapping слој + Влезни фактури/трошоци ✅ Завршено

И двата дела веќе се имплементирани (пред оваа ревизија на планот) — детали во `POSTING_RULES_ADDENDUM.md`. Имиња на табели/класи отстапуваат од скицата подолу, функцијата е иста:

**6а — Категории и account-mapping (продажна страна)**
- [x] Табела `service_categories` (+ `product_categories`): `id`, `name`, `domestic_account_id`, `foreign_account_id`, `domestic_vat_rate_id`, `foreign_vat_rate_id`
- [x] Табела `vat_rates`: `id`, `rate`, `type`, `name`, `payable_account_id`
- [x] `invoice_lines` носи `account_id`/`vat_rate_id` (замрзнати при креирање), поврзано преку `product_id`/`service_id` → категорија, не директно `category_id`
- [x] `InvoiceService::createInvoice()` + `issue()` — групирање по резолвирано конто+ДДВ стапка (контекст domestic/foreign)

**6б — Влезни фактури/трошоци**
- [x] Табела `expense_categories`: `id`, `name`, `domestic_account_id`, `foreign_account_id`, `vat_deductible` (full/none/**partial сè уште не поддржано**), `is_capitalizable`, `reverse_charge_applicable`
- [x] Табели: `purchase_invoices` (не `expenses`), `purchase_invoice_lines` (не `expense_lines`)
- [x] `PurchaseInvoiceService::createPurchaseInvoice()` + `post()` (не `ExpenseService::createExpense()`) — групирање по конто, ДДВ стапка се внесува рачно по линија (не се резолвира од категорија — образложение во `DECISIONS.md`)
- [x] `is_capitalizable` / `reverse_charge_applicable` / `vat_deductible = 'partial'` → **блокирани со исклучок** до Фаза 8 / идни чекори, не тивко погрешно книжење
- [x] UI: листа влезни фактури, категории на трошоци, форма со категорија по линија

**Излез од фазата:** ✅ Секоја фактура (влезна и излезна) автоматски книжи на точно конто според категорија+контекст, никогаш рачен избор. Секој трошок се внесува формално.

---

## Фаза 7 — Банка / изводи + порамнување ✅ Завршено

- [x] Табели: `bank_statements`, `bank_transactions` (`amount`, `direction`, `matched_status` — само `unmatched`/`matched`, нема посебна `partial`: делумно платена фактура е претставена преку намалено пресметано салдо, не преку статус на трансакцијата)
- [x] Внес: рачен внес за почеток (CSV импорт останува идна доработка)
- [x] `PaymentMatchingService` — порамнување со отворена фактура (излезна `matchToSalesInvoice()` или влезна `matchToPurchaseInvoice()`), journal entry Парични средства ↔ AR/AP (партнер-таговиран), статус фактура → `paid` само кога пресметаното салдо стигне точно 0, поддршка за делумно плаќање (фактурата останува отворена со намалено салдо), пречекорување на преостанатото салдо е одбиено со грешка
- [x] UI: `/bank-statements` листа + форма за нов извод, преглед на извод со инлајн форма за нова трансакција, поврзување со фактура преку модал на истата страница (не посебна страница) — пикер на отворени фактури **само од партнерот избран на трансакцијата** (и филтрирани по насока: `in` → излезни, `out` → влезни), со видливо преостанато салдо по фактура

**Излез од фазата:** ✅ Верификувано преку 6 PHPUnit теста + рачен HTTP тек (создај извод → додади трансакција → матчирај делумно → матчирај остаток → фактурата станува `paid`, книжењето секогаш балансирано, отворените ставки извештај се точен).

---

## Фаза 8 — Основни средства (основно ниво, НОВО)

За клиент со возен парк/опрема, возила и алат се основни средства од ден 1.

- [ ] Табела `fixed_assets`: `id`, `name`, `account_id`, `purchase_date`, `purchase_value`, `useful_life_months`, `status`
- [ ] Влезна фактура (Фаза 6б) за `is_capitalizable` категорија → создава запис во `fixed_assets`, книжи на конто за основни средства
- [ ] Основна месечна амортизација (прав линиски метод), journal entry Debit Трошок за амортизација / Credit Акумулирана амортизација
- [ ] UI: листа средства, амортизациски план по средство

**Излез од фазата:** Камион/алат купен преку влезна фактура автоматски станува основно средство со амортизација.

---

## Фаза 9 (подоцна, не сега)

Само за референца, не се работи веднаш:
- Аванси (примени/дадени) и кредитни известија — посебни документ-типови
- Валута/курсни разлики — ако клиентот фактурира во EUR
- Плата — рачен journal entry за нето плата + придонеси е доволен (веќе овозможено од Фаза 2), целосен payroll модул е одделен проект
- Целосно материјално работење (залиха на резервни делови) — само ако клиентот реално чува залиха
- Целосна ревалоризација на амортизација, РЕВ-3 образец — доразработка на Фаза 8
- Е-фактура интеграција со УЈП (UBL 2.1, потпис, API) — задолжителна од 1.10.2026, намерно одложено
- Multi-tenant / multi-client (кога ќе се проширува производот)

---

## Забелешки за работа со Claude Code

- Секоја фаза = засебна серија commits; не преминувај на следна фаза пред UI+DB на тековната да работи и рачно тестирана
- Барај од Claude Code да пишува migration + repository + service + controller заедно за секој фичер, во тој редослед
- Дебит/кредит балансирањето веќе е (или треба да е) тестирано со PHPUnit тест на `LedgerService` — види `TESTING.md`
- Види `POSTING_RULES_ADDENDUM.md` за деталниот дизајн на account-mapping логиката (категорија+контекст → конто), релевантно за Фаза 6
