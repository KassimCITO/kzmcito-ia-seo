# Engine Editorial "El Día de Michoacán" - Plugin WordPress

## Descripción

Plugin de WordPress desarrollado según la **Antigravity Master Specification** que implementa un motor editorial agentico con IA para transformación automática de contenidos, optimización SEO y generación de traducciones multilingües.

## Versión

**2.0.0** - Implementación completa del pipeline de 4 fases

## Características Principales

### 🤖 Motor Agentico de IA
- Soporte para múltiples modelos: Claude (Sonnet/Opus), Gemini Pro, GPT-4/3.5
- Procesamiento automático o manual de contenidos
- Sistema de prompts jerárquico (Global + Categoría)

### 📝 Pipeline de 4 Fases

#### Fase 1: Análisis
- Identificación de keywords y entidades
- Detección automática de categoría
- Análisis de estructura del contenido

#### Fase 2: Transformación
- Limpieza de basura de Office (tags `mso-`, estilos inline)
- Expansión de contenido (850-1200 palabras)
- Generación automática de encabezados H2-H4
- Inserción de Tabla de Contenidos (TOC)
- Generación de FAQ con Schema JSON-LD

#### Fase 3: Inyección SEO
- Integración completa con RankMath
- Generación de Focus Keyword, Meta Description, SEO Title
- Configuración avanzada para score 100/100
- Optimización de slugs

#### Fase 4: Localización
- Traducción semántica a 7 idiomas por defecto
- Sistema de caché de traducciones
- Gestión CRUD de idiomas personalizados

### 🌍 Idiomas por Defecto
- Inglés (en)
- Portugués (pt)
- Francés (fr)
- Alemán (de)
- Ruso (ru)
- Hindi (hi)
- Chino Simplificado (zh)

### 📊 Categorías Soportadas
1. **Michoacán** - Noticias locales
2. **Educación** - Contenido educativo
3. **Entretenimiento** - Cultura y espectáculos
4. **Justicia** - Temas legales
5. **Salud** - Salud y bienestar
6. **Seguridad** - Seguridad pública

## Estructura del Plugin

```
kzmcito-ia-seo/
├── kzmcito-ia-seo.php          # Archivo principal del plugin
├── includes/                    # Clases principales
│   ├── class-core.php          # Orquestador del pipeline
│   ├── class-prompt-manager.php # Gestor de prompts
│   ├── class-content-processor.php # Procesador de contenido
│   ├── class-seo-injector.php  # Inyector de SEO
│   ├── class-translation-manager.php # Gestor de traducciones
│   ├── class-api-client.php    # Cliente de APIs de IA
│   ├── class-meta-fields.php   # Campos meta personalizados
│   └── class-admin-ui.php      # Interfaz de administración
├── admin/                       # Assets de administración
│   ├── assets/
│   │   ├── css/
│   │   │   └── admin.css       # Estilos del admin
│   │   └── js/
│   │       └── admin.js        # JavaScript del admin
│   └── views/                   # Vistas del admin
├── prompts/                     # Archivos de prompts
│   ├── system-prompt-global.md
│   ├── 01-michoacan.md
│   ├── 02-educacion.md
│   ├── 03-entretenimiento.md
│   ├── 04-justicia.md
│   ├── 05-salud.md
│   ├── 06-seguridad.md
│   ├── antigravity-master-spec.md
│   └── backups/                 # Backups automáticos
└── README.md                    # Este archivo
```

## Instalación

1. Subir la carpeta `kzmcito-ia-seo` a `/wp-content/plugins/`
2. Activar el plugin desde el panel de WordPress
3. Ir a **Engine IA > Configuración**
4. Configurar API Keys para los modelos de IA
5. Ajustar configuración según necesidades

## Configuración

### API Keys Requeridas

Configurar al menos una de las siguientes:

- **Claude (Anthropic)**: Obtener en https://console.anthropic.com/
- **Gemini (Google)**: Obtener en https://makersuite.google.com/
- **OpenAI (GPT)**: Obtener en https://platform.openai.com/

### Opciones de Configuración

- **Modelo de IA**: Seleccionar modelo preferido
- **Procesamiento Automático**: Activar/desactivar procesamiento al guardar
- **Palabras Mínimas/Máximas**: Rango para expansión de contenido
- **Habilitar TOC**: Insertar tabla de contenidos automáticamente
- **Habilitar FAQ**: Generar FAQ con Schema JSON-LD

## Uso

### Procesamiento Manual

1. Editar un post o página
2. En el panel lateral, buscar **Engine Editorial IA**
3. Hacer clic en **Procesar Ahora**
4. El sistema ejecutará las 4 fases automáticamente

### Procesamiento Automático

Si está habilitado en la configuración, el contenido se procesará automáticamente al guardar.

### Gestión de Prompts

1. Ir a **Engine IA > Prompts**
2. Seleccionar categoría a editar
3. Modificar el prompt según necesidades
4. Guardar cambios (se crea backup automático)

### Gestión de Idiomas

1. Ir a **Engine IA > Idiomas**
2. Ver idiomas activos
3. Agregar nuevos idiomas personalizados
4. Activar/desactivar idiomas según necesidad

## Campos Meta Personalizados

El plugin registra los siguientes campos meta:

### Procesamiento
- `_kzmcito_last_processed`: Fecha del último procesamiento
- `_kzmcito_category_detected`: Categoría detectada
- `_kzmcito_pending_seo_injection`: Inyección SEO pendiente
- `_kzmcito_processing_log`: Log de eventos

### Análisis
- `_kzmcito_analysis_data`: Datos del análisis
- `_kzmcito_keywords`: Keywords extraídas
- `_kzmcito_entities`: Entidades extraídas

### SEO
- `_kzmcito_seo_score`: Score SEO calculado
- `_kzmcito_rankmath_injected`: Estado de inyección RankMath
- `_kzmcito_has_toc`: TOC generado
- `_kzmcito_has_faq`: FAQ generado

### Traducción
- `kzmcito_translations_cache`: Caché de traducciones
- `_kzmcito_available_languages`: Idiomas disponibles
- `_kzmcito_last_translated`: Fecha de última traducción

## Principios No Negociables

Según la Antigravity Master Specification:

1. **Integridad**: No alterar scripts, embeds o shortcodes originales
2. **Determinismo**: Output profesional y publicable sin edición manual
3. **Seguridad**: Uso estricto de `wp_kses_post`, `sanitize_text_field` y validación de permisos

## Modo Fallback

Si no se detecta una categoría predefinida, el sistema aplica únicamente el **System Prompt Global** y registra el evento para análisis.

## Requisitos

- WordPress 6.0 o superior
- PHP 8.0 o superior
- RankMath SEO (recomendado para funcionalidad completa)
- Conexión a internet para APIs de IA

## Soporte y Desarrollo

- **Autor**: KassimCITO
- **Versión**: 2.0.0
- **Licencia**: GPL v2 or later

## Changelog

### 2.0.0 (2026-01-25)
- Implementación completa del pipeline de 4 fases
- Soporte para Claude, Gemini y GPT
- Sistema de prompts jerárquico
- Gestión multilingüe con caché
- Integración completa con RankMath
- Interfaz de administración completa
- Sistema de backups de prompts
- Estadísticas y logs de procesamiento

## Notas de Desarrollo

Este plugin fue desarrollado siguiendo la **Antigravity Master Specification** como única fuente de verdad. No se improvisó sobre las reglas establecidas.

El sistema está diseñado para ser:
- **Autónomo**: El usuario final no escribe prompts
- **Transparente**: Logs detallados de cada fase
- **Escalable**: Fácil agregar nuevas categorías e idiomas
- **Seguro**: Validación y sanitización en todos los puntos de entrada
