# Footstep source and public catalog snapshot
Snapshot:22 November2026. Laravel backend and Next.js storefront from the active new VPS. The retired old VPS is not a runtime dependency.

## Included
- Laravel application/routes/views/configuration, Composer lock.
- Next.js source/assets, npm lock, attributed GeoIP dataset.
- data/schema.sql:103-table schema only; no private rows.
- data/public-catalog.json:506 published public products, public variants/categories/brands/attributes and sanitized upload mappings. No purchase cost, stock counts, sales totals or uploader identity.
- Publicly referenced product/brand/category images, content-hashed filenames.

## Deliberately excluded
No real customer/order/payment records, private invoices, credentials, .env, API tokens, app/signing keys, sessions, outboxes, logs, database backups, server SSH/TLS keys, node_modules, vendor or generated builds. This public repository is not a private disaster-recovery backup. Real business data and secrets stay in separate private backups.

## Setup
Use PHP8.3 with required Composer extensions, MariaDB10.11, Node.js and npm. Install Laravel dependencies with composer install --no-dev. Copy laravel/.env.example to laravel/.env privately, configure your own database and generate a fresh application key. Create writable Laravel storage/{app,framework/cache,framework/sessions,framework/views,logs} and bootstrap/cache directories. Import schema.sql into an EMPTY database only. Configure your own admin identity privately. No production account is bundled.

The public-catalog JSON is a sanitized reference export, not a complete live SQL restore. Public upload IDs and product references are retained; images live under laravel/public/uploads/public-catalog/. Populate catalog rows with an import adapted to the included schema and explicit defaults for excluded fields. Do not treat omitted stock quantities as available inventory.

For Next.js: cd nextjs; npm ci; copy .env.example to .env.local privately and configure your backend origin; npm run build; npm start. Regional UI requires trusted reverse-proxy visitor IP forwarding and a valid locally managed interface flag, as implemented in source. Fail-closed fallback remains domestic when configuration is absent.

Do not enable courier/SMS/payment/Meta workers until your own credentials, guards and webhook origins have been configured and verified. No live credentials or dispatching setup is provided.
