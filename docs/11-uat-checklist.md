# UAT Checklist

## Public Content

- [ ] Homepage content is business-approved and CMS-driven where required.
- [ ] Service pages describe PerrymanFinance accurately without unsupported regulatory, performance, or custody claims.
- [ ] CTA language remains consultative, such as "Learn More," "View Details," and "Request Consultation."
- [ ] Placeholder legal, financial, or operational copy is clearly marked for review and not treated as final.

## Navigation

- [ ] Desktop header links route to the expected pages.
- [ ] Mobile menu opens, closes, supports keyboard use, and routes correctly.
- [ ] Footer links route to public, legal, and contact pages.
- [ ] Breadcrumbs or contextual navigation appear where implemented and are accurate.
- [ ] Unknown URLs show a helpful not-found page.

## Admin

- [ ] Valid admin users can log in and reach the dashboard.
- [ ] Invalid credentials are rejected with safe messaging.
- [ ] Session refresh and logout work reliably.
- [ ] Protected admin routes require authentication.
- [ ] Role-based permissions hide or deny unauthorized actions.
- [ ] Sensitive admin actions create audit records.

## Investments

- [ ] Published opportunities appear on the public investment list.
- [ ] Draft, review, and archived opportunities do not appear publicly.
- [ ] Detail pages show risk classification, factual descriptions, and required disclaimers.
- [ ] Admins can create, edit, submit for review, publish, and archive opportunities.
- [ ] Publishing is blocked unless required risk and disclaimer fields are complete.
- [ ] Opportunity copy avoids "Invest Now," guaranteed returns, fake balances, or payment/custody functionality.

## Insights

- [ ] Published articles appear on the public insights list.
- [ ] Draft, review, and archived articles do not appear publicly.
- [ ] Article detail pages show title, excerpt/body, category, tags, and related content where configured.
- [ ] Admins can create, edit, submit for review, publish, and archive articles.
- [ ] Rich content is sanitized and does not execute scripts.

## Enquiries

- [ ] Valid contact, consultation, and onboarding-intake enquiries are persisted.
- [ ] Consent is required before submission.
- [ ] Validation errors are clear and accessible.
- [ ] Notification failure does not lose a persisted enquiry.
- [ ] Admins can view enquiries and update status through the approved workflow.
- [ ] Public responses do not expose internal IDs or operational errors.

## Legal

- [ ] Terms, privacy policy, risk disclosure, and cookie policy are published and readable.
- [ ] Legal content is CMS-editable and versioned.
- [ ] Effective dates and versions display correctly where applicable.
- [ ] New legal versions follow draft -> review -> published -> archived.
- [ ] Legal placeholders have qualified counsel/business approval before production release.

## SEO

- [ ] Public routes include title, meta description, canonical URL, robots directive, and Open Graph data.
- [ ] Article, FAQ, Organization, and Breadcrumb structured data are present where applicable.
- [ ] Sitemap excludes admin routes, drafts, previews, archived content, and noindex content.
- [ ] `robots.txt` reflects staging and production environment policy.
- [ ] Redirects for renamed published slugs work.

## Responsive

- [ ] Core public pages work at mobile, tablet, laptop, and desktop widths.
- [ ] Admin workflows remain usable on tablet and desktop.
- [ ] Text, buttons, cards, tables, and forms do not overlap or cause horizontal scrolling.
- [ ] Images and media scale without distortion or layout shift.

## Accessibility

- [ ] Each route has one main landmark and a logical heading structure.
- [ ] Forms have labels, validation feedback, and focus management.
- [ ] Keyboard users can navigate menus, links, buttons, forms, and disclosures.
- [ ] Focus indicators are visible.
- [ ] Color contrast meets WCAG 2.1 AA practices.
- [ ] Reduced-motion preferences are respected where motion exists.

## Security

- [ ] Admin endpoints reject missing, invalid, expired, and revoked tokens.
- [ ] Permission checks are enforced server-side.
- [ ] Login, password reset, enquiries, and uploads are rate-limited where configured.
- [ ] Uploads reject invalid MIME types, oversized files, oversized dimensions, scripts, and disguised files.
- [ ] Responses do not expose stack traces, SQL details, secrets, tokens, or internal diagnostics.
- [ ] Security headers, CORS, cookie settings, and HTTPS behavior are verified for staging.
- [ ] No `.env`, logs, migrations, source internals, tests, or private uploads are web-accessible.
