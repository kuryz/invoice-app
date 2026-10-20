# NairaBill API

Base URL: `https://your-domain.example/api/v1/billing`  
Authentication: `X-API-Key: nb_test_...` or `X-API-Key: nb_live_...`. Use the key only on your backend. API keys are stored as SHA-256 hashes.

## Create an invoice

`POST /invoices` — JSON body:

```json
{
  "customer_name": "Jordan Okafor",
  "customer_email": "jordan@example.com",
  "customer_phone": "+2348000000000",
  "currency": "NGN",
  "invoice_number": "ORD-1001",
  "external_id": "order_1001",
  "description": "Website project milestone",
  "items": [
    {"description": "Website design", "quantity": 1, "unit_price": 150000},
    {"description": "Maintenance", "quantity": 2, "unit_price": 12500}
  ],
  "tax_rate": 0,
  "discount": 0,
  "fee_payer": "customer",
  "due_date": "2026-11-01",
  "success_url": "https://merchant.example/paid",
  "cancel_url": "https://merchant.example/checkout",
  "metadata": {"order_id": "ORD-1001"}
}
```

`fee_payer` values: `customer` (customer bears platform fee), `merchant` (merchant bears it), `split` (fee split 50/50). The current configurable fee formula is `amount × INVOICE_PLATFORM_FEE_PERCENT / 100 + INVOICE_PLATFORM_FEE_FIXED`. This only calculates and records the fee split; it does not charge or transfer funds.

Response includes `public_id`, invoice status, invoice total (`total_amount`), customer payable total including their fee share (`amount_due`), fee breakdown and `hosted_url`. The current hosted page displays invoice details. **A payment gateway must be integrated and verified before it can collect payments or mark invoices paid.** Never mark an invoice paid based on a browser redirect alone.

## List invoices

`GET /invoices?limit=20&offset=0` (limit 1–100).

## Get invoice

`GET /invoices/{public_id}` returns the invoice and its items for the authenticated workspace.

## Cancel invoice

`POST /invoices/{public_id}/cancel` only cancels invoices whose status is `unpaid`.

## Errors

Responses follow `{ "success": false, "message": "..." }`; validation errors generally use HTTP 422, missing authentication 401, missing invoice 404 and state conflicts 409.

## Fee and compliance notes

Fee rates are configurable via environment variables. Configure them only after checking payment-provider terms and applicable law; disclose fees before payment. Confirm whether fee passing/splitting is allowed in the relevant jurisdiction and payment method. Currency is currently an ISO-like 3-letter label; no FX conversion is performed. Amounts are represented to two decimal places. Before production launch, add rate limiting, idempotency-key enforcement, audit/retention policy, queued signed webhook delivery, gateway callbacks, automated tests and monitoring.
