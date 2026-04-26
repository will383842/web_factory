import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
    },
  },
  test: {
    globals: true,
    environment: 'jsdom',
    include: ['tests/js/**/*.{test,spec}.{ts,tsx}', 'resources/js/**/*.{test,spec}.{ts,tsx}'],
    exclude: ['node_modules', 'tests/Browser', 'tests/Feature', 'tests/Unit', 'tests/Arch'],
    coverage: {
      provider: 'v8',
      reporter: ['text', 'lcov', 'html'],
      reportsDirectory: './coverage',
      include: ['resources/js/**/*.{ts,vue}'],
      exclude: ['**/*.d.ts', '**/types/**', '**/Pages/**'],
      thresholds: {
        lines: 60,
        statements: 60,
        functions: 60,
        branches: 50,
      },
    },
  },
});
