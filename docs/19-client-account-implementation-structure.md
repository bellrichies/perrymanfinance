# PerrymanFinance Client Account Implementation Structure

## Purpose

This document structures the approved client-account expansion requested for PerrymanFinance:

- client account creation and login;
- public frontend account entry points;
- client dashboard;
- client investment-plan selection;
- admin approval or rejection of client investment requests;
- admin manual balance adjustments;
- client visibility into investment growth through approved reporting snapshots.

This is not an approval to build deposits, withdrawals, payment processing, trading, brokerage execution, custody,
wallets, private-key handling, or automated guaranteed-return calculations.

## Product Boundaries

The client account module may show client-specific reporting data, but it must treat all balances and growth figures as
approved reporting values. A displayed balance is not a cash wallet and is not automatically withdrawable. Growth figures
must come from approved reporting snapshots, administrator-entered valuation updates, or a future approved reporting
import.

Do not use:

- Invest Now;
- Deposit;
- Withdraw;
- Cash Out;
- Trade;
- Guaranteed Return;
- Daily ROI;
- Automatic Profit.

Use:

- Select Plan;
- Submit for Review;
- Approved Balance;
- Reported Value;
- Growth Snapshot;
- Request Support.

## Public Frontend Navigation

The public website header must expose client access, not administrator access.

Header behavior:

- replace any public `Login` button with `Client Login` linking to `/client/login`;
- provide a nearby or contextual `Create Account` path linking to `/client/register`, especially on client-login,
  investment-plan, and onboarding-related screens;
- do not show `/admin/login`, `Admin Login`, CMS links, staff links, or admin affordances anywhere in the public
  header, footer, mobile drawer, sitemap, robots-visible public navigation, or marketing pages;
- keep `/admin/login` as a direct, unadvertised staff URL protected by backend authentication, rate limiting, and
  security monitoring;
- authenticated clients should see `Dashboard` instead of `Client Login`, linking to `/client`;
- mobile navigation must follow the same rule: client access is visible, admin access is hidden.

The admin application may still exist under `/admin`, but it is not part of the public frontend navigation experience.

## Roles and Permissions

Client users:

- register, verify email, log in, reset password;
- view their own profile and dashboard;
- view published eligible plans;
- submit plan requests;
- view their own approved investments, reporting snapshots, documents, and notifications.

Admin client-operations permissions:

- `clients.view`;
- `clients.update_status`;
- `client_plans.review`;
- `client_balances.adjust`;
- `client_reports.publish`;
- `client_documents.manage`;
- `client_audit.view`.

CMS content permissions must not automatically grant access to client financial or reporting data.

## Core Workflows

Client registration:

```text
Register
  -> Validate inputs and consent
  -> Create pending client user
  -> Send verification email
  -> Verify email
  -> Enable login
  -> Audit event
```

Client plan selection:

```text
Login
  -> Open Plans
  -> Select published eligible investment opportunity
  -> Enter requested amount if enabled
  -> Acknowledge risk and non-guarantee disclosure
  -> Submit request
  -> See pending status on dashboard
```

Admin approval:

```text
Admin opens pending requests
  -> Reviews client and selected plan
  -> Approves or rejects with required reason
  -> Creates active client investment account on approval
  -> Records initial allocation where applicable
  -> Client receives notification
  -> Audit event
```

Manual balance and growth reporting:

```text
Admin opens client investment account
  -> Adds balance adjustment or reporting snapshot
  -> Enters growth amount, source reference, effective date, and methodology note
  -> System derives principal from the approved investment amount
  -> System calculates reported value and growth percent
  -> Publishes approved reporting value
  -> Client dashboard updates
  -> Audit event
```

## Backend Design Flow

The backend should be implemented as vertical slices inside a separate `ClientAccount` module:

```text
Migration
  -> Repository
  -> Domain Policy
  -> Application Service
  -> Controller
  -> Route
  -> API Resource/DTO
  -> Feature and Security Tests
```

Client auth flow:

```text
Register Request
  -> ClientRegistrationRequest validation
  -> ClientIdentityService creates pending user
  -> Verification token stored hashed
  -> Notification service sends verification email
  -> ClientAuditService records registration
```

Client dashboard flow:

```text
GET /api/v1/client/dashboard
  -> ClientAuthMiddleware authenticates client
  -> ClientOwnershipPolicy scopes all records to authenticated client
  -> DashboardService loads profile, requests, active accounts, snapshots, notifications
  -> DashboardResource returns normalized, display-safe data
```

Admin operation flow:

```text
Admin Request
  -> AdminAuthMiddleware
  -> PermissionMiddleware checks client-operation permission
  -> IdempotencyMiddleware for state-changing actions
  -> ClientOperationsService performs transaction
  -> Audit event is appended
  -> Client notification is queued/created
```

Backend design rules:

- controllers translate HTTP only;
- services own workflows, transactions, idempotency decisions, and audit coordination;
- repositories own persistence and never authorize access;
- policies enforce client ownership and admin permissions;
- client-facing resources must omit internal IDs, admin notes, raw audit data, and source details that are internal-only;
- all money-like values use fixed precision decimals and explicit currency codes;
- dashboard responses must be cache-disabled with private response headers.

## Frontend Design Flow

The frontend should use a separate client account feature area:

```text
src/features/clientAccount/
  auth/
  dashboard/
  plans/
  investments/
  documents/
  profile/
  support/
```

Client route flow:

```text
Public Header
  -> Client Login
  -> Register link when needed
  -> Authenticated Client Route Guard
  -> Client Layout
  -> Dashboard / Plans / Investments / Documents / Profile / Support
```

Client dashboard layout:

```text
Topbar: account status, support link, logout
Summary band: reported balance, active plan, latest snapshot date
Main content: growth chart/table and active investment detail
Side content: pending requests, notifications, documents
Footer note: risk and non-guarantee disclosure
```

User-friendly design patterns:

- keep registration to the minimum fields needed to create an account;
- use a single clear primary action per screen;
- use progressive disclosure for risk text, with required acknowledgement before submission;
- show request status with plain language: `Pending Review`, `Approved`, `Rejected`, `Cancelled`;
- use inline validation and a top-level error summary for forms;
- prevent duplicate submits and show a clear success confirmation;
- use empty states that guide the client to the next available action;
- keep charts restrained, readable, and backed by tabular values for accessibility;
- show dates and currency consistently;
- do not use gamified profit visuals, urgency, confetti, fake data, or speculative crypto styling.

## Data Model

`client_users`

- id, uuid, email, password_hash, status, email_verified_at, last_login_at, created_at, updated_at.

`client_profiles`

- client_user_id, first_name, last_name, phone, country, address fields if required, consent fields, created_at,
  updated_at.

`client_plan_requests`

- client_user_id, investment_opportunity_id, requested_amount, currency, status, risk_acknowledged_at, client_note,
  reviewed_by, reviewed_at, admin_note, created_at, updated_at.

`client_investment_accounts`

- client_user_id, investment_opportunity_id, status, approved_amount, current_balance, currency, approved_by,
  approved_at, last_snapshot_at, created_at, updated_at.

`client_balance_adjustments`

- client_investment_account_id, adjustment_type, amount, currency, reason, source_reference, effective_at, created_by,
  created_at.

`client_reporting_snapshots`

- client_investment_account_id, snapshot_date, principal_amount, reported_value, growth_amount, growth_percent,
  currency, methodology_note, source_reference, approved_by, approved_at, created_at.

For manual snapshot publication, administrators enter only the growth amount and evidence fields. The service must derive
`principal_amount` from `client_investment_accounts.approved_amount`, calculate `reported_value` as principal plus
growth amount, and calculate `growth_percent` from growth amount divided by principal. Client-supplied calculated values
must not be trusted.

`client_audit_events`

- actor_type, actor_id, client_user_id, action, entity_type, entity_id, old_values_json, new_values_json, ip_address,
  user_agent, request_id, created_at.

## API Contract

Client:

```text
POST /api/v1/client/auth/register
POST /api/v1/client/auth/verify-email
POST /api/v1/client/auth/login
POST /api/v1/client/auth/refresh
POST /api/v1/client/auth/logout
POST /api/v1/client/auth/forgot-password
POST /api/v1/client/auth/reset-password
GET  /api/v1/client/auth/me

GET  /api/v1/client/dashboard
GET  /api/v1/client/plans
POST /api/v1/client/plan-requests
GET  /api/v1/client/plan-requests
GET  /api/v1/client/investments
GET  /api/v1/client/investments/{uuid}/snapshots
```

Admin:

```text
GET  /api/v1/admin/clients
GET  /api/v1/admin/clients/{uuid}
GET  /api/v1/admin/client-plan-requests
POST /api/v1/admin/client-plan-requests/{uuid}/approve
POST /api/v1/admin/client-plan-requests/{uuid}/reject
POST /api/v1/admin/client-investments/{uuid}/balance-adjustments
POST /api/v1/admin/client-investments/{uuid}/reporting-snapshots
```

All state-changing client reporting endpoints require idempotency keys.

## Client Dashboard UX

The dashboard should show:

- reported balance;
- active investment plan;
- pending plan request status;
- latest growth snapshot;
- compact growth chart or table;
- documents and support shortcuts;
- risk/non-guarantee disclosure.

Every dashboard state must be covered:

- loading;
- empty account;
- pending plan request;
- approved investment with no snapshots;
- approved investment with snapshots;
- error.

## Admin UX

Admin screens should be simple and queue-driven:

- client list with search/filter;
- pending plan request queue;
- request detail with approve/reject actions;
- required reason field for every decision;
- client investment detail;
- balance adjustment form;
- reporting snapshot form;
- client audit timeline.

Manual balance increase must require:

- amount;
- currency;
- adjustment type;
- effective date;
- source reference;
- reason;
- confirmation.

## Security and Compliance Controls

Required controls:

- separate client identity tables;
- password hashing with PHP password APIs;
- short-lived access tokens and rotating hashed refresh tokens;
- email verification before dashboard access;
- MFA before sensitive reporting/document access where feasible;
- client ownership checks on every client record;
- admin RBAC for all client operations;
- audit on registration, login, plan requests, approvals, balance changes, snapshots, and admin views;
- no sensitive client data in logs;
- no public caching of client API responses;
- no editable historical balance deletion in production.

## Testing Requirements

Backend tests:

- client registration validation;
- email verification;
- login, refresh, logout, password reset;
- client cannot access another client's data;
- plan request requires risk acknowledgement;
- duplicate plan request handling;
- admin approval/rejection permissions;
- balance adjustment validation and audit;
- reporting snapshot validation and audit;
- idempotency-key reuse behavior.

Frontend tests:

- client register/login forms;
- dashboard loading, empty, pending, approved, and error states;
- plan selection and submission;
- admin approval/rejection flow;
- balance adjustment form validation;
- growth snapshot rendering.

E2E tests:

- client registers, logs in, selects a plan, and sees pending status;
- admin approves request and adds a reporting snapshot;
- client sees approved plan and growth snapshot;
- unauthorized client access is denied.
