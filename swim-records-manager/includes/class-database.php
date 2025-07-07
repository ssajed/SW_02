<?php
if (!defined('ABSPATH')) exit;

class SRM_Database {
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . SRM_TABLE_NAME;
        
        $sql = "CREATE TABLE $table_name (
            swimmer_id BIGINT(20) NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            national_code VARCHAR(10) NOT NULL,
            level VARCHAR(10) NOT NULL DEFAULT 'training',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (swimmer_id),
            UNIQUE KEY (national_code),
            INDEX (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // ایجاد نقش شناگر
        add_role('swimmer', 'شناگر', [
            'read' => true,
            'edit_posts' => false
        ]);
    }
}