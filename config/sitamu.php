<?php

/*
| Konfigurasi aplikasi SiTamu (PRD Bab 5.4). Nilai [DEFAULT] dibaca dari .env
| agar dapat diubah tanpa menyentuh logika. Kode lain memakai config('sitamu.*').
*/

return [

    'institution_name' => env('INSTITUTION_NAME', 'Dinas Perindustrian dan Perdagangan Provinsi Jawa Timur (Disperindag)'),

    'login' => [
        'max_attempts' => (int) env('LOGIN_MAX_ATTEMPTS', 5),
        'lock_minutes' => (int) env('LOGIN_LOCK_MINUTES', 15),
        'rate_limit_max' => (int) env('LOGIN_RATE_LIMIT_MAX', 20),
        'rate_limit_window_minutes' => (int) env('LOGIN_RATE_LIMIT_WINDOW_MINUTES', 15),
    ],

    'guest' => [
        'rate_limit_max' => (int) env('GUEST_RATE_LIMIT_MAX', 5),
        'rate_limit_window_minutes' => (int) env('GUEST_RATE_LIMIT_WINDOW_MINUTES', 10),
        'google_verify_rate_limit_max' => (int) env('GOOGLE_VERIFY_RATE_LIMIT_MAX', 10),
        'email_verify_ttl_minutes' => (int) env('EMAIL_VERIFY_TTL_MINUTES', 30),
        'history_per_page' => (int) env('GUEST_HISTORY_PER_PAGE', 10),
    ],

    'google_client_id' => env('GOOGLE_CLIENT_ID'),

    'poll_interval_seconds' => (int) env('POLL_INTERVAL_SECONDS', 30),

    'waiting_alert_minutes' => (int) env('WAITING_ALERT_MINUTES', 15),

    'signature_max_kb' => (int) env('SIGNATURE_MAX_KB', 200),

    'export' => [
        'csv_delimiter' => env('CSV_DELIMITER', ';'),
        'rich_max_rows' => (int) env('EXPORT_RICH_MAX_ROWS', 500),
    ],

];
