import { getJson, requestJson } from '../../services/apiClient';

export const transitions = { new: ['in_progress', 'spam', 'closed'], in_progress: ['resolved', 'spam', 'closed'], resolved: ['in_progress', 'closed'], spam: ['in_progress', 'closed'], closed: ['in_progress'] } as const;
export type EnquiryStatus = keyof typeof transitions;
export type Enquiry = { uuid: string; name: string; email: string; phone?: string; enquiry_type: string; subject: string; message: string; source_page?: string; consent_at: string; status: EnquiryStatus; created_at: string; resolved_at?: string };
type Envelope<T> = { data: T; meta: { page: number; total_pages: number; total: number } };
export const enquiryService = {
  list: (search: string, status: string, page: number, signal?: AbortSignal) => getJson<Envelope<Enquiry[]>>(`/admin/enquiries?${new URLSearchParams({ search, status, page: String(page) })}`, signal),
  detail: (uuid: string, signal?: AbortSignal) => getJson<Envelope<Enquiry>>(`/admin/enquiries/${encodeURIComponent(uuid)}`, signal),
  update: (uuid: string, status: string) => requestJson<Envelope<Enquiry>>(`/admin/enquiries/${encodeURIComponent(uuid)}`, { method: 'PATCH', body: JSON.stringify({ status }) }),
};
