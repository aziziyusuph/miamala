# Miamala

**Miamala** is a transaction management application for recording, organizing, searching, and reconciling payments from multiple payment channels.

This branch contains the **Laravel rebuild** of the original Miamala application. The rebuild is being developed as a clean, maintainable Laravel application while preserving the core transaction-management purpose of the original project.

> **Development status:** M4 Phase 3A-3E is complete on the `laravel-rebuild` branch. The Laravel transaction module currently supports transaction CRUD, server-side lifecycle rules, search and filtering, filtered totals, pagination, CSV export, automatic reconciliation classification, a dedicated reconcile/unreconcile workflow, reconciliation-state filtering, reconciliation-state preservation during ordinary edits, business isolation, authorization, and soft deletion.

## What Miamala Does

Miamala is designed for businesses, schools, landlords, NGOs, online sellers, and other organizations that need a simple way to manage incoming payments.

### Current capabilities

* Record payment transactions
* Edit and delete transactions
* Track customer name and phone number
* Record payment provider/channel
* Store transaction IDs and order references
* Categorize transactions
* Track transaction status
* Record payment dates
* Store expected amounts for reconciliation
* Mark transactions as reconciled or unreconciled
* Search by customer name, phone number, transaction ID, or order reference
* Filter by provider, status, category, date range, and reconciliation state
* Enforce supported transaction lifecycle transitions server-side
* Classify reconciliation as unreconciled, exact match, underpaid, or overpaid
* Calculate filtered transaction counts and total amounts
* Paginate transaction records
* Export all matching filtered transactions to CSV
* Soft-delete transactions
* Preserve reconciliation state during ordinary transaction edits
* Isolate transactions by business and authorize transaction actions
* Seed the application with realistic sample transaction data
* Validate transaction business rules and prevent duplicate transaction IDs

### Supported payment channels

* M-Pesa
* Airtel Money
* Mixx by Yas
* Bank
* Cash
* Other

### Transaction statuses

* Pending
* Completed
* Failed
* Refunded

### Transaction lifecycle

Supported lifecycle transitions are:

* pending -> completed
* pending -> failed
* failed -> pending
* completed -> refunded

All other transitions are rejected server-side. Refund processing and payment-provider refund integrations are not currently implemented.

### Reconciliation

Miamala automatically calculates the `reconciliation_status` classification as:

* `unreconciled`
* `exact_match`
* `underpaid`
* `overpaid`

The `difference` is calculated as received amount minus expected amount using currency minor units. The separate `reconciled` field is a manual workflow state and is not the same as `reconciliation_status`.

Authenticated users can reconcile or unreconcile an owned transaction through:

```text
POST /transactions/{transaction}/reconcile
```

Transaction lists can be filtered with `reconciled=1` or `reconciled=0`. Ordinary transaction edits preserve the existing `reconciled` state.

### CSV export

Authenticated users can export the complete filtered transaction set through:

```text
GET /transactions/export
```

The export reuses the active search, provider, status, category, payment-date, and reconciliation-state filters. It is not limited by pagination, streams records, applies business isolation, excludes soft-deleted transactions, and protects spreadsheet formula-like values.

## Technology Stack

* **PHP 8.3+**
* **Laravel 13**
* **Blade** for server-rendered views
* **Vite** for frontend asset development
* **SQLite** for the default Laravel development setup
* **MySQL / PostgreSQL** can be configured through Laravel's database configuration
* **PHPUnit** for automated testing

The Laravel rebuild follows Laravel's conventional MVC structure with controllers, form requests, Eloquent models, migrations, factories, seeders, Blade views, and feature tests.

## Project Structure

```text
app/
├── Http/
│   ├── Controllers/
│   │   └── TransactionController.php
│   └── Requests/
│       ├── StoreTransactionRequest.php
│       └── UpdateTransactionRequest.php
├── Models/
│   ├── Business.php
│   └── Transaction.php
├── Policies/
│   └── TransactionPolicy.php
└── Services/
    ├── TransactionLifecycleService.php
    └── TransactionReconciliationService.php

database/
├── factories/
│   ├── BusinessFactory.php
│   └── TransactionFactory.php
├── migrations/
│   └── transaction and business ownership migrations
└── seeders/
    └── TransactionSeeder.php

resources/
├── css/
├── js/
└── views/
    └── transactions/
        ├── _form.blade.php
        ├── create.blade.php
        ├── edit.blade.php
        ├── index.blade.php
        └── show.blade.php

routes/
└── web.php

tests/
└── Feature/
    ├── TransactionBusinessScopeTest.php
    ├── TransactionCrudTest.php
    ├── TransactionExportTest.php
    ├── TransactionFoundationTest.php
    ├── TransactionLifecycleTest.php
    ├── TransactionPolicyTest.php
    ├── TransactionReconciliationTest.php
    └── TransactionReconciliationWorkflowTest.php

legacy/
└── index.php
```

The `legacy/` directory preserves the original PHP implementation while the main application is rebuilt in Laravel.

## M4 Milestone Status

* M4 Phase 1: Data foundation - completed
* M4 Phase 2A: Business ownership foundation - completed
* M4 Phase 2B: Authentication and authorization - completed
* M4 Phase 3A: Transaction lifecycle rules - completed
* M4 Phase 3B: Transaction filtering, totals, and pagination - completed
* M4 Phase 3C: Filtered transaction CSV export - completed
* M4 Phase 3D: Dedicated reconciliation workflow - completed
* M4 Phase 3E: Reconciliation state preservation - completed

## Getting Started

### Requirements

* PHP 8.3 or later
* Composer
* Node.js and npm
* A supported database

### Installation

Clone the repository and switch to the Laravel rebuild branch:

```bash
git clone https://github.com/aziziyusuph/miamala.git
cd miamala
git checkout laravel-rebuild
```

Install PHP dependencies:

```bash
composer install
```

Create your environment file:

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

Configure the database in `.env`, then run migrations:

```bash
php artisan migrate
```

Install frontend dependencies and build the assets:

```bash
npm install
npm run build
```

### Seed sample data

To populate the application with sample transactions:

```bash
php artisan db:seed --class=TransactionSeeder
```

Or, on a fresh development database:

```bash
php artisan migrate:fresh --seed
```

### Run the application

```bash
php artisan serve
```

Then open:

```text
http://127.0.0.1:8000
```

The transaction management interface is available at:

```text
http://127.0.0.1:8000/transactions
```

## Development Commands

Run the test suite:

```bash
php artisan test
```

Run Laravel Pint:

```bash
vendor/bin/pint
```

Build frontend assets:

```bash
npm run build
```

## Transaction Data Model

| Field             | Purpose                                     |
| ----------------- | ------------------------------------------- |
| `customer_name`   | Name of the customer or payer               |
| `phone`           | Customer phone number                       |
| `provider`        | Payment channel/provider                    |
| `transaction_id`  | Unique payment transaction identifier       |
| `category`        | Business category of the payment            |
| `amount`          | Amount received                             |
| `status`          | Pending, completed, failed, or refunded     |
| `payment_date`    | Date and time of payment                    |
| `order_reference` | Related order, invoice, or reference number |
| `expected_amount` | Amount expected for reconciliation          |
| `reconciliation_status` | Automatically calculated reconciliation classification |
| `reconciled`      | Whether the payment has been reconciled     |
| `notes`           | Additional transaction information          |

`reconciliation_status` is automatically calculated from the received amount, expected amount, and order reference. `reconciled` is a separate manual workflow state.

Transaction records use soft deletes, and commonly queried fields such as phone number, provider, status, category, payment date, order reference, and reconciliation status are indexed.

## Current Verification

At checkpoint `5636d2e` on branch `laravel-rebuild`:

```text
php artisan test
105 tests passed
345 assertions
```

This is a checkpoint-specific verification result and may change as development continues.

## M4 Phase 4 Baseline

M4 Phase 3 is complete at checkpoint `5636d2e`. The current baseline is a business-scoped Laravel transaction module with CRUD, lifecycle enforcement, reconciliation workflow, search and filtering, totals, pagination, CSV export, authorization, and soft deletion.

M4 Phase 4 scope has not been finalized. Dashboard and reporting needs, production deployment readiness, payment-provider integrations, and further operational UX improvements are possible candidates for future discovery only.

## Roadmap

The Laravel rebuild will continue in incremental milestones.

Planned areas include:

* Dashboard and payment summaries
* Reporting and analytics
* Payment-provider integrations
* Production deployment configuration
* Documentation for administrators and users

M4 Phase 3A-3E is complete. Future milestone scope will be defined separately after discovery and review.

## Contributing

Contributions, suggestions, and issue reports are welcome.

1. Fork the repository.
2. Create a feature branch.
3. Make your changes.
4. Add or update tests where appropriate.
5. Run the test suite and code formatter.
6. Open a pull request.

## License

Miamala is open-source software licensed under the **MIT License**.

## Project

**Miamala** — a practical payment transaction management system being rebuilt with Laravel.

Repository: https://github.com/aziziyusuph/miamala
