jQuery(document).ready(function($) {
    // متغیرهای global
    let currentPage = 1;
    let currentSearch = '';

    // بارگذاری اولیه لیست شناگران
    loadSwimmers();

    // نمایش فرم
    $('#srm-add-new').on('click', function(e) {
        e.preventDefault();
        $('#srm-add-form').slideToggle();
    });

    $('#srm-cancel-add').on('click', function() {
        $('#srm-add-form').slideUp();
    });

    // ارسال فرم
    $('#srm-add-swimmer-form').on('submit', function(e) {
        e.preventDefault();
        
        // اعتبارسنجی فیلدها
        const first_name = $('#srm-first-name').val().trim();
        const last_name = $('#srm-last-name').val().trim();
        const national_code = $('#srm-national-code').val().trim();
        const level = $('input[name="srm-level"]:checked').val();

        if (!first_name || !last_name || !national_code) {
            alert('لطفاً تمام فیلدهای ضروری را پر کنید!');
            return;
        }

        if (!/^\d{10}$/.test(national_code)) {
            alert('کد ملی باید 10 رقمی باشد!');
            return;
        }

        const $submitBtn = $(this).find('button[type="submit"]');
        $submitBtn.prop('disabled', true).html('<span class="spinner is-active"></span> در حال ثبت...');

        $.ajax({
            url: srm_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'srm_add_swimmer',
                first_name: first_name,
                last_name: last_name,
                national_code: national_code,
                level: level,
                security: srm_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('شناگر با موفقیت ثبت شد!');
                    $('#srm-add-swimmer-form')[0].reset();
                    $('#srm-add-form').slideUp();
                    loadSwimmers();
                } else {
                    alert('خطا: ' + (response.data || 'عملیات ناموفق بود'));
                }
            },
            error: function(xhr) {
                alert('خطای سرور: ' + xhr.responseText);
            },
            complete: function() {
                $submitBtn.prop('disabled', false).text('ثبت شناگر');
            }
        });
    });

    // جستجو
    $('#srm-search-btn').on('click', function() {
        currentSearch = $('#srm-search-input').val();
        loadSwimmers(1, currentSearch);
    });

    $('#srm-search-input').on('keypress', function(e) {
        if (e.which === 13) {
            currentSearch = $(this).val();
            loadSwimmers(1, currentSearch);
        }
    });

    // بارگذاری لیست شناگران
    function loadSwimmers(page = 1, search = '') {
        currentPage = page;
        currentSearch = search || currentSearch;

        $('#srm-swimmers-list').html('<tr><td colspan="5"><div class="srm-loader">در حال بارگذاری...</div></td></tr>');

        $.ajax({
            url: srm_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'srm_get_swimmers',
                page: currentPage,
                search: currentSearch,
                security: srm_admin.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    renderSwimmers(response.data.items);
                    renderPagination(response.data.pagination);
                } else {
                    $('#srm-swimmers-list').html('<tr><td colspan="5">خطا در دریافت داده‌ها</td></tr>');
                }
            },
            error: function() {
                $('#srm-swimmers-list').html('<tr><td colspan="5">خطا در اتصال به سرور</td></tr>');
            }
        });
    }

    // نمایش لیست
    function renderSwimmers(items) {
        const $tbody = $('#srm-swimmers-list');
        $tbody.empty();

        if (!items || items.length === 0) {
            $tbody.append('<tr><td colspan="5">هیچ شناگری یافت نشد</td></tr>');
            return;
        }

        items.forEach(function(item) {
            const levelLabel = srm_admin.levels[item.level] || 'نامشخص';
            const levelClass = getLevelClass(item.level);
            const date = new Date(item.created_at);
            const persianDate = date.toLocaleDateString('fa-IR');

            $tbody.append(`
                <tr>
                    <td>${item.full_name || ''}</td>
                    <td>${item.national_code || ''}</td>
                    <td><span class="srm-badge srm-badge-${levelClass}">${levelLabel}</span></td>
                    <td>${persianDate}</td>
                    <td>
                        <button class="button srm-edit-swimmer" data-id="${item.swimmer_id}">
                            <span class="dashicons dashicons-edit"></span> ویرایش
                        </button>
                        <button class="button srm-delete-swimmer" data-id="${item.swimmer_id}">
                            <span class="dashicons dashicons-trash"></span> حذف
                        </button>
                    </td>
                </tr>
            `);
        });

        // اضافه کردن event handlerها
        $('.srm-edit-swimmer').on('click', function(e) {
            e.preventDefault();
            editSwimmer($(this).data('id'));
        });

        $('.srm-delete-swimmer').on('click', function(e) {
            e.preventDefault();
            deleteSwimmer($(this).data('id'));
        });
    }

    // کلاس‌های سطح مهارت
    function getLevelClass(level) {
        const classes = {
            'training': 'info',
            'C': 'success',
            'B': 'warning',
            'A': 'primary',
            'A+': 'danger'
        };
        return classes[level] || 'secondary';
    }

    // صفحه‌بندی
    function renderPagination(pagination) {
        const $pagination = $('#srm-pagination');
        $pagination.empty();

        if (!pagination || pagination.max_pages <= 1) return;

        let html = `
            <div class="tablenav-pages">
                <span class="displaying-num">${pagination.total} شناگر</span>
                <span class="pagination-links">
        `;

        // دکمه اول
        if (pagination.current_page > 1) {
            html += `
                <button class="button first-page" data-page="1">« اولین</button>
                <button class="button prev-page" data-page="${pagination.current_page - 1}">‹ قبلی</button>
            `;
        }

        html += `<span class="current-page">صفحه ${pagination.current_page} از ${pagination.max_pages}</span>`;

        // دکمه آخر
        if (pagination.current_page < pagination.max_pages) {
            html += `
                <button class="button next-page" data-page="${pagination.current_page + 1}">بعدی ›</button>
                <button class="button last-page" data-page="${pagination.max_pages}">آخرین »</button>
            `;
        }

        html += `</span></div>`;
        $pagination.html(html);

        // event handlerهای صفحه‌بندی
        $('.first-page, .prev-page, .next-page, .last-page').on('click', function() {
            loadSwimmers($(this).data('page'), currentSearch);
        });
    }

    // ویرایش شناگر
    function editSwimmer(swimmerId) {
        alert('ویژگی ویرایش برای شناگر با شناسه ' + swimmerId + ' در حال توسعه است');
    }

    // حذف شناگر
    function deleteSwimmer(swimmerId) {
        if (!confirm('آیا از حذف این شناگر مطمئن هستید؟')) return;
    
        $.ajax({
            url: srm_admin.ajax_url,
            type: 'POST',
            dataType: 'json', // اضافه کردن این خط
            data: {
                action: 'srm_delete_swimmer',
                swimmer_id: swimmerId,
                security: srm_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('شناگر با موفقیت حذف شد!');
                    loadSwimmers(currentPage, currentSearch);
                } else {
                    alert('خطا: ' + (response.data || response.message || 'عملیات ناموفق بود'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', xhr.responseText);
                alert('خطای ارتباط با سرور. لطفاً کنسول مرورگر را بررسی کنید.');
            }
        });
    }
});