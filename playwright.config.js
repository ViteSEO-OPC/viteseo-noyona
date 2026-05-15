const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
  testDir: './tests',

  reporter: 'html',

  use: {
    baseURL: 'http://noyonaqa.local',
    trace: 'on-first-retry',
  },
});