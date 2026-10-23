# Miamala — Payment records and reconciliation, in one clear workflow.

## Try the Public Demo

Visit the live public demo:

https://miamala.onrender.com

The public demo uses simulated transaction data, does not process real money, and does not connect to live payment-provider APIs. The current live behavior should be treated as a demonstration environment under active development, and any visible permissions or workflow details should be verified in the deployed application before being described as guaranteed.

**Miamala** is an open-source payment operations and transaction-management platform under development for businesses and organizations across Africa.

Organizations receive payments through multiple channels and need a clearer way to organize transaction records, investigate discrepancies, and reconcile payments.

> **Development Notice:** Active development of the next-generation Miamala architecture is currently taking place on the [laravel-rebuild](https://github.com/aziziyusuph/miamala/tree/laravel-rebuild) branch.
>
> - The primary development branch is [laravel-rebuild](https://github.com/aziziyusuph/miamala/tree/laravel-rebuild).

---

## Current project status

Miamala has evolved from an early PHP transaction-management prototype into a Laravel-based architecture.

The primary development branch is:

- [laravel-rebuild](https://github.com/aziziyusuph/miamala/tree/laravel-rebuild)

The last documented baseline is:

**Last documented baseline:** 133 tests passed, 509 assertions. This is not a guarantee of the latest CI status; check GitHub Actions for current verification.

---

## What Miamala does today

The current implementation includes:

- Transaction management and lifecycle operations
- Transaction reconciliation
- Authentication and authorization
- Business-level data isolation
- Search and filtering
- CSV transaction export
- Landing page and authenticated dashboard
- Configurable demo-account foundation with simulated transactions
- Automated testing

---

## Why it matters

African businesses increasingly receive and send money through multiple channels.

A business may use:

- M-Pesa
- Airtel Money
- Mixx by Yas
- Bank transfers
- Cash
- Other payment channels

Managing these transactions independently can make it difficult to answer basic operational questions, including:

- Which payments have been received?
- Which transactions are still pending?
- Which transactions match their expected amount?
- Are there duplicate records?
- Which transactions need investigation?
- Can transaction records be exported and audited?
- How can different payment rails be brought into one workflow?

Miamala is being built to address this problem.

---

## Payment channels and integrations

The current project supports transaction records associated with channel labels such as:

- M-Pesa
- Airtel Money
- Mixx by Yas
- Bank
- Cash
- Other

These labels are not proof of live production integrations. Production payment-provider integrations, webhooks, and a versioned public API remain planned unless the repository proves otherwise.

---

## Project links

**GitHub Repository**

https://github.com/aziziyusuph/miamala

**Public Demo**

https://miamala.onrender.com

**Current Development Branch**

https://github.com/aziziyusuph/miamala/tree/laravel-rebuild

**GitHub Sponsors**

https://github.com/sponsors/aziziyusuph

---

## License

Miamala is open-source software licensed under the **MIT License**.

---

## Contributing

Contributions are welcome.

Areas for contribution may include:

- Laravel development
- Payment architecture
- API design
- Reconciliation logic
- Security and validation
- Testing
- Documentation
- Developer tooling
- African payment ecosystem research

Before making substantial changes, please open an issue to discuss the proposed approach.
