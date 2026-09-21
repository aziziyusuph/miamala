# Miamala

**Miamala** is an open-source payment operations and transaction reconciliation platform being built for businesses and organizations operating across Africa's fragmented payment landscape.

Miamala is designed to help businesses manage transactions across multiple payment channels, maintain reliable transaction records, identify discrepancies, and build toward a unified integration layer for African payment providers.

> **Miamala is currently under active development. Production payment-provider integrations and a public production demo are not yet available.**

---

## Why Miamala?

African businesses increasingly receive and send money through multiple payment channels.

A business may use:

* M-Pesa
* Airtel Money
* Mixx by Yas
* Bank transfers
* Cash
* Other payment channels

Managing these transactions independently can make it difficult to answer basic operational questions:

* What payments have been received?
* Which transactions are still pending?
* Which transactions match the expected amount?
* Are there duplicate transactions?
* Which transactions require investigation?
* Can transaction records be exported and audited?
* How can different payment rails eventually be brought into one system?

Miamala is being built around this problem.

The long-term goal is to provide **open-source payment operations and reconciliation infrastructure that can work across African markets and payment rails.**

---

## Current Status

Miamala is currently in active development on the `laravel-rebuild` branch.

### M4 Phase 3 — Complete

The current M4 implementation includes:

* Transaction management
* Transaction lifecycle management
* Transaction reconciliation
* Business-level data isolation
* Authentication
* Authorization policies
* Search and filtering
* Soft deletion
* CSV transaction export
* Validation
* Automated tests
* Service-oriented transaction logic

Current verification baseline:

**105 tests passed / 345 assertions**

The project is now positioned to move toward the next stage of its architecture: provider abstraction, normalized payment events, APIs, webhooks, and eventually real payment-provider integrations.

---

# Core Capabilities

## Transaction Management

Miamala provides a structured transaction management system for recording and managing payment transactions.

Transactions can contain information such as:

* Amount
* Expected amount
* Provider
* Provider transaction ID
* Transaction status
* Reconciliation status
* Transaction date
* Business
* Additional transaction metadata

---

## Transaction Lifecycle

Miamala separates transaction lifecycle operations from controllers through dedicated services.

This provides a foundation for handling transaction state changes consistently.

The architecture is designed to evolve toward more sophisticated payment-event processing as the project develops.

---

## Transaction Reconciliation

Reconciliation is one of the core areas of Miamala.

The system can compare transaction information against expected values and identify transactions that require attention.

For example:

```text
Expected Amount
       │
       ▼
Transaction Received
       │
       ▼
Compare Amount
       │
   ┌───┴────┐
   │        │
 Match   Mismatch
   │        │
   ▼        ▼
Reconciled  Investigation
```

The long-term objective is to support reconciliation across multiple payment providers and payment rails.

---

## Payment Channels

The current system supports transaction records associated with channels such as:

* M-Pesa
* Airtel Money
* Mixx by Yas
* Bank
* Cash
* Other

**Important:** these channel values currently represent transaction records inside Miamala. Direct production integrations with these providers have not yet been implemented.

Provider integrations are part of the future roadmap.

---

## Search and Filtering

Transactions can be searched and filtered to help businesses locate relevant records and investigate transaction activity.

---

## CSV Export

Miamala supports exporting transaction data to CSV.

This provides a simple way to:

* Analyze transactions externally
* Create reports
* Share transaction records
* Perform additional reconciliation
* Maintain operational records

---

## Business Data Isolation

Miamala is designed around business-level data isolation.

Transactions are associated with businesses, providing a foundation for supporting multiple businesses while keeping their transaction data separated.

The architecture can later evolve toward a broader multi-tenant organization model.

---

# Architecture Direction

The current application provides the foundation for a larger payment infrastructure platform.

The long-term architecture is moving toward:

```text
                    Miamala Platform
                           │
                    Organization
                           │
                        Business
                           │
          ┌────────────────┼────────────────┐
          │                │                │
     Users/Roles     Payment Accounts   Transactions
                                             │
                                      Transaction Events
                                             │
                                      Reconciliation
                                             │
                                      Reporting / API
```

The payment-provider architecture is intended to evolve toward an abstraction layer:

```text
                 Miamala Payment Layer
                          │
                  PaymentProvider
                          │
       ┌──────────────────┼──────────────────┐
       │                  │                  │
   M-Pesa Adapter   Airtel Adapter     Mixx Adapter
       │                  │                  │
       └──────────────────┼──────────────────┘
                          │
                   Normalized Events
                          │
                  Transaction Engine
                          │
              ┌───────────┴───────────┐
              │                       │
        Reconciliation            Reporting
              │                       │
              └───────────┬───────────┘
                          │
                         API
```

This architecture is intended to prevent provider-specific logic from becoming tightly coupled to the core transaction system.

---

# API-First Direction

A major future direction for Miamala is to expose payment operations through a stable API.

Potential API areas include:

```text
/api/v1/transactions
/api/v1/payments
/api/v1/reconciliation
/api/v1/providers
/api/v1/webhooks
```

The API would eventually allow external applications to interact with Miamala programmatically.

This could enable use cases such as:

* E-commerce applications
* School management systems
* Property-management platforms
* NGO systems
* SME applications
* Mobile applications
* Other African software products

---

# Future Payment Architecture

Miamala is intended to evolve beyond a basic transaction management application.

Future domain components may include:

```text
Business
Customer
PaymentAccount
PaymentTransaction
TransactionEvent
Reconciliation
Provider
ProviderAccount
Webhook
IdempotencyKey
Order
```

The purpose of these components is to create a normalized payment domain that can operate independently from individual payment providers.

---

# Reliability and Financial Data Integrity

Financial transaction systems require stronger reliability guarantees than ordinary CRUD applications.

Miamala's architecture is therefore being developed with areas such as:

* Idempotency
* Duplicate transaction prevention
* Provider transaction identifiers
* Transaction lifecycle tracking
* Reconciliation
* Validation
* Authorization
* Auditability
* Automated testing
* Reliable state transitions

A future implementation will introduce a more explicit immutable transaction-event history.

For example:

```text
Payment Initiated
       │
       ▼
Payment Received
       │
       ▼
Payment Confirmed
       │
       ▼
Payment Reconciled
       │
       ├──────► Payment Reversed
       │
       └──────► Investigation Required
```

This event-oriented model is intended to provide stronger auditability than relying only on the current transaction status.

---

# Security

Security is an ongoing part of Miamala development.

Current foundations include:

* Authentication
* Authorization policies
* Business-level data isolation
* Input validation
* Protected transaction operations
* Automated testing

As Miamala moves toward real payment-provider integrations, additional security requirements will include:

* API authentication
* API authorization
* Secure webhook verification
* Idempotency controls
* Rate limiting
* Secret management
* Audit logs
* Sensitive-data protection
* Provider credential isolation

---

# Technology Stack

Miamala currently uses:

| Technology                      | Purpose                               |
| ------------------------------- | ------------------------------------- |
| Laravel                         | Backend application framework         |
| PHP                             | Application language                  |
| PostgreSQL                      | Database                              |
| Neon                            | PostgreSQL hosting during development |
| Render                          | Application hosting                   |
| GitHub                          | Source control and collaboration      |
| PHPUnit / Laravel testing tools | Automated testing                     |

---

# Project Structure

The Laravel application follows a service-oriented architecture.

Important areas include:

```text
app/
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Policies/
│
├── Models/
│
└── Services/
    ├── TransactionLifecycleService.php
    └── TransactionReconciliationService.php

database/
├── factories/
├── migrations/
└── seeders/

resources/
├── views/
└── ...

routes/
├── web.php
└── ...

tests/
├── Feature/
└── Unit/
```

The structure will evolve as provider integrations and API functionality are introduced.

---

# Development Milestones

## M4 Phase 3A

Completed.

Focus included transaction lifecycle functionality and supporting application architecture.

## M4 Phase 3B

Completed.

Focus included transaction reconciliation functionality.

## M4 Phase 3C

Completed.

Focus included CSV transaction export.

## M4 Phase 3D

Completed.

Focus included additional application functionality and supporting reliability improvements.

## M4 Phase 3E

Completed.

Focus included final M4 Phase 3 integration, testing, and stabilization.

### Current Baseline

```text
105 tests passed
345 assertions
```

---

# Roadmap

The roadmap is focused on evolving Miamala from a transaction management application into open-source payment operations infrastructure.

## Phase 4 — Architecture and Reliability

Planned areas:

* Update and strengthen the transaction domain
* Provider abstraction
* Normalized payment events
* Immutable transaction history
* Idempotency
* Improved auditability
* Additional security hardening

---

## API Layer

Planned:

* REST API
* API authentication
* API authorization
* Versioned endpoints
* Transaction API
* Payment API
* Reconciliation API
* Provider API
* Webhook endpoints
* API documentation

---

## Payment Provider Integration

Future provider adapters may include:

* M-Pesa
* Airtel Money
* Mixx by Yas
* Bank/payment APIs

The objective is to keep provider-specific implementations behind a common interface.

Example:

```php
interface PaymentProvider
{
    public function initiatePayment(array $data);

    public function verifyPayment(string $transactionId);

    public function getTransaction(string $transactionId);

    public function handleWebhook(array $payload);
}
```

Actual provider implementations will be introduced only after their technical and operational requirements have been properly validated.

---

## Webhooks

Future webhook support will allow Miamala to receive asynchronous payment events from providers.

Potential flow:

```text
Payment Provider
       │
       │ Webhook
       ▼
Miamala API
       │
       ▼
Validate Event
       │
       ▼
Idempotency Check
       │
       ▼
Normalize Event
       │
       ▼
Update Transaction
       │
       ▼
Reconciliation
```

---

## Multi-Tenancy

The current business isolation model provides a foundation for future multi-tenant support.

The longer-term model may become:

```text
Platform
   │
Organization
   │
Business
   │
Users
   │
Payment Accounts
   │
Transactions
```

This would allow Miamala to support organizations managing multiple businesses or payment operations.

---

## Public Demo

A public demonstration environment is planned.

The demo will use simulated transaction data so developers and potential users can explore Miamala without connecting real payment accounts.

---

## Real-World Validation

An important future milestone is testing Miamala with real businesses and organizations.

Potential pilot users include:

* Small businesses
* Schools
* NGOs
* Landlords
* Online businesses
* Software applications

The objective is to measure practical outcomes such as:

* Transactions processed
* Reconciliation accuracy
* Discrepancies identified
* Duplicate transactions detected
* Time saved during reconciliation
* Failed or pending transactions
* API usage

---

# Open Source Vision

Miamala is being developed as an open-source project.

The long-term vision is to create infrastructure that African developers and organizations can build upon rather than requiring every application to independently solve fragmented payment operations.

Potential ecosystem participants could include:

```text
Payment Providers
       │
       ▼
     Miamala
       │
 ┌─────┼─────────┐
 │     │         │
SMEs  NGOs   Developers
 │     │         │
 └─────┼─────────┘
       │
   Applications
```

The project is particularly interested in interoperability, reconciliation, developer access, and reducing the complexity of working across multiple payment rails.

---

# Getting Started

## Requirements

Before running Miamala locally, make sure you have:

* PHP 8.4+
* Composer
* PostgreSQL
* Node.js and npm
* Git

---

## Clone the Repository

```bash
git clone https://github.com/aziziyusuph/miamala.git
cd miamala
```

Switch to the Laravel rebuild branch:

```bash
git checkout laravel-rebuild
```

---

## Install Dependencies

```bash
composer install
npm install
```

---

## Environment Configuration

Copy the example environment file:

```bash
cp .env.example .env
```

Generate the Laravel application key:

```bash
php artisan key:generate
```

Configure your PostgreSQL database in `.env`.

---

## Run Migrations

```bash
php artisan migrate
```

If seed data is available:

```bash
php artisan db:seed
```

---

## Start the Application

```bash
php artisan serve
```

For frontend development:

```bash
npm run dev
```

---

# Running Tests

Run the full test suite with:

```bash
php artisan test
```

Current baseline:

```text
105 tests passed
345 assertions
```

Maintaining a strong automated test suite is a key part of the project's reliability strategy.

---

# Contributing

Contributions are welcome.

Potential areas for contribution include:

* Laravel development
* Payment architecture
* API design
* Payment-provider research
* Reconciliation algorithms
* Security
* Testing
* Documentation
* Developer tooling
* African payment ecosystem research

Before making substantial changes, please open an issue to discuss the proposed approach.

---

# Project Philosophy

Miamala is being built around several principles:

### Open Source First

Payment infrastructure should be accessible to developers who want to build for African markets.

### Provider Neutrality

The core transaction system should not depend on a single payment provider.

### Reliability

Financial data requires strong consistency, traceability, and predictable behavior.

### Interoperability

Different payment rails should be able to communicate through normalized interfaces.

### Developer Friendly

Miamala should eventually make it easier for developers to integrate payment operations without learning every provider's implementation independently.

### Africa-Wide

Miamala is being designed for African markets rather than a single country.

---

# Project Links

**GitHub Repository**

https://github.com/aziziyusuph/miamala

**GitHub Sponsors**

https://github.com/sponsors/aziziyusuph

---

# License

Miamala is open-source software licensed under the **MIT License**.

---

## About

Miamala is an open-source project by **Azizi Yusuph**, focused on building payment operations and transaction reconciliation infrastructure for Africa.

The project is currently under active development and welcomes developers, researchers, businesses, and organizations interested in improving payment interoperability and financial transaction operations across African markets.
