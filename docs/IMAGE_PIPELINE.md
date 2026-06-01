# Product Image Pipeline

Background system that lets products go live with a placeholder image, then fetches real product
photos by manufacturer part number from distributor APIs — with family reuse, strict matching, a
review queue, and a live admin dashboard.

## Lifecycle (`products.image_status`)
```
placeholder ──dispatch──▶ queued ──job──▶ fetched | reused | manual_review | failed
     ▲                              │
     └────────── quota hit ─────────┘   (reset, retried next cycle)
```
- **placeholder** – on the default image, needs dispatching
- **queued** – a fetch job is enqueued (in flight)
- **fetched** – image downloaded from a provider (`image_source` = digikey/element14/…)
- **reused** – image reused from the same family (`product_image_assets`), no API call
- **manual_review** – no trusted image found anywhere (kept placeholder, in the review export)
- **failed** – the job errored repeatedly

Safety: the job **never overwrites a real or manually-uploaded image** (only acts on `def.png` /
`placeholder` / `queued` / `failed` / `manual_review`, unless `--allow-overwrite`).

## Providers (`app/Services/ImageProviders/`)
Chain order in `FetchProductImageJob`: **DigiKey → element14 → manual (stub)**. Each only accepts an
**exact MPN match** whose manufacturer matches the brand or a known equivalent (Telemecanique ≡
Schneider), and a **downloadable** image (MIME-validated).

| Provider | Quota (free) | Image CDN | Notes |
|---|---|---|---|
| DigiKey | 1,000 / day | downloadable | primary |
| element14 / Farnell | free API key | downloadable | covers DigiKey misses |
| Nexar / Octopart | ~100 / day | downloadable | disabled (too small) |
| Mouser | 1,000 / day | **Akamai-blocked** | data only, no usable images |

### `.env` keys
```
DIGIKEY_CLIENT_ID=...
DIGIKEY_CLIENT_SECRET=...
ELEMENT14_API_KEY=...
ELEMENT14_STORE=in.element14.com        # or uk.farnell.com / www.newark.com
# ELEMENT14_IMAGE_BASE=                  # only if the derived image host 404s
# MOUSER_API_KEY=...                     # optional (classification only)
QUEUE_CONNECTION=sync                    # global stays sync; image jobs use the 'database' connection
CACHE_DRIVER=file                        # required for withoutOverlapping locks
APP_URL=https://yourdomain.com           # CRITICAL: image URLs are APP_URL + /storage/app/public/...
```

## Commands
```bash
# enqueue background fetches for products needing an image
php artisan products:dispatch-image-jobs --limit=1000 [--include-failed] [--include-review]

# provider-scoped retry: sweep review items through ONE provider only, skipping products that
# already tried it (e.g. give DigiKey-misses an Element14 shot WITHOUT re-burning DigiKey quota)
php artisan products:dispatch-image-jobs --include-review --provider=element14 --limit=200

# process the queue (a short-lived worker)
php artisan queue:work database --queue=images --tries=3 --stop-when-empty --max-time=55

# one-time backfill of image_status + family keys for existing products (dry-run by default)
php artisan products:normalize-image-status [--execute]

# build an HTML contact-sheet of fetched images to spot-check
php artisan products:image-gallery [--source=element14] [--status=fetched|manual_review|any]
```

## Admin
- Dashboard + live progress: **`/admin/product/image-pipeline`**
- Review export (CSV of `manual_review`/`failed`): button on the dashboard, or
  `/admin/product/image-pipeline/review-export`

## Scheduler (hands-off)
Registered in `app/Console/Kernel.php`:
- `01:00 daily` → dispatch ~1,000 (a day's DigiKey quota) of the `placeholder` backlog
- `every minute` → drain the `images` queue with a short worker (`withoutOverlapping`, `stop-when-empty`)

The OS must run the Laravel scheduler every minute:

**Hostinger / cPanel (cron):**
```
* * * * * cd /home/uXXXX/domains/YOURDOMAIN/public_html && /usr/bin/php artisan schedule:run >> storage/logs/cron.log 2>&1
```
(Use the real path from `pwd` and php binary from `which php`.)

**Windows / Laragon (local):** a Task Scheduler entry running
`php <project>\artisan schedule:run` every 1 minute.

## Monitoring
```bash
php artisan schedule:list                 # confirm the two scheduled tasks
php artisan queue:failed                  # failed jobs (retry: queue:retry all)
tail -f storage/logs/laravel.log | grep FetchProductImageJob
```
