# Accounting System — Posting Rules & Account Mapping Addendum

**Context:** This extends the existing phased plan (Phase 0–6, pure PHP, no framework, PDO, double-entry core already built/in progress). This document covers the **account mapping design** for how invoice lines get posted to the correct accounts — worked out in detail before implementation. Treat this as an addendum to Phase 1 (Chart of Accounts) and Phase 4 (Invoicing), not a replacement.

This is a **general-purpose accounting system**, not industry-specific. Any domain examples below (transport, etc.) are illustrative only.

---

## Core principle

No account is ever chosen manually by the user at invoice-entry time. Every account is resolved automatically from a **predefined mapping** set up once, in configuration, on the product/service/category level — never on the invoice itself. The invoice-entry UI only asks the user to pick a partner and a product/service/category from a dropdown; the account and VAT rate are looked up, not typed.

---

## 1. Products/services are grouped into categories — never mapped to accounts individually

Do NOT map an account per product/SKU. That doesn't scale and makes the chart of accounts unmanageable (accounts should stay a stable, relatively small structure).

Correct model:

```
product_categories: id, name, domestic_account_id, foreign_account_id, ...
products: id, name, category_id, price, ...

service_categories: id, name, domestic_account_id, foreign_account_id, ...
services: id, name, category_id, price, ...
```

- Revenue account is resolved from the **category**, not the individual product/service.
- Product/service-level detail (e.g. "how much did Product A sell for") is answered from `invoice_lines` grouped by `product_id` — a reporting/analytics query, NOT from the chart of accounts. The general ledger only needs to balance at the category-account level; per-product breakdowns are a separate query layer on top.

Rule of thumb: up to ~10-15 categories → each gets its own revenue account. Beyond that, keep the chart of accounts at a higher grouping level and rely on `category_id`/`product_id` for finer reporting.

## 2. Account resolution depends on category + context, not category alone

The same category can legitimately map to *different* accounts depending on context — most commonly whether the partner is domestic or foreign (and potentially EU vs non-EU, since VAT treatment differs by law).

Two implementation options, pick based on how many contexts are needed:

**A. Simple (2 contexts: domestic/foreign) — fixed columns:**
```
product_categories / service_categories:
  id, name,
  domestic_account_id, domestic_vat_rate_id,
  foreign_account_id, foreign_vat_rate_id
```

**B. General (3+ contexts, e.g. domestic / EU-B2B-reverse-charge / non-EU-export) — mapping table:**
```
category_account_map: id, category_id, context, account_id, vat_rate_id
-- context: 'domestic' | 'eu' | 'non_eu' | 'default'
```
Fall back to a `'default'` row when no context-specific row exists, so not every category needs every context populated.

Start with option A; only move to B if a third context is genuinely needed.

### Resolution algorithm at invoice-line entry time

```php
function resolveContext(Partner $partner): string {
    if (!$partner->isForeign()) return 'domestic';
    return $partner->isEu() ? 'eu' : 'non_eu';
}

function resolveAccountForLine(Category $category, Partner $partner): int {
    $context = resolveContext($partner);
    return $category->accountFor($context) ?? $category->accountFor('default');
}

function resolveVatRateForLine(Category $category, Partner $partner): VatRate {
    $context = resolveContext($partner);
    return $category->vatRateFor($context) ?? $category->vatRateFor('default');
}
```

The user picks a partner and a product/service on the invoice line. Everything else (account, VAT rate) is deterministic lookup — never a manual choice.

## 3. Posting algorithm (invoice → journal entry)

Group invoice lines by resolved account before posting — do NOT post one journal line per invoice line.

```
1. Debit:  one line for the full gross total → Accounts Receivable (partner's AR account, or default AR account)
2. Credit: group lines by resolved revenue account → one credit line per unique account, summed net amount
3. Credit: group lines by resolved VAT rate → one credit line per unique rate, summed VAT amount
```

Example: an invoice with 5 lines across 2 categories and 2 VAT rates produces 1 debit + up to 4 credit lines, always balanced, always readable in the general ledger.

Same logic in reverse for purchase invoices (debit = expense/asset accounts + deductible VAT, credit = Accounts Payable).

## 4. VAT rate needs a `type`, not just a percentage

```
vat_rates: id, rate, type, name
-- type: 'standard' | 'reduced' | 'zero' | 'exempt_with_credit' | 'exempt_no_credit' | 'out_of_scope' | 'reverse_charge'
```
A flat percentage isn't enough for correct VAT return reporting — the legal basis for 0%/exempt differs (zero-rated vs exempt vs out-of-scope vs reverse-charge), and each is reported differently.

## 5. Purchase-side specifics (expense_categories)

```
expense_categories: id, name,
  domestic_account_id, foreign_account_id,
  vat_deductible,        -- 'full' | 'none' | 'partial'
  is_capitalizable,       -- true → must go through fixed-asset flow, not a plain expense account
  reverse_charge_applicable  -- true when the category typically involves foreign-supplied services
```

- **Reverse charge**: when a service is received from a foreign supplier, the buyer self-assesses VAT. Posting is different from a normal domestic purchase:
  ```
  Debit:  VAT receivable (self-charged)
  Credit: VAT payable (self-charged)
  Debit:  Expense
  Credit: Accounts Payable (foreign supplier)
  ```
- **Fixed assets**: a purchase flagged `is_capitalizable` must NOT post to a normal expense account — it goes to an asset account and needs a depreciation schedule (can be stubbed for now, but the posting must go to the right account from day one, not be reclassified later).
- **VAT deductibility**: `none`/`partial` categories must not fully reclaim input VAT — needed for correct VAT return figures.

## 6. Two additional document types (not just "another invoice")

- **Advance invoices (received/paid)**: money received/paid before the final invoice is NOT revenue/expense yet — it posts to a liability/asset "advance" account, and is cleared when the final invoice is issued.
- **Credit notes**: a linked document (reference to the original invoice), not a new invoice with negative amounts — it must generate a full reversal-style journal entry.

## 7. Currency / FX ✅ Built

- Invoices in foreign currency need `exchange_rate` stored at invoice date.
- Settlement at a different rate on payment date generates a realized FX gain/loss — needs a dedicated "FX gain/loss" account and a settlement step in the payment-matching flow.

**Implementation notes:** `currencies` table (MKD fixed as base, foreign
currencies configurable via `/currencies`); `invoices`/`purchase_invoices`
carry `currency_id`+`exchange_rate` (manual entry, frozen at document
creation) — the document itself stays in its own currency, conversion to
MKD happens only at `issue()`/`post()` time. Realized gain/loss: a
`$closeWithFxDifference` flag on `PaymentMatchingService::matchToSalesInvoice()`/
`matchToPurchaseInvoice()` lets a foreign-currency invoice close even when
the settled MKD amount doesn't exactly match the booked MKD balance, posting
the difference to the client's existing `7750`/`4750` accounts (positive/
negative FX differences — already present in the imported chart of accounts,
no new accounts needed). Unrealized period-end revaluation is a separate
`FxRevaluationService` (`fx_revaluations`/`fx_revaluation_lines` tables) that
adjusts the GL AR/AP balance to a new rate without touching the invoice
itself, always diffing against the *last* revaluation (not the original
rate) so repeated runs don't double-count.

## 8. Foreign-currency bank statements (девизни изводи) ✅ Built

**Status: not built.** Confirmed by inspecting the schema — `bank_statements`/
`bank_transactions` (`012_bank_statements.sql`) have no `currency_id` or
`exchange_rate` column at all; `amount DECIMAL(15,2)` is always treated as
MKD. `accounts` has no currency dimension either. A девизна сметка (EUR/USD
bank account) currently can only be entered by manually pre-converting to MKD
before typing it in — the actual foreign-currency amount is lost, and there's
no second source of truth to check the conversion against.

This section designs the extension using the **same pattern already proven
for invoices** (`019_currencies_and_invoice_fx.sql`: `currency_id INT NOT
NULL DEFAULT 1` + `exchange_rate DECIMAL(10,6) NOT NULL DEFAULT 1.000000`,
both FK'd to `currencies`), not a new mechanism.

### 8.1 Rate lives on the transaction, not the statement

A statement (`bank_statements`) can span several calendar days; the NBRM
reference rate changes daily. Putting `exchange_rate` on the statement would
force one rate for every line in it, which is wrong the moment a statement
crosses a day boundary. So:

```
bank_statements:
  + currency_id INT NOT NULL DEFAULT 1  -- the account's currency; every
                                          -- transaction on this statement
                                          -- must match it (one statement =
                                          -- one bank account = one currency)

bank_transactions:
  + exchange_rate DECIMAL(10,6) NOT NULL DEFAULT 1.000000  -- NBRM rate on
                                                             -- transaction_date,
                                                             -- entered manually,
                                                             -- same as invoices
  -- `amount` stays the transaction's own currency (EUR/USD/...), NOT MKD.
  -- The MKD equivalent (amount * exchange_rate) is computed at posting time,
  -- exactly like Invoice::grossInBaseCurrency() does — never stored redundantly.
```

`currency_id = 1` (base/MKD) keeps every existing денарски statement
row behaves exactly as today (`exchange_rate` stays `1.000000`, amount ==
MKD amount) — no migration of existing data, no behavior change for the
common case.

### 8.2 Chart of accounts: one bank GL account per currency

Do not try to hold multiple currencies in a single GL account. A devizna
sметка is its own analytic account (mirrors how the client's imported chart
already splits `1200`/`1201` domestic/foreign receivables, and `100` → `1001`
жиро сметка analytic in `018_client_509_analytic_accounts.sql`):

```
100  Парични средства (group)
1001 ЖИРО СМЕТКА - MKD           (existing)
1002 ДЕВИЗНА СМЕТКА - EUR        (new, per bank/currency as needed)
1003 ДЕВИЗНА СМЕТКА - USD        (new, only if a real account exists)
```

`accounts` itself does **not** need a `currency_id` column for this — the
statement's `currency_id` already pins the transaction to one account and one
currency 1:1 (`bank_statements.account_id` + `bank_statements.currency_id`
together say "this GL account only ever moves in EUR"). Adding a currency
column to `accounts` would duplicate that and risk the two disagreeing;
resolving it from the statement, same as today, is simpler and matches
"don't add abstraction not yet justified."

### 8.3 Posting (`PaymentMatchingService`) — simpler than originally drafted

The GL is always MKD (unchanged invariant). Implementation turned out simpler
than the foreign-to-foreign comparison originally sketched above: every place
that used to read `$transaction->amount` (implicitly assumed MKD) now reads
`$transaction->amountInBaseCurrency()` (`bcmul(amount, exchange_rate, 2)`,
mirrors `Invoice::grossInBaseCurrency()`) instead — a one-line substitution,
not a parallel foreign-currency code path:

- **Path A — manual, no invoice** (`postManual`): both journal legs (bank
  account + the manually-picked GL account) post `amountInBaseCurrency()`
  instead of the raw `amount`. The transaction row still keeps the original
  `amount` (EUR) + `exchange_rate` for the audit trail / reconciliation
  against the printed statement — only the GL posting is converted.

- **Path B — matched to an invoice** (`matchToSalesInvoice`/
  `matchToPurchaseInvoice`): `$mkdAmount = $transaction->amountInBaseCurrency()`
  is computed once and substituted everywhere the code used to use
  `$transaction->amount` — the outstanding comparison, `resolveFxDifference()`,
  the AR/AP journal line, and the bank leg. Since `resolveFxDifference()`
  already operated entirely in MKD (comparing against the invoice's
  `grossInBaseCurrency()`-derived outstanding), converting the transaction to
  MKD *before* handing it to that existing, already-tested logic reuses it
  unchanged — no new foreign-to-foreign comparison code needed.
  **Dropped the "block cross-currency settlement" guard from the original
  draft**: it's unnecessary. A EUR invoice settled from a USD account (or an
  MKD account) is not a special case once both sides are in MKD terms — the
  FX-difference machinery already exists precisely to absorb a mismatch
  between the booked MKD balance and whatever MKD amount actually arrived,
  regardless of which currency produced it. Blocking it would have been
  speculative restriction, not a correctness requirement.

- **Denarski path is untouched by construction.** Any statement with
  `currency_id = 1` gets `exchange_rate` forced to `1.000000` in
  `resolveTransactionRate()` — `amountInBaseCurrency()` then always equals
  `amount`, so every formula above reduces to exactly today's behavior. This
  is enforced once, at the row-insertion boundary
  (`PaymentMatchingService::resolveTransactionRate()`), not scattered across
  branches in each posting method.

- **`BankTransactionRepository::matchedAmountForInvoice()`** sums
  `amount * exchange_rate` (MySQL) — needed a `CAST(... AS DECIMAL(15,2))`
  around the `SUM`, since MySQL widens the decimal scale of the product
  (`amount` 2 places × `exchange_rate` 6 places) and returns e.g.
  `"700.00000000"` instead of `"700.00"` without it, which broke exact-string
  assertions in `PaymentMatchingServiceTest` on the existing (denarski) suite
  until caught and fixed.

- **Running `balance_after` stays in the statement's own currency** (EUR for
  a devizna izvod), not MKD — it exists to reconcile against the printed/
  e-statement balance, which is in EUR. MKD conversion happens only at
  GL-posting time, never touches `insertTransactionRow()`/`lastBalance()`.

Covered by `tests/BankStatementCurrencyTest.php`: manual FX posting, the
denarski-forced-rate guarantee, a rejected non-positive rate, full/partial
EUR settlement, and FX-close against a bank rate that differs from the
invoice's own rate.

### 8.4 Left out of this addendum, flag if actually needed later

- **Period-end revaluation of the bank balance itself.**
  `FxRevaluationService` today revalues open AR/AP only; an unsettled EUR
  bank balance sitting at period end also has unrealized FX exposure in
  principle. This is a real gap but a separate, smaller extension once the
  statement-side currency exists — don't build it speculatively now.
- **Live NBRM rate lookup.** Rate stays a manual field per transaction, same
  as invoices — no external rate-fetching service.
- **A single bank account holding mixed currencies.** Not a real-world case
  for a devizna сметка; one account = one currency, enforced by §8.1.

## 9. Cross-cutting

- **Never DELETE a posted journal entry.** Cancelling an invoice after it's posted creates a reversal entry; the original stays for audit trail.
- **Rounding**: compute VAT per line, sum, round once at the end; keep a rounding account for residual differences if needed.

---

## What to build (incremental order for Claude Code)

Given the core double-entry engine and basic invoicing already exist, extend in this order:

1. ✅ Add `type` to `vat_rates`, extend `product_categories`/`service_categories`/`expense_categories` with the account-mapping columns from sections 1–2 and 5 above (migration + repository updates).
2. ✅ Rework the invoice-posting service to use the context-resolution + group-by-account algorithm from section 3 (both sales and purchase sides). Purchase side: `PurchaseInvoiceService`, account resolved from `expense_categories` + domestic/foreign context (like sales), but the VAT rate is entered manually per line instead of resolved — it's whatever the supplier printed on the received invoice, not something this company controls. `vat_rates` got a `receivable_account_id` column (mirrors `payable_account_id`, but for deductible input VAT) and AP posts to new analytic sub-accounts `2200`/`2201` (mirroring the `1200`/`1201` receivables pattern). `vat_deductible = 'none'` folds the VAT into the expense line instead of splitting it to a receivable account.
3. Add reverse-charge posting path for purchases where `expense_category.reverse_charge_applicable = true`. **Guarded, not built**: `PurchaseInvoiceService::createPurchaseInvoice()` throws if a line's category has this flag set, so a category can be configured ahead of time without silently mis-posting until this step lands.
4. Add fixed-asset posting path for `is_capitalizable` categories (account only for now; depreciation schedule can be a stub/TODO). **Guarded, not built** — same reasoning as step 3.
5. Add `advance_invoices` and `credit_notes` as their own tables/flows, each with their own posting logic, linked to the original invoice/partner.
6. ✅ Add `exchange_rate` to invoices + FX gain/loss posting on settlement in the payment-matching step. Also added: period-end unrealized revaluation (`FxRevaluationService`), not originally scoped in this step but built alongside since it shares the same account-mapping (see §7).
7. Update reporting queries (trial balance, VAT ledger) to respect `vat_rates.type` for correct grouping. Note: `ReportService::vatSummary()` already reads `vat_rates`-linked accounts `260`/`160` generically, so it picked up purchase-side input VAT with no changes needed once step 2 landed.
8. ✅ Add `currency_id`/`exchange_rate` to `bank_statements`/`bank_transactions` and rework `PaymentMatchingService` per §8 above. Turned out simpler than drafted: convert to MKD once via `BankTransaction::amountInBaseCurrency()` and reuse the existing MKD-only posting/FX logic unchanged, rather than a parallel foreign-to-foreign comparison — no cross-currency settlement block needed either (migration `021_bank_statement_fx.sql`, `tests/BankStatementCurrencyTest.php`).

Also not yet built: `vat_deductible = 'partial'` has no stored split ratio in the schema, so `createPurchaseInvoice()` guards against it the same way as steps 3/4 — add the ratio column and the split logic when a real category needs it.

Each step should ship with its own migration + repository + service + a manual test invoice before moving to the next.
