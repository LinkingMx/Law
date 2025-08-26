const { chromium } = require('playwright');

(async () => {
  try {
    const browser = await chromium.launch({ headless: false, slowMo: 500 });
    const page = await browser.newPage();
    
    console.log('🌐 Navigating to admin panel...');
    await page.goto('http://law.test/admin', { waitUntil: 'networkidle' });
    
    // Login to admin
    await page.fill('input[name="email"]', 'armando.reyes@grupocosteno.com');
    await page.fill('input[name="password"]', 'C@sten0.2019+');
    await page.click('button[type="submit"]');
    
    await page.waitForURL('**/admin');
    console.log('✅ Logged into admin panel');
    
    // Take admin light mode screenshot
    await page.screenshot({ path: 'admin-current-light.png', fullPage: true });
    console.log('📸 Admin light mode screenshot saved');
    
    // Try to click on user avatar to access dark mode
    console.log('🔍 Looking for user avatar to toggle theme...');
    
    // Click on avatar in top-right corner
    try {
      await page.click('.fi-avatar img', { timeout: 5000 });
      console.log('✅ Clicked avatar');
      await page.waitForTimeout(1000);
      
      // Look for dark mode option
      const darkModeButton = await page.$('text=/dark/i');
      if (darkModeButton) {
        await darkModeButton.click();
        console.log('✅ Toggled to dark mode');
        await page.waitForTimeout(2000);
        
        await page.screenshot({ path: 'admin-current-dark.png', fullPage: true });
        console.log('📸 Admin dark mode screenshot saved');
      }
    } catch (e) {
      console.log('❌ Could not toggle theme:', e.message);
    }
    
    await page.waitForTimeout(5000);
    await browser.close();
    
  } catch (error) {
    console.error('❌ Error:', error.message);
  }
})();