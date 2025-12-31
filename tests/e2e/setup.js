// Global setup for Puppeteer tests
jest.setTimeout(30000);

// Custom matchers
expect.extend({
  async toBeVisible(element) {
    const isVisible = await element.isIntersectingViewport();
    return {
      pass: isVisible,
      message: () => `Expected element to ${isVisible ? 'not ' : ''}be visible`,
    };
  },
});
