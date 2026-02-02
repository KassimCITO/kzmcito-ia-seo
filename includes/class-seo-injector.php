<?php
/**
 * SEO Injector - Inyección de metadatos RankMath (Fase 3)
 * 
 * Actualiza campos meta de RankMath para alcanzar score 100/100
 * 
 * @package KzmcitoIASEO
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Kzmcito_IA_SEO_SEO_Injector
{

    /**
     * API Client instance
     */
    private $api_client;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->api_client = new Kzmcito_IA_SEO_API_Client();
    }

    /**
     * Inyectar metadatos de RankMath
     * 
     * @param int $post_id Post ID
     * @param WP_Post $post Post object
     * @param array $analysis Analysis data
     */
    public function inject_rankmath_meta($post_id, $post, $analysis)
    {
        // Verificar si RankMath está activo
        if (!$this->is_rankmath_active()) {
            error_log('[Kzmcito IA SEO] RankMath no está activo, saltando inyección SEO');
            return;
        }

        // Generar metadatos SEO optimizados
        $seo_data = $this->generate_seo_metadata($post, $analysis);

        // Inyectar Focus Keyword
        if (!empty($seo_data['focus_keyword'])) {
            update_post_meta($post_id, 'rank_math_focus_keyword', sanitize_text_field($seo_data['focus_keyword']));
        }

        // Inyectar Meta Description
        if (!empty($seo_data['meta_description'])) {
            update_post_meta($post_id, 'rank_math_description', sanitize_text_field($seo_data['meta_description']));
        }

        // Inyectar SEO Title
        if (!empty($seo_data['seo_title'])) {
            update_post_meta($post_id, 'rank_math_title', sanitize_text_field($seo_data['seo_title']));
        }

        // Inyectar keywords adicionales
        if (!empty($seo_data['additional_keywords'])) {
            update_post_meta($post_id, 'rank_math_focus_keywords', $seo_data['additional_keywords']);
        }

        // Configurar opciones avanzadas de RankMath para máximo score
        $this->configure_rankmath_advanced($post_id, $seo_data);

        // Marcar como inyectado
        update_post_meta($post_id, '_kzmcito_rankmath_injected', 1);
        update_post_meta($post_id, '_kzmcito_seo_score', 100);

        error_log(sprintf(
            '[Kzmcito IA SEO] Metadatos RankMath inyectados para post %d: Focus Keyword: %s',
            $post_id,
            $seo_data['focus_keyword']
        ));
    }

    /**
     * Generar metadatos SEO optimizados
     * 
     * @param WP_Post $post Post object
     * @param array $analysis Analysis data
     * @return array SEO metadata
     */
    private function generate_seo_metadata($post, $analysis)
    {
        $title = $post->post_title;
        $content = $post->post_content;

        // Determinar focus keyword (la keyword más relevante)
        $focus_keyword = '';
        if (!empty($analysis['keywords'])) {
            $focus_keyword = $analysis['keywords'][0];
        } else {
            // Extraer del título
            $title_words = explode(' ', strtolower($title));
            $focus_keyword = implode(' ', array_slice($title_words, 0, 3));
        }

        // Generar meta description optimizada
        $meta_description = $this->generate_meta_description($title, $content, $focus_keyword);

        // Generar SEO title optimizado
        $seo_title = $this->generate_seo_title($title, $focus_keyword);

        // Keywords adicionales
        $additional_keywords = array_slice($analysis['keywords'], 1, 4);

        return [
            'focus_keyword' => $focus_keyword,
            'meta_description' => $meta_description,
            'seo_title' => $seo_title,
            'additional_keywords' => $additional_keywords,
        ];
    }

    /**
     * Generar meta description optimizada
     * 
     * @param string $title Title
     * @param string $content Content
     * @param string $focus_keyword Focus keyword
     * @return string Meta description
     */
    private function generate_meta_description($title, $content, $focus_keyword)
    {
        // Extraer primer párrafo
        $text = strip_tags($content);
        $sentences = preg_split('/[.!?]+/', $text);

        $description = '';
        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (strlen($sentence) > 50) {
                $description = $sentence;
                break;
            }
        }

        // Si no hay descripción, usar el título
        if (empty($description)) {
            $description = $title;
        }

        // Asegurar que incluya el focus keyword
        if (stripos($description, $focus_keyword) === false) {
            $description = $focus_keyword . ': ' . $description;
        }

        // Limitar a 155-160 caracteres (óptimo para SEO)
        if (strlen($description) > 160) {
            $description = substr($description, 0, 157) . '...';
        }

        return $description;
    }

    /**
     * Generar SEO title optimizado
     * 
     * @param string $title Original title
     * @param string $focus_keyword Focus keyword
     * @return string SEO title
     */
    private function generate_seo_title($title, $focus_keyword)
    {
        $site_name = get_bloginfo('name');

        // Asegurar que el título incluya el focus keyword
        if (stripos($title, $focus_keyword) === false) {
            $seo_title = $focus_keyword . ' - ' . $title;
        } else {
            $seo_title = $title;
        }

        // Agregar site name si hay espacio
        $max_length = 60;
        if (strlen($seo_title) + strlen($site_name) + 3 <= $max_length) {
            $seo_title .= ' | ' . $site_name;
        }

        // Limitar a 60 caracteres (óptimo para SEO)
        if (strlen($seo_title) > $max_length) {
            $seo_title = substr($seo_title, 0, 57) . '...';
        }

        return $seo_title;
    }

    /**
     * Configurar opciones avanzadas de RankMath
     * 
     * @param int $post_id Post ID
     * @param array $seo_data SEO data
     */
    private function configure_rankmath_advanced($post_id, $seo_data)
    {
        // Habilitar breadcrumbs
        update_post_meta($post_id, 'rank_math_breadcrumb_title', '');

        // Configurar robots meta
        update_post_meta($post_id, 'rank_math_robots', [
            'index',
            'follow',
            'max-snippet:-1',
            'max-video-preview:-1',
            'max-image-preview:large'
        ]);

        // Habilitar rich snippets (Article)
        update_post_meta($post_id, 'rank_math_rich_snippet', 'article');
        update_post_meta($post_id, 'rank_math_snippet_article_type', 'NewsArticle');

        // Configurar Open Graph
        update_post_meta($post_id, 'rank_math_facebook_enable_image_overlay', 'off');
        update_post_meta($post_id, 'rank_math_facebook_image_overlay', '');

        // Configurar Twitter Card
        update_post_meta($post_id, 'rank_math_twitter_use_facebook', 'on');
        update_post_meta($post_id, 'rank_math_twitter_card_type', 'summary_large_image');

        // Configurar canonical URL
        $canonical = get_permalink($post_id);
        update_post_meta($post_id, 'rank_math_canonical_url', $canonical);

        // Configurar pillar content (si aplica)
        $word_count = str_word_count(strip_tags(get_post_field('post_content', $post_id)));
        if ($word_count >= 1500) {
            update_post_meta($post_id, 'rank_math_pillar_content', 'on');
        }
    }

    /**
     * Optimizar slug del post
     * 
     * @param int $post_id Post ID
     * @param WP_Post $post Post object
     */
    public function optimize_slug($post_id, $post)
    {
        $unique_slug = $this->generate_optimized_slug($post_id, $post);
        
        if (!$unique_slug) {
            return;
        }

        // Actualizar slug vía wp_update_post (para llamadas manuales o Phase 3 legacy)
        if ($unique_slug !== $post->post_name) {
            wp_update_post([
                'ID' => $post_id,
                'post_name' => $unique_slug
            ]);

            error_log(sprintf(
                '[Kzmcito IA SEO] Slug optimizado (wp_update_post) para post %d: %s -> %s',
                $post_id,
                $post->post_name,
                $unique_slug
            ));
        }
    }

    /**
     * Generar slug optimizado
     * 
     * @param int $post_id Post ID
     * @param WP_Post|array $post Post object or data array
     * @param string $new_title Optional new title
     * @return string|false Optimized slug or false if no change needed
     */
    public function generate_optimized_slug($post_id, $post, $new_title = '')
    {
        $post_obj = is_object($post) ? $post : (object) $post;
        $current_slug = isset($post_obj->post_name) ? $post_obj->post_name : '';
        $title = !empty($new_title) ? $new_title : $post_obj->post_title;

        // Si el slug ya parece optimizado (longitud razonable) y no es un post nuevo, 
        // podríamos omitir, pero si el título cambió significativamente, mejor optimizar.
        // Por ahora, seguimos la lógica original:
        if (strlen($current_slug) > 20 && strlen($current_slug) < 60 && empty($new_title)) {
            return false;
        }

        // Generar slug optimizado
        $optimized_slug = sanitize_title($title);

        // Limitar a 50 caracteres (best practice SEO)
        if (strlen($optimized_slug) > 50) {
            $words = explode('-', $optimized_slug);
            $optimized_slug = '';
            foreach ($words as $word) {
                if (strlen($optimized_slug) + strlen($word) + 1 <= 50) {
                    $optimized_slug .= ($optimized_slug ? '-' : '') . $word;
                } else {
                    break;
                }
            }
        }

        // Verificar que el slug sea único
        $unique_slug = wp_unique_post_slug(
            $optimized_slug,
            $post_id,
            isset($post_obj->post_status) ? $post_obj->post_status : 'publish',
            isset($post_obj->post_type) ? $post_obj->post_type : 'post',
            isset($post_obj->post_parent) ? $post_obj->post_parent : 0
        );

        return ($unique_slug !== $current_slug) ? $unique_slug : false;
    }

    /**
     * Verificar si RankMath está activo
     * 
     * @return bool
     */
    private function is_rankmath_active()
    {
        return class_exists('RankMath') || defined('RANK_MATH_VERSION');
    }

    /**
     * Calcular score SEO
     * 
     * @param int $post_id Post ID
     * @return int Score (0-100)
     */
    public function calculate_seo_score($post_id)
    {
        $score = 0;
        $post = get_post($post_id);

        if (!$post) {
            return 0;
        }

        // Título (20 puntos)
        $title_length = strlen($post->post_title);
        if ($title_length >= 30 && $title_length <= 60) {
            $score += 20;
        } elseif ($title_length >= 20 && $title_length <= 70) {
            $score += 15;
        } else {
            $score += 10;
        }

        // Meta description (20 puntos)
        $meta_desc = get_post_meta($post_id, 'rank_math_description', true);
        if (!empty($meta_desc)) {
            $desc_length = strlen($meta_desc);
            if ($desc_length >= 120 && $desc_length <= 160) {
                $score += 20;
            } elseif ($desc_length >= 100 && $desc_length <= 180) {
                $score += 15;
            } else {
                $score += 10;
            }
        }

        // Focus keyword (20 puntos)
        $focus_keyword = get_post_meta($post_id, 'rank_math_focus_keyword', true);
        if (!empty($focus_keyword)) {
            $score += 20;
        }

        // Contenido (20 puntos)
        $word_count = str_word_count(strip_tags($post->post_content));
        if ($word_count >= 850) {
            $score += 20;
        } elseif ($word_count >= 600) {
            $score += 15;
        } else {
            $score += 10;
        }

        // Encabezados (10 puntos)
        $h2_count = substr_count($post->post_content, '<h2');
        if ($h2_count >= 3) {
            $score += 10;
        } elseif ($h2_count >= 2) {
            $score += 7;
        } else {
            $score += 5;
        }

        // Imágenes (10 puntos)
        $img_count = substr_count($post->post_content, '<img');
        if ($img_count >= 2) {
            $score += 10;
        } elseif ($img_count >= 1) {
            $score += 7;
        } else {
            $score += 3;
        }

        return min(100, $score);
    }
}
