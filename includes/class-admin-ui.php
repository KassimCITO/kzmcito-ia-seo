<?php
/**
 * Admin UI - Interfaz de administración
 * 
 * Gestión de prompts dinámicos, categorías, configuración de modelos,
 * idiomas, fallback de APIs y sumario Reuters
 * 
 * @package KzmcitoIASEO
 * @since 3.0.0
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
            __('Categorías y Prompts', 'kzmcito-ia-seo'),
            __('Categorías', 'kzmcito-ia-seo'),
            'manage_options',
            'kzmcito-ia-seo-categories',
            [$this, 'render_categories_page']
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
     * ==========================================
     * CONFIGURACIÓN PRINCIPAL
     * ==========================================
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
                $extra = '';
                if (!empty($result['fallback_used']) && $result['fallback_used']) {
                    $extra = ' <em>(' . __('usando fallback', 'kzmcito-ia-seo') . ')</em>';
                }
                echo '<div class="notice notice-success"><p>' . esc_html($result['message']) . $extra . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html($result['message']) . '</p></div>';
            }
        }

        ?>
        <div class="wrap">
            <h1>
                <?php _e('Configuración de KzmCITO IA SEO', 'kzmcito-ia-seo'); ?>
                <span class="kzmcito-version-badge">v<?php echo KZMCITO_IA_SEO_VERSION; ?></span>
            </h1>

            <form method="post" action="">
                <?php wp_nonce_field('kzmcito_settings'); ?>

                <!-- ===== SECCIÓN: MODELO IA ===== -->
                <h2 class="kzmcito-section-title">
                    <span class="dashicons dashicons-cloud"></span>
                    <?php _e('Modelo de IA Principal', 'kzmcito-ia-seo'); ?>
                </h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="kzmcito_ai_model">
                                <?php _e('Modelo Primario', 'kzmcito-ia-seo'); ?>
                            </label>
                        </th>
                        <td>
                            <select name="kzmcito_ai_model" id="kzmcito_ai_model" class="regular-text">
                                <optgroup label="Anthropic">
                                    <option value="claude-sonnet" <?php selected(get_option('kzmcito_ai_model'), 'claude-sonnet'); ?>>Claude 3.5 Sonnet</option>
                                    <option value="claude-opus" <?php selected(get_option('kzmcito_ai_model'), 'claude-opus'); ?>>Claude 3 Opus</option>
                                    <option value="claude-haiku" <?php selected(get_option('kzmcito_ai_model'), 'claude-haiku'); ?>>Claude 3 Haiku</option>
                                </optgroup>
                                <optgroup label="Google">
                                    <option value="gemini-pro" <?php selected(get_option('kzmcito_ai_model'), 'gemini-pro'); ?>>Gemini 2.0 Flash</option>
                                </optgroup>
                                <optgroup label="OpenAI">
                                    <option value="gpt-4" <?php selected(get_option('kzmcito_ai_model'), 'gpt-4'); ?>>GPT-4 Turbo</option>
                                    <option value="gpt-4o" <?php selected(get_option('kzmcito_ai_model'), 'gpt-4o'); ?>>GPT-4o</option>
                                    <option value="gpt-4o-mini" <?php selected(get_option('kzmcito_ai_model'), 'gpt-4o-mini'); ?>>GPT-4o Mini</option>
                                    <option value="gpt-3.5" <?php selected(get_option('kzmcito_ai_model'), 'gpt-3.5'); ?>>GPT-3.5 Turbo</option>
                                </optgroup>
                                <optgroup label="DeepSeek">
                                    <option value="deepseek-chat" <?php selected(get_option('kzmcito_ai_model'), 'deepseek-chat'); ?>>DeepSeek Chat</option>
                                </optgroup>
                                <optgroup label="Mistral">
                                    <option value="mistral-large" <?php selected(get_option('kzmcito_ai_model'), 'mistral-large'); ?>>Mistral Large</option>
                                </optgroup>
                                <optgroup label="Groq">
                                    <option value="groq-llama" <?php selected(get_option('kzmcito_ai_model'), 'groq-llama'); ?>>LLaMA 3.3 70B (Groq)</option>
                                </optgroup>
                            </select>
                            <p class="description">
                                <?php _e('Si este modelo falla, se intentarán automáticamente los demás proveedores configurados (fallback en cascada).', 'kzmcito-ia-seo'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <!-- ===== SECCIÓN: API KEYS ===== -->
                <h2 class="kzmcito-section-title">
                    <span class="dashicons dashicons-admin-network"></span>
                    <?php _e('Claves API (Fallback en Cascada)', 'kzmcito-ia-seo'); ?>
                </h2>
                <p class="description" style="margin-bottom: 15px;">
                    <?php _e('Configura múltiples API keys. Si el modelo principal falla, se intentará automáticamente con los demás proveedores que tengan key configurada.', 'kzmcito-ia-seo'); ?>
                </p>
                <table class="form-table">
                    <?php
                    $api_configs = [
                        ['key' => 'kzmcito_api_key_claude', 'label' => 'Claude (Anthropic)', 'url' => 'https://console.anthropic.com/settings/keys', 'url_label' => 'Obtener API Key de Claude'],
                        ['key' => 'kzmcito_api_key_gemini', 'label' => 'Gemini (Google)', 'url' => 'https://makersuite.google.com/app/apikey', 'url_label' => 'Obtener API Key de Gemini'],
                        ['key' => 'kzmcito_api_key_openai', 'label' => 'OpenAI (GPT / Codex)', 'url' => 'https://platform.openai.com/api-keys', 'url_label' => 'Obtener API Key de OpenAI'],
                        ['key' => 'kzmcito_api_key_deepseek', 'label' => 'DeepSeek', 'url' => 'https://platform.deepseek.com/api_keys', 'url_label' => 'Obtener API Key de DeepSeek'],
                        ['key' => 'kzmcito_api_key_mistral', 'label' => 'Mistral AI', 'url' => 'https://console.mistral.ai/api-keys/', 'url_label' => 'Obtener API Key de Mistral'],
                        ['key' => 'kzmcito_api_key_groq', 'label' => 'Groq (LLaMA)', 'url' => 'https://console.groq.com/keys', 'url_label' => 'Obtener API Key de Groq'],
                    ];

                    foreach ($api_configs as $config):
                        $has_key = !empty(get_option($config['key'], ''));
                    ?>
                    <tr>
                        <th scope="row">
                            <label for="<?php echo esc_attr($config['key']); ?>">
                                <?php printf(__('API Key - %s', 'kzmcito-ia-seo'), $config['label']); ?>
                                <?php if ($has_key): ?>
                                    <span class="dashicons dashicons-yes" style="color: #46b450;" title="<?php _e('Configurada', 'kzmcito-ia-seo'); ?>"></span>
                                <?php endif; ?>
                            </label>
                        </th>
                        <td>
                            <input type="password" name="<?php echo esc_attr($config['key']); ?>"
                                id="<?php echo esc_attr($config['key']); ?>"
                                value="<?php echo esc_attr(get_option($config['key'])); ?>" class="regular-text">
                            <p class="description">
                                <a href="<?php echo esc_url($config['url']); ?>" target="_blank" rel="noopener">
                                    → <?php echo esc_html($config['url_label']); ?>
                                </a>
                            </p>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                    <tr>
                        <th scope="row">
                            <label for="kzmcito_google_maps_api_key">
                                <?php _e('API Key - Google Maps', 'kzmcito-ia-seo'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="password" name="kzmcito_google_maps_api_key" id="kzmcito_google_maps_api_key"
                                value="<?php echo esc_attr(get_option('kzmcito_google_maps_api_key')); ?>" class="regular-text">
                            <p class="description">
                                <a href="https://console.cloud.google.com/google/maps-apis/credentials" target="_blank" rel="noopener">
                                    → <?php _e('Obtener API Key de Google Maps', 'kzmcito-ia-seo'); ?>
                                </a>
                            </p>
                        </td>
                    </tr>
                </table>

                <!-- ===== SECCIÓN: CONTENIDO ===== -->
                <h2 class="kzmcito-section-title">
                    <span class="dashicons dashicons-editor-paste-word"></span>
                    <?php _e('Procesamiento de Contenido', 'kzmcito-ia-seo'); ?>
                </h2>
                <table class="form-table">
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
                            <label for="kzmcito_enable_summary">
                                <?php _e('Sumario Reuters', 'kzmcito-ia-seo'); ?>
                            </label>
                        </th>
                        <td>
                            <select name="kzmcito_enable_summary" id="kzmcito_enable_summary">
                                <option value="yes" <?php selected(get_option('kzmcito_enable_summary', 'yes'), 'yes'); ?>>
                                    <?php _e('Sí - Generar Ideas Clave (3-5 viñetas estilo Reuters)', 'kzmcito-ia-seo'); ?>
                                </option>
                                <option value="no" <?php selected(get_option('kzmcito_enable_summary', 'yes'), 'no'); ?>>
                                    <?php _e('No', 'kzmcito-ia-seo'); ?>
                                </option>
                            </select>
                            <p class="description">
                                <?php _e('Autogenerar un sumario de 3-5 ideas clave al inicio del post (si no existe uno).', 'kzmcito-ia-seo'); ?>
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
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="kzmcito_ga4_measurement_id">
                                <?php _e('Google Analytics 4 ID', 'kzmcito-ia-seo'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" name="kzmcito_ga4_measurement_id" id="kzmcito_ga4_measurement_id"
                                value="<?php echo esc_attr(get_option('kzmcito_ga4_measurement_id')); ?>" class="regular-text" placeholder="G-XXXXXXXXXX">
                            <p class="description">
                                <?php _e('ID de Medición de GA4 para seguimiento de eventos.', 'kzmcito-ia-seo'); ?>
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
     * ==========================================
     * CATEGORÍAS Y PROMPTS DINÁMICOS
     * ==========================================
     */
    public function render_categories_page()
    {
        $prompt_manager = new Kzmcito_IA_SEO_Prompt_Manager();

        // Guardar selección de categorías
        if (isset($_POST['kzmcito_save_categories'])) {
            check_admin_referer('kzmcito_categories');
            $selected = isset($_POST['kzmcito_cat_selected']) ? array_map('sanitize_text_field', $_POST['kzmcito_cat_selected']) : [];
            $prompt_manager->save_selected_categories($selected);
            echo '<div class="notice notice-success"><p>' . __('Categorías actualizadas exitosamente', 'kzmcito-ia-seo') . '</p></div>';
        }

        // Guardar prompt de categoría
        if (isset($_POST['kzmcito_save_cat_prompt'])) {
            check_admin_referer('kzmcito_categories');
            $cat_slug = sanitize_text_field($_POST['cat_prompt_slug']);
            $cat_content = wp_kses_post($_POST['cat_prompt_content']);

            if ($prompt_manager->save_category_prompt($cat_slug, $cat_content)) {
                echo '<div class="notice notice-success"><p>' . sprintf(__('Prompt guardado para categoría: %s', 'kzmcito-ia-seo'), $cat_slug) . '</p></div>';
            }
        }

        $categories = $prompt_manager->get_site_categories();
        $editing_slug = isset($_GET['edit_cat']) ? sanitize_text_field($_GET['edit_cat']) : '';

        ?>
        <div class="wrap">
            <h1>
                <span class="dashicons dashicons-category" style="font-size: 28px; margin-right: 5px;"></span>
                <?php _e('Categorías y Prompts Dinámicos', 'kzmcito-ia-seo'); ?>
            </h1>

            <p class="description" style="font-size: 14px; margin-bottom: 20px;">
                <?php _e('Selecciona las categorías de tu sitio que deseas procesar con IA. Para cada categoría puedes escribir un prompt específico que se fusionará con el prompt global.', 'kzmcito-ia-seo'); ?>
            </p>

            <!-- Selector de Categorías -->
            <form method="post" action="">
                <?php wp_nonce_field('kzmcito_categories'); ?>

                <div class="kzmcito-categories-grid">
                    <?php foreach ($categories as $cat): ?>
                    <div class="kzmcito-cat-card <?php echo $cat['selected'] ? 'selected' : ''; ?>">
                        <label class="kzmcito-cat-label">
                            <input type="checkbox" name="kzmcito_cat_selected[]" value="<?php echo esc_attr($cat['slug']); ?>"
                                <?php checked($cat['selected']); ?>>
                            <span class="kzmcito-cat-name"><?php echo esc_html($cat['name']); ?></span>
                            <span class="kzmcito-cat-count"><?php echo intval($cat['count']); ?> posts</span>
                        </label>
                        <div class="kzmcito-cat-actions">
                            <?php if ($cat['has_prompt']): ?>
                                <span class="dashicons dashicons-yes" style="color: #46b450;" title="<?php _e('Tiene prompt personalizado', 'kzmcito-ia-seo'); ?>"></span>
                            <?php endif; ?>
                            <a href="?page=kzmcito-ia-seo-categories&edit_cat=<?php echo esc_attr($cat['slug']); ?>" class="button button-small">
                                <?php _e('Editar Prompt', 'kzmcito-ia-seo'); ?>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <p class="submit">
                    <button type="submit" name="kzmcito_save_categories" class="button button-primary">
                        <span class="dashicons dashicons-saved" style="margin-top: 4px;"></span>
                        <?php _e('Guardar Selección de Categorías', 'kzmcito-ia-seo'); ?>
                    </button>
                </p>
            </form>

            <?php if ($editing_slug): 
                $editing_cat = null;
                foreach ($categories as $c) {
                    if ($c['slug'] === $editing_slug) { $editing_cat = $c; break; }
                }
                if ($editing_cat):
            ?>
            <!-- Editor de Prompt para Categoría -->
            <hr style="margin: 30px 0;">
            <h2>
                <span class="dashicons dashicons-edit"></span>
                <?php printf(__('Prompt Específico: %s', 'kzmcito-ia-seo'), esc_html($editing_cat['name'])); ?>
            </h2>
            <p class="description">
                <?php _e('Este prompt se fusionará con el Prompt Global del sistema. Las instrucciones específicas de categoría tienen prioridad sobre las globales.', 'kzmcito-ia-seo'); ?>
            </p>

            <form method="post" action="">
                <?php wp_nonce_field('kzmcito_categories'); ?>
                <input type="hidden" name="cat_prompt_slug" value="<?php echo esc_attr($editing_slug); ?>">

                <textarea name="cat_prompt_content" id="cat_prompt_content" rows="15"
                    class="large-text code" placeholder="<?php _e('Escribe las instrucciones específicas para esta categoría...', 'kzmcito-ia-seo'); ?>"><?php echo esc_textarea($editing_cat['prompt']); ?></textarea>

                <p class="description" style="margin-top: 10px;">
                    <strong><?php _e('Variables disponibles:', 'kzmcito-ia-seo'); ?></strong>
                    <code>{{site_name}}</code>, <code>{{site_url}}</code>, <code>{{current_date}}</code>, <code>{{current_year}}</code>, <code>{{min_words}}</code>, <code>{{max_words}}</code>
                </p>

                <p class="submit">
                    <button type="submit" name="kzmcito_save_cat_prompt" class="button button-primary">
                        <?php _e('Guardar Prompt de Categoría', 'kzmcito-ia-seo'); ?>
                    </button>
                    <a href="?page=kzmcito-ia-seo-categories" class="button">
                        <?php _e('Cancelar', 'kzmcito-ia-seo'); ?>
                    </a>
                </p>
            </form>
            <?php endif; endif; ?>
        </div>

        <style>
            .kzmcito-categories-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 15px;
                margin-top: 20px;
            }
            .kzmcito-cat-card {
                background: #fff;
                border: 2px solid #dcdcde;
                border-radius: 8px;
                padding: 15px;
                transition: all 0.2s;
            }
            .kzmcito-cat-card:hover {
                border-color: #2271b1;
                box-shadow: 0 2px 8px rgba(34, 113, 177, 0.15);
            }
            .kzmcito-cat-card.selected {
                border-color: #2271b1;
                background: #f0f6fc;
            }
            .kzmcito-cat-label {
                display: flex;
                align-items: center;
                gap: 10px;
                cursor: pointer;
                font-size: 14px;
            }
            .kzmcito-cat-label input[type="checkbox"] {
                width: 18px;
                height: 18px;
            }
            .kzmcito-cat-name {
                font-weight: 600;
                flex: 1;
            }
            .kzmcito-cat-count {
                color: #787c82;
                font-size: 12px;
            }
            .kzmcito-cat-actions {
                display: flex;
                align-items: center;
                gap: 8px;
                margin-top: 10px;
                padding-top: 10px;
                border-top: 1px solid #eee;
                justify-content: flex-end;
            }
        </style>
        <?php
    }

    /**
     * ==========================================
     * EDITOR DE PROMPTS
     * ==========================================
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
                        <?php _e('Prompts Disponibles', 'kzmcito-ia-seo'); ?>
                    </h3>
                    <ul>
                        <?php foreach ($prompts as $slug => $data): ?>
                            <li>
                                <a href="?page=kzmcito-ia-seo-prompts&category=<?php echo esc_attr($slug); ?>"
                                    class="<?php echo $current_category === $slug ? 'active' : ''; ?>">
                                    <?php if ($slug === 'global'): ?>
                                        <span class="dashicons dashicons-admin-site" style="font-size: 16px;"></span>
                                    <?php else: ?>
                                        <span class="dashicons dashicons-category" style="font-size: 16px;"></span>
                                    <?php endif; ?>
                                    <?php echo esc_html($data['name']); ?>
                                    <?php if (!empty($data['content'])): ?>
                                        <span class="dashicons dashicons-yes" style="color: #46b450; font-size: 14px;"></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <hr>
                    <p class="description" style="padding: 0 12px; font-size: 11px;">
                        <?php _e('Para agregar nuevas categorías, ve a la pestaña "Categorías".', 'kzmcito-ia-seo'); ?>
                    </p>
                </div>

                <div class="kzmcito-prompts-content">
                    <h2>
                        <?php echo esc_html($current_prompt['name']); ?>
                        <?php if ($current_category === 'global'): ?>
                            <span class="kzmcito-badge-info"><?php _e('Sistema', 'kzmcito-ia-seo'); ?></span>
                        <?php else: ?>
                            <span class="kzmcito-badge-cat"><?php _e('Categoría', 'kzmcito-ia-seo'); ?></span>
                        <?php endif; ?>
                    </h2>

                    <form method="post" action="">
                        <?php wp_nonce_field('kzmcito_prompts'); ?>
                        <input type="hidden" name="prompt_category" value="<?php echo esc_attr($current_category); ?>">

                        <textarea name="prompt_content" id="prompt_content" rows="20"
                            class="large-text code"><?php echo esc_textarea($current_prompt['content']); ?></textarea>

                        <p class="description" style="margin-top: 10px;">
                            <strong><?php _e('Fuente:', 'kzmcito-ia-seo'); ?></strong>
                            <code><?php echo esc_html($current_prompt['source'] ?? 'file'); ?></code>
                            &nbsp;|&nbsp;
                            <strong><?php _e('Variables:', 'kzmcito-ia-seo'); ?></strong>
                            <code>{{site_name}}</code>, <code>{{site_url}}</code>, <code>{{current_date}}</code>, <code>{{min_words}}</code>, <code>{{max_words}}</code>
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
                    flex: 0 0 240px;
                    background: #fff;
                    padding: 15px;
                    border: 1px solid #ccd0d4;
                    border-radius: 4px;
                }
                .kzmcito-prompts-sidebar ul {
                    margin: 0;
                    padding: 0;
                    list-style: none;
                }
                .kzmcito-prompts-sidebar li {
                    margin: 3px 0;
                }
                .kzmcito-prompts-sidebar a {
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    padding: 8px 12px;
                    text-decoration: none;
                    border-radius: 4px;
                    font-size: 13px;
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
                    border-radius: 4px;
                }
                .kzmcito-badge-info {
                    display: inline-block;
                    padding: 2px 8px;
                    background: #2271b1;
                    color: #fff;
                    border-radius: 3px;
                    font-size: 11px;
                    font-weight: 600;
                    text-transform: uppercase;
                    vertical-align: middle;
                }
                .kzmcito-badge-cat {
                    display: inline-block;
                    padding: 2px 8px;
                    background: #dba617;
                    color: #fff;
                    border-radius: 3px;
                    font-size: 11px;
                    font-weight: 600;
                    text-transform: uppercase;
                    vertical-align: middle;
                }
            </style>
        </div>
        <?php
    }

    /**
     * ==========================================
     * IDIOMAS
     * ==========================================
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
            <h1><?php _e('Gestión de Idiomas', 'kzmcito-ia-seo'); ?></h1>

            <h2><?php _e('Idiomas Activos', 'kzmcito-ia-seo'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Código', 'kzmcito-ia-seo'); ?></th>
                        <th><?php _e('Nombre', 'kzmcito-ia-seo'); ?></th>
                        <th><?php _e('Nombre Nativo', 'kzmcito-ia-seo'); ?></th>
                        <th><?php _e('Estado', 'kzmcito-ia-seo'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($languages as $lang): ?>
                        <tr>
                            <td><code><?php echo esc_html($lang['code']); ?></code></td>
                            <td><?php echo esc_html($lang['name']); ?></td>
                            <td><?php echo esc_html($lang['native_name']); ?></td>
                            <td>
                                <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                                <?php _e('Activo', 'kzmcito-ia-seo'); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2><?php _e('Agregar Nuevo Idioma', 'kzmcito-ia-seo'); ?></h2>
            <form method="post" action="">
                <?php wp_nonce_field('kzmcito_languages'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="lang_code"><?php _e('Código', 'kzmcito-ia-seo'); ?></label></th>
                        <td><input type="text" name="lang_code" id="lang_code" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="lang_name"><?php _e('Nombre', 'kzmcito-ia-seo'); ?></label></th>
                        <td><input type="text" name="lang_name" id="lang_name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="lang_native_name"><?php _e('Nombre Nativo', 'kzmcito-ia-seo'); ?></label></th>
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
     * ==========================================
     * ESTADÍSTICAS
     * ==========================================
     */
    public function render_stats_page()
    {
        $api_client = new Kzmcito_IA_SEO_API_Client();
        $available_providers = $api_client->get_available_providers();

        ?>
        <div class="wrap">
            <h1><?php _e('Estadísticas de KzmCITO IA SEO', 'kzmcito-ia-seo'); ?></h1>

            <div class="kzmcito-stats-grid">
                <div class="kzmcito-stat-box">
                    <h3><?php _e('Posts Procesados', 'kzmcito-ia-seo'); ?></h3>
                    <p class="kzmcito-stat-number"><?php echo $this->get_processed_posts_count(); ?></p>
                </div>

                <div class="kzmcito-stat-box">
                    <h3><?php _e('Traducciones Generadas', 'kzmcito-ia-seo'); ?></h3>
                    <p class="kzmcito-stat-number"><?php echo $this->get_translations_count(); ?></p>
                </div>

                <div class="kzmcito-stat-box">
                    <h3><?php _e('Modo Fallback', 'kzmcito-ia-seo'); ?></h3>
                    <p class="kzmcito-stat-number"><?php echo get_option('kzmcito_fallback_count', 0); ?></p>
                </div>

                <div class="kzmcito-stat-box" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                    <h3><?php _e('Proveedores IA Activos', 'kzmcito-ia-seo'); ?></h3>
                    <p class="kzmcito-stat-number"><?php echo count($available_providers); ?></p>
                    <p style="font-size: 12px; opacity: 0.9; margin-top: 5px;">
                        <?php echo esc_html(implode(', ', array_map('ucfirst', $available_providers))); ?>
                    </p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * ==========================================
     * GUARDAR CONFIGURACIÓN
     * ==========================================
     */
    private function save_settings()
    {
        $options = [
            'kzmcito_ai_model',
            'kzmcito_api_key_claude',
            'kzmcito_api_key_gemini',
            'kzmcito_api_key_openai',
            'kzmcito_api_key_deepseek',
            'kzmcito_api_key_mistral',
            'kzmcito_api_key_groq',
            'kzmcito_auto_process',
            'kzmcito_enable_summary',
            'kzmcito_min_words',
            'kzmcito_max_words',
            'kzmcito_enable_toc',
            'kzmcito_enable_faq',
            'kzmcito_google_maps_api_key',
            'kzmcito_ga4_measurement_id',
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
