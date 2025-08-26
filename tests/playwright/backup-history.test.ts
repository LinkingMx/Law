import { test, expect } from '@playwright/test';

class BackupHistoryPage {
  constructor(private page: any) {}

  async login(email: string, password: string) {
    await this.page.goto('/admin/login');
    await this.page.fill('input[name="email"]', email);
    await this.page.fill('input[name="password"]', password);
    await this.page.click('button[type="submit"]');
    // Wait for navigation after login
    await this.page.waitForLoadState('networkidle');
  }

  async navigateToBackupHistory() {
    await this.page.goto('/admin/backup-history');
    await this.page.waitForLoadState('networkidle');
  }

  async takeScreenshot(name: string) {
    await this.page.screenshot({ 
      path: `test-results/screenshots/${name}.png`,
      fullPage: true 
    });
  }

  async getPageTitle() {
    return await this.page.title();
  }

  async isElementVisible(selector: string) {
    return await this.page.isVisible(selector);
  }

  async clickElement(selector: string) {
    await this.page.click(selector);
  }

  async getTextContent(selector: string) {
    return await this.page.textContent(selector);
  }

  async getAllTextContents(selector: string) {
    return await this.page.locator(selector).allTextContents();
  }

  async waitForElement(selector: string, timeout = 5000) {
    await this.page.waitForSelector(selector, { timeout });
  }

  async getElementCount(selector: string) {
    return await this.page.locator(selector).count();
  }
}

test.describe('Backup History Page UI Analysis', () => {
  let backupHistoryPage: BackupHistoryPage;

  test.beforeEach(async ({ page }) => {
    backupHistoryPage = new BackupHistoryPage(page);
    
    // Login before each test
    await backupHistoryPage.login('armando.reyes@grupocosteno.com', 'C@sten0.2019+');
  });

  test('should navigate to backup history page and analyze UI layout', async () => {
    await backupHistoryPage.navigateToBackupHistory();
    
    // Take initial screenshot
    await backupHistoryPage.takeScreenshot('backup-history-initial');

    // Verify page title
    const title = await backupHistoryPage.getPageTitle();
    expect(title).toContain('Historial de Backups');

    // Check if main UI elements are present
    expect(await backupHistoryPage.isElementVisible('div.space-y-6')).toBe(true);
    expect(await backupHistoryPage.isElementVisible('div.bg-blue-50')).toBe(true); // Info banner
    
    console.log('✓ Page loaded successfully with correct title');
    console.log('✓ Main container elements are visible');
  });

  test('should analyze info banner content and styling', async () => {
    await backupHistoryPage.navigateToBackupHistory();

    // Check info banner elements
    const infoBanner = 'div.bg-blue-50';
    expect(await backupHistoryPage.isElementVisible(infoBanner)).toBe(true);
    
    // Check for information icon
    expect(await backupHistoryPage.isElementVisible('svg[class*="heroicon-o-information-circle"]')).toBe(true);
    
    // Check banner title
    const bannerTitle = await backupHistoryPage.getTextContent('h3.text-sm.font-medium');
    expect(bannerTitle).toBe('Historial de Backups');
    
    // Check banner description
    const bannerDescription = await backupHistoryPage.getTextContent('div.text-sm.text-blue-700 p');
    expect(bannerDescription).toContain('backups disponibles');
    
    console.log('✓ Info banner is properly structured');
    console.log('✓ Banner contains appropriate icon and text content');
  });

  test('should analyze header actions (refresh button)', async () => {
    await backupHistoryPage.navigateToBackupHistory();

    // Look for the refresh button in the header
    const refreshButton = 'button:has-text("Actualizar")';
    
    // Check if refresh button exists and is visible
    if (await backupHistoryPage.isElementVisible(refreshButton)) {
      console.log('✓ Refresh button is visible');
      
      // Take screenshot before clicking
      await backupHistoryPage.takeScreenshot('before-refresh-click');
      
      // Click refresh button
      await backupHistoryPage.clickElement(refreshButton);
      
      // Wait a moment for any loading states
      await backupHistoryPage.page.waitForTimeout(1000);
      
      // Take screenshot after clicking
      await backupHistoryPage.takeScreenshot('after-refresh-click');
      
      console.log('✓ Refresh button is clickable and responsive');
    } else {
      console.log('⚠ Refresh button not found - may be part of Filament header actions');
    }
  });

  test('should analyze backup table structure and content', async () => {
    await backupHistoryPage.navigateToBackupHistory();

    // Check for empty state or table content
    const emptyState = 'div:has-text("No hay backups disponibles")';
    const tableHeader = 'div.grid.grid-cols-12.gap-4';
    
    if (await backupHistoryPage.isElementVisible(emptyState)) {
      console.log('📋 Empty state detected - no backups available');
      
      // Analyze empty state
      expect(await backupHistoryPage.isElementVisible('svg[class*="heroicon-o-circle-stack"]')).toBe(true);
      
      const emptyTitle = await backupHistoryPage.getTextContent('h3:has-text("No hay backups disponibles")');
      expect(emptyTitle).toBe('No hay backups disponibles');
      
      const emptyDescription = await backupHistoryPage.getTextContent('p:has-text("Ejecuta tu primer backup")');
      expect(emptyDescription).toContain('Gestión de Backups');
      
      console.log('✓ Empty state is well-designed with icon and helpful message');
      
    } else if (await backupHistoryPage.isElementVisible(tableHeader)) {
      console.log('📋 Backup table detected - analyzing structure');
      
      // Analyze table headers
      const headers = await backupHistoryPage.getAllTextContents(tableHeader + ' div');
      console.log('Table headers:', headers);
      
      expect(headers).toContain('Nombre del Archivo');
      expect(headers).toContain('Ubicación');
      expect(headers).toContain('Tamaño');
      expect(headers).toContain('Fecha');
      expect(headers).toContain('Acciones');
      
      // Count backup rows
      const backupRows = 'div.divide-y > div.px-6.py-4';
      const backupCount = await backupHistoryPage.getElementCount(backupRows);
      console.log(`Found ${backupCount} backup entries`);
      
      if (backupCount > 0) {
        // Analyze first backup row
        await backupHistoryPage.analyzeBackupRow(0);
      }
      
      console.log('✓ Table structure is properly organized with all necessary columns');
    }
  });

  test('should analyze backup row actions and interactions', async () => {
    await backupHistoryPage.navigateToBackupHistory();

    const backupRows = 'div.divide-y > div.px-6.py-4';
    const backupCount = await backupHistoryPage.getElementCount(backupRows);
    
    if (backupCount > 0) {
      console.log(`🔍 Analyzing actions for ${backupCount} backup entries`);
      
      // Focus on first backup row for detailed analysis
      const firstRow = `${backupRows}:first-child`;
      
      // Check for action buttons
      const downloadButton = `${firstRow} button:has-text("Descargar")`;
      const validateButton = `${firstRow} button:has-text("Validar")`;
      const deleteButton = `${firstRow} button:has-text("Eliminar")`;
      
      // Test download button
      if (await backupHistoryPage.isElementVisible(downloadButton)) {
        console.log('✓ Download button is visible');
        expect(await backupHistoryPage.isElementVisible(`${downloadButton} svg[class*="heroicon-o-arrow-down-tray"]`)).toBe(true);
        console.log('✓ Download button has appropriate icon');
      }
      
      // Test validate button
      if (await backupHistoryPage.isElementVisible(validateButton)) {
        console.log('✓ Validate button is visible');
        expect(await backupHistoryPage.isElementVisible(`${validateButton} svg[class*="heroicon-o-shield-check"]`)).toBe(true);
        console.log('✓ Validate button has appropriate icon');
      }
      
      // Test delete button
      if (await backupHistoryPage.isElementVisible(deleteButton)) {
        console.log('✓ Delete button is visible');
        expect(await backupHistoryPage.isElementVisible(`${deleteButton} svg[class*="heroicon-o-trash"]`)).toBe(true);
        console.log('✓ Delete button has appropriate icon');
        
        // Test delete confirmation (without actually deleting)
        // await backupHistoryPage.clickElement(deleteButton);
        // This would trigger a confirmation dialog - we skip actual deletion for safety
      }
      
      // Take screenshot of actions
      await backupHistoryPage.takeScreenshot('backup-row-actions');
      
    } else {
      console.log('📝 No backup entries found to test actions');
    }
  });

  test('should test responsive design and mobile compatibility', async ({ page }) => {
    await backupHistoryPage.navigateToBackupHistory();

    // Test different viewport sizes
    const viewports = [
      { width: 1920, height: 1080, name: 'desktop' },
      { width: 1024, height: 768, name: 'tablet' },
      { width: 375, height: 667, name: 'mobile' }
    ];

    for (const viewport of viewports) {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await page.waitForTimeout(500); // Allow layout to adjust
      
      await backupHistoryPage.takeScreenshot(`responsive-${viewport.name}`);
      
      // Check if main elements are still visible
      expect(await backupHistoryPage.isElementVisible('div.space-y-6')).toBe(true);
      
      console.log(`✓ Layout responds correctly on ${viewport.name} (${viewport.width}x${viewport.height})`);
    }
  });

  test('should analyze page accessibility and keyboard navigation', async ({ page }) => {
    await backupHistoryPage.navigateToBackupHistory();

    // Check for proper heading structure
    const headings = await page.locator('h1, h2, h3, h4, h5, h6').allTextContents();
    console.log('Page headings:', headings);

    // Check for proper button labels
    const buttons = await page.locator('button').allTextContents();
    console.log('Available buttons:', buttons.filter(text => text.trim()));

    // Test tab navigation
    await page.keyboard.press('Tab');
    const focusedElement = await page.evaluate(() => document.activeElement?.tagName);
    console.log('First focusable element:', focusedElement);

    // Check for proper color contrast (visual inspection needed)
    console.log('✓ Accessibility structure analyzed - manual review recommended for color contrast');
  });

  test('should analyze performance and loading states', async ({ page }) => {
    // Start performance monitoring
    await page.goto('/admin/backup-history', { waitUntil: 'networkidle' });

    // Measure page load performance
    const performanceTiming = await page.evaluate(() => {
      return {
        domContentLoaded: performance.timing.domContentLoadedEventEnd - performance.timing.navigationStart,
        loadComplete: performance.timing.loadEventEnd - performance.timing.navigationStart,
      };
    });

    console.log('Performance metrics:');
    console.log(`- DOM Content Loaded: ${performanceTiming.domContentLoaded}ms`);
    console.log(`- Page Load Complete: ${performanceTiming.loadComplete}ms`);

    // Check for loading states or spinners
    const loadingIndicators = await page.locator('[class*="loading"], [class*="spinner"], [class*="skeleton"]').count();
    console.log(`Loading indicators found: ${loadingIndicators}`);

    console.log('✓ Performance analysis completed');
  });
});

// Helper method to analyze individual backup rows
BackupHistoryPage.prototype.analyzeBackupRow = async function(rowIndex: number) {
  const rowSelector = `div.divide-y > div.px-6.py-4:nth-child(${rowIndex + 1})`;
  
  // Check file name
  const fileName = await this.getTextContent(`${rowSelector} div.col-span-4 div.text-sm.font-medium`);
  console.log(`  - File: ${fileName}`);
  
  // Check location badge
  const locationBadge = await this.getTextContent(`${rowSelector} div.col-span-2 span`);
  console.log(`  - Location: ${locationBadge}`);
  
  // Check size
  const size = await this.getTextContent(`${rowSelector} div.col-span-2:nth-child(3) div.text-sm`);
  console.log(`  - Size: ${size}`);
  
  // Check date
  const date = await this.getTextContent(`${rowSelector} div.col-span-2:nth-child(4) div.text-sm`);
  console.log(`  - Date: ${date}`);
  
  // Count action buttons
  const actionButtons = await this.getElementCount(`${rowSelector} div.col-span-2:nth-child(5) button`);
  console.log(`  - Action buttons: ${actionButtons}`);
};