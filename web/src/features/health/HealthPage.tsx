import { Link } from 'react-router-dom';
import { useHealth } from './useHealth';
import { Metadata } from '../public/Metadata';

export function HealthPage() {
  const health = useHealth();
  return <main className="mx-auto max-w-2xl space-y-5 p-8"><Metadata title="API availability" seo={{ meta_title: 'API availability', meta_description: 'Application availability check.', robots: 'noindex,nofollow' }} /><h1 className="text-3xl font-semibold">API availability</h1>{health.isPending && <p role="status">Checking API…</p>}{health.isSuccess && <p role="status">API is available.</p>}{health.isError && <p role="alert">API is unavailable. <button onClick={() => void health.refetch()}>Retry</button></p>}<Link to="/">Return home</Link></main>;
}

