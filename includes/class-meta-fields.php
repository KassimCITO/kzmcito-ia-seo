<?php
/**
 * Meta Fields Manager - Registro de campos meta personalizados
 * 
 * Gestiona todos los campos kzmcito_* para el plugin
 * 
 * @package KzmcitoIASEO
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Kzmcito_IA_SEO_Meta_Fields
{

    /**
     * Registrar todos los campos meta
     */
    public function register()
    {
        $this->register_processing_meta();
        $this->register_seo_meta();
        $this->register_translation_meta();
        $this->register_analysis_meta();
    }

    /**
     * Registrar meta fields de procesamiento
     */
    private function register_processing_meta()
    {
        // Última vez que se procesó el post
        register_post_meta('post', '_kzmcito_last_processed', [
            'type' => 'string',
            'description' => __('Fecha y hora del último procesamiento', 'kzmcito-ia-seo'),
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'sanitize_text_field',
        ]);

        register_post_meta('page', '_kzmcito_last_processed', [
            'type' => 'string',
            'description' => __('Fecha y hora del último procesamiento', 'kzmcito-ia-seo'),
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'sanitize_text_field',
        ]);

        // Categoría detectada
        register_post_meta('post', '_kzmcito_category_detected', [
            'type' => 'string',
            'description' => __('Categoría detectada por el sistema', 'kzmcito-ia-seo'),
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'sanitize_text_field',
        ]);

        register_post_meta('page', '_kzmcito_category_detected', [
            'type' => 'string',
            'description' => __('Categoría detectada por el sistema', 'kzmcito-ia-seo'),
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'sanitize_text_field',
        ]);

        // Inyección SEO pendiente
        register_post_meta('post', '_kzmcito_pending_seo_injection', [
            'type' => 'boolean',
            'description' => __('Indica si hay inyección SEO pendiente', 'kzmcito-ia-seo'),
            'single' => true,
            'show_in_rest' => false,
            'sanitize_callback' => 'absint',
        ]);

        register_post_meta('page', '_kzmcito_pending_seo_injection', [
            'type' => 'boolean',
            'description' => __('Indica si hay inyección SEO pendiente', 'kzmcito-ia-seo'),
            'single' => true,
            'show_in_rest' => false,
            'sanitize_callback' => 'absint',
        ]);

        // Log de procesamiento
        register_post_meta('post', '_kzmcito_processing_log', [
            'type' => 'array',
            'description' => __('Log de eventos de procesamiento', 'kzmcito-ia-seo'),
            'single' => true,
            'show_in_rest' => false,
        ]);

        register_post_meta('page', '_kzmcito_processing_log', [
            'type' => 'array',
            'description' => __('Log de eventos de procesamiento', 'kzmcito-ia-seo'),
            'single' => true,
            'show_in_rest' => false,
        ]);
    }

    /**
     * Registrar meta fields de análisis
     */
    private function register_analysis_meta()
    {
        // Datos de análisis
        register_post_meta('post', '_kzmcito_analysis_data', [
            'type' => 'array',
            'description' => __('Datos del análisis de contenido', 'kzmcito-ia-seo'),
            'single' => true,
            'show_in_rest' => false,
        ]);

        register_post_meta('page', '_kzmcito_analysis_data', [
            'type' => 'array',
            'description' => __('Datos del análisis de contenido', 'kzmcito-ia-seo'),
            'single' => true,
            'show_in_rest' => false,
        ]);

        // Keywords extraídas
        register_post_meta('post', '_kzmcito_keywords', [
            'type' => 'array',
            'description' => __('Keywords extraídas del contenido', 'kzmcito-ia-seo'),
            'single' => true,
            'show_in_rest' => true,
        ]);

        register_post_meta('page', '_kzmcito_keywords', [
            'type' => 'array',
            'description' => __('Keywords extraídas del contenido', 'kzmcito-ia-seo'),
            'single' => true,
            'show_in_rest' => true,
        ]);

        // Entidades extraídas
        register_post_meta('post', '_kzmcito_entities', [
            'type' => 'array',
            'description' => __('Entidades extraídas del contenido', 'kzmcito-ia-seo'),
            'single' => true,
            'show_in_rest' => true,
        ]);

        register_post_meta('page', '_kzmcito_entities', [
            'type' => 'array',
            'description' => __('Entidades extraídas del contenido', 'kzmcito-ia-seo'),
            'single' => true,
            'show_in_rest' => true,
        ]);
    }

    /**
     * Registrar meta fields de SEO
     */
    private function register_seo_meta()
    {
        // Score SEO
        register_post_meta('post', '_kzmcito_seo_score', [
            'type' => 'integer',
            'description' => __('Score SEO calculado', 'kzmcito-ia-seo'),
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'absint',
        ]);

        register_post_meta('page', '_kzmcito_seo_score', [
            'type' => 'integer',
            'description' => __('Score SEO calculado', 'kzmcito-ia-seo'),
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'absint',
        ]);

        // Datos de RankMath inyectados
        register_post_meta('post', '_kzmcito_rankmath_injected', [
            'type' => 'boolean',
            'description' => __('Indica si se inyectaron datos de RankMath', 'kzmcito-ia-seo'),
            'single' => true,
            'show_in_rest' => false,
            'sanitize_callback' => 'absint',
        ]);

        register_post_meta('page', '_kzmcito_rankmath_injected', [
            'type' => 'boolean',
            'description' => __('Indica si se inyectaron datos de RankMath', 'kzmcito-ia-seo'),
            'single' => true,
            'show_in_rest' => false,
            'sanitize_callback' => 'absint',
        ]);

        // TOC generado
        register_post_meta('post', '_kzmcito_has_toc', [
            'type' => 'boolean',
            'description' => __('Indica si se generó tabla de contenidos', 'kzmcito-ia-seo'),
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'absint',
        ]);

        register_post_meta('page', '_kzmcito_has_toc', [
            'type' => 'boolean',
            'description' => __('Indica si se generó tabla de contenidos', 'kzmcito-ia-seo'),
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'absint',
        ]);

        // FAQ generado
        register_post_meta('post', '_kzmcito_has_faq', [
            'type' => 'boolean',
            'description' => __('Indica si se generó FAQ', 'kzmcito-ia-seo'),
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'absint',
        ]);

        register_post_meta('page', '_kzmcito_has_faq', [
            'type' => 'boolean',
            'description' => __('Indica si se generó FAQ', 'kzmcito-ia-seo'),
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'absint',
        ]);
    }

    /**
     * Registrar meta fields de traducción
     */
    private function register_translation_meta()
    {
        // Cache de traducciones
        register_post_meta('post', 'kzmcito_translations_cache', [
            'type' => 'array',
            'description' => __('Cache de traducciones del contenido', 'kzmcito-ia-seo'),
            'single' => true,
            'show_in_rest' => false,
        ]);

        register_post_meta('page', 'kzmcito_translations_cache', [
            'type' => 'array',
            'description' => __('Cache de traducciones del contenido', 'kzmcito-ia-seo'),
            'single' => true,
            'show_in_rest' => false,
        ]);

        // Idiomas disponibles
        register_post_meta('post', '_kzmcito_available_languages', [
            'type' => 'array',
            'description' => __('Idiomas disponibles para este post', 'kzmcito-ia-seo'),
            'single' => true,
            'show_in_rest' => true,
        ]);

        register_post_meta('page', '_kzmcito_available_languages', [
            'type' => 'array',
            'description' => __('Idiomas disponibles para este post', 'kzmcito-ia-seo'),
            'single' => true,
            'show_in_rest' => true,
        ]);

        // Fecha de última traducción
        register_post_meta('post', '_kzmcito_last_translated', [
            'type' => 'string',
            'description' => __('Fecha de última traducción', 'kzmcito-ia-seo'),
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'sanitize_text_field',
        ]);

        register_post_meta('page', '_kzmcito_last_translated', [
            'type' => 'string',
            'description' => __('Fecha de última traducción', 'kzmcito-ia-seo'),
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'sanitize_text_field',
        ]);
    }

    /**
     * Obtener todos los meta fields de un post
     * 
     * @param int $post_id Post ID
     * @return array Meta fields
     */
    public function get_all_meta($post_id)
    {
        return [
            'processing' => [
                'last_processed' => get_post_meta($post_id, '_kzmcito_last_processed', true),
                'category_detected' => get_post_meta($post_id, '_kzmcito_category_detected', true),
                'pending_seo_injection' => get_post_meta($post_id, '_kzmcito_pending_seo_injection', true),
                'processing_log' => get_post_meta($post_id, '_kzmcito_processing_log', true),
            ],
            'analysis' => [
                'analysis_data' => get_post_meta($post_id, '_kzmcito_analysis_data', true),
                'keywords' => get_post_meta($post_id, '_kzmcito_keywords', true),
                'entities' => get_post_meta($post_id, '_kzmcito_entities', true),
            ],
            'seo' => [
                'seo_score' => get_post_meta($post_id, '_kzmcito_seo_score', true),
                'rankmath_injected' => get_post_meta($post_id, '_kzmcito_rankmath_injected', true),
                'has_toc' => get_post_meta($post_id, '_kzmcito_has_toc', true),
                'has_faq' => get_post_meta($post_id, '_kzmcito_has_faq', true),
            ],
            'translation' => [
                'translations_cache' => get_post_meta($post_id, 'kzmcito_translations_cache', true),
                'available_languages' => get_post_meta($post_id, '_kzmcito_available_languages', true),
                'last_translated' => get_post_meta($post_id, '_kzmcito_last_translated', true),
            ],
        ];
    }

    /**
     * Limpiar meta fields de un post
     * 
     * @param int $post_id Post ID
     */
    public function clean_meta($post_id)
    {
        $meta_keys = [
            '_kzmcito_last_processed',
            '_kzmcito_category_detected',
            '_kzmcito_pending_seo_injection',
            '_kzmcito_processing_log',
            '_kzmcito_analysis_data',
            '_kzmcito_keywords',
            '_kzmcito_entities',
            '_kzmcito_seo_score',
            '_kzmcito_rankmath_injected',
            '_kzmcito_has_toc',
            '_kzmcito_has_faq',
            'kzmcito_translations_cache',
            '_kzmcito_available_languages',
            '_kzmcito_last_translated',
        ];

        foreach ($meta_keys as $key) {
            delete_post_meta($post_id, $key);
        }
    }
}
