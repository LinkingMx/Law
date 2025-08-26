import { chromium } from 'playwright';

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();

  try {
    console.log('🔐 Navigating to login page...');
    await page.goto('http://saashelpdesk.test/admin/login');
    
    // Take screenshot of login page
    await page.screenshot({ path: 'screenshots/01-login-page.png', fullPage: true });
    console.log('📸 Login page screenshot saved');

    // Fill in login credentials
    console.log('📝 Filling login credentials...');
    await page.fill('#data\\.email', 'armando.reyes@grupocosteno.com');
    await page.fill('#data\\.password', 'C@sten0.2019+');
    
    // Submit login form
    console.log('🚀 Submitting login...');
    await page.click('button[type="submit"]');
    
    // Wait for dashboard or redirect
    await page.waitForURL('**/admin/**', { timeout: 10000 });
    console.log('✅ Successfully logged in');

    // Navigate to backup history
    console.log('📂 Navigating to backup history...');
    await page.goto('http://saashelpdesk.test/admin/backup-history');
    
    // Wait for page to load
    await page.waitForLoadState('networkidle');
    
    // Take screenshot of backup history page
    await page.screenshot({ path: 'screenshots/02-backup-history.png', fullPage: true });
    console.log('📸 Backup history screenshot saved');

    // Analyze UI elements
    console.log('\n🔍 ANALYZING UI ELEMENTS:');
    
    // Check page title
    const pageTitle = await page.title();
    console.log(`📄 Page Title: ${pageTitle}`);
    
    // Check for main heading
    const heading = await page.locator('h1, h2').first().textContent();
    console.log(`📋 Main Heading: ${heading}`);
    
    // Check for table
    const tableExists = await page.locator('table').count();
    console.log(`📊 Tables found: ${tableExists}`);
    
    if (tableExists > 0) {
      // Analyze table headers
      const headers = await page.locator('th').allTextContents();
      console.log(`📑 Table Headers: ${headers.join(', ')}`);
      
      // Count table rows
      const rowCount = await page.locator('tbody tr').count();
      console.log(`📈 Data Rows: ${rowCount}`);
    }
    
    // Check for action buttons
    const buttons = await page.locator('button').allTextContents();
    console.log(`🔘 Buttons found: ${buttons.join(', ')}`);
    
    // Check for refresh/update button specifically
    const refreshButton = await page.locator('button:has-text("Actualizar"), button:has-text("Refresh")').count();
    console.log(`🔄 Refresh button found: ${refreshButton > 0 ? 'Yes' : 'No'}`);
    
    // Test clicking refresh if it exists
    if (refreshButton > 0) {
      console.log('🔄 Testing refresh button...');
      await page.locator('button:has-text("Actualizar"), button:has-text("Refresh")').first().click();
      await page.waitForTimeout(2000);
      await page.screenshot({ path: 'screenshots/03-after-refresh.png', fullPage: true });
      console.log('📸 After refresh screenshot saved');
    }
    
    // Check for any error messages
    const errorMessages = await page.locator('.error, .alert-danger, [role="alert"]').allTextContents();
    if (errorMessages.length > 0) {
      console.log(`❌ Error messages found: ${errorMessages.join(', ')}`);
    } else {
      console.log('✅ No error messages detected');
    }
    
    // Check navigation
    const navItems = await page.locator('nav a, .navigation a').allTextContents();
    console.log(`🧭 Navigation items: ${navItems.slice(0, 10).join(', ')}${navItems.length > 10 ? '...' : ''}`);
    
    // Check for backup-specific elements
    const backupActions = await page.locator('button:has-text("Descargar"), button:has-text("Download"), button:has-text("Eliminar"), button:has-text("Delete"), button:has-text("Validar"), button:has-text("Validate")').count();
    console.log(`💾 Backup action buttons found: ${backupActions}`);
    
    console.log('\n📊 UI ANALYSIS COMPLETE');
    
  } catch (error) {
    console.error('❌ Error during analysis:', error);
    await page.screenshot({ path: 'screenshots/error.png', fullPage: true });
  } finally {
    await browser.close();
  }
})();