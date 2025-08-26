import { chromium } from 'playwright';

(async () => {
  const browser = await chromium.launch({ 
    headless: false,
    slowMo: 1000 // Hacer más lento para observar
  });
  
  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 }
  });
  
  const page = await context.newPage();

  try {
    console.log('🔍 Navegando directamente a la página...');
    await page.goto('http://saashelpdesk.test/admin/login', { waitUntil: 'networkidle' });
    
    // Esperar a que aparezcan los campos de login
    console.log('⏳ Esperando campos de login...');
    await page.waitForSelector('input[type="email"], input[name="email"]', { timeout: 10000 });
    
    console.log('🔐 Llenando credenciales...');
    // Buscar el campo de email de múltiples formas
    const emailField = await page.locator('input[type="email"], input[name="email"]').first();
    await emailField.fill('armando.reyes@grupocosteno.com');
    
    // Buscar el campo de password
    const passwordField = await page.locator('input[type="password"], input[name="password"]').first();
    await passwordField.fill('C@sten0.2019+');
    
    console.log('🚀 Enviando formulario...');
    await page.click('button[type="submit"], input[type="submit"]');
    
    // Esperar navegación
    await page.waitForURL('**/admin**', { timeout: 15000 });
    console.log('✅ Login exitoso');

    console.log('🚀 Navegando al email template...');
    await page.goto('http://saashelpdesk.test/admin/email-templates/6/edit', { waitUntil: 'networkidle' });
    
    // Tomar screenshot de la página completa
    await page.screenshot({ path: 'screenshots/email-template-page-full.png', fullPage: true });
    console.log('📸 Screenshot de la página guardado');

    // Buscar el botón de ayuda/variables de forma más amplia
    console.log('🔍 Buscando botón de Variables/Ayuda...');
    
    // Esperar un poco para que cargue todo
    await page.waitForTimeout(3000);
    
    // Buscar botones que contengan texto relevante
    const buttons = await page.locator('button').all();
    console.log(`📊 Encontré ${buttons.length} botones en total`);
    
    let targetButton = null;
    
    for (let i = 0; i < buttons.length; i++) {
      const button = buttons[i];
      try {
        const text = await button.textContent();
        const ariaLabel = await button.getAttribute('aria-label');
        const title = await button.getAttribute('title');
        const isVisible = await button.isVisible();
        
        if (isVisible) {
          const buttonInfo = `Botón ${i + 1}: "${text}" | aria: "${ariaLabel}" | title: "${title}"`;
          console.log(buttonInfo);
          
          // Buscar palabras clave
          const searchText = (text + ' ' + ariaLabel + ' ' + title).toLowerCase();
          if (searchText.includes('variable') || 
              searchText.includes('ayuda') || 
              searchText.includes('help') ||
              searchText.includes('info') ||
              searchText.includes('?')) {
            targetButton = button;
            console.log(`✅ Encontré botón objetivo: "${text}"`);
            break;
          }
        }
      } catch (e) {
        // Continuar con el siguiente
      }
    }
    
    if (!targetButton) {
      console.log('🔍 No encontré botón específico, buscando iconos y elementos...');
      
      // Buscar elementos con iconos que puedan ser de ayuda
      const iconElements = await page.locator('svg, [class*="icon"], [class*="heroicon"]').all();
      console.log(`📊 Encontré ${iconElements.length} iconos`);
      
      // Buscar elementos clickeables cerca de campos de texto
      const textareas = await page.locator('textarea').all();
      if (textareas.length > 0) {
        console.log(`📝 Encontré ${textareas.length} textareas`);
        
        // Buscar botones cerca del primer textarea
        const firstTextarea = textareas[0];
        const nearbyButtons = await page.locator('button').near(firstTextarea, { threshold: 200 }).all();
        console.log(`🎯 Encontré ${nearbyButtons.length} botones cerca del textarea`);
        
        for (let i = 0; i < nearbyButtons.length; i++) {
          const button = nearbyButtons[i];
          const text = await button.textContent();
          console.log(`Botón cercano ${i + 1}: "${text}"`);
        }
      }
      
      console.log('📸 Tomando screenshot para análisis manual...');
      await page.screenshot({ path: 'screenshots/page-for-manual-analysis.png', fullPage: true });
      
      // Intentar buscar modales que ya estén en el DOM
      console.log('🔍 Buscando modales existentes...');
      const modals = await page.locator('[role="dialog"], .modal, [data-modal], [class*="modal"]').all();
      
      if (modals.length > 0) {
        console.log(`📋 Encontré ${modals.length} modales en el DOM`);
        
        for (let i = 0; i < modals.length; i++) {
          const modal = modals[i];
          const isVisible = await modal.isVisible();
          const classes = await modal.getAttribute('class');
          console.log(`Modal ${i + 1}: visible=${isVisible}, classes="${classes}"`);
        }
      }
      
      return;
    }

    console.log('🖱️ Haciendo clic en el botón...');
    await targetButton.click();
    
    // Esperar a que aparezca el modal
    console.log('⏳ Esperando modal...');
    await page.waitForSelector('[role="dialog"], .modal, [data-modal]', { timeout: 10000 });
    
    // Tomar screenshot del modal
    console.log('📸 Tomando screenshots del modal...');
    await page.screenshot({ path: 'screenshots/variables-modal-complete.png', fullPage: true });
    
    // Analizar el modal
    const modal = await page.locator('[role="dialog"], .modal, [data-modal]').first();
    const modalBox = await modal.boundingBox();
    
    if (modalBox) {
      await page.screenshot({ 
        path: 'screenshots/variables-modal-focused.png',
        clip: modalBox
      });
    }
    
    console.log('🔍 Analizando estructura del modal...');
    
    // Obtener todas las secciones del modal
    const sections = await modal.locator('> div, [class*="space-y"], [class*="p-"], [class*="border"]').all();
    console.log(`📊 Secciones encontradas: ${sections.length}`);
    
    const analysis = {
      totalSections: sections.length,
      modalSize: modalBox,
      spacing: [],
      headers: [],
      codeElements: []
    };
    
    // Analizar headers
    const headers = await modal.locator('h1, h2, h3, h4').all();
    for (let i = 0; i < headers.length; i++) {
      const header = headers[i];
      const text = await header.textContent();
      const classes = await header.getAttribute('class');
      analysis.headers.push({ text: text?.trim(), classes });
    }
    
    // Analizar elementos de código
    const codeElements = await modal.locator('code').all();
    analysis.codeElements.push({ count: codeElements.length });
    
    console.log('📊 Análisis del modal:', JSON.stringify(analysis, null, 2));
    
    // Crear plan de mejoras basado en el análisis
    console.log('\n🎨 PLAN DE MEJORAS UI:');
    console.log('====================');
    
    if (analysis.totalSections > 8) {
      console.log('1. ✅ ESPACIADO: Reducir espaciado entre secciones similares');
    }
    
    if (analysis.headers.length > 5) {
      console.log('2. ✅ JERARQUÍA: Simplificar jerarquía de títulos');
    }
    
    console.log('3. ✅ MÁRGENES: Revisar márgenes internos de tarjetas');
    console.log('4. ✅ RESPONSIVE: Mejorar diseño en pantallas pequeñas');
    console.log('5. ✅ CÓDIGO: Optimizar legibilidad de elementos code');
    console.log('6. ✅ SCROLL: Implementar scroll interno si es necesario');
    
    console.log('\n✅ Análisis completado. Revisa los screenshots para detalles visuales.');
    
  } catch (error) {
    console.error('❌ Error:', error.message);
    await page.screenshot({ path: 'screenshots/error-screenshot.png', fullPage: true });
  } finally {
    // Mantener el navegador abierto por 10 segundos para inspección manual
    console.log('🔍 Manteniendo navegador abierto 10 segundos para inspección...');
    await page.waitForTimeout(10000);
    await browser.close();
  }
})();