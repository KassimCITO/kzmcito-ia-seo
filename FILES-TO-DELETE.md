# ARCHIVOS A ELIMINAR - Limpieza del Proyecto

## 📁 Archivos que PUEDEN ser eliminados de forma segura

Estos archivos fueron creados durante el desarrollo pero NO son necesarios para el funcionamiento del plugin en producción:

### 1. **Archivos de Desarrollo/Testing** (Si existen)
```
includes/admin-settings.php          ❌ ELIMINAR (duplicado, funcionalidad en class-admin-ui.php)
includes/class-admin.php             ❌ ELIMINAR (duplicado, funcionalidad en class-admin-ui.php)
includes/class-cache.php             ❌ ELIMINAR (stub vacío, funcionalidad en class-cache-manager.php)
includes/class-frontend.php          ❌ ELIMINAR (stub vacío, no se usa)
includes/class-generator.php         ❌ ELIMINAR (stub vacío, funcionalidad en class-api-client.php)
includes/frontend-content.php        ❌ ELIMINAR (stub vacío, no se usa)
includes/meta-box.php                ❌ ELIMINAR (funcionalidad en kzmcito-ia-seo.php)
includes/openai-generator.php        ❌ ELIMINAR (stub vacío, funcionalidad en class-api-client.php)
```

### 2. **Carpeta ZIP** (Si existe)
```
zip/                                 ❌ ELIMINAR COMPLETA (archivos temporales de empaquetado)
```

### 3. **Archivos de Assets No Utilizados** (Si existen)
```
assets/js/lang-switcher.js           ❌ ELIMINAR (no se usa en la versión actual)
```

## ✅ Archivos que DEBEN MANTENERSE

### **Archivos Principales**
```
kzmcito-ia-seo.php                   ✅ MANTENER (archivo principal del plugin)
README.md                             ✅ MANTENER (documentación)
IMPLEMENTATION-SUMMARY.md             ✅ MANTENER (resumen técnico)
ARCHITECTURE.md                       ✅ MANTENER (diagrama de arquitectura)
WP-ROCKET-INTEGRATION.md              ✅ MANTENER (documentación de caché)
```

### **Clases Principales (includes/)**
```
includes/class-core.php               ✅ MANTENER (orquestador del pipeline)
includes/class-prompt-manager.php     ✅ MANTENER (gestor de prompts)
includes/class-content-processor.php  ✅ MANTENER (procesador de contenido)
includes/class-seo-injector.php       ✅ MANTENER (inyector SEO)
includes/class-translation-manager.php ✅ MANTENER (gestor de traducciones)
includes/class-api-client.php         ✅ MANTENER (cliente de APIs)
includes/class-meta-fields.php        ✅ MANTENER (campos meta)
includes/class-cache-manager.php      ✅ MANTENER (gestor de caché WP-Rocket)
includes/class-admin-ui.php           ✅ MANTENER (interfaz de administración)
```

### **Assets de Administración (admin/)**
```
admin/assets/css/admin.css            ✅ MANTENER (estilos del admin)
admin/assets/js/admin.js              ✅ MANTENER (JavaScript del admin)
admin/views/                          ✅ MANTENER (carpeta para vistas futuras)
```

### **Prompts (prompts/)**
```
prompts/system-prompt-global.md       ✅ MANTENER (prompt global)
prompts/01-michoacan.md               ✅ MANTENER (prompt de categoría)
prompts/02-educacion.md               ✅ MANTENER (prompt de categoría)
prompts/03-entretenimiento.md         ✅ MANTENER (prompt de categoría)
prompts/04-justicia.md                ✅ MANTENER (prompt de categoría)
prompts/05-salud.md                   ✅ MANTENER (prompt de categoría)
prompts/06-seguridad.md               ✅ MANTENER (prompt de categoría)
prompts/antigravity-master-spec.md    ✅ MANTENER (especificación de referencia)
prompts/backups/                      ✅ MANTENER (carpeta para backups automáticos)
```

### **Assets Públicos (assets/)**
```
assets/                               ✅ MANTENER (carpeta para assets públicos futuros)
```

## 🔧 Comando para Eliminar Archivos Innecesarios

Ejecuta estos comandos desde la raíz del plugin:

```bash
# Eliminar archivos stub/duplicados
rm -f includes/admin-settings.php
rm -f includes/class-admin.php
rm -f includes/class-cache.php
rm -f includes/class-frontend.php
rm -f includes/class-generator.php
rm -f includes/frontend-content.php
rm -f includes/meta-box.php
rm -f includes/openai-generator.php

# Eliminar carpeta zip si existe
rm -rf zip/

# Eliminar assets no utilizados
rm -f assets/js/lang-switcher.js
```

## 📊 Resumen

### **Antes de Limpiar:**
- Total de archivos en `includes/`: 16 archivos
- Archivos innecesarios: 8 archivos

### **Después de Limpiar:**
- Total de archivos en `includes/`: 9 archivos (solo los necesarios)
- Reducción: ~44% menos archivos

## ⚠️ Notas Importantes

1. **Backups**: Antes de eliminar, asegúrate de tener un backup completo
2. **Verificación**: Después de eliminar, verifica que el plugin funcione correctamente
3. **Git**: Si usas control de versiones, haz commit antes de eliminar

## ✅ Estructura Final Limpia

```
kzmcito-ia-seo/
├── kzmcito-ia-seo.php
├── README.md
├── IMPLEMENTATION-SUMMARY.md
├── ARCHITECTURE.md
├── WP-ROCKET-INTEGRATION.md
├── includes/
│   ├── class-core.php
│   ├── class-prompt-manager.php
│   ├── class-content-processor.php
│   ├── class-seo-injector.php
│   ├── class-translation-manager.php
│   ├── class-api-client.php
│   ├── class-meta-fields.php
│   ├── class-cache-manager.php
│   └── class-admin-ui.php
├── admin/
│   ├── assets/
│   │   ├── css/
│   │   │   └── admin.css
│   │   └── js/
│   │       └── admin.js
│   └── views/
├── prompts/
│   ├── system-prompt-global.md
│   ├── 01-michoacan.md
│   ├── 02-educacion.md
│   ├── 03-entretenimiento.md
│   ├── 04-justicia.md
│   ├── 05-salud.md
│   ├── 06-seguridad.md
│   ├── antigravity-master-spec.md
│   └── backups/
└── assets/
```

---

**Total de archivos a eliminar**: 8-10 archivos  
**Espacio liberado**: Aproximadamente 5-10 KB  
**Beneficio**: Código más limpio y mantenible
