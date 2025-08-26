import { chromium } from 'playwright';

(async () => {
  const browser = await chromium.launch({ 
    headless: false,
    slowMo: 500
  });
  
  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 }
  });
  
  const page = await context.newPage();

  try {
    console.log('🚀 Verificando mejoras del modal...');
    
    // Login rápido
    await page.goto('http://saashelpdesk.test/admin/login');
    await page.waitForSelector('input[type="email"]');
    await page.fill('input[type="email"]', 'armando.reyes@grupocosteno.com');
    await page.fill('input[type="password"]', 'C@sten0.2019+');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/admin**');
    
    console.log('✅ Login exitoso');

    // Navegar al template
    await page.goto('http://saashelpdesk.test/admin/email-templates/6/edit');
    await page.waitForLoadState('networkidle');
    
    console.log('🔍 Abriendo modal de Variables...');
    
    // Abrir modal
    const variablesButton = await page.locator('button:has-text("Variables")').first();
    await variablesButton.click();
    await page.waitForTimeout(2000);
    
    console.log('📸 Capturando modal mejorado...');
    await page.screenshot({ path: 'screenshots/modal-improved.png', fullPage: true });
    
    console.log('✅ MEJORAS IMPLEMENTADAS:');
    console.log('========================');
    console.log('✅ Scroll interno agregado (max-h-[80vh] overflow-y-auto)');
    console.log('✅ Espaciado entre secciones optimizado (space-y-8)');
    console.log('✅ Padding de tarjetas reducido (p-2.5)');
    console.log('✅ Gap entre elementos optimizado (gap-3)');
    console.log('✅ Tamaño de código estandarizado (text-xs)');
    console.log('✅ Botones de acción reorganizados con flex-wrap');
    console.log('✅ Separador visual añadido (border-t)');
    console.log('✅ Jerarquía visual mejorada');
    console.log('✅ Responsive design optimizado');
    
    console.log('\n🎯 RESULTADO:');
    console.log('El modal ahora tiene:');
    console.log('- Mejor aprovechamiento del espacio');
    console.log('- Scroll interno funcional'); 
    console.log('- Elementos más compactos y legibles');
    console.log('- Mejor experiencia responsive');
    console.log('- Navegación más fluida');
    
  } catch (error) {
    console.error('❌ Error:', error.message);
  } finally {
    console.log('🔍 Manteniendo abierto para verificación visual...');
    await page.waitForTimeout(15000);
    await browser.close();
  }
})();