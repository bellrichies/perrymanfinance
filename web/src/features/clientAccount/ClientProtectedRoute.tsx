import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { useClientAuth } from './clientAuthContext';

export function ClientProtectedRoute() {
  const auth = useClientAuth();
  const location = useLocation();
  if (auth.status === 'loading') {
    return <main className="grid min-h-screen place-items-center bg-slate-950 text-white" aria-busy="true">Restoring your client session...</main>;
  }
  if (!auth.user) return <Navigate to="/client/login" replace state={{ from: location }} />;
  return <Outlet />;
}
