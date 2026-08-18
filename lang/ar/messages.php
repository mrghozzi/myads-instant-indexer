<?php

return [
    'plugin_name' => 'الأرشفة اللحظية المباشرة',
    'plugin_description' => 'أرشفة المواضيع والفيديوهات والمنتجات لحظياً في محركات البحث Google و Bing و Yandex فور نشرها.',
    'dashboard_title' => 'الأرشفة اللحظية (Instant Indexer)',
    'dashboard_eyebrow' => 'Instant Search Engine Indexing',
    'dashboard_desc' => 'أداة الإرسال اللحظي المباشر لمحركات البحث العالمية (Google Indexing API و IndexNow لـ Bing و Yandex و Seznam) لتسريع الفهرسة وظهور المحتوى الجديد فوراً.',

    // Stats
    'stat_total_submissions' => 'إجمالي الإرسالات',
    'stat_today_submissions' => 'إرسالات اليوم',
    'stat_google_rate' => 'معدل نجاح Google',
    'stat_indexnow_rate' => 'معدل نجاح IndexNow',
    'stat_total_errors' => 'الأخطاء المسجلة',

    // Tabs
    'tab_overview' => 'لوحة المراقبة والسجلات',
    'tab_submitter' => 'أداة الإرسال الفوري',
    'tab_candidates' => 'الأرشفة السريعة من المنصة',
    'tab_settings' => 'إعدادات المحركات والمفاتيح',
    'tab_docs' => 'دليل الربط والتعليمات',

    // Submitter
    'submit_urls_title' => 'إرسال روابط محددة لمحركات البحث',
    'submit_urls_desc' => 'أدخل رابطاً واحداً أو عدة روابط (رابط في كل سطر) لإرسالها فورياً إلى Google أو IndexNow أو كلاهما.',
    'urls_input_label' => 'قائمة الروابط (URL List)',
    'urls_input_placeholder' => "https://yourdomain.com/t123\nhttps://yourdomain.com/video\nhttps://yourdomain.com/store/item",
    'action_label' => 'نوع الإجراء',
    'action_updated' => 'نشر أو تحديث الرابط (URL_UPDATED)',
    'action_deleted' => 'إشعار حذف الرابط (URL_DELETED)',
    'engines_select_label' => 'محركات البحث المستهدفة',
    'engine_google' => 'Google Indexing API',
    'engine_indexnow' => 'IndexNow Hub (Bing, Yandex, Seznam)',
    'btn_submit_now' => 'إرسال الروابط الآن ⚡',

    // Candidate Content
    'candidates_title' => 'المحتوى المنشور حديثاً في المنصة',
    'candidates_desc' => 'استعرض أحدث المواضيع والفيديوهات والمنتجات المنشورة واختر ما ترغب بأرشفته فوراً بنقرة زر.',
    'btn_index_selected' => 'أرشفة الروابط المحددة',
    'select_all' => 'تحديد الكل',
    'status_indexed' => 'تمت الأرشفة',
    'status_not_indexed' => 'لم يؤرشف بعد',

    // Settings
    'settings_google_title' => 'إعدادات Google Indexing API',
    'settings_google_desc' => 'يتطلب حساب خدمة في Google Cloud Console مع صلاحيات Google Indexing API وتفويض الحساب في Google Search Console.',
    'google_enable_toggle' => 'تفعيل الأرشفة التلقائية عبر Google Indexing API',
    'google_json_label' => 'ملف بيانات حساب الخدمة (Service Account JSON)',
    'google_json_placeholder' => 'الصق محتوى ملف JSON هنا...',
    'google_json_file_label' => 'أو ارفع ملف الـ JSON مباشرة',
    'btn_test_google' => 'فحص اتصال Google 🔍',

    'settings_indexnow_title' => 'إعدادات بروتوكول IndexNow',
    'settings_indexnow_desc' => 'مدعوم رسمياً من Microsoft Bing و Yandex و Seznam و Naver لإشعار المحركات لحظياً بتغييرات الروابط.',
    'indexnow_enable_toggle' => 'تفعيل الأرشفة التلقائية عبر IndexNow',
    'indexnow_key_label' => 'مفتاح التحقق (IndexNow API Key)',
    'btn_generate_key' => 'توليد مفتاح جديد 🎲',
    'indexnow_host_label' => 'اسم النطاق / المضيف (Host)',
    'key_location_label' => 'رابط التحقق من المفتاح (Key Location)',
    'btn_test_indexnow' => 'فحص اتصال IndexNow 🔍',

    'settings_triggers_title' => 'مشغلات الأرشفة التلقائية (Automated Triggers)',
    'settings_triggers_desc' => 'حدد أنواع المحتوى التي ترغب بإرسال روابطها فورياً لمحركات البحث بمجرد نشرها.',
    'trigger_topic' => 'أرشفة مواضيع المنتدى الجديدة فور النشر',
    'trigger_video' => 'أرشفة مقاطع الفيديو والشورتس الجديدة فور النشر',
    'trigger_product' => 'أرشفة منتجات المتجر والخدمات الجديدة',
    'trigger_news' => 'أرشفة المقالات والأخبار الجديدة',
    'trigger_directory' => 'أرشفة مواقع الدليل الجديدة',
    'trigger_page' => 'أرشفة الصفحات الثابتة والمخصصة عند الإنشاء أو التعديل',
    'trigger_delete' => 'إرسال إشعار حذف الرابط (URL_DELETED) عند حذف المحتوى',
    'btn_save_settings' => 'حفظ الإعدادات والمشغلات',

    // Logs
    'logs_title' => 'سجل عمليات الأرشفة في الوقت الفعلي',
    'logs_filter_all' => 'جميع المحركات',
    'logs_filter_status_all' => 'جميع الحالات',
    'logs_search_placeholder' => 'بحث بالرابط أو الحالة...',
    'col_url' => 'الرابط',
    'col_engine' => 'المحرك',
    'col_source' => 'المصدر',
    'col_status' => 'الحالة',
    'col_code' => 'رمز HTTP',
    'col_duration' => 'الوقت',
    'col_date' => 'التاريخ',
    'col_actions' => 'إجراءات',
    'btn_view_response' => 'تفاصيل الاستجابة',
    'btn_retry' => 'إعادة المحاولة',
    'btn_clear_logs' => 'مسح السجلات',
    'btn_export_csv' => 'تصدير CSV',

    // Messages
    'settings_saved_success' => 'تم حفظ إعدادات الأرشفة والمشغلات بنجاح.',
    'key_generated_success' => 'تم توليد مفتاح IndexNow جديد وحفظه وتحديث ملف التحقق.',
    'no_valid_urls_found' => 'لم يتم العثور على أي روابط صالحة للإرسال.',
    'url_submitted_success' => 'تم إرسال الرابط بنجاح إلى محركات البحث المحددة.',
    'url_submitted_with_errors' => 'تم إرسال الرابط مع وجود أخطاء: :msg',
    'batch_submitted_success' => 'تمت معالجة إرسال :count رابط دفعة واحدة بنجاح.',
    'no_candidate_selected' => 'يرجى تحديد رابط واحد على الأقل للإرسال.',
    'retry_success' => 'تمت إعادة محاولة إرسال الرابط بنجاح.',
    'retry_failed' => 'فشلت إعادة محاولة إرسال الرابط. يرجى مراجعة تفاصيل الخطأ.',
    'logs_cleared_success' => 'تم تنظيف سجلات الأرشفة بنجاح.',
];
