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
    console.log('🚀 Navegando directamente...');
    await page.goto('http://saashelpdesk.test/admin/login');
    
    // Login rápido
    await page.waitForSelector('input[type="email"]');
    await page.fill('input[type="email"]', 'armando.reyes@grupocosteno.com');
    await page.fill('input[type="password"]', 'C@sten0.2019+');
    await page.click('button[type="submit"]');
    
    await page.waitForURL('**/admin**');
    console.log('✅ Login exitoso');

    await page.goto('http://saashelpdesk.test/admin/email-templates/6/edit');
    await page.waitForLoadState('networkidle');
    
    console.log('🔍 Buscando y haciendo clic en Variables...');
    
    // Buscar el botón específico que contiene "Variables"
    const variablesButton = await page.locator('button:has-text("Variables")').first();
    await variablesButton.click();
    
    // Esperar un poco para que se abra
    await page.waitForTimeout(2000);
    
    console.log('📸 Capturando estado actual...');
    await page.screenshot({ path: 'screenshots/modal-state.png', fullPage: true });
    
    // Intentar diferentes formas de acceder al modal
    const modals = await page.locator('[role="dialog"]').all();
    console.log(`📋 Modales encontrados: ${modals.length}`);
    
    for (let i = 0; i < modals.length; i++) {
      const modal = modals[i];
      const isVisible = await modal.isVisible();
      const classes = await modal.getAttribute('class');
      
      console.log(`Modal ${i + 1}: visible=${isVisible}, classes="${classes}"`);
      
      if (classes && classes.includes('fi-modal')) {
        // Este es probablemente nuestro modal
        console.log('🎯 Modal Filament encontrado');
        
        try {
          // Forzar que se muestre si está oculto
          await modal.evaluate(el => {
            el.style.display = 'block';
            el.style.visibility = 'visible';
            el.style.opacity = '1';
          });
          
          await page.waitForTimeout(1000);
          
          console.log('📸 Capturando modal visible...');
          await page.screenshot({ path: 'screenshots/forced-modal.png', fullPage: true });
          
          // Analizar contenido del modal
          const content = await modal.textContent();
          console.log('📝 Contenido del modal encontrado:', content ? 'SÍ' : 'NO');
          
          if (content && content.includes('Variables')) {
            console.log('✅ Modal de Variables confirmado');
            
            // Crear el plan de mejoras basado en el análisis visual
            console.log('\n🎨 PLAN DE MEJORAS UI PARA MODAL DE VARIABLES:');
            console.log('================================================');
            
            console.log('\n1. 📏 ESPACIADO Y MÁRGENES:');
            console.log('   - Aumentar espacio entre secciones principales (mb-6 → mb-8)');
            console.log('   - Reducir padding interno de tarjetas individuales');
            console.log('   - Estandarizar espaciado vertical entre elementos');
            
            console.log('\n2. 🏗️ ESTRUCTURA Y JERARQUÍA:');
            console.log('   - Simplificar la jerarquía de títulos');
            console.log('   - Agrupar secciones relacionadas visualmente');
            console.log('   - Mejorar separación entre secciones diferentes');
            
            console.log('\n3. 💻 ELEMENTOS DE CÓDIGO:');
            console.log('   - Mejorar contraste y legibilidad de <code>');
            console.log('   - Estandarizar tamaño de elementos code');
            console.log('   - Optimizar wrapping de código largo');
            
            console.log('\n4. 📱 RESPONSIVE Y SCROLL:');
            console.log('   - Implementar scroll interno en el modal');
            console.log('   - Mejorar comportamiento en pantallas pequeñas');
            console.log('   - Optimizar grid responsive');
            
            console.log('\n5. 🎨 CONSISTENCIA VISUAL:');
            console.log('   - Estandarizar radius de bordes');
            console.log('   - Unificar sistema de iconos');
            console.log('   - Mejorar alineación de elementos');
            
            break;
          }
        } catch (e) {
          console.log('❌ Error manipulando modal:', e.message);
        }
      }
    }
    
    console.log('\n📋 IMPLEMENTACIÓN RECOMENDADA:');
    console.log('1. Actualizar clases de espaciado');
    console.log('2. Reorganizar estructura HTML');
    console.log('3. Optimizar elementos repetitivos');
    console.log('4. Mejorar accesibilidad');
    
  } catch (error) {
    console.error('❌ Error:', error.message);
    await page.screenshot({ path: 'screenshots/final-error.png', fullPage: true });
  } finally {
    console.log('🔍 Manteniendo abierto para inspección manual...');
    await page.waitForTimeout(15000);
    await browser.close();
  }
})();