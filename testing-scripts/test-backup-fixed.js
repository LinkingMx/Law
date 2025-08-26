import { chromium } from 'playwright';

(async () => {
  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext();
  const page = await context.newPage();

  try {
    console.log('🔐 Navigating to login page...');
    await page.goto('http://saashelpdesk.test/admin/login');
    
    // Wait for page to load completely
    await page.waitForLoadState('networkidle');
    
    // Take screenshot of login page
    await page.screenshot({ path: 'screenshots/01-login-page.png', fullPage: true });
    console.log('📸 Login page screenshot saved');

    // Fill in login credentials with retry
    console.log('📝 Filling login credentials...');
    await page.waitForSelector('#data\\.email', { timeout: 10000 });
    await page.fill('#data\\.email', 'armando.reyes@grupocosteno.com');
    await page.fill('#data\\.password', 'C@sten0.2019+');
    
    // Wait a moment for form to be ready
    await page.waitForTimeout(1000);
    
    // Submit login form
    console.log('🚀 Submitting login...');
    await page.click('button[type="submit"]');
    
    // Wait for navigation after login
    console.log('⏳ Waiting for redirect after login...');
    await page.waitForURL('**/admin/**', { timeout: 15000 });
    
    // Check current URL
    const currentUrl = page.url();
    console.log(`📍 Current URL after login: ${currentUrl}`);
    
    console.log('✅ Successfully logged in');

    // Navigate to backup history
    console.log('📂 Navigating to backup history...');
    await page.goto('http://saashelpdesk.test/admin/backup-history');
    
    // Wait for page to load
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000); // Extra wait for dynamic content
    
    // Check current URL
    const backupUrl = page.url();
    console.log(`📍 Current URL: ${backupUrl}`);
    
    // Take screenshot of backup history page
    await page.screenshot({ path: 'screenshots/02-backup-history.png', fullPage: true });
    console.log('📸 Backup history screenshot saved');

    // Analyze UI elements
    console.log('\n🔍 ANALYZING UI ELEMENTS:');
    
    // Check page title
    const pageTitle = await page.title();
    console.log(`📄 Page Title: ${pageTitle}`);
    
    // Check for main heading
    const headings = await page.locator('h1, h2, .text-xl, .text-2xl').allTextContents();
    console.log(`📋 Headings found: ${headings.filter(h => h.trim()).join(', ')}`);
    
    // Check for breadcrumbs or page indicators
    const breadcrumbs = await page.locator('.breadcrumb, [aria-label="breadcrumb"], .fi-breadcrumbs').allTextContents();
    if (breadcrumbs.length > 0) {
      console.log(`🍞 Breadcrumbs: ${breadcrumbs.join(', ')}`);
    }
    
    // Check for table
    const tableExists = await page.locator('table').count();
    console.log(`📊 Tables found: ${tableExists}`);
    
    if (tableExists > 0) {
      // Analyze table headers
      const headers = await page.locator('th').allTextContents();
      console.log(`📑 Table Headers: ${headers.filter(h => h.trim()).join(', ')}`);
      
      // Count table rows (excluding header)
      const rowCount = await page.locator('tbody tr').count();
      console.log(`📈 Data Rows: ${rowCount}`);
      
      // Get sample data from first few rows
      if (rowCount > 0) {
        const firstRowData = await page.locator('tbody tr').first().locator('td').allTextContents();
        console.log(`📋 First row data: ${firstRowData.filter(d => d.trim()).join(' | ')}`);
      }
    }
    
    // Check for action buttons (more specific to Filament)
    const allButtons = await page.locator('button, .fi-btn').allTextContents();
    const cleanButtons = allButtons.filter(b => b.trim()).map(b => b.trim().replace(/\s+/g, ' '));
    console.log(`🔘 Buttons found: ${cleanButtons.join(', ')}`);
    
    // Check for refresh/update button specifically
    const refreshButton = await page.locator('button:has-text("Actualizar"), button:has-text("Refresh"), .fi-btn:has-text("Actualizar")').count();
    console.log(`🔄 Refresh button found: ${refreshButton > 0 ? 'Yes' : 'No'}`);
    
    // Test clicking refresh if it exists
    if (refreshButton > 0) {
      console.log('🔄 Testing refresh button...');
      try {
        await page.locator('button:has-text("Actualizar"), button:has-text("Refresh"), .fi-btn:has-text("Actualizar")').first().click();
        await page.waitForTimeout(3000);
        await page.screenshot({ path: 'screenshots/03-after-refresh.png', fullPage: true });
        console.log('📸 After refresh screenshot saved');
      } catch (refreshError) {
        console.log(`⚠️ Error clicking refresh: ${refreshError.message}`);
      }
    }
    
    // Check for any error messages or notifications
    const errorSelectors = [
      '.error', 
      '.alert-danger', 
      '[role="alert"]',
      '.fi-notification',
      '.notification',
      '.alert'
    ];
    
    for (const selector of errorSelectors) {
      const elements = await page.locator(selector).allTextContents();
      if (elements.length > 0) {
        console.log(`❌ Messages found (${selector}): ${elements.filter(e => e.trim()).join(', ')}`);
      }
    }
    
    // Check navigation (Filament sidebar)
    const navItems = await page.locator('.fi-sidebar-nav a, nav a, .navigation a').allTextContents();
    const cleanNavItems = navItems.filter(n => n.trim()).map(n => n.trim());
    if (cleanNavItems.length > 0) {
      console.log(`🧭 Navigation items: ${cleanNavItems.slice(0, 10).join(', ')}${cleanNavItems.length > 10 ? '...' : ''}`);
    }
    
    // Check for backup-specific elements
    const backupActionSelectors = [
      'button:has-text("Descargar")',
      'button:has-text("Download")', 
      'button:has-text("Eliminar")',
      'button:has-text("Delete")',
      'button:has-text("Validar")',
      'button:has-text("Validate")',
      '.fi-dropdown-list-item'
    ];
    
    let backupActions = 0;
    for (const selector of backupActionSelectors) {
      backupActions += await page.locator(selector).count();
    }
    console.log(`💾 Backup action elements found: ${backupActions}`);
    
    // Check for empty state or no data messages
    const emptyStateSelectors = [
      '.empty-state',
      '.no-data', 
      ':has-text("No backups found")',
      ':has-text("Sin datos")',
      '.fi-ta-empty-state'
    ];
    
    for (const selector of emptyStateSelectors) {
      const emptyElements = await page.locator(selector).count();
      if (emptyElements > 0) {
        console.log(`📭 Empty state found with selector: ${selector}`);
      }
    }
    
    // Final page structure analysis
    const pageStructure = await page.locator('main, .fi-main, .content').count();
    console.log(`🏗️ Main content areas found: ${pageStructure}`);
    
    console.log('\n📊 UI ANALYSIS COMPLETE');
    
    // Keep browser open for manual inspection
    console.log('🔍 Browser kept open for manual inspection. Close it manually when done.');
    await page.waitForTimeout(30000); // Wait 30 seconds before auto-closing
    
  } catch (error) {
    console.error('❌ Error during analysis:', error);
    await page.screenshot({ path: 'screenshots/error.png', fullPage: true });
  } finally {
    await browser.close();
  }
})();