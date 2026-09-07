import { requestJson } from '../../services/apiClient';

export type PageSection = { type: string; content: Record<string, unknown> };
export type CmsPage = { id: number; uuid: string; title: string; slug: string; page_type: string; status: string; excerpt: string | null; sections?: PageSection[]; updated_at: string };
export type LegalDocument = { id: number; uuid: string; document_type: string; title: string; slug: string; version: string; content: string; effective_at: string | null; status: string };
export type SiteSetting = { setting_key: string; value: unknown; is_public: number };
export type MediaAsset = { uuid: string; original_name: string; mime_type: string; byte_size: number; width: number; height: number; alt_text: string | null };
export type SeoMetadata = { meta_title: string; meta_description: string; canonical_url: string | null; robots: string; open_graph: Record<string, unknown> | null };
type Envelope<T> = { success: true; data: T; meta: object; message: string | null };

export const cmsService = {
  pages: () => requestJson<Envelope<CmsPage[]>>('/admin/pages'),
  page: (uuid: string) => requestJson<Envelope<CmsPage>>(`/admin/pages/${uuid}`),
  savePage: (page: Partial<CmsPage>) => requestJson<Envelope<CmsPage>>(page.uuid ? `/admin/pages/${page.uuid}` : '/admin/pages', { method: page.uuid ? 'PATCH' : 'POST', body: JSON.stringify(page) }),
  deletePage: (uuid: string) => requestJson(`/admin/pages/${uuid}`, { method: 'DELETE' }),
  legal: () => requestJson<Envelope<LegalDocument[]>>('/admin/legal-documents'),
  saveLegal: (document: Partial<LegalDocument>) => requestJson<Envelope<LegalDocument>>(document.uuid ? `/admin/legal-documents/${document.uuid}` : '/admin/legal-documents', { method: document.uuid ? 'PATCH' : 'POST', body: JSON.stringify(document) }),
  settings: () => requestJson<Envelope<SiteSetting[]>>('/admin/settings'),
  saveSetting: (key: string, value: unknown, isPublic: boolean) => requestJson(`/admin/settings/${encodeURIComponent(key)}`, { method: 'PUT', body: JSON.stringify({ value, is_public: isPublic }) }),
  media: () => requestJson<Envelope<MediaAsset[]>>('/admin/media'),
  upload: (file: File, altText: string) => { const body = new FormData(); body.append('file', file); body.append('alt_text', altText); return requestJson<Envelope<MediaAsset>>('/admin/media', { method: 'POST', body }); },
  deleteMedia: (uuid: string) => requestJson(`/admin/media/${uuid}`, { method: 'DELETE' }),
  seo: (type: 'page' | 'legal_document', uuid: string) => requestJson<Envelope<SeoMetadata>>(`/admin/seo/${type}/${uuid}`),
  saveSeo: (type: 'page' | 'legal_document', uuid: string, data: SeoMetadata) => requestJson<Envelope<SeoMetadata>>(`/admin/seo/${type}/${uuid}`, { method: 'PUT', body: JSON.stringify(data) }),
};
