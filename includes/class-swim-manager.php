<?php
if (!defined('ABSPATH')) exit;

class SRM_Swim_Manager {
    const SWIM_STYLES = [
        'freestyle' => 'کرال سینه',
        'backstroke' => 'کرال پشت',
        'breaststroke' => 'قورباغه',
        'butterfly' => 'پروانه'
    ];

    public static function init() {
        add_action('admin_init', [__CLASS__, 'register_swim_settings']);
        add_action('wp_ajax_srm_update_swim_records', [__CLASS__, 'update_swim_records']);
        add_action('wp_ajax_srm_update_swim_skills', [__CLASS__, 'update_swim_skills']);
        add_action('wp_ajax_srm_get_swim_achievements', [__CLASS__, 'get_swim_achievements']);
    }

    public static function register_swim_settings() {
        register_setting('srm_swim_settings', 'srm_swim_requirements', [
            'type' => 'array',
            'default' => [
                'A+' => [
                    'distance' => 2000,
                    'styles' => ['freestyle', 'backstroke'],
                    'time_requirements' => [
                        'freestyle' => 120,  // seconds per 100m
                        'backstroke' => 130
                    ]
                ],
                'A' => [
                    'distance' => 1500,
                    'styles' => ['freestyle'],
                    'time_requirements' => [
                        'freestyle' => 140
                    ]
                ],
                'B' => [
                    'distance' => 1000,
                    'styles' => [],
                    'time_requirements' => []
                ],
                'C' => [
                    'distance' => 500,
                    'styles' => [],
                    'time_requirements' => []
                ],
                'training' => [
                    'distance' => 0,
                    'styles' => [],
                    'time_requirements' => []
                ]
            ],
            'sanitize_callback' => [__CLASS__, 'sanitize_swim_settings']
        ]);
    }

    public static function sanitize_swim_settings($settings) {
        $sanitized = [];
        foreach ($settings as $level => $data) {
            $sanitized_level = sanitize_key($level);
            $sanitized[$sanitized_level] = [
                'distance' => absint($data['distance']),
                'styles' => array_map('sanitize_key', $data['styles']),
                'time_requirements' => array_map('floatval', $data['time_requirements'])
            ];
        }
        return $sanitized;
    }

    public static function get_swimmer_records($user_id) {
        $records = get_user_meta($user_id, 'srm_swim_records', true) ?: [];
        
        // Ensure all styles exist in records
        foreach (self::SWIM_STYLES as $style => $name) {
            if (!isset($records[$style])) {
                $records[$style] = 0;
            }
        }
        
        return $records;
    }

    public static function get_swim_style_images($style) {
        return [
            'off' => SRM_PLUGIN_URL . 'assets/images/' . $style . '_off.png',
            'on' => SRM_PLUGIN_URL . 'assets/images/' . $style . '_on.png'
        ];
    }

    public static function update_swim_records() {
        check_ajax_referer('srm_ajax_nonce', 'security');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('دسترسی غیرمجاز', 403);
        }

        $user_id = absint($_POST['user_id']);
        $records = array_map('floatval', $_POST['records']);
        $dates = array_map('sanitize_text_field', $_POST['dates']);

        // Validate records
        foreach ($records as $style => $time) {
            if ($time < 0) {
                wp_send_json_error('مقادیر رکورد نامعتبر است', 400);
            }
            
            // Save record date
            if (!empty($dates[$style])) {
                update_user_meta($user_id, "srm_{$style}_record_date", $dates[$style]);
            }
        }

        update_user_meta($user_id, 'srm_swim_records', $records);
        wp_send_json_success('رکوردهای شناگر با موفقیت به‌روزرسانی شد');
    }

    public static function update_swim_skills() {
        check_ajax_referer('srm_ajax_nonce', 'security');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('دسترسی غیرمجاز', 403);
        }

        $user_id = absint($_POST['user_id']);
        $skills = array_map('sanitize_key', $_POST['skills']);

        // Validate skills
        $valid_skills = array_keys(self::SWIM_STYLES);
        foreach ($skills as $skill) {
            if (!in_array($skill, $valid_skills)) {
                wp_send_json_error('تخصص انتخاب شده نامعتبر است', 400);
            }
        }

        // Check min/max skills
        if (count($skills) < 1) {
            wp_send_json_error('حداقل یک تخصص باید انتخاب شود', 400);
        }

        if (count($skills) > 4) {
            wp_send_json_error('حداکثر ۴ تخصص می‌توانید انتخاب کنید', 400);
        }

        update_user_meta($user_id, 'srm_swim_skills', $skills);
        wp_send_json_success('تخصص‌های شناگر با موفقیت به‌روزرسانی شد');
    }

    public static function get_swim_achievements() {
        check_ajax_referer('srm_ajax_nonce', 'security');

        $user_id = absint($_POST['user_id']);
        $level = get_user_meta($user_id, 'swim_level', true);
        $records = self::get_swimmer_records($user_id);
        $requirements = get_option('srm_swim_requirements', []);
        $level_requirements = $requirements[$level] ?? [];

        $achievements = [
            'passed' => [],
            'pending' => []
        ];

        // Check distance achievement
        $swim_distance = floatval(get_user_meta($user_id, 'srm_total_distance', true));
        $required_distance = $level_requirements['distance'] ?? 0;
        
        if ($swim_distance >= $required_distance) {
            $achievements['passed'][] = [
                'title' => 'مسافت طی شده',
                'description' => sprintf('شناگر توانسته است %d متر شنا کند', $required_distance),
                'icon' => 'distance'
            ];
        } else {
            $achievements['pending'][] = [
                'title' => 'مسافت طی شده',
                'description' => sprintf('%d متر از %d متر باقی مانده', 
                    $required_distance - $swim_distance, 
                    $required_distance),
                'icon' => 'distance'
            ];
        }

        // Check style requirements
        foreach ($level_requirements['styles'] ?? [] as $style) {
            $record = $records[$style] ?? 0;
            $required_time = $level_requirements['time_requirements'][$style] ?? 0;
            
            if ($record > 0 && $record <= $required_time) {
                $achievements['passed'][] = [
                    'title' => sprintf('رکورد %s', self::SWIM_STYLES[$style]),
                    'description' => sprintf('زمان کمتر از %s ثانیه', $required_time),
                    'icon' => $style
                ];
            } else {
                $achievements['pending'][] = [
                    'title' => sprintf('رکورد %s', self::SWIM_STYLES[$style]),
                    'description' => $record > 0 ? 
                        sprintf('%s ثانیه از %s ثانیه باقی مانده', 
                            $record - $required_time, 
                            $required_time) :
                        'رکوردی ثبت نشده است',
                    'icon' => $style
                ];
            }
        }

        wp_send_json_success($achievements);
    }

    public static function calculate_swim_level($user_id) {
        $records = self::get_swimmer_records($user_id);
        $requirements = get_option('srm_swim_requirements', []);
        $total_distance = floatval(get_user_meta($user_id, 'srm_total_distance', true));
        
        $possible_levels = array_reverse(array_keys($requirements));
        
        foreach ($possible_levels as $level) {
            $req = $requirements[$level];
            
            // Check distance
            if ($total_distance < $req['distance']) {
                continue;
            }
            
            // Check style requirements
            $all_passed = true;
            foreach ($req['styles'] as $style) {
                $record = $records[$style] ?? 0;
                $required = $req['time_requirements'][$style] ?? 0;
                
                if ($record == 0 || $record > $required) {
                    $all_passed = false;
                    break;
                }
            }
            
            if ($all_passed) {
                return $level;
            }
        }
        
        return 'training';
    }
}