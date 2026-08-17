# CLAUDE.md

This repo starts from a **project template for agentic development**: tooling,
guardrails, and process decisions are pre-made so a new project starts with
building, not configuring. Optimize for **clean, simple, working software** —
production-minded habits from day one, without speculative complexity.

## What this repo is

- A project built on the template's pre-made decisions. Project-specific docs
  (`SPEC.md`, `ARCHITECTURE.md`) get written as the project takes shape.
- Stack (pre-decided): **pure PHP, no framework** on the backend; **plain HTML,
  jQuery, Bootstrap, CSS** on the frontend. No Angular, no Node-based frontend
  build, no ORM.
- Dependency manager is **Composer** (PSR-4 autoload only — kept minimal, no
  framework packages pulled in "for convenience"). jQuery/Bootstrap are loaded
  via CDN or vendored static files, not an npm build pipeline.

## Engineering principles (read before adding anything)

- **Honor the spec.** If `SPEC.md` exists, read it before building and treat its
  "Out of scope" list as binding — a request that crosses it gets flagged, not
  silently built.
- **Simplest design that solves the problem well.** No speculative auth, RBAC,
  caching, retry/circuit-breaker logic, queues, ORMs, or extra abstraction
  layers "for scale later". Add them when a real requirement demands it — and
  say so when one does.
- **No framework.** Don't introduce Laravel/Symfony/Slim or any framework
  without an explicit decision recorded in `DECISIONS.md` first. This is a
  deliberate choice, not an oversight.
- **Clean code over clever code.** Small, focused functions and classes; code
  that reads top-down without a decoder ring. Prefer boring, obvious solutions.
- **Clear boundaries, minimal ceremony.** Controller → Service → Repository →
  Domain layering (see `.claude/rules/php.md`), nothing beyond what the
  codebase's actual size justifies.
- **Every DB write that must be atomic uses a transaction.** Especially
  anything touching the ledger (`journal_entries`/`journal_lines`) — debit and
  credit lines are posted together or not at all.
- **Stubs are temporary and labeled.** Hardcoded/in-memory data is fine while
  the real source isn't the current focus — label it `// MOCK` so it can't
  silently ship as real.
- **Every new dependency is a decision.** Ask before adding a Composer package;
  justify it in one line. Record significant choices in `DECISIONS.md` so they
  aren't relitigated.
- **Flag scope creep, don't absorb it.** If a request implies new
  infrastructure (database, deployment, secrets, external services), stop and
  say so before building it.

## Conventions

- PHP conventions load automatically: `.claude/rules/php.md` (PSR-12, PDO,
  autoload, transactions, no ORM).
- Commit messages follow Conventional Commits — see `COMMITS.md`.
- **No personal data in committed files.** Reference people by role or
  function — never personal names, emails, or phone numbers — in any doc,
  comment, or fixture. Git history is permanent.
- Descriptive names, no dead code, no commented-out blocks left behind.

## Commands

| Action | Command |
|---|---|
| Install dependencies | `composer install` |
| Run migrations | `php scripts/migrate.php` |
| Dev server | `php -S localhost:8000 -t public` |
| Lint (PSR-12) | `composer lint` (php-cs-fixer, dry-run) |
| Fix lint | `composer lint:fix` |
| Test | `composer test` (PHPUnit) |

Frontend has no build step — edit HTML/CSS/JS in `public/` directly, jQuery and
Bootstrap loaded via CDN `<script>`/`<link>` tags or vendored static files.

## How a new project starts

1. If the org's intake produced a charter and/or PRD, fill them in as
   `CHARTER.md` / `PRD.md`. Small internal/solo efforts can skip straight to
   `/spec`.
2. Run `/spec` to scope the first increment into a lean `SPEC.md`.
3. Run `composer install` + `php scripts/migrate.php` to bootstrap.
4. Build the smallest end-to-end slice FIRST (one route → one controller → one
   DB read/write). Prove the wiring, then grow features on it.
5. Once structure settles (and only if >1 moving part), run
   `/sync-architecture` to write `ARCHITECTURE.md`.

## Guardrails (these mirror the enforced policy in `.claude/settings.json`)

The doc explains; `permissions` (allow / ask / deny) and the `guard.mjs` hook
enforce.

- **Always do (autopilot):** read and search the codebase; run `composer`,
  `php`; `git status`/`diff`/`add`/`log`; run lint, and tests. File edits run
  without prompting (`acceptEdits`).
- **Ask first (you decide):** `git push`; installing dependencies
  (`composer require`); `gh` operations; anything that publishes, posts, or
  sends; accepting terms or changing account settings.
- **Never do (blocked):** read or print secrets / real `.env` files /
  credentials / DB passwords; `rm -rf`, `git push --force`, `git reset --hard`,
  `DROP TABLE`; write to `.env*`, `/vendor`, `.git/`. Exception:
  **`.env.example`** holds placeholders only and is yours to read and maintain.
  *Production: none exists yet — when it does, add deny rules for its deploy
  commands and hosts in `settings.json` before pointing anything at it.*

Separately: don't declare a task done until lint + tests pass, and never commit
real secrets (use `.env`, gitignored, with a `.env.example`).

## Deeper docs (read on demand)

- `README.md` — what this template is and how to start a project from it.
- `SETUP.md` — full install/bootstrap runbook.
- `TESTING.md` — test policy: right-sized, behavior-focused, grows with the code.
- `PLAN.md` / `POSTING_RULES_ADDENDUM.md` — project-specific build phases (this project).
- `SPEC.md` — the lean, agent-facing build contract with the binding out-of-scope list.
- `ARCHITECTURE.md` — system shape, when one exists (written by `/sync-architecture`).
- `DECISIONS.md` — why we chose X over Y, so we don't relitigate it.
