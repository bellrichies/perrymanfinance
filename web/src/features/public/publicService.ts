import { useQuery } from '@tanstack/react-query';
import { requestJson } from '../../services/apiClient';
import type { CmsPage, LegalDocument, SeoMetadata } from '../content/cmsService';

type Envelope<T> = { success: true; data: T };
export type PublicPage = CmsPage & { seo?: SeoMetadata | null };
export type PublicLegal = LegalDocument & { seo?: SeoMetadata | null };
export type PublicRedirect = { source_path: string; destination_path: string; status_code: number };
export const publicService = {
  page: (slug: string, signal?: AbortSignal) => requestJson<Envelope<PublicPage>>(`/pages/${encodeURIComponent(slug)}`, { signal }),
  legal: (slug: string, signal?: AbortSignal) => requestJson<Envelope<PublicLegal>>(`/legal/${encodeURIComponent(slug)}`, { signal }),
  settings: (signal?: AbortSignal) => requestJson<Envelope<Record<string, unknown>>>('/site-settings/public', { signal }),
  redirect: (path: string, signal?: AbortSignal) => requestJson<Envelope<PublicRedirect>>(`/seo/redirect?path=${encodeURIComponent(path)}`, { signal }),
  enquire: (data: Record<string, unknown>) => requestJson<Envelope<null>>('/enquiries', { method: 'POST', body: JSON.stringify(data) }),
};
export function usePublicPage(slug: string) {
  return useQuery({ queryKey: ['page', slug], queryFn: ({ signal }) => publicService.page(slug, signal) });
}
export function usePublicSettings() {
  return useQuery({ queryKey: ['public-settings'], queryFn: ({ signal }) => publicService.settings(signal) });
}
