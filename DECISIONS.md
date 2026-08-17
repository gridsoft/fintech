# Decisions

Why we chose X over Y, so we don't relitigate it. One short entry per settled
decision: what was decided, why, and what would make us revisit it. Newest on top.

---

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

---

## Project decisions (append here)

<!--
### <Decision title>            (YYYY-MM-DD)
<What was decided and the one-line why.>
**Revisit when:** <the trigger that would reopen this.>
-->

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
