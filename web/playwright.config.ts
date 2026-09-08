import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './e2e',
  use: { channel: 'chromium', baseURL: 'http://127.0.0.1:4177' },
  webServer: { command: 'npm run dev -- --host 127.0.0.1 --port 4177', url: 'http://127.0.0.1:4177', reuseExistingServer: !process.env.CI },
});
