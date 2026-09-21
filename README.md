# Miamala

**Miamala** is an open-source payment operations and transaction management platform being built for businesses and organizations operating across Africa's fragmented payment landscape.

The project is designed to help businesses manage transactions across multiple payment channels, improve transaction visibility, and build toward reliable payment reconciliation and interoperability across African payment rails.

> **Development Notice:** Active development of the next-generation Miamala architecture is currently taking place on the [`laravel-rebuild`](https://github.com/aziziyusuph/miamala/tree/laravel-rebuild) branch.

---

## The Problem

African businesses increasingly operate across multiple payment channels.

A single business may receive payments through:

* Mobile money
* Bank transfers
* Cash
* Multiple payment providers
* Online applications and platforms

Managing these payment records separately can make reconciliation and financial operations difficult.

Businesses need to know:

* Which payments have been received?
* Which transactions are still pending?
* Which payments match expected amounts?
* Are there duplicate transactions?
* Which transactions require investigation?
* Can payment records be exported and audited?
* How can multiple payment rails eventually work together?

Miamala is being built to address these operational challenges.

---

# Vision

The long-term vision for Miamala is to become **open-source payment operations and reconciliation infrastructure for Africa**.

Rather than building around a single payment provider, Miamala is intended to provide a normalized layer through which applications and organizations can manage transactions across different payment rails.

The project is moving toward:

```text id="4t7m0v"
             African Payment Ecosystem
                       │
       ┌───────────────┼────────────────┐
       │               │                │
   Mobile Money      Banks        Other Rails
       │               │                │
       └───────────────┼────────────────┘
                       │
                    Miamala
                       │
       ┌───────────────┼────────────────┐
       │               │                │
 Transactions    Reconciliation       API
       │               │                │
       └───────────────┼────────────────┘
                       │
                Applications
```

---

# Current Project Status

Miamala has evolved from an early PHP transaction-management prototype into a Laravel-based architecture.

The current development branch is:

**`laravel-rebuild`**

The Laravel rebuild introduces a stronger foundation for:

* Transaction management
* Transaction lifecycle management
* Transaction reconciliation
* Business-level data isolation
* Authentication
* Authorization
* Search and filtering
* CSV export
* Service-oriented architecture
* Automated testing

The current Laravel development baseline has:

**105 tests passed / 345 assertions**

---

# Main vs Laravel Rebuild

The repository currently contains two important development stages.

| Branch            | Purpose                                      |
| ----------------- | -------------------------------------------- |
| `main`            | Original/stable project line                 |
| `laravel-rebuild` | Current next-generation Laravel architecture |

The `laravel-rebuild` branch is currently the primary development direction for Miamala.

Developers interested in the latest architecture should start there.

---

# Laravel Rebuild

The Laravel rebuild represents the next stage of Miamala's development.

It is being designed around a more structured payment domain and will eventually support capabilities such as:

* Payment-provider abstraction
* Normalized payment events
* Webhooks
* Idempotency
* API access
* Improved auditability
* Multi-tenant architecture
* Payment-provider integrations
* Advanced reconciliation

The goal is to evolve Miamala from a transaction-management application into a reusable payment operations platform.

---

# Payment Channels

Miamala is designed to work with transactions originating from multiple payment channels.

The current development model includes channels such as:

* M-Pesa
* Airtel Money
* Mixx by Yas
* Bank
* Cash
* Other

**Important:** the current project does not yet provide production integrations with these payment providers.

These providers represent the payment channels Miamala is being designed to accommodate. Actual provider integrations are part of the future roadmap.

---

# Reconciliation

Transaction reconciliation is a central part of Miamala's long-term direction.

A simplified future workflow looks like:

```text id="z7v6se"
Payment Received
       │
       ▼
Transaction Recorded
       │
       ▼
Expected vs Actual
       │
   ┌───┴────┐
   │        │
 Match   Mismatch
   │        │
   ▼        ▼
Reconciled Investigation
```

The objective is to make it easier for organizations to identify discrepancies and maintain reliable financial transaction records.

---

# Architecture Direction

The future Miamala architecture is intended to separate the core transaction domain from individual payment providers.

```text id="ax1y0f"
                 Miamala Platform
                        │
                   Payment API
                        │
                Payment Provider
                   Abstraction
                        │
       ┌────────────────┼────────────────┐
       │                │                │
   M-Pesa          Airtel Money       Mixx
   Adapter           Adapter         Adapter
       │                │                │
       └────────────────┼────────────────┘
                        │
                 Normalized Events
                        │
                 Transaction Engine
                        │
             ┌──────────┴──────────┐
             │                     │
       Reconciliation          Reporting
```

This approach is intended to prevent payment-provider-specific logic from becoming tightly coupled to Miamala's core transaction system.

---

# Planned API

A major future direction is an API that allows external applications to interact with Miamala.

Potential API areas include:

```text id="q3m1p6"
/api/v1/transactions
/api/v1/payments
/api/v1/reconciliation
/api/v1/providers
/api/v1/webhooks
```

Potential users of such an API could include:

* E-commerce applications
* School-management systems
* Property-management platforms
* NGOs
* SMEs
* Mobile applications
* Other African software platforms

---

# Reliability

Because Miamala deals with financial transaction records, reliability is a core architectural concern.

The project is being developed toward capabilities including:

* Idempotency
* Duplicate transaction prevention
* Transaction lifecycle tracking
* Reconciliation
* Validation
* Authorization
* Auditability
* Automated testing
* Reliable state transitions

Future versions will introduce more explicit transaction-event histories to improve traceability.

---

# Technology

The current Laravel development architecture uses:

* **Laravel**
* **PHP**
* **PostgreSQL**
* **Neon**
* **Render**
* **GitHub**

The original project began as a PHP-based transaction-management prototype before being rebuilt using Laravel.

---

# Getting Started

For the original `main` branch:

```bash id="u3q7yr"
git clone https://github.com/aziziyusuph/miamala.git
cd miamala
```

For the current Laravel development version:

```bash id="z7qkq4"
git checkout laravel-rebuild
```

Then see the Laravel rebuild README for the latest development setup and architecture documentation.

---

# Roadmap

Miamala's development roadmap includes:

### Completed / In Development

* Transaction management
* Transaction lifecycle
* Transaction reconciliation
* Business data isolation
* Authentication and authorization
* Search and filtering
* CSV export
* Automated testing
* Laravel architecture rebuild

### Planned

* Payment-provider abstraction
* Normalized payment events
* Immutable transaction history
* Idempotency
* Webhooks
* Public API
* API documentation
* Payment-provider integrations
* Multi-tenancy
* Public demonstration environment
* Real-world pilot testing

---

# Open Source Vision

Miamala is being developed as open-source infrastructure.

The project aims to give African developers and organizations a foundation for building payment-enabled applications without every project having to independently solve the complexities of fragmented payment operations.

The long-term ecosystem could include:

```text id="0mxn8v"
Payment Providers
       │
       ▼
    Miamala
       │
 ┌─────┼──────────┐
 │     │          │
SMEs  NGOs   Developers
 │     │          │
 └─────┼──────────┘
       │
 Applications
```

The project is particularly interested in:

* Payment interoperability
* Transaction reconciliation
* Open-source infrastructure
* Developer accessibility
* Financial data reliability
* African payment ecosystems

---

# Contributing

Contributions are welcome.

Areas where contributors may eventually help include:

* Laravel development
* API development
* Payment architecture
* Provider research
* Reconciliation
* Security
* Testing
* Documentation
* African payment ecosystem research

For substantial changes, please open an issue first to discuss the proposed approach.

---

# Project Links

**GitHub Repository**

https://github.com/aziziyusuph/miamala

**Current Development Branch**

https://github.com/aziziyusuph/miamala/tree/laravel-rebuild

**GitHub Sponsors**

https://github.com/sponsors/aziziyusuph

---

# License

Miamala is open-source software licensed under the **MIT License**.

---

# About Miamala

Miamala is an open-source project by **Azizi Yusuph** focused on building payment operations and transaction reconciliation infrastructure for Africa.

The project is currently under active development.

The immediate focus is strengthening the Laravel architecture, transaction domain, reconciliation capabilities, reliability, and developer foundations needed for future payment-provider integrations and APIs.
