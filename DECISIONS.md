# Decisions

Why we chose X over Y, so we don't relitigate it. One short entry per settled
decision: what was decided, why, and what would make us revisit it. Newest on top.

---

### Currency/FX: documents only, no multi-currency bank accounts; reuse existing 7750/4750 accounts   (2026-08-18)
Foreign-currency support is scoped to sales/purchase invoices only — bank
statements and accounts stay MKD-only. A foreign invoice's AR/AP is booked in
MKD at the invoice-date rate; settlement is a plain MKD bank amount, and the
gap between that and the booked MKD value is the realized FX difference,
posted only when the user explicitly opts in (`closeWithFxDifference`) since
an exact match essentially never happens with real rates. This avoids ever
needing a foreign-currency-denominated GL account. The gain/loss accounts are
not new — `7750`/`4750` already exist in this client's imported chart of
accounts (`018_client_509_analytic_accounts.sql`) for exactly this purpose,
found before creating anything new.
**Revisit when:** the client starts holding an actual EUR bank account (not
just invoicing in EUR) — that reopens the "multi-currency ledger accounts"
question this decision deliberately avoided.

### FX revaluation diffs from the last revaluation, not the invoice's original rate   (2026-08-18)
Period-end unrealized revaluation (`FxRevaluationService`) doesn't touch the
invoice or re-derive from its original exchange rate on every run — each
invoice's true remaining foreign-currency amount is invariant (computed once
from the original rate + real settlement history), but the MKD value it's
compared against for the *next* revaluation's delta is whatever the *last*
revaluation booked (`fx_revaluation_lines.mkd_value_after`), tracked in a
small ledger table. Comparing against the original rate on every run would
double-count prior adjustments unless each revaluation were reversed at the
start of the next period — which this project doesn't have infrastructure
for (no auto-reversing entries). Revaluation entries adjust the GL account
only; they never touch `invoices`/`purchase_invoices` rows, so per-invoice
outstanding-balance tracking for actual settlement stays untouched.
**Revisit when:** auto-reversing journal entries become a real feature — at
that point the simpler "always diff from original rate, reverse each period"
model becomes viable and this tracking table could be retired.

### Partner fields: fixed columns, not EAV/meta-key-value   (2026-08-17)
`partners` grew real typed columns for address (split into line1/line2/postal
code/city), contact (phone/fax/mobile/email/website), and banking (bank
account/VAT number/IBAN/SWIFT/TimoCom ID) — not a generic meta-key/value
table, even though that was the initial ask. Every field in the reference
requirement was a known, universal field for this domain, not something that
varies unpredictably per partner; modeling known fields as EAV loses type
safety, FK constraints, and plain SQL joins for reporting, for no real
benefit. A narrow `partner_custom_fields` (key/value) table was added
alongside it, but strictly as a fallback for genuinely unpredictable extras
that don't fit the standard set — not for replicating fields that are already
columns. `partner_employees` was added as a proper one-to-many table (name,
job title, phone, email per partner), since that's a real repeating relation,
not a meta-field case.
**Revisit when:** a specific field repeatedly needs modeling that can't be
enumerated in advance across many partners — that's the signal to grow the
meta table's role, not to retrofit EAV over the fixed columns.

### jQuery + DataTables (CDN) for list-table sort/filter/pagination   (2026-08-17)
Every browse-style table (accounts, partners, invoices, categories, journal,
reports, ...) now gets client-side column sorting, a per-column filter row,
and pagination for free via `table.data-table` + a small generic initializer
(`public/assets/js/data-table.js`). DataTables was picked over hand-rolling
this because it's the standard, battle-tested jQuery plugin for exactly this
— progressively enhances a plain server-rendered `<table>`, zero backend
changes, zero build step (CDN `<script>`/`<link>` tags, same pattern as
Bootstrap). jQuery itself was already the named stack choice in `CLAUDE.md`
but hadn't actually been loaded until now. Columns where sort/filter would be
meaningless or misleading (action menus, running-balance columns whose values
only make sense in chronological order) are opted out via a `data-no-filter`
attribute on the `<th>`, read generically by the initializer — no per-view JS.
**Revisit when:** a table needs server-side pagination because client-side
sorting/filtering over the full result set becomes too slow (thousands of
rows) — DataTables supports an AJAX server-side mode for that, not wired up now.

## Stack defaults (this project)

### Pure PHP, no framework
No Laravel/Symfony/Slim. At this scale (single client, solo dev) a framework adds
more ceremony than it saves — routing, DB access, and templating are all simple
enough to hand-roll and keep fully understandable.
**Revisit when:** the app grows enough moving parts (auth, multiple modules,
many routes) that hand-rolled routing/DI becomes the bottleneck, not a
convenience.

### Plain HTML + jQuery + Bootstrap, no frontend build step
No Angular/React/Vue, no Node build pipeline for the frontend. jQuery/Bootstrap
via CDN or vendored static files. Matches existing skillset, zero build
tooling to maintain for a server-rendered PHP app.
**Revisit when:** the UI needs real client-side state/interactivity that jQuery
DOM-patching makes painful — not before.

### Composer for PHP dependencies (PSR-4 autoload only)
Standard PHP tooling. Kept deliberately minimal — autoload + a couple of dev
tools (php-cs-fixer, PHPUnit), not a growing dependency tree.
**Revisit when:** never, unless the ecosystem forces a change.

### PDO + prepared statements, no ORM
Double-entry accounting logic needs full control over transactions
(`beginTransaction`/`commit`/`rollBack`) — an ORM's abstraction gets in the way
more than it helps here, and the query patterns are simple enough not to need
one.
**Revisit when:** the schema grows complex enough that hand-written SQL becomes
the bulk of change effort rather than the business logic.

### Native git hooks (`.githooks/`), not husky/lint-staged
No Node-based frontend project exists to hang husky off of. Plain shell scripts
via `git config core.hooksPath .githooks` give the same enforcement
(gitleaks, php-cs-fixer, PHPUnit) with zero extra runtime dependency.
**Revisit when:** a Node toolchain becomes genuinely necessary for another
reason (unlikely for this stack).

### Local git hooks as the quality gate; no CI in the template
Hooks (gitleaks, php-cs-fixer, commit-msg check, PHPUnit on push) give every
clone enforcement with zero infrastructure.
**Revisit when:** the project gets a repo host with CI — then mirror the gates
in the pipeline and record it here.

### Smoke tests at bootstrap, tests grow with the code
A fresh scaffold gets a wiring-level smoke test only (router responds); real
specs follow the logic as it's written — starting with `LedgerService`'s
debit==credit invariant, since that's the part that would embarrass us if it
broke silently. See `TESTING.md`.
**Revisit when:** a bug class recurs, or once real money starts flowing
through the system for the first client.

### Deploy: GitHub Actions over SSH, pull-in-place on cPanel   (2026-08-18)
Push to `master` triggers a GitHub Actions workflow (`.github/workflows/deploy.yml`)
that SSHes into the cPanel box and runs `git reset --hard origin/master` +
`composer install` + `php database/migrate.php` in a clone that lives
*outside* `public_html`, with the domain's document root pointed at that
clone's `public/` subfolder. Chosen over cPanel's built-in Git Version
Control (pull-based, would need a manual "Update from Remote" click or a
webhook cPanel doesn't natively expose) and over FTP-only deploy actions
(would require committing `vendor/` to the repo, since `composer install`
can't run without shell access — SSH was confirmed available, so there was
no reason to accept that tradeoff). Runbook: `DEPLOY.md`. Claude Code itself
is denied direct `ssh`/`scp`/`sftp` in `.claude/settings.json` — deploys only
happen through this one reviewed, auditable workflow, not ad-hoc shell
access to production.
**Revisit when:** downtime during the in-place `git reset --hard` window
actually matters (→ move to a releases-directory + symlink-swap pattern), or
a staging environment is added (→ this workflow becomes the template for a
second one gated on a different branch/environment).

---

## Project decisions (append here)

<!--
### <Decision title>            (YYYY-MM-DD)
<What was decided and the one-line why.>
**Revisit when:** <the trigger that would reopen this.>
-->

### Уредување на веќе издадена/заведена фактура: дозволено, но задолжително прекнижува — и само ако сè уште ништо не е поврзано со неа   (2026-08-19)
Претходната одлука (истиот ден, погоре во овој фајл) ја рестриктираше
едит-функцијата само на `draft`. Клиентот побара издадени/заведени фактури
исто да можат да се уредуваат, со свесно прифаќање дека тоа мора да
прекнижи. Изградено: `LedgerService::reverseEntry()` — сторнира постоечки
запис со нов (дебит/кредит заменети), **никогаш не брише** оригиналниот
(ревизорска трага). `InvoiceService::updateInvoice()`/
`PurchaseInvoiceService::updatePurchaseInvoice()` сега дозволуваат
`status === 'issued'/'posted'`, ама САМО кога `issuedInvoiceEditBlockReason()`/
`postedInvoiceEditBlockReason()` враќа null — секоја од следниве, ако постои,
блокира: матчирана банкарска трансакција (`BankTransactionRepository::matchedAmountForInvoice()`),
применет аванс (`AdvanceApplicationRepository::appliedAmountForInvoice()`),
веќе создадено основно средство (`FixedAssetRepository::existsForPurchaseInvoice()`,
само влезна страна), или (само излезна страна) е-фактура веќе
`sent`/`accepted` кај УЈП — тоа е веќе регистрирано кај даночната управа,
локална измена без известување на УЈП би било невистинито. Причината зошто
секоја од овие блокира е иста: сите читаат `total_gross` **во живо** од
фактурата (не снапшот), па тивка промена по веќе постоечко плаќање/аванс/
средство би ја расипала пресметката за секого што веќе зависи од старата
вредност. Проверката е јавна и повикана и на GET `/edit` (не само на
зачувување) — корисникот не добива форма што секако би отпаднала.
**Технички detail:** `reverseEntry()`/`postEntry()` секој управува со
сопствена PDO транзакција (нема вгнездени транзакции на овој wrapper) — истото
ограничување веќе го прифаќаат `issue()`/`post()` (не ново овде), па
header+линии се зачувуваат во своја транзакција, потоа сторно+репост
следат како засебни чекори.
**Revisit when:** некој од блокираните случаи (пр. фактура со матчирана
уплата) реално почесто треба уредување — тогаш веројатно треба вистинска
кредитна нота/сторно-документ кон партнерот, не проширување на оваа
"тивка" репост-патека.

### Праќање кон УЈП е-фактура: рачно копче, никогаш автоматски при издавање   (2026-08-19)
`issue()` (draft→issued, книжење во главната книга) и „Прати како е-фактура“
се два независни чекори — издавањето никогаш автоматски не ја праќа
фактурата кон УЈП. Штом УЈП прифати е-фактура, таа е регистрирана кај
даночната управа и видлива кај купувачот; поништување бара формално сторно/
корекција кон УЈП (административна постапка), не бришење на нацрт. Со уште
нула жива практика со овој тек, автоматизацијата ја отстранува последната
шанса некој да фати погрешен ДДВ индикатор/ЕДБ пред тоа да стане неповратно
во државен систем. Копчето е видливо само на `issued`/`paid` фактура и само
кога `einvoice_status` е `not_sent`/`error`/`rejected` (не дозволува двојно
праќање). Статусот (`einvoice_status`/`einvoice_euid`/`einvoice_error`,
migration 024) се памети на фактурата преку `SalesInvoiceEinvoiceService`,
одделно од `InvoiceService`/`LedgerService` — грешка при праќање никогаш не
го допира книжењето.
**Revisit when:** откако постои реално искуство со текот (десетици успешни
рачни праќања без изненадувања) — тогаш автоматско праќање при `issue()`
станува разумно да се разгледа, не порано.

### УЈП е-фактура: scaffold без жив клиент — JSON+JWS потпис преку OpenSSL, не UBL/нов пакет   (2026-08-19)
`PLAN.md` порано намерно го одложи ова ("UBL 2.1, потпис") бидејќи немаше
итност и немаше клиент. Клиентот сега бара да се подготви инфраструктурата
однапред, пред да постои реален договор/сертификат, за да треба само да се
пополнат `.env` вредности штом стигнат. Официјалната УЈП документација
(доставена директно од клиентот — `api-documentation-public7.pdf` +
`json_primeri_13.8.2026.pdf`) го потврдува форматот: **JSON payload потпишан
како compact JWS (RS256)**, не UBL 2.1 XML како што претходно се претпоставуваше
— `PLAN.md` беше поправено. Изградено: `App\Service\Einvoice\EinvoiceConfig`
(чита `config/config.php['einvoice']`, сите клиент-специфични вредности
default на `null` — нема mock), `JwsSigner` (compact JWS преку вградениот
`openssl_sign`, чита `.p12`/`.pfx` преку `openssl_pkcs12_read` — **нема нов
Composer пакет**, оправдување: RFC 7515 compact JWS е неколку линии со
вградениот OpenSSL, не заслужува нова зависност), `UjpEinvoiceClient` (cURL,
сите нештитени reference-data сервиси + еден репрезентативен потпишан тек
`sendSalesInvoice`/`currentSalesInvoiceStatus` — не сите ~20 документ-операции
одеднаш, туку доказ дека механизмот работи, останатите се додаваат кога реално
затребаат), `SalesInvoicePayloadBuilder` (чист трансформатор Invoice→JSON,
тестиран без жива врска). ДДВ стапките добија `ujp_tax_indicator_code`
(migration 023) — **рачно поле, не се изведува автоматски** од rate/type,
бидејќи истата стапка (пр. 18%) може да значи различен УЈП код во зависност
од контекст (пр. член 32-а обратно оданочување) — билдерот експлицитно
одбива да прати фактура ако стапката на употребена линија нема мапиран код.
**Отворен ризик, нерешен без реален клиент:** ако квалификуваниот сертификат
на клиентот излезе да е хардверски токен (потпишува преку Windows CryptoAPI,
не преку читлив `.p12` приватен клуч), `JwsSigner` нема да работи на Linux/
cPanel продукцијата — ќе треба сервис за потпишување на друга (Windows)
машина. Исто така непотврдено без жив sandbox повик: точната содржина на JWS
header-от (дали треба `x5c`/`kid` покрај `alg`) и дали телото на потпишан
повик е чист JWS string или JSON обвивка околу него — двете се
најдобрите претпоставки од достапната документација, не се тестирани живо.
**Revisit when:** клиентот достави тест-профил (EUJP-ID/EDB преку
`eujptest.ujp.gov.mk/ureg`) и сертификат — прв жив повик кон
`efakturatest.ujp.gov.mk` ќе потврди или побие двете претпоставки погоре.

### Analytic sub-accounts 2200/2201 for accounts payable   (2026-08-17)
Purchase invoices post to new analytic sub-accounts `2200`/`2201` (domestic/foreign
supplier) as children of the official `220`/`221`, instead of posting to `220`/`221`
directly. Matches the same choice already made for receivables (`1200`/`1201` as
children of `120`/`121`), so AR and AP follow one consistent pattern and both leave
room for finer-grained analytics later without renumbering the official accounts.
**Revisit when:** never, unless the receivables analytics pattern itself gets
revisited.

### Purchase invoice lines take VAT rate as manual input, not resolved from category   (2026-08-17)
Unlike sales-side `product_categories`/`service_categories` (which store a default
VAT rate per context and resolve it automatically), `expense_categories` has no VAT
rate columns — a purchase invoice line's VAT rate is typed in by the user instead.
Reasoning: on the sales side this company controls the VAT rate it charges, so a
category-level default is correct. On the purchase side, the VAT rate is whatever
the supplier printed on the invoice they issued — a category can plausibly see
different rates from different suppliers, so a fixed default would misfire. The
account is still resolved automatically from the category, same as sales.
**Revisit when:** never expected to change — this follows directly from who
controls the rate on each side of a transaction.

### Invoice outstanding balance is computed, not stored   (2026-08-17)
`bank_transactions` has no `partial` value in its `matched_status` enum (only
`unmatched`/`matched`), and invoices have no `amount_paid` column. A bank
transaction matches to exactly one invoice; the invoice's outstanding balance
is always `total_gross` minus the sum of its already-matched transactions
(`BankTransactionRepository::matchedAmountForInvoice()`), computed on demand.
Partial payment just means the invoice stays in its current status with a
smaller computed balance — status flips to `paid` only when that balance
hits exactly zero. Avoids a second source of truth (a stored running balance)
that could drift from the transaction history backing it.
**Revisit when:** a transaction needs to split across multiple invoices in
one go — that needs a real allocations table, this model assumes 1:1.
