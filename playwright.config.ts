import { defineConfig } from '@playwright/test';
import baseConfig from '@wordpress/scripts/config/playwright.config.js';

export default defineConfig({
  ...baseConfig,
  testDir: './Test',
  use: {
    ...baseConfig.use,
    baseURL: process.env.WP_BASE_URL || 'http://localhost:8889',
  },
});