import { chromium } from 'playwright';

(async () => {
  const browser = await chromium.launch({ 
    headless: false,
    slowMo: 1000
  });
  
  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 }
  });
  
  const page = await context.newPage();

  try {
    console.log('🔍 Analizando problemas de UI en el modal...');
    
    // Login
    await page.goto('http://saashelpdesk.test/admin/login');
    await page.waitForSelector('input[type="email"]');
    await page.fill('input[type="email"]', 'armando.reyes@grupocosteno.com');
    await page.fill('input[type="password"]', 'C@sten0.2019+');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/admin**');

    // Navegar al template
    await page.goto('http://saashelpdesk.test/admin/email-templates/6/edit');
    await page.waitForLoadState('networkidle');
    
    // Abrir modal
    const variablesButton = await page.locator('button:has-text("Variables")').first();
    await variablesButton.click();
    await page.waitForTimeout(3000);
    
    console.log('📸 Capturando análisis detallado...');
    await page.screenshot({ path: 'screenshots/ui-analysis-full.png', fullPage: true });
    
    // Analizar elementos específicos
    const modal = await page.locator('[role="dialog"]').first();
    
    console.log('🔍 PROBLEMAS IDENTIFICADOS:');
    console.log('===========================');
    
    // Analizar bordes
    const borderElements = await modal.locator('[class*="border"]').all();
    console.log(`📊 Elementos con border encontrados: ${borderElements.length}`);
    
    // Analizar espaciado de iconos
    const iconElements = await modal.locator('svg').all();
    console.log(`🎯 Iconos encontrados: ${iconElements.length}`);
    
    // Problemas específicos a revisar:
    console.log('\n❌ PROBLEMAS A CORREGIR:');
    console.log('1. Bordes muy blancos/prominentes');
    console.log('2. Espaciado inconsistente entre iconos y texto');
    console.log('3. Elementos con demasiado contraste');
    console.log('4. Posible falta de sutileza en los bordes');
    console.log('5. Spacing entre elementos internos');
    
    console.log('\n🎯 SOLUCIONES A IMPLEMENTAR:');
    console.log('1. Cambiar border por border-gray-200 dark:border-gray-700');
    console.log('2. Ajustar mr-2 a mr-1.5 para iconos pequeños');
    console.log('3. Usar border-opacity para bordes más sutiles');
    console.log('4. Revisar contraste de elementos code');
    console.log('5. Estandarizar espaciado interno');
    
  } catch (error) {
    console.error('❌ Error:', error.message);
  } finally {
    console.log('🔍 Manteniendo abierto para inspección detallada...');
    await page.waitForTimeout(20000);
    await browser.close();
  }
})();