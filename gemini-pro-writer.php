<?php
/**
 * Plugin Name: Gemini Pro Writer
 * Description: Gemini AI integration into Post Editor with smart image detection and auto-placement.
 * Version: 2.1
 * Author: CasperdotID
 */

if (!defined('ABSPATH')) exit;

define('GPW_PATH', plugin_dir_path(__FILE__));
define('GPW_URL', plugin_dir_url(__FILE__));

require_once GPW_PATH . 'includes/class-api.php';
require_once GPW_PATH . 'includes/class-settings.php';
require_once GPW_PATH . 'includes/class-post-editor.php';

// Inisialisasi plugin
new Gemini_Settings();
new Gemini_Post_Editor();