const { chromium } = require('playwright');

(async () => {
  try {
    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage();
    
    // Go to login page and take a quick screenshot
    await page.goto('http://law.test/login', { timeout: 10000 });
    await page.screenshot({ path: 'updated-frontend.png' });
    console.log('✅ Screenshot saved: updated-frontend.png');
    
    await browser.close();
  } catch (error) {
    console.error('Error:', error.message);
  }
})();