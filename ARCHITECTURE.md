# ARQUITECTURA DEL PLUGIN - KzmCITO IA SEO

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    KZMCITO-IA-SEO.PHP (Main Plugin File)                │
│                                                                           │
│  • Singleton Pattern                                                     │
│  • Autoloader                                                            │
│  • Hooks Registration                                                    │
│  • Database Table Creation                                               │
│  • Meta Box Registration                                                 │
│  • AJAX Handlers                                                         │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                    ┌───────────────┴───────────────┐
                    │                               │
                    ▼                               ▼
        ┌─────────────────────┐         ┌─────────────────────┐
        │   ADMIN UI          │         │   FRONTEND FILTERS  │
        │  (Admin Panel)      │         │ (Language Detector) │
        └─────────────────────┘         └─────────────────────┘
                    │                              │
        ┌───────────┴───────────┐         ┌────────┴────────┐
        │                       │         ▼                 ▼
        ▼                       ▼    ┌──────────┐      ┌──────────┐
┌──────────────┐      ┌──────────────────┐  │ Content  │      │  Title   │
│ Settings     │      │ Prompt Editor    │  │ Filter   │      │  Filter  │
│ Page         │      │ Language Manager │  └──────────┘      └──────────┘
└──────────────┘      └──────────────────┘         │
                                │                  ▼
                                ▼           ┌─────────────────────┐
                    ┌─────────────────────┐ │   CACHE MANAGER     │
                    │   CORE ORCHESTRATOR │ │ (rocket_clean_post) │
                    │   (class-core.php)  │ └─────────────────────┘
                    └─────────────────────┘
                                │
                ┌───────────────┼───────────────┐
                │               │               │
                ▼               ▼               ▼
        ┌──────────┐    ┌──────────┐    ┌──────────┐
        │ PHASE 1  │    │ PHASE 2  │    │ PHASE 3  │
        │ Analysis │───▶│Transform │───▶│ SEO Inj. │
        └──────────┘    └──────────┘    └──────────┘
                                                │
                                                ▼
                                        ┌──────────┐
                                        │ PHASE 4  │
                                        │Translate │
                                        └──────────┘
```

════════════════════════════════════════════════════════════════════════════

## 🏗️ COMPONENTES PRINCIPALES

### 1. CORE ORCHESTRATOR (class-core.php)
El motor principal que coordina el pipeline de 4 fases. Maneja la lógica de negocio y la transición entre estados.

### 2. PROMPT MANAGER (class-prompt-manager.php)
Gestiona la carga jerárquica de prompts (Global + Categoría). Incluye sistema de backups y validación de integridad.

### 3. CONTENT PROCESSOR (class-content-processor.php)
Realiza la transformación pesada del contenido: limpieza de código Office, expansión vía IA, generación de encabezados (H2-H4), inserción de TOC y FAQ.

### 4. SEO INJECTOR (class-seo-injector.php)
Integración nativa con RankMath. Genera metadatos optimizados (Focus Keyword, Meta Description, Title) basándose en el análisis del contenido para alcanzar score 100/100.

### 5. TRANSLATION MANAGER (class-translation-manager.php)
Gestiona la localización a 7+ idiomas. Utiliza modelos de IA para traducciones semánticas y mantiene una caché persistente en la base de datos.

### 6. CACHE MANAGER (class-cache-manager.php)
Integración completa con WP-Rocket. Limpia automáticamente la caché del post, purga Cloudflare y pre-carga las URLs traducidas después de cada procesamiento.

### 7. LANGUAGE DETECTOR (class-language-detector.php) 🆕
Detecta inteligentemente el idioma del navegador del usuario y sirve la versión traducida desde la caché sin cambiar la URL. Es transparente para Google (los bots siempre ven español).

════════════════════════════════════════════════════════════════════════════

## 🔄 PIPELINE DE 4 FASES

### FASE 1: ANÁLISIS
- Detección de categoría (michoacan, salud, etc.)
- Extracción de keywords y entidades.
- Análisis de estructura y conteo de palabras.

### FASE 2: TRANSFORMACIÓN
- Limpieza profunda de HTML (Office tags, inline styles).
- Expansión de contenido (850-1200 palabras).
- Inserción de TOC (Tabla de Contenidos) y FAQ Schema JSON-LD.

### FASE 3: INYECCIÓN SEO
- Actualización de campos RankMath.
- Optimización de slugs y configuración de robots.
- Garantía de score 100/100.

### FASE 4: LOCALIZACIÓN
- Generación de versiones multilingües.
- Almacenamiento en caché persistente.
- Pre-carga de caché en WP-Rocket.

════════════════════════════════════════════════════════════════════════════

## 🛡️ CAPAS DE SEGURIDAD
1. **Sanitización Estricta**: `wp_kses_post()` para contenido y `sanitize_text_field()` para metadatos.
2. **Validación de Permisos**: Chequeos de `current_user_can('edit_posts')` en todas las acciones.
3. **Protección Nonce**: Verificación de nonces en todos los formularios y llamadas AJAX.
4. **Consultas Preparadas**: Uso de `$wpdb->prepare()` para toda interacción con la base de datos.
