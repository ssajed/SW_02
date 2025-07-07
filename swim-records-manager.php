<?php
/**
 * Plugin Name: مدیریت حرفه‌ای شناگران
 * Description: سیستم جامع مدیریت شناگران و رکوردهای شنا
 * Version: 1.0.0
 * Author: ساجد محمدی اصل
 * Author URI: https://sasalan.ir
 * License: GPLv2
 */

defined('ABSPATH') or die('دسترسی غیرمجاز!');

// تعریف ثابت‌ها
define('SRM_VERSION', '1.0.0');
define('SRM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SRM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SRM_TABLE_NAME', 'srm_swimmers');

// بارگذاری فایل‌های ضروری
require_once SRM_PLUGIN_DIR . 'includes/class-database.php';
require_once SRM_PLUGIN_DIR . 'includes/class-swimmers.php';
require_once SRM_PLUGIN_DIR . 'includes/class-ajax-handler.php';
require_once SRM_PLUGIN_DIR . 'includes/class-swim-manager.php';
require_once SRM_PLUGIN_DIR . 'admin/class-admin-page.php'; // اضافه کردن این خط
require_once SRM_PLUGIN_DIR . 'public/class-public-profile.php';

// فعال‌سازی افزونه
register_activation_hook(__FILE__, ['SRM_Database', 'create_tables']);

// راه‌اندازی ماژول‌ها
add_action('plugins_loaded', function() {
    SRM_Swimmers::init();
    SRM_AJAX_Handler::init();
    SRM_Swim_Manager::init();
    SRM_Public_Profile::init();
    
    if (is_admin()) {
        new SRM_Admin_Page();
    }
});