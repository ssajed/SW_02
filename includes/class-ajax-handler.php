<?php
if (!defined('ABSPATH')) exit;

class SRM_AJAX_Handler {
    public static function init() {
        // مدیریت شناگران
        add_action('wp_ajax_srm_add_swimmer', [__CLASS__, 'add_swimmer']);
        add_action('wp_ajax_srm_get_swimmers', [__CLASS__, 'get_swimmers']);
        add_action('wp_ajax_srm_delete_swimmer', [__CLASS__, 'delete_swimmer']);
        add_action('wp_ajax_srm_update_swimmer', [__CLASS__, 'update_swimmer']);
        
        // مدیریت رکوردهای شنا
        add_action('wp_ajax_srm_get_swim_records', [__CLASS__, 'get_swim_records']);
        add_action('wp_ajax_srm_update_swim_records', [__CLASS__, 'update_swim_records']);
        add_action('wp_ajax_srm_update_swim_skills', [__CLASS__, 'update_swim_skills']);
        
        // عمومی
        add_action('wp_ajax_srm_get_profile_data', [__CLASS__, 'get_profile_data']);
    }

    /**
     * افزودن شناگر جدید
     */
    public static function add_swimmer() {
        check_ajax_referer('srm_ajax_nonce', 'security');
        
        // اعتبارسنجی داده‌های ورودی
        $required = ['first_name', 'last_name', 'national_code', 'level'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error("فیلد {$field} الزامی است", 400);
            }
        }
        
        if (!preg_match('/^\d{10}$/', $_POST['national_code'])) {
            wp_send_json_error('کد ملی باید 10 رقمی باشد', 400);
        }
        
        $result = SRM_Swimmers::add_swimmer([
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'national_code' => sanitize_text_field($_POST['national_code']),
            'level' => sanitize_text_field($_POST['level'])
        ]);
        
        if ($result['success']) {
            wp_send_json_success([
                'message' => 'شناگر با موفقیت ثبت شد',
                'user_id' => $result['user_id']
            ]);
        } else {
            wp_send_json_error($result['message'] ?? 'خطا در ثبت شناگر', 500);
        }
    }

    /**
     * دریافت لیست شناگران
     */
    public static function get_swimmers() {
        check_ajax_referer('srm_ajax_nonce', 'security');
        
        $args = [
            'per_page' => isset($_POST['per_page']) ? absint($_POST['per_page']) : 20,
            'page' => isset($_POST['page']) ? absint($_POST['page']) : 1,
            'search' => isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '',
            'orderby' => isset($_POST['orderby']) ? sanitize_key($_POST['orderby']) : 'created_at',
            'order' => isset($_POST['order']) ? strtoupper(sanitize_key($_POST['order'])) : 'DESC'
        ];
        
        $data = SRM_Swimmers::get_swimmers($args);
        
        if ($data) {
            wp_send_json_success([
                'items' => $data['items'],
                'pagination' => [
                    'total' => $data['total'],
                    'per_page' => $data['per_page'],
                    'current_page' => $data['current_page'],
                    'max_pages' => $data['max_pages']
                ]
            ]);
        } else {
            wp_send_json_error('خطا در دریافت داده‌های شناگران', 500);
        }
    }

    /**
     * حذف شناگر
     */
    public static function delete_swimmer() {
        check_ajax_referer('srm_ajax_nonce', 'security');

        if (!isset($_POST['swimmer_id']) || !current_user_can('manage_options')) {
            wp_send_json_error('دسترسی غیرمجاز', 403);
        }

        $swimmer_id = absint($_POST['swimmer_id']);
        $result = SRM_Swimmers::delete_swimmer($swimmer_id);
        
        if ($result) {
            wp_send_json_success('شناگر با موفقیت حذف شد');
        } else {
            wp_send_json_error('خطا در حذف شناگر', 500);
        }
    }

    /**
     * به‌روزرسانی اطلاعات شناگر
     */
    public static function update_swimmer() {
        check_ajax_referer('srm_ajax_nonce', 'security');

        if (!isset($_POST['swimmer_id']) || !current_user_can('manage_options')) {
            wp_send_json_error('دسترسی غیرمجاز', 403);
        }

        $data = [
            'swimmer_id' => absint($_POST['swimmer_id']),
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'national_code' => sanitize_text_field($_POST['national_code']),
            'level' => sanitize_text_field($_POST['level'])
        ];

        $result = SRM_Swimmers::update_swimmer($data);
        
        if ($result) {
            wp_send_json_success('اطلاعات شناگر با موفقیت به‌روزرسانی شد');
        } else {
            wp_send_json_error('خطا در به‌روزرسانی اطلاعات', 500);
        }
    }

    /**
     * دریافت رکوردهای شناگر
     */
    public static function get_swim_records() {
        check_ajax_referer('srm_ajax_nonce', 'security');
        
        $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : get_current_user_id();
        $records = SRM_Swim_Manager::get_swimmer_records($user_id);
        
        wp_send_json_success($records);
    }

    /**
     * به‌روزرسانی رکوردهای شنا
     */
    public static function update_swim_records() {
        check_ajax_referer('srm_ajax_nonce', 'security');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('دسترسی غیرمجاز', 403);
        }

        $user_id = absint($_POST['user_id']);
        $records = array_map('floatval', $_POST['records']);

        update_user_meta($user_id, 'srm_swim_records', $records);
        wp_send_json_success('رکوردهای شناگر با موفقیت به‌روزرسانی شد');
    }

    /**
     * به‌روزرسانی مهارت‌های شنا
     */
    public static function update_swim_skills() {
        check_ajax_referer('srm_ajax_nonce', 'security');
    
        if (!current_user_can('manage_options')) {
            wp_send_json_error('دسترسی غیرمجاز', 403);
        }
    
        $user_id = absint($_POST['user_id']);
        $skills = isset($_POST['skills']) ? array_map('sanitize_text_field', $_POST['skills']) : [];
        
        // بررسی محدودیت انتخاب (1 تا 4 تخصص)
        if (count($skills) > 4) {
            wp_send_json_error('حداکثر 4 تخصص می‌توانید انتخاب کنید', 400);
        }
        
        // بررسی وجود حداقل یک تخصص
        if (empty($skills)) {
            wp_send_json_error('حداقل یک تخصص باید انتخاب شود', 400);
        }
        
        update_user_meta($user_id, 'srm_swim_skills', $skills);
        wp_send_json_success('تخصص‌های شناگر با موفقیت به‌روزرسانی شد');
    }

    /**
     * دریافت داده‌های پروفایل
     */
    public static function get_profile_data() {
        check_ajax_referer('srm_ajax_nonce', 'security');
        
        $user_id = get_current_user_id();
        $data = [
            'user_info' => SRM_Swimmers::get_swimmer_by_user_id($user_id),
            'swim_records' => SRM_Swim_Manager::get_swimmer_records($user_id),
            'swim_skills' => get_user_meta($user_id, 'srm_swim_skills', true) ?: [],
            'levels' => get_option('srm_swimmers_levels', [])
        ];
        
        wp_send_json_success($data);
    }
}