<?php
/**
 * Content Processor - Procesador de contenido (Fase 2)
 * 
 * Limpieza, TOC, FAQ, Expansión, encabezados y Sumario Reuters
 * 
 * @package KzmcitoIASEO
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Kzmcito_IA_SEO_Content_Processor
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
     * Limpiar contenido de basura de Office y estilos inline
     * 
     * @param string $content Content to clean
     * @return string Cleaned content
     */
    public function clean_content($content)
    {
        // Eliminar tags mso- de Office
        $content = preg_replace('/<(\w+)[^>]*\s+class="[^"]*mso-[^"]*"[^>]*>/i', '<$1>', $content);
        $content = preg_replace('/<(\w+)[^>]*\s+style="[^"]*mso-[^"]*"[^>]*>/i', '<$1>', $content);

        // Eliminar atributos style inline
        $content = preg_replace('/\s*style\s*=\s*"[^"]*"/i', '', $content);

        // Eliminar spans vacíos
        $content = preg_replace('/<span[^>]*>\s*<\/span>/i', '', $content);

        // Eliminar divs vacíos
        $content = preg_replace('/<div[^>]*>\s*<\/div>/i', '', $content);

        // Normalizar espacios en blanco
        $content = preg_replace('/\s+/', ' ', $content);
        $content = preg_replace('/>\s+</', '><', $content);

        // Reemplazar comillas latinas/símbolos por comillas tipográficas estándar
        $content = str_replace(['«', '»'], '"', $content);

        // Limpiar con wp_kses_post para seguridad
        $content = wp_kses_post($content);

        // Linkificar teléfonos y correos
        $content = $this->linkify_contacts($content);

        // Renderizar mapas de Google si existen marcadores
        $content = $this->render_google_maps($content);

        return trim($content);
    }

    /**
     * Renderizar mapas de Google a partir de marcadores [kzmcito_google_map location="..."]
     * 
     * @param string $content Content
     * @return string Content with maps
     */
    public function render_google_maps($content)
    {
        $api_key = get_option('kzmcito_google_maps_api_key', '');

        if (empty($api_key)) {
            // Si no hay API Key, eliminar los marcadores para no ensuciar el post
            return preg_replace('/\[kzmcito_google_map location=".*?"\]/', '', $content);
        }

        return preg_replace_callback(
            '/\[kzmcito_google_map location="(.*?)"\]/',
            function ($matches) use ($api_key) {
                $location = urlencode($matches[1]);
                $map_html = '<div class="kzmcito-google-map-container" style="margin: 25px 0; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">';
                $map_html .= sprintf(
                    '<iframe width="100%%" height="400" frameborder="0" style="border:0" 
                    src="https://www.google.com/maps/embed/v1/place?key=%s&q=%s" allowfullscreen></iframe>',
                    esc_attr($api_key),
                    $location
                );
                $map_html .= '</div>';
                return $map_html;
            },
            $content
        );
    }

    /**
     * Convertir teléfonos y correos en texto plano a enlaces HTML
     * 
     * @param string $content Content
     * @return string Linkified content
     */
    public function linkify_contacts($content)
    {
        // 1. Linkificar Correos Electrónicos
        $content = preg_replace_callback(
            '/(?<!href=["\'])(?<!>)\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/i',
            function ($matches) {
                return '<a href="mailto:' . esc_attr($matches[0]) . '">' . esc_html($matches[0]) . '</a>';
            },
            $content
        );

        // 2. Linkificar Teléfonos
        $content = preg_replace_callback(
            '/(?<!href=["\'])(?<!>)\b(\+?\d{1,3}[-.\\s]?)?\(?\d{3}\)?[-.\\s]?\d{3}[-.\\s]?\d{4}\b/',
            function ($matches) {
                $clean_tel = preg_replace('/\D/', '', $matches[0]);
                return '<a href="tel:' . esc_attr($clean_tel) . '">' . esc_html($matches[0]) . '</a>';
            },
            $content
        );

        return $content;
    }

    /**
     * ==========================================
     * SUMARIO REUTERS (Key Takeaways)
     * ==========================================
     */

    /**
     * Insertar sumario si no existe
     * 
     * @param string $content Post content
     * @param string $title Post title
     * @param array $analysis Analysis data
     * @return string Content with summary
     */
    public function maybe_insert_summary($content, $title, $analysis)
    {
        // Ya tiene sumario, no duplicar
        if ($analysis['has_summary']) {
            return $content;
        }

        // Generar sumario con IA
        $summary_items = $this->generate_summary($content, $title, $analysis);

        if (empty($summary_items)) {
            return $content;
        }

        // Construir HTML del sumario estilo Reuters
        $summary_html = $this->build_reuters_summary_html($summary_items);

        // Insertar después del primer párrafo
        $first_p_pos = strpos($content, '</p>');
        if ($first_p_pos !== false) {
            $content = substr_replace($content, '</p>' . "\n\n" . $summary_html . "\n\n", $first_p_pos, 4);
        } else {
            $content = $summary_html . "\n\n" . $content;
        }

        return $content;
    }

    /**
     * Generar sumario usando IA
     * 
     * @param string $content Post content
     * @param string $title Post title
     * @param array $analysis Analysis data
     * @return array Summary bullet points
     */
    private function generate_summary($content, $title, $analysis)
    {
        $summary_prompt = "";

        // Añadir prompt del sistema si está disponible
        if (!empty($analysis['prompts']['merged'])) {
            $summary_prompt .= $analysis['prompts']['merged'] . "\n\n";
        }

        $summary_prompt .= "# TAREA: GENERAR SUMARIO EJECUTIVO (KEY TAKEAWAYS)\n\n";
        $summary_prompt .= "## Título del artículo:\n{$title}\n\n";
        $summary_prompt .= "## Contenido del artículo:\n" . wp_trim_words(strip_tags($content), 500) . "\n\n";
        $summary_prompt .= "## Instrucciones:\n";
        $summary_prompt .= "1. Genera entre 3 y 5 ideas clave (key takeaways) que resuman el artículo\n";
        $summary_prompt .= "2. Cada punto debe ser una oración concisa de 10-20 palabras\n";
        $summary_prompt .= "3. Los puntos deben dar al lector una idea clara de lo que tratará el artículo\n";
        $summary_prompt .= "4. Usa lenguaje directo, informativo y objetivo (estilo Reuters/AP)\n";
        $summary_prompt .= "5. NO incluyas opiniones, solo hechos y datos clave\n";
        $summary_prompt .= "6. Devuelve SOLO un array JSON con las ideas como strings:\n";
        $summary_prompt .= '   ["Punto 1...", "Punto 2...", "Punto 3..."]' . "\n\n";
        $summary_prompt .= "Sumario JSON:\n";

        $response = $this->api_client->generate_content($summary_prompt, [
            'max_tokens' => 500,
            'temperature' => 0.5,
        ]);

        if ($response && !empty($response['content'])) {
            // Extraer JSON de la respuesta
            $json_match = preg_match('/\[.*\]/s', $response['content'], $matches);
            if ($json_match) {
                $items = json_decode($matches[0], true);
                if (is_array($items) && count($items) >= 3 && count($items) <= 5) {
                    return $items;
                }
            }
        }

        // Fallback: extraer oraciones clave del contenido
        return $this->extract_key_sentences($content, $title);
    }

    /**
     * Fallback: Extraer oraciones clave del contenido
     * 
     * @param string $content Post content
     * @param string $title Post title  
     * @return array Key sentences (3-5)
     */
    private function extract_key_sentences($content, $title)
    {
        $text = strip_tags($content);
        $sentences = preg_split('/[.!?]+/', $text);
        $sentences = array_filter(array_map('trim', $sentences), function ($s) {
            return strlen($s) > 30 && strlen($s) < 200;
        });

        if (count($sentences) < 3) {
            return [];
        }

        // Tomar las primeras 3-4 oraciones significativas
        return array_slice(array_values($sentences), 0, min(4, count($sentences)));
    }

    /**
     * Construir HTML del sumario estilo Reuters
     * 
     * @param array $items Summary items
     * @return string HTML
     */
    private function build_reuters_summary_html($items)
    {
        $html = '<div class="kzmcito-summary kzmcito-key-takeaways" role="region" aria-label="' . esc_attr__('Ideas clave', 'kzmcito-ia-seo') . '">';
        $html .= '<div class="kzmcito-summary-header">';
        $html .= '<span class="kzmcito-summary-icon" aria-hidden="true">&#9679;</span>';
        $html .= '<span class="kzmcito-summary-title">' . esc_html__('Ideas Clave', 'kzmcito-ia-seo') . '</span>';
        $html .= '</div>';
        $html .= '<ul class="kzmcito-summary-list">';

        foreach ($items as $item) {
            $html .= '<li class="kzmcito-summary-item">';
            $html .= '<span class="kzmcito-summary-bullet" aria-hidden="true"></span>';
            $html .= '<span class="kzmcito-summary-text">' . esc_html(trim($item)) . '</span>';
            $html .= '</li>';
        }

        $html .= '</ul>';
        $html .= '</div>';

        return $html;
    }

    /**
     * ==========================================
     * EXPANSIÓN DE CONTENIDO
     * ==========================================
     */

    /**
     * Expandir contenido corto usando IA
     * 
     * @param string $content Original content
     * @param string $title Post title
     * @param array $prompts Prompts data
     * @param string $category Category
     * @return string Expanded content
     */
    public function expand_content($content, $title, $prompts, $category)
    {
        $min_words = get_option('kzmcito_min_words', 850);
        $max_words = get_option('kzmcito_max_words', 1200);
        $current_words = str_word_count(strip_tags($content));

        if ($current_words >= $min_words) {
            return $content;
        }

        // Preparar prompt para expansión
        $expansion_prompt = $this->build_expansion_prompt($content, $title, $prompts, $min_words, $max_words);

        // Llamar a la API
        $expanded = $this->api_client->generate_content($expansion_prompt, [
            'max_tokens' => 2000,
            'temperature' => 0.7,
        ]);

        if ($expanded && !empty($expanded['content'])) {
            return wp_kses_post($expanded['content']);
        }

        return $content;
    }

    /**
     * Construir prompt para expansión de contenido
     */
    private function build_expansion_prompt($content, $title, $prompts, $min_words, $max_words)
    {
        $prompt = $prompts['merged'] . "\n\n";
        $prompt .= "# TAREA: EXPANSIÓN DE CONTENIDO\n\n";
        $prompt .= "## Título del artículo:\n{$title}\n\n";
        $prompt .= "## Contenido actual:\n{$content}\n\n";
        $prompt .= "## Instrucciones:\n";
        $prompt .= "1. El contenido actual tiene " . str_word_count(strip_tags($content)) . " palabras\n";
        $prompt .= "2. Debes expandirlo a un rango de {$min_words}-{$max_words} palabras\n";
        $prompt .= "3. Mantén el rigor periodístico y la coherencia editorial\n";
        $prompt .= "4. Agrega información relevante, contexto y detalles\n";
        $prompt .= "5. NO alteres scripts, embeds o shortcodes originales\n";
        $prompt .= "6. Devuelve SOLO el contenido expandido en HTML limpio\n\n";
        $prompt .= "Contenido expandido:\n";

        return $prompt;
    }

    /**
     * ==========================================
     * ENCABEZADOS
     * ==========================================
     */

    /**
     * Mejorar encabezados H2-H4
     * 
     * @param string $content Content
     * @param array $analysis Analysis data
     * @return string Content with enhanced headings
     */
    public function enhance_headings($content, $analysis)
    {
        // Si ya tiene suficientes encabezados, no modificar
        if ($analysis['has_headings']['h2'] >= 3) {
            return $content;
        }

        // Dividir contenido en párrafos
        $paragraphs = explode('</p>', $content);

        // Analizar párrafos y agregar encabezados donde sea apropiado
        $enhanced_content = '';
        $heading_count = 0;

        foreach ($paragraphs as $index => $paragraph) {
            if (empty(trim(strip_tags($paragraph)))) {
                continue;
            }

            // Agregar encabezado cada 3-4 párrafos
            if ($index > 0 && $index % 3 === 0 && $heading_count < 5) {
                $heading_text = $this->generate_heading_from_paragraph($paragraph);
                if ($heading_text) {
                    $enhanced_content .= "<h2>{$heading_text}</h2>\n";
                    $heading_count++;
                }
            }

            $enhanced_content .= $paragraph;
            if (!str_ends_with($paragraph, '</p>')) {
                $enhanced_content .= '</p>';
            }
        }

        return $enhanced_content;
    }

    /**
     * Generar encabezado desde párrafo
     */
    private function generate_heading_from_paragraph($paragraph)
    {
        $text = strip_tags($paragraph);
        $words = explode(' ', $text);

        if (count($words) < 5) {
            return false;
        }

        // Tomar las primeras 5-8 palabras como encabezado
        $heading_words = array_slice($words, 0, min(8, count($words)));
        $heading = implode(' ', $heading_words);

        // Capitalizar primera letra
        $heading = ucfirst($heading);

        // Eliminar puntuación final
        $heading = rtrim($heading, '.,;:!?');

        return $heading;
    }

    /**
     * ==========================================
     * TABLE OF CONTENTS (TOC)
     * ==========================================
     */

    /**
     * Insertar tabla de contenidos (TOC)
     * 
     * @param string $content Content
     * @return string Content with TOC
     */
    public function insert_toc($content)
    {
        // Extraer todos los encabezados H2
        preg_match_all('/<h2[^>]*>(.*?)<\/h2>/i', $content, $matches);

        if (empty($matches[1]) || count($matches[1]) < 2) {
            return $content;
        }

        // Construir TOC
        $toc = '<div class="kzmcito-toc">';
        $toc .= '<h2 class="toc-title">' . __('Tabla de Contenidos', 'kzmcito-ia-seo') . '</h2>';
        $toc .= '<ul class="toc-list">';

        foreach ($matches[1] as $index => $heading) {
            $heading_text = strip_tags($heading);
            $heading_id = sanitize_title($heading_text);

            // Agregar ID al encabezado en el contenido
            $content = preg_replace(
                '/<h2[^>]*>' . preg_quote($heading, '/') . '<\/h2>/i',
                '<h2 id="' . $heading_id . '">' . $heading . '</h2>',
                $content,
                1
            );

            $toc .= '<li><a href="#' . $heading_id . '">' . esc_html($heading_text) . '</a></li>';
        }

        $toc .= '</ul>';
        $toc .= '</div>';

        // Insertar TOC después del sumario si existe, o después del primer párrafo
        $summary_end = strpos($content, '</div><!-- /kzmcito-summary -->');
        if ($summary_end !== false) {
            $insert_pos = $summary_end + strlen('</div><!-- /kzmcito-summary -->');
            $content = substr_replace($content, "\n\n" . $toc . "\n\n", $insert_pos, 0);
        } else {
            $first_p_pos = strpos($content, '</p>');
            if ($first_p_pos !== false) {
                $content = substr_replace($content, '</p>' . "\n\n" . $toc . "\n\n", $first_p_pos, 4);
            } else {
                $content = $toc . "\n\n" . $content;
            }
        }

        return $content;
    }

    /**
     * ==========================================
     * FAQ
     * ==========================================
     */

    /**
     * Insertar FAQ con Schema JSON-LD
     * 
     * @param string $content Content
     * @param array $analysis Analysis data
     * @return string Content with FAQ
     */
    public function insert_faq($content, $analysis)
    {
        // Generar FAQ usando IA
        $faq_data = $this->generate_faq($content, $analysis);

        if (empty($faq_data)) {
            return $content;
        }

        // Construir HTML del FAQ
        $faq_html = '<div class="kzmcito-faq">';
        $faq_html .= '<h2 class="faq-title">' . __('Preguntas Frecuentes', 'kzmcito-ia-seo') . '</h2>';

        $schema_items = [];

        foreach ($faq_data as $item) {
            $faq_html .= '<div class="faq-item">';
            $faq_html .= '<h3 class="faq-question">' . esc_html($item['question']) . '</h3>';
            $faq_html .= '<div class="faq-answer">' . wp_kses_post($item['answer']) . '</div>';
            $faq_html .= '</div>';

            // Agregar a schema
            $schema_items[] = [
                '@type' => 'Question',
                'name' => $item['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => strip_tags($item['answer'])
                ]
            ];
        }

        $faq_html .= '</div>';

        // Agregar Schema JSON-LD
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $schema_items
        ];

        $faq_html .= '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';

        // Insertar FAQ al final del contenido
        $content .= "\n\n" . $faq_html;

        return $content;
    }

    /**
     * Generar FAQ usando IA
     */
    private function generate_faq($content, $analysis)
    {
        // Preparar prompt para FAQ
        $faq_prompt = $analysis['prompts']['merged'] . "\n\n";
        $faq_prompt .= "# TAREA: GENERAR FAQ\n\n";
        $faq_prompt .= "## Contenido del artículo:\n{$content}\n\n";
        $faq_prompt .= "## Instrucciones:\n";
        $faq_prompt .= "1. Genera 3-5 preguntas frecuentes relevantes basadas en el contenido\n";
        $faq_prompt .= "2. Cada pregunta debe tener una respuesta concisa (50-100 palabras)\n";
        $faq_prompt .= "3. Las preguntas deben ser naturales y útiles para el lector\n";
        $faq_prompt .= "4. Devuelve el resultado en formato JSON:\n";
        $faq_prompt .= '   [{"question": "...", "answer": "..."}, ...]' . "\n\n";
        $faq_prompt .= "FAQ en JSON:\n";

        // Llamar a la API
        $response = $this->api_client->generate_content($faq_prompt, [
            'max_tokens' => 1000,
            'temperature' => 0.7,
        ]);

        if ($response && !empty($response['content'])) {
            // Extraer JSON de la respuesta
            $json_match = preg_match('/\[.*\]/s', $response['content'], $matches);
            if ($json_match) {
                $faq_data = json_decode($matches[0], true);
                if (is_array($faq_data)) {
                    return $faq_data;
                }
            }
        }

        // Fallback: FAQ básico
        return [
            [
                'question' => '¿De qué trata este artículo?',
                'answer' => 'Este artículo proporciona información detallada sobre el tema tratado.'
            ]
        ];
    }
}
