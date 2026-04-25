<?php
class Gemini_Post_Editor {
    public function __construct() {
        add_action('add_meta_boxes', [$this, 'add_gemini_box']);
        add_action('admin_enqueue_scripts', [$this, 'assets']);
        add_action('wp_ajax_gemini_generate', [$this, 'handle_ajax']);
    }

    public function assets($hook) {
        if (!in_array($hook, ['post.php', 'post-new.php'])) return;
        wp_enqueue_media();
        wp_enqueue_script('gpw-js', GPW_URL . 'assets/admin.js', ['jquery'], time(), true);
    }

    public function add_gemini_box() {
        add_meta_box('gpw_box', 'Gemini AI Assistant', [$this, 'render_box'], ['post', 'page'], 'side', 'high');
    }

    public function render_box() {
        $model = get_option('gpw_default_model', 'gemini-1.5-flash');
        echo '<p><strong>Model:</strong> '.$model.'</p>';
        echo '<textarea id="gpw_prompt" style="width:100%; height:100px;" placeholder="Instruksi AI... (Contoh: Buat tutorial lengkap dari gambar ini)"></textarea>';
        echo '<div id="gpw_img_preview" style="margin:10px 0; display:flex; flex-wrap:wrap; gap:5px;"></div>';
        echo '<button type="button" id="gpw_upload_btn" class="button button-secondary" style="width:100%; margin-bottom:5px;">Pilih Gambar</button>';
        echo '<button type="button" id="gpw_gen_btn" class="button button-primary" style="width:100%">Generate Tutorial</button>';
        echo '<div id="gpw_status" style="margin-top:10px; font-size:12px;"></div>';
    }

    public function handle_ajax() {
        $prompt = $_POST['prompt'] ?? '';
        $images = $_POST['images'] ?? [];
        $model = get_option('gpw_default_model', 'gemini-1.5-flash');

        // REFINED PROMPT: Instruksi agar AI menempatkan gambar di posisi yang tepat
        $refined_prompt = "Tugas: Buat tutorial yang sangat lengkap, detail, dan panjang berdasarkan gambar yang dikirim.\n";
        $refined_prompt .= "PENTING: Masukkan gambar di dalam teks tutorial pada langkah yang sesuai dengan kode: [IMAGE_X] (X adalah urutan gambar mulai dari 0).\n";
        $refined_prompt .= "Contoh: Jika gambar pertama menjelaskan langkah awal, tuliskan [IMAGE_0] setelah penjelasan langkah tersebut.\n";
        $refined_prompt .= "Jangan memberikan ringkasan, buatlah penjelasan yang mendalam untuk setiap langkah.\n\n";
        $refined_prompt .= "Instruksi Tambahan: " . $prompt;

        $result = Gemini_API::generate($model, $refined_prompt, $images);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            $final_content = $result;

            // Proses penggantian Placeholder [IMAGE_X] menjadi tag HTML gambar
            if (!empty($images)) {
                foreach ($images as $index => $id) {
                    $url = wp_get_attachment_url($id);
                    $img_html = '<figure class="wp-block-image size-large"><img src="' . $url . '" alt="Step Image ' . ($index + 1) . '" /></figure>';
                    $final_content = str_replace("[IMAGE_{$index}]", $img_html, $final_content);
                }
            }

            wp_send_json_success([
                'text' => wpautop($final_content)
            ]);
        }
    }
}