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
    await page.waitForTimeout(2000);
    
    console.log('📝 Filling login credentials...');
    await page.fill('#data\\.email', 'armando.reyes@grupocosteno.com');
    await page.fill('#data\\.password', 'C@sten0.2019+');
    
    // Take screenshot before submitting
    await page.screenshot({ path: 'screenshots/before-submit.png', fullPage: true });
    
    console.log('🚀 Submitting login form...');
    
    // Listen for response
    page.on('response', response => {
      console.log(`📡 Response: ${response.status()} ${response.url()}`);
    });
    
    // Listen for navigation
    page.on('framenavigated', frame => {
      console.log(`🧭 Navigated to: ${frame.url()}`);
    });
    
    // Click submit and wait for navigation
    await Promise.all([
      page.waitForNavigation({ timeout: 10000 }),
      page.click('button[type="submit"]')
    ]);
    
    console.log(`✅ After submit, current URL: ${page.url()}`);
    
    // Take screenshot after submit
    await page.screenshot({ path: 'screenshots/after-submit.png', fullPage: true });
    
    // Check for validation errors
    const validationErrors = await page.locator('.error, .invalid-feedback, [role="alert"]').allTextContents();
    if (validationErrors.length > 0) {
      console.log(`⚠️ Validation errors: ${validationErrors.join(', ')}`);
    }
    
    // If we're still on login page, check why
    if (page.url().includes('/admin/login')) {
      console.log('❌ Still on login page. Checking for errors...');
      
      // Look for any error messages
      const allTexts = await page.locator('*').allTextContents();
      const errorTexts = allTexts.filter(text => 
        text.includes('error') || 
        text.includes('invalid') || 
        text.includes('incorrect') ||
        text.includes('failed')
      );
      
      if (errorTexts.length > 0) {
        console.log(`🔍 Possible error texts: ${errorTexts.join(', ')}`);
      }
      
      // Check if form was actually submitted
      const currentUrl = page.url();
      console.log(`🔍 Current URL details: ${currentUrl}`);
    } else {
      console.log('✅ Successfully redirected after login!');
      
      // Now try to go to backup history
      console.log('📂 Navigating to backup history...');
      await page.goto('http://saashelpdesk.test/admin/backup-history');
      await page.waitForLoadState('networkidle');
      
      const finalUrl = page.url();
      console.log(`📍 Final URL: ${finalUrl}`);
      
      await page.screenshot({ path: 'screenshots/backup-history-success.png', fullPage: true });
      console.log('📸 Backup history screenshot saved');
    }
    
    // Keep browser open for inspection
    console.log('🔍 Keeping browser open for 60 seconds...');
    await page.waitForTimeout(60000);
    
  } catch (error) {
    console.error('❌ Error during login debug:', error);
    await page.screenshot({ path: 'screenshots/login-error.png', fullPage: true });
  } finally {
    await browser.close();
  }
})();