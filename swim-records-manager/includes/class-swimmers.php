<?php
if (!defined('ABSPATH')) exit;

class SRM_Swimmers {
    public static function init() {
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }
    
    public static function register_settings() {
        register_setting('srm_swimmers_group', 'srm_swimmers_levels', [
            'type' => 'array',
            'default' => [
                'training' => 'آموزشی',
                'C' => 'سطح C',
                'B' => 'سطح B',
                'A' => 'سطح A',
                'A+' => 'سطح A+'
            ],
            'sanitize_callback' => [__CLASS__, 'sanitize_levels']
        ]);
    }
    
    public static function sanitize_levels($levels) {
        $sanitized = [];
        foreach ($levels as $key => $value) {
            $sanitized[sanitize_key($key)] = sanitize_text_field($value);
        }
        return $sanitized;
    }
    
    public static function add_swimmer($data) {
        global $wpdb;
        
        $defaults = [
            'first_name' => '',
            'last_name' => '',
            'national_code' => '',
            'level' => 'training'
        ];
        
        $data = wp_parse_args($data, $defaults);
        
        // بررسی تکراری نبودن کد ملی
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}" . SRM_TABLE_NAME . " WHERE national_code = %s",
                $data['national_code']
            )
        );
        
        if ($existing) {
            return [
                'success' => false,
                'message' => 'کد ملی تکراری است'
            ];
        }
        
        // ایجاد کاربر
        $userdata = [
            'user_login' => $data['national_code'],
            'user_pass'  => wp_generate_password(),
            'user_email' => $data['national_code'] . '@srm.swim',
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'role'       => 'swimmer'
        ];
        
        $user_id = wp_insert_user($userdata);
        
        if (is_wp_error($user_id)) {
            return [
                'success' => false,
                'message' => $user_id->get_error_message()
            ];
        }
        
        // ذخیره متادیتا
        update_user_meta($user_id, 'national_code', $data['national_code']);
        update_user_meta($user_id, 'swim_level', $data['level']);
        
        // ثبت در جدول شناگران
        $result = $wpdb->insert(
            $wpdb->prefix . SRM_TABLE_NAME,
            [
                'user_id' => $user_id,
                'full_name' => $data['first_name'] . ' ' . $data['last_name'],
                'national_code' => $data['national_code'],
                'level' => $data['level'],
                'created_at' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s', '%s']
        );
        
        return [
            'success' => (bool) $result,
            'user_id' => $user_id
        ];
    }
    
    public static function get_swimmers($args = []) {
        global $wpdb;
        
        $defaults = [
            'per_page' => 20,
            'page' => 1,
            'search' => '',
            'orderby' => 'created_at',
            'order' => 'DESC'
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $where = '';
        $params = [];
        
        if (!empty($args['search'])) {
            $where = " WHERE full_name LIKE %s OR national_code LIKE %s";
            $params = [
                '%' . $wpdb->esc_like($args['search']) . '%',
                '%' . $wpdb->esc_like($args['search']) . '%'
            ];
        }
        
        $query = "SELECT * FROM {$wpdb->prefix}" . SRM_TABLE_NAME . $where . 
                 " ORDER BY {$args['orderby']} {$args['order']}" .
                 " LIMIT %d OFFSET %d";
        
        $params[] = $args['per_page'];
        $params[] = ($args['page'] - 1) * $args['per_page'];
        
        $items = $wpdb->get_results($wpdb->prepare($query, $params));
        
        $count_query = "SELECT COUNT(*) FROM {$wpdb->prefix}" . SRM_TABLE_NAME . $where;
        $total = $wpdb->get_var($params ? $wpdb->prepare($count_query, array_slice($params, 0, -2)) : $count_query);
        
        return [
            'items' => $items,
            'total' => $total,
            'per_page' => $args['per_page'],
            'current_page' => $args['page'],
            'max_pages' => ceil($total / $args['per_page'])
        ];
    }
    
    public static function get_swimmer_by_user_id($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . SRM_TABLE_NAME;
        
        $swimmer = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE user_id = %d",
                $user_id
            )
        );
        
        if (!$swimmer) {
            $user = get_userdata($user_id);
            if ($user) {
                $swimmer = (object)[
                    'user_id' => $user_id,
                    'full_name' => $user->first_name . ' ' . $user->last_name,
                    'national_code' => get_user_meta($user_id, 'national_code', true),
                    'level' => get_user_meta($user_id, 'swim_level', true),
                    'created_at' => $user->user_registered
                ];
            }
        }
        
        return $swimmer;
    }
    
    public static function get_swimmer_by_national_code($national_code) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}" . SRM_TABLE_NAME . " WHERE national_code = %s",
                $national_code
            )
        );
    }
    
    public static function update_swimmer($data) {
        global $wpdb;
        
        $swimmer_id = isset($data['swimmer_id']) ? absint($data['swimmer_id']) : 0;
        $user_id = isset($data['user_id']) ? absint($data['user_id']) : 0;
        
        if (!$swimmer_id || !$user_id) {
            return false;
        }
        
        // Update user data
        $userdata = [
            'ID' => $user_id,
            'first_name' => sanitize_text_field($data['first_name']),
            'last_name' => sanitize_text_field($data['last_name'])
        ];
        
        if (isset($data['national_code'])) {
            $userdata['user_login'] = sanitize_text_field($data['national_code']);
            $userdata['user_email'] = sanitize_text_field($data['national_code']) . '@srm.swim';
        }
        
        $result = wp_update_user($userdata);
        
        if (is_wp_error($result)) {
            return false;
        }
        
        // Update meta data
        if (isset($data['national_code'])) {
            update_user_meta($user_id, 'national_code', sanitize_text_field($data['national_code']));
        }
        
        if (isset($data['level'])) {
            update_user_meta($user_id, 'swim_level', sanitize_text_field($data['level']));
        }
        
        // Update swimmers table
        $update_data = [
            'full_name' => sanitize_text_field($data['first_name']) . ' ' . sanitize_text_field($data['last_name'])
        ];
        
        if (isset($data['national_code'])) {
            $update_data['national_code'] = sanitize_text_field($data['national_code']);
        }
        
        if (isset($data['level'])) {
            $update_data['level'] = sanitize_text_field($data['level']);
        }
        
        return $wpdb->update(
            $wpdb->prefix . SRM_TABLE_NAME,
            $update_data,
            ['swimmer_id' => $swimmer_id],
            ['%s'],
            ['%d']
        );
    }
    
    public static function delete_swimmer($swimmer_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . SRM_TABLE_NAME;
        
        // دریافت user_id قبل از حذف
        $user_id = $wpdb->get_var(
            $wpdb->prepare("SELECT user_id FROM $table_name WHERE swimmer_id = %d", $swimmer_id)
        );
        
        if (!$user_id) {
            return false;
        }
        
        // حذف از جدول شناگران
        $deleted = $wpdb->delete(
            $table_name,
            ['swimmer_id' => $swimmer_id],
            ['%d']
        );
        
        if ($deleted) {
            // حذف کاربر و متادیتاهای مرتبط
            require_once(ABSPATH . 'wp-admin/includes/user.php');
            
            // حذف تمام متادیتاهای کاربر
            $meta_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT umeta_id FROM $wpdb->usermeta WHERE user_id = %d", 
                $user_id
            ));
            
            foreach ($meta_ids as $mid) {
                delete_metadata_by_mid('user', $mid);
            }
            
            // حذف خود کاربر
            return wp_delete_user($user_id);
        }
        
        return false;
    }
}