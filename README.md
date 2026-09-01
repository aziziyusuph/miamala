# Miamala

**Miamala** is a transaction management application for recording, organizing, searching, and reconciling payments from multiple payment channels.

This branch contains the **Laravel rebuild** of the original Miamala application. The rebuild is being developed as a clean, maintainable Laravel application while preserving the core transaction-management purpose of the original project.

> **Development status:** Active rebuild. The transaction-management foundation and CRUD workflow are currently implemented; additional features are being developed incrementally.

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
* Filter by provider, status, category, and date range
* Paginate transaction records
* Soft-delete transactions
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
│   └── Transaction.php

database/
├── factories/
│   └── TransactionFactory.php
├── migrations/
│   └── *_create_transactions_table.php
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
        └── index.blade.php

routes/
└── web.php

tests/
└── Feature/
    ├── TransactionCrudTest.php
    └── TransactionFoundationTest.php

legacy/
└── index.php
```

The `legacy/` directory preserves the original PHP implementation while the main application is rebuilt in Laravel.

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
| `reconciled`      | Whether the payment has been reconciled     |
| `notes`           | Additional transaction information          |

Transaction records use soft deletes, and commonly queried fields such as phone number, provider, status, category, payment date, order reference, and reconciliation status are indexed.

## Roadmap

The Laravel rebuild will continue in incremental milestones.

Planned areas include:

* Dashboard and payment summaries
* Improved reconciliation workflows
* CSV export
* Authentication and user management
* Reporting and analytics
* Payment-provider integrations
* Production deployment configuration
* Additional automated tests
* Documentation for administrators and users

Features will be marked as implemented in this README as the rebuild progresses.

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
