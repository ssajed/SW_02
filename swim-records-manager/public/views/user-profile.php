<?php
/**
 * Template for displaying user profile
 */

// Check if user is logged in
if (!is_user_logged_in()) {
    echo '<div class="srm-alert">برای مشاهده پروفایل باید وارد شوید</div>';
    return;
}

$user_id = get_current_user_id();
$user_data = SRM_Swimmers::get_swimmer_by_user_id($user_id);

if (!$user_data) {
    echo '<div class="srm-alert">اطلاعات شناگر یافت نشد</div>';
    return;
}

$user_skills = get_user_meta($user_id, 'srm_swim_skills', true) ?: [];
$swim_records = SRM_Swim_Manager::get_swimmer_records($user_id);
$level_labels = get_option('srm_swimmers_levels', []);
$user_level = get_user_meta($user_id, 'swim_level', true);
?>

<div class="srm-profile-container">
    <!-- ستون سمت راست (25%) -->
    <div class="srm-profile-sidebar">
        <!-- تصویر پروفایل -->
        <div class="srm-profile-picture">
            <?php echo get_avatar($user_id, 150); ?>
            <?php if (current_user_can('manage_options')): ?>
                <button class="srm-change-avatar">تغییر عکس</button>
            <?php endif; ?>
        </div>
        
        <!-- تخصص‌های شنا -->
<div class="srm-swim-specialties">
    <h3 class="srm-section-title">تخصص‌های شنا</h3>
    <div class="srm-specialty-grid">
        <?php foreach (SRM_Swim_Manager::SWIM_STYLES as $style => $name): 
            $images = SRM_Swim_Manager::get_swim_style_images($style);
            $is_active = in_array($style, $user_skills);
        ?>
        <div class="srm-specialty-item <?php echo $is_active ? 'is-active' : ''; ?>" 
             data-skill="<?php echo esc_attr($style); ?>">
            <div class="srm-specialty-image-container">
                <img src="<?php echo esc_url($images['off']); ?>" 
                     class="srm-specialty-image srm-specialty-off" 
                     alt="<?php echo esc_attr($name); ?>">
                <img src="<?php echo esc_url($images['on']); ?>" 
                     class="srm-specialty-image srm-specialty-on" 
                     alt="<?php echo esc_attr($name); ?>">
            </div>
            <span class="srm-specialty-name"><?php echo esc_html($name); ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>
        <!-- سطح شناگر -->
        <div class="srm-swimmer-level">
            <h3 class="srm-section-title">سطح شناگر</h3>
            <div class="srm-level-badge srm-level-<?php echo esc_attr($user_level); ?>">
                <?php echo esc_html($level_labels[$user_level] ?? $user_level); ?>
            </div>
        </div>
        
        <!-- منوی پروفایل -->
        <nav class="srm-profile-menu">
            <ul class="srm-menu-list">
                <li class="srm-menu-item is-active" data-section="user-info">
                    <span class="srm-menu-icon dashicons dashicons-admin-users"></span>
                    <span class="srm-menu-text">مشخصات کاربر</span>
                </li>
                <li class="srm-menu-item" data-section="swim-records">
                    <span class="srm-menu-icon dashicons dashicons-chart-bar"></span>
                    <span class="srm-menu-text">رکوردهای شناگر</span>
                </li>
                <li class="srm-menu-item" data-section="achievements">
                    <span class="srm-menu-icon dashicons dashicons-awards"></span>
                    <span class="srm-menu-text">دستاوردها</span>
                </li>
                <li class="srm-menu-item" data-section="financial-status">
                    <span class="srm-menu-icon dashicons dashicons-money-alt"></span>
                    <span class="srm-menu-text">وضعیت مالی</span>
                </li>
                <li class="srm-menu-item srm-logout-item">
                    <a href="<?php echo wp_logout_url(); ?>" class="srm-logout-link">
                        <span class="srm-menu-icon dashicons dashicons-exit"></span>
                        <span class="srm-menu-text">خروج از حساب کاربری</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    
    <!-- ستون سمت چپ (75%) -->
    <div class="srm-profile-main-content">
        <!-- بخش مشخصات کاربر -->
        <div id="user-info" class="srm-content-section is-active">
            <h2 class="srm-content-title">مشخصات کاربر</h2>
            <div class="srm-user-details">
                <div class="srm-detail-row">
                    <span class="srm-detail-label">نام کامل:</span>
                    <span class="srm-detail-value"><?php echo esc_html($user_data->full_name); ?></span>
                </div>
                <div class="srm-detail-row">
                    <span class="srm-detail-label">کد ملی:</span>
                    <span class="srm-detail-value"><?php echo esc_html($user_data->national_code); ?></span>
                </div>
                <div class="srm-detail-row">
                    <span class="srm-detail-label">تاریخ ثبت نام:</span>
                    <span class="srm-detail-value"><?php echo date_i18n('j F Y', strtotime($user_data->created_at)); ?></span>
                </div>
                <div class="srm-detail-row">
                    <span class="srm-detail-label">سطح فعلی:</span>
                    <span class="srm-detail-value srm-level-badge srm-level-<?php echo esc_attr($user_level); ?>">
                        <?php echo esc_html($level_labels[$user_level] ?? $user_level); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- بخش رکوردهای شنا -->
        <div id="swim-records" class="srm-content-section">
            <h2 class="srm-content-title">رکوردهای شنا</h2>
            <table class="srm-records-table">
                <thead>
                    <tr>
                        <th class="srm-record-style">سبک شنا</th>
                        <th class="srm-record-time">بهترین رکورد (ثانیه)</th>
                        <th class="srm-record-date">تاریخ ثبت</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($swim_records as $style => $record): 
                        if ($record > 0): ?>
                        <tr class="srm-record-row">
                            <td class="srm-record-style">
                                <?php echo esc_html(SRM_Swim_Manager::SWIM_STYLES[$style] ?? $style); ?>
                            </td>
                            <td class="srm-record-time">
                                <?php echo number_format($record, 2); ?>
                            </td>
                            <td class="srm-record-date">
                                <?php 
                                $record_date = get_user_meta($user_id, "srm_{$style}_record_date", true);
                                echo $record_date ? date_i18n('Y/m/d', strtotime($record_date)) : '--';
                                ?>
                            </td>
                        </tr>
                        <?php endif;
                    endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- بخش دستاوردها -->
        <div id="achievements" class="srm-content-section">
            <h2 class="srm-content-title">دستاوردها</h2>
            <div class="srm-achievements-grid">
                <!-- از طریق AJAX پر می‌شود -->
                <div class="srm-loading-achievements">
                    <span class="dashicons dashicons-update spin"></span>
                    در حال بارگذاری دستاوردها...
                </div>
            </div>
        </div>
        
        <!-- بخش وضعیت مالی -->
        <div id="financial-status" class="srm-content-section">
            <h2 class="srm-content-title">وضعیت مالی</h2>
            <div class="srm-financial-cards">
                <!-- از طریق AJAX پر می‌شود -->
                <div class="srm-loading-financial">
                    <span class="dashicons dashicons-update spin"></span>
                    در حال بارگذاری وضعیت مالی...
                </div>
            </div>
        </div>
    </div>
</div>