<?php
/**
 * Prompt Manager - Gestor de prompts dinámico
 * 
 * Carga jerárquica: System Prompt Global + Prompt de categorías del sitio
 * Las categorías se leen dinámicamente de WordPress y el usuario
 * puede crear prompts personalizados por categoría.
 * 
 * @package KzmcitoIASEO
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Kzmcito_IA_SEO_Prompt_Manager
{

    /**
     * Directorio de prompts (solo para el global)
     */
    private $prompts_dir;

    /**
     * Cache de prompts cargados
     */
    private $prompts_cache = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->prompts_dir = KZMCITO_IA_SEO_PROMPTS_DIR;
    }

    /**
     * Cargar prompts fusionados (Global + Categoría)
     * 
     * @param string $category Category slug
     * @return array Prompts data
     */
    public function load_prompts($category)
    {
        // Verificar cache
        $cache_key = 'prompts_' . $category;
        if (isset($this->prompts_cache[$cache_key])) {
            return $this->prompts_cache[$cache_key];
        }

        // Cargar System Prompt Global
        $global_prompt = $this->load_global_prompt();

        // Cargar Prompt de Categoría desde DB
        $category_prompt = '';
        if ($category !== 'global') {
            $category_prompt = $this->load_category_prompt_from_db($category);
        }

        // Fusionar prompts
        $merged_prompt = $this->merge_prompts($global_prompt, $category_prompt, $category);

        $prompts_data = [
            'global' => $global_prompt,
            'category' => $category_prompt,
            'merged' => $merged_prompt,
            'category_name' => $category,
            'fallback_mode' => empty($category_prompt),
        ];

        // Guardar en cache
        $this->prompts_cache[$cache_key] = $prompts_data;

        // Log si está en modo fallback
        if ($prompts_data['fallback_mode'] && $category !== 'global') {
            $this->log_fallback($category);
        }

        return $prompts_data;
    }

    /**
     * Cargar prompt global (desde archivo o DB override)
     * 
     * @return string Global prompt content
     */
    private function load_global_prompt()
    {
        // Primero verificar si hay un override en la DB
        $db_override = get_option('kzmcito_global_prompt_override', '');
        if (!empty($db_override)) {
            return $this->process_prompt_content($db_override);
        }

        // Cargar desde archivo
        return $this->load_prompt_file('system-prompt-global.md');
    }

    /**
     * Cargar archivo de prompt
     * 
     * @param string $filename Filename
     * @return string Prompt content
     */
    private function load_prompt_file($filename)
    {
        $filepath = $this->prompts_dir . $filename;

        if (!file_exists($filepath)) {
            return '';
        }

        $content = file_get_contents($filepath);

        // Procesar el contenido del prompt
        $content = $this->process_prompt_content($content);

        return $content;
    }

    /**
     * Cargar prompt de categoría desde la base de datos
     * 
     * @param string $category_slug Category slug
     * @return string Category prompt content
     */
    private function load_category_prompt_from_db($category_slug)
    {
        $category_prompts = get_option('kzmcito_category_prompts', []);

        if (isset($category_prompts[$category_slug]) && !empty($category_prompts[$category_slug])) {
            return $this->process_prompt_content($category_prompts[$category_slug]);
        }

        return '';
    }

    /**
     * Procesar contenido del prompt
     * 
     * @param string $content Raw content
     * @return string Processed content
     */
    private function process_prompt_content($content)
    {
        // Eliminar caracteres de escape de markdown
        $content = str_replace('\\#', '#', $content);
        $content = str_replace('\\*', '*', $content);
        $content = str_replace('\\-', '-', $content);
        $content = str_replace('\\`', '`', $content);
        $content = str_replace('\\_', '_', $content);

        // Normalizar saltos de línea
        $content = str_replace("\r\n", "\n", $content);

        // Reemplazar variables dinámicas
        $content = $this->replace_variables($content);

        return trim($content);
    }

    /**
     * Reemplazar variables en el prompt
     * 
     * @param string $content Content with variables
     * @return string Content with replaced variables
     */
    private function replace_variables($content)
    {
        $variables = [
            '{{site_name}}' => get_bloginfo('name'),
            '{{site_url}}' => get_bloginfo('url'),
            '{{current_date}}' => date_i18n(get_option('date_format')),
            '{{current_year}}' => date('Y'),
            '{{min_words}}' => get_option('kzmcito_min_words', 850),
            '{{max_words}}' => get_option('kzmcito_max_words', 1200),
        ];

        return str_replace(
            array_keys($variables),
            array_values($variables),
            $content
        );
    }

    /**
     * Fusionar prompts global y de categoría
     * 
     * @param string $global_prompt Global prompt
     * @param string $category_prompt Category prompt
     * @param string $category Category name
     * @return string Merged prompt
     */
    private function merge_prompts($global_prompt, $category_prompt, $category)
    {
        if (empty($category_prompt)) {
            return $global_prompt;
        }

        // Fusión jerárquica
        $merged = "# INSTRUCCIONES DEL SISTEMA\n\n";
        $merged .= "## Contexto Global\n\n";
        $merged .= $global_prompt . "\n\n";
        $merged .= "---\n\n";
        $merged .= "## Contexto Específico de Categoría: " . ucfirst($category) . "\n\n";
        $merged .= $category_prompt . "\n\n";
        $merged .= "---\n\n";
        $merged .= "## Instrucciones de Fusión\n\n";
        $merged .= "Debes aplicar AMBOS conjuntos de instrucciones:\n";
        $merged .= "1. Las directrices globales del sistema\n";
        $merged .= "2. Las directrices específicas de la categoría " . ucfirst($category) . "\n\n";
        $merged .= "En caso de conflicto, las instrucciones específicas de categoría tienen prioridad.\n";

        return $merged;
    }

    /**
     * Obtener todas las categorías del sitio de WordPress
     * 
     * @return array Categories with their prompts
     */
    public function get_site_categories()
    {
        $categories = get_categories([
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);

        $category_prompts = get_option('kzmcito_category_prompts', []);
        $selected_categories = get_option('kzmcito_selected_categories', []);

        $result = [];
        foreach ($categories as $cat) {
            $result[] = [
                'id' => $cat->term_id,
                'slug' => $cat->slug,
                'name' => $cat->name,
                'count' => $cat->count,
                'selected' => in_array($cat->slug, $selected_categories),
                'has_prompt' => !empty($category_prompts[$cat->slug] ?? ''),
                'prompt' => $category_prompts[$cat->slug] ?? '',
            ];
        }

        return $result;
    }

    /**
     * Guardar categorías seleccionadas
     * 
     * @param array $category_slugs Array de slugs seleccionados
     * @return bool
     */
    public function save_selected_categories($category_slugs)
    {
        return update_option('kzmcito_selected_categories', array_map('sanitize_text_field', $category_slugs));
    }

    /**
     * Guardar prompt personalizado de una categoría
     * 
     * @param string $category_slug Category slug
     * @param string $content Prompt content
     * @return bool Success
     */
    public function save_category_prompt($category_slug, $content)
    {
        $category_prompts = get_option('kzmcito_category_prompts', []);

        // Backup anterior
        $this->backup_category_prompt($category_slug, $category_prompts[$category_slug] ?? '');

        // Guardar nuevo
        $category_prompts[sanitize_text_field($category_slug)] = wp_kses_post($content);
        $result = update_option('kzmcito_category_prompts', $category_prompts);

        // Limpiar cache
        $this->prompts_cache = [];

        if ($result) {
            error_log(sprintf(
                '[Kzmcito IA SEO] Prompt de categoría actualizado: %s (%d bytes)',
                $category_slug,
                strlen($content)
            ));
        }

        return $result;
    }

    /**
     * Backup de prompt de categoría
     */
    private function backup_category_prompt($category_slug, $old_content)
    {
        if (empty($old_content)) return;

        $backups = get_option('kzmcito_prompt_backups', []);
        $backups[$category_slug][] = [
            'content' => $old_content,
            'timestamp' => time(),
            'date' => current_time('mysql'),
        ];

        // Mantener solo los últimos 5 backups por categoría
        if (count($backups[$category_slug]) > 5) {
            $backups[$category_slug] = array_slice($backups[$category_slug], -5);
        }

        update_option('kzmcito_prompt_backups', $backups);
    }

    /**
     * Obtener prompts disponibles (global + categorías con prompt)
     * 
     * @return array Available prompts
     */
    public function get_available_prompts()
    {
        $prompts = [];

        // Prompt global
        $prompts['global'] = [
            'name' => __('Sistema Global', 'kzmcito-ia-seo'),
            'file' => 'system-prompt-global.md',
            'content' => $this->load_global_prompt(),
            'source' => 'file',
        ];

        // Prompts de categorías configuradas
        $category_prompts = get_option('kzmcito_category_prompts', []);
        $selected_categories = get_option('kzmcito_selected_categories', []);

        foreach ($selected_categories as $slug) {
            $term = get_term_by('slug', $slug, 'category');
            if ($term) {
                $prompts[$slug] = [
                    'name' => $term->name,
                    'file' => 'db:category_prompt',
                    'content' => $category_prompts[$slug] ?? '',
                    'source' => 'database',
                ];
            }
        }

        return $prompts;
    }

    /**
     * Guardar prompt global (override en DB)
     * 
     * @param string $content New content
     * @return bool Success
     */
    public function save_global_prompt($content)
    {
        // También guardar en archivo como respaldo
        $filepath = $this->prompts_dir . 'system-prompt-global.md';

        // Crear backup del archivo
        if (file_exists($filepath)) {
            $backup_dir = $this->prompts_dir . 'backups/';
            if (!file_exists($backup_dir)) {
                wp_mkdir_p($backup_dir);
            }
            $backup_file = $backup_dir . 'system-prompt-global.md.' . time() . '.bak';
            copy($filepath, $backup_file);
        }

        // Guardar en archivo
        file_put_contents($filepath, $content);

        // Guardar override en DB
        update_option('kzmcito_global_prompt_override', wp_kses_post($content));

        // Limpiar cache
        $this->prompts_cache = [];

        return true;
    }

    /**
     * Guardar prompt editado (compatibilidad con interfaz existente)
     * 
     * @param string $category Category slug or 'global'
     * @param string $content New content
     * @return bool Success
     */
    public function save_prompt($category, $content)
    {
        if ($category === 'global') {
            return $this->save_global_prompt($content);
        }

        return $this->save_category_prompt($category, $content);
    }

    /**
     * Obtener backups disponibles para una categoría
     * 
     * @param string $category Category slug or 'global'
     * @return array Backups
     */
    public function get_backups($category)
    {
        if ($category === 'global') {
            // Backups de archivo
            $backup_dir = $this->prompts_dir . 'backups/';
            if (!file_exists($backup_dir)) {
                return [];
            }

            $backups = [];
            $files = glob($backup_dir . 'system-prompt-global.md.*.bak');

            foreach ($files as $file) {
                preg_match('/\.(\d+)\.bak$/', $file, $matches);
                if (isset($matches[1])) {
                    $timestamp = $matches[1];
                    $backups[] = [
                        'timestamp' => $timestamp,
                        'date' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $timestamp),
                        'file' => basename($file),
                        'size' => filesize($file),
                    ];
                }
            }

            usort($backups, function ($a, $b) {
                return $b['timestamp'] - $a['timestamp'];
            });

            return $backups;
        }

        // Backups de categoría desde DB
        $all_backups = get_option('kzmcito_prompt_backups', []);
        return $all_backups[$category] ?? [];
    }

    /**
     * Restaurar prompt desde backup
     * 
     * @param string $category Category slug or 'global'
     * @param string $backup_timestamp Backup timestamp
     * @return bool Success
     */
    public function restore_prompt($category, $backup_timestamp)
    {
        if ($category === 'global') {
            $backup_file = $this->prompts_dir . 'backups/system-prompt-global.md.' . $backup_timestamp . '.bak';
            if (!file_exists($backup_file)) {
                return false;
            }

            $filepath = $this->prompts_dir . 'system-prompt-global.md';
            $result = copy($backup_file, $filepath);

            // Actualizar DB override también
            if ($result) {
                $content = file_get_contents($filepath);
                update_option('kzmcito_global_prompt_override', $content);
            }

            $this->prompts_cache = [];
            return $result;
        }

        // Restaurar categoría desde DB backup
        $all_backups = get_option('kzmcito_prompt_backups', []);
        $cat_backups = $all_backups[$category] ?? [];

        foreach ($cat_backups as $backup) {
            if ((string)$backup['timestamp'] === (string)$backup_timestamp) {
                return $this->save_category_prompt($category, $backup['content']);
            }
        }

        return false;
    }

    /**
     * Registrar evento de fallback
     * 
     * @param string $category Category that triggered fallback
     */
    private function log_fallback($category)
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[Kzmcito IA SEO] [FALLBACK MODE] Categoría "%s" no tiene prompt personalizado, usando solo prompt global',
                $category
            ));
        }

        // Incrementar contador de fallbacks
        $fallback_count = get_option('kzmcito_fallback_count', 0);
        update_option('kzmcito_fallback_count', $fallback_count + 1);

        // Registrar categoría que causó fallback
        $fallback_categories = get_option('kzmcito_fallback_categories', []);
        if (!in_array($category, $fallback_categories)) {
            $fallback_categories[] = $category;
            update_option('kzmcito_fallback_categories', $fallback_categories);
        }
    }

    /**
     * Validar integridad de prompts
     * 
     * @return array Validation results
     */
    public function validate_prompts()
    {
        $results = [];
        $prompts = $this->get_available_prompts();

        foreach ($prompts as $slug => $data) {
            $is_valid = !empty($data['content']);
            $word_count = str_word_count($data['content']);

            $results[$slug] = [
                'name' => $data['name'],
                'source' => $data['source'],
                'is_valid' => $is_valid,
                'word_count' => $word_count,
                'size' => strlen($data['content']),
            ];
        }

        return $results;
    }
}
