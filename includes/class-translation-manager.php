<?php
/**
 * Translation Manager - Gestor de traducciones (Fase 4)
 * 
 * Generación de versiones multilingües y caché
 * 
 * @package KzmcitoIASEO
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Kzmcito_IA_SEO_Translation_Manager
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
     * Generar traducciones para todos los idiomas activos
     * 
     * @param int $post_id Post ID
     * @param WP_Post $post Post object
     * @param array $analysis Analysis data
     */
    public function generate_translations($post_id, $post, $analysis)
    {
        $active_languages = $this->get_active_languages();

        if (empty($active_languages)) {
            return;
        }

        $translations = [];

        foreach ($active_languages as $lang) {
            $translation = $this->translate_post($post_id, $lang['code']);

            if ($translation['success']) {
                $translations[$lang['code']] = $translation['data'];
            }
        }

        // Guardar en caché
        update_post_meta($post_id, 'kzmcito_translations_cache', $translations);
        update_post_meta($post_id, '_kzmcito_available_languages', array_keys($translations));
        update_post_meta($post_id, '_kzmcito_last_translated', current_time('mysql'));

        error_log(sprintf(
            '[Kzmcito IA SEO] Traducciones generadas para post %d: %d idiomas',
            $post_id,
            count($translations)
        ));
    }

    /**
     * Traducir post a un idioma específico
     * 
     * @param int $post_id Post ID
     * @param string $language Language code
     * @return array Result
     */
    public function translate_post($post_id, $language)
    {
        $post = get_post($post_id);

        if (!$post) {
            return [
                'success' => false,
                'message' => __('Post no encontrado', 'kzmcito-ia-seo')
            ];
        }

        // Verificar cache
        $cache = get_post_meta($post_id, 'kzmcito_translations_cache', true);
        if (is_array($cache) && isset($cache[$language])) {
            return [
                'success' => true,
                'message' => __('Traducción obtenida desde caché', 'kzmcito-ia-seo'),
                'data' => $cache[$language],
                'from_cache' => true
            ];
        }

        // Obtener nombre del idioma
        $lang_info = $this->get_language_info($language);

        if (!$lang_info) {
            return [
                'success' => false,
                'message' => __('Idioma no válido', 'kzmcito-ia-seo')
            ];
        }

        // Traducir contenido
        $translated_content = $this->translate_content(
            $post->post_content,
            $language,
            $lang_info['name']
        );

        // Traducir título
        $translated_title = $this->translate_text(
            $post->post_title,
            $language,
            $lang_info['name']
        );

        // Traducir meta description
        $meta_desc = get_post_meta($post_id, 'rank_math_description', true);
        $translated_meta_desc = '';
        if (!empty($meta_desc)) {
            $translated_meta_desc = $this->translate_text(
                $meta_desc,
                $language,
                $lang_info['name']
            );
        }

        $translation_data = [
            'language' => $language,
            'language_name' => $lang_info['name'],
            'title' => $translated_title,
            'content' => $translated_content,
            'meta_description' => $translated_meta_desc,
            'translated_at' => current_time('mysql'),
        ];

        return [
            'success' => true,
            'message' => sprintf(__('Traducción a %s completada', 'kzmcito-ia-seo'), $lang_info['name']),
            'data' => $translation_data,
            'from_cache' => false
        ];
    }

    /**
     * Traducir contenido
     * 
     * @param string $content Content to translate
     * @param string $language Target language code
     * @param string $language_name Target language name
     * @return string Translated content
     */
    private function translate_content($content, $language, $language_name)
    {
        $prompt = "Traduce el siguiente contenido HTML al {$language_name}.\n\n";
        $prompt .= "IMPORTANTE:\n";
        $prompt .= "1. Mantén TODOS los tags HTML intactos\n";
        $prompt .= "2. NO traduzcas nombres propios, marcas o URLs\n";
        $prompt .= "3. Mantén la estructura y formato original\n";
        $prompt .= "4. Usa traducción semántica localizada (no literal)\n";
        $prompt .= "5. Devuelve SOLO el contenido traducido, sin explicaciones\n\n";
        $prompt .= "Contenido a traducir:\n{$content}\n\n";
        $prompt .= "Contenido traducido al {$language_name}:\n";

        $response = $this->api_client->generate_content($prompt, [
            'max_tokens' => 3000,
            'temperature' => 0.3,
        ]);

        if ($response && !empty($response['content'])) {
            return wp_kses_post($response['content']);
        }

        return $content;
    }

    /**
     * Traducir texto simple
     * 
     * @param string $text Text to translate
     * @param string $language Target language code
     * @param string $language_name Target language name
     * @return string Translated text
     */
    private function translate_text($text, $language, $language_name)
    {
        $prompt = "Traduce el siguiente texto al {$language_name}:\n\n";
        $prompt .= "{$text}\n\n";
        $prompt .= "Traducción al {$language_name}:\n";

        $response = $this->api_client->generate_content($prompt, [
            'max_tokens' => 200,
            'temperature' => 0.3,
        ]);

        if ($response && !empty($response['content'])) {
            return sanitize_text_field(trim($response['content']));
        }

        return $text;
    }

    /**
     * Obtener idiomas activos
     * 
     * @return array Active languages
     */
    public function get_active_languages()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kzmcito_languages';

        $languages = $wpdb->get_results(
            "SELECT * FROM {$table_name} WHERE is_active = 1 ORDER BY name ASC",
            ARRAY_A
        );

        return $languages ?: [];
    }

    /**
     * Obtener información de un idioma
     * 
     * @param string $code Language code
     * @return array|false Language info
     */
    public function get_language_info($code)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kzmcito_languages';

        $language = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table_name} WHERE code = %s", $code),
            ARRAY_A
        );

        return $language ?: false;
    }

    /**
     * Agregar idioma personalizado
     * 
     * @param string $code Language code
     * @param string $name Language name
     * @param string $native_name Native language name
     * @return bool Success
     */
    public function add_language($code, $name, $native_name)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kzmcito_languages';

        $result = $wpdb->insert(
            $table_name,
            [
                'code' => sanitize_text_field($code),
                'name' => sanitize_text_field($name),
                'native_name' => sanitize_text_field($native_name),
                'is_active' => 1
            ],
            ['%s', '%s', '%s', '%d']
        );

        return $result !== false;
    }

    /**
     * Actualizar idioma
     * 
     * @param int $id Language ID
     * @param array $data Language data
     * @return bool Success
     */
    public function update_language($id, $data)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kzmcito_languages';

        $update_data = [];
        if (isset($data['code'])) {
            $update_data['code'] = sanitize_text_field($data['code']);
        }
        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
        }
        if (isset($data['native_name'])) {
            $update_data['native_name'] = sanitize_text_field($data['native_name']);
        }
        if (isset($data['is_active'])) {
            $update_data['is_active'] = absint($data['is_active']);
        }

        $result = $wpdb->update(
            $table_name,
            $update_data,
            ['id' => $id],
            null,
            ['%d']
        );

        return $result !== false;
    }

    /**
     * Eliminar idioma
     * 
     * @param int $id Language ID
     * @return bool Success
     */
    public function delete_language($id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kzmcito_languages';

        $result = $wpdb->delete(
            $table_name,
            ['id' => $id],
            ['%d']
        );

        return $result !== false;
    }

    /**
     * Limpiar caché de traducciones de un post
     * 
     * @param int $post_id Post ID
     */
    public function clear_translation_cache($post_id)
    {
        delete_post_meta($post_id, 'kzmcito_translations_cache');
        delete_post_meta($post_id, '_kzmcito_available_languages');
        delete_post_meta($post_id, '_kzmcito_last_translated');
    }
}
