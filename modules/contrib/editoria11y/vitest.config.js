import { defineConfig } from 'vitest/config';

/**
 * Vitest config for the module's JS unit tests.
 *
 * editoria11yOptions.js is native ESM (`export function …`), so we only need
 * a jsdom environment (the transform reads `window`) and the global shims in
 * tests/js/unit/setup.js (Drupal / drupalSettings).
 *
 * `globals: true` exposes describe/test/expect without imports.
 */
export default defineConfig({
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: ['./tests/js/unit/setup.js'],
    include: ['tests/js/unit/**/*.test.js'],
  },
});
