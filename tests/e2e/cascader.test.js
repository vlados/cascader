/**
 * Cascader Component E2E Tests
 *
 * These tests verify the interactive behavior of the cascader component
 * including navigation, selection, multi-select, and search functionality.
 */

const BASE_URL = 'http://localhost:3456';

describe('Cascader Component', () => {
  beforeAll(async () => {
    await page.goto(BASE_URL);
    // Wait for Alpine.js to initialize
    await page.waitForFunction(() => window.Alpine !== undefined);
    await page.waitForTimeout(500);
  });

  beforeEach(async () => {
    // Refresh and wait for initialization
    await page.goto(BASE_URL);
    await page.waitForFunction(() => window.Alpine !== undefined);
    await page.waitForTimeout(500);
  });

  describe('Basic Single Select', () => {
    it('should open dropdown when trigger is clicked', async () => {
      const trigger = await page.$('[data-testid="cascader-trigger"]');
      await trigger.click();

      await page.waitForSelector('[data-testid="desktop-dialog"][open]');
      const dialog = await page.$('[data-testid="desktop-dialog"]');
      const isOpen = await dialog.evaluate(el => el.hasAttribute('open'));

      expect(isOpen).toBe(true);
    });

    it('should show first level options initially', async () => {
      const trigger = await page.$('[data-testid="cascader-trigger"]');
      await trigger.click();

      await page.waitForSelector('[data-testid="desktop-dialog"][open]');

      // Check for first level options
      const electronics = await page.$('[data-testid="option-1"]');
      const clothing = await page.$('[data-testid="option-2"]');
      const other = await page.$('[data-testid="option-3"]');

      expect(electronics).not.toBeNull();
      expect(clothing).not.toBeNull();
      expect(other).not.toBeNull();
    });

    it('should show second level on hover/click', async () => {
      const trigger = await page.$('[data-testid="cascader-trigger"]');
      await trigger.click();

      await page.waitForSelector('[data-testid="desktop-dialog"][open]');

      // Click on Electronics
      const electronics = await page.$('[data-testid="option-1"]');
      await electronics.click();

      await page.waitForTimeout(300);

      // Check for second level options (Phones, Tablets)
      const phones = await page.$('[data-testid="option-11"]');
      const tablets = await page.$('[data-testid="option-12"]');

      expect(phones).not.toBeNull();
      expect(tablets).not.toBeNull();
    });

    it('should show third level on hover/click', async () => {
      const trigger = await page.$('[data-testid="cascader-trigger"]');
      await trigger.click();

      await page.waitForSelector('[data-testid="desktop-dialog"][open]');

      // Navigate: Electronics -> Phones
      const electronics = await page.$('[data-testid="option-1"]');
      await electronics.click();
      await page.waitForTimeout(200);

      const phones = await page.$('[data-testid="option-11"]');
      await phones.click();
      await page.waitForTimeout(200);

      // Check for third level options (iPhone, Android)
      const iphone = await page.$('[data-testid="option-111"]');
      const android = await page.$('[data-testid="option-112"]');

      expect(iphone).not.toBeNull();
      expect(android).not.toBeNull();
    });

    it('should select leaf node and close dropdown', async () => {
      const trigger = await page.$('[data-testid="cascader-trigger"]');
      await trigger.click();

      await page.waitForSelector('[data-testid="desktop-dialog"][open]');

      // Navigate and select: Electronics -> Phones -> iPhone
      await page.click('[data-testid="option-1"]');
      await page.waitForTimeout(200);
      await page.click('[data-testid="option-11"]');
      await page.waitForTimeout(200);
      await page.click('[data-testid="option-111"]');

      await page.waitForTimeout(500);

      // Check that dialog is closed
      const dialog = await page.$('[data-testid="desktop-dialog"]');
      const isOpen = await dialog.evaluate(el => el.hasAttribute('open'));
      expect(isOpen).toBe(false);

      // Check selected text
      const selectedText = await page.$eval('[data-testid="selected-text"]', el => el.textContent);
      expect(selectedText).toBe('Electronics / Phones / iPhone');
    });

    it('should select leaf node without children directly', async () => {
      const trigger = await page.$('[data-testid="cascader-trigger"]');
      await trigger.click();

      await page.waitForSelector('[data-testid="desktop-dialog"][open]');

      // Select "Other" which has no children
      await page.click('[data-testid="option-3"]');

      await page.waitForTimeout(500);

      // Check selected text
      const selectedText = await page.$eval('[data-testid="selected-text"]', el => el.textContent);
      expect(selectedText).toBe('Other');
    });

    it('should close dropdown when clicking outside', async () => {
      const trigger = await page.$('[data-testid="cascader-trigger"]');
      await trigger.click();

      await page.waitForSelector('[data-testid="desktop-dialog"][open]');

      // Click on the backdrop (dialog element itself)
      const dialog = await page.$('[data-testid="desktop-dialog"]');
      await dialog.click({ position: { x: 0, y: 0 } });

      await page.waitForTimeout(300);

      const isOpen = await dialog.evaluate(el => el.hasAttribute('open'));
      expect(isOpen).toBe(false);
    });
  });

  describe('Search Functionality', () => {
    it('should filter options when searching', async () => {
      const trigger = await page.$('[data-testid="cascader-trigger"]');
      await trigger.click();

      await page.waitForSelector('[data-testid="desktop-dialog"][open]');

      // Type in search
      const searchInput = await page.$('[data-testid="search-input"]');
      await searchInput.type('iPhone');

      await page.waitForTimeout(300);

      // Check search results appear
      const searchResults = await page.$('[data-testid="search-results"]');
      const isVisible = await searchResults.evaluate(el => {
        const style = window.getComputedStyle(el);
        return style.display !== 'none';
      });

      expect(isVisible).toBe(true);
    });

    it('should show full path in search results', async () => {
      const trigger = await page.$('[data-testid="cascader-trigger"]');
      await trigger.click();

      await page.waitForSelector('[data-testid="desktop-dialog"][open]');

      const searchInput = await page.$('[data-testid="search-input"]');
      await searchInput.type('iPhone');

      await page.waitForTimeout(300);

      const searchResult = await page.$('[data-testid="search-result"]');
      const resultText = await searchResult.evaluate(el => el.textContent.trim());

      expect(resultText).toBe('Electronics / Phones / iPhone');
    });

    it('should select from search results', async () => {
      const trigger = await page.$('[data-testid="cascader-trigger"]');
      await trigger.click();

      await page.waitForSelector('[data-testid="desktop-dialog"][open]');

      const searchInput = await page.$('[data-testid="search-input"]');
      await searchInput.type('iPhone');

      await page.waitForTimeout(300);

      const searchResult = await page.$('[data-testid="search-result"]');
      await searchResult.click();

      await page.waitForTimeout(500);

      // Check selected value
      const selectedText = await page.$eval('[data-testid="selected-text"]', el => el.textContent);
      expect(selectedText).toBe('Electronics / Phones / iPhone');
    });

    it('should search across all levels', async () => {
      const trigger = await page.$('[data-testid="cascader-trigger"]');
      await trigger.click();

      await page.waitForSelector('[data-testid="desktop-dialog"][open]');

      const searchInput = await page.$('[data-testid="search-input"]');
      await searchInput.type('Men');

      await page.waitForTimeout(300);

      const searchResult = await page.$('[data-testid="search-result"]');
      const resultText = await searchResult.evaluate(el => el.textContent.trim());

      expect(resultText).toBe('Clothing / Men');
    });
  });

  describe('Multi-Select Mode', () => {
    it('should allow selecting multiple items', async () => {
      const trigger = await page.$('[data-testid="multi-cascader-trigger"]');
      await trigger.click();

      await page.waitForSelector('[data-testid="multi-desktop-dialog"][open]');

      // Navigate to Fruits
      await page.click('[data-testid="multi-option-1"]');
      await page.waitForTimeout(200);

      // Select Apple
      await page.click('[data-testid="multi-option-11"]');
      await page.waitForTimeout(200);

      // Select Banana
      await page.click('[data-testid="multi-option-12"]');
      await page.waitForTimeout(200);

      // Check selected values
      const selectedValue = await page.$eval('[data-testid="multi-selected-value"]', el => el.textContent);
      const values = JSON.parse(selectedValue);

      expect(values).toContain(11);
      expect(values).toContain(12);
      expect(values.length).toBe(2);
    });

    it('should show checkbox for leaf nodes', async () => {
      const trigger = await page.$('[data-testid="multi-cascader-trigger"]');
      await trigger.click();

      await page.waitForSelector('[data-testid="multi-desktop-dialog"][open]');

      // Navigate to Fruits
      await page.click('[data-testid="multi-option-1"]');
      await page.waitForTimeout(200);

      // Check for checkbox
      const checkbox = await page.$('[data-testid="checkbox"]');
      expect(checkbox).not.toBeNull();
    });

    it('should toggle selection on click', async () => {
      const trigger = await page.$('[data-testid="multi-cascader-trigger"]');
      await trigger.click();

      await page.waitForSelector('[data-testid="multi-desktop-dialog"][open]');

      // Navigate and select
      await page.click('[data-testid="multi-option-1"]');
      await page.waitForTimeout(200);
      await page.click('[data-testid="multi-option-11"]');
      await page.waitForTimeout(200);

      // Verify selected
      let selectedValue = await page.$eval('[data-testid="multi-selected-value"]', el => el.textContent);
      expect(JSON.parse(selectedValue)).toContain(11);

      // Deselect
      await page.click('[data-testid="multi-option-11"]');
      await page.waitForTimeout(200);

      // Verify deselected
      selectedValue = await page.$eval('[data-testid="multi-selected-value"]', el => el.textContent);
      expect(JSON.parse(selectedValue)).not.toContain(11);
    });

    it('should show count of selected items', async () => {
      const trigger = await page.$('[data-testid="multi-cascader-trigger"]');
      await trigger.click();

      await page.waitForSelector('[data-testid="multi-desktop-dialog"][open]');

      // Navigate and select multiple
      await page.click('[data-testid="multi-option-1"]');
      await page.waitForTimeout(200);
      await page.click('[data-testid="multi-option-11"]');
      await page.waitForTimeout(100);
      await page.click('[data-testid="multi-option-12"]');
      await page.waitForTimeout(100);
      await page.click('[data-testid="multi-option-13"]');
      await page.waitForTimeout(200);

      // Close and check text
      await page.keyboard.press('Escape');
      await page.waitForTimeout(300);

      const selectedText = await page.$eval('[data-testid="multi-selected-text"]', el => el.textContent);
      expect(selectedText).toBe('3 selected');
    });
  });

  describe('Deep Nesting (5 Levels)', () => {
    it('should navigate through all 5 levels', async () => {
      const trigger = await page.$('[data-testid="deep-cascader-trigger"]');
      await trigger.click();

      await page.waitForSelector('[data-testid="deep-desktop-dialog"][open]');

      // Level 1
      await page.click('[data-testid="deep-option-1"]');
      await page.waitForTimeout(200);

      // Level 2
      await page.click('[data-testid="deep-option-2"]');
      await page.waitForTimeout(200);

      // Level 3
      await page.click('[data-testid="deep-option-3"]');
      await page.waitForTimeout(200);

      // Level 4
      await page.click('[data-testid="deep-option-4"]');
      await page.waitForTimeout(200);

      // Level 5 (leaf)
      await page.click('[data-testid="deep-option-5"]');
      await page.waitForTimeout(500);

      // Check selected text shows full path
      const selectedText = await page.$eval('[data-testid="deep-selected-text"]', el => el.textContent);
      expect(selectedText).toBe('Level 1 / Level 2 / Level 3 / Level 4 / Level 5 Leaf');
    });

    it('should show correct number of columns', async () => {
      const trigger = await page.$('[data-testid="deep-cascader-trigger"]');
      await trigger.click();

      await page.waitForSelector('[data-testid="deep-desktop-dialog"][open]');

      // Initially only 1 column
      let columns = await page.$$('[data-testid^="deep-column-"]');
      expect(columns.length).toBeGreaterThanOrEqual(1);

      // Navigate through levels
      await page.click('[data-testid="deep-option-1"]');
      await page.waitForTimeout(200);
      await page.click('[data-testid="deep-option-2"]');
      await page.waitForTimeout(200);
      await page.click('[data-testid="deep-option-3"]');
      await page.waitForTimeout(200);
      await page.click('[data-testid="deep-option-4"]');
      await page.waitForTimeout(300);

      // Should have 5 columns now (4 parent levels + 1 leaf level)
      columns = await page.$$('[data-testid^="deep-column-"]');
      expect(columns.length).toBe(5);
    });
  });

  describe('Keyboard Navigation', () => {
    it('should close dropdown on Escape key', async () => {
      const trigger = await page.$('[data-testid="cascader-trigger"]');
      await trigger.click();

      await page.waitForSelector('[data-testid="desktop-dialog"][open]');

      await page.keyboard.press('Escape');
      await page.waitForTimeout(300);

      const dialog = await page.$('[data-testid="desktop-dialog"]');
      const isOpen = await dialog.evaluate(el => el.hasAttribute('open'));
      expect(isOpen).toBe(false);
    });

    it('should focus search input on open', async () => {
      const trigger = await page.$('[data-testid="cascader-trigger"]');
      await trigger.click();

      await page.waitForSelector('[data-testid="desktop-dialog"][open]');
      await page.waitForTimeout(100);

      const activeElement = await page.evaluate(() => document.activeElement?.getAttribute('data-testid'));
      expect(activeElement).toBe('search-input');
    });
  });

  describe('State Preservation', () => {
    it('should restore navigation state when reopening', async () => {
      const trigger = await page.$('[data-testid="cascader-trigger"]');
      await trigger.click();

      await page.waitForSelector('[data-testid="desktop-dialog"][open]');

      // Navigate and select
      await page.click('[data-testid="option-1"]');
      await page.waitForTimeout(200);
      await page.click('[data-testid="option-11"]');
      await page.waitForTimeout(200);
      await page.click('[data-testid="option-111"]');

      await page.waitForTimeout(500);

      // Reopen
      await trigger.click();
      await page.waitForSelector('[data-testid="desktop-dialog"][open]');
      await page.waitForTimeout(300);

      // Should show the path to selected item
      const iphone = await page.$('[data-testid="option-111"]');
      expect(iphone).not.toBeNull();
    });
  });
});
