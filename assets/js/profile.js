jQuery(document).ready(function($) {
    // مدیریت انتخاب تخصص‌ها توسط مدیر
    if ($('body').hasClass('wp-admin')) {
        $('.srm-specialty-item').on('click', function() {
            const $item = $(this);
            const skill = $item.data('skill');
            
            // اگر قبلا انتخاب شده بود، غیرفعال می‌کنیم
            if ($item.hasClass('is-active')) {
                $item.removeClass('is-active');
            } 
            // اگر کمتر از 4 تا انتخاب شده، فعال می‌کنیم
            else if ($('.srm-specialty-item.is-active').length < 4) {
                $item.addClass('is-active');
            }
            
            // جمع‌آوری تخصص‌های انتخاب شده
            const selectedSkills = [];
            $('.srm-specialty-item.is-active').each(function() {
                selectedSkills.push($(this).data('skill'));
            });
            
            // ارسال به سرور
            $.ajax({
                url: srm_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'srm_update_swim_skills',
                    user_id: srm_profile.user_id,
                    skills: selectedSkills,
                    security: srm_admin.nonce
                },
                success: function(response) {
                    if (!response.success) {
                        alert('خطا در ذخیره تخصص‌ها');
                    }
                },
                error: function() {
                    alert('خطا در ارتباط با سرور');
                }
            });
        });
    }
});