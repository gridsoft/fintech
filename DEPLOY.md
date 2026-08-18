# Deploy — cPanel via GitHub Actions (FTP) + manual migrations

Push to `master` → GitHub Actions builds Composer dependencies and FTP-syncs
the code to the cPanel server. **Migrations do not run automatically** — FTP
can only move files, not execute commands, so a new migration is applied by
hand over SSH after the code lands (see §4). Nothing else runs automatically
anywhere — this is the only deploy path.

> Why FTP and not SSH: SSH was the original plan (see git history /
> `.claude/settings.json`'s ssh/scp/sftp deny rules, still in place), but
> this host (parsicek.si/NeoServ) rejects public-key auth for this account
> at the server level — confirmed with correct key, correct `authorized_keys`
> permissions (600/700), still refused. Password-based SSH does work, which
> is enough for the occasional manual migration run; it's not usable for
> unattended CI. See **Troubleshooting** for the full diagnosis if this ever
> gets resolved with host support and SSH-based deploy is worth revisiting.

---

## 0. One-time server setup (do this once, manually, over SSH — password auth)

The web root of this app is `public/`, not the repo root — `src/`, `vendor/`,
migrations, and `.env` must **never** sit inside a publicly-servable folder.

```bash
ssh yourcpaneluser@yourhost -p <ssh-port>   # password auth

mkdir -p ~/fintech-app
```

Get the code onto the server the first time — either `git clone
https://github.com/gridsoft/fintech.git ~/fintech-app` (what we did
initially; fine to leave `.git` there afterward, it's inert once FTP takes
over) or just let the first FTP deploy populate an empty `~/fintech-app`.
Either way, then:

```bash
cd ~/fintech-app

# Composer often isn't globally installed on shared hosting — check first:
composer --version || (curl -sS https://getcomposer.org/installer | php)
# If it's not global, use `php ~/composer.phar install ...` here and in §4
# instead of bare `composer install ...` (the GitHub Actions runner always
# has global composer, so this only matters for manual on-server commands).
composer install --no-dev --optimize-autoloader --no-interaction

cp .env.example .env
# edit .env with the REAL production DB credentials — nano/vim .env
# also set APP_ENV=production and APP_DEBUG=false for a live site

php database/migrate.php   # schema, migrations up to wherever seed data is first needed
php database/seed.php      # official MK chart of accounts — later migrations need this data
php database/migrate.php   # remaining migrations that reference specific account codes
```

That three-step order matters on a genuinely fresh database: several
migrations look up accounts by code (`SELECT id FROM accounts WHERE code =
'120'`), which only exist after `seed.php` runs — the first `migrate.php`
call will simply stop with an error at whichever migration hits this first;
that's expected, run `seed.php` then `migrate.php` again to finish.
`database/migrate.php` and `database/seed.php` are both safe to re-run any
time afterward — migrations skip anything already applied, and the seed
skips codes that already exist.

**Document root:** cPanel → **Domains → (your domain) → Document Root** → set
it to `/home/yourcpaneluser/fintech-app/public`. **This often isn't available
for the account's primary domain** (cPanel restricts it to addon
domains/subdomains on many setups) — if so, use this instead, which
preserves `cgi-bin`, `.well-known` (AutoSSL needs this — never delete it),
and cPanel's own PHP-version `.htaccess` block:

```bash
cd ~/public_html
ln -s ~/fintech-app/public/index.php index.php
ln -s ~/fintech-app/public/assets assets
cat >> .htaccess << 'EOF'

RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [L]
EOF
```

The symlinks mean every future deploy (which updates `~/fintech-app/public`)
is picked up automatically — nothing to re-copy into `public_html`.
(`public/.htaccess` in the repo has the same rewrite rule, for the case
where the document-root approach *is* available and Apache/LiteSpeed reads
it directly from there instead.)

`.env` is gitignored and excluded from the FTP deploy step — never touched
by CI. Editing it again later is a manual SSH step, same as this first time.

---

## 1. FTP credentials

cPanel → **FTP Accounts** — either use the main account's FTP login, or
create a dedicated FTP account scoped to `~/fintech-app` specifically
(**Directory** field when creating it) so a leaked FTP credential can't
touch anything outside the app folder. Note the host, username, password,
and the exact server-side path it lands in.

---

## 2. GitHub repo secrets

**Settings → Secrets and variables → Actions → New repository secret**:

| Secret | Value |
|---|---|
| `FTP_HOST` | FTP hostname (cPanel's FTP Accounts page shows it, often same as the domain or server hostname) |
| `FTP_USERNAME` | the FTP account's username |
| `FTP_PASSWORD` | the FTP account's password |
| `FTP_SERVER_DIR` | target path, e.g. `/fintech-app/` — if the FTP account is scoped to that directory, this is usually just `/`; if it's the main account, it's the path relative to the FTP root (test with an FTP client first if unsure) |

None of these are read by anything except the `deploy.yml` workflow.

---

## 3. Deploying (code only)

- **Automatic:** pushing to `master` builds `vendor/` on the GitHub Actions
  runner (not the server — no server-side `composer install` needed) and
  FTP-uploads the result.
- **Manual re-run:** repo → **Actions → Deploy to cPanel → Run workflow**.
- The workflow never deletes files on the server that aren't in the repo
  (no clean-slate mode) and never touches `.env` — it only uploads/overwrites.

---

## 4. Running a migration (manual, after a deploy that includes a new one)

FTP already placed any new `database/migrations/*.sql` file on the server as
part of the regular deploy — this is just executing it:

```bash
ssh yourcpaneluser@yourhost -p <ssh-port>
cd ~/fintech-app
php database/migrate.php
```

If it's the *first* migration that needs fresh seed data (rare after initial
setup), follow the same migrate → seed → migrate sequence as §0.

---

## Troubleshooting

- **`migrate.php`/`seed.php` print nothing and exit non-zero:** production
  PHP configs usually have `display_errors` off, so fatal errors are
  swallowed silently. Re-run with `php -d display_errors=1 database/migrate.php`
  to see the real message.
- **cPanel's browser-based Terminal mangles multi-line pasted commands**
  (`Unsuccessful stat on filename containing newline` or similar): collapse
  the command to a single line before pasting, or run it from a real
  terminal (PowerShell/etc.) instead of the browser Terminal.
- **SSH public-key auth rejected, falls straight through to a password
  prompt**, even after importing + authorizing the key in cPanel's SSH
  Access page: on this host (parsicek.si/NeoServ), we confirmed the key
  content matched exactly, fixed `~/.ssh` (700) and `authorized_keys` (600)
  permissions (cPanel's own key-import UI had left `authorized_keys` world-
  readable, `644` — a common cause of exactly this symptom), and it was
  *still* rejected outright (server goes straight from "Offering public key"
  to "next method: password", no partial progress). That points to a
  server-side policy (`PubkeyAuthentication` disabled or restricted for this
  account) that only host support can fix — not something fixable from the
  account side. This is *why* deploy uses FTP instead of SSH; revisit
  SSH-based deploy if support ever resolves this.
- Also separately hit: the account's SSH access needed an explicit
  "activation" step in the **My NEOSERV** client portal (distinct from the
  cPanel key UI) before *any* SSH auth worked at all, key or password check
  that first if a fresh account can't connect at all (not the "falls
  through to password" symptom above, but "connection timed out"/refused).
- **Repeated failed SSH attempts get your IP firewalled** (`Connection
  closed` with no auth prompt at all): CSF/fail2ban-style protection,
  common on CloudLinux cPanel boxes. Wait 15–30 min, or ask host support to
  unblock the IP.

## What this deliberately doesn't do

- No automatic migrations — see the callout at the top. Revisit if SSH-based
  deploy ever becomes viable, or if a secured HTTP migration-trigger
  endpoint is added later.
- No zero-downtime/blue-green switch, no build artifacts/releases directory
  — files overwritten in place. Fine at this scale (single small app, brief
  window per deploy); revisit if downtime during deploy ever actually matters.
- No automatic rollback on failure. If a deploy breaks something, redeploy
  the last good commit (`workflow_dispatch` after `git revert`, or push a
  fix) rather than manually patching the server.
- No staging environment. Revisit once there's a second environment worth
  having.
