<?php
/**
 * Cache Manager - Integración con WP-Rocket y otros plugins de caché
 * 
 * Gestiona la limpieza y precarga de caché después del procesamiento
 * 
 * @package KzmcitoIASEO
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Kzmcito_IA_SEO_Cache_Manager
{

    /**
     * Plugins de caché detectados
     */
    private $cache_plugins = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->detect_cache_plugins();
    }

    /**
     * Detectar plugins de caché activos
     */
    private function detect_cache_plugins()
    {
        // WP-Rocket
        if (function_exists('rocket_clean_post')) {
            $this->cache_plugins['wp-rocket'] = true;
        }

        // W3 Total Cache
        if (function_exists('w3tc_flush_post')) {
            $this->cache_plugins['w3-total-cache'] = true;
        }

        // WP Super Cache
        if (function_exists('wp_cache_post_change')) {
            $this->cache_plugins['wp-super-cache'] = true;
        }

        // LiteSpeed Cache
        if (class_exists('LiteSpeed_Cache_API')) {
            $this->cache_plugins['litespeed-cache'] = true;
        }

        // WP Fastest Cache
        if (class_exists('WpFastestCache')) {
            $this->cache_plugins['wp-fastest-cache'] = true;
        }

        // Autoptimize
        if (class_exists('autoptimizeCache')) {
            $this->cache_plugins['autoptimize'] = true;
        }

        error_log('[Kzmcito IA SEO] Plugins de caché detectados: ' . implode(', ', array_keys($this->cache_plugins)));
    }

    /**
     * Limpiar caché de un post específico
     * 
     * @param int $post_id Post ID
     */
    public function clear_post_cache($post_id)
    {
        if (empty($this->cache_plugins)) {
            error_log('[Kzmcito IA SEO] No se detectaron plugins de caché');
            return;
        }

        $cleared = [];

        // WP-Rocket
        if (isset($this->cache_plugins['wp-rocket'])) {
            $this->clear_rocket_post($post_id);
            $cleared[] = 'WP-Rocket';
        }

        // W3 Total Cache
        if (isset($this->cache_plugins['w3-total-cache'])) {
            $this->clear_w3tc_post($post_id);
            $cleared[] = 'W3 Total Cache';
        }

        // WP Super Cache
        if (isset($this->cache_plugins['wp-super-cache'])) {
            $this->clear_wpsc_post($post_id);
            $cleared[] = 'WP Super Cache';
        }

        // LiteSpeed Cache
        if (isset($this->cache_plugins['litespeed-cache'])) {
            $this->clear_litespeed_post($post_id);
            $cleared[] = 'LiteSpeed Cache';
        }

        // WP Fastest Cache
        if (isset($this->cache_plugins['wp-fastest-cache'])) {
            $this->clear_wpfc_post($post_id);
            $cleared[] = 'WP Fastest Cache';
        }

        // Autoptimize
        if (isset($this->cache_plugins['autoptimize'])) {
            $this->clear_autoptimize_cache();
            $cleared[] = 'Autoptimize';
        }

        // Limpiar caché de objeto de WordPress
        wp_cache_delete($post_id, 'posts');
        wp_cache_delete($post_id, 'post_meta');

        error_log(sprintf(
            '[Kzmcito IA SEO] Caché limpiado para post %d en: %s',
            $post_id,
            implode(', ', $cleared)
        ));

        // Registrar estadística
        $this->log_cache_clear($post_id, $cleared);
    }

    /**
     * Limpiar caché completo del sitio
     */
    public function clear_site_cache()
    {
        $cleared = [];

        // WP-Rocket
        if (isset($this->cache_plugins['wp-rocket'])) {
            if (function_exists('rocket_clean_domain')) {
                rocket_clean_domain();
                $cleared[] = 'WP-Rocket (dominio completo)';
            }
        }

        // W3 Total Cache
        if (isset($this->cache_plugins['w3-total-cache'])) {
            if (function_exists('w3tc_flush_all')) {
                w3tc_flush_all();
                $cleared[] = 'W3 Total Cache (todo)';
            }
        }

        // WP Super Cache
        if (isset($this->cache_plugins['wp-super-cache'])) {
            if (function_exists('wp_cache_clear_cache')) {
                wp_cache_clear_cache();
                $cleared[] = 'WP Super Cache (todo)';
            }
        }

        // LiteSpeed Cache
        if (isset($this->cache_plugins['litespeed-cache'])) {
            if (method_exists('LiteSpeed_Cache_API', 'purge_all')) {
                LiteSpeed_Cache_API::purge_all();
                $cleared[] = 'LiteSpeed Cache (todo)';
            }
        }

        // WP Fastest Cache
        if (isset($this->cache_plugins['wp-fastest-cache'])) {
            if (class_exists('WpFastestCache')) {
                $wpfc = new WpFastestCache();
                if (method_exists($wpfc, 'deleteCache')) {
                    $wpfc->deleteCache();
                    $cleared[] = 'WP Fastest Cache (todo)';
                }
            }
        }

        error_log('[Kzmcito IA SEO] Caché del sitio limpiado: ' . implode(', ', $cleared));
    }

    /**
     * Limpiar caché de WP-Rocket para un post
     * 
     * @param int $post_id Post ID
     */
    private function clear_rocket_post($post_id)
    {
        // Limpiar caché del post
        if (function_exists('rocket_clean_post')) {
            rocket_clean_post($post_id);
        }

        // Limpiar caché de minificación
        if (function_exists('rocket_clean_minify')) {
            rocket_clean_minify();
        }

        // Limpiar caché de archivos CSS/JS
        if (function_exists('rocket_clean_cache_busting')) {
            rocket_clean_cache_busting();
        }

        // Limpiar caché de páginas relacionadas (home, archivo, etc.)
        if (function_exists('rocket_clean_home')) {
            rocket_clean_home();
        }

        // Purgar Cloudflare si está configurado
        if (function_exists('rocket_purge_cloudflare')) {
            rocket_purge_cloudflare();
        }
    }

    /**
     * Limpiar caché de W3 Total Cache para un post
     * 
     * @param int $post_id Post ID
     */
    private function clear_w3tc_post($post_id)
    {
        if (function_exists('w3tc_flush_post')) {
            w3tc_flush_post($post_id);
        }

        // Limpiar caché de minificación
        if (function_exists('w3tc_flush_minify')) {
            w3tc_flush_minify();
        }
    }

    /**
     * Limpiar caché de WP Super Cache para un post
     * 
     * @param int $post_id Post ID
     */
    private function clear_wpsc_post($post_id)
    {
        if (function_exists('wp_cache_post_change')) {
            wp_cache_post_change($post_id);
        }
    }

    /**
     * Limpiar caché de LiteSpeed para un post
     * 
     * @param int $post_id Post ID
     */
    private function clear_litespeed_post($post_id)
    {
        if (method_exists('LiteSpeed_Cache_API', 'purge_post')) {
            LiteSpeed_Cache_API::purge_post($post_id);
        }
    }

    /**
     * Limpiar caché de WP Fastest Cache para un post
     * 
     * @param int $post_id Post ID
     */
    private function clear_wpfc_post($post_id)
    {
        if (class_exists('WpFastestCache')) {
            $wpfc = new WpFastestCache();
            if (method_exists($wpfc, 'singleDeleteCache')) {
                $wpfc->singleDeleteCache(false, $post_id);
            }
        }
    }

    /**
     * Limpiar caché de Autoptimize
     */
    private function clear_autoptimize_cache()
    {
        if (class_exists('autoptimizeCache')) {
            autoptimizeCache::clearall();
        }
    }

    /**
     * Pre-cargar caché para un post y sus traducciones
     * 
     * @param int $post_id Post ID
     */
    public function preload_post_cache($post_id)
    {
        // Solo pre-cargar si WP-Rocket está activo
        if (!isset($this->cache_plugins['wp-rocket'])) {
            return;
        }

        $urls_to_preload = [];

        // URL del post original
        $post_url = get_permalink($post_id);
        if ($post_url) {
            $urls_to_preload[] = $post_url;
        }

        // URLs de traducciones (si existen)
        $available_languages = get_post_meta($post_id, '_kzmcito_available_languages', true);
        if (is_array($available_languages)) {
            foreach ($available_languages as $lang) {
                // Agregar parámetro de idioma a la URL
                $translated_url = add_query_arg('lang', $lang, $post_url);
                $urls_to_preload[] = $translated_url;
            }
        }

        // Pre-cargar URLs
        foreach ($urls_to_preload as $url) {
            $this->preload_url($url);
        }

        error_log(sprintf(
            '[Kzmcito IA SEO] Pre-carga de caché iniciada para post %d (%d URLs)',
            $post_id,
            count($urls_to_preload)
        ));
    }

    /**
     * Pre-cargar una URL específica
     * 
     * @param string $url URL to preload
     */
    private function preload_url($url)
    {
        // Hacer una petición HTTP para generar el caché
        wp_remote_get($url, [
            'timeout' => 10,
            'sslverify' => false,
            'headers' => [
                'User-Agent' => 'Kzmcito-IA-SEO-Cache-Preloader/2.0'
            ]
        ]);
    }

    /**
     * Limpiar caché de Cloudflare (si está configurado con WP-Rocket)
     * 
     * @param int $post_id Post ID
     */
    public function purge_cloudflare($post_id = null)
    {
        if (!isset($this->cache_plugins['wp-rocket'])) {
            return;
        }

        if ($post_id) {
            // Purgar URL específica en Cloudflare
            $post_url = get_permalink($post_id);
            if (function_exists('rocket_purge_cloudflare_url')) {
                rocket_purge_cloudflare_url($post_url);
            }
        } else {
            // Purgar todo Cloudflare
            if (function_exists('rocket_purge_cloudflare')) {
                rocket_purge_cloudflare();
            }
        }
    }

    /**
     * Obtener estadísticas de caché
     * 
     * @return array Cache statistics
     */
    public function get_cache_stats()
    {
        $stats = [
            'plugins_detected' => array_keys($this->cache_plugins),
            'total_clears' => get_option('kzmcito_cache_clear_count', 0),
            'last_clear' => get_option('kzmcito_cache_last_clear', ''),
            'posts_cleared' => get_option('kzmcito_cache_posts_cleared', []),
        ];

        return $stats;
    }

    /**
     * Registrar limpieza de caché
     * 
     * @param int $post_id Post ID
     * @param array $cleared Cleared plugins
     */
    private function log_cache_clear($post_id, $cleared)
    {
        // Incrementar contador
        $count = get_option('kzmcito_cache_clear_count', 0);
        update_option('kzmcito_cache_clear_count', $count + 1);

        // Actualizar última limpieza
        update_option('kzmcito_cache_last_clear', current_time('mysql'));

        // Registrar posts limpiados
        $posts_cleared = get_option('kzmcito_cache_posts_cleared', []);
        $posts_cleared[$post_id] = [
            'timestamp' => current_time('mysql'),
            'plugins' => $cleared,
        ];

        // Mantener solo los últimos 100 registros
        if (count($posts_cleared) > 100) {
            $posts_cleared = array_slice($posts_cleared, -100, 100, true);
        }

        update_option('kzmcito_cache_posts_cleared', $posts_cleared);
    }

    /**
     * Verificar si WP-Rocket está activo
     * 
     * @return bool
     */
    public function is_rocket_active()
    {
        return isset($this->cache_plugins['wp-rocket']);
    }

    /**
     * Obtener configuración de WP-Rocket
     * 
     * @return array|false Rocket configuration
     */
    public function get_rocket_config()
    {
        if (!$this->is_rocket_active()) {
            return false;
        }

        $config = [];

        // Verificar si la minificación está activa
        if (function_exists('get_rocket_option')) {
            $config['minify_css'] = get_rocket_option('minify_css', false);
            $config['minify_js'] = get_rocket_option('minify_js', false);
            $config['minify_html'] = get_rocket_option('minify_html', false);
            $config['cache_mobile'] = get_rocket_option('cache_mobile', false);
            $config['do_caching_mobile_files'] = get_rocket_option('do_caching_mobile_files', false);
        }

        return $config;
    }

    /**
     * Optimizar configuración de WP-Rocket para el plugin
     */
    public function optimize_rocket_config()
    {
        if (!$this->is_rocket_active()) {
            return;
        }

        // Excluir parámetros de idioma del caché
        if (function_exists('get_rocket_option') && function_exists('update_rocket_option')) {
            $cache_query_strings = get_rocket_option('cache_query_strings', []);

            if (!in_array('lang', $cache_query_strings)) {
                $cache_query_strings[] = 'lang';
                update_rocket_option('cache_query_strings', $cache_query_strings);
            }
        }

        error_log('[Kzmcito IA SEO] Configuración de WP-Rocket optimizada');
    }

    /**
     * Hook: Limpiar caché después de procesar un post
     * 
     * @param int $post_id Post ID
     */
    public function after_process_hook($post_id)
    {
        // Limpiar caché del post
        $this->clear_post_cache($post_id);

        // Pre-cargar caché
        $this->preload_post_cache($post_id);

        // Purgar Cloudflare si está configurado
        $this->purge_cloudflare($post_id);
    }
}
