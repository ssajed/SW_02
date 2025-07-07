<?php
if (!defined('ABSPATH')) exit;

class SRM_Admin_Page {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }
    
    public function add_admin_menu() {
        $icon_svg = 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#00BCD4">
                <path d="M12 15c1.66 0 3-1.34 3-3V6c0-1.66-1.34-3-3-3S9 4.34 9 6v6c0 1.66 1.34 3 3 3zm5.91-3c-.49 0-.9.36-.98.85C16.52 15.22 14.47 17 12 17s-4.52-1.78-4.93-4.15c-.08-.49-.49-.85-.98-.85-.61 0-1.09.54-1 1.14.49 3 2.89 5.35 5.91 5.78V21c0 .55.45 1 1 1s1-.45 1-1v-2.08c3.02-.43 5.42-2.78 5.91-5.78.1-.6-.39-1.14-1-1.14z"/>
            </svg>'
        );
        
        add_menu_page(
            'مدیریت شناگران',
            'شناگران',
            'manage_options',
            'swim-manager',
            [$this, 'render_admin_page'], // تغییر نام متد
            $icon_svg,
            6
        );
    }
    
    public function enqueue_assets($hook) {
        if ($hook !== 'toplevel_page_swim-manager') return;
        
        wp_enqueue_style(
            'srm-admin-css',
            SRM_PLUGIN_URL . 'assets/css/admin.css',
            [],
            SRM_VERSION
        );
        
        wp_enqueue_script(
            'srm-admin-js',
            SRM_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            SRM_VERSION,
            true
        );
        
        wp_localize_script('srm-admin-js', 'srm_admin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('srm_ajax_nonce'),
            'levels' => get_option('srm_swimmers_levels', [
                'training' => 'آموزشی',
                'C' => 'سطح C',
                'B' => 'سطح B',
                'A' => 'سطح A',
                'A+' => 'سطح A+'
            ])
        ]);
    }
    
    /**
     * متد جدید برای رندر صفحه مدیریت
     */
    public function render_admin_page() {
        // بررسی سطح دسترسی
        if (!current_user_can('manage_options')) {
            wp_die('شما دسترسی لازم برای مشاهده این صفحه را ندارید.');
        }
        
        // نمایش هدر صفحه
        echo '<div class="wrap">';
        echo '<h1>' . esc_html(get_admin_page_title()) . '</h1>';
        
        // بارگذاری محتوای صفحه
        include SRM_PLUGIN_DIR . 'admin/views/swimmers-list.php';
        
        // نمایش فوتر صفحه
        echo '</div>';
    }
}