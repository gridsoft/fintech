# Agentic development template (PHP / jQuery / Bootstrap variant)

A starting base for new projects built **with** AI agents (Claude Code), where
the recurring decisions are already made: stack, commit conventions, quality
gates, secret scanning, and agent guardrails. Clone it, configure a couple of
placeholders, and start building — the project begins with working rails
instead of a week of setup debates.

**Philosophy:** clean, simple, working software. Production-minded habits from
day one (hooks, linting, secret scanning, conventional commits) — without
speculative complexity (no framework/ORM/auth/caching/queues until a real
requirement demands them).

## Pre-made decisions

| Decision | Choice | Where it's enforced |
|---|---|---|
| Backend | Pure PHP, no framework | `.claude/rules/php.md` |
| Frontend | Plain HTML + jQuery + Bootstrap, no build step | `.claude/rules/php.md` |
| Dependencies | Composer, PSR-4 autoload only | `composer.json` |
| DB access | PDO + prepared statements, no ORM | `.claude/rules/php.md` |
| Commits | Conventional Commits | `.githooks/commit-msg` |
| Secrets | never committed; `.env` gitignored, `.env.example` is the contract | gitleaks via `.githooks/pre-commit` |
| Agent guardrails | allow / ask / deny permissions + fail-closed guard hook | `.claude/settings.json`, `.claude/hooks/guard.mjs` |

The "why" behind these lives in `DECISIONS.md`. Disagree with one? Change it
there, deliberately — don't erode it silently.

## Start a new project from this template

1. **Clone/copy** this repo into the new project's repo.
2. **Configure the placeholders**:
   - `.env.example` — replace with the env vars the project actually needs.
   - `.claude/hooks/guard.mjs` — adjust `PROTECTED` paths for this project.
3. **Follow `SETUP.md`** — prerequisites, scaffold, hooks, migrations.
4. **Bring the business intake, if there is one:** fill in `CHARTER.md`/`PRD.md`
   only if the org's process requires it. Solo/small projects can skip both.
5. **Scope before building:** run `/spec` in Claude Code to write a lean
   `SPEC.md`.
6. **Build the smallest end-to-end slice first**, then grow features on
   proven wiring.
7. **Replace this README** with the project's own once it has an identity.

## What's in the box

```
CLAUDE.md                  ← agent instructions: principles, conventions, guardrails
CHARTER.md / PRD.md        ← optional intake templates (skip for solo/small projects)
SETUP.md                   ← full bootstrap runbook
TESTING.md                 ← test policy: right-sized, behavior-focused (PHPUnit)
COMMITS.md                 ← Conventional Commits reference
DECISIONS.md               ← decision log, seeded with this project's stack choices
composer.json              ← PSR-4 autoload + dev tools (php-cs-fixer, phpunit)
scripts/migrate.php        ← tiny SQL migration runner
.githooks/                 ← pre-commit (gitleaks + lint), commit-msg, pre-push (tests)
.claude/                   ← committed agent config: permissions, guard hook, php.md rule
```

Everything under `.claude/` is committed on purpose: every teammate's agent
session inherits the same permissions, guard hook, and rules.

## Notes for template maintainers

- Local git hooks are the enforcement layer here; there is no CI backstop.
  When the project gets a CI pipeline, mirror the gates there.
- When a production environment exists, add its deploy commands/hosts to the
  `deny` list in `.claude/settings.json` **before** pointing anything at it.
- **No personal data in committed files** — docs reference roles/functions,
  never individuals. Git history is permanent.
