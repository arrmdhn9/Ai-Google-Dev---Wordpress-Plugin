<?php
class Gemini_API {
    public static function get_api_key() {
        return get_option('gpw_api_key');
    }

    public static function get_models() {
        $key = self::get_api_key();
        if (!$key) return new WP_Error('no_key', 'API Key is not filled in.');

        $url = "https://generativelanguage.googleapis.com/v1/models?key=" . $key;
        $response = wp_remote_get($url);

        if (is_wp_error($response)) return $response;

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['error'])) {
            return new WP_Error('api_error', $body['error']['message']);
        }

        return $body['models'] ?? [];
    }

    public static function generate($model, $prompt, $images = []) {
        $key = self::get_api_key();
        $url = "https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent?key=" . $key;

        $parts = [['text' => $prompt]];

        foreach ($images as $img_id) {
            $path = get_attached_file($img_id);
            if ($path && file_exists($path)) {
                $parts[] = [
                    'inline_data' => [
                        'mime_type' => get_post_mime_type($img_id),
                        'data'      => base64_encode(file_get_contents($path))
                    ]
                ];
            }
        }

        $payload = [
            'contents' => [['parts' => $parts]],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 99999999, // Ditingkatkan agar teks tidak terpotong
            ]
        ];

        $response = wp_remote_post($url, [
            'body'    => json_encode($payload),
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 120 // Timeout lama karena konten panjang
        ]);

        if (is_wp_error($response)) return $response;

        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new WP_Error('api_error', "Google API Error: " . $body['error']['message']);
        }

        if (!isset($body['candidates'][0]['content']['parts'][0]['text'])) {
            return new WP_Error('empty_res', 'API tidak mengembalikan teks. Coba model lain atau cek gambar.');
        }

        return $body['candidates'][0]['content']['parts'][0]['text'];
    }
}