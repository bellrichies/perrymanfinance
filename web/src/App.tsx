import { useHealth } from './features/health/useHealth';

export function App() {
  const health = useHealth();

  return (
    <main className="flex min-h-screen items-center justify-center px-6 py-16">
      <section className="w-full max-w-3xl rounded-3xl border border-blue-900 bg-slate-950/70 p-8 shadow-2xl shadow-black/30 sm:p-12">
        <p className="mb-4 text-sm font-semibold uppercase tracking-[0.25em] text-emerald-300">
          PerrymanFinance
        </p>
        <h1 className="max-w-2xl text-4xl font-semibold tracking-tight text-white sm:text-5xl">
          Engineering foundation is ready.
        </h1>
        <p className="mt-5 max-w-2xl text-lg leading-8 text-slate-300">
          This Phase 1 screen verifies the public web application and its connection to the REST API.
        </p>

        <div className="mt-10 rounded-2xl border border-slate-800 bg-slate-900 p-5" aria-live="polite">
          <p className="text-sm font-medium text-slate-400">API status</p>
          {health.isPending && <p className="mt-2 text-white">Checking API availability…</p>}
          {health.isError && (
            <div className="mt-2">
              <p className="text-rose-300">The API is currently unavailable.</p>
              <button
                className="mt-4 rounded-lg bg-emerald-300 px-4 py-2 font-semibold text-slate-950"
                type="button"
                onClick={() => void health.refetch()}
              >
                Try again
              </button>
            </div>
          )}
          {health.data && (
            <p className="mt-2 flex items-center gap-3 font-semibold text-emerald-300">
              <span className="h-2.5 w-2.5 rounded-full bg-emerald-300" aria-hidden="true" />
              API operational · version {health.data.data.version}
            </p>
          )}
        </div>
      </section>
    </main>
  );
}

