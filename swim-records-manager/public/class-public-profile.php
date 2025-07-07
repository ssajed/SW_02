<?php
if (!defined('ABSPATH')) exit;

class SRM_Public_Profile {
    public static function init() {
        add_shortcode('swimmer_profile', [__CLASS__, 'render_profile']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }

    public static function enqueue_assets() {
        if (is_page('profile')) {
            wp_enqueue_style(
                'srm-profile-css',
                SRM_PLUGIN_URL . 'admin/assets/css/profile.css',
                [],
                SRM_VERSION
            );

            wp_enqueue_script(
                'srm-profile-js',
                SRM_PLUGIN_URL . 'admin/assets/js/profile.js',
                ['jquery'],
                SRM_VERSION,
                true
            );

            wp_localize_script('srm-profile-js', 'srm_profile', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('srm_profile_nonce')
            ]);
        }
    }

    public static function render_profile($atts) {
        if (!is_user_logged_in()) {
            return '<div class="srm-alert">برای مشاهده پروفایل باید وارد شوید</div>';
        }

        $user_id = get_current_user_id();
        ob_start();
        include SRM_PLUGIN_DIR . 'public/views/user-profile.php';
        return ob_get_clean();
    }
}