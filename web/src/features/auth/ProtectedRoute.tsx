import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { useAuth } from './authContext';

export function ProtectedRoute() {
  const auth = useAuth(); const location = useLocation();
  if (auth.status === 'loading') return <main className="grid min-h-screen place-items-center" aria-busy="true">Restoring your secure session…</main>;
  if (!auth.user) return <Navigate to="/admin/login" replace state={{ from: location }} />;
  return <Outlet />;
}
