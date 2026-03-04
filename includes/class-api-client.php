<?php
/**
 * API Client - Cliente para modelos de IA con fallback automático
 * 
 * Soporte para: Claude, Gemini, GPT, DeepSeek, Mistral, Groq
 * Fallback: Cascada automática si el modelo principal falla
 * 
 * @package KzmcitoIASEO
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Kzmcito_IA_SEO_API_Client
{

    /**
     * Modelo de IA principal seleccionado
     */
    private $model;

    /**
     * API Keys por proveedor
     */
    private $api_keys = [];

    /**
     * Orden de fallback (cascada)
     */
    private $fallback_order = [];

    /**
     * Registro de último proveedor usado exitosamente
     */
    private $last_successful_provider = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->model = get_option('kzmcito_ai_model', 'claude-sonnet');
        $this->load_all_api_keys();
        $this->build_fallback_order();
    }

    /**
     * Cargar todas las API Keys configuradas
     */
    private function load_all_api_keys()
    {
        $this->api_keys = [
            'claude'   => get_option('kzmcito_api_key_claude', ''),
            'gemini'   => get_option('kzmcito_api_key_gemini', ''),
            'openai'   => get_option('kzmcito_api_key_openai', ''),
            'deepseek' => get_option('kzmcito_api_key_deepseek', ''),
            'mistral'  => get_option('kzmcito_api_key_mistral', ''),
            'groq'     => get_option('kzmcito_api_key_groq', ''),
        ];
    }

    /**
     * Construir orden de fallback basado en modelo principal y keys disponibles
     */
    private function build_fallback_order()
    {
        // Orden de prioridad personalizable: modelo principal primero, luego los demás con key
        $custom_order = get_option('kzmcito_fallback_order', '');

        if (!empty($custom_order)) {
            $this->fallback_order = array_filter(
                array_map('trim', explode(',', $custom_order)),
                function ($provider) {
                    return !empty($this->api_keys[$this->normalize_provider($provider)] ?? '');
                }
            );
        }

        // Si no hay orden personalizado, construir automáticamente
        if (empty($this->fallback_order)) {
            // El modelo principal va primero
            $this->fallback_order = [$this->model];

            // Agregar los demás proveedores con API key válida
            $all_providers = ['claude-sonnet', 'gemini-pro', 'gpt-4', 'deepseek-chat', 'mistral-large', 'groq-llama'];

            foreach ($all_providers as $provider) {
                if ($provider === $this->model) continue;
                $normalized = $this->normalize_provider($provider);
                if (!empty($this->api_keys[$normalized])) {
                    $this->fallback_order[] = $provider;
                }
            }
        }
    }

    /**
     * Normalizar nombre de proveedor para obtener API key
     */
    private function normalize_provider($model)
    {
        if (strpos($model, 'claude') !== false) return 'claude';
        if (strpos($model, 'gemini') !== false) return 'gemini';
        if (strpos($model, 'gpt') !== false || strpos($model, 'codex') !== false) return 'openai';
        if (strpos($model, 'deepseek') !== false) return 'deepseek';
        if (strpos($model, 'mistral') !== false) return 'mistral';
        if (strpos($model, 'groq') !== false || strpos($model, 'llama') !== false) return 'groq';
        return '';
    }

    /**
     * Generar contenido usando IA con fallback automático
     * 
     * @param string $prompt Prompt
     * @param array $options Options
     * @return array|false Response
     */
    public function generate_content($prompt, $options = [])
    {
        $defaults = [
            'max_tokens' => 2000,
            'temperature' => 0.7,
        ];

        $options = wp_parse_args($options, $defaults);

        // Intentar cada proveedor en orden de fallback
        foreach ($this->fallback_order as $model_key) {
            $provider = $this->normalize_provider($model_key);
            $api_key = $this->api_keys[$provider] ?? '';

            if (empty($api_key)) {
                continue;
            }

            $this->log_attempt($model_key, 'Intentando con modelo: ' . $model_key);

            $result = $this->call_provider($model_key, $api_key, $prompt, $options);

            if ($result !== false) {
                $this->last_successful_provider = $model_key;
                $result['fallback_used'] = ($model_key !== $this->model);
                $this->log_attempt($model_key, 'Éxito con modelo: ' . $model_key);
                return $result;
            }

            $this->log_attempt($model_key, 'Fallo con modelo: ' . $model_key . ', intentando siguiente...');
        }

        error_log('[Kzmcito IA SEO] CRÍTICO: Todos los proveedores fallaron. Sin respuesta de IA.');
        return false;
    }

    /**
     * Llamar al proveedor correcto según el modelo
     */
    private function call_provider($model_key, $api_key, $prompt, $options)
    {
        $provider = $this->normalize_provider($model_key);

        switch ($provider) {
            case 'claude':
                return $this->call_claude($api_key, $prompt, $options, $model_key);
            case 'gemini':
                return $this->call_gemini($api_key, $prompt, $options);
            case 'openai':
                return $this->call_openai($api_key, $prompt, $options, $model_key);
            case 'deepseek':
                return $this->call_deepseek($api_key, $prompt, $options);
            case 'mistral':
                return $this->call_mistral($api_key, $prompt, $options);
            case 'groq':
                return $this->call_groq($api_key, $prompt, $options);
            default:
                return false;
        }
    }

    /**
     * Llamar a Claude (Anthropic)
     */
    private function call_claude($api_key, $prompt, $options, $model_key = 'claude-sonnet')
    {
        $endpoint = 'https://api.anthropic.com/v1/messages';

        $model_name = 'claude-3-5-sonnet-20241022';
        if ($model_key === 'claude-opus') {
            $model_name = 'claude-3-opus-20240229';
        } elseif ($model_key === 'claude-haiku') {
            $model_name = 'claude-3-haiku-20240307';
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
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01'
            ],
            'body' => wp_json_encode($body),
            'timeout' => 90,
        ]);

        if (is_wp_error($response)) {
            error_log('[Kzmcito IA SEO] Error en llamada a Claude: ' . $response->get_error_message());
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code >= 400) {
            error_log('[Kzmcito IA SEO] Claude HTTP ' . $status_code . ': ' . wp_remote_retrieve_body($response));
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['content'][0]['text'])) {
            return [
                'content' => $body['content'][0]['text'],
                'model' => $model_name,
                'provider' => 'claude',
                'usage' => $body['usage'] ?? []
            ];
        }

        return false;
    }

    /**
     * Llamar a Gemini (Google)
     */
    private function call_gemini($api_key, $prompt, $options)
    {
        $model_name = 'gemini-2.0-flash';
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model_name}:generateContent?key={$api_key}";

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
            'timeout' => 90,
        ]);

        if (is_wp_error($response)) {
            error_log('[Kzmcito IA SEO] Error en llamada a Gemini: ' . $response->get_error_message());
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code >= 400) {
            error_log('[Kzmcito IA SEO] Gemini HTTP ' . $status_code . ': ' . wp_remote_retrieve_body($response));
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['candidates'][0]['content']['parts'][0]['text'])) {
            return [
                'content' => $body['candidates'][0]['content']['parts'][0]['text'],
                'model' => $model_name,
                'provider' => 'gemini',
                'usage' => $body['usageMetadata'] ?? []
            ];
        }

        return false;
    }

    /**
     * Llamar a OpenAI (GPT / Codex)
     */
    private function call_openai($api_key, $prompt, $options, $model_key = 'gpt-4')
    {
        $endpoint = 'https://api.openai.com/v1/chat/completions';

        $model_name = 'gpt-4-turbo-preview';
        if ($model_key === 'gpt-3.5') {
            $model_name = 'gpt-3.5-turbo';
        } elseif ($model_key === 'gpt-4o') {
            $model_name = 'gpt-4o';
        } elseif ($model_key === 'gpt-4o-mini') {
            $model_name = 'gpt-4o-mini';
        } elseif ($model_key === 'codex') {
            $model_name = 'gpt-4o'; // Codex uses GPT-4o as base
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
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'body' => wp_json_encode($body),
            'timeout' => 90,
        ]);

        if (is_wp_error($response)) {
            error_log('[Kzmcito IA SEO] Error en llamada a OpenAI: ' . $response->get_error_message());
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code >= 400) {
            error_log('[Kzmcito IA SEO] OpenAI HTTP ' . $status_code . ': ' . wp_remote_retrieve_body($response));
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['choices'][0]['message']['content'])) {
            return [
                'content' => $body['choices'][0]['message']['content'],
                'model' => $model_name,
                'provider' => 'openai',
                'usage' => $body['usage'] ?? []
            ];
        }

        return false;
    }

    /**
     * Llamar a DeepSeek
     */
    private function call_deepseek($api_key, $prompt, $options)
    {
        $endpoint = 'https://api.deepseek.com/chat/completions';
        $model_name = 'deepseek-chat';

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
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'body' => wp_json_encode($body),
            'timeout' => 90,
        ]);

        if (is_wp_error($response)) {
            error_log('[Kzmcito IA SEO] Error en llamada a DeepSeek: ' . $response->get_error_message());
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code >= 400) {
            error_log('[Kzmcito IA SEO] DeepSeek HTTP ' . $status_code . ': ' . wp_remote_retrieve_body($response));
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['choices'][0]['message']['content'])) {
            return [
                'content' => $body['choices'][0]['message']['content'],
                'model' => $model_name,
                'provider' => 'deepseek',
                'usage' => $body['usage'] ?? []
            ];
        }

        return false;
    }

    /**
     * Llamar a Mistral AI
     */
    private function call_mistral($api_key, $prompt, $options)
    {
        $endpoint = 'https://api.mistral.ai/v1/chat/completions';
        $model_name = 'mistral-large-latest';

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
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'body' => wp_json_encode($body),
            'timeout' => 90,
        ]);

        if (is_wp_error($response)) {
            error_log('[Kzmcito IA SEO] Error en llamada a Mistral: ' . $response->get_error_message());
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code >= 400) {
            error_log('[Kzmcito IA SEO] Mistral HTTP ' . $status_code . ': ' . wp_remote_retrieve_body($response));
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['choices'][0]['message']['content'])) {
            return [
                'content' => $body['choices'][0]['message']['content'],
                'model' => $model_name,
                'provider' => 'mistral',
                'usage' => $body['usage'] ?? []
            ];
        }

        return false;
    }

    /**
     * Llamar a Groq (LLaMA rápido)
     */
    private function call_groq($api_key, $prompt, $options)
    {
        $endpoint = 'https://api.groq.com/openai/v1/chat/completions';
        $model_name = 'llama-3.3-70b-versatile';

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
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'body' => wp_json_encode($body),
            'timeout' => 60,
        ]);

        if (is_wp_error($response)) {
            error_log('[Kzmcito IA SEO] Error en llamada a Groq: ' . $response->get_error_message());
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code >= 400) {
            error_log('[Kzmcito IA SEO] Groq HTTP ' . $status_code . ': ' . wp_remote_retrieve_body($response));
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['choices'][0]['message']['content'])) {
            return [
                'content' => $body['choices'][0]['message']['content'],
                'model' => $model_name,
                'provider' => 'groq',
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
                'message' => sprintf(
                    __('Conexión exitosa con %s (Modelo: %s)', 'kzmcito-ia-seo'),
                    $response['provider'],
                    $response['model']
                ),
                'model' => $response['model'],
                'provider' => $response['provider'],
                'fallback_used' => $response['fallback_used'] ?? false,
            ];
        }

        return [
            'success' => false,
            'message' => __('Error de conexión con todos los proveedores configurados', 'kzmcito-ia-seo'),
        ];
    }

    /**
     * Obtener proveedores disponibles (con key configurada)
     *
     * @return array
     */
    public function get_available_providers()
    {
        $providers = [];
        foreach ($this->api_keys as $provider => $key) {
            if (!empty($key)) {
                $providers[] = $provider;
            }
        }
        return $providers;
    }

    /**
     * Obtener el último proveedor exitoso
     *
     * @return string|null
     */
    public function get_last_successful_provider()
    {
        return $this->last_successful_provider;
    }

    /**
     * Log de intento de API
     */
    private function log_attempt($model, $message)
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Kzmcito IA SEO] [API Fallback] ' . $message);
        }
    }
}
