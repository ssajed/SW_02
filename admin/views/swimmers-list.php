<div class="wrap srm-admin">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-groups"></span>
        مدیریت شناگران
    </h1>
    
    <a href="#" id="srm-add-new" class="page-title-action">
        افزودن شناگر جدید
    </a>
    
    <hr class="wp-header-end">
    
    <!-- فرم افزودن شناگر جدید -->
    <div id="srm-add-form" class="srm-card" style="display: none;">
        <h2>افزودن شناگر جدید</h2>
        <form id="srm-add-swimmer-form">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="srm-first-name">نام</label></th>
                    <td><input type="text" id="srm-first-name" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="srm-last-name">نام خانوادگی</label></th>
                    <td><input type="text" id="srm-last-name" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="srm-national-code">کد ملی</label></th>
                    <td>
                        <input type="text" id="srm-national-code" class="regular-text" 
                               pattern="\d{10}" title="کد ملی باید 10 رقمی باشد" required>
                    </td>
                </tr>
                <tr>
    <th scope="row"><label>سطح مهارت</label></th>
    <td>
        <div class="srm-level-options">
            <?php 
            $levels = get_option('srm_swimmers_levels', [
                'training' => 'آموزشی',
                'C' => 'سطح C',
                'B' => 'سطح B', 
                'A' => 'سطح A',
                'A+' => 'سطح A+'
            ]);
            
            $first = true;
            foreach ($levels as $value => $label): 
            ?>
            <label class="srm-level-option">
                <input 
                    type="radio" 
                    name="srm-level" 
                    value="<?php echo esc_attr($value); ?>" 
                    <?php echo $first ? 'checked' : ''; ?>
                    required
                >
                <span class="srm-level-label"><?php echo esc_html($label); ?></span>
            </label>
            <?php 
            $first = false;
            endforeach; 
            ?>
        </div>
    </td>
</tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary">ثبت شناگر</button>
                <button type="button" id="srm-cancel-add" class="button">انصراف</button>
            </p>
        </form>
    </div>
    
    <!-- لیست شناگران -->
    <div class="srm-card">
        <h2>لیست شناگران</h2>
        
        <div class="srm-list-header">
            <div class="srm-search-box">
                <input type="search" id="srm-search-input" placeholder="جستجو...">
                <button type="button" id="srm-search-btn" class="button">جستجو</button>
            </div>
        </div>
        
        <div class="srm-list-container">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>نام کامل</th>
                        <th>کد ملی</th>
                        <th>سطح مهارت</th>
                        <th>تاریخ ثبت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody id="srm-swimmers-list">
                    <!-- محتوای از طریق AJAX بارگذاری می‌شود -->
                </tbody>
            </table>
            
            <div class="srm-pagination" id="srm-pagination">
                <!-- صفحه‌بندی از طریق AJAX بارگذاری می‌شود -->
            </div>
        </div>
    </div>
</div>