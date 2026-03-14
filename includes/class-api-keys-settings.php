<?php
/**
 * API Keys Settings - Configuración compartida de claves API
 *
 * Registra las API Keys de los proveedores de IA en el menú nativo
 * Ajustes de WordPress (Settings API), de modo que sean accesibles
 * y compartidas entre todos los plugins KzmCITO.
 *
 * Ruta en WP Admin: Ajustes → API Keys KzmCITO
 *
 * @package KzmcitoIASEO
 * @since   3.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Kzmcito_API_Keys_Settings
{
    /**
     * Slug de la página de ajustes
     */
    const PAGE_SLUG = 'kzmcito-api-keys';

    /**
     * Nombre del grupo de opciones (Settings API)
     */
    const OPTION_GROUP = 'kzmcito_api_keys_group';

    /**
     * Sección principal
     */
    const SECTION_ID = 'kzmcito_api_keys_section';

    /**
     * Lista centralizada de todas las API Keys gestionadas
     */
    public static function get_api_key_configs(): array
    {
        return [
            [
                'key'       => 'kzmcito_api_key_claude',
                'label'     => 'Claude (Anthropic)',
                'url'       => 'https://console.anthropic.com/settings/keys',
                'url_label' => 'Obtener API Key de Claude',
            ],
            [
                'key'       => 'kzmcito_api_key_gemini',
                'label'     => 'Gemini (Google)',
                'url'       => 'https://makersuite.google.com/app/apikey',
                'url_label' => 'Obtener API Key de Gemini',
            ],
            [
                'key'       => 'kzmcito_api_key_openai',
                'label'     => 'OpenAI (GPT / Codex)',
                'url'       => 'https://platform.openai.com/api-keys',
                'url_label' => 'Obtener API Key de OpenAI',
            ],
            [
                'key'       => 'kzmcito_api_key_deepseek',
                'label'     => 'DeepSeek',
                'url'       => 'https://platform.deepseek.com/api_keys',
                'url_label' => 'Obtener API Key de DeepSeek',
            ],
            [
                'key'       => 'kzmcito_api_key_mistral',
                'label'     => 'Mistral AI',
                'url'       => 'https://console.mistral.ai/api-keys/',
                'url_label' => 'Obtener API Key de Mistral',
            ],
            [
                'key'       => 'kzmcito_api_key_groq',
                'label'     => 'Groq (LLaMA)',
                'url'       => 'https://console.groq.com/keys',
                'url_label' => 'Obtener API Key de Groq',
            ],
            [
                'key'       => 'kzmcito_google_maps_api_key',
                'label'     => 'Google Maps',
                'url'       => 'https://console.cloud.google.com/google/maps-apis/credentials',
                'url_label' => 'Obtener API Key de Google Maps',
            ],
        ];
    }

    /**
     * Registro de hooks
     */
    public function init(): void
    {
        add_action('admin_menu',  [$this, 'add_settings_page']);
        add_action('admin_init',  [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_styles']);
    }

    /**
     * Añadir subpágina bajo el menú nativo Ajustes de WordPress
     */
    public function add_settings_page(): void
    {
        add_options_page(
            __('API Keys KzmCITO', 'kzmcito-ia-seo'),          // Título de la página
            __('API Keys KzmCITO', 'kzmcito-ia-seo'),          // Título en el menú
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'render_page']
        );
    }

    /**
     * Registrar opciones con la Settings API de WordPress
     */
    public function register_settings(): void
    {
        // Registrar cada key individualmente para validate/sanitize
        foreach (self::get_api_key_configs() as $config) {
            register_setting(
                self::OPTION_GROUP,
                $config['key'],
                [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'default'           => '',
                ]
            );
        }

        // Sección principal
        add_settings_section(
            self::SECTION_ID,
            __('Claves de API – Proveedores de IA y Servicios', 'kzmcito-ia-seo'),
            [$this, 'render_section_description'],
            self::PAGE_SLUG
        );

        // Campo por cada proveedor
        foreach (self::get_api_key_configs() as $config) {
            add_settings_field(
                $config['key'],
                $config['label'],
                [$this, 'render_field'],
                self::PAGE_SLUG,
                self::SECTION_ID,
                $config   // se pasa como $args al callback
            );
        }
    }

    /**
     * Descripción de la sección
     */
    public function render_section_description(): void
    {
        echo '<p class="kzmcito-api-desc">';
        esc_html_e(
            'Configura aquí las claves API de todos los proveedores de IA. '
            . 'Estos valores son compartidos entre todos los plugins KzmCITO instalados en este sitio. '
            . 'Si el proveedor principal falla, el sistema usará automáticamente los demás en modo fallback en cascada.',
            'kzmcito-ia-seo'
        );
        echo '</p>';
    }

    /**
     * Renderizar campo de contraseña para cada API Key
     *
     * @param array $args Configuración del campo (key, label, url, url_label)
     */
    public function render_field(array $args): void
    {
        $value   = get_option($args['key'], '');
        $has_key = !empty($value);
        $field_id = esc_attr($args['key']);
        ?>
        <div class="kzmcito-api-field-wrap">
            <div class="kzmcito-api-input-row">
                <input
                    type="password"
                    id="<?php echo $field_id; ?>"
                    name="<?php echo $field_id; ?>"
                    value="<?php echo esc_attr($value); ?>"
                    class="regular-text kzmcito-api-input"
                    autocomplete="new-password"
                >
                <button type="button"
                        class="button kzmcito-toggle-key"
                        data-target="<?php echo $field_id; ?>"
                        title="<?php esc_attr_e('Mostrar / Ocultar', 'kzmcito-ia-seo'); ?>">
                    <span class="dashicons dashicons-visibility"></span>
                </button>
                <?php if ($has_key): ?>
                    <span class="kzmcito-key-ok dashicons dashicons-yes-alt"
                          title="<?php esc_attr_e('Key configurada', 'kzmcito-ia-seo'); ?>"></span>
                <?php endif; ?>
            </div>
            <p class="description">
                <a href="<?php echo esc_url($args['url']); ?>" target="_blank" rel="noopener noreferrer">
                    → <?php echo esc_html($args['url_label']); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Renderizar la página completa de ajustes
     */
    public function render_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap kzmcito-api-keys-wrap">
            <h1 class="kzmcito-api-page-title">
                <span class="dashicons dashicons-admin-network"></span>
                <?php esc_html_e('API Keys KzmCITO', 'kzmcito-ia-seo'); ?>
            </h1>

            <div class="kzmcito-api-intro-card">
                <span class="dashicons dashicons-info-outline kzmcito-api-intro-icon"></span>
                <div>
                    <strong><?php esc_html_e('Configuración centralizada y compartida', 'kzmcito-ia-seo'); ?></strong><br>
                    <?php esc_html_e(
                        'Las claves almacenadas aquí son utilizadas por todos los plugins KzmCITO activos en este WordPress. '
                        . 'Solo necesitas configurarlas una vez.',
                        'kzmcito-ia-seo'
                    ); ?>
                </div>
            </div>

            <form method="post" action="options.php">
                <?php
                settings_fields(self::OPTION_GROUP);
                do_settings_sections(self::PAGE_SLUG);
                submit_button(__('Guardar Claves API', 'kzmcito-ia-seo'));
                ?>
            </form>
        </div>

        <style>
            .kzmcito-api-keys-wrap { max-width: 860px; }

            .kzmcito-api-page-title {
                display: flex;
                align-items: center;
                gap: 10px;
                font-size: 23px;
                margin-bottom: 18px;
            }
            .kzmcito-api-page-title .dashicons {
                font-size: 28px;
                width: 28px;
                height: 28px;
                color: #2271b1;
            }

            .kzmcito-api-intro-card {
                display: flex;
                align-items: flex-start;
                gap: 12px;
                background: #eef6fb;
                border-left: 4px solid #2271b1;
                padding: 14px 18px;
                border-radius: 4px;
                margin-bottom: 24px;
                font-size: 13.5px;
                line-height: 1.5;
            }
            .kzmcito-api-intro-icon {
                font-size: 24px;
                width: 24px;
                height: 24px;
                color: #2271b1;
                flex-shrink: 0;
                margin-top: 2px;
            }

            .kzmcito-api-desc {
                color: #50575e;
                font-size: 13px;
                margin-bottom: 16px;
            }

            .kzmcito-api-field-wrap { display: flex; flex-direction: column; gap: 4px; }

            .kzmcito-api-input-row {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .kzmcito-api-input { font-family: monospace; letter-spacing: 0.05em; }

            .kzmcito-toggle-key {
                padding: 4px 8px !important;
                height: 30px;
                display: flex;
                align-items: center;
            }
            .kzmcito-toggle-key .dashicons { font-size: 16px; width: 16px; height: 16px; }

            .kzmcito-key-ok {
                color: #46b450;
                font-size: 20px;
                width: 20px;
                height: 20px;
            }
        </style>

        <script>
        (function () {
            document.querySelectorAll('.kzmcito-toggle-key').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var targetId = btn.getAttribute('data-target');
                    var input = document.getElementById(targetId);
                    var icon  = btn.querySelector('.dashicons');
                    if (!input) return;
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.classList.replace('dashicons-visibility', 'dashicons-hidden');
                    } else {
                        input.type = 'password';
                        icon.classList.replace('dashicons-hidden', 'dashicons-visibility');
                    }
                });
            });
        })();
        </script>
        <?php
    }

    /**
     * Cargar estilos adicionales solo en nuestra página
     */
    public function enqueue_styles(string $hook): void
    {
        if ($hook !== 'settings_page_' . self::PAGE_SLUG) {
            return;
        }
        // Los estilos están inline en render_page(); este hook puede usarse
        // para encolar un CSS externo en el futuro si se prefiere.
    }
}
