export type AdminUser = { id: string; email: string; display_name: string; roles: string[]; permissions: string[] };
export type AuthResponse = { success: true; data: { user: AdminUser; access_token: string; token_type: 'Bearer'; expires_in: number }; meta: object; message: string | null };
