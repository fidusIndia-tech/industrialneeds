# Deployment Runbook — Phase 1 (Hardening + SEO) & Phase 2 (Catalog Scale)

This document describes how to deploy the changes on branch `feature/catalog-scale`
to production. It covers both phases (the branch contains all of Phase 1 and Phase 2).

> **Read section 2 first.** There is one step that, if skipped, makes every
> category page show zero products.

---

## 1. What's in this release

**Phase 1 — Hardening & SEO**
- Login/auth rate-limiting (admin/seller/customer).
- DB dumps can no longer be committed (`*.sql` gitignored).
- Production-safe `.env.example` defaults.
- Static sitemap generator (`sitemap:generate`) + re-enabled `/sitemap.xml`, fixed `robots.txt`.
- Product structured data (JSON-LD), real meta descriptions, canonical tags.
- Clean, collision-checked product slugs (no random suffix) for new products.

**Phase 2 — Catalog Scale**
- Indexed `product_categories` pivot replacing the slow `category_ids` JSON scans
  (category browse no longer loads the whole catalog into PHP).
- Centralized, FULLTEXT-ready product search (LIKE fallback).
- Cached front-end mega-menu category tree.
- Meilisearch scaffolded via Laravel Scout — **optional, off by default**.

**Two honest notes**
- **Search engine is optional.** With `SCOUT_DRIVER=null` (the default) search uses
  the database, exactly as before. Meilisearch (typo-tolerance, relevance) is turned
  on only when you provision it (section 7).
- **Native MySQL FULLTEXT** may not build on some MySQL 8.0.x versions (a known
  online-DDL bug). The migration is best-effort: it logs a warning and continues,
  and search falls back to LIKE. This does not block deployment.

---

## 2. ⚠️ Critical ordering

The new code makes category pages read from a new table, `product_categories`,
which does **not** exist in production yet. You **must** run, in this order:

1. `php artisan migrate --force` — creates the table
2. `php artisan catalog:sync-product-categories` — fills it from existing data

Until step 2 finishes, category pages will show **no products**. Always run these
back-to-back, inside maintenance mode (section 4).

---

## 3. Production `.env` changes (the real Phase 1 hardening)

The repository `.env` is gitignored, so hardening only takes effect when you edit
the **server's** `.env`:

```
APP_ENV=production
APP_DEBUG=false

# Set to your real production domain — used to build sitemap URLs.
SITEMAP_BASE_URL=https://industrialsupply.in

# Leave as null unless you have provisioned Meilisearch (see section 7).
SCOUT_DRIVER=null
SCOUT_QUEUE=false
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=
```

Also on the server, outside the application config:

- **Delete** `industrialneeds_db.sql` if it exists in the web root (a full DB dump
  must never be web-accessible).
- **Rotate** the three exposed API keys — Mouser, DigiKey, Element14 — and store the
  new values in the server `.env`.
- **Confirm OPcache is enabled** in the production PHP (`php -i | grep opcache.enable`).
  Most production LAMP stacks already have it; it is the single biggest page-speed win.

---

## 4. Deploy steps (run on the production server, in order)

```bash
# 0. Maintenance mode (avoids serving half-migrated pages)
php artisan down

# 1. Get the new code (after merging the PR into main)
git pull origin main

# 2. Install dependencies — REQUIRED: this release adds Scout + Meilisearch packages.
#    Skipping this causes a "Class Laravel\Scout\Searchable not found" fatal error.
composer install --no-dev --optimize-autoloader

# 3. Database — CRITICAL ORDER (see section 2)
php artisan migrate --force
php artisan catalog:sync-product-categories

# 4. SEO — build the static sitemap
php artisan sitemap:generate

# 5. Rebuild caches for production
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Back online
php artisan up
```

Then **restart PHP** so OPcache loads the new code:

```bash
sudo systemctl restart php8.1-fpm     # or your PHP-FPM version
# or, for Apache mod_php:
sudo service apache2 restart
```

> The first request after a restart is slow (OPcache compiles the codebase once).
> Every request after that is fast. This is normal.

---

## 5. Scheduler (daily sitemap + image pipeline)

Confirm this single cron entry exists on production (`crontab -e`):

```
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

This regenerates the sitemap daily (02:00) and runs the background image jobs.

**Also re-run `php artisan sitemap:generate` after any large product import**, so
new products appear in the sitemap immediately rather than waiting for the daily run.

---

## 6. SEO finalization (Google)

- In **Google Search Console**, submit `https://<your-domain>/sitemap.xml`.
- Verify `https://<your-domain>/sitemap.xml` loads in a browser and lists product URLs.

---

## 7. (Optional) Enable Meilisearch for real search

Only do this when you want typo-tolerant, relevance-ranked search. Otherwise leave
`SCOUT_DRIVER=null` — the database search works fine.

1. Run a Meilisearch service on the server (or use a managed instance).
   Example with the Linux binary:
   ```bash
   ./meilisearch --master-key "<a-strong-key>" --db-path ./data.ms --http-addr 127.0.0.1:7700 --env production
   ```
   Run it under a process manager (systemd / supervisor) so it survives reboots.

2. Server `.env`:
   ```
   SCOUT_DRIVER=meilisearch
   MEILISEARCH_HOST=http://127.0.0.1:7700
   MEILISEARCH_KEY=<the same key>
   SCOUT_QUEUE=true          # recommended: index in the background (needs a queue worker)
   ```

3. Apply settings + import all products:
   ```bash
   php artisan config:cache
   php artisan search:reindex-products
   ```

4. **Re-run `php artisan search:reindex-products` after each bulk import** — bulk
   import inserts rows directly and bypasses Scout's automatic indexing.

If Meilisearch goes down, search automatically falls back to the database (it will
not error). Note: with `SCOUT_DRIVER=meilisearch`, product *saves* try to index
synchronously unless `SCOUT_QUEUE=true`, so keep the service running or use the queue.

---

## 8. Post-deploy verification

- A category page shows products → confirms the backfill ran.
- `https://<your-domain>/sitemap.xml` loads and lists products.
- Open a product page → "View Source" contains `application/ld+json` and a real
  `<meta name="description">` (a sentence, not the slug).
- Second page load feels fast → OPcache is active.
- (If Meilisearch enabled) a misspelled search term still returns results.

---

## 9. Rollback

If something goes wrong:

```bash
php artisan down
git checkout <previous-commit-or-tag>
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
php artisan up
```

The `product_categories` table and the FULLTEXT index are additive — leaving them in
place is harmless after a code rollback. To fully reverse the schema:
`php artisan migrate:rollback --step=2`.
