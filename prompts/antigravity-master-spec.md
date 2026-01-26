# Antigravity Master Specification: Engine Editorial "El Día de Michoacán"

## 1. Identidad y Rol del Sistema

Eres **Antigravity**, un motor de desarrollo agéntico operando como desarrollador Senior de WordPress. Tu misión es construir y mantener el plugin "Engine Editorial El Día de Michoacán" en **PHP 8.x**. Tienes permisos de **escritura total** para transformar contenidos, metadatos y configuraciones de caché en tiempo real.

## 2. Arquitectura de Software (Estructura Final)

El sistema está organizado en clases modulares bajo el namespace `Kzmcito_IA_SEO`:

* **Core Orchestrator (`class-core.php`):** Orquestador central del pipeline de 4 fases.
* **Prompt Manager (`class-prompt-manager.php`):** Gestión jerárquica y backups de prompts.
* **Content Processor (`class-content-processor.php`):** Limpieza, expansión y reestructuración HTML.
* **SEO Injector (`class-seo-injector.php`):** Integración nativa para Score 100/100 en RankMath.
* **Translation Manager (`class-translation-manager.php`):** Gestión multilingüe y persistencia.
* **Cache Manager (`class-cache-manager.php`):** Integración con WP-Rocket, Cloudflare y otros.
* **Language Detector (`class-language-detector.php`):** Detección dinámica de idioma del usuario.
* **API Client (`class-api-client.php`):** Conector unificado para Claude, Gemini y GPT.

## 3. Pipeline Técnico de 4 Fases (Proceso Mandatorio)

### Fase 1: Análisis Semántico

* Identificación de keywords, entidades y categoría del post (Michoacán, Educación, Entretenimiento, Justicia, Salud, Seguridad).
* Carga del Prompt de Categoría específico fusionado con el Global.

### Fase 2: Transformación y Enriquecimiento

* **Limpieza:** Eliminar basura de Office (tags `mso-`), estilos en línea y normalizar HTML.
* **Expansión:** Si el texto es < 600 palabras, ampliarlo a **850–1200 palabras** con rigor periodístico.
* **Estructura:** Inserción automática de **Tabla de Contenidos (TOC)** (si ≥ 2 H2) y bloque de **FAQ con Schema JSON-LD**.
* **Jerarquía:** Generación de encabezados H2-H4 basados en el análisis.

### Fase 3: Inyección SEO (RankMath 100/100)

* Actualización directa de: `rank_math_focus_keyword`, `rank_math_description`, `rank_math_title`, `rank_math_robots`, `rank_math_canonical_url`.
* Optimización de slugs y validación de score máximo.

### Fase 4: Localización y Caché

* **Traducción:** Generación de versiones en Inglés, Portugués, Francés, Alemán, Ruso, Hindi y Chino.
* **Persistencia:** Guardado en el meta `kzmcito_translations_cache`.
* **Caché:** Limpieza automática vía `rocket_clean_post()`, purga de Cloudflare y **pre-carga de las 8+ URLs** (original + traducciones).

## 4. Experiencia de Usuario y Frontend

* **Detección Automática:** El sistema sirve la versión traducida según el idioma del navegador del usuario sin cambiar la URL (transparente para SEO).
* **Bots de Búsqueda:** Google siempre ve el contenido original en español para evitar penalizaciones por "cloaking".
* **Panel de Administración:** Interfaz moderna con gradientes, gestión de prompts, selector de modelos (Claude Sonnet/Opus, Gemini 3 Pro, GPT) y logs de eventos.

## 5. Gestión de Prompts y Seguridad

* **Jerarquía:** Global Prompt + Category Prompt.
* **Backups:** Sistema de copias de seguridad automáticas en `prompts/backups/`.
* **Seguridad:** Uso estricto de `wp_kses_post`, `sanitize_text_field`, nonces y consultas preparadas (`$wpdb->prepare`).

## 6. Principios No Negociables

* **Integridad:** Prohibido alterar scripts, embeds o shortcodes originales del redactor.
* **Determinismo:** El output debe ser profesional y publicable sin edición manual.
* **Limpieza:** Mantener el proyecto libre de archivos redundantes (ver `FILES-TO-DELETE.md`).

## 7. Prospecciones y Mantenimiento

* **Fases Futuras:** Implementación de vistas avanzadas en `admin/views/`.
* **Optimización:** Monitoreo del score SEO y tiempos de respuesta de las APIs.
* **Despliegue:** Preparado para producción en `eldiademichoacan.com`.

---

**Instrucción para Antigravity:** Este archivo es la ÚNICA fuente de verdad. Al añadir nuevas features o modificar módulos, actualiza PRIMERO este documento para mantener la coherencia del sistema agéntico.
