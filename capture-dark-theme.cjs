const { chromium } = require('playwright');

(async () => {
  try {
    const browser = await chromium.launch({ headless: false, slowMo: 500 });
    const page = await browser.newPage();
    
    await page.goto('http://law.test/admin', { waitUntil: 'networkidle' });
    console.log('✅ Admin panel loaded');
    
    // Take light mode screenshot
    await page.screenshot({ path: 'filament-light-full.png', fullPage: true });
    console.log('📸 Light mode screenshot saved');
    
    // Click on user avatar in top-right
    console.log('🔍 Looking for user avatar...');
    
    // Try multiple selectors for the user menu
    const userSelectors = [
      '.fi-avatar', 
      'img[alt*="avatar"]',
      'button:has(img)',
      '[data-dropdown-trigger]',
      '.filament-dropdown-trigger'
    ];
    
    let clicked = false;
    for (const selector of userSelectors) {
      try {
        const element = await page.$(selector);
        if (element) {
          await element.click();
          console.log('✅ Clicked user menu:', selector);
          clicked = true;
          break;
        }
      } catch (e) { 
        continue; 
      }
    }
    
    if (!clicked) {
      // Try clicking on the top-right area where avatar should be
      await page.click('body', { position: { x: 1200, y: 32 } });
      console.log('🎯 Clicked top-right area');
    }
    
    await page.waitForTimeout(1500);
    
    // Look for theme/dark mode options
    const themeSelectors = [
      'text=Dark', 
      'text=Light', 
      'text=Tema', 
      'text=Oscuro', 
      'text=Theme', 
      'text=Appearance', 
      '[data-theme]'
    ];
    
    let themeFound = false;
    for (const selector of themeSelectors) {
      try {
        const element = await page.$(selector);
        if (element) {
          await element.click();
          console.log('✅ Clicked theme toggle:', selector);
          themeFound = true;
          break;
        }
      } catch (e) { 
        continue; 
      }
    }
    
    if (themeFound) {
      await page.waitForTimeout(2000);
      await page.screenshot({ path: 'filament-dark-full.png', fullPage: true });
      console.log('📸 Dark mode screenshot saved');
    } else {
      console.log('❌ Theme toggle not found');
      await page.screenshot({ path: 'menu-state.png' });
    }
    
    // Keep browser open briefly for manual inspection
    console.log('🔄 Keeping browser open for 10 seconds...');
    await page.waitForTimeout(10000);
    
    await browser.close();
  } catch (error) {
    console.error('❌ Error:', error.message);
  }
})();