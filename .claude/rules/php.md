# PHP conventions

Loads for any `.php` file. This project is **pure PHP, no framework** — these
rules exist precisely because there's no framework to enforce structure for us.

## Style

- PSR-12 formatting, enforced by `php-cs-fixer` (`composer lint` / `composer lint:fix`).
- Strict types at the top of every file: `declare(strict_types=1);`
- Descriptive class/method names, no abbreviations that need a decoder ring.

## Structure (see PLAN.md for the full layout)

```
/public          → index.php (single entry point), assets
/src
  /Core          → Database (PDO wrapper), Router, Request, Response
  /Domain        → Account, JournalEntry, JournalLine, Invoice, Partner ...
  /Repository    → one class per table, all SQL lives here
  /Service       → business logic (LedgerService, InvoiceService, ReportService)
  /Http/Controllers
  /View          → plain PHP templates
```

- **Repository** = the only layer that writes SQL. No SQL in controllers or services.
- **Service** = business rules and orchestration (e.g. building journal lines
  from an invoice, resolving account mappings). Services call repositories,
  never the other way around.
- **Controller** = thin. Parses the request, calls a service, renders a view or
  returns JSON. No business logic in controllers.
- Don't add a layer (mapper, facade, DTO class) until the codebase's actual
  size justifies it — three files that just pass data through each other is a
  smell, not a pattern.

## Database access

- **PDO only, prepared statements always** — never interpolate user input into
  SQL, no exceptions.
- Every multi-row write that must be atomic (especially journal postings) is
  wrapped in a transaction:
  ```php
  $pdo->beginTransaction();
  try {
      // ... inserts ...
      $pdo->commit();
  } catch (\Throwable $e) {
      $pdo->rollBack();
      throw $e;
  }
  ```
- No ORM. Repository methods return plain arrays or simple typed value
  objects — not ActiveRecord-style models with behavior baked in.

## Autoload

- PSR-4 via Composer: `"App\\": "src/"` in `composer.json`. No `require`/`include`
  chains for project code.

## Frontend (served from `/public`)

- Plain HTML + jQuery + Bootstrap. No build step, no bundler.
- jQuery/Bootstrap via CDN `<script>`/`<link>` tags (pin versions) or vendored
  static files under `public/assets/vendor/` if offline use matters.
- Escape all output (`htmlspecialchars()`) when echoing user-supplied or
  DB-sourced data into HTML — this is a plain-PHP project, so there's no
  templating engine auto-escaping for you.
- Keep JS in `public/assets/js/*.js` files, not inline `<script>` blocks
  scattered through views, once a page's JS grows past a few lines.

## What NOT to do

- Don't add a framework (Laravel, Symfony, Slim) without a recorded decision in
  `DECISIONS.md` first.
- Don't add an ORM (Eloquent, Doctrine) — see "Database access" above.
- Don't add a frontend build pipeline (webpack, vite, npm) for jQuery/Bootstrap
  — CDN or static vendored files are enough at this scale.
- Don't reach for a router library — the hand-rolled `Router` in `src/Core` is
  intentionally simple; extend it rather than replacing it with a package.
