# Project setup

Bootstrap runbook for a new project started from this template. Stack:
**pure PHP (no framework)** backend, **plain HTML + jQuery + Bootstrap** frontend
(no build step). Dependency manager is **Composer**. Copy this folder into a new
empty repo and follow the steps in order.

> Philosophy: gates should be fast and automatic. Keep them light enough that
> nobody reaches for `--no-verify` — a bypassed gate is worse than no gate.

---

## 0. Prerequisites

- PHP 8.2+ (`php -v`)
- Composer (`composer -V`)
- MySQL or PostgreSQL running locally
- `gitleaks` (secret scan, hard-enforced on every commit): `brew install gitleaks`
  / `winget install gitleaks` / a release binary.
- `php-cs-fixer` and `phpunit` — installed as Composer dev dependencies below,
  no global install needed.

---

## 1. Backend — pure PHP

```bash
mkdir -p public src/Core src/Domain src/Repository src/Service src/Http/Controllers src/View database/migrations tests
composer init --name=yourorg/accounting --require=php:^8.2 --no-interaction
composer require --dev friendsofphp/php-cs-fixer phpunit/phpunit
```

`composer.json` autoload block:
```json
"autoload": { "psr-4": { "App\\": "src/" } }
```

Minimal entry point (`public/index.php`):
```php
<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

// Router dispatch goes here — see src/Core/Router.php
echo "OK";
```

```bash
composer install
php -S localhost:8000 -t public   # dev server
```

Add `composer.json` scripts:
```json
"scripts": {
  "lint": "php-cs-fixer fix --dry-run --diff",
  "lint:fix": "php-cs-fixer fix",
  "test": "phpunit tests"
}
```

---

## 2. Frontend — plain HTML + jQuery + Bootstrap

No scaffolding tool, no build step. In your base layout (`src/View/layout.php`):

```html
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
```

Pin exact versions. If the client's environment needs to work offline, vendor
these files into `public/assets/vendor/` instead of CDN — see
`.claude/rules/php.md`.

---

## 3. Database — migrations

```bash
mkdir -p database/migrations
```

Plain numbered `.sql` files (`001_create_accounts.sql`, `002_create_journal.sql`, ...).
A tiny migration runner (`scripts/migrate.php`) reads them in order and applies
any not yet recorded in a `migrations` tracking table. No migration framework
needed at this scale.

---

## 4. Quality gates — native git hooks (no Node)

There's no frontend Node project here, so hooks are plain shell scripts, not
husky. Point git at the committed hooks folder:

```bash
git config core.hooksPath .githooks
chmod +x .githooks/*
```

Copy these files from the template into the repo root (already included here):
- `.githooks/pre-commit` — gitleaks secret scan, then `composer lint`
- `.githooks/commit-msg` — Conventional Commits format check
- `.githooks/pre-push` — `composer test`

**Why pre-push is just tests, not lint+test again:** lint already ran at commit
time; re-running it on every push adds time without adding safety. Tests prove
behavior, which is the gate worth keeping on the slower path.

### 4a. Secret scanning (local gate)

`.githooks/pre-commit` runs `gitleaks protect --staged` before lint. This is
the enforcement layer (no CI backstop yet), so it hard-fails if gitleaks isn't
installed — a skipped scanner is the same as no scanner.

- Commit `.env.example` (placeholders only); keep real DB credentials in `.env`.
- `.gitignore` ignores `.env`/`.env.*` while keeping `.env.example`, plus
  `/vendor`, editor/OS noise. Does **not** ignore `.claude/`, which is
  committed on purpose.
- `.gitleaks.toml` allowlists `.env.example` and test fixtures.

---

## 5. Claude guardrails

Copy the `.claude/` folder into the repo and commit it.

- `.claude/settings.json` — permissions policy (allow/ask/deny) + PreToolUse hook.
- `.claude/hooks/guard.mjs` — content backstop (runs via `node`, which Claude
  Code itself already depends on — no extra Node project needed). Blocks
  destructive shell commands, edits/reads of protected paths, and any access
  to real `.env` files.
- `.claude/rules/php.md` — PHP conventions (PSR-12, PDO, no ORM, no framework).

Edit `PROTECTED` in `guard.mjs` and the `deny` list in `settings.json` to match
this project's real off-limits paths. When a production target exists, add its
deploy command + host to `deny`.

---

## 6. Conventional commits + versioning

`COMMITS.md` documents the format; `.githooks/commit-msg` checks it on every
commit.

```bash
composer require --dev --no-interaction (optional: a PHP changelog tool, or bump versions manually via git tags)
```

At this project's size, manual `git tag`s are enough — no changelog automation
needed. Revisit in `DECISIONS.md` if that changes.

---

## 7. First slice (don't skip)

1. Run `/spec` to scope the first increment into a lean `SPEC.md`.
2. Build the smallest end-to-end path first: one route → one controller → one
   DB read/write. Prove the wiring before adding features.
3. Add the `LedgerService` balance-invariant test immediately once it exists —
   see `TESTING.md`.
4. Run `/sync-architecture` to write `ARCHITECTURE.md` once the project has
   more than one moving part.

---

## Files in this template

```
README.md                    ← template front door
SETUP.md                     ← you are here
CLAUDE.md                    ← agent instructions (PHP + jQuery/Bootstrap)
CHARTER.md / PRD.md          ← optional intake templates (skip for solo/small projects)
COMMITS.md                   ← commit convention reference
DECISIONS.md                 ← decision log, seeded with this project's stack choices
TESTING.md                   ← test policy (PHPUnit)
.githooks/{pre-commit,commit-msg,pre-push}
.gitleaks.toml
.gitignore
.env.example
composer.json
scripts/migrate.php          ← tiny SQL migration runner
.claude/settings.json        ← permissions + PreToolUse hook
.claude/hooks/guard.mjs      ← destructive-action + folder guard
.claude/rules/php.md         ← PHP conventions
```
