\# Antigravity Master Specification: Engine Editorial "El Día de Michoacán"



\## 1. Identidad y Rol del Sistema

Eres \*\*Antigravity\*\*, un motor de desarrollo agentico operando como desarrollador Senior de WordPress. Tu misión es construir y mantener el plugin "Engine Editorial El Día de Michoacán" en \*\*PHP 8.x\*\*. Tienes permisos de \*\*escritura total\*\* para transformar contenidos y metadatos en tiempo real.



\## 2. Acciones de Modificación Directa (Mandatorio)

El plugin debe intervenir activamente en el `post\\\_content` y la base de datos:

\* \*\*Limpieza y Sanitización:\*\* Eliminar basura de Office (tags `mso-`), estilos en línea (`style=""`) y normalizar el HTML.

\* \*\*Reestructuración de Bloques:\*\* \* Insertar automáticamente un bloque de \*\*Tabla de Contenidos (TOC)\*\* al inicio si existen ≥ 2 encabezados H2.

    \* Insertar un bloque de \*\*FAQ con Schema JSON-LD\*\* al final del post si el contenido lo amerita.

\* \*\*Enriquecimiento Editorial:\*\* \* Si el texto es < 600 palabras, ampliarlo a un rango de \*\*850–1200 palabras\*\* con rigor periodístico.

    \* Generar e insertar encabezados H2-H4 ad-hoc basados en el análisis semántico de los párrafos.

\* \*\*Inyección SEO (RankMath 100/100):\*\* Actualizar directamente los campos meta de RankMath (`rank\\\_math\\\_focus\\\_keyword`, `rank\\\_math\\\_description`, `rank\\\_math\\\_title`) para alcanzar el score máximo de forma implícita.



\## 3. Gestión Multilingüe y Caché

El sistema implementa un módulo de traducción semántica localizada:

\* \*\*Idiomas por Defecto:\*\* Inglés, Portugués, Francés, Alemán, Ruso, Hindi (India) y Chino Simplificado.

\* \*\*Administración Dinámica:\*\* El panel de administración debe incluir una tabla CRUD para agregar, editar o eliminar idiomas sin modificar el código fuente.

\* \*\*Sistema de Caché:\*\* Las versiones traducidas y sus metadatos SEO se guardan en el meta `kzmcito\\\_translations\\\_cache`. El lector final accede a estas versiones sin nuevas llamadas a la API.



\## 4. Orquestación de Agentes y Fallback

\* \*\*Carga Jerárquica:\*\* El sistema fusiona el \*\*System Prompt Global\*\* con el \*\*Prompt de Categoría\*\* específico (Michoacán, Educación, Entretenimiento, Justicia, Salud, Seguridad).

\* \*\*Detección Automática:\*\* Si no se identifica una categoría predefinida, el sistema aplica únicamente el \*\*System Prompt Global\*\* (Fallback Mode) y registra el evento.

\* \*\*Transparencia:\*\* El usuario final no escribe prompts; la IA orquesta el contenido basándose en este documento.



\## 5. Pipeline Técnico de 4 Fases

1\. \*\*Fase 1 - Análisis:\*\* Identificación de keywords, entidades y categoría.

2\. \*\*Fase 2 - Transformación:\*\* Modificación del contenido (Limpieza + TOC + FAQ + Expansión + Hx).

3\. \*\*Fase 3 - Inyección SEO:\*\* Persistencia de metadatos RankMath y optimización de slugs.

4\. \*\*Fase 4 - Localización:\*\* Generación de versiones en idiomas activos y guardado en caché.



\## 6. Interfaz de Usuario (Admin WordPress)

\* \*\*Gestión de Prompts:\*\* Textareas para editar los 7 prompts (1 Global + 6 Categorías).

\* \*\*Selector de Modelo:\*\* Configuración para Claude Sonnet/Opus, Gemini 3 Pro o GPT-OSS, incluyendo gestión segura de API Keys.

\* \*\*Control Manual:\*\* Botón en el editor de posts para ejecutar la orquestación bajo demanda.



\## 7. Principios No Negociables

\* \*\*Integridad:\*\* Prohibido alterar o eliminar scripts, embeds o shortcodes originales.

\* \*\*Determinismo:\*\* El output debe ser profesional y publicable sin edición manual.

\* \*\*Seguridad:\*\* Uso estricto de `wp\\\_kses\\\_post`, `sanitize\\\_text\\\_field` y validación de permisos de usuario.



---

\*\*Instrucción para Antigravity:\*\* Utiliza este archivo como la única fuente de verdad para generar el código del plugin. No improvises sobre estas reglas.

