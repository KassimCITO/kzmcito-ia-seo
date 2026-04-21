# Manual de Usuario: Engine Editorial IA (Versión 3.1.0)

![Proceso IA Engine](kzmcito_ai_process_workflow_1770336834631.png)

## 1. Introducción

El **Engine Editorial IA** es una solución integral para WordPress diseñada para automatizar la transformación de contenidos, optimización SEO y localización multilingüe. Utilizando modelos de inteligencia artificial de vanguardia, el plugin convierte borradores simples en artículos profesionales listos para ser publicados.

---

## 2. Características Principales

### 🤖 Inteligencia Artificial Multifacética

* **Soporte Multimodelo**: Compatible con Claude 3.5 (Sonnet/Opus), Google Gemini Pro y OpenAI GPT-4.
* **Procesamiento Jerárquico**: Combina prompts globales con instrucciones específicas por categoría (Educación, Salud, Justicia, etc.).
* **Pipeline de 4 Fases**: Análisis, Transformación, Inyección SEO y Localización.
* **Optimización de Títulos**: Sustitución automática de comillas latinas (« ») por comillas tipográficas estándar para SEO.
* **Enriquecimiento Inteligente**: Expansión de acrónimos con nomenclatura completa y enlaces a fuentes oficiales (ej. CFE, IMSS).
* **Linkificación de Contactos**: Conversión automática de números de teléfono y correos electrónicos en texto plano a enlaces funcionales (`tel:` y `mailto:`) para mejorar la experiencia táctil y de escritorio.
* **Mapas Dinámicos**: Inserción automática de un mapa de Google al final del post si se detecta una ubicación geográfica o municipio relevante.
* **Infografías Sugeridas**: Capacidad de generar estructuras de datos para infografías ad-hoc que facilitan la comprensión del post.

### 🎛️ Control Per-Post (Nuevo en v3.1.0)

* **Toggle Activar/Desactivar**: Control visual en el editor para activar o desactivar el procesamiento IA para cada post individual.
* **Compatible con todos los editores**: Gutenberg (Block Editor), Editor Clásico, WPBakery y Elementor.
* **Panel reubicable en Gutenberg**: El panel "KzmCITO IA SEO" aparece en el sidebar del editor y puede reubicarse como cualquier otro panel nativo (Extracto, Discusión, etc.).
* **Procesamiento al guardar**: El pipeline se ejecuta automáticamente al hacer clic en **Guardar**, **Publicar** o **Actualizar**, siempre que el toggle esté activo.

### 🌍 Localización y Traducción

* **Traducción JIT (Just-In-Time)**: Genera traducciones automáticamente al ser solicitadas por el usuario.
* **Rangos Contemplados**: Ajuste inteligente de longitud de contenido entre **650 y 950 palabras**.
* **Detección de Navegador**: Detecta el idioma preferido del visitante y ofrece la traducción si está disponible.
* **Cuadro Flotante Premium**: Interfaz moderna con opción de descarte para mejorar la experiencia de usuario.

### 📊 Seguimiento y Analítica (GA4)

* **Rastreo de Interacciones**: Registro de eventos `translation_interaction` en Google Analytics 4.
* **Dimensiones capturadas**: Idioma del navegador, idioma de destino, tipo de interacción y estado de soporte.

---

## 3. Configuración Inicial

### Paso 1: Configuración de API Keys

Para que el motor funcione, debe configurar al menos una llave de API:

| Proveedor | Enlace para generar API Key | Documentación |
| :--- | :--- | :--- |
| **Anthropic (Claude)** | [Consola Anthropic](https://console.anthropic.com/) | [Guía Claude](https://docs.anthropic.com/) |
| **Google (Gemini)** | [Google AI Studio](https://aistudio.google.com/) | [Guía Gemini](https://ai.google.dev/) |
| **OpenAI (GPT)** | [Dashboard OpenAI](https://platform.openai.com/api-keys) | [Guía OpenAI](https://platform.openai.com/docs/) |

### Paso 2: Configuración de Google Analytics 4

1. Diríjase a **Engine IA > Configuración**.
2. Ingrese su **ID de Medición de GA4** (ejemplo: `G-XXXXXXXXXX`).
3. Si ya tiene GA4 configurado en su sitio, el plugin lo detectará automáticamente.

---

## 4. Guía de Uso

### Control de Procesamiento IA (Toggle Per-Post)

Desde la versión 3.1.0, cada post o página cuenta con un **toggle individual** para activar o desactivar el procesamiento IA:

#### En Gutenberg (Block Editor):
1. Abra el post en el editor de bloques.
2. En el **sidebar derecho**, localice el panel **"KzmCITO IA SEO"** (debajo de los paneles nativos de WordPress).
3. Use el **toggle "Activar KzmCITO IA SEO"** para controlar si el procesamiento se ejecutará al guardar.
4. El panel muestra el estado actual: fecha de último procesamiento y categoría detectada.
5. **Tip**: Puede reubicar el panel arrastrándolo a otra posición en el sidebar, como cualquier panel nativo de WordPress.

#### En Editor Clásico / WPBakery / Elementor:
1. Abra el post en el editor.
2. En el **panel lateral** (meta box), localice **"Engine Editorial IA"**.
3. Use el **switch toggle** para activar o desactivar el procesamiento.
4. El toggle se guarda automáticamente al guardar/actualizar el post.

### Procesamiento de Contenido

1. **Automático al guardar**: Con el toggle **activado** (por defecto), cada vez que haga clic en **Guardar**, **Publicar** o **Actualizar**, el contenido pasará por el pipeline de 4 fases.
2. **Manual**: En la edición de cualquier Post, encontrará el botón **"Procesar Ahora"** para ejecutar el pipeline bajo demanda.
3. **Desactivado**: Con el toggle **desactivado**, puede guardar el post sin que el plugin lo procese.

### Gestión de Prompts

Usted puede personalizar cómo la IA transforma los contenidos en **Engine IA > Prompts**.

* **Prompt Global**: Reglas base de estilo y seguridad.
* **Prompts por Categoría**: Instrucciones específicas para temas locales, salud, seguridad, etc.

---

## 5. Especificaciones Técnicas

* **Versión de PHP**: 8.0 o superior recomendada.
* **Integración SEO**: Compatible nativamente con **RankMath SEO**.
* **Editores compatibles**: Gutenberg (Block Editor), Editor Clásico, WPBakery, Elementor.
* **Seguridad**: Implementación estricta con sanitización de datos en cada fase.
* **Campos Meta**: El plugin almacena datos críticos en campos meta prefijados con `_kzmcito_` para no interferir con otros plugins.
* **Toggle per-post**: Campo `_kzmcito_ia_enabled` (boolean, default `true`) controla la activación del procesamiento por post individual.

---

## 6. Recursos Adicionales

* **Repositorio Oficial**: [GitHub - KassimCITO/kzmcito-ia-seo](https://github.com/KassimCITO/kzmcito-ia-seo)
* **Soporte Técnico**: Contactar a través de la consola de administración del plugin.

---
*Este manual fue generado automáticamente y está sujeto a actualizaciones según el desarrollo del software.*
