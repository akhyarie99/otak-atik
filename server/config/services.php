<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // WhatsApp lewat Fonnte (PRD 6.8 & 9.2) — token belum tersedia di
    // lingkungan ini. FonnteService no-op dengan aman kalau kosong,
    // lihat catatan di app/Services/FonnteService.php.
    'fonnte' => [
        'token' => env('FONNTE_TOKEN'),
        'url' => env('FONNTE_URL', 'https://api.fonnte.com/send'),
    ],

    // Midtrans — virtual account & transfer bank (milestone 7.2, PRD
    // 9.2). Akun/kredensial SUNGGUHAN belum ada untuk proyek ini —
    // MidtransService mensimulasikan transaksi dengan aman kalau
    // server_key kosong, lihat catatan di app/Services/MidtransService.php.
    'midtrans' => [
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'produksi' => env('MIDTRANS_PRODUKSI', false),
    ],

];
