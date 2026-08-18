# Deploy — cPanel via GitHub Actions

Push to `master` → GitHub Actions SSHes into the cPanel server, pulls the
latest code, reinstalls Composer dependencies, and runs pending migrations.
Nothing runs automatically anywhere else — this is the only deploy path.

> Philosophy: the server is a **pull target**, not something Claude Code (or
> anyone) SSHes into directly to push changes by hand. All deploys go through
> this reviewed workflow so there's one auditable path, not several.

---

## 0. One-time server setup (do this once, manually, over SSH)

The web root of this app is `public/`, not the repo root — `src/`, `vendor/`,
migrations, and `.env` must **never** sit inside a publicly-servable folder.
Clone the repo *outside* `public_html` and point the domain's document root
at its `public/` subfolder instead.

```bash
ssh yourcpaneluser@yourhost -p <ssh-port>

git clone https://github.com/gridsoft/fintech.git ~/fintech-app
cd ~/fintech-app
composer install --no-dev --optimize-autoloader

cp .env.example .env
# edit .env with the REAL production DB credentials — nano/vim .env

php database/migrate.php   # schema, migrations 001–009
php database/seed.php      # official MK chart of accounts — later migrations need this data
php database/migrate.php   # remaining migrations (010+) that reference specific account codes
```

That three-step order matters on a genuinely fresh database: several migrations from 010 onward look up accounts by code (`SELECT id FROM accounts WHERE code = '120'`), which only exist after `seed.php` runs. `database/migrate.php` and `database/seed.php` are both safe to re-run any time afterward — migrations skip anything already applied, and the seed skips codes that already exist.

Then in cPanel: **Domains → (your domain) → Document Root** → set it to
`/home/yourcpaneluser/fintech-app/public`. If cPanel won't let you point a
domain's document root outside `public_html`, use a symlink instead:
`rm -rf ~/public_html && ln -s ~/fintech-app/public ~/public_html` (only if
`public_html` is otherwise empty — back it up first if not).

`.env` is gitignored and never touched by the deploy workflow (`git reset
--hard` only affects tracked files) — editing it again later is a manual SSH
step, same as this first time.

---

## 1. SSH key for GitHub Actions

Generate a dedicated deploy key (don't reuse your personal one):

```bash
ssh-keygen -t ed25519 -f cpanel_deploy_key -N ""
```

- Add `cpanel_deploy_key.pub` to cPanel: **SSH Access → Manage SSH Keys →
  Import Key**, then **Authorize** it.
- Keep `cpanel_deploy_key` (the private half) to paste into GitHub in the
  next step. Delete the local copy afterward — it lives only in the GitHub
  secret from here on.

---

## 2. GitHub repo secrets

**Settings → Secrets and variables → Actions → New repository secret** —
add all five:

| Secret | Value |
|---|---|
| `CPANEL_HOST` | server hostname or IP |
| `CPANEL_SSH_PORT` | SSH port (cPanel's SSH Access page shows it — often not 22) |
| `CPANEL_SSH_USER` | cPanel username |
| `CPANEL_SSH_KEY` | contents of the **private** key file (`cpanel_deploy_key`) |
| `CPANEL_DEPLOY_PATH` | absolute path to the clone, e.g. `/home/yourcpaneluser/fintech-app` |

None of these are read by anything except the `deploy.yml` workflow.

---

## 3. Deploying

- **Automatic:** merging/pushing to `master` triggers it.
- **Manual re-run:** repo → **Actions → Deploy to cPanel → Run workflow** —
  useful to retry after fixing a transient failure, without needing a new commit.

Migrations run on every deploy (`php database/migrate.php`) — safe by
design, it only applies migrations not yet recorded as run (see
`database/migrate.php`), so a deploy with no new migration is a no-op there.

---

## What this deliberately doesn't do

- No zero-downtime/blue-green switch, no build artifacts/releases directory
  — `git reset --hard` in place. Fine at this scale (single small app, brief
  window per deploy); revisit if downtime during deploy ever actually matters.
- No automatic rollback on failure. If a deploy breaks something, `git log`
  on the server to find the last good commit, `git reset --hard <sha>`,
  re-run `composer install`/migrate manually over SSH.
- No staging environment. Revisit once there's a second environment worth
  having.
