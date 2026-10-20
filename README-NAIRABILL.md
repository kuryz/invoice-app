# NairaBill — API-first invoice platform

NairaBill helps SaaS products, marketplaces and service businesses create invoices from their own platforms and choose who bears the platform transaction fee: customer, merchant, or split. The package is integrated into the supplied FJFrame 9.0 PHP framework.

## Included
- Tech-focused product landing page at `/` (also `/invoice-platform`)
- Hosted invoice display at `/pay/{public_id}`
- Workspace-scoped `nb_test_` / `nb_live_` API keys
- Create, list, retrieve and cancel invoice API endpoints
- Invoice items, tax, discounts, due date, external ID and metadata
- Configurable fee calculation and payer allocation
- SQL migration, API docs, installation guide and CLI workspace/key bootstrap
- Platform admin console at `/admin/platform` for workspaces, invoices, API keys, webhooks, events, paid volume and fee metrics
- Admin actions to suspend/reactivate workspaces and revoke API keys; admin login at `/admin/login`

## Important
Invoice creation and fee allocation work independently of a payment gateway. The hosted invoice page is not a checkout page yet; no funds are collected, settled, or marked paid automatically. The platform console expects an active legacy `users` account with `groupp=1` and `status=1`; use your framework administrator account. See `docs/INSTALL.md` and `docs/API.md` for the integration boundary and launch checklist.

## Security
The provided `.env` contains placeholders only. Copy `.env.example` to `.env` and configure real secrets privately. Do not commit or serve `.env` from the web root. If the source archive you supplied contained real credentials, rotate them.


## Customer business portal

- Register: `/customer/register`
- Passwordless sign in: `/customer/login` (email magic link)
- Business dashboard: `/customer/dashboard`
- Invoice list: `/customer/invoices`
- Dashboard supports manual invoice creation, hosted invoice links, workspace statistics, API key creation and API key revocation.
- Customer accounts are stored in `invoice_workspace_users`, separate from the framework's existing general-purpose `users` table. Registration creates a workspace and owner account.
- Run the updated migration `database/migrations/20261014_create_invoice_platform.sql` before enabling customer registration. Existing installations can re-run it because the statements use `CREATE TABLE IF NOT EXISTS`.
- API keys are displayed only once after generation; only a SHA-256 hash is stored. Use HTTPS and keep keys on your server.

Note: Hosted invoice pages are not a payment gateway by themselves. Integrate and verify a payment provider before treating invoices as paid or accepting live money.


## Passwordless merchant sign-in (updated)

Merchant registration and login use emailed, single-use magic links instead of passwords. Links expire after 15 minutes; only a SHA-256 token hash is stored in `invoice_magic_links`. The login response intentionally does not reveal whether an email address exists.

1. Run the updated `database/migrations/20261014_create_invoice_platform.sql` migration. It now creates `invoice_magic_links` after `invoice_workspace_users`.
2. Set `APP_URL` to the exact public HTTPS URL.
3. Configure outbound email on the server (PHP `mail()` must be able to hand messages to a working MTA/hosting mail service). Set `MAIL_FROM_ADDRESS` and `MAIL_FROM_NAME` in `.env`; use a verified sender address and configure SPF/DKIM/DMARC for reliable delivery.
4. Visit `/customer/register` to create a workspace. Open the email link and confirm sign-in; `GET /customer/magic-link` displays a confirmation page and `POST /customer/magic-link` consumes the token. This reduces accidental token consumption by email link scanners.
5. Test expiry, replay prevention, disabled users, and email delivery on staging before launch.

Magic-link email delivery uses PHP's native `mail()` transport, not a bundled SMTP provider. If your hosting does not configure `mail()`, configure the host's mail transport or replace `InvoiceMagicLinkMailer` with your transactional provider adapter. Do not log or store raw magic-link tokens.
