import { getJson } from '../../services/apiClient';

export interface HealthResponse {
  success: true;
  data: {
    status: 'ok';
    service: string;
    version: string;
  };
  meta: Record<string, never>;
  message: null;
}

export function getHealth(signal?: AbortSignal): Promise<HealthResponse> {
  return getJson<HealthResponse>('/health', signal);
}

