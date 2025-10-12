<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi ini mengizinkan frontend (seperti Ionic Angular) mengakses
    | Laravel API yang berjalan di domain/port berbeda. Sangat penting untuk
    | mobile app dan pengembangan lokal.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'supports_credentials' => true,

    'allowed_methods' => ['*'], // Mengizinkan semua HTTP method

    // ❗ Gunakan wildcard (*) SAJA atau daftar spesifik SAJA — jangan campur!
    // Versi PRODUKSI — whitelist domain spesifik:
        'allowed_origins' => [
        'http://localhost:8100',           // Ionic serve (dev browser)
        'capacitor://localhost',           // Android/iOS via Capacitor
        'http://192.168.1.10:8100',        // Emulator/device LAN
        'https://yourdomain.com',          // Domain produksi
    ],

    // Versi PENGEMBANGAN — izinkan semuanya (jika perlu):
    // 'allowed_origins' => ['*'],        // ← Gunakan ini HANYA saat dev lokal

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],           // Semua header diperbolehkan

    'exposed_headers' => [],

    'max_age' => 0,
];
