# Miamala

**Open-source payment transaction management for businesses and organizations.**

Miamala is an open-source payment transaction management platform designed to help businesses and organizations record, organize, track, reconcile, and report on payments from mobile money, banks, and other digital payment channels.

The project is being developed with a particular focus on practical payment workflows in Tanzania and across Africa.

[![GitHub Sponsors](https://img.shields.io/github/sponsors/aziziyusuph?style=flat\&logo=github)](https://github.com/sponsors/aziziyusuph)
[![License](https://img.shields.io/github/license/aziziyusuph/miamala)](https://github.com/aziziyusuph/miamala/blob/main/LICENSE)

---

## Why Miamala?

Businesses and organizations increasingly receive payments through multiple channels, including mobile money, bank transfers, and other digital payment services.

Managing these transactions across separate platforms, spreadsheets, messages, and systems can make it difficult to:

* Track incoming payments
* Find transactions quickly
* Match payments with orders
* Identify payment discrepancies
* Monitor pending and failed payments
* Reconcile expected and received amounts
* Produce reliable transaction reports

**Miamala aims to provide a centralized, open-source platform for managing these workflows.**

---

## Core capabilities

Miamala is being developed to support:

* Payment transaction recording
* Customer and payment information management
* Transaction categorization
* Transaction search and filtering
* Payment status tracking
* Order and payment reconciliation
* Expected-versus-received amount comparison
* Transaction summaries
* Reporting and exports
* Multiple payment channels
* APIs and payment-provider integrations

---

## Payment channels

Miamala is designed with Tanzania's digital payment ecosystem in mind.

The platform is intended to support payment channels such as:

* **M-Pesa**
* **Airtel Money**
* **Mixx by Yas**
* **Bank payments**
* Other payment providers as the platform evolves

Provider integrations will be developed separately from the core transaction domain so that Miamala can support multiple providers without tightly coupling the core application to a specific payment service.

---

## Technology

The current Laravel rebuild is being developed with:

* **Laravel 13**
* **PHP 8.4+**
* **PostgreSQL**
* **Eloquent ORM**
* **Blade**
* **Vite**

The project is being developed and maintained using GitHub, with the production architecture targeting PostgreSQL and cloud deployment.

---

## Project status

🚧 **Active development**

Miamala began as a lightweight PHP transaction management prototype and is now being rebuilt as a maintainable Laravel application.

The original PHP implementation remains on the `main` branch while the Laravel implementation is being developed and tested on the:

**`laravel-rebuild` branch**

This approach allows the original prototype to remain available while the new implementation is developed incrementally with automated tests and a structured architecture.

### Laravel rebuild progress

#### Milestone 1 — Transaction Foundation

* [x] Laravel 13 application foundation
* [x] Transaction database schema
* [x] Transaction Eloquent model
* [x] Transaction factory
* [x] Transaction seeder
* [x] Soft deletion
* [x] Transaction validation rules
* [x] Automated tests

#### Milestone 2 — Transaction Management

* [x] Transaction CRUD
* [x] Create transaction form
* [x] Edit transaction form
* [x] Transaction listing
* [x] Transaction search
* [x] Provider filtering
* [x] Status filtering
* [x] Category filtering
* [x] Date-range filtering
* [x] Pagination
* [x] Feature tests

**Current Laravel test status: 28 tests passing / 72 assertions**

#### Milestone 3 — Reconciliation

* [ ] Reconciliation business rules
* [ ] Expected-versus-received amount comparison
* [ ] Underpayment detection
* [ ] Overpayment detection
* [ ] Reconciliation workflow
* [ ] Reconciliation reporting

---

## Roadmap

### Transaction management

* [x] Transaction recording
* [x] Transaction CRUD
* [x] Search and filtering
* [x] Transaction status management
* [ ] Payment reconciliation
* [ ] Dashboard and reporting
* [ ] CSV export
* [ ] Customer management

### Security and users

* [ ] Authentication
* [ ] User management
* [ ] Business accounts
* [ ] Role-based access control
* [ ] Audit logging

### Developer platform

* [ ] REST API
* [ ] API authentication
* [ ] Provider webhook infrastructure
* [ ] Idempotent transaction processing
* [ ] Developer documentation

### Payment integrations

* [ ] M-Pesa integration
* [ ] Airtel Money integration
* [ ] Mixx by Yas integration
* [ ] Bank payment workflows
* [ ] Additional African payment providers

### Production readiness

* [ ] Automated CI/CD
* [ ] Security hardening
* [ ] Performance optimization
* [ ] Production monitoring
* [ ] Comprehensive documentation
* [ ] Stable release

The roadmap will evolve based on real-world usage, community feedback, contributors, and available resources.

---

## Getting started

The Laravel implementation is currently under active development on the `laravel-rebuild` branch.

### Prerequisites

You will need:

* PHP 8.4+
* Composer
* PostgreSQL
* Node.js and npm

### Clone the repository

```bash
git clone https://github.com/aziziyusuph/miamala.git
cd miamala
```

### Switch to the Laravel development branch

```bash
git checkout laravel-rebuild
```

### Install PHP dependencies

```bash
composer install
```

### Configure the environment

```bash
cp .env.example .env
php artisan key:generate
```

Configure your PostgreSQL database credentials in `.env`.

### Run migrations

```bash
php artisan migrate
```

### Run the test suite

```bash
php artisan test
```

---

## Contributing

Contributions are welcome.

If you would like to contribute:

1. Fork the repository.
2. Create a feature branch from the appropriate development branch.
3. Make your changes.
4. Add or update automated tests.
5. Run the test suite.
6. Commit your changes.
7. Push your branch.
8. Open a pull request.

For significant architectural or product changes, please open an issue first so the proposal can be discussed before implementation.

---

## Security

Miamala deals with payment transaction data, so security is an important part of the project's development.

If you discover a security vulnerability, please do **not** disclose it publicly through a GitHub issue.

A dedicated security reporting process will be established as the project approaches production readiness.

---

## Support Miamala

Miamala is open source. Building, testing, documenting, and maintaining reliable software requires time and resources.

If you believe in the project's vision, you can support its continued development through **GitHub Sponsors**.

### Sponsorship helps support

* New features
* Payment integrations
* Security improvements
* Automated testing
* Documentation
* Infrastructure
* Bug fixes
* Open-source maintenance

❤️ **[Sponsor Miamala](https://github.com/sponsors/aziziyusuph)**

Every contribution helps support the continued development of Miamala and other open-source work.

---

## Community

Miamala welcomes developers, businesses, organizations, researchers, and other contributors interested in improving payment transaction management through open-source technology.

Ideas, feature requests, technical discussions, bug reports, and contributions are welcome through GitHub Issues and Pull Requests.

---

## License

Miamala is open-source software released under the **MIT License**.

See the [LICENSE](LICENSE) file for details.

---

## Project vision

**Miamala aims to make payment transaction management simpler, more transparent, and more accessible through open-source technology.**

**Built in Tanzania. Designed for Africa. Open to the world.**
