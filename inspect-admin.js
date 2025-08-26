const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: false });
  const page = await browser.newPage();
  
  try {
    // Navigate to admin login
    await page.goto('http://law.test/admin');
    await page.waitForLoadState('networkidle');
    
    // Login
    await page.fill('input[name="email"]', 'armando.reyes@grupocosteno.com');
    await page.fill('input[name="password"]', 'C@sten0.2019+');
    await page.click('button[type="submit"]');
    
    // Wait for dashboard
    await page.waitForURL('**/admin');
    await page.waitForLoadState('networkidle');
    
    console.log('✅ Logged in successfully. Taking light mode screenshot...');
    
    // Take light mode screenshot
    await page.screenshot({ path: 'admin-light-mode.png', fullPage: true });
    
    // Click on user avatar to access theme settings
    console.log('Looking for user avatar/menu...');
    await page.waitForSelector('[data-testid="user-menu-trigger"], .fi-dropdown-trigger, .fi-avatar', { timeout: 10000 });
    
    // Try different selectors for the user menu
    const userMenuSelectors = [
      '[data-testid="user-menu-trigger"]',
      '.fi-dropdown-trigger',
      '.fi-avatar',
      '.fi-user-menu-trigger',
      'button[aria-haspopup="menu"]',
      '.filament-dropdown-trigger'
    ];
    
    let userMenuClicked = false;
    for (const selector of userMenuSelectors) {
      try {
        const element = await page.$(selector);
        if (element) {
          console.log(`Found user menu with selector: ${selector}`);
          await element.click();
          userMenuClicked = true;
          break;
        }
      } catch (e) {
        continue;
      }
    }
    
    if (!userMenuClicked) {
      console.log('Could not find user menu. Taking screenshot of current state...');
      await page.screenshot({ path: 'admin-no-menu-found.png', fullPage: true });
      
      // Let's try to find all clickable elements with avatar or user in them
      const allElements = await page.$$eval('*', elements => 
        elements
          .filter(el => {
            const text = el.textContent?.toLowerCase() || '';
            const classes = el.className?.toLowerCase() || '';
            const id = el.id?.toLowerCase() || '';
            return (
              text.includes('user') || 
              text.includes('profile') || 
              text.includes('avatar') ||
              classes.includes('avatar') ||
              classes.includes('user') ||
              classes.includes('dropdown') ||
              id.includes('user') ||
              id.includes('avatar')
            );
          })
          .map(el => ({
            tag: el.tagName,
            text: el.textContent?.slice(0, 50),
            classes: el.className,
            id: el.id
          }))
      );
      
      console.log('Found potential user menu elements:', allElements);
      
      // Try clicking on elements with "dropdown" in class name
      const dropdownElements = await page.$$('.fi-dropdown-trigger, [role="button"], button');
      if (dropdownElements.length > 0) {
        console.log(`Found ${dropdownElements.length} potential dropdown/button elements`);
        for (let i = dropdownElements.length - 1; i >= Math.max(0, dropdownElements.length - 3); i--) {
          try {
            await dropdownElements[i].click();
            await page.waitForTimeout(1000);
            
            // Check if a dropdown appeared
            const dropdown = await page.$('.fi-dropdown-panel, [role="menu"], .dropdown-menu');
            if (dropdown) {
              console.log(`✅ Dropdown opened with element ${i}`);
              break;
            }
          } catch (e) {
            console.log(`Failed to click element ${i}:`, e.message);
          }
        }
      }
    }
    
    // Wait a bit for dropdown to appear
    await page.waitForTimeout(2000);
    
    // Look for theme/appearance options
    const themeSelectors = [
      'text=Theme',
      'text=Appearance',
      'text=Dark',
      'text=Light',
      'text=Tema',
      'text=Apariencia',
      'text=Oscuro',
      'text=Claro'
    ];
    
    let themeOptionFound = false;
    for (const selector of themeSelectors) {
      try {
        const element = await page.$(selector);
        if (element) {
          console.log(`Found theme option with selector: ${selector}`);
          await element.click();
          themeOptionFound = true;
          break;
        }
      } catch (e) {
        continue;
      }
    }
    
    if (themeOptionFound) {
      // Wait for theme to change
      await page.waitForTimeout(2000);
      console.log('✅ Theme toggled. Taking dark mode screenshot...');
      await page.screenshot({ path: 'admin-dark-mode.png', fullPage: true });
    } else {
      console.log('Could not find theme toggle option');
      await page.screenshot({ path: 'admin-dropdown-open.png', fullPage: true });
    }
    
    // Keep browser open for 30 seconds so you can manually inspect
    console.log('Browser will stay open for 30 seconds for manual inspection...');
    await page.waitForTimeout(30000);
    
  } catch (error) {
    console.error('Error:', error);
    await page.screenshot({ path: 'admin-error.png', fullPage: true });
  } finally {
    await browser.close();
  }
})();