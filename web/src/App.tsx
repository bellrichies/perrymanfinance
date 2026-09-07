import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { AdminLayout, DashboardPage } from './features/auth/AdminLayout';
import { AuthProvider } from './features/auth/AuthProvider';
import { LoginPage } from './features/auth/LoginPage';
import { ProtectedRoute } from './features/auth/ProtectedRoute';
import { ForgotPasswordPage, ResetPasswordPage } from './features/auth/PasswordResetPages';

export function App() {
  return <BrowserRouter><AuthProvider><Routes>
    <Route path="/admin/login" element={<LoginPage />} />
    <Route path="/admin/forgot-password" element={<ForgotPasswordPage />} />
    <Route path="/admin/reset-password" element={<ResetPasswordPage />} />
    <Route element={<ProtectedRoute />}><Route path="/admin" element={<AdminLayout />}><Route index element={<DashboardPage />} /></Route></Route>
    <Route path="*" element={<Navigate to="/admin" replace />} />
  </Routes></AuthProvider></BrowserRouter>;
}
