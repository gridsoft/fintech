# Testing policy

Tests exist to prove behavior and catch regressions — not to hit a number. The
rule of thumb: **test what would embarrass you if it broke**, at the cheapest
level that catches it. Coverage grows with the code; it is never the goal
itself.

## Day one — wire the smoke layer

```bash
composer require --dev phpunit/phpunit
```

```php
// tests/HealthTest.php
use PHPUnit\Framework\TestCase;

final class HealthTest extends TestCase
{
    public function testRouterRespondsOk(): void
    {
        // hit the router/front controller directly, or via a lightweight
        // HTTP client against `php -S localhost:8000 -t public`
        $this->assertTrue(true); // replace once the router exists
    }
}
```

```bash
composer test   # wired to `vendor/bin/phpunit`
```

That's the wiring proof. `.githooks/pre-push` runs it on every push.

## First real test — the ledger invariant

As soon as `LedgerService` exists, it gets a real test before anything else:

```php
// tests/LedgerServiceTest.php
final class LedgerServiceTest extends TestCase
{
    public function testUnbalancedEntryIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $ledger->postEntry([
            ['account_id' => 1, 'debit' => 100, 'credit' => 0],
            ['account_id' => 2, 'debit' => 0, 'credit' => 50], // unbalanced
        ]);
    }

    public function testBalancedEntryPostsSuccessfully(): void { /* ... */ }
}
```

This is the single most important test in the whole system — the double-entry
invariant is what makes every downstream report trustworthy.

## As the code grows — what earns a test

- **Domain/business logic** — account-resolution rules (domestic/foreign
  context lookup), invoice→journal-line grouping, VAT calculations. Cheapest
  tests, highest value. Any function with branches worth thinking about is
  worth a unit test.
- **Every bug fix gets a regression test.** Write the test that would have
  caught it, watch it fail, then fix. Non-negotiable — recurring bugs are the
  most expensive kind.
- **Repository methods touching money** — trial balance sums, account
  balances, VAT ledger totals. A thin integration test against a real (test)
  database beats a mocked one here.
- **Controller happy path + error shape** — one test per route for the
  expected success response and the validation-error response.

## What NOT to test (or add)

- **No coverage thresholds as targets.** They breed assertion-free tests. If a
  floor is wanted later, adopt it deliberately and record it in `DECISIONS.md`.
- **No mock-everything unit tests** that only verify mocks were called. Prefer
  a thin integration test at the real DB boundary — especially for anything
  touching `journal_entries`/`journal_lines`.
- **No e2e browser suite by default.** Add one (e.g. Playwright) only once
  there are stable, critical flows worth the maintenance cost.

## When to tighten the policy

Raise the bar deliberately — and record it in `DECISIONS.md` — when:

- The code touches **money, auth, or data integrity** (most of this project
  qualifies from day one — the ledger, VAT figures, invoice totals).
- A **bug recurred** because a class of logic is under-tested — cover the
  class, not just the instance.

For this project specifically: `LedgerService`, `InvoiceService`'s
posting/grouping logic, and any VAT/rounding calculation are "tighten from the
start" territory, not "tighten later."
