<?php
/**
 * Language Detector - Detección automática de idioma del usuario
 * 
 * Sirve contenido traducido desde caché según idioma del navegador
 * Transparente para Google (siempre ve español)
 * 
 * @package KzmcitoIASEO
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Kzmcito_IA_SEO_Language_Detector
{

    /**
     * Idioma por defecto (español)
     */
    private $default_language = 'es';

    /**
     * Cookie name para preferencia de idioma
     */
    private $cookie_name = 'kzmcito_user_language';

    /**
     * Translation Manager instance
     */
    private $translation_manager;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->translation_manager = new Kzmcito_IA_SEO_Translation_Manager();
    }

    /**
     * Detectar idioma del usuario
     * 
     * @return string Language code (es, en, pt, etc.)
     */
    public function detect_user_language()
    {
        // 1. Si es bot de Google, siempre español
        if ($this->is_search_bot()) {
            return $this->default_language;
        }

        // 2. Si hay cookie de preferencia, usarla
        if (isset($_COOKIE[$this->cookie_name])) {
            $lang = sanitize_text_field($_COOKIE[$this->cookie_name]);
            if ($this->is_valid_language($lang)) {
                return $lang;
            }
        }

        // 3. Detectar desde navegador
        $browser_lang = $this->detect_browser_language();
        if ($browser_lang && $this->is_valid_language($browser_lang)) {
            return $browser_lang;
        }

        // 4. Fallback a español
        return $this->default_language;
    }

    /**
     * Detectar si es un bot de búsqueda
     * 
     * @return bool
     */
    private function is_search_bot()
    {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            return false;
        }

        $user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);

        $bots = [
            'googlebot',
            'bingbot',
            'slurp',        // Yahoo
            'duckduckbot',
            'baiduspider',
            'yandexbot',
            'facebot',      // Facebook
            'ia_archiver',  // Alexa
            'msnbot',
            'teoma',
        ];

        foreach ($bots as $bot) {
            if (strpos($user_agent, $bot) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detectar idioma del navegador
     * 
     * @return string|false Language code or false
     */
    private function detect_browser_language()
    {
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return false;
        }

        $accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];

        // Parsear Accept-Language header
        // Ejemplo: "en-US,en;q=0.9,es;q=0.8,pt;q=0.7"
        preg_match_all('/([a-z]{2})(?:-[A-Z]{2})?(?:;q=([0-9.]+))?/', $accept_language, $matches);

        if (empty($matches[1])) {
            return false;
        }

        // Obtener idiomas con sus prioridades
        $languages = [];
        foreach ($matches[1] as $index => $lang) {
            $priority = isset($matches[2][$index]) && $matches[2][$index] !== ''
                ? floatval($matches[2][$index])
                : 1.0;
            $languages[$lang] = $priority;
        }

        // Ordenar por prioridad (mayor a menor)
        arsort($languages);

        // Retornar el idioma con mayor prioridad que esté disponible
        foreach ($languages as $lang => $priority) {
            if ($this->is_valid_language($lang)) {
                return $lang;
            }
        }

        return false;
    }

    /**
     * Verificar si un idioma es válido (está activo)
     * 
     * @param string $lang Language code
     * @return bool
     */
    private function is_valid_language($lang)
    {
        $active_languages = $this->translation_manager->get_active_languages();

        foreach ($active_languages as $language) {
            if ($language['code'] === $lang) {
                return true;
            }
        }

        // Español siempre es válido
        return $lang === $this->default_language;
    }

    /**
     * Obtener contenido traducido si existe
     * 
     * @param string $content Original content
     * @param int $post_id Post ID
     * @param string $lang Language code
     * @return string Translated content or original
     */
    public function get_translated_content($content, $post_id, $lang)
    {
        // Si es español (default), retornar original
        if ($lang === $this->default_language) {
            return $content;
        }

        // Obtener traducciones desde caché
        $translations = get_post_meta($post_id, 'kzmcito_translations_cache', true);

        if (!is_array($translations) || !isset($translations[$lang])) {
            return $content;
        }

        // Retornar contenido traducido
        return $translations[$lang]['content'];
    }

    /**
     * Obtener título traducido si existe
     * 
     * @param string $title Original title
     * @param int $post_id Post ID
     * @param string $lang Language code
     * @return string Translated title or original
     */
    public function get_translated_title($title, $post_id, $lang)
    {
        // Si es español (default), retornar original
        if ($lang === $this->default_language) {
            return $title;
        }

        // Obtener traducciones desde caché
        $translations = get_post_meta($post_id, 'kzmcito_translations_cache', true);

        if (!is_array($translations) || !isset($translations[$lang])) {
            return $title;
        }

        // Retornar título traducido
        return $translations[$lang]['title'];
    }

    /**
     * Establecer cookie de preferencia de idioma
     * 
     * @param string $lang Language code
     */
    public function set_language_preference($lang)
    {
        if (!$this->is_valid_language($lang)) {
            return;
        }

        // Cookie por 30 días
        $expire = time() + (30 * DAY_IN_SECONDS);

        setcookie(
            $this->cookie_name,
            $lang,
            $expire,
            COOKIEPATH,
            COOKIE_DOMAIN,
            is_ssl(),
            true // httponly
        );
    }

    /**
     * Obtener nombre del idioma actual
     * 
     * @param string $lang Language code
     * @return string Language name
     */
    public function get_language_name($lang)
    {
        $language_info = $this->translation_manager->get_language_info($lang);

        if ($language_info) {
            return $language_info['native_name'];
        }

        return 'Español'; // Default
    }

    /**
     * Obtener todos los idiomas disponibles para un post
     * 
     * @param int $post_id Post ID
     * @return array Available languages
     */
    public function get_available_languages_for_post($post_id)
    {
        $available = get_post_meta($post_id, '_kzmcito_available_languages', true);

        if (!is_array($available)) {
            return [$this->default_language];
        }

        // Siempre incluir español
        if (!in_array($this->default_language, $available)) {
            array_unshift($available, $this->default_language);
        }

        return $available;
    }

    /**
     * Renderizar selector de idioma (opcional)
     * 
     * @param int $post_id Post ID
     * @return string HTML del selector
     */
    public function render_language_selector($post_id)
    {
        $current_lang = $this->detect_user_language();
        $available_languages = $this->get_available_languages_for_post($post_id);

        if (count($available_languages) <= 1) {
            return ''; // No hay traducciones
        }

        $html = '<div class="kzmcito-language-selector">';
        $html .= '<select id="kzmcito-lang-select" onchange="kzmcitoChangeLang(this.value)">';

        foreach ($available_languages as $lang) {
            $lang_name = $this->get_language_name($lang);
            $selected = $lang === $current_lang ? ' selected' : '';
            $html .= sprintf(
                '<option value="%s"%s>%s</option>',
                esc_attr($lang),
                $selected,
                esc_html($lang_name)
            );
        }

        $html .= '</select>';
        $html .= '</div>';

        // JavaScript para cambiar idioma
        $html .= '<script>
        function kzmcitoChangeLang(lang) {
            document.cookie = "' . $this->cookie_name . '=" + lang + "; path=/; max-age=' . (30 * DAY_IN_SECONDS) . '";
            location.reload();
        }
        </script>';

        return $html;
    }
}
