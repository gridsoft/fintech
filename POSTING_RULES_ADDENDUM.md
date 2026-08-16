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

## 7. Currency / FX

- Invoices in foreign currency need `exchange_rate` stored at invoice date.
- Settlement at a different rate on payment date generates a realized FX gain/loss — needs a dedicated "FX gain/loss" account and a settlement step in the payment-matching flow.

## 8. Cross-cutting

- **Never DELETE a posted journal entry.** Cancelling an invoice after it's posted creates a reversal entry; the original stays for audit trail.
- **Rounding**: compute VAT per line, sum, round once at the end; keep a rounding account for residual differences if needed.

---

## What to build (incremental order for Claude Code)

Given the core double-entry engine and basic invoicing already exist, extend in this order:

1. Add `type` to `vat_rates`, extend `product_categories`/`service_categories`/`expense_categories` with the account-mapping columns from sections 1–2 and 5 above (migration + repository updates).
2. Rework the invoice-posting service to use the context-resolution + group-by-account algorithm from section 3 (both sales and purchase sides).
3. Add reverse-charge posting path for purchases where `expense_category.reverse_charge_applicable = true`.
4. Add fixed-asset posting path for `is_capitalizable` categories (account only for now; depreciation schedule can be a stub/TODO).
5. Add `advance_invoices` and `credit_notes` as their own tables/flows, each with their own posting logic, linked to the original invoice/partner.
6. Add `exchange_rate` to invoices + FX gain/loss posting on settlement in the payment-matching step.
7. Update reporting queries (trial balance, VAT ledger) to respect `vat_rates.type` for correct grouping.

Each step should ship with its own migration + repository + service + a manual test invoice before moving to the next.
