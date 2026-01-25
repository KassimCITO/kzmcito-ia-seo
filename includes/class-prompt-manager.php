<?php
/**
 * Prompt Manager - Gestor de prompts por categoría
 * 
 * Carga jerárquica: System Prompt Global + Prompt de Categoría
 * 
 * @package KzmcitoIASEO
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Kzmcito_IA_SEO_Prompt_Manager
{

    /**
     * Directorio de prompts
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
        $global_prompt = $this->load_prompt_file('system-prompt-global.md');

        // Cargar Prompt de Categoría
        $category_prompt = '';
        if ($category !== 'global') {
            $category_prompt = $this->load_category_prompt($category);
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
     * Cargar prompt de categoría
     * 
     * @param string $category Category slug
     * @return string Category prompt content
     */
    private function load_category_prompt($category)
    {
        // Mapeo de categorías a archivos
        $category_files = [
            'michoacan' => '01-michoacan.md',
            'educacion' => '02-educacion.md',
            'entretenimiento' => '03-entretenimiento.md',
            'justicia' => '04-justicia.md',
            'salud' => '05-salud.md',
            'seguridad' => '06-seguridad.md',
        ];

        if (!isset($category_files[$category])) {
            return '';
        }

        return $this->load_prompt_file($category_files[$category]);
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
            // Modo Fallback: solo prompt global
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
     * Obtener todos los prompts disponibles
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
            'content' => $this->load_prompt_file('system-prompt-global.md'),
        ];

        // Prompts de categoría
        $categories = [
            'michoacan' => ['name' => 'Michoacán', 'file' => '01-michoacan.md'],
            'educacion' => ['name' => 'Educación', 'file' => '02-educacion.md'],
            'entretenimiento' => ['name' => 'Entretenimiento', 'file' => '03-entretenimiento.md'],
            'justicia' => ['name' => 'Justicia', 'file' => '04-justicia.md'],
            'salud' => ['name' => 'Salud', 'file' => '05-salud.md'],
            'seguridad' => ['name' => 'Seguridad', 'file' => '06-seguridad.md'],
        ];

        foreach ($categories as $slug => $data) {
            $prompts[$slug] = [
                'name' => $data['name'],
                'file' => $data['file'],
                'content' => $this->load_prompt_file($data['file']),
            ];
        }

        return $prompts;
    }

    /**
     * Guardar prompt editado
     * 
     * @param string $category Category slug or 'global'
     * @param string $content New content
     * @return bool Success
     */
    public function save_prompt($category, $content)
    {
        // Sanitizar contenido
        $content = wp_kses_post($content);

        // Determinar archivo
        if ($category === 'global') {
            $filename = 'system-prompt-global.md';
        } else {
            $category_files = [
                'michoacan' => '01-michoacan.md',
                'educacion' => '02-educacion.md',
                'entretenimiento' => '03-entretenimiento.md',
                'justicia' => '04-justicia.md',
                'salud' => '05-salud.md',
                'seguridad' => '06-seguridad.md',
            ];

            if (!isset($category_files[$category])) {
                return false;
            }

            $filename = $category_files[$category];
        }

        $filepath = $this->prompts_dir . $filename;

        // Crear backup
        if (file_exists($filepath)) {
            $backup_dir = $this->prompts_dir . 'backups/';
            if (!file_exists($backup_dir)) {
                wp_mkdir_p($backup_dir);
            }

            $backup_file = $backup_dir . $filename . '.' . time() . '.bak';
            copy($filepath, $backup_file);
        }

        // Guardar nuevo contenido
        $result = file_put_contents($filepath, $content);

        // Limpiar cache
        $this->prompts_cache = [];

        // Log
        if ($result !== false) {
            error_log(sprintf(
                '[Kzmcito IA SEO] Prompt actualizado: %s (%d bytes)',
                $category,
                $result
            ));
        }

        return $result !== false;
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
        // Determinar archivo
        if ($category === 'global') {
            $filename = 'system-prompt-global.md';
        } else {
            $category_files = [
                'michoacan' => '01-michoacan.md',
                'educacion' => '02-educacion.md',
                'entretenimiento' => '03-entretenimiento.md',
                'justicia' => '04-justicia.md',
                'salud' => '05-salud.md',
                'seguridad' => '06-seguridad.md',
            ];

            if (!isset($category_files[$category])) {
                return false;
            }

            $filename = $category_files[$category];
        }

        $backup_file = $this->prompts_dir . 'backups/' . $filename . '.' . $backup_timestamp . '.bak';

        if (!file_exists($backup_file)) {
            return false;
        }

        $filepath = $this->prompts_dir . $filename;
        $result = copy($backup_file, $filepath);

        // Limpiar cache
        $this->prompts_cache = [];

        return $result;
    }

    /**
     * Obtener backups disponibles
     * 
     * @param string $category Category slug or 'global'
     * @return array Backups
     */
    public function get_backups($category)
    {
        // Determinar archivo
        if ($category === 'global') {
            $filename = 'system-prompt-global.md';
        } else {
            $category_files = [
                'michoacan' => '01-michoacan.md',
                'educacion' => '02-educacion.md',
                'entretenimiento' => '03-entretenimiento.md',
                'justicia' => '04-justicia.md',
                'salud' => '05-salud.md',
                'seguridad' => '06-seguridad.md',
            ];

            if (!isset($category_files[$category])) {
                return [];
            }

            $filename = $category_files[$category];
        }

        $backup_dir = $this->prompts_dir . 'backups/';

        if (!file_exists($backup_dir)) {
            return [];
        }

        $backups = [];
        $files = glob($backup_dir . $filename . '.*.bak');

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

        // Ordenar por timestamp descendente
        usort($backups, function ($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });

        return $backups;
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
                '[Kzmcito IA SEO] [FALLBACK MODE] Categoría "%s" no encontrada, usando solo prompt global',
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
                'file' => $data['file'],
                'exists' => file_exists($this->prompts_dir . $data['file']),
                'is_valid' => $is_valid,
                'word_count' => $word_count,
                'size' => file_exists($this->prompts_dir . $data['file']) ? filesize($this->prompts_dir . $data['file']) : 0,
            ];
        }

        return $results;
    }
}
