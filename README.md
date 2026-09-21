# Inventory Management System

A Laravel 13 inventory management system: providers, categories, products,
storages, and clients, with full FIFO-based purchasing, sales, refunds, and
profit reporting.

## Stack

- PHP 8.3, Laravel 13
- SQLite (default, via `database/database.sqlite`)
- Tailwind CSS v4 + Vite for the UI
- Pest for testing
- [Scramble](https://scramble.dedoc.co) for auto-generated OpenAPI/Swagger docs

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm install
npm run build
```

## Running

```bash
composer run dev
```

This starts the PHP server, queue listener, log tailer (`pail`), and the
Vite dev server together. Alternatively, run `php artisan serve` and
`npm run dev` separately.

The web UI is served at `/` and the API under `/api`. The OpenAPI spec
(`api.json`, gitignored) is regenerated automatically whenever `php artisan
serve` or `composer run dev` starts.

## API Documentation

Interactive Swagger UI: **`/docs/api`**
Raw OpenAPI spec: **`/docs/api.json`**

Docs are generated automatically from route definitions, Form Request
validation rules, and API Resources — no manual annotations required.

## Business Domain

| Concept | What it represents |
|---|---|
| **Provider** | A supplier you purchase stock from |
| **Category** | Groups products under a provider; supports nested sub-categories |
| **Product** | A sellable item, with a `sale_price` snapshot used for new orders |
| **Storage** | A warehouse or shop that holds stock |
| **Client** | A customer you sell products to |
| **Batch** | One purchase from a provider, containing one or more `BatchItem`s at a fixed unit cost |
| **StorageStock** | Current total quantity of a product in a storage |
| **StockMovement** | Immutable ledger entry for every stock change (purchase, sale, provider refund, client refund) |
| **ClientOrder** | A sale to a client, allocated across batches via FIFO |
| **ClientOrderAllocation** | Records which batch (and at what cost) fulfilled part of an order item |
| **ProviderRefund** / **ClientRefund** | Returns to a provider or from a client, scoped to a single batch / order |

### FIFO allocation

Orders never specify a batch or cost — only `product_id` and `quantity`.
`OrderService` locks and consumes the oldest available `BatchItem`s first,
recording one `ClientOrderAllocation` per batch touched. If stock is
insufficient across all batches, the entire order rolls back.

### Refunds

- **Provider refunds** are scoped to a single batch and can't exceed that
  batch's available quantity.
- **Client refunds** restore units to the *exact* batch/allocation they were
  originally sold from (never a different batch), and can't exceed what's
  still refundable per order item.

### Profit

Computed per batch from actual FIFO allocations, net of client refunds —
never from the product's current `sale_price`:

```
revenue = net_quantity × order_item.unit_price   (price at time of sale)
cost    = net_quantity × allocation.unit_cost    (batch's purchase cost)
profit  = revenue − cost
```

### Historical stock

`GET /api/storage/stock?date=...` reconstructs quantities from the
`stock_movements` ledger as of a given date, rather than reading the live
`storage_stocks` snapshot — so past stock levels stay accurate even after
later purchases, sales, or refunds.

### Soft deletes

`Provider`, `Category`, `Product`, `Storage`, and `Client` use soft deletes
(`deleted_at`). They're referenced by `restrictOnDelete()` foreign keys
elsewhere (batches, orders, etc.), so a hard delete would fail once a
record is in use — soft delete hides it from lists while keeping
referential integrity and letting it be restored from the database if
needed.

## Architecture

```
Controller → Form Request → Service → Eloquent
```

- **Controllers** (`app/Http/Controllers/Api`, `.../Web`) stay thin.
- **Form Requests** own validation.
- **Services** (`app/Services`) own business logic and transactions:
  `PurchaseService`, `OrderService`, `ProviderRefundService`,
  `ClientRefundService`, `InventoryService`, `BatchProfitService`.
  Every inventory-changing operation runs inside `DB::transaction()` with
  `lockForUpdate()` on the rows it touches, so concurrent requests can never
  push stock negative.
- Simple CRUD (providers, categories, products, storages, clients) skips
  the service layer and goes straight from controller to Eloquent — no
  transaction/locking is needed for a single-row create/update/delete.

## Web UI

Server-rendered Blade pages, each posting to the JSON API via `fetch`
(see `resources/js/app.js` for the shared helper):

| Route | Purpose |
|---|---|
| `/` | Dashboard — stock across all (or one) storage, quick profit snapshot |
| `/clients`, `/providers`, `/categories`, `/products`, `/storages` | CRUD with inline edit/delete |
| `/purchases/create` | Record a purchase batch |
| `/orders/create` | Create a FIFO order (product + quantity only) |
| `/provider-refunds/create` | Refund units from a batch to its provider |
| `/client-refunds/create` | Refund units from a client order |
| `/batches/profit` | Profit per batch |

## Testing

```bash
php artisan test
# or a single file/filter:
php artisan test --filter=OrderServiceTest
```

The suite runs against an in-memory SQLite database (see `phpunit.xml`) and
never touches `database/database.sqlite`.

## Code style

```bash
vendor/bin/pint
```
