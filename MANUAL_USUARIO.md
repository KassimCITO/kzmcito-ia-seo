# Manual de Usuario: Engine Editorial IA (Versión 2.5.0)

![Proceso IA Engine](kzmcito_ai_process_workflow_1770336834631.png)

## 1. Introducción

El **Engine Editorial IA** es una solución integral para WordPress diseñada para automatizar la transformación de contenidos, optimización SEO y localización multilingüe. Utilizando modelos de inteligencia artificial de vanguardia, el plugin convierte borradores simples en artículos profesionales listos para ser publicados.

---

## 2. Características Principales

### 🤖 Inteligencia Artificial Multifacética

* **Soporte Multimodelo**: Compatible con Claude 3.5 (Sonnet/Opus), Google Gemini Pro y OpenAI GPT-4.
* **Procesamiento Jerárquico**: Combina prompts globales con instrucciones específicas por categoría (Educación, Salud, Justicia, etc.).
* **Pipeline de 4 Fases**: Análisis, Transformación, Inyección SEO y Localización.

### 🌍 Localización y Traducción

* **Traducción JIT (Just-In-Time)**: Genera traducciones automáticamente al ser solicitadas por el usuario.
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

### Procesamiento de Contenido

1. **Automático**: Active "Procesar al guardar" en la configuración para que cada nuevo post pase por el pipeline.
2. **Manual**: En la edición de cualquier Post, encontrará el panel lateral **Engine Editorial IA** con el botón **"Procesar Ahora"**.

### Gestión de Prompts

Usted puede personalizar cómo la IA transforma los contenidos en **Engine IA > Prompts**.

* **Prompt Global**: Reglas base de estilo y seguridad.
* **Prompts por Categoría**: Instrucciones específicas para temas locales, salud, seguridad, etc.

---

## 5. Especificaciones Técnicas

* **Versión de PHP**: 8.0 o superior recomendada.
* **Integración SEO**: Compatible nativamente con **RankMath SEO**.
* **Seguridad**: Implementación estricta de principios `Antigravity` con sanitización de datos en cada fase.
* **Campos Meta**: El plugin almacena datos críticos en campos meta prefijados con `_kzmcito_` para no interferir con otros plugins.

---

## 6. Recursos Adicionales

* **Repositorio Oficial**: [GitHub - KassimCITO/kzmcito-ia-seo](https://github.com/KassimCITO/kzmcito-ia-seo)
* **Soporte Técnico**: Contactar a través de la consola de administración del plugin.

---
*Este manual fue generado automáticamente y está sujeto a actualizaciones según el desarrollo del software.*
