import { chromium } from 'playwright';

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();

  try {
    console.log('🔐 Navigating to login page...');
    await page.goto('http://saashelpdesk.test/admin/login');
    
    // Take screenshot of login page
    await page.screenshot({ path: 'screenshots/login-check.png', fullPage: true });
    
    // Get page HTML to debug
    const html = await page.content();
    console.log('Page loaded successfully');
    
    // Look for input fields
    const inputs = await page.locator('input').all();
    console.log(`Found ${inputs.length} input fields`);
    
    for (let i = 0; i < inputs.length; i++) {
      const input = inputs[i];
      const type = await input.getAttribute('type');
      const name = await input.getAttribute('name');
      const id = await input.getAttribute('id');
      const placeholder = await input.getAttribute('placeholder');
      console.log(`Input ${i + 1}: type="${type}", name="${name}", id="${id}", placeholder="${placeholder}"`);
    }
    
    // Look for buttons
    const buttons = await page.locator('button').all();
    console.log(`Found ${buttons.length} buttons`);
    
    for (let i = 0; i < buttons.length; i++) {
      const button = buttons[i];
      const text = await button.textContent();
      const type = await button.getAttribute('type');
      console.log(`Button ${i + 1}: text="${text}", type="${type}"`);
    }
    
  } catch (error) {
    console.error('❌ Error:', error);
  } finally {
    await browser.close();
  }
})();