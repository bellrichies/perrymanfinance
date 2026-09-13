# Staging Smoke-Test Checklist

Use this checklist after every staging deployment and before UAT sign-off.

## Pre-Smoke Setup

- [ ] Confirm the deployed commit SHA, build artifact, and migration set match the release candidate.
- [ ] Confirm staging uses staging-only secrets, database, SMTP, and public base URL.
- [ ] Confirm production debug output is disabled.
- [ ] Confirm `/api/v1/health` returns success.
- [ ] Confirm `robots.txt` and sitemap staging behavior match the environment policy.

## Public Website

- [ ] Load `/`, `/about`, `/investment-solutions`, `/digital-assets`, `/wealth-management`, `/how-it-works`, `/investments`, `/insights`, `/faq`, `/contact`, `/terms`, `/privacy-policy`, `/risk-disclosure`, and `/cookie-policy`.
- [ ] Confirm each public page has one visible main heading and no visible stack traces or raw API errors.
- [ ] Confirm header navigation, footer navigation, mobile menu, and legal navigation work.
- [ ] Confirm missing routes show the not-found page and noindex metadata.

## Critical Public Workflows

- [ ] Submit a valid contact enquiry with consent and confirm a generic success message.
- [ ] Submit an invalid contact form and confirm accessible field errors without creating an enquiry.
- [ ] Open an investment list item and confirm detail content, risk classification, and disclaimer are visible.
- [ ] Open an insight list item and confirm article detail content is visible.
- [ ] Confirm draft or archived pages, legal documents, investments, and articles are not publicly accessible.

## Admin Workflows

- [ ] Log in with a valid staging admin account.
- [ ] Confirm invalid login fails with a safe error message.
- [ ] Confirm refresh keeps an active admin session usable.
- [ ] Log out and confirm protected admin pages redirect to sign-in.
- [ ] Confirm a lower-permission admin cannot access unauthorized content actions.
- [ ] Create, submit for review, and publish a page.
- [ ] Create, submit for review, and publish a legal document.
- [ ] Create, submit for review, and publish an investment opportunity with a risk classification and disclaimer.
- [ ] Create, submit for review, and publish an insight article.
- [ ] Review a submitted enquiry and update its status.
- [ ] Upload a valid image.
- [ ] Attempt to upload an invalid or disguised file and confirm rejection.

## Release Gates

- [ ] Backend tests pass.
- [ ] Frontend lint, type-check, tests, E2E, and production build pass.
- [ ] Browser console is free of release-blocking errors on core public and admin routes.
- [ ] No unsupported wallet, deposit, withdrawal, brokerage execution, guaranteed return, or fabricated performance claims are visible.
- [ ] Rollback reference and database backup checkpoint are recorded.
