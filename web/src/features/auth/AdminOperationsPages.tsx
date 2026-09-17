import { useEffect, useState } from 'react';
import { getJson } from '../../services/apiClient';

type Envelope<T> = { data: T };
type AdminStaffUser = { uuid: string; email: string; display_name: string; status: string; last_login_at: string | null; created_at: string; roles: string | null };
type AuditLog = { id: number; event: string; subject_type: string | null; subject_id: string | null; request_id: string | null; ip_address: string | null; created_at: string; actor_email: string | null; actor_name: string | null };

export function AdminUsersPage() {
  const [users, setUsers] = useState<AdminStaffUser[] | null>(null);
  const [error, setError] = useState('');
  useEffect(() => { getJson<Envelope<AdminStaffUser[]>>('/admin/users').then(response => setUsers(response.data)).catch(() => setError('Admin users could not be loaded.')); }, []);
  return <main className="space-y-6"><header><h1 className="text-2xl font-semibold">Admin Users</h1><p className="mt-1 text-sm text-slate-400">Staff access is managed through backend RBAC. Use this view to review active administrators and assigned roles.</p></header>{error && <p className="rounded border border-red-200 bg-red-50 p-3 text-sm text-red-800">{error}</p>}{!users && !error && <p aria-busy="true">Loading users...</p>}<section className="overflow-x-auto rounded-lg bg-white p-5 text-slate-900 shadow-sm"><table className="min-w-full text-left text-sm"><thead><tr className="border-b"><th className="py-2">Name</th><th>Email</th><th>Status</th><th>Roles</th><th>Last login</th></tr></thead><tbody>{users?.map(user => <tr key={user.uuid} className="border-b last:border-0"><td className="py-2 font-medium">{user.display_name}</td><td>{user.email}</td><td>{user.status}</td><td>{user.roles?.replaceAll(',', ', ') || 'None'}</td><td>{user.last_login_at ? new Date(user.last_login_at).toLocaleString() : 'Never'}</td></tr>)}</tbody></table>{users?.length === 0 && <p className="py-6 text-sm text-slate-600">No admin users found.</p>}</section></main>;
}

export function AdminAuditLogsPage() {
  const [logs, setLogs] = useState<AuditLog[] | null>(null);
  const [error, setError] = useState('');
  useEffect(() => { getJson<Envelope<AuditLog[]>>('/admin/audit-logs?limit=100').then(response => setLogs(response.data)).catch(() => setError('Audit logs could not be loaded.')); }, []);
  return <main className="space-y-6"><header><h1 className="text-2xl font-semibold">Audit Logs</h1><p className="mt-1 text-sm text-slate-400">Recent administrative security and content events. Sensitive tokens and passwords are excluded from audit snapshots.</p></header>{error && <p className="rounded border border-red-200 bg-red-50 p-3 text-sm text-red-800">{error}</p>}{!logs && !error && <p aria-busy="true">Loading audit logs...</p>}<section className="overflow-x-auto rounded-lg bg-white p-5 text-slate-900 shadow-sm"><table className="min-w-full text-left text-sm"><thead><tr className="border-b"><th className="py-2">Event</th><th>Actor</th><th>Subject</th><th>Request</th><th>When</th></tr></thead><tbody>{logs?.map(log => <tr key={log.id} className="border-b last:border-0"><td className="py-2 font-medium">{log.event}</td><td>{log.actor_name || log.actor_email || 'System'}</td><td>{log.subject_type ? `${log.subject_type}: ${log.subject_id ?? ''}` : 'None'}</td><td>{log.request_id ?? 'None'}</td><td>{new Date(log.created_at).toLocaleString()}</td></tr>)}</tbody></table>{logs?.length === 0 && <p className="py-6 text-sm text-slate-600">No audit log entries found.</p>}</section></main>;
}
