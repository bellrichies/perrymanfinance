import { spawn, spawnSync } from 'node:child_process';
import { mkdir, rm, writeFile, access, open } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const root = fileURLToPath(new URL('../', import.meta.url));
const state = path.join(root, '.dev');
const stopFile = path.join(state, 'stop');
const lockFile = path.join(state, 'lock');
const apiPort = Number(process.env.DEV_API_PORT ?? 8090);
const webPort = Number(process.env.DEV_WEB_PORT ?? 5173);
if (![apiPort, webPort].every(port => Number.isInteger(port) && port > 1023 && port <= 65535)) throw new Error('Development ports must be integers from 1024 to 65535.');
await mkdir(state, { recursive: true });
if (process.argv[2] === 'down') {
  await writeFile(stopFile, 'stop');
  console.log('Shutdown requested for the development servers started by up.');
} else if (process.argv[2] === 'up') {
  const php = spawnSync('php', ['-r', 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);'], { windowsHide: true });
  if (php.error || php.status !== 0) throw new Error('PHP 8.3+ must be available on PATH.');
  let lock;
  try { lock = await open(lockFile, 'wx'); }
  catch { throw new Error('Development supervisor already running. Use down first; remove .dev/lock only after verifying a previous supervisor has exited.'); }
  await rm(stopFile, { force: true });
  const children = [
    spawn('php', ['-S', `127.0.0.1:${apiPort}`, '-t', 'backend/public'], { cwd: root, stdio: 'inherit', windowsHide: true }),
    spawn(process.execPath, ['node_modules/vite/bin/vite.js', '--host', 'localhost', '--port', String(webPort), '--strictPort'], { cwd: path.join(root, 'web'), env: { ...process.env, VITE_DEV_PROXY_TARGET: `http://127.0.0.1:${apiPort}` }, stdio: 'inherit', windowsHide: true }),
  ];
  let stopping = false;
  async function stop(code = 0) {
    if (stopping) return;
    stopping = true;
    clearInterval(timer);
    await Promise.all(children.map(child => new Promise(resolve => {
      if (child.exitCode !== null || child.signalCode !== null || !child.pid) return resolve();
      child.once('exit', resolve);
      child.kill();
    })));
    await lock.close();
    await rm(lockFile, { force: true });
    await rm(stopFile, { force: true });
    process.exitCode = code;
  }
  const timer = setInterval(() => { void access(stopFile).then(() => stop()).catch(() => {}); }, 300);
  for (const child of children) {
    child.once('error', error => { console.error(error.message); void stop(1); });
    child.once('exit', code => { if (!stopping) void stop(code ?? 1); });
  }
  process.once('SIGINT', () => void stop());
  process.once('SIGTERM', () => void stop());
} else {
  throw new Error('Usage: node scripts/dev.mjs up|down');
}

