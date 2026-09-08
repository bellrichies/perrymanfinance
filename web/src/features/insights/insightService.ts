import { requestJson } from '../../services/apiClient';

export type SeoMetadata = { meta_title: string; meta_description: string; canonical_url?: string | null; robots: string; open_graph?: Record<string, unknown> | null };
export type ArticleCategory = { id: number; name: string; slug: string; description: string | null };
export type Tag = { id: number; name: string; slug: string };
export type Article = { id?: number; uuid: string; category_id: number; category_name: string; category_slug: string; title: string; slug: string; excerpt: string; content: string; cover_media_path: string | null; cover_media_alt: string | null; author: string; tags: Tag[]; tag_ids?: number[]; featured: number | boolean; status: 'draft' | 'review' | 'published' | 'archived'; published_at: string | null; seo?: SeoMetadata | null; related?: Article[] };
export type FAQ = { id?: number; question: string; answer: string; category: string | null; position: number; status: 'draft' | 'review' | 'published' | 'archived'; published_at?: string | null };
export type InsightFilters = { page?: number; per_page?: number; category?: string; tag?: string; featured?: string; search?: string; status?: string; sort?: string };
type Envelope<T> = { success: true; data: T; meta: { page?: number; per_page?: number; total?: number; total_pages?: number }; message: string | null };

function query(filters: InsightFilters): string { const params = new URLSearchParams(); Object.entries(filters).forEach(([key, value]) => { if (value !== undefined && value !== '') params.set(key, String(value)); }); const value = params.toString(); return value ? `?${value}` : ''; }

export const insightService = {
  publicList: (filters: InsightFilters, signal?: AbortSignal) => requestJson<Envelope<Article[]>>(`/insights${query(filters)}`, { signal }),
  publicDetail: (slug: string, signal?: AbortSignal) => requestJson<Envelope<Article>>(`/insights/${encodeURIComponent(slug)}`, { signal }),
  categories: (signal?: AbortSignal) => requestJson<Envelope<ArticleCategory[]>>('/insight-categories', { signal }),
  tags: (signal?: AbortSignal) => requestJson<Envelope<Tag[]>>('/tags', { signal }),
  faq: (category?: string, signal?: AbortSignal) => requestJson<Envelope<FAQ[]>>(`/faq${category ? `?category=${encodeURIComponent(category)}` : ''}`, { signal }),
  adminList: (filters: InsightFilters) => requestJson<Envelope<Article[]>>(`/admin/articles${query(filters)}`),
  adminDetail: (uuid: string) => requestJson<Envelope<Article>>(`/admin/articles/${uuid}`),
  saveArticle: (item: Partial<Article>) => requestJson<Envelope<Article>>(item.uuid ? `/admin/articles/${item.uuid}` : '/admin/articles', { method: item.uuid ? 'PATCH' : 'POST', body: JSON.stringify({ ...item, tag_ids: item.tag_ids ?? item.tags?.map((tag) => tag.id) ?? [] }) }),
  archiveArticle: (uuid: string) => requestJson<Envelope<Article>>(`/admin/articles/${uuid}`, { method: 'DELETE' }),
  saveCategory: (item: Partial<ArticleCategory>) => requestJson<Envelope<ArticleCategory>>(item.id ? `/admin/article-categories/${item.id}` : '/admin/article-categories', { method: item.id ? 'PATCH' : 'POST', body: JSON.stringify(item) }),
  deleteCategory: (id: number) => requestJson<Envelope<null>>(`/admin/article-categories/${id}`, { method: 'DELETE' }),
  saveTag: (item: Partial<Tag>) => requestJson<Envelope<Tag>>(item.id ? `/admin/tags/${item.id}` : '/admin/tags', { method: item.id ? 'PATCH' : 'POST', body: JSON.stringify(item) }),
  deleteTag: (id: number) => requestJson<Envelope<null>>(`/admin/tags/${id}`, { method: 'DELETE' }),
  adminFaqs: () => requestJson<Envelope<FAQ[]>>('/admin/faqs'),
  saveFaq: (item: Partial<FAQ>) => requestJson<Envelope<FAQ>>(item.id ? `/admin/faqs/${item.id}` : '/admin/faqs', { method: item.id ? 'PATCH' : 'POST', body: JSON.stringify(item) }),
  deleteFaq: (id: number) => requestJson<Envelope<null>>(`/admin/faqs/${id}`, { method: 'DELETE' }),
};
