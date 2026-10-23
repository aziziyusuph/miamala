# Miamala — Payment records and reconciliation, in one clear workflow.

Miamala is an open-source payment operations and transaction-management platform under development for businesses and organizations across Africa. It aims to help organizations organize transaction records, investigate discrepancies, and reconcile payments received through different channels.

## Try the Public Demo

**Public demo:** https://miamala.onrender.com

The public demo uses simulated transaction data, does not process real money, and does not connect to live payment-provider APIs. Miamala is under active development, and the demo should be treated as a demonstration environment rather than a production payment service. Verify the deployed application's current permissions and workflows before relying on specific behavior.

## The Problem

Organizations may receive payments through mobile money, bank transfers, cash, and other channels. Keeping records consistent and identifying discrepancies across these channels can be difficult.

Miamala is being developed to provide a clearer workflow for managing transaction records and reconciling expected amounts against recorded payments.

## Current Status

Miamala has evolved from an early PHP transaction-management prototype into a Laravel-based application.

The primary development branch is [`laravel-rebuild`](https://github.com/aziziyusuph/miamala/tree/laravel-rebuild).

The project is in active development and public-demo validation. It is not presented as a fully integrated production payment platform.

### Current implementation

The Laravel application includes functionality for:

- Transaction management and lifecycle operations.
- Transaction reconciliation and discrepancy classification.
- Authentication and authorization.
- Business-level data isolation.
- Transaction search and filtering.
- CSV transaction export.
- A public landing page and authenticated dashboard.
- A configurable demo-account foundation with simulated transactions.
- Automated tests.

Features and permissions should be assessed against the current code and deployed application. The presence of transaction records for a particular payment channel does not mean that channel has a live provider integration.

## Technology Stack

- **Backend:** PHP 8.4+ and Laravel
- **Database:** PostgreSQL
- **Frontend:** Laravel Blade and the application's frontend assets
- **Development and deployment:** Composer, Node.js/npm, and Docker-compatible environments

## Getting Started

### Prerequisites

Install the following tools before setting up the project:

- PHP 8.4 or later, with the PHP extensions required by the application and Composer dependencies.
- Composer 2.x.
- Node.js and npm.
- PostgreSQL.
- Git.

### 1. Clone the repository

```bash
git clone https://github.com/aziziyusuph/miamala.git
cd miamala
git checkout laravel-rebuild
```

### 2. Install dependencies

```bash
composer install
npm install
```

### 3. Configure the environment

Create a local environment file:

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

Edit `.env` and configure the application environment and database connection. For a PostgreSQL database, configure the appropriate values for:

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
```

Replace the database placeholders with your own local development database credentials. These values are examples of configuration fields, not working credentials.

Keep `.env` private. Do not commit passwords, API credentials, application secrets, or other sensitive values to Git.

### 4. Run database migrations

Create the database first, then run:

```bash
php artisan migrate
```

This creates the database schema required by the migrations.

Run migrations only against the database you intend to modify. Review the configured database connection before executing migration commands, particularly when working with a shared or hosted database.

If your local setup requires sample data, inspect the available seeders before deciding whether to run:

```bash
php artisan db:seed
```

This is separate from provisioning the dedicated public-demo account described below.

### 5. Start the application

In one terminal, start Laravel:

```bash
php artisan serve
```

If the frontend assets are required for your local setup, start the development asset process in another terminal:

```bash
npm run dev
```

Open the local URL reported by Laravel, usually:

http://127.0.0.1:8000

For a production-style frontend build, use:

```bash
npm run build
```

## Running Tests

Run the automated test suite with:

```bash
php artisan test
```

The last documented test baseline was:

**Last documented baseline:** 133 tests passed, 509 assertions. This is not a guarantee of the latest CI status; check GitHub Actions for current verification.

Check the current test output and GitHub Actions results before treating the code as verified. Tests using an isolated or in-memory database do not, by themselves, prove that a hosted database has the correct schema or configuration.

## Public Demo and Demo Account Seeding

The public demo is intended to demonstrate Miamala using simulated transaction data. It does not process real money or connect to live payment-provider APIs.

The application includes a demo seeding command:

```bash
php artisan miamala:seed-demo
```

The command provisions or refreshes the configured demo business, demo user, and simulated transaction records. It is separate from database migrations and does not replace the need to create the schema first.

### Required demo configuration

Configure the demo account through the appropriate environment variables:

- `MIAMALA_DEMO_EMAIL`
- `MIAMALA_DEMO_PASSWORD`

Use a dedicated demo account and a strong, unique password. Never put real credentials in the README, source code, screenshots, commits, or public issue reports.

### Database safety requirements

**Only seed a database dedicated to the demo.**

Before running the demo seeding command:

1. Confirm which database the application is connected to.
2. Verify the target database name and connection settings.
3. Confirm that the database contains no real customer, financial, or other production data.
4. Apply the required migrations to that dedicated demo database.
5. Run the seed command and review its target-database confirmation before proceeding.

The command includes a target-database confirmation step. The `--force` option bypasses interactive confirmation and must be used only when the target has been independently verified as the intended demo database.

For example, the following command is appropriate only when the configured database is a verified, dedicated demo database:

```bash
php artisan miamala:seed-demo --force
```

**Never run demo seeding against a production database or a database containing real customer or financial records.** Do not assume that the hosting provider offers a particular way to execute Artisan commands; establish and verify the appropriate execution method for the target environment first.

## Payment Channels and Integrations

Miamala is being designed around the needs of organizations that receive payments through multiple channels, including:

- M-Pesa
- Airtel Money
- Mixx by Yas
- Bank transfers
- Cash

These are payment-channel categories used to describe transaction records. Their presence in the application does not establish that live payment-provider connections are available.

Production provider integrations, automated webhooks, and a versioned public API remain planned capabilities unless and until they are implemented and verified in the repository.

## Project Roadmap

Development is focused on validating the Laravel application and its public-demo experience before expanding the platform.

Areas for future development include:

- Integrations with mobile-money providers and banks.
- Automated transaction notifications and webhook processing.
- A versioned public API for supported integrations.
- More robust reconciliation workflows and reporting.
- Further validation with businesses and organizations across Africa.

Roadmap items are plans, not claims that these features are currently available.

## Contributing

Contributions, bug reports, and suggestions are welcome.

1. Review the repository and the current `laravel-rebuild` branch.
2. Create a focused branch for your change.
3. Make changes that fit the existing Laravel architecture.
4. Run the relevant automated tests:

   ```bash
   php artisan test
   ```

5. Submit a pull request describing the problem, the solution, and the verification performed.

Do not include credentials, private customer information, or real financial data in issues, tests, commits, or pull requests.

## Project Links

- **Public demo:** https://miamala.onrender.com
- **GitHub repository:** https://github.com/aziziyusuph/miamala
- **Primary development branch:** https://github.com/aziziyusuph/miamala/tree/laravel-rebuild
- **GitHub Sponsors:** https://github.com/sponsors/aziziyusuph

## License

Miamala is open-source software licensed under the MIT License. See the repository's `LICENSE` file for the full license text.
