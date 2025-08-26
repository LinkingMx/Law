import { chromium } from 'playwright';

(async () => {
  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext();
  const page = await context.newPage();

  try {
    console.log('🔐 Logging into admin panel...');
    await page.goto('http://saashelpdesk.test/admin/login');
    await page.waitForLoadState('networkidle');
    
    await page.fill('#data\\.email', 'armando.reyes@grupocosteno.com');
    await page.fill('#data\\.password', 'C@sten0.2019+');
    
    await Promise.all([
      page.waitForNavigation(),
      page.click('button[type="submit"]')
    ]);
    
    console.log('✅ Login successful, navigating to backup history...');
    
    // Navigate to backup history
    await page.goto('http://saashelpdesk.test/admin/backup-history');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(5000); // Extra wait for Livewire components
    
    // Take initial screenshot
    await page.screenshot({ path: 'screenshots/backup-history-initial.png', fullPage: true });
    console.log('📸 Initial backup history screenshot saved');

    console.log('\n📊 COMPREHENSIVE BACKUP HISTORY UI ANALYSIS:');
    console.log('=' .repeat(60));
    
    // 1. Page Basic Info
    const pageTitle = await page.title();
    const currentUrl = page.url();
    console.log(`📄 Page Title: ${pageTitle}`);
    console.log(`🔗 Current URL: ${currentUrl}`);
    
    // 2. Page Headings and Structure
    const headings = await page.locator('h1, h2, h3, .text-xl, .text-2xl, .text-3xl').allTextContents();
    const cleanHeadings = headings.filter(h => h.trim()).map(h => h.trim());
    console.log(`📋 Page Headings: ${cleanHeadings.join(' | ')}`);
    
    // 3. Breadcrumbs
    const breadcrumbs = await page.locator('.fi-breadcrumbs, [aria-label="breadcrumb"]').allTextContents();
    if (breadcrumbs.length > 0) {
      console.log(`🍞 Breadcrumbs: ${breadcrumbs.filter(b => b.trim()).join(' > ')}`);
    }
    
    // 4. Navigation Analysis
    const sidebarItems = await page.locator('.fi-sidebar-nav-item').allTextContents();
    const activeNavItem = await page.locator('.fi-sidebar-nav-item.fi-active').textContent();
    console.log(`🧭 Active Navigation: ${activeNavItem?.trim() || 'None detected'}`);
    console.log(`📚 Sidebar Items Count: ${sidebarItems.length}`);
    
    // 5. Table Analysis
    const tables = await page.locator('table').count();
    console.log(`📊 Tables Found: ${tables}`);
    
    if (tables > 0) {
      // Table headers
      const headers = await page.locator('th').allTextContents();
      const cleanHeaders = headers.filter(h => h.trim()).map(h => h.trim());
      console.log(`📑 Table Headers: ${cleanHeaders.join(' | ')}`);
      
      // Table rows
      const bodyRows = await page.locator('tbody tr').count();
      console.log(`📈 Data Rows: ${bodyRows}`);
      
      if (bodyRows > 0) {
        // Sample data from first row
        const firstRowCells = await page.locator('tbody tr').first().locator('td').allTextContents();
        const cleanCells = firstRowCells.filter(c => c.trim()).map(c => c.trim().substring(0, 30));
        console.log(`📋 First Row Sample: ${cleanCells.join(' | ')}`);
        
        // Look for action buttons in table rows
        const rowActions = await page.locator('tbody tr').first().locator('button, .fi-dropdown-trigger, .fi-ta-actions').count();
        console.log(`⚙️ Actions per Row: ${rowActions}`);
      } else {
        // Check for empty state
        const emptyState = await page.locator('.fi-ta-empty-state, .empty-state').count();
        console.log(`📭 Empty State Displayed: ${emptyState > 0 ? 'Yes' : 'No'}`);
        
        if (emptyState > 0) {
          const emptyText = await page.locator('.fi-ta-empty-state, .empty-state').textContent();
          console.log(`📭 Empty State Message: ${emptyText?.trim()}`);
        }
      }
    }
    
    // 6. Header Actions Analysis
    const headerActions = await page.locator('.fi-header-actions button, .fi-page-header button').count();
    console.log(`🔘 Header Action Buttons: ${headerActions}`);
    
    if (headerActions > 0) {
      const headerButtonTexts = await page.locator('.fi-header-actions button, .fi-page-header button').allTextContents();
      const cleanButtonTexts = headerButtonTexts.filter(b => b.trim()).map(b => b.trim());
      console.log(`🔘 Header Buttons: ${cleanButtonTexts.join(', ')}`);
    }
    
    // 7. Test Refresh Button Functionality
    const refreshButtons = await page.locator('button:has-text("Actualizar"), button:has-text("Refresh")').count();
    console.log(`🔄 Refresh Buttons Found: ${refreshButtons}`);
    
    if (refreshButtons > 0) {
      console.log('\n🔄 TESTING REFRESH FUNCTIONALITY:');
      console.log('-'.repeat(40));
      
      try {
        // Click refresh button
        await page.locator('button:has-text("Actualizar"), button:has-text("Refresh")').first().click();
        console.log('✅ Refresh button clicked successfully');
        
        // Wait for any loading indicators
        await page.waitForTimeout(3000);
        
        // Take screenshot after refresh
        await page.screenshot({ path: 'screenshots/backup-history-after-refresh.png', fullPage: true });
        console.log('📸 After refresh screenshot saved');
        
        // Check for any notifications
        const notifications = await page.locator('.fi-notification, .alert').allTextContents();
        if (notifications.length > 0) {
          console.log(`📢 Notifications: ${notifications.filter(n => n.trim()).join(', ')}`);
        }
        
      } catch (refreshError) {
        console.log(`❌ Refresh Error: ${refreshError.message}`);
        await page.screenshot({ path: 'screenshots/refresh-error.png' });
      }
    }
    
    // 8. Download/Action Button Testing
    const downloadButtons = await page.locator('button:has-text("Descargar"), button:has-text("Download")').count();
    const deleteButtons = await page.locator('button:has-text("Eliminar"), button:has-text("Delete")').count();
    const validateButtons = await page.locator('button:has-text("Validar"), button:has-text("Validate")').count();
    
    console.log(`\n💾 BACKUP ACTION BUTTONS:`);
    console.log('-'.repeat(40));
    console.log(`📥 Download Buttons: ${downloadButtons}`);
    console.log(`🗑️ Delete Buttons: ${deleteButtons}`);
    console.log(`✅ Validate Buttons: ${validateButtons}`);
    
    // 9. Dropdown Actions Analysis
    const dropdownTriggers = await page.locator('.fi-dropdown-trigger, [role="button"][aria-haspopup="true"]').count();
    console.log(`📋 Dropdown Triggers: ${dropdownTriggers}`);
    
    if (dropdownTriggers > 0) {
      console.log('\n📋 TESTING DROPDOWN ACTIONS:');
      console.log('-'.repeat(40));
      
      try {
        // Click first dropdown
        await page.locator('.fi-dropdown-trigger, [role="button"][aria-haspopup="true"]').first().click();
        await page.waitForTimeout(1000);
        
        // Get dropdown options
        const dropdownOptions = await page.locator('.fi-dropdown-list-item, .dropdown-item').allTextContents();
        const cleanOptions = dropdownOptions.filter(o => o.trim()).map(o => o.trim());
        console.log(`📋 Dropdown Options: ${cleanOptions.join(', ')}`);
        
        // Close dropdown by clicking outside
        await page.click('body');
        await page.waitForTimeout(500);
        
      } catch (dropdownError) {
        console.log(`⚠️ Dropdown test error: ${dropdownError.message}`);
      }
    }
    
    // 10. Page Performance and Loading
    console.log(`\n⚡ PERFORMANCE ANALYSIS:`);
    console.log('-'.repeat(40));
    
    // Check for loading indicators
    const loadingIndicators = await page.locator('.loading, .spinner, .fi-loading').count();
    console.log(`⏳ Loading Indicators: ${loadingIndicators}`);
    
    // Check page responsiveness
    const pageWidth = await page.evaluate(() => window.innerWidth);
    const pageHeight = await page.evaluate(() => window.innerHeight);
    console.log(`📐 Viewport: ${pageWidth}x${pageHeight}`);
    
    // 11. Accessibility Check
    console.log(`\n♿ ACCESSIBILITY ANALYSIS:`);
    console.log('-'.repeat(40));
    
    const ariaLabels = await page.locator('[aria-label]').count();
    const headingStructure = await page.locator('h1, h2, h3, h4, h5, h6').count();
    const buttons = await page.locator('button').count();
    const links = await page.locator('a').count();
    
    console.log(`🏷️ Elements with aria-label: ${ariaLabels}`);
    console.log(`📖 Heading Elements: ${headingStructure}`);
    console.log(`🔘 Total Buttons: ${buttons}`);
    console.log(`🔗 Total Links: ${links}`);
    
    // 12. Error Detection
    console.log(`\n❌ ERROR DETECTION:`);
    console.log('-'.repeat(40));
    
    const errorSelectors = [
      '.error', '.alert-danger', '.text-red-500', 
      '.fi-notification[data-type="error"]',
      '[role="alert"]'
    ];
    
    let errorsFound = false;
    for (const selector of errorSelectors) {
      const errorElements = await page.locator(selector).count();
      if (errorElements > 0) {
        const errorTexts = await page.locator(selector).allTextContents();
        console.log(`❌ Errors (${selector}): ${errorTexts.filter(e => e.trim()).join(', ')}`);
        errorsFound = true;
      }
    }
    
    if (!errorsFound) {
      console.log('✅ No errors detected');
    }
    
    // 13. Final Assessment
    console.log(`\n📋 FINAL UI ASSESSMENT:`);
    console.log('='.repeat(60));
    
    const isResponsive = pageWidth > 768;
    const hasData = bodyRows > 0;
    const hasActions = headerActions > 0 || downloadButtons > 0;
    const isAccessible = ariaLabels > 5 && headingStructure > 0;
    
    console.log(`📱 Responsive Design: ${isResponsive ? '✅ Yes' : '❌ No'}`);
    console.log(`📊 Has Data: ${hasData ? '✅ Yes' : '📭 Empty'}`);
    console.log(`⚙️ Has Actions: ${hasActions ? '✅ Yes' : '❌ No'}`);
    console.log(`♿ Accessibility Ready: ${isAccessible ? '✅ Good' : '⚠️ Needs Work'}`);
    console.log(`❌ Error Free: ${!errorsFound ? '✅ Yes' : '⚠️ Has Issues'}`);
    
    // Take final comprehensive screenshot
    await page.screenshot({ path: 'screenshots/backup-history-final.png', fullPage: true });
    console.log('\n📸 Final comprehensive screenshot saved');
    
    console.log('\n🎉 ANALYSIS COMPLETE! Check screenshots folder for visual reference.');
    
    // Keep browser open for manual inspection
    console.log('\n🔍 Browser will stay open for 2 minutes for manual inspection...');
    await page.waitForTimeout(120000);
    
  } catch (error) {
    console.error('❌ Analysis Error:', error);
    await page.screenshot({ path: 'screenshots/analysis-error.png', fullPage: true });
  } finally {
    await browser.close();
  }
})();