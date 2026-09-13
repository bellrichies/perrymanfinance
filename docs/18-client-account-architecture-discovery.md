# PerrymanFinance Client Account Architecture Discovery

## Purpose

This document records architecture discovery for a possible authenticated PerrymanFinance client account module. It is a proposal and migration strategy only. It does not approve implementation of client-money, custody, brokerage, exchange, wallet, payment, investment subscription, transaction ledger, or automated-return functionality.

The existing MVP CMS is sufficient for public content, enquiries, investment catalogue entries, SEO, media, legal pages, and admin publishing workflows. It is not sufficient for financial transaction processing, KYC transaction onboarding, portfolio accounting for real client money, custody, or asset/money movement.

## Architecture Proposal

PerrymanFinance should treat the authenticated client account module as a new regulated client platform boundary, not as an extension of the CMS.

```text
Public Website / CMS
  - content
  - legal/risk disclosures
  - public investment catalogue
  - enquiries
  - admin publishing

Client Account Platform
  - client identity
  - MFA
  - KYC / suitability
  - read-only portfolio reporting
  - statements
  - document access
  - notifications
  - audit/compliance evidence

Future Regulated Transaction Platform
  - subscriptions
  - transaction ledger
  - settlement
  - payment movement
  - custody integrations
  - reconciliation
```

The recommended first approved slice is read-only client reporting and document access. Investment subscriptions, transactions, deposits, withdrawals, wallets, custody, or settlement should remain out of scope until a separate regulated transaction architecture is approved.

## Discovery Areas

| Area | Proposed Architecture | Key Dependencies / Risks |
| --- | --- | --- |
| Client identity | Separate `ClientIdentity` module with client profiles, account status, verified emails/phones, session records, device records, and client-specific authorization. | Must not reuse admin identity directly. Requires privacy basis, account-opening policy, support workflow, lockout/recovery controls. |
| MFA | Mandatory for clients and staff accessing client data. Prefer TOTP/WebAuthn; SMS only as fallback due to SIM-swap risk. | Recovery codes, step-up MFA for sensitive document/download/profile actions, audit on enrollment/change. |
| KYC | Separate `KYC` bounded context with provider integration, applicant status, evidence references, risk rating, sanctions/PEP status, suitability/investor classification. | Legal/regulatory review required before implementation. Avoid storing raw identity documents unless necessary. Provider contracts, retention policy, consent, data residency. |
| Portfolio reporting | Read-only `PortfolioReporting` module sourced from approved books/records or custodian/advisor systems. Store snapshots, not invented calculations. | Must define source of truth, valuation methodology, stale-data handling, disclaimer language, approval workflow. |
| Statements | Generated or imported immutable statement artifacts with period, version, source, checksum, and approval status. | Statements are regulated communications. Require retention, correction/reissue policy, watermarking/versioning, audit. |
| Investment subscriptions | Do not include in first slice. If approved later, create a separate `Subscription` workflow with eligibility, offer docs, acknowledgements, cooling-off/approval states, and idempotent submission. | May constitute regulated offering/subscription/client-money workflow. Requires legal terms, suitability, payment/settlement design, compliance sign-off. |
| Transactions | Do not implement as CMS or simple CRUD. Future `Transactions` module must distinguish orders, subscriptions, cash movements, asset movements, fees, reversals, corrections, and external references. | Requires immutable ledger design, reconciliation, idempotency, audit, operations tooling, compliance review. |
| Notifications | Separate notification service for account events, document availability, KYC updates, statement publication, support messages. | Templates need compliance approval. Avoid sensitive financial details in email/SMS. In-app notices should be auditable. |
| Document access | `ClientDocuments` module with entitlement checks, signed/short-lived download links, classification, document type, expiry, checksum, and access logs. | Sensitive documents require encryption, least privilege, download audit, retention/deletion rules. |
| Audit/compliance controls | Append-only audit events for identity, MFA, KYC, document access, report views/downloads, admin impersonation, statement publication, and data exports. | Audit logs must exclude secrets but preserve evidentiary value. Consider WORM/exportable retention. |

## Legal and Regulatory Dependencies

Before implementation, PerrymanFinance needs decisions or counsel-approved documents for:

- operating entity, jurisdiction, licensing/registration status, and permitted client services;
- whether the portal is informational, advisory, brokerage, fund subscription, custody, or payment-related;
- KYC/AML obligations, sanctions screening, PEP handling, beneficial ownership, source-of-funds/source-of-wealth requirements;
- investor classification and suitability/appropriateness rules;
- client agreement, privacy policy, risk disclosure, electronic communications consent, statement/report disclaimers;
- retention periods for KYC, audit logs, communications, statements, and transaction records;
- data residency and cross-border processing rules;
- vendor due diligence for KYC, custody, payment, statement generation, email/SMS, storage, and analytics providers.

## Money and Asset Movement Implications

Any feature that allows a client to subscribe, fund, redeem, withdraw, transfer, trade, or instruct PerrymanFinance to move money/assets materially changes the platform class.

Those features imply:

- client-money controls;
- segregation of client assets;
- settlement workflows;
- bank/payment/custodian integrations;
- ledger and subledger accounting;
- reconciliation;
- fraud monitoring;
- operational approval queues;
- dispute/reversal handling;
- regulatory reporting;
- stronger incident response obligations.

Do not add "Invest Now," wallet funding, deposit instructions, withdrawal forms, trade tickets, automated balances, or subscription confirmations to the CMS/public app.

## Custody Implications

If PerrymanFinance directly or indirectly controls client assets, private keys, wallets, bank accounts, omnibus accounts, or custodian instructions, the platform needs a custody architecture.

That would require:

- custodian-of-record/legal model;
- key management or zero private-key handling decision;
- dual-control approvals;
- segregation between client assets and company assets;
- reconciliation against custodian/bank records;
- incident response for unauthorized transfers;
- insurance and regulatory review;
- proof-of-reserves or reporting strategy if digital assets are involved.

Recommended stance for the first client module: no custody, no wallet creation, no private keys, no payment movement. Display only read-only data from approved sources.

## Data-Security Classification

| Class | Examples | Controls |
| --- | --- | --- |
| Public | Published pages, public investment catalogue, public legal pages | CMS controls, cacheable where appropriate |
| Internal | Admin workflow metadata, non-sensitive operational logs | RBAC, audit, least privilege |
| Confidential Client Data | Client profile, contact details, holdings summaries, statement metadata, support messages | Encryption at rest where possible, strict ABAC, audit, no public caching |
| Restricted Regulated Data | KYC documents, government IDs, sanctions results, financial account references, transaction records, statements | Strongest access controls, encryption, retention policy, vendor review, access monitoring |

KYC files, statements, portfolio reports, transaction records, and document downloads should never be stored as ordinary CMS media assets.

## Threat Model

Primary threats:

- account takeover through credential stuffing, phishing, weak MFA recovery, or SIM swap;
- session theft and refresh-token compromise;
- IDOR, where one client accesses another client's documents or reports;
- compromised admin viewing or exporting client data outside role;
- malicious or mistaken staff publishing incorrect reports or statements;
- tampered portfolio data or imported transactions;
- duplicate subscription or payment instruction;
- fake KYC approval or stale sanctions state;
- document leakage through public URLs, logs, CDN, backups, or misconfigured storage;
- notification leakage containing sensitive data;
- audit-log tampering;
- vendor/API compromise;
- reconciliation breaks between portal data and official books/records.

Controls:

- client-specific ABAC, not only role checks;
- MFA and step-up authentication;
- short-lived signed document URLs;
- immutable audit;
- request IDs and idempotency keys;
- dual approval for sensitive admin actions;
- import validation and checksums;
- separate storage for restricted documents;
- no sensitive values in logs;
- regular access reviews;
- incident response and breach notification playbooks.

## Idempotency Requirements

Idempotency is mandatory for all future financial or regulated writes:

- KYC application creation/update callbacks;
- document upload/import;
- statement publication;
- notification dispatch;
- subscription intent submission;
- payment/deposit instruction creation;
- transaction import;
- ledger posting;
- cancellation/reversal/correction workflows.

Use client-supplied or server-issued idempotency keys with scoped uniqueness:

```text
actor_id + operation_type + idempotency_key
```

Store request hash, result hash/status, timestamp, and expiry. Reject key reuse with a materially different payload.

## Reconciliation Requirements

Reconciliation is required before portfolio, statement, subscription, or transaction features are treated as authoritative.

Reconcile:

- portal holdings against custodian/advisor systems;
- cash balances against bank/custodian records;
- transaction imports against source files/APIs;
- statement totals against reporting snapshots;
- subscriptions against approvals and settlement receipts;
- document availability against client entitlements;
- notification dispatch against delivery state.

Reconciliation should have:

- daily or periodic jobs;
- exception queues;
- maker/checker resolution;
- immutable correction records;
- operational reports;
- alerting for stale or failed imports;
- no silent overwrites of regulated records.

## Audit Requirements

Audit must be append-only and queryable by compliance.

Capture:

- login success/failure;
- MFA enrollment, removal, recovery, challenge failures;
- password/email/phone changes;
- client profile changes;
- KYC status changes and provider callbacks;
- suitability/risk-profile changes;
- portfolio snapshot imports and approvals;
- statement generation, approval, publication, download;
- document upload, classification, entitlement, view/download;
- notification creation and delivery status;
- subscription intent creation, approval, rejection, cancellation;
- transaction import, posting, correction, reversal;
- admin access to client records;
- impersonation/support access with explicit reason;
- export/delete/privacy requests;
- permission and role changes.

Do not store passwords, tokens, raw secrets, full document contents, or unnecessary sensitive data inside audit snapshots.

## Segregation From the Existing CMS

Keep these separate:

```text
CMS/Admin Content
  - admin_users
  - roles/permissions for content staff
  - pages/articles/legal/investment catalogue
  - media assets
  - enquiries
  - CMS audit logs

Client Platform
  - client_users
  - client_profiles
  - client_sessions
  - mfa_factors
  - kyc_cases
  - client_accounts
  - portfolio_snapshots
  - statements
  - client_documents
  - notifications
  - client_audit_events

Future Transaction Platform
  - subscription_intents
  - payment_instructions
  - transaction_events
  - ledger_entries
  - reconciliation_runs
  - reconciliation_exceptions
```

Admin CMS users should not automatically gain access to client data. Client operations roles should be separate from content roles, with explicit permissions and stronger audit.

## Migration Strategy

1. Discovery Gate
   Freeze product scope for the client module. Decide whether the first release is read-only reporting/document access or includes regulated transaction workflows. Recommended: read-only only.

2. Compliance Gate
   Obtain legal sign-off for account terms, privacy updates, KYC requirements, reporting disclaimers, data retention, permitted jurisdictions, and whether PerrymanFinance may present portfolio/statement data.

3. Architecture Gate
   Create a separate client-account architecture decision record covering identity, MFA, ABAC, data classification, storage, audit, vendor integrations, reconciliation, and incident response.

4. Infrastructure Preparation
   Add restricted storage separate from CMS media, encryption/key-management policy, backup classification, operational logging, monitoring, and access-review procedures.

5. Client Identity Foundation
   Introduce separate client identity tables and routes under a distinct namespace such as:

   ```text
   /api/v1/client/auth/*
   /api/v1/client/me
   ```

   Do not reuse `/api/v1/admin/*` or admin CMS permissions.

6. MFA and Session Hardening
   Require MFA before enabling any client data. Add device/session management, recovery controls, throttling, and suspicious-login audit.

7. Document Access Pilot
   Start with manually approved, read-only document access. Store document metadata and restricted files outside public media. Require entitlement checks and download audit.

8. Read-Only Reporting Pilot
   Add portfolio/reporting snapshots from an approved source of truth. Label stale/unverified data clearly. No automated ROI, no fabricated balances, no transaction entry.

9. Statements
   Add immutable statement records only after the reporting source, approval workflow, correction/reissue policy, and retention policy are approved.

10. KYC Integration
    Add KYC only after provider selection, DPIA/privacy review, AML/sanctions workflow approval, retention rules, and operational review queues exist.

11. Subscriptions / Transactions Deferred
    Treat subscriptions and transactions as a later program. Before implementation, design ledgering, settlement, custody boundaries, idempotency, reconciliation, maker/checker approval, and regulatory reporting.

12. Operational Readiness
    Before launch, run security testing, access-control tests, IDOR tests, audit verification, backup/restore tests, document leakage checks, incident drills, and compliance UAT.

## Recommended First Release Scope

Approved first slice:

- separate client login;
- mandatory MFA;
- client profile read-only view;
- restricted document access;
- read-only reporting snapshots;
- downloadable approved statements if source/approval process exists;
- in-app notifications for document/report availability;
- full audit trail.

Explicitly excluded from first slice:

- deposits;
- withdrawals;
- wallets;
- custody;
- trading;
- investment subscriptions;
- payment instructions;
- transaction ledger;
- automated performance/ROI calculations;
- KYC-driven transaction onboarding unless legally approved.
