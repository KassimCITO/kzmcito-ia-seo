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
    public function get_browser_preferred_languages()
    {
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return [];
        }

        $accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        preg_match_all('/([a-z]{2})(?:-[A-Z]{2})?(?:;q=([0-9.]+))?/', $accept_language, $matches);

        if (empty($matches[1])) {
            return [];
        }

        $languages = [];
        foreach ($matches[1] as $index => $lang) {
            $priority = isset($matches[2][$index]) && $matches[2][$index] !== ''
                ? floatval($matches[2][$index])
                : 1.0;
            $languages[$lang] = $priority;
        }

        arsort($languages);
        return array_keys($languages);
    }

    /**
     * Detectar idioma del navegador (solo si es válido/soportado)
     * 
     * @return string|false Language code or false
     */
    private function detect_browser_language()
    {
        $preferred = $this->get_browser_preferred_languages();

        foreach ($preferred as $lang) {
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

        // SI NO EXISTE CACHÉ, GENERAR JIT (Just-In-Time)
        if (!is_array($translations) || !isset($translations[$lang])) {
            $this->trigger_jit_process($post_id, $lang);
            
            // Volver a obtener de caché después del proceso JIT
            $translations = get_post_meta($post_id, 'kzmcito_translations_cache', true);
            
            if (!is_array($translations) || !isset($translations[$lang])) {
                return $content; // Fallback final si falló JIT
            }
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

        // SI NO EXISTE CACHÉ, GENERAR JIT (Just-In-Time)
        if (!is_array($translations) || !isset($translations[$lang])) {
            $this->trigger_jit_process($post_id, $lang);
            
            // Volver a obtener de caché
            $translations = get_post_meta($post_id, 'kzmcito_translations_cache', true);
            
            if (!is_array($translations) || !isset($translations[$lang])) {
                return $title;
            }
        }

        // Retornar título traducido
        return $translations[$lang]['title'];
    }

    /**
     * Disparar el proceso de generación Just-In-Time
     * 
     * @param int $post_id Post ID
     * @param string $lang Target language
     */
    private function trigger_jit_process($post_id, $lang)
    {
        // Solo disparar si el idioma es válido
        if (!$this->is_valid_language($lang)) {
            return;
        }

        // 1. Asegurar que el post original esté procesado (Fases 1-3)
        $core = kzmcito_ia_seo()->get_core();
        $processed = $core->ensure_post_is_processed($post_id);

        if ($processed) {
            // 2. Generar traducción para este idioma específico
            $this->translation_manager->translate_post($post_id, $lang);
        }
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

        // Cookie por 365 días
        $expire = time() + (365 * DAY_IN_SECONDS);

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
            if (window.kzmcitoTrackEvent) {
                window.kzmcitoTrackEvent("translation_interaction", {
                    "language": lang,
                    "interaction_type": "manual_select",
                    "previous_language": "' . esc_js($current_lang) . '"
                });
            }
            document.cookie = "' . $this->cookie_name . '=" + lang + "; path=/; max-age=' . (365 * DAY_IN_SECONDS) . '; SameSite=Lax" + (window.location.protocol === "https:" ? "; Secure" : "");
            setTimeout(function() { location.reload(); }, 150);
        }
        </script>';

        return $html;
    }

    /**
     * Renderizar cuadro flotante de cambio de idioma
     * 
     * @param int|null $post_id Post ID
     */
    public function render_language_floating_box($post_id = null)
    {
        if (!$post_id) {
            $post_id = get_the_ID();
        }

        if (!$post_id || !is_singular()) {
            return;
        }

        // Si el usuario cerró el cuadro manualmente, no mostrarlo esta sesión
        if (isset($_COOKIE['kzmcito_hide_lang_box']) && $_COOKIE['kzmcito_hide_lang_box'] === '1') {
            return;
        }

        if ($this->is_search_bot()) {
            return;
        }

        $current_lang = $this->detect_user_language();
        $browser_langs = $this->get_browser_preferred_languages();
        $primary_browser_lang = !empty($browser_langs) ? $browser_langs[0] : 'unknown';
        $available_languages = $this->get_available_languages_for_post($post_id);
        $ga4_id = get_option('kzmcito_ga4_measurement_id');

        // Determinar si debemos mostrar el cuadro flotante
        $show_box = false;
        $box_text = '';
        $target_lang = '';

        if ($current_lang !== $this->default_language) {
            // Caso: Viendo traducción, ofrecer volver al original
            $show_box = true;
            $box_text = $this->get_ui_text('read_original', $current_lang);
            $target_lang = $this->default_language;
        } else {
            // Caso: En español, ver si el idioma del navegador tiene traducción
            $supported_browser_lang = $this->detect_browser_language();
            if ($supported_browser_lang && $supported_browser_lang !== $this->default_language && in_array($supported_browser_lang, $available_languages)) {
                $show_box = true;
                $box_text = $this->get_ui_text('read_in', $supported_browser_lang);
                $target_lang = $supported_browser_lang;
            }
        }

        // 1. Script de rastreo (GA4) - Se ejecuta para todos los usuarios
        ?>
        <script>
            (function() {
                const gaId = '<?php echo esc_js($ga4_id); ?>';
                const currentLang = '<?php echo esc_js($current_lang); ?>';
                const browserLang = '<?php echo esc_js($primary_browser_lang); ?>';
                const supportedLangs = <?php echo json_encode(array_column($this->translation_manager->get_active_languages(), 'code')); ?>;
                const isSupported = supportedLangs.includes(browserLang) || browserLang === 'es' || browserLang === 'unknown';

                function trackEvent(name, params) {
                    if (typeof gtag === 'function') {
                        gtag('event', name, params);
                    } else if (gaId) {
                        if (!window.kzmcito_ga_loaded) {
                            const script = document.createElement('script');
                            script.src = 'https://www.googletagmanager.com/gtag/js?id=' + gaId;
                            script.async = true;
                            document.head.appendChild(script);
                            window.dataLayer = window.dataLayer || [];
                            window.gtag = function(){dataLayer.push(arguments);}
                            gtag('js', new Date());
                            gtag('config', gaId);
                            window.kzmcito_ga_loaded = true;
                        }
                        gtag('event', name, params);
                    }
                }

                // Track inicial: Detección y solicitud de idioma
                trackEvent('translation_interaction', {
                    'language': currentLang,
                    'browser_language': browserLang,
                    'status': isSupported ? 'supported' : 'unsupported',
                    'interaction_type': 'page_load',
                    'is_translation': currentLang !== 'es'
                });

                // Exportar para uso en clicks
                window.kzmcitoTrackEvent = trackEvent;
            })();
        </script>
        <?php

        if (!$show_box) {
            return;
        }

        // 2. Renderizar UI del cuadro flotante
        ?>
        <div id="kzmcito-lang-box" class="kzmcito-premium-box">
            <div class="kzmcito-lang-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
            </div>
            <span class="kzmcito-lang-text"><?php echo esc_html($box_text); ?></span>
            <div id="kzmcito-lang-close" class="kzmcito-close-icon" title="<?php esc_attr_e('Cerrar', 'kzmcito-ia-seo'); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </div>
        </div>

        <style>
            #kzmcito-lang-box {
                position: fixed;
                bottom: 30px;
                left: 30px;
                z-index: 999999;
                padding: 10px 20px;
                background: rgba(255, 255, 255, 0.7);
                backdrop-filter: blur(15px);
                -webkit-backdrop-filter: blur(15px);
                border: 1px solid rgba(255, 255, 255, 0.4);
                border-radius: 16px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
                cursor: pointer;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                font-size: 14px;
                font-weight: 600;
                color: #1d1d1f;
                display: flex;
                align-items: center;
                gap: 12px;
                transition: all 0.5s cubic-bezier(0.19, 1, 0.22, 1);
                user-select: none;
                opacity: 1;
                transform: translateY(0);
            }
            #kzmcito-lang-box:hover {
                transform: translateY(-5px);
                background: rgba(255, 255, 255, 0.9);
                box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
                border-color: rgba(255, 255, 255, 0.6);
            }
            #kzmcito-lang-box.hidden {
                opacity: 0;
                visibility: hidden;
                transform: translateY(40px);
            }
            .kzmcito-lang-icon { color: #0071e3; display: flex; align-items: center; }
            .kzmcito-close-icon {
                color: #86868b;
                display: flex;
                align-items: center;
                padding: 4px;
                border-radius: 50%;
                transition: background 0.2s ease;
                margin-left: -4px;
            }
            .kzmcito-close-icon:hover {
                background: rgba(0,0,0,0.05);
                color: #1d1d1f;
            }
            @media (max-width: 768px) {
                #kzmcito-lang-box { bottom: 20px; left: 20px; right: 20px; justify-content: space-between; }
            }
        </style>

        <script>
            (function() {
                const box = document.getElementById('kzmcito-lang-box');
                const targetLang = '<?php echo esc_js($target_lang); ?>';
                const currentLang = '<?php echo esc_js($current_lang); ?>';
                const cookieName = '<?php echo esc_js($this->cookie_name); ?>';
                const expiry = 365 * 24 * 60 * 60;

                if (!box) return;

                const closeBtn = document.getElementById('kzmcito-lang-close');

                box.addEventListener('click', function(e) {
                    // Si clicamos en cerrar, no disparamos la traducción
                    if (e.target.closest('#kzmcito-lang-close')) return;

                    if (window.kzmcitoTrackEvent) {
                        window.kzmcitoTrackEvent('translation_interaction', {
                            'language': targetLang,
                            'interaction_type': 'manual_switch',
                            'previous_language': currentLang
                        });
                    }
                    
                    document.cookie = cookieName + "=" + targetLang + "; path=/; max-age=" + expiry + "; SameSite=Lax" + (window.location.protocol === 'https:' ? '; Secure' : '');
                    box.style.opacity = '0.5';
                    box.style.pointerEvents = 'none';
                    setTimeout(() => location.reload(), 150);
                });

                if (closeBtn) {
                    closeBtn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        // Ocultar permanentemente en esta sesión
                        document.cookie = "kzmcito_hide_lang_box=1; path=/; SameSite=Lax" + (window.location.protocol === 'https:' ? '; Secure' : '');
                        box.classList.add('hidden');
                        
                        if (window.kzmcitoTrackEvent) {
                            window.kzmcitoTrackEvent('translation_interaction', {
                                'language': currentLang,
                                'interaction_type': 'dismiss_box'
                            });
                        }
                    });
                }

                window.addEventListener('scroll', function() {
                    if (window.scrollY > 100) box.classList.add('hidden');
                    else box.classList.remove('hidden');
                }, { passive: true });
            })();
        </script>
        <?php
    }

    /**
     * Obtener texto de la interfaz según el idioma
     */
    private function get_ui_text($key, $lang)
    {
        $texts = [
            'read_in' => [
                'en' => 'Read in English',
                'pt' => 'Ler em Português',
                'fr' => 'Lire en Français',
                'de' => 'Auf Deutsch lesen',
                'ru' => 'Читать на русском',
                'hi' => 'हिंदी में पढ़ें',
                'zh' => '用简体中文阅读',
                'es' => 'Leer en Español'
            ],
            'read_original' => [
                'en' => 'Read original',
                'pt' => 'Ler original',
                'fr' => 'Lire l\'original',
                'de' => 'Original lesen',
                'ru' => 'Читать оригинал',
                'hi' => 'मूल पढ़ें',
                'zh' => '阅读简体中文原文',
                'es' => 'Leer original'
            ]
        ];

        // Determinar idioma para el texto
        $ui_lang = $lang;
        if (!isset($texts[$key][$ui_lang])) {
            $ui_lang = 'en'; // Fallback a inglés
        }

        return $texts[$key][$ui_lang];
    }
}
