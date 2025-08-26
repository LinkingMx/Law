import { test, expect } from '@playwright/test';

test('Debug login page structure', async ({ page }) => {
  await page.goto('http://saashelpdesk.test/admin/login');
  
  // Wait for page to load
  await page.waitForLoadState('networkidle');
  
  // Take a screenshot
  await page.screenshot({ 
    path: 'test-results/screenshots/login-page-debug.png',
    fullPage: true 
  });
  
  // Get page HTML to analyze structure
  const html = await page.content();
  console.log('Page title:', await page.title());
  
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
  
  // Look for form elements
  const forms = await page.locator('form').all();
  console.log(`Found ${forms.length} forms`);
  
  // Look for buttons
  const buttons = await page.locator('button').all();
  console.log(`Found ${buttons.length} buttons`);
  
  for (let i = 0; i < buttons.length; i++) {
    const button = buttons[i];
    const type = await button.getAttribute('type');
    const text = await button.textContent();
    console.log(`Button ${i + 1}: type="${type}", text="${text}"`);
  }
});