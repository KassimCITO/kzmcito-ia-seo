<?php
/**
 * Plugin Name: Engine Editorial El Día de Michoacán
 * Plugin URI: https://kzmcito.com
 * Description: Motor editorial agentico con IA para transformación de contenidos, SEO automático y caché multilingüe. Integrado con RankMath.
 * Version: 2.0.0
 * Author: KassimCITO
 * Author URI: https://kzmcito.com
 * Text Domain: kzmcito-ia-seo
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Constantes del Plugin
 */
define('KZMCITO_IA_SEO_VERSION', '2.0.0');
define('KZMCITO_IA_SEO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KZMCITO_IA_SEO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('KZMCITO_IA_SEO_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('KZMCITO_IA_SEO_PROMPTS_DIR', KZMCITO_IA_SEO_PLUGIN_DIR . 'prompts/');

/**
 * Autoloader para las clases del plugin
 */
spl_autoload_register(function ($class) {
    // Prefijo de namespace del plugin
    $prefix = 'KzmcitoIASEO\\';

    // Verificar si la clase usa nuestro namespace
    if (strpos($class, $prefix) !== 0) {
        return;
    }

    // Obtener el nombre relativo de la clase
    $relative_class = substr($class, strlen($prefix));

    // Convertir namespace a ruta de archivo
    $file = KZMCITO_IA_SEO_PLUGIN_DIR . 'includes/class-' .
        strtolower(str_replace('_', '-', str_replace('\\', '-', $relative_class))) . '.php';

    // Si el archivo existe, incluirlo
    if (file_exists($file)) {
        require_once $file;
    }
});

/**
 * Clase Principal del Plugin
 */
class Kzmcito_IA_SEO
{

    /**
     * Instancia única del plugin (Singleton)
     */
    private static $instance = null;

    /**
     * Instancia del Core Orchestrator
     */
    private $core;

    /**
     * Obtener instancia única del plugin
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado (Singleton)
     */
    private function __construct()
    {
        $this->load_dependencies();
        $this->init_hooks();
        $this->init_core();
    }

    /**
     * Cargar dependencias del plugin
     */
    private function load_dependencies()
    {
        // Core classes
        require_once KZMCITO_IA_SEO_PLUGIN_DIR . 'includes/class-core.php';
        require_once KZMCITO_IA_SEO_PLUGIN_DIR . 'includes/class-prompt-manager.php';
        require_once KZMCITO_IA_SEO_PLUGIN_DIR . 'includes/class-content-processor.php';
        require_once KZMCITO_IA_SEO_PLUGIN_DIR . 'includes/class-seo-injector.php';
        require_once KZMCITO_IA_SEO_PLUGIN_DIR . 'includes/class-translation-manager.php';
        require_once KZMCITO_IA_SEO_PLUGIN_DIR . 'includes/class-api-client.php';
        require_once KZMCITO_IA_SEO_PLUGIN_DIR . 'includes/class-meta-fields.php';
        require_once KZMCITO_IA_SEO_PLUGIN_DIR . 'includes/class-cache-manager.php';

        // Admin UI (solo en admin)
        if (is_admin()) {
            require_once KZMCITO_IA_SEO_PLUGIN_DIR . 'includes/class-admin-ui.php';
        }
    }

    /**
     * Inicializar hooks de WordPress
     */
    private function init_hooks()
    {
        // Activación y desactivación
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        // Inicialización del plugin
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_action('init', [$this, 'register_meta_fields']);

        // Hooks de contenido (Pipeline de 4 fases)
        add_filter('wp_insert_post_data', [$this, 'process_content_before_save'], 99, 2);
        add_action('save_post', [$this, 'process_meta_after_save'], 20, 3);

        // Admin hooks
        if (is_admin()) {
            add_action('admin_menu', [$this, 'register_admin_menu']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
            add_action('add_meta_boxes', [$this, 'register_meta_boxes']);
        }

        // Frontend hooks
        add_filter('the_content', [$this, 'filter_frontend_content'], 999);

        // AJAX hooks
        add_action('wp_ajax_kzmcito_process_post', [$this, 'ajax_process_post']);
        add_action('wp_ajax_kzmcito_translate_content', [$this, 'ajax_translate_content']);
    }

    /**
     * Inicializar el Core Orchestrator
     */
    private function init_core()
    {
        $this->core = new Kzmcito_IA_SEO_Core();
    }

    /**
     * Activación del plugin
     */
    public function activate()
    {
        // Crear opciones por defecto
        $default_options = [
            'kzmcito_ai_model' => 'claude-sonnet',
            'kzmcito_api_key_claude' => '',
            'kzmcito_api_key_gemini' => '',
            'kzmcito_api_key_openai' => '',
            'kzmcito_auto_process' => 'no',
            'kzmcito_min_words' => 850,
            'kzmcito_max_words' => 1200,
            'kzmcito_enable_toc' => 'yes',
            'kzmcito_enable_faq' => 'yes',
            'kzmcito_active_languages' => ['en', 'pt', 'fr', 'de', 'ru', 'hi', 'zh'],
        ];

        foreach ($default_options as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }

        // Crear tabla para idiomas personalizados
        $this->create_languages_table();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Desactivación del plugin
     */
    public function deactivate()
    {
        flush_rewrite_rules();
    }

    /**
     * Crear tabla de idiomas
     */
    private function create_languages_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kzmcito_languages';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            code varchar(10) NOT NULL,
            name varchar(100) NOT NULL,
            native_name varchar(100) NOT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY code (code)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Insertar idiomas por defecto
        $default_languages = [
            ['en', 'English', 'English'],
            ['pt', 'Portuguese', 'Português'],
            ['fr', 'French', 'Français'],
            ['de', 'German', 'Deutsch'],
            ['ru', 'Russian', 'Русский'],
            ['hi', 'Hindi', 'हिन्दी'],
            ['zh', 'Chinese Simplified', '简体中文'],
        ];

        foreach ($default_languages as $lang) {
            $wpdb->insert(
                $table_name,
                [
                    'code' => $lang[0],
                    'name' => $lang[1],
                    'native_name' => $lang[2],
                    'is_active' => 1
                ],
                ['%s', '%s', '%s', '%d']
            );
        }
    }

    /**
     * Cargar traducciones
     */
    public function load_textdomain()
    {
        load_plugin_textdomain(
            'kzmcito-ia-seo',
            false,
            dirname(KZMCITO_IA_SEO_PLUGIN_BASENAME) . '/languages'
        );
    }

    /**
     * Registrar campos meta personalizados
     */
    public function register_meta_fields()
    {
        $meta_fields = new Kzmcito_IA_SEO_Meta_Fields();
        $meta_fields->register();
    }

    /**
     * Procesar contenido antes de guardar (Fases 1-3)
     */
    public function process_content_before_save($data, $postarr)
    {
        // Solo procesar posts y páginas
        if (!in_array($data['post_type'], ['post', 'page'])) {
            return $data;
        }

        // Verificar si el procesamiento automático está habilitado
        $auto_process = get_option('kzmcito_auto_process', 'no');
        $manual_trigger = isset($_POST['kzmcito_process_now']) && $_POST['kzmcito_process_now'] === '1';

        if ($auto_process === 'yes' || $manual_trigger) {
            // Ejecutar el pipeline de procesamiento
            $processed_data = $this->core->process_content($data, $postarr);
            return $processed_data;
        }

        return $data;
    }

    /**
     * Procesar metadatos después de guardar (Fase 4)
     */
    public function process_meta_after_save($post_id, $post, $update)
    {
        // Evitar auto-save y revisiones
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Verificar permisos
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Solo procesar posts y páginas
        if (!in_array($post->post_type, ['post', 'page'])) {
            return;
        }

        // Verificar si se debe procesar
        $auto_process = get_option('kzmcito_auto_process', 'no');
        $manual_trigger = isset($_POST['kzmcito_process_now']) && $_POST['kzmcito_process_now'] === '1';

        if ($auto_process === 'yes' || $manual_trigger) {
            // Fase 4: Localización y caché de traducciones
            $this->core->process_translations($post_id, $post);
        }
    }

    /**
     * Registrar menú de administración
     */
    public function register_admin_menu()
    {
        $admin_ui = new Kzmcito_IA_SEO_Admin_UI();
        $admin_ui->register_menu();
    }

    /**
     * Registrar meta boxes
     */
    public function register_meta_boxes()
    {
        add_meta_box(
            'kzmcito_ia_seo_control',
            __('Engine Editorial IA', 'kzmcito-ia-seo'),
            [$this, 'render_control_meta_box'],
            ['post', 'page'],
            'side',
            'high'
        );
    }

    /**
     * Renderizar meta box de control
     */
    public function render_control_meta_box($post)
    {
        wp_nonce_field('kzmcito_ia_seo_meta_box', 'kzmcito_ia_seo_nonce');

        $last_processed = get_post_meta($post->ID, '_kzmcito_last_processed', true);
        $category_detected = get_post_meta($post->ID, '_kzmcito_category_detected', true);

        ?>
        <div class="kzmcito-control-panel">
            <p>
                <strong><?php _e('Estado:', 'kzmcito-ia-seo'); ?></strong><br>
                <?php if ($last_processed): ?>
                    <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                    <?php printf(__('Procesado: %s', 'kzmcito-ia-seo'), date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_processed))); ?>
                <?php else: ?>
                    <span class="dashicons dashicons-warning" style="color: orange;"></span>
                    <?php _e('No procesado', 'kzmcito-ia-seo'); ?>
                <?php endif; ?>
            </p>

            <?php if ($category_detected): ?>
                <p>
                    <strong><?php _e('Categoría detectada:', 'kzmcito-ia-seo'); ?></strong><br>
                    <span class="kzmcito-category-badge"><?php echo esc_html(ucfirst($category_detected)); ?></span>
                </p>
            <?php endif; ?>

            <p>
                <button type="button" class="button button-primary button-large" id="kzmcito-process-now" style="width: 100%;">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Procesar Ahora', 'kzmcito-ia-seo'); ?>
                </button>
                <input type="hidden" name="kzmcito_process_now" id="kzmcito_process_now_input" value="0">
            </p>

            <p class="description">
                <?php _e('Ejecuta el pipeline completo de 4 fases: Análisis, Transformación, SEO e Inyección, y Localización.', 'kzmcito-ia-seo'); ?>
            </p>
        </div>

        <style>
            .kzmcito-control-panel {
                padding: 10px 0;
            }

            .kzmcito-category-badge {
                display: inline-block;
                padding: 4px 8px;
                background: #0073aa;
                color: white;
                border-radius: 3px;
                font-size: 12px;
            }
        </style>

        <script>
            jQuery(document).ready(function ($) {
                $('#kzmcito-process-now').on('click', function () {
                    if (confirm('<?php _e('¿Estás seguro de que deseas procesar este contenido con IA?', 'kzmcito-ia-seo'); ?>')) {
                        $('#kzmcito_process_now_input').val('1');
                        $(this).prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> <?php _e('Procesando...', 'kzmcito-ia-seo'); ?>');
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Cargar assets de administración
     */
    public function enqueue_admin_assets($hook)
    {
        // Solo cargar en páginas del plugin
        if (strpos($hook, 'kzmcito-ia-seo') === false && !in_array($hook, ['post.php', 'post-new.php'])) {
            return;
        }

        wp_enqueue_style(
            'kzmcito-ia-seo-admin',
            KZMCITO_IA_SEO_PLUGIN_URL . 'admin/assets/css/admin.css',
            [],
            KZMCITO_IA_SEO_VERSION
        );

        wp_enqueue_script(
            'kzmcito-ia-seo-admin',
            KZMCITO_IA_SEO_PLUGIN_URL . 'admin/assets/js/admin.js',
            ['jquery'],
            KZMCITO_IA_SEO_VERSION,
            true
        );

        wp_localize_script('kzmcito-ia-seo-admin', 'kzmcitoIASEO', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('kzmcito_ia_seo_ajax'),
            'i18n' => [
                'processing' => __('Procesando...', 'kzmcito-ia-seo'),
                'success' => __('¡Contenido procesado exitosamente!', 'kzmcito-ia-seo'),
                'error' => __('Error al procesar el contenido', 'kzmcito-ia-seo'),
            ]
        ]);
    }

    /**
     * Filtrar contenido en el frontend
     */
    public function filter_frontend_content($content)
    {
        // Aquí se puede agregar lógica adicional para el frontend
        return $content;
    }

    /**
     * AJAX: Procesar post manualmente
     */
    public function ajax_process_post()
    {
        check_ajax_referer('kzmcito_ia_seo_ajax', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Permisos insuficientes', 'kzmcito-ia-seo')]);
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

        if (!$post_id) {
            wp_send_json_error(['message' => __('ID de post inválido', 'kzmcito-ia-seo')]);
        }

        $result = $this->core->process_post_manually($post_id);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX: Traducir contenido
     */
    public function ajax_translate_content()
    {
        check_ajax_referer('kzmcito_ia_seo_ajax', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Permisos insuficientes', 'kzmcito-ia-seo')]);
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $language = isset($_POST['language']) ? sanitize_text_field($_POST['language']) : '';

        if (!$post_id || !$language) {
            wp_send_json_error(['message' => __('Parámetros inválidos', 'kzmcito-ia-seo')]);
        }

        $translation_manager = new Kzmcito_IA_SEO_Translation_Manager();
        $result = $translation_manager->translate_post($post_id, $language);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Obtener instancia del Core
     */
    public function get_core()
    {
        return $this->core;
    }
}

/**
 * Inicializar el plugin
 */
function kzmcito_ia_seo()
{
    return Kzmcito_IA_SEO::get_instance();
}

// Iniciar el plugin
kzmcito_ia_seo();
