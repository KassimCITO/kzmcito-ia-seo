<?php
/**
 * Core Orchestrator - Motor principal del plugin
 * 
 * Orquesta el pipeline de 4 fases según Antigravity Master Specification
 * 
 * @package KzmcitoIASEO
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Kzmcito_IA_SEO_Core
{

    /**
     * Instancia del Prompt Manager
     */
    private $prompt_manager;

    /**
     * Instancia del Content Processor
     */
    private $content_processor;

    /**
     * Instancia del SEO Injector
     */
    private $seo_injector;

    /**
     * Instancia del Translation Manager
     */
    private $translation_manager;

    /**
     * Instancia del Cache Manager
     */
    private $cache_manager;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->init_components();
    }

    /**
     * Inicializar componentes
     */
    private function init_components()
    {
        $this->prompt_manager = new Kzmcito_IA_SEO_Prompt_Manager();
        $this->content_processor = new Kzmcito_IA_SEO_Content_Processor();
        $this->seo_injector = new Kzmcito_IA_SEO_SEO_Injector();
        $this->translation_manager = new Kzmcito_IA_SEO_Translation_Manager();
        $this->cache_manager = new Kzmcito_IA_SEO_Cache_Manager();
    }

    /**
     * Pipeline de 4 Fases - Procesamiento completo
     * 
     * @param array $data Post data
     * @param array $postarr Post array
     * @return array Modified post data
     */
    public function process_content($data, $postarr)
    {
        try {
            $post_id = isset($postarr['ID']) ? $postarr['ID'] : 0;

            // Registrar inicio del procesamiento
            $this->log_event('pipeline_start', $post_id, 'Iniciando pipeline de 4 fases');

            // FASE 1: ANÁLISIS
            $analysis = $this->phase_1_analysis($data, $postarr);
            $this->log_event('phase_1_complete', $post_id, 'Análisis completado', $analysis);

            // FASE 2: TRANSFORMACIÓN
            $transformed_data = $this->phase_2_transformation($data, $analysis);
            $this->log_event('phase_2_complete', $post_id, 'Transformación completada');

            // FASE 2.5: OPTIMIZACIÓN DE SLUG (Para Redirecciones RankMath adecuadas)
            if ($post_id) {
                $optimized_slug = $this->seo_injector->generate_optimized_slug(
                    $post_id, 
                    get_post($post_id), 
                    $transformed_data['post_title']
                );
                
                if ($optimized_slug) {
                    $transformed_data['post_name'] = $optimized_slug;
                    $this->log_event('slug_optimized_filter', $post_id, 'Slug optimizado en el filtro: ' . $optimized_slug);
                }
            }

            // FASE 3: INYECCIÓN SEO
            // (Se ejecuta en save_post hook para tener acceso al post_id)

            // Marcar para procesamiento de Fase 3 y 4
            if ($post_id) {
                update_post_meta($post_id, '_kzmcito_pending_seo_injection', 1);
                update_post_meta($post_id, '_kzmcito_analysis_data', $analysis);
            }

            return $transformed_data;

        } catch (Exception $e) {
            $this->log_event('pipeline_error', $post_id, 'Error en pipeline: ' . $e->getMessage());

            // En caso de error, devolver datos originales
            return $data;
        }
    }

    /**
     * FASE 1: ANÁLISIS
     * Identificación de keywords, entidades y categoría
     * 
     * @param array $data Post data
     * @param array $postarr Post array
     * @return array Analysis results
     */
    private function phase_1_analysis($data, $postarr)
    {
        $content = $data['post_content'];
        $title = str_replace(['«', '»'], '"', $data['post_title']);
        $data['post_title'] = $title; // Sanitizar también en el objeto de datos original

        // Detectar categoría del post
        $category = $this->detect_category($postarr);

        // Cargar prompts (Global + Categoría)
        $prompts = $this->prompt_manager->load_prompts($category);

        // Análisis de contenido
        $analysis = [
            'category' => $category,
            'prompts' => $prompts,
            'word_count' => str_word_count(strip_tags($content)),
            'has_headings' => $this->count_headings($content),
            'keywords' => $this->extract_keywords($content, $title),
            'entities' => $this->extract_entities($content),
            'needs_expansion' => str_word_count(strip_tags($content)) < get_option('kzmcito_min_words', 650),
            'needs_toc' => $this->count_headings($content)['h2'] >= 2,
            'needs_faq' => $this->should_add_faq($content, $category),
        ];

        return $analysis;
    }

    /**
     * FASE 2: TRANSFORMACIÓN
     * Modificación del contenido (Limpieza + TOC + FAQ + Expansión + Hx)
     * 
     * @param array $data Post data
     * @param array $analysis Analysis results
     * @return array Modified post data
     */
    private function phase_2_transformation($data, $analysis)
    {
        $content = $data['post_content'];

        // 1. Limpieza y sanitización
        $content = $this->content_processor->clean_content($content);

        // 2. Expansión de contenido si es necesario
        if ($analysis['needs_expansion']) {
            $content = $this->content_processor->expand_content(
                $content,
                $data['post_title'],
                $analysis['prompts'],
                $analysis['category']
            );
        }

        // 3. Generar e insertar encabezados H2-H4
        $content = $this->content_processor->enhance_headings($content, $analysis);

        // 4. Insertar TOC si es necesario
        if ($analysis['needs_toc'] && get_option('kzmcito_enable_toc', 'yes') === 'yes') {
            $content = $this->content_processor->insert_toc($content);
        }

        // 5. Insertar FAQ si es necesario
        if ($analysis['needs_faq'] && get_option('kzmcito_enable_faq', 'yes') === 'yes') {
            $content = $this->content_processor->insert_faq($content, $analysis);
        }

        // Actualizar el contenido
        $data['post_content'] = $content;

        return $data;
    }

    /**
     * FASE 3: INYECCIÓN SEO
     * Persistencia de metadatos RankMath y optimización de slugs
     * 
     * @param int $post_id Post ID
     * @param array $analysis Analysis results
     */
    public function phase_3_seo_injection($post_id, $analysis)
    {
        // Verificar si hay inyección pendiente
        if (!get_post_meta($post_id, '_kzmcito_pending_seo_injection', true)) {
            return;
        }

        $post = get_post($post_id);

        // Inyectar metadatos de RankMath
        $this->seo_injector->inject_rankmath_meta($post_id, $post, $analysis);

        // Optimizar slug
        $this->seo_injector->optimize_slug($post_id, $post);

        // Marcar como procesado
        delete_post_meta($post_id, '_kzmcito_pending_seo_injection');
        update_post_meta($post_id, '_kzmcito_last_processed', current_time('mysql'));
        update_post_meta($post_id, '_kzmcito_category_detected', $analysis['category']);

        $this->log_event('phase_3_complete', $post_id, 'Inyección SEO completada');
    }

    /**
     * Asegurar que el post esté procesado (Fases 1-3)
     * Usado para Just-In-Time processing
     * 
     * @param int $post_id Post ID
     * @return bool True si está procesado o se procesó exitosamente
     */
    public function ensure_post_is_processed($post_id)
    {
        $last_processed = get_post_meta($post_id, '_kzmcito_last_processed', true);
        
        if ($last_processed) {
            return true;
        }

        $post = get_post($post_id);
        if (!$post) return false;

        $this->log_event('jit_processing_start', $post_id, 'Iniciando procesamiento Just-In-Time');

        // Preparar datos para el pipeline
        $data = [
            'post_content' => $post->post_content,
            'post_title' => $post->post_title,
            'post_type' => $post->post_type,
        ];

        $postarr = [
            'ID' => $post_id,
            'post_category' => wp_get_post_categories($post_id),
        ];

        // Ejecutar pipeline Fases 1-2
        $processed_data = $this->process_content($data, $postarr);

        // Actualizar post content si cambió
        if ($processed_data['post_content'] !== $post->post_content) {
            wp_update_post([
                'ID' => $post_id,
                'post_content' => $processed_data['post_content'],
            ]);
        }

        // Ejecutar Fase 3 (SEO Injection)
        $analysis = get_post_meta($post_id, '_kzmcito_analysis_data', true);
        $this->phase_3_seo_injection($post_id, $analysis);

        return true;
    }

    /**
     * FASE 4: LOCALIZACIÓN
     * Generación de versiones en idiomas activos y guardado en caché
     * 
     * @param int $post_id Post ID
     * @param WP_Post $post Post object
     */
    public function process_translations($post_id, $post)
    {
        // Obtener datos de análisis
        $analysis = get_post_meta($post_id, '_kzmcito_analysis_data', true);

        // EJECUTAR FASE 3 (SEO INJECTION) - Esto es rápido y necesario en el guardado
        if (get_post_meta($post_id, '_kzmcito_pending_seo_injection', true)) {
            $this->phase_3_seo_injection($post_id, $analysis);
        }

        // COMENTADO: Ya no generamos todas las traducciones en el save_post
        // Las traducciones se generarán Just-In-Time (JIT) en la primera visita real
        // $this->translation_manager->generate_translations($post_id, $post, $analysis);

        $this->log_event('save_process_complete', $post_id, 'Proceso de guardado completado. Traducciones pendientes para JIT.');

        // LIMPIAR CACHÉ
        $this->cache_manager->clear_post_cache($post_id);
        
        // Purgar Cloudflare
        $this->cache_manager->purge_cloudflare($post_id);

        $this->log_event('cache_cleared', $post_id, 'Caché limpiado. JIT listo para activarse.');
    }

    /**
     * Procesar post manualmente (AJAX)
     * 
     * @param int $post_id Post ID
     * @return array Result
     */
    public function process_post_manually($post_id)
    {
        $post = get_post($post_id);

        if (!$post) {
            return [
                'success' => false,
                'message' => __('Post no encontrado', 'kzmcito-ia-seo')
            ];
        }

        try {
            // Preparar datos
            $data = [
                'post_content' => $post->post_content,
                'post_title' => $post->post_title,
                'post_type' => $post->post_type,
            ];

            $postarr = [
                'ID' => $post_id,
                'post_category' => wp_get_post_categories($post_id),
            ];

            // Ejecutar pipeline
            $processed_data = $this->process_content($data, $postarr);

            // Actualizar post
            wp_update_post([
                'ID' => $post_id,
                'post_content' => $processed_data['post_content'],
            ]);

            // Procesar traducciones
            $this->process_translations($post_id, get_post($post_id));

            return [
                'success' => true,
                'message' => __('Contenido procesado exitosamente', 'kzmcito-ia-seo'),
                'data' => [
                    'word_count' => str_word_count(strip_tags($processed_data['post_content'])),
                    'category' => get_post_meta($post_id, '_kzmcito_category_detected', true),
                ]
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Detectar categoría del post
     * 
     * @param array $postarr Post array
     * @return string Category slug
     */
    private function detect_category($postarr)
    {
        $categories = isset($postarr['post_category']) ? $postarr['post_category'] : [];

        // Mapeo de categorías a prompts
        $category_map = [
            'michoacan' => '01-michoacan',
            'educacion' => '02-educacion',
            'entretenimiento' => '03-entretenimiento',
            'justicia' => '04-justicia',
            'salud' => '05-salud',
            'seguridad' => '06-seguridad',
        ];

        // Buscar categoría coincidente
        foreach ($categories as $cat_id) {
            $category = get_category($cat_id);
            if ($category) {
                $slug = $category->slug;
                foreach ($category_map as $key => $prompt_file) {
                    if (strpos($slug, $key) !== false) {
                        return $key;
                    }
                }
            }
        }

        // Fallback: usar prompt global
        $this->log_event('category_fallback', 0, 'No se detectó categoría específica, usando prompt global');
        return 'global';
    }

    /**
     * Contar encabezados en el contenido
     * 
     * @param string $content Content
     * @return array Heading counts
     */
    private function count_headings($content)
    {
        return [
            'h2' => substr_count($content, '<h2'),
            'h3' => substr_count($content, '<h3'),
            'h4' => substr_count($content, '<h4'),
        ];
    }

    /**
     * Extraer keywords del contenido
     * 
     * @param string $content Content
     * @param string $title Title
     * @return array Keywords
     */
    private function extract_keywords($content, $title)
    {
        // Implementación básica - puede mejorarse con NLP
        $text = strip_tags($content . ' ' . $title);
        $words = str_word_count(strtolower($text), 1, 'áéíóúñü');

        // Filtrar palabras comunes (stop words)
        $stop_words = ['el', 'la', 'de', 'que', 'y', 'a', 'en', 'un', 'ser', 'se', 'no', 'haber', 'por', 'con', 'su', 'para', 'como', 'estar', 'tener', 'le', 'lo', 'todo', 'pero', 'más', 'hacer', 'o', 'poder', 'decir', 'este', 'ir', 'otro', 'ese', 'la', 'si', 'me', 'ya', 'ver', 'porque', 'dar', 'cuando', 'él', 'muy', 'sin', 'vez', 'mucho', 'saber', 'qué', 'sobre', 'mi', 'alguno', 'mismo', 'yo', 'también', 'hasta', 'año', 'dos', 'querer', 'entre', 'así', 'primero', 'desde', 'grande', 'eso', 'ni', 'nos', 'llegar', 'pasar', 'tiempo', 'ella', 'sí', 'día', 'uno', 'bien', 'poco', 'deber', 'entonces', 'poner', 'cosa', 'tanto', 'hombre', 'parecer', 'nuestro', 'tan', 'donde', 'ahora', 'parte', 'después', 'vida', 'quedar', 'siempre', 'creer', 'hablar', 'llevar', 'dejar', 'nada', 'cada', 'seguir', 'menos', 'nuevo', 'encontrar', 'algo', 'solo', 'decir', 'salir', 'volver', 'tomar', 'conocer', 'vivir', 'sentir', 'tratar', 'mirar', 'contar', 'empezar', 'esperar', 'buscar', 'existir', 'entrar', 'trabajar', 'escribir', 'perder', 'producir', 'ocurrir', 'entender', 'pedir', 'recibir', 'recordar', 'terminar', 'permitir', 'aparecer', 'conseguir', 'comenzar', 'servir', 'sacar', 'necesitar', 'mantener', 'resultar', 'leer', 'caer', 'cambiar', 'presentar', 'crear', 'abrir', 'considerar', 'oír', 'acabar', 'mil', 'contra', 'cual'];

        $words = array_diff($words, $stop_words);
        $word_freq = array_count_values($words);
        arsort($word_freq);

        return array_slice(array_keys($word_freq), 0, 10);
    }

    /**
     * Extraer entidades del contenido
     * 
     * @param string $content Content
     * @return array Entities
     */
    private function extract_entities($content)
    {
        // Implementación básica - detectar palabras capitalizadas
        $text = strip_tags($content);
        preg_match_all('/\b[A-ZÁÉÍÓÚÑ][a-záéíóúñ]+(?:\s+[A-ZÁÉÍÓÚÑ][a-záéíóúñ]+)*\b/', $text, $matches);

        return array_unique(array_slice($matches[0], 0, 20));
    }

    /**
     * Determinar si se debe agregar FAQ
     * 
     * @param string $content Content
     * @param string $category Category
     * @return bool
     */
    private function should_add_faq($content, $category)
    {
        // Categorías que típicamente necesitan FAQ
        $faq_categories = ['salud', 'educacion', 'justicia'];

        if (in_array($category, $faq_categories)) {
            return true;
        }

        // Detectar preguntas en el contenido
        $question_count = substr_count($content, '?');

        return $question_count >= 2;
    }

    /**
     * Registrar evento en el log
     * 
     * @param string $event Event name
     * @param int $post_id Post ID
     * @param string $message Message
     * @param mixed $data Additional data
     */
    private function log_event($event, $post_id, $message, $data = null)
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[Kzmcito IA SEO] [%s] Post ID: %d - %s',
                $event,
                $post_id,
                $message
            ));

            if ($data) {
                error_log('[Kzmcito IA SEO] Data: ' . print_r($data, true));
            }
        }

        // Guardar en meta del post para debugging
        if ($post_id) {
            $logs = get_post_meta($post_id, '_kzmcito_processing_log', true);
            if (!is_array($logs)) {
                $logs = [];
            }

            $logs[] = [
                'timestamp' => current_time('mysql'),
                'event' => $event,
                'message' => $message,
                'data' => $data,
            ];

            update_post_meta($post_id, '_kzmcito_processing_log', $logs);
        }
    }
}
