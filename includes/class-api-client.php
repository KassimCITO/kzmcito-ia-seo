<?php
/**
 * API Client - Cliente para modelos de IA
 * 
 * Soporte para Claude (Anthropic), Gemini (Google), GPT (OpenAI)
 * 
 * @package KzmcitoIASEO
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Kzmcito_IA_SEO_API_Client
{

    /**
     * Modelo de IA seleccionado
     */
    private $model;

    /**
     * API Key
     */
    private $api_key;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->model = get_option('kzmcito_ai_model', 'claude-sonnet');
        $this->load_api_key();
    }

    /**
     * Cargar API Key según el modelo
     */
    private function load_api_key()
    {
        if (strpos($this->model, 'claude') !== false) {
            $this->api_key = get_option('kzmcito_api_key_claude', '');
        } elseif (strpos($this->model, 'gemini') !== false) {
            $this->api_key = get_option('kzmcito_api_key_gemini', '');
        } elseif (strpos($this->model, 'gpt') !== false) {
            $this->api_key = get_option('kzmcito_api_key_openai', '');
        }
    }

    /**
     * Generar contenido usando IA
     * 
     * @param string $prompt Prompt
     * @param array $options Options
     * @return array|false Response
     */
    public function generate_content($prompt, $options = [])
    {
        if (empty($this->api_key)) {
            error_log('[Kzmcito IA SEO] API Key no configurada para modelo: ' . $this->model);
            return false;
        }

        $defaults = [
            'max_tokens' => 2000,
            'temperature' => 0.7,
        ];

        $options = wp_parse_args($options, $defaults);

        // Llamar al modelo correspondiente
        if (strpos($this->model, 'claude') !== false) {
            return $this->call_claude($prompt, $options);
        } elseif (strpos($this->model, 'gemini') !== false) {
            return $this->call_gemini($prompt, $options);
        } elseif (strpos($this->model, 'gpt') !== false) {
            return $this->call_openai($prompt, $options);
        }

        return false;
    }

    /**
     * Llamar a Claude (Anthropic)
     * 
     * @param string $prompt Prompt
     * @param array $options Options
     * @return array|false Response
     */
    private function call_claude($prompt, $options)
    {
        $endpoint = 'https://api.anthropic.com/v1/messages';

        // Determinar modelo específico
        $model_name = 'claude-3-sonnet-20240229';
        if ($this->model === 'claude-opus') {
            $model_name = 'claude-3-opus-20240229';
        }

        $body = [
            'model' => $model_name,
            'max_tokens' => $options['max_tokens'],
            'temperature' => $options['temperature'],
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ]
        ];

        $response = wp_remote_post($endpoint, [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $this->api_key,
                'anthropic-version' => '2023-06-01'
            ],
            'body' => wp_json_encode($body),
            'timeout' => 60,
        ]);

        if (is_wp_error($response)) {
            error_log('[Kzmcito IA SEO] Error en llamada a Claude: ' . $response->get_error_message());
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['content'][0]['text'])) {
            return [
                'content' => $body['content'][0]['text'],
                'model' => $model_name,
                'usage' => $body['usage'] ?? []
            ];
        }

        return false;
    }

    /**
     * Llamar a Gemini (Google)
     * 
     * @param string $prompt Prompt
     * @param array $options Options
     * @return array|false Response
     */
    private function call_gemini($prompt, $options)
    {
        $model_name = 'gemini-1.5-pro';
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model_name}:generateContent?key={$this->api_key}";

        $body = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => $options['temperature'],
                'maxOutputTokens' => $options['max_tokens'],
            ]
        ];

        $response = wp_remote_post($endpoint, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($body),
            'timeout' => 60,
        ]);

        if (is_wp_error($response)) {
            error_log('[Kzmcito IA SEO] Error en llamada a Gemini: ' . $response->get_error_message());
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['candidates'][0]['content']['parts'][0]['text'])) {
            return [
                'content' => $body['candidates'][0]['content']['parts'][0]['text'],
                'model' => $model_name,
                'usage' => $body['usageMetadata'] ?? []
            ];
        }

        return false;
    }

    /**
     * Llamar a OpenAI (GPT)
     * 
     * @param string $prompt Prompt
     * @param array $options Options
     * @return array|false Response
     */
    private function call_openai($prompt, $options)
    {
        $endpoint = 'https://api.openai.com/v1/chat/completions';

        $model_name = 'gpt-4-turbo-preview';
        if ($this->model === 'gpt-3.5') {
            $model_name = 'gpt-3.5-turbo';
        }

        $body = [
            'model' => $model_name,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => $options['max_tokens'],
            'temperature' => $options['temperature'],
        ];

        $response = wp_remote_post($endpoint, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key,
            ],
            'body' => wp_json_encode($body),
            'timeout' => 60,
        ]);

        if (is_wp_error($response)) {
            error_log('[Kzmcito IA SEO] Error en llamada a OpenAI: ' . $response->get_error_message());
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['choices'][0]['message']['content'])) {
            return [
                'content' => $body['choices'][0]['message']['content'],
                'model' => $model_name,
                'usage' => $body['usage'] ?? []
            ];
        }

        return false;
    }

    /**
     * Verificar conexión con la API
     * 
     * @return array Result
     */
    public function test_connection()
    {
        $test_prompt = "Responde con 'OK' si recibes este mensaje.";

        $response = $this->generate_content($test_prompt, [
            'max_tokens' => 10,
            'temperature' => 0,
        ]);

        if ($response) {
            return [
                'success' => true,
                'message' => sprintf(__('Conexión exitosa con %s', 'kzmcito-ia-seo'), $this->model),
                'model' => $response['model'],
            ];
        }

        return [
            'success' => false,
            'message' => sprintf(__('Error de conexión con %s', 'kzmcito-ia-seo'), $this->model),
        ];
    }
}
