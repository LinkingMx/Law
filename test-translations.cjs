const { chromium } = require('playwright');

(async () => {
  try {
    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage();
    
    // Navigate to login page
    await page.goto('http://law.test/login', { timeout: 15000 });
    await page.screenshot({ path: 'login-translated.png' });
    console.log('✅ Login page screenshot saved');
    
    // Try to login
    await page.fill('input[name="email"]', 'armando.reyes@grupocosteno.com');
    await page.fill('input[name="password"]', 'C@sten0.2019+');
    await page.click('button[type="submit"]');
    
    // Wait for dashboard
    await page.waitForURL('**/dashboard', { timeout: 10000 });
    await page.screenshot({ path: 'dashboard-translated.png' });
    console.log('✅ Dashboard screenshot saved');
    
    await browser.close();
  } catch (error) {
    console.error('Error:', error.message);
  }
})();