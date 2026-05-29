# Khoobie deployment playbook

This document covers two things:

1. **First-time install** on a fresh server (one-time)
2. **Ongoing deploys** of bug-fixes & features without breaking the live site (every push)

> If you only remember one rule: **never edit code directly on production**. Everything goes through git → atomic deploy → instant rollback if needed.

---

## 1. First-time install on a fresh server

### Server requirements (minimum)

| Component | Version | Why |
|---|---|---|
| OS | Ubuntu 22.04 / Debian 12 / CentOS Stream 9 | Standard Linux LAMP target |
| RAM | 2 GB minimum, 4 GB recommended | PHP-FPM + MySQL + image processing headroom |
| Disk | 20 GB | App ~100 MB, DB ~50 MB at launch, uploads grow |
| PHP | 8.2+ with FPM | CodeIgniter 4.7 requires 8.1+ |
| PHP extensions | `mysqli intl mbstring curl openssl gd zip fileinfo iconv xml bcmath` | All checked by `setup.php` |
| MySQL / MariaDB | 8.0 / 10.6 | utf8mb4 + JSON columns required |
| Webserver | Apache 2.4 or Nginx 1.22 | Either works; Apache needs `mod_rewrite` |
| Node | 18+ | ONLY needed if you build assets on the server (recommend: build locally) |
| Composer | 2.x | Dependency manager |

### One-shot install

```bash
# 1) On your local machine: package up the deploy bundle
git archive --format=zip --output=khoobie-bundle.zip HEAD
zip -g khoobie-bundle.zip khoobie-db-backup.sql setup.php

# 2) Upload to server (example: rsync over SSH)
scp khoobie-bundle.zip user@khoobie.com:/tmp/

# 3) On the server: unpack into the web root
ssh user@khoobie.com
sudo mkdir -p /var/www/khoobie && cd /var/www/khoobie
sudo unzip /tmp/khoobie-bundle.zip
sudo chown -R www-data:www-data .
sudo chmod -R 755 .
sudo chmod -R 775 writable

# 4) Install PHP dependencies + build assets (or skip if assets already built locally)
composer install --no-dev --optimize-autoloader
# If public/assets/ wasn't included in the zip:
# npm ci && npm run build

# 5) Configure Apache / Nginx to point at public/
#    Apache vhost example below.

# 6) Open https://your-domain.com/setup.php in a browser
#    Walk through the 7 steps — env check, DB import, .env config, admin user, lock.

# 7) After step 7: DELETE the installer and DB backup
sudo rm /var/www/khoobie/setup.php /var/www/khoobie/khoobie-db-backup.sql

# 8) Set up cron jobs (see bottom of this file)
```

### Apache vhost (most Indian hosting uses Apache)

```apache
<VirtualHost *:443>
    ServerName khoobie.com
    ServerAlias www.khoobie.com
    DocumentRoot /var/www/khoobie/current/public

    <Directory /var/www/khoobie/current/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # User uploads — served directly, not through PHP
    Alias /uploads /var/www/khoobie/shared/writable/uploads
    <Directory /var/www/khoobie/shared/writable/uploads>
        Require all granted
        # Important: block PHP execution from the uploads dir
        php_flag engine off
    </Directory>

    SSLEngine on
    SSLCertificateFile      /etc/letsencrypt/live/khoobie.com/fullchain.pem
    SSLCertificateKeyFile   /etc/letsencrypt/live/khoobie.com/privkey.pem

    # Long-cache built assets (Vite emits hashed filenames)
    <LocationMatch "^/assets/.+\.(js|css|woff2|png|jpg|svg|webp)$">
        Header set Cache-Control "public, max-age=31536000, immutable"
    </LocationMatch>

    ErrorLog  /var/log/apache2/khoobie-error.log
    CustomLog /var/log/apache2/khoobie-access.log combined
</VirtualHost>

# Redirect http → https
<VirtualHost *:80>
    ServerName khoobie.com
    ServerAlias www.khoobie.com
    Redirect permanent / https://khoobie.com/
</VirtualHost>
```

### Nginx vhost (if you prefer)

```nginx
server {
    listen 443 ssl http2;
    server_name khoobie.com www.khoobie.com;
    root /var/www/khoobie/current/public;
    index index.php;

    location / { try_files $uri $uri/ /index.php?$args; }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    }

    location /uploads/ {
        alias /var/www/khoobie/shared/writable/uploads/;
        location ~ \.php$ { deny all; }
    }

    location ~* /assets/.+\.(js|css|woff2|png|jpg|svg|webp)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    ssl_certificate     /etc/letsencrypt/live/khoobie.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/khoobie.com/privkey.pem;
}
server { listen 80; server_name khoobie.com www.khoobie.com; return 301 https://$host$request_uri; }
```

### After install: cron jobs

Open the web user's crontab (`sudo crontab -u www-data -e`) and add:

```cron
# Daily DB backup at 3am IST (30-day retention)
0 3 * * * /var/www/khoobie/current/deploy/backup-db.sh

# Abandoned-cart recovery (every 6 hours)
0 */6 * * * cd /var/www/khoobie/current && php spark cart:recover-abandoned

# Subscription renewals (daily 4am)
0 4 * * * cd /var/www/khoobie/current && php spark subscriptions:renew

# Marketplace price refresh for affiliate products (daily 5am)
0 5 * * * cd /var/www/khoobie/current && php spark affiliate:refresh-prices

# Newsletter queue flush (every 15 min)
*/15 * * * * cd /var/www/khoobie/current && php spark newsletter:send-batch
```

---

## 2. Ongoing deploys — the workflow

This is the part you asked about: **once the site is live with real customers, how do you push bug fixes without breaking things?**

### The mental model

```
┌─────────────────┐      git push       ┌──────────┐   ./deploy.sh   ┌──────────────┐
│  Your laptop    │ ──────────────────► │  GitHub  │ ──────────────► │ Production   │
│  (XAMPP local)  │                     │  (main)  │                 │ /var/www/    │
└─────────────────┘                     └──────────┘                 │   khoobie/   │
                                                                     │  ├ current → │
                                                                     │  │  releases │
                                                                     │  │  /2026-…  │
                                                                     │  └ shared/   │
                                                                     │     .env     │
                                                                     │     uploads/ │
                                                                     └──────────────┘
```

**Three things never live in git** (they live in `shared/` on the server):
1. `.env` — server-specific secrets
2. `writable/uploads/` — user-generated images, invoices, downloads
3. `writable/session/`, `writable/logs/`, `writable/cache/` — runtime state

Each new release is a fresh git clone into `releases/YYYY-MM-DD-HHMMSS/`. The deploy script symlinks the shared stuff into it, runs migrations, then **atomically flips `current → new-release`** with a single `mv -T` (zero downtime). If anything goes wrong, `./rollback.sh` flips it back in <1 second.

### Branching model

| Branch | Purpose | Who deploys |
|---|---|---|
| `main` | What's live in production | `./deploy.sh` |
| `staging` | What's on `staging.khoobie.com` for QA | auto-deploy on push |
| `feature/xxx` | Your day-to-day work | never deployed to prod |

Workflow:
```
1. Work locally on feature/cart-discount-fix
2. git push origin feature/cart-discount-fix
3. Open a PR to staging — review, merge — auto-deploys to staging
4. QA on staging.khoobie.com
5. Merge staging → main
6. SSH to prod and run: ./deploy/deploy.sh
```

### A single deploy, step by step (what `deploy.sh` does)

```
1. Take a fresh DB backup  (pre-deploy safety net)
2. git clone main into releases/2026-05-28-141533/
3. Symlink shared/.env, shared/writable/{uploads,session,logs,cache} into the release
4. composer install --no-dev --optimize-autoloader
5. php spark migrate --all          ← additive only, never edits existing data
6. php spark cache:clear
7. chown -R www-data + chmod 775
8. mv -T  releases/2026-05-28-141533/  →  current   (ATOMIC, <1ms)
9. systemctl reload php8.2-fpm      ← drops OPcache
10. Prune releases/ to last 3
```

### What if a deploy goes wrong?

**Code bug only (no DB changes):**
```bash
./deploy/rollback.sh
# Done. <1 second. Site is back on the previous release.
```

**Bad DB migration too:**
```bash
./deploy/rollback.sh
# Then restore the pre-deploy backup:
gunzip -c /var/www/khoobie/backups/khoobie-2026-05-28-141500.sql.gz | mysql -uroot khoobie
```

The pre-deploy backup taken in step 1 above is exactly for this scenario. **Every deploy carries its own safety net.**

### Migration discipline (the #1 thing that breaks production)

**Rules for `app/Database/Migrations/`:**

1. **Once a migration is shipped to production, NEVER edit it.** Add a new migration that fixes/extends.
2. Migrations must be **additive and backwards-compatible**. The old code still has to work for the few seconds during a deploy.
   - ✅ Add a new column with a default
   - ✅ Add a new table
   - ✅ Add an index
   - ❌ Drop a column (do it in two deploys: stop reading it → next release drops it)
   - ❌ Rename a column (same — copy to new column, deploy, switch reads, deploy, drop old)
3. **Test the migration on a fresh clone of production** before shipping:
   ```bash
   # On your local machine, pull a copy of prod DB:
   ssh prod 'cat /var/www/khoobie/backups/khoobie-$(date +%F)*.sql.gz' | gunzip > prod-copy.sql
   mysql -uroot khoobie_test < prod-copy.sql
   # Run the migration:
   php spark migrate --all -g default --db khoobie_test
   ```
4. **Long-running migrations:** anything that touches >100k rows should be a spark command run manually, NOT a migration that blocks a deploy.

### Asset building — local, not on prod

We commit `public/assets/app.js` and `public/assets/app.css` to git (the Vite build output) so the server never needs Node installed. Workflow:

```bash
# Local — before pushing
npm run build
git add public/assets/
git commit -m "rebuild assets"
git push
```

This makes deploys 5–10× faster and removes a whole class of "but it worked locally" failures.

### User uploads — never lose them

User-uploaded images, invoices, and download files live in `shared/writable/uploads/`. Because we symlink this into every release, **no deploy ever touches user files**. But you still need off-site backup:

```bash
# Add to cron: nightly sync to S3/R2/Backblaze (~1 GB free tier covers a year)
0 4 * * * aws s3 sync /var/www/khoobie/shared/writable/uploads/ s3://khoobie-uploads/ --storage-class STANDARD_IA --delete
```

### Feature flags for risky changes

For anything where you're worried about breaking conversions, add a feature flag in `.env`:

```php
// In code:
if (env('feature.new_checkout_flow', false)) {
    return $this->newCheckoutFlow();
}
return $this->oldCheckoutFlow();
```

Then on production, edit `shared/.env` and set `feature.new_checkout_flow = true` for 10% of users (or all), without a deploy. Easy off-switch if metrics dip.

### Monitoring after every deploy

5-minute post-deploy checklist:
1. `tail -f /var/www/khoobie/shared/writable/logs/log-$(date +%F).php` — watch for errors
2. Open the home page in incognito — does it render in <2s?
3. Add a test product to cart and complete checkout (use Razorpay test mode) — does it succeed?
4. Check `/admin` — dashboard loads with real numbers?

---

## TL;DR — the answer to your question

**"What's the best way to push bug fixes and new features to production?"**

```
1. Code → commit → push to GitHub (main branch)
2. SSH to production server
3. cd /var/www/khoobie && ./current/deploy/deploy.sh
4. Wait 30–60 seconds.
5. If anything goes wrong: ./current/deploy/rollback.sh — instant.
```

That's it. Atomic, reversible, never edits prod files directly, never touches user uploads or sessions, never loses customer data (pre-deploy backup runs first). The whole machinery is in `deploy/` — `deploy.sh`, `rollback.sh`, `backup-db.sh`. Read those three short scripts once and you'll know exactly what happens during a deploy.
