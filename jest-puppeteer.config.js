module.exports = {
  launch: {
    headless: 'new',
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
  },
  server: {
    command: 'npx serve tests/e2e/fixtures -l 3456',
    port: 3456,
    launchTimeout: 10000,
    usedPortAction: 'kill',
  },
};
