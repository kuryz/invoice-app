# NairaBill installation (on FJFrame 9.0)

## Requirements
- PHP 8.1+, PDO MySQL, JSON and mbstring extensions
- MySQL 8.0+ or MariaDB with JSON support
- HTTPS in production

## Install
1. Upload/deploy the framework as usual. Keep the framework's existing routes; NairaBill routes are registered from `app/Routes/invoice.php`.
2. Create a database and set `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env`.
3. Import `database/migrations/20261014_create_invoice_platform.sql` into that database.
4. Set `APP_URL` to the public origin (no trailing slash), `APP_DEBUG=false`, and `APP_ENV=production`.
5. Set `INVOICE_PLATFORM_FEE_PERCENT` and `INVOICE_PLATFORM_FEE_FIXED` as applicable. Defaults are 1.5% and 0 in the current implementation. Confirm applicable rules/provider terms before using fee pass-through.
6. Create a workspace and API key from the project root: `php bin/create-invoice-key.php "Example Business" billing@example.com test`. The key is displayed once. Store it in a secret manager/environment variable. Use `live` only when you are ready for production.
7. Point the web server document root at `public/`. Ensure rewrite rules route non-file paths to `public/index.php` (Apache config example below).
8. Visit `/invoice-platform` for the product page and `/pay/{public_id}` for the hosted invoice display.

## Apache rewrite
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

## Smoke test
```bash
curl -X POST 'https://your-domain.example/api/v1/billing/invoices' \
 -H 'Content-Type: application/json' \
 -H 'X-API-Key: nb_test_REPLACE_WITH_KEY' \
 -d '{"customer_name":"Test Customer","customer_email":"test@example.com","currency":"NGN","fee_payer":"split","items":[{"description":"Service","quantity":1,"unit_price":10000}]}'
```

## Production readiness boundary
This package delivers the invoice data model, fee allocation, API-key-scoped endpoints, hosted invoice display, migration, API documentation and workspace-key bootstrap. It is **not a live payment processor yet**: there is no gateway checkout/session creation, provider webhook verification, actual fee collection/split settlement, email delivery or dashboard login/API-key management UI. Integrate a supported gateway, verify signed callbacks server-side, make payment updates idempotent and test on sandbox before accepting real payments. The existing framework's `.env` must not be shipped publicly; rotate any credentials that were included in development archives.
