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
        echo '<textarea id="gpw_prompt" style="width:100%" placeholder="Instruksi AI..."></textarea>';
        echo '<div id="gpw_img_preview" style="margin:10px 0; display:flex; flex-wrap:wrap; gap:5px;"></div>';
        echo '<button type="button" id="gpw_upload_btn" class="button button-secondary">Pilih Gambar</button>';
        echo '<button type="button" id="gpw_gen_btn" class="button button-primary" style="float:right">Generate</button>';
        echo '<div id="gpw_status" style="margin-top:10px; font-size:12px;"></div>';
    }

    // Cari fungsi handle_ajax() di file class-post-editor.php dan ganti dengan ini:
    public function handle_ajax() {
        $prompt = $_POST['prompt'];
        $images = $_POST['images'] ?? [];
        $model = get_option('gpw_default_model', 'gemini-1.5-flash');

        $result = Gemini_API::generate($model, $prompt, $images);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            // Ambil URL gambar agar JS bisa memasukkannya ke Editor
            $img_urls = [];
            foreach ($images as $id) {
                $img_urls[] = wp_get_attachment_url($id);
            }

            wp_send_json_success([
                'text' => wpautop($result), // Mengubah \n menjadi <p>
                'images' => $img_urls
            ]);
        }
    }
}