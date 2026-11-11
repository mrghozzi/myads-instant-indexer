<?php

return [
    'plugin_name' => 'Instant Indexer',
    'plugin_description' => 'Real-time automatic URL indexing for Google, Bing, Yandex and Seznam search engines using Google Indexing API & IndexNow.',
    'dashboard_title' => 'Instant Indexer',
    'dashboard_eyebrow' => 'Search Engine Realtime Indexing',
    'dashboard_desc' => 'Instant search engine push protocol integration for Google Indexing API & IndexNow to accelerate crawling and index fresh content instantly.',

    // Stats
    'stat_total_submissions' => 'Total Submissions',
    'stat_today_submissions' => 'Today Submissions',
    'stat_google_rate' => 'Google Success Rate',
    'stat_indexnow_rate' => 'IndexNow Success Rate',
    'stat_total_errors' => 'Errors Logged',

    // Tabs
    'tab_overview' => 'Overview & Logs',
    'tab_submitter' => 'Instant Submitter',
    'tab_candidates' => 'Quick System Indexer',
    'tab_settings' => 'Engines & API Settings',
    'tab_docs' => 'Setup Guide & Docs',

    // Submitter
    'submit_urls_title' => 'Submit URLs to Search Engines',
    'submit_urls_desc' => 'Enter one or multiple URLs (one per line) to instantly push to Google, IndexNow, or both.',
    'urls_input_label' => 'URL List',
    'urls_input_placeholder' => "https://yourdomain.com/t123\nhttps://yourdomain.com/video\nhttps://yourdomain.com/store/item",
    'action_label' => 'Action Type',
    'action_updated' => 'Publish or Update (URL_UPDATED)',
    'action_deleted' => 'Delete URL (URL_DELETED)',
    'engines_select_label' => 'Target Engines',
    'engine_google' => 'Google Indexing API',
    'engine_indexnow' => 'IndexNow Hub (Bing, Yandex, Seznam)',
    'btn_submit_now' => 'Submit URLs Now ⚡',

    // Candidate Content
    'candidates_title' => 'Recently Published Platform Content',
    'candidates_desc' => 'Browse the latest topics, videos, and products and index them in bulk with a single click.',
    'btn_index_selected' => 'Index Selected URLs',
    'select_all' => 'Select All',
    'status_indexed' => 'Indexed',
    'status_not_indexed' => 'Not Indexed Yet',

    // Settings
    'settings_google_title' => 'Google Indexing API Settings',
    'settings_google_desc' => 'Requires a Google Cloud Service Account with Google Indexing API enabled and ownership/delegation in Google Search Console.',
    'google_enable_toggle' => 'Enable Automatic Google Indexing API',
    'google_json_label' => 'Service Account Credentials JSON',
    'google_json_placeholder' => 'Paste your service account JSON file contents here...',
    'google_json_file_label' => 'Or upload JSON file directly',
    'btn_test_google' => 'Test Google Connection 🔍',

    'settings_indexnow_title' => 'IndexNow Protocol Settings',
    'settings_indexnow_desc' => 'Supported by Microsoft Bing, Yandex, Seznam, and Naver to instantly notify engines of URL changes.',
    'indexnow_enable_toggle' => 'Enable Automatic IndexNow Protocol',
    'indexnow_key_label' => 'IndexNow API Key',
    'btn_generate_key' => 'Generate New Key 🎲',
    'indexnow_host_label' => 'Domain Host',
    'key_location_label' => 'Key Location URL',
    'btn_test_indexnow' => 'Test IndexNow Connection 🔍',

    'settings_triggers_title' => 'Automated Indexing Triggers',
    'settings_triggers_desc' => 'Choose which content types will be automatically pushed to search engines upon publication.',
    'trigger_topic' => 'Auto-index new Forum Topics on creation',
    'trigger_video' => 'Auto-index new Videos and Clips on creation',
    'trigger_product' => 'Auto-index new Store Products on creation',
    'trigger_news' => 'Auto-index new News and Articles',
    'trigger_directory' => 'Auto-index new Directory listings',
    'trigger_page' => 'Auto-index Custom/Static Pages on save',
    'trigger_group' => 'Auto-index public Community Groups on creation',
    'trigger_delete' => 'Send URL_DELETED notification when content is deleted',
    'btn_save_settings' => 'Save Configuration & Triggers',

    // Logs
    'logs_title' => 'Real-Time Indexing Activity Logs',
    'logs_filter_all' => 'All Engines',
    'logs_filter_status_all' => 'All Statuses',
    'logs_search_placeholder' => 'Search by URL or status...',
    'col_url' => 'URL',
    'col_engine' => 'Engine',
    'col_source' => 'Source',
    'col_status' => 'Status',
    'col_code' => 'HTTP Code',
    'col_duration' => 'Time',
    'col_date' => 'Date',
    'col_actions' => 'Actions',
    'btn_view_response' => 'View Response',
    'btn_retry' => 'Retry',
    'btn_clear_logs' => 'Clear Logs',
    'btn_export_csv' => 'Export CSV',

    // Messages
    'settings_saved_success' => 'Indexer settings and triggers saved successfully.',
    'key_generated_success' => 'New IndexNow key generated and verification file updated.',
    'no_valid_urls_found' => 'No valid URLs were found to submit.',
    'url_submitted_success' => 'URL submitted successfully to search engines.',
    'url_submitted_with_errors' => 'URL submitted with warnings/errors: :msg',
    'batch_submitted_success' => 'Successfully processed :count URLs in batch.',
    'no_candidate_selected' => 'Please select at least one URL to submit.',
    'retry_success' => 'URL submission retried successfully.',
    'retry_failed' => 'Failed to retry submission. Check error response.',
    'logs_cleared_success' => 'Indexing logs cleared successfully.',
];
