import { requestJson } from '../../services/apiClient';

export type SeoMetadata = { meta_title: string; meta_description: string; canonical_url?: string | null; robots: string; open_graph?: Record<string, unknown> | null };
export type InvestmentCategory = { id: number; name: string; slug: string; description: string | null; position: number };
export type InvestmentOpportunity = { id?: number; uuid: string; category_id: number; category_name: string; category_slug: string; title: string; slug: string; short_description: string; full_description: string; strategy_summary: string | null; investment_objective: string | null; investment_horizon: string | null; risk_classification: 'low' | 'moderate' | 'high' | 'very_high'; minimum_investment_display: string | null; currency_display: string | null; status: 'draft' | 'review' | 'published' | 'archived'; featured: number | boolean; cover_media_path: string | null; cover_media_alt: string | null; disclaimer: string; published_at: string | null; seo?: SeoMetadata | null };
export type InvestmentFilters = { page?: number; per_page?: number; category?: string; risk?: string; featured?: string; search?: string; status?: string; sort?: string };
type Envelope<T> = { success: true; data: T; meta: { page?: number; per_page?: number; total?: number; total_pages?: number }; message: string | null };

function query(filters: InvestmentFilters): string { const params = new URLSearchParams(); Object.entries(filters).forEach(([key, value]) => { if (value !== undefined && value !== '') params.set(key, String(value)); }); const value = params.toString(); return value ? `?${value}` : ''; }

export const investmentService = {
  publicList: (filters: InvestmentFilters, signal?: AbortSignal) => requestJson<Envelope<InvestmentOpportunity[]>>(`/investments${query(filters)}`, { signal }),
  publicDetail: (slug: string, signal?: AbortSignal) => requestJson<Envelope<InvestmentOpportunity>>(`/investments/${encodeURIComponent(slug)}`, { signal }),
  categories: (signal?: AbortSignal) => requestJson<Envelope<InvestmentCategory[]>>('/investment-categories', { signal }),
  adminList: (filters: InvestmentFilters) => requestJson<Envelope<InvestmentOpportunity[]>>(`/admin/investments${query(filters)}`),
  adminDetail: (uuid: string) => requestJson<Envelope<InvestmentOpportunity>>(`/admin/investments/${uuid}`),
  save: (item: Partial<InvestmentOpportunity>) => requestJson<Envelope<InvestmentOpportunity>>(item.uuid ? `/admin/investments/${item.uuid}` : '/admin/investments', { method: item.uuid ? 'PATCH' : 'POST', body: JSON.stringify(item) }),
  archive: (uuid: string) => requestJson<Envelope<InvestmentOpportunity>>(`/admin/investments/${uuid}`, { method: 'DELETE' }),
};
