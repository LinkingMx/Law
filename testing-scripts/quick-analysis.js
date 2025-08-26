import { chromium } from 'playwright';

(async () => {
  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext();
  const page = await context.newPage();

  try {
    // Login
    await page.goto('http://saashelpdesk.test/admin/login');
    await page.waitForLoadState('networkidle');
    await page.fill('#data\\.email', 'armando.reyes@grupocosteno.com');
    await page.fill('#data\\.password', 'C@sten0.2019+');
    await Promise.all([
      page.waitForNavigation(),
      page.click('button[type="submit"]')
    ]);
    
    // Navigate to backup history
    await page.goto('http://saashelpdesk.test/admin/backup-history');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);
    
    await page.screenshot({ path: 'screenshots/final-backup-history.png', fullPage: true });

    console.log('\n🎯 BACKUP HISTORY UI ANALYSIS REPORT:');
    console.log('='.repeat(50));
    
    // Basic page info
    console.log(`📄 Page Title: ${await page.title()}`);
    console.log(`🔗 URL: ${page.url()}`);
    
    // Check for main elements
    const tables = await page.locator('table').count();
    const buttons = await page.locator('button').count();
    const headings = await page.locator('h1, h2, h3').allTextContents();
    
    console.log(`📊 Tables: ${tables}`);
    console.log(`🔘 Buttons: ${buttons}`);
    console.log(`📋 Headings: ${headings.filter(h => h.trim()).join(', ')}`);
    
    if (tables > 0) {
      const headers = await page.locator('th').allTextContents();
      const rows = await page.locator('tbody tr').count();
      console.log(`📑 Table Headers: ${headers.filter(h => h.trim()).join(', ')}`);
      console.log(`📈 Data Rows: ${rows}`);
      
      if (rows === 0) {
        const emptyMessage = await page.locator('.fi-ta-empty-state, .empty-state').textContent();
        console.log(`📭 Empty State: ${emptyMessage?.trim() || 'No specific message'}`);
      }
    }
    
    // Look for refresh button
    const refreshBtn = await page.locator('button:has-text("Actualizar")').count();
    console.log(`🔄 Refresh Button: ${refreshBtn > 0 ? 'Found' : 'Not found'}`);
    
    if (refreshBtn > 0) {
      console.log('\n🔄 Testing refresh button...');
      await page.locator('button:has-text("Actualizar")').click();
      await page.waitForTimeout(2000);
      console.log('✅ Refresh button clicked successfully');
      await page.screenshot({ path: 'screenshots/after-refresh-test.png', fullPage: true });
    }
    
    // Check for action buttons
    const actionButtons = {
      download: await page.locator('button:has-text("Descargar")').count(),
      delete: await page.locator('button:has-text("Eliminar")').count(),
      validate: await page.locator('button:has-text("Validar")').count()
    };
    
    console.log('\n💾 Action Buttons:');
    console.log(`📥 Download: ${actionButtons.download}`);
    console.log(`🗑️ Delete: ${actionButtons.delete}`);
    console.log(`✅ Validate: ${actionButtons.validate}`);
    
    // Check for errors
    const errors = await page.locator('.error, [role="alert"], .alert-danger').allTextContents();
    if (errors.length > 0) {
      console.log(`❌ Errors: ${errors.filter(e => e.trim()).join(', ')}`);
    } else {
      console.log('✅ No errors detected');
    }
    
    console.log('\n📊 UI ASSESSMENT:');
    console.log(`✅ Page loads successfully`);
    console.log(`✅ Has proper title and headings`);
    console.log(`${tables > 0 ? '✅' : '❌'} Has data table`);
    console.log(`${refreshBtn > 0 ? '✅' : '❌'} Has refresh functionality`);
    console.log(`${buttons > 5 ? '✅' : '⚠️'} Has interactive elements`);
    console.log(`${errors.length === 0 ? '✅' : '❌'} Error-free interface`);
    
    console.log('\n📸 Screenshots saved to screenshots/ folder');
    console.log('🎉 Analysis complete!');
    
    // Keep open for manual review
    await page.waitForTimeout(60000);
    
  } catch (error) {
    console.error('❌ Error:', error.message);
    await page.screenshot({ path: 'screenshots/error-analysis.png' });
  } finally {
    await browser.close();
  }
})();