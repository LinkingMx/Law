const { chromium } = require('playwright');

(async () => {
  try {
    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage();
    
    await page.goto('http://law.test/login', { timeout: 10000 });
    await page.screenshot({ path: 'frontend-admin-colors.png' });
    console.log('✅ Frontend with admin colors screenshot saved');
    
    await browser.close();
  } catch (error) {
    console.error('Error:', error.message);
  }
})();