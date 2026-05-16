# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

IndustrialNeeds is a multi-vendor B2B ecommerce platform built on **Laravel 8 / PHP 7.4–8.0**, served locally via **Laragon** (MySQL). It supports three user roles — Admin, Seller, and Customer — each with their own controllers, middleware, and route files.

---

## Common Commands

```bash
# Serve (Laragon handles this — start Laragon, not artisan serve)

# Run all migrations
php artisan migrate

# Roll back last migration
php artisan migrate:rollback

# Run a specific migration file
php artisan migrate --path=database/migrations/2026_05_15_145028_add_product_code_to_products_table.php

# Clear all caches (do this after changing config or routes)
php artisan optimize:clear

# Run the full test suite (uses SQLite in-memory)
php artisan test
# or
./vendor/bin/phpunit

# Run a single test file
./vendor/bin/phpunit tests/Feature/ExampleTest.php

# Compile frontend assets (development, with watch)
npm run dev
npm run watch

# Compile for production
npm run prod
```

---

## Architecture

### Role separation

Each user role maps to its own layer:

| Layer | Namespace | Routes file | Middleware |
|---|---|---|---|
| Admin | `App\Http\Controllers\Admin` | `routes/admin.php` | `AdminMiddleware` |
| Seller | `App\Http\Controllers\Seller` | `routes/seller.php` | `SellerMiddleware` |
| Customer | `App\Http\Controllers\Customer` | `routes/customer.php` | `CustomerMiddleware` |
| Public web | `App\Http\Controllers\Web` | `routes/web.php` | `maintenance_mode` |
| REST API | — | `routes/api/` | `SellerApiMiddleware`, Passport |

Admin routes are guarded by `module:*` middleware (`ModulePermissionMiddleware`) that checks which sections an employee role is allowed to access.

### CPU helper layer (`app/CPU/`)

Business logic lives in autoloaded static classes — **not** in models or controllers:

- `Helpers` — `get_business_settings()`, `combinations()` (generates variant permutations), `pagination_limit()`, `translate()` wrapper
- `BackEndHelper` — currency conversion (`currency_to_usd`, `usd_to_currency`)
- `ProductManager` — product queries used by the API and web frontend
- `ImageManager` — `upload()`, `update()`, `delete()` against the `public` Storage disk
- `CategoryManager`, `BrandManager`, `CartManager`, `OrderManager`, `CustomerManager`

Always use these static helpers rather than duplicating their logic in controllers.

### Business settings

All site-wide configuration (currency model, default language, company name, etc.) is stored in the `business_settings` table as `{type, value}` key-value rows. Read them with `Helpers::get_business_settings('key')`. Frequently-read keys (`currency_model`, `system_default_currency`, `language`, `company_name`, `decimal_point_settings`, `currency_symbol_position`) are cached in the session automatically.

### Multi-language / translation

All user-facing strings must use `\App\CPU\translate('key')` (aliased as the global `translate()` function in Blade views). The function:
1. Looks up the key in `resources/lang/{locale}/messages.php`.
2. If absent, auto-inserts it with a humanised default and returns it.

In views use `{{\App\CPU\translate('Some Label')}}`. Never use Laravel's `__()` directly for UI strings.

### Product model — key design decisions

**Category hierarchy:** Products store their categories as a JSON array in `category_ids`: `[{"id":"1","position":1}, {"id":"5","position":2}, {"id":"12","position":3}]`. Position 1 = top-level, 2 = sub, 3 = sub-sub. When creating or editing a product you must build this array manually.

**Variants / SKU combinations:** Colors and choice attributes are stored as JSON in `colors`, `choice_options`, and `variation` columns. `Helpers::combinations()` generates every permutation from the option arrays and each combination becomes a row in `product_stocks`.

**Scopes:** `Product::active()` filters by brand active + product published + seller approved. `Product::scopeSellerApproved()` includes both seller products (seller status = `approved`) and admin-added in-house products. Always use `->active()` on public-facing queries.

**Translations:** `name` and `details` are stored in English on the product row and in other locales via the polymorphic `translations` table (`App\Model\Translation`). The `getNameAttribute` and `getDetailsAttribute` accessors return the translated value automatically on non-admin/non-seller URLs.

**`product_code`:** A nullable string column added in May 2026 to store manufacturer part numbers. It is searched alongside `name` in both the web search and the API search. During bulk import it is treated as an optional field and checked for duplicates against all existing `product_code` values before inserting.

### Bulk product import (2-step, session-based)

1. **Step 1 — Preview** (`POST /admin/product/bulk-import-preview`): Parses the Excel file with `FastExcel`, validates each row (required fields, brand ID, category IDs, duplicate `product_code`), stores the cleaned array in `session('product_import_data')`, and renders the preview table.
2. **Step 2 — Confirm** (`POST /admin/product/bulk-import`): Reads the session array, downloads `thumbnail_url` and `gallery_urls` (comma-separated) via `file_get_contents`, saves images to `storage/app/public/product/`, then bulk-inserts with `DB::table('products')->insert()`. `set_time_limit(0)` is called because image downloading can be slow.

If the session expires between steps the user is redirected to start again.

### Inquiry system

`App\Inquiry` (in the root `App\` namespace, not `App\Model\`) stores product enquiry leads. Admin routes are under `admin/inquiries` (guarded by `module:support_section`). The model uses standard `$fillable` mass assignment; no Seller-side access exists yet.

### Image storage

All images are stored on the `public` disk (`storage/app/public/`). Thumbnails go to `product/thumbnail/`, gallery images to `product/`. Use `ImageManager::upload()` / `ImageManager::update()` for single-file uploads from form inputs. Bulk import uses `Storage::disk('public')->put()` directly since the source is a URL, not an uploaded file.

### Payment gateways

Each gateway has its own controller (`StripePaymentController`, `RazorPayController`, `PaypalPaymentController`, etc.) and its credentials are stored in `business_settings`. Do not hardcode credentials; always read them via `Helpers::get_business_settings()`.
