/**
 * Convierte todos los iconos SVG de disciplinas de los componentes Vue
 * a archivos PNG de 72x72 (2x para retina) para uso en PDF con DomPDF.
 *
 * DomPDF no puede renderizar SVGs de manera confiable (ignora width/height,
 * usa viewBox nativo, etc.), pero renderiza PNGs perfectamente.
 *
 * PRERREQUISITOS (solo la primera vez):
 *   cd frontend && npm install @resvg/resvg-js --save-dev
 *
 * USO (desde la raíz del proyecto):
 *   node scripts/convert_svgs_to_png.js
 *
 * NOTA: Solo es necesario correr este script si se agrega o modifica
 * un ícono de disciplina en frontend/src/components/icons/disciplines/
 */
const { Resvg } = require('@resvg/resvg-js');
const fs = require('fs');
const path = require('path');

const vueDir = path.join(__dirname, '..', 'frontend', 'src', 'components', 'icons', 'disciplines');
const pngDir = path.join(__dirname, '..', 'backend', 'resources', 'icons', 'disciplines');

// Crear el directorio de salida si no existe
if (!fs.existsSync(pngDir)) {
    fs.mkdirSync(pngDir, { recursive: true });
}

// Mapeo de nombres de componentes Vue → nombres de archivo para el PDF
const componentMap = {
    'IconBasquetbol': 'basquetbol',
    'IconFrontenis': 'frontenis',
    'IconFutbol': 'futbol',
    'IconPadel': 'padel',
    'IconSquash': 'squash',
    'IconTenis': 'tenis',
    'IconVoleibol': 'voleibol',
    'IconAerobic': 'aerobic',
    'IconJazz': 'jazz',
    'IconZumba': 'zumba',
    'IconDance': 'dance',
    'IconMeditation': 'meditation',
    'IconPilates': 'pilates',
    'IconBarre': 'barre',
    'IconYoga': 'yoga',
    'IconHigieneColumna': 'higiene_columna',
    'IconGym': 'gym',
    'IconMartialArts': 'martial_arts',
    'IconSpinning': 'spinning',
    'IconGymnastics': 'gymnastics',
    'IconSwimming': 'swimming',
    'IconDefault': 'default'
};

const TARGET_SIZE = 72; // 72px = 2x de 36px para nitidez

let successCount = 0;
let errorCount = 0;

fs.readdirSync(vueDir).forEach(file => {
    if (!file.endsWith('.vue')) return;
    
    const compName = file.replace('.vue', '');
    if (!componentMap[compName]) return;
    
    const vuePath = path.join(vueDir, file);
    const content = fs.readFileSync(vuePath, 'utf-8');
    
    // Extraer el SVG del template de Vue
    const match = content.match(/<svg[\s\S]*?<\/svg>/i);
    if (!match) {
        console.warn(`⚠️  No SVG found in ${file}`);
        errorCount++;
        return;
    }
    
    let svg = match[0];
    
    // Reemplazar currentColor con el azul del sistema
    svg = svg.replace(/currentColor/g, '#0d3a77');
    
    // Asegurar que tiene fill si no lo tiene
    if (svg.match(/<svg[^>]*>/)[0].indexOf('fill=') === -1) {
        svg = svg.replace(/<svg\b/i, '<svg fill="#0d3a77"');
    }
    
    try {
        const resvg = new Resvg(svg, {
            fitTo: {
                mode: 'width',
                value: TARGET_SIZE,
            },
            background: 'rgba(0,0,0,0)', // Fondo transparente
        });
        
        const pngData = resvg.render();
        const pngBuffer = pngData.asPng();
        
        const outName = componentMap[compName] + '.png';
        const outPath = path.join(pngDir, outName);
        fs.writeFileSync(outPath, pngBuffer);
        
        console.log(`✅ ${file} → ${outName} (${pngBuffer.length} bytes)`);
        successCount++;
    } catch (err) {
        console.error(`❌ Error converting ${file}: ${err.message}`);
        errorCount++;
    }
});

console.log(`\n🎯 Conversión completada: ${successCount} exitosos, ${errorCount} errores`);
