<?php
/**
 * Admin UI - Interfaz de administración
 * 
 * Gestión de prompts, configuración de modelos, idiomas
 * 
 * @package KzmcitoIASEO
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Kzmcito_IA_SEO_Admin_UI
{

    /**
     * Registrar menú de administración
     */
    public function register_menu()
    {
        add_menu_page(
            __('KzmCITO IA SEO', 'kzmcito-ia-seo'),
            __('KzmCITO IA', 'kzmcito-ia-seo'),
            'manage_options',
            'kzmcito-ia-seo',
            [$this, 'render_settings_page'],
            'dashicons-admin-generic',
            30
        );

        add_submenu_page(
            'kzmcito-ia-seo',
            __('Configuración', 'kzmcito-ia-seo'),
            __('Configuración', 'kzmcito-ia-seo'),
            'manage_options',
            'kzmcito-ia-seo',
            [$this, 'render_settings_page']
        );

        add_submenu_page(
            'kzmcito-ia-seo',
            __('Editor de Prompts', 'kzmcito-ia-seo'),
            __('Prompts', 'kzmcito-ia-seo'),
            'manage_options',
            'kzmcito-ia-seo-prompts',
            [$this, 'render_prompts_page']
        );

        add_submenu_page(
            'kzmcito-ia-seo',
            __('Gestión de Idiomas', 'kzmcito-ia-seo'),
            __('Idiomas', 'kzmcito-ia-seo'),
            'manage_options',
            'kzmcito-ia-seo-languages',
            [$this, 'render_languages_page']
        );

        add_submenu_page(
            'kzmcito-ia-seo',
            __('Estadísticas', 'kzmcito-ia-seo'),
            __('Estadísticas', 'kzmcito-ia-seo'),
            'manage_options',
            'kzmcito-ia-seo-stats',
            [$this, 'render_stats_page']
        );
    }

    /**
     * Renderizar página de configuración
     */
    public function render_settings_page()
    {
        // Guardar configuración
        if (isset($_POST['kzmcito_save_settings'])) {
            check_admin_referer('kzmcito_settings');
            $this->save_settings();
            echo '<div class="notice notice-success"><p>' . __('Configuración guardada exitosamente', 'kzmcito-ia-seo') . '</p></div>';
        }

        // Probar conexión
        if (isset($_POST['kzmcito_test_connection'])) {
            check_admin_referer('kzmcito_settings');
            $api_client = new Kzmcito_IA_SEO_API_Client();
            $result = $api_client->test_connection();

            if ($result['success']) {
                echo '<div class="notice notice-success"><p>' . esc_html($result['message']) . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html($result['message']) . '</p></div>';
            }
        }

        ?>
        <div class="wrap">
            <h1>
                <?php _e('Configuración de KzmCITO IA SEO', 'kzmcito-ia-seo'); ?>
            </h1>

            <form method="post" action="">
                <?php wp_nonce_field('kzmcito_settings'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="kzmcito_ai_model">
                                <?php _e('Modelo de IA', 'kzmcito-ia-seo'); ?>
                            </label>
                        </th>
                        <td>
                            <select name="kzmcito_ai_model" id="kzmcito_ai_model" class="regular-text">
                                <option value="claude-sonnet" <?php selected(get_option('kzmcito_ai_model'), 'claude-sonnet'); ?>>Claude 3 Sonnet</option>
                                <option value="claude-opus" <?php selected(get_option('kzmcito_ai_model'), 'claude-opus'); ?>>
                                    Claude 3 Opus</option>
                                <option value="gemini-pro" <?php selected(get_option('kzmcito_ai_model'), 'gemini-pro'); ?>>
                                    Gemini 1.5 Pro</option>
                                <option value="gpt-4" <?php selected(get_option('kzmcito_ai_model'), 'gpt-4'); ?>>GPT-4 Turbo
                                </option>
                                <option value="gpt-3.5" <?php selected(get_option('kzmcito_ai_model'), 'gpt-3.5'); ?>>GPT-3.5
                                    Turbo</option>
                            </select>
                            <p class="description">
                                <?php _e('Selecciona el modelo de IA a utilizar', 'kzmcito-ia-seo'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="kzmcito_api_key_claude">
                                <?php _e('API Key - Claude', 'kzmcito-ia-seo'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="password" name="kzmcito_api_key_claude" id="kzmcito_api_key_claude"
                                value="<?php echo esc_attr(get_option('kzmcito_api_key_claude')); ?>" class="regular-text">
                            <p class="description">
                                <?php _e('API Key de Anthropic (Claude)', 'kzmcito-ia-seo'); ?><br>
                                <a href="https://console.anthropic.com/settings/keys" target="_blank" rel="noopener">
                                    <?php _e('→ Obtener API Key de Claude', 'kzmcito-ia-seo'); ?>
                                </a>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="kzmcito_api_key_gemini">
                                <?php _e('API Key - Gemini', 'kzmcito-ia-seo'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="password" name="kzmcito_api_key_gemini" id="kzmcito_api_key_gemini"
                                value="<?php echo esc_attr(get_option('kzmcito_api_key_gemini')); ?>" class="regular-text">
                            <p class="description">
                                <?php _e('API Key de Google (Gemini)', 'kzmcito-ia-seo'); ?><br>
                                <a href="https://makersuite.google.com/app/apikey" target="_blank" rel="noopener">
                                    <?php _e('→ Obtener API Key de Gemini', 'kzmcito-ia-seo'); ?>
                                </a>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="kzmcito_api_key_openai">
                                <?php _e('API Key - OpenAI', 'kzmcito-ia-seo'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="password" name="kzmcito_api_key_openai" id="kzmcito_api_key_openai"
                                value="<?php echo esc_attr(get_option('kzmcito_api_key_openai')); ?>" class="regular-text">
                            <p class="description">
                                <?php _e('API Key de OpenAI (GPT)', 'kzmcito-ia-seo'); ?><br>
                                <a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener">
                                    <?php _e('→ Obtener API Key de OpenAI', 'kzmcito-ia-seo'); ?>
                                </a>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="kzmcito_auto_process">
                                <?php _e('Procesamiento Automático', 'kzmcito-ia-seo'); ?>
                            </label>
                        </th>
                        <td>
                            <select name="kzmcito_auto_process" id="kzmcito_auto_process">
                                <option value="yes" <?php selected(get_option('kzmcito_auto_process'), 'yes'); ?>>
                                    <?php _e('Sí', 'kzmcito-ia-seo'); ?>
                                </option>
                                <option value="no" <?php selected(get_option('kzmcito_auto_process'), 'no'); ?>>
                                    <?php _e('No', 'kzmcito-ia-seo'); ?>
                                </option>
                            </select>
                            <p class="description">
                                <?php _e('Procesar automáticamente al guardar posts', 'kzmcito-ia-seo'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="kzmcito_min_words">
                                <?php _e('Palabras Mínimas', 'kzmcito-ia-seo'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="number" name="kzmcito_min_words" id="kzmcito_min_words"
                                value="<?php echo esc_attr(get_option('kzmcito_min_words', 850)); ?>" class="small-text">
                            <p class="description">
                                <?php _e('Mínimo de palabras para expansión de contenido', 'kzmcito-ia-seo'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="kzmcito_max_words">
                                <?php _e('Palabras Máximas', 'kzmcito-ia-seo'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="number" name="kzmcito_max_words" id="kzmcito_max_words"
                                value="<?php echo esc_attr(get_option('kzmcito_max_words', 1200)); ?>" class="small-text">
                            <p class="description">
                                <?php _e('Máximo de palabras para expansión de contenido', 'kzmcito-ia-seo'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="kzmcito_enable_toc">
                                <?php _e('Habilitar TOC', 'kzmcito-ia-seo'); ?>
                            </label>
                        </th>
                        <td>
                            <select name="kzmcito_enable_toc" id="kzmcito_enable_toc">
                                <option value="yes" <?php selected(get_option('kzmcito_enable_toc'), 'yes'); ?>>
                                    <?php _e('Sí', 'kzmcito-ia-seo'); ?>
                                </option>
                                <option value="no" <?php selected(get_option('kzmcito_enable_toc'), 'no'); ?>>
                                    <?php _e('No', 'kzmcito-ia-seo'); ?>
                                </option>
                            </select>
                            <p class="description">
                                <?php _e('Insertar tabla de contenidos automáticamente', 'kzmcito-ia-seo'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="kzmcito_enable_faq">
                                <?php _e('Habilitar FAQ', 'kzmcito-ia-seo'); ?>
                            </label>
                        </th>
                        <td>
                            <select name="kzmcito_enable_faq" id="kzmcito_enable_faq">
                                <option value="yes" <?php selected(get_option('kzmcito_enable_faq'), 'yes'); ?>>
                                    <?php _e('Sí', 'kzmcito-ia-seo'); ?>
                                </option>
                                <option value="no" <?php selected(get_option('kzmcito_enable_faq'), 'no'); ?>>
                                    <?php _e('No', 'kzmcito-ia-seo'); ?>
                                </option>
                            </select>
                            <p class="description">
                                <?php _e('Insertar FAQ con Schema JSON-LD automáticamente', 'kzmcito-ia-seo'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" name="kzmcito_save_settings" class="button button-primary">
                        <?php _e('Guardar Configuración', 'kzmcito-ia-seo'); ?>
                    </button>
                    <button type="submit" name="kzmcito_test_connection" class="button button-secondary">
                        <?php _e('Probar Conexión', 'kzmcito-ia-seo'); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Renderizar página de prompts
     */
    public function render_prompts_page()
    {
        $prompt_manager = new Kzmcito_IA_SEO_Prompt_Manager();

        // Guardar prompt
        if (isset($_POST['kzmcito_save_prompt'])) {
            check_admin_referer('kzmcito_prompts');
            $category = sanitize_text_field($_POST['prompt_category']);
            $content = wp_kses_post($_POST['prompt_content']);

            if ($prompt_manager->save_prompt($category, $content)) {
                echo '<div class="notice notice-success"><p>' . __('Prompt guardado exitosamente', 'kzmcito-ia-seo') . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . __('Error al guardar el prompt', 'kzmcito-ia-seo') . '</p></div>';
            }
        }

        $prompts = $prompt_manager->get_available_prompts();
        $current_category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : 'global';
        $current_prompt = $prompts[$current_category] ?? $prompts['global'];

        ?>
        <div class="wrap">
            <h1>
                <?php _e('Editor de Prompts', 'kzmcito-ia-seo'); ?>
            </h1>

            <div class="kzmcito-prompts-editor">
                <div class="kzmcito-prompts-sidebar">
                    <h3>
                        <?php _e('Categorías', 'kzmcito-ia-seo'); ?>
                    </h3>
                    <ul>
                        <?php foreach ($prompts as $slug => $data): ?>
                            <li>
                                <a href="?page=kzmcito-ia-seo-prompts&category=<?php echo esc_attr($slug); ?>"
                                    class="<?php echo $current_category === $slug ? 'active' : ''; ?>">
                                    <?php echo esc_html($data['name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="kzmcito-prompts-content">
                    <h2>
                        <?php echo esc_html($current_prompt['name']); ?>
                    </h2>

                    <form method="post" action="">
                        <?php wp_nonce_field('kzmcito_prompts'); ?>
                        <input type="hidden" name="prompt_category" value="<?php echo esc_attr($current_category); ?>">

                        <textarea name="prompt_content" id="prompt_content" rows="20"
                            class="large-text code"><?php echo esc_textarea($current_prompt['content']); ?></textarea>

                        <p class="description">
                            <?php _e('Archivo:', 'kzmcito-ia-seo'); ?>
                            <code><?php echo esc_html($current_prompt['file']); ?></code>
                        </p>

                        <p class="submit">
                            <button type="submit" name="kzmcito_save_prompt" class="button button-primary">
                                <?php _e('Guardar Prompt', 'kzmcito-ia-seo'); ?>
                            </button>
                        </p>
                    </form>
                </div>
            </div>

            <style>
                .kzmcito-prompts-editor {
                    display: flex;
                    gap: 20px;
                    margin-top: 20px;
                }

                .kzmcito-prompts-sidebar {
                    flex: 0 0 200px;
                    background: #fff;
                    padding: 15px;
                    border: 1px solid #ccd0d4;
                }

                .kzmcito-prompts-sidebar ul {
                    margin: 0;
                    padding: 0;
                    list-style: none;
                }

                .kzmcito-prompts-sidebar li {
                    margin: 5px 0;
                }

                .kzmcito-prompts-sidebar a {
                    display: block;
                    padding: 8px 12px;
                    text-decoration: none;
                    border-radius: 3px;
                }

                .kzmcito-prompts-sidebar a:hover {
                    background: #f0f0f1;
                }

                .kzmcito-prompts-sidebar a.active {
                    background: #2271b1;
                    color: #fff;
                }

                .kzmcito-prompts-content {
                    flex: 1;
                    background: #fff;
                    padding: 20px;
                    border: 1px solid #ccd0d4;
                }
            </style>
        </div>
        <?php
    }

    /**
     * Renderizar página de idiomas
     */
    public function render_languages_page()
    {
        $translation_manager = new Kzmcito_IA_SEO_Translation_Manager();

        // Procesar acciones CRUD
        if (isset($_POST['kzmcito_add_language'])) {
            check_admin_referer('kzmcito_languages');
            $code = sanitize_text_field($_POST['lang_code']);
            $name = sanitize_text_field($_POST['lang_name']);
            $native_name = sanitize_text_field($_POST['lang_native_name']);

            if ($translation_manager->add_language($code, $name, $native_name)) {
                echo '<div class="notice notice-success"><p>' . __('Idioma agregado exitosamente', 'kzmcito-ia-seo') . '</p></div>';
            }
        }

        if (isset($_POST['kzmcito_update_language'])) {
            check_admin_referer('kzmcito_languages');
            $id = intval($_POST['lang_id']);
            $data = [
                'is_active' => isset($_POST['lang_active']) ? 1 : 0,
            ];

            if ($translation_manager->update_language($id, $data)) {
                echo '<div class="notice notice-success"><p>' . __('Idioma actualizado exitosamente', 'kzmcito-ia-seo') . '</p></div>';
            }
        }

        $languages = $translation_manager->get_active_languages();

        ?>
        <div class="wrap">
            <h1>
                <?php _e('Gestión de Idiomas', 'kzmcito-ia-seo'); ?>
            </h1>

            <h2>
                <?php _e('Idiomas Activos', 'kzmcito-ia-seo'); ?>
            </h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>
                            <?php _e('Código', 'kzmcito-ia-seo'); ?>
                        </th>
                        <th>
                            <?php _e('Nombre', 'kzmcito-ia-seo'); ?>
                        </th>
                        <th>
                            <?php _e('Nombre Nativo', 'kzmcito-ia-seo'); ?>
                        </th>
                        <th>
                            <?php _e('Estado', 'kzmcito-ia-seo'); ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($languages as $lang): ?>
                        <tr>
                            <td><code><?php echo esc_html($lang['code']); ?></code></td>
                            <td>
                                <?php echo esc_html($lang['name']); ?>
                            </td>
                            <td>
                                <?php echo esc_html($lang['native_name']); ?>
                            </td>
                            <td>
                                <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                                <?php _e('Activo', 'kzmcito-ia-seo'); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2>
                <?php _e('Agregar Nuevo Idioma', 'kzmcito-ia-seo'); ?>
            </h2>
            <form method="post" action="">
                <?php wp_nonce_field('kzmcito_languages'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="lang_code">
                                <?php _e('Código', 'kzmcito-ia-seo'); ?>
                            </label></th>
                        <td><input type="text" name="lang_code" id="lang_code" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="lang_name">
                                <?php _e('Nombre', 'kzmcito-ia-seo'); ?>
                            </label></th>
                        <td><input type="text" name="lang_name" id="lang_name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="lang_native_name">
                                <?php _e('Nombre Nativo', 'kzmcito-ia-seo'); ?>
                            </label></th>
                        <td><input type="text" name="lang_native_name" id="lang_native_name" class="regular-text" required></td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" name="kzmcito_add_language" class="button button-primary">
                        <?php _e('Agregar Idioma', 'kzmcito-ia-seo'); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Renderizar página de estadísticas
     */
    public function render_stats_page()
    {
        ?>
        <div class="wrap">
            <h1>
                <?php _e('Estadísticas de KzmCITO IA SEO', 'kzmcito-ia-seo'); ?>
            </h1>

            <div class="kzmcito-stats-grid">
                <div class="kzmcito-stat-box">
                    <h3>
                        <?php _e('Posts Procesados', 'kzmcito-ia-seo'); ?>
                    </h3>
                    <p class="kzmcito-stat-number">
                        <?php echo $this->get_processed_posts_count(); ?>
                    </p>
                </div>

                <div class="kzmcito-stat-box">
                    <h3>
                        <?php _e('Traducciones Generadas', 'kzmcito-ia-seo'); ?>
                    </h3>
                    <p class="kzmcito-stat-number">
                        <?php echo $this->get_translations_count(); ?>
                    </p>
                </div>

                <div class="kzmcito-stat-box">
                    <h3>
                        <?php _e('Modo Fallback', 'kzmcito-ia-seo'); ?>
                    </h3>
                    <p class="kzmcito-stat-number">
                        <?php echo get_option('kzmcito_fallback_count', 0); ?>
                    </p>
                </div>
            </div>

            <style>
                .kzmcito-stats-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                    gap: 20px;
                    margin-top: 20px;
                }

                .kzmcito-stat-box {
                    background: #fff;
                    padding: 20px;
                    border: 1px solid #ccd0d4;
                    border-radius: 4px;
                }

                .kzmcito-stat-number {
                    font-size: 48px;
                    font-weight: bold;
                    color: #2271b1;
                    margin: 10px 0 0 0;
                }
            </style>
        </div>
        <?php
    }

    /**
     * Guardar configuración
     */
    private function save_settings()
    {
        $options = [
            'kzmcito_ai_model',
            'kzmcito_api_key_claude',
            'kzmcito_api_key_gemini',
            'kzmcito_api_key_openai',
            'kzmcito_auto_process',
            'kzmcito_min_words',
            'kzmcito_max_words',
            'kzmcito_enable_toc',
            'kzmcito_enable_faq',
        ];

        foreach ($options as $option) {
            if (isset($_POST[$option])) {
                update_option($option, sanitize_text_field($_POST[$option]));
            }
        }
    }

    /**
     * Obtener cantidad de posts procesados
     */
    private function get_processed_posts_count()
    {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_kzmcito_last_processed'");
    }

    /**
     * Obtener cantidad de traducciones
     */
    private function get_translations_count()
    {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'kzmcito_translations_cache'");
    }
}
