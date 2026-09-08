# Editorial publishing

Editorial publishing covers Insights articles, categories, tags, related content, and public FAQs. It is informational only and must not contain guaranteed returns, invented licensing claims, trading execution, custody, deposits, withdrawals, or client-money accounting.

## API

- `GET /api/v1/insights`: public paginated article list. Supports `page`, `per_page` (maximum 50), `category`, `tag`, `featured`, `search`, and allowlisted `sort` values (`published_at`, `title`, `created_at`, `updated_at`; prefix with `-` for descending).
- `GET /api/v1/insights/{slug}`: public published article detail with category, tags, author display name, SEO metadata, and related published articles.
- `GET /api/v1/insight-categories`: category reference data.
- `GET /api/v1/tags`: tag reference data.
- `GET /api/v1/faq`: public FAQ list ordered by display position. Supports optional `category`.
- `GET|POST /api/v1/admin/articles`, `GET|PATCH|DELETE /api/v1/admin/articles/{uuid}`: permission-protected article administration. Delete archives rather than removing an article.
- `POST /api/v1/admin/article-categories`, `PATCH|DELETE /api/v1/admin/article-categories/{id}`: permission-protected category management. Categories referenced by articles cannot be deleted.
- `POST /api/v1/admin/tags`, `PATCH|DELETE /api/v1/admin/tags/{id}`: permission-protected tag management. Tags referenced by articles cannot be deleted.
- `GET|POST /api/v1/admin/faqs`, `PATCH|DELETE /api/v1/admin/faqs/{id}`: permission-protected FAQ management. Delete archives rather than removing a FAQ.

Public article and FAQ endpoints expose only records whose workflow status is `published` and whose publication timestamp is absent where allowed or not in the future. Articles follow `draft -> review -> published -> archived`; archived items can return to draft. New articles and FAQs must begin as drafts.

Article content, FAQ answers, and rich SEO-adjacent text are sanitized server-side with the shared HTML allowlist. Script, style, iframe, object, embed, attributes, event handlers, and arbitrary executable markup are stripped before persistence.

Related article logic returns up to three published articles sharing the same category or at least one tag with the current article, ordered by most recent publication.
