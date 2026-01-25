<?php
/**
 * Content Processor - Procesador de contenido (Fase 2)
 * 
 * Limpieza, TOC, FAQ, Expansión y mejora de encabezados
 * 
 * @package KzmcitoIASEO
 * @since 2.0.0
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

        // Limpiar con wp_kses_post para seguridad
        $content = wp_kses_post($content);

        return trim($content);
    }

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
     * 
     * @param string $content Original content
     * @param string $title Post title
     * @param array $prompts Prompts data
     * @param int $min_words Minimum words
     * @param int $max_words Maximum words
     * @return string Expansion prompt
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
     * 
     * @param string $paragraph Paragraph content
     * @return string|false Heading text or false
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

        // Insertar TOC después del primer párrafo
        $first_p_pos = strpos($content, '</p>');
        if ($first_p_pos !== false) {
            $content = substr_replace($content, '</p>' . "\n\n" . $toc . "\n\n", $first_p_pos, 4);
        } else {
            $content = $toc . "\n\n" . $content;
        }

        return $content;
    }

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
     * 
     * @param string $content Content
     * @param array $analysis Analysis data
     * @return array FAQ items
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
        $faq_prompt .= '   [{"question": "...", "answer": "..."}, ...]\n\n';
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
