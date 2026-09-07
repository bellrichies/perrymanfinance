# Investment-opportunity catalogue

The catalogue is informational only. It does not accept funds, create subscriptions, calculate returns, execute trades, or manage wallets. Public calls expose only opportunities whose workflow status is `published`, whose publication timestamp is present and not in the future, and which have not been deleted.

## API

- `GET /api/v1/investments`: public paginated list. Supports `page`, `per_page` (maximum 50), `category`, `risk`, `featured`, `search`, and allowlisted `sort` values (`published_at`, `title`, `created_at`, `updated_at`; prefix with `-` for descending).
- `GET /api/v1/investments/{slug}`: public published detail with category, cover-media metadata, disclaimer, and SEO.
- `GET /api/v1/investment-categories`: category reference data.
- `GET|POST /api/v1/admin/investments`, `GET|PATCH|DELETE /api/v1/admin/investments/{uuid}`: permission-protected administration. Delete archives rather than removing an opportunity.
- `POST /api/v1/admin/investment-categories`, `PATCH|DELETE /api/v1/admin/investment-categories/{id}`: permission-protected category management. Categories referenced by opportunities cannot be deleted.

The list response uses `meta.page`, `meta.per_page`, `meta.total`, and `meta.total_pages`. Risk classification is restricted to `low`, `moderate`, `high`, or `very_high`. New opportunities begin in `draft` and follow `draft -> review -> published -> archived`; archived items can return to draft. Publication requires a risk classification and disclaimer. Slugs are globally unique.

SEO metadata is saved atomically with an opportunity when an `seo` object is supplied. Financial descriptions and disclaimers remain administrator-editable and require business/compliance review before production publication.
