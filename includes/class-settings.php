<?php
class Gemini_Settings {
    public function __construct() {
        add_action('admin_menu', [$this, 'menu']);
    }

    public function menu() {
        add_options_page('Gemini Settings', 'Gemini AI Writer', 'manage_options', 'gemini-settings', [$this, 'page']);
    }

    public function page() {
        if (isset($_POST['save_gpw'])) {
            update_option('gpw_api_key', sanitize_text_field($_POST['gpw_key']));
            update_option('gpw_default_model', sanitize_text_field($_POST['gpw_model']));
            echo '<div class="updated"><p>Settings saved!</p></div>';
        }

        $key = get_option('gpw_api_key');
        $models = Gemini_API::get_models();
        ?>
        <div class="wrap">
            <h1>Gemini API Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>API Key</th>
                        <td><input type="password" name="gpw_key" value="<?php echo esc_attr($key); ?>" class="regular-text"></td>
                    </tr>
                    <?php if (!is_wp_error($models) && !empty($models)): ?>
                    <tr>
                        <th>Default Model</th>
                        <td>
                            <select name="gpw_model">
                                <?php foreach ($models as $m): 
                                    $m_name = basename($m['name']);
                                    if (strpos($m_name, 'vision') !== false || strpos($m_name, 'flash') !== false || strpos($m_name, 'pro') !== false): ?>
                                    <option value="<?php echo $m_name; ?>" <?php selected(get_option('gpw_default_model'), $m_name); ?>>
                                        <?php echo $m['displayName']; ?>
                                    </option>
                                <?php endif; endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <?php elseif (is_wp_error($models)): ?>
                        <tr><th>Error</th><td><span style="color:red"><?php echo $models->get_error_message(); ?></span></td></tr>
                    <?php endif; ?>
                </table>
                <input type="submit" name="save_gpw" class="button button-primary" value="Save & Load Models">
            </form>
        </div>
        <?php
    }
}