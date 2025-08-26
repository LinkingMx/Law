import { chromium } from 'playwright';

(async () => {
  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext();
  const page = await context.newPage();

  try {
    console.log('🔍 Navegando a la página de login...');
    await page.goto('http://saashelpdesk.test/admin/login');
    
    // Login
    console.log('🔐 Haciendo login...');
    await page.fill('input[name="email"]', 'armando.reyes@grupocosteno.com');
    await page.fill('input[name="password"]', 'C@sten0.2019+');
    await page.click('button[type="submit"]');
    
    // Esperar a que se complete el login
    await page.waitForURL('**/admin');
    console.log('✅ Login exitoso');

    // Navegar al recurso de email templates
    console.log('🚀 Navegando al recurso de email templates...');
    await page.goto('http://saashelpdesk.test/admin/email-templates/6/edit');
    
    // Esperar a que cargue la página
    await page.waitForLoadState('networkidle');
    console.log('✅ Página cargada');

    // Buscar el botón de Variables/Ayuda
    console.log('🔍 Buscando el botón de Variables...');
    
    // Intentar varios selectores posibles para el botón de variables
    const possibleSelectors = [
      'button:has-text("Variables")',
      'button:has-text("Ayuda")',
      '[data-tippy-content*="Variables"]',
      'button[title*="Variables"]',
      'button[aria-label*="Variables"]',
      '.fi-btn:has-text("Variables")',
      '.fi-btn:has-text("Ayuda")',
      'button:has(svg) >> text=/Variables|Ayuda|Help/i'
    ];

    let variablesButton = null;
    for (const selector of possibleSelectors) {
      try {
        variablesButton = await page.locator(selector).first();
        if (await variablesButton.isVisible()) {
          console.log(`✅ Encontré el botón con selector: ${selector}`);
          break;
        }
      } catch (e) {
        // Continuar con el siguiente selector
      }
    }

    if (!variablesButton || !(await variablesButton.isVisible())) {
      console.log('🔍 No encontré el botón específico, buscando todos los botones visibles...');
      const allButtons = await page.locator('button').all();
      console.log(`📊 Encontré ${allButtons.length} botones en la página`);
      
      for (let i = 0; i < allButtons.length; i++) {
        const button = allButtons[i];
        const text = await button.textContent();
        const ariaLabel = await button.getAttribute('aria-label');
        const title = await button.getAttribute('title');
        
        if (text || ariaLabel || title) {
          console.log(`Botón ${i + 1}: "${text}" | aria-label: "${ariaLabel}" | title: "${title}"`);
          
          if ((text && text.toLowerCase().includes('variable')) || 
              (text && text.toLowerCase().includes('ayuda')) ||
              (ariaLabel && ariaLabel.toLowerCase().includes('variable')) ||
              (title && title.toLowerCase().includes('variable'))) {
            variablesButton = button;
            console.log(`✅ Encontré el botón de variables: "${text}"`);
            break;
          }
        }
      }
    }

    if (!variablesButton || !(await variablesButton.isVisible())) {
      console.log('❌ No pude encontrar el botón de Variables. Tomando screenshot para análisis...');
      await page.screenshot({ path: 'screenshots/email-template-page.png', fullPage: true });
      console.log('📸 Screenshot guardado en screenshots/email-template-page.png');
      
      // Buscar cualquier modal o elemento relacionado con variables
      console.log('🔍 Buscando elementos relacionados con variables en el DOM...');
      const variableElements = await page.locator('[class*="variable"], [id*="variable"], [data-*="variable"]').all();
      console.log(`📊 Encontré ${variableElements.length} elementos relacionados con variables`);
      
      return;
    }

    // Hacer clic en el botón de Variables
    console.log('🖱️ Haciendo clic en el botón de Variables...');
    await variablesButton.click();
    
    // Esperar a que aparezca el modal
    console.log('⏳ Esperando a que aparezca el modal...');
    await page.waitForSelector('[role="dialog"], .modal, [data-modal]', { timeout: 10000 });
    
    // Tomar screenshot del modal
    console.log('📸 Tomando screenshot del modal...');
    await page.screenshot({ path: 'screenshots/variables-modal.png', fullPage: true });
    
    // Analizar la estructura del modal
    console.log('🔍 Analizando la estructura del modal...');
    
    const modal = await page.locator('[role="dialog"], .modal, [data-modal]').first();
    
    // Obtener información del modal
    const modalInfo = {
      isVisible: await modal.isVisible(),
      boundingBox: await modal.boundingBox(),
      classes: await modal.getAttribute('class'),
    };
    
    console.log('📊 Información del modal:', modalInfo);
    
    // Analizar elementos internos del modal
    const sections = await modal.locator('div[class*="space-y"], div[class*="p-"], div[class*="border"]').all();
    console.log(`📊 Encontré ${sections.length} secciones principales en el modal`);
    
    // Analizar espaciado
    console.log('📏 Analizando espaciado y márgenes...');
    
    const spacingAnalysis = [];
    for (let i = 0; i < Math.min(sections.length, 10); i++) {
      const section = sections[i];
      const classes = await section.getAttribute('class');
      const boundingBox = await section.boundingBox();
      
      spacingAnalysis.push({
        index: i,
        classes: classes,
        height: boundingBox?.height,
        width: boundingBox?.width
      });
    }
    
    console.log('📊 Análisis de espaciado:', spacingAnalysis);
    
    // Analizar títulos y headers
    const headers = await modal.locator('h1, h2, h3, h4, h5, h6').all();
    console.log(`📊 Encontré ${headers.length} títulos en el modal`);
    
    for (let i = 0; i < headers.length; i++) {
      const header = headers[i];
      const text = await header.textContent();
      const classes = await header.getAttribute('class');
      console.log(`Título ${i + 1}: "${text}" | Classes: ${classes}`);
    }
    
    // Analizar elementos de código
    const codeElements = await modal.locator('code').all();
    console.log(`📊 Encontré ${codeElements.length} elementos de código`);
    
    // Tomar screenshot específico del contenido del modal
    await page.screenshot({ 
      path: 'screenshots/variables-modal-detailed.png',
      clip: modalInfo.boundingBox || undefined
    });
    
    console.log('✅ Análisis completado. Screenshots guardados.');
    console.log('📋 PLAN DE MEJORAS A CREAR:');
    console.log('1. Analizar espaciado entre secciones');
    console.log('2. Revisar márgenes internos de elementos');
    console.log('3. Evaluar jerarquía visual de títulos');
    console.log('4. Optimizar diseño responsive');
    console.log('5. Mejorar legibilidad de elementos de código');
    
  } catch (error) {
    console.error('❌ Error durante el análisis:', error);
    await page.screenshot({ path: 'screenshots/error-state.png', fullPage: true });
  } finally {
    await browser.close();
  }
})();